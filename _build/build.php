<?php
/**
 * mxEditorJs Build Script
 *
 * @package mxeditorjs
 */

declare(strict_types=1);

use MODX\Revolution\modCategory;
use MODX\Revolution\modEvent;
use MODX\Revolution\modPlugin;
use MODX\Revolution\modPluginEvent;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\modX;
use MODX\Revolution\Transport\modPackageBuilder;
use MODX\Revolution\Transport\modTransportPackage;
use xPDO\Transport\xPDOTransport;

set_time_limit(0);

class MxEditorJsPackage
{
    private modX $modx;
    private modPackageBuilder $builder;
    private modCategory $category;
    private array $config;
    private array $category_attributes;

    public function __construct(modX $modx, array $config)
    {
        $this->modx = $modx;
        $this->modx->initialize('mgr');

        $root = dirname(__FILE__, 2) . '/';
        $core = $root . 'core/components/' . $config['name_lower'] . '/';
        $assets = $root . 'assets/components/' . $config['name_lower'] . '/';

        $this->config = array_merge([
            'log_level' => modX::LOG_LEVEL_INFO,
            'log_target' => XPDO_CLI_MODE ? 'ECHO' : 'HTML',
            'root' => $root,
            'build' => $root . '_build/',
            'elements' => $root . '_build/elements/',
            'resolvers' => $root . '_build/resolvers/',
            'core' => $core,
            'assets' => $assets,
        ], $config);

        $this->modx->setLogLevel($this->config['log_level']);
        $this->modx->setLogTarget($this->config['log_target']);

        $this->initialize();
    }

    public function process(): modPackageBuilder
    {
        $elements = $this->getElementFiles();

        foreach ($elements as $element) {
            $name = preg_replace('#\.php$#', '', $element);
            if (method_exists($this, $name)) {
                $this->{$name}();
            }
        }

        $vehicle = $this->builder->createVehicle($this->category, $this->category_attributes);

        $vehicle->resolve('file', [
            'source' => $this->config['core'],
            'target' => "return MODX_CORE_PATH . 'components/';",
        ]);
        $vehicle->resolve('file', [
            'source' => $this->config['assets'],
            'target' => "return MODX_ASSETS_PATH . 'components/';",
        ]);

        $resolvers = $this->getResolverFiles();
        foreach ($resolvers as $resolver) {
            if ($vehicle->resolve('php', ['source' => $this->config['resolvers'] . $resolver])) {
                $this->modx->log(modX::LOG_LEVEL_INFO, 'Added resolver ' . preg_replace('#\.php$#', '', $resolver));
            }
        }

        $this->builder->putVehicle($vehicle);

        $this->builder->setPackageAttributes([
            'changelog' => $this->readDocFile('changelog.txt'),
            'license' => $this->readDocFile('license.txt'),
            'readme' => $this->readDocFile('readme.txt'),
            'requires' => [
                'php' => '>=8.2.0',
                'modx' => '>=3.0.3',
            ],
        ]);
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Added package attributes and setup options.');

        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packing up transport package zip...');
        $this->builder->pack();

        if (!empty($this->config['install'])) {
            $this->install();
        }

        return $this->builder;
    }

    private function initialize(): void
    {
        $this->builder = new modPackageBuilder($this->modx);
        $this->builder->createPackage($this->config['name_lower'], $this->config['version'], $this->config['release']);
        $this->builder->registerNamespace(
            $this->config['name_lower'],
            false,
            true,
            '{core_path}components/' . $this->config['name_lower'] . '/'
        );
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Created Transport Package and Namespace.');

        $this->category = $this->modx->newObject(modCategory::class);
        $this->category->set('category', $this->config['name']);
        $this->category_attributes = [
            xPDOTransport::UNIQUE_KEY => 'category',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [],
        ];
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Created main Category.');
    }

    private function getElementFiles(): array
    {
        if (!is_dir($this->config['elements'])) {
            return [];
        }
        return array_filter(
            scandir($this->config['elements']),
            fn($file) => !in_array($file[0], ['_', '.'], true)
        );
    }

    private function getResolverFiles(): array
    {
        if (!is_dir($this->config['resolvers'])) {
            return [];
        }
        return array_filter(
            scandir($this->config['resolvers']),
            fn($file) => !in_array($file[0], ['_', '.'], true)
        );
    }

    private function install(): void
    {
        $signature = $this->builder->getSignature();
        $sig = explode('-', $signature);
        $versionSignature = explode('.', $sig[1]);

        $package = $this->modx->getObject(modTransportPackage::class, ['signature' => $signature]);
        if (!$package) {
            $package = $this->modx->newObject(modTransportPackage::class);
            $package->set('signature', $signature);
            $package->fromArray([
                'created' => date('Y-m-d h:i:s'),
                'updated' => null,
                'state' => 1,
                'workspace' => 1,
                'provider' => 0,
                'source' => $signature . '.transport.zip',
                'package_name' => $this->config['name'],
                'version_major' => $versionSignature[0],
                'version_minor' => !empty($versionSignature[1]) ? $versionSignature[1] : 0,
                'version_patch' => !empty($versionSignature[2]) ? $versionSignature[2] : 0,
            ]);
            if (!empty($sig[2])) {
                $r = preg_split('#([0-9]+)#', $sig[2], -1, PREG_SPLIT_DELIM_CAPTURE);
                if (is_array($r) && !empty($r)) {
                    $package->set('release', $r[0]);
                    $package->set('release_index', $r[1] ?? '0');
                } else {
                    $package->set('release', $sig[2]);
                }
            }
            $package->save();
        }

        if ($package->install()) {
            $this->modx->runProcessor('System/ClearCache');
        }
    }

    private function settings(): void
    {
        $settingsFile = $this->config['elements'] . 'settings.php';
        if (!file_exists($settingsFile)) {
            return;
        }
        $settings = include $settingsFile;
        if (!is_array($settings)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in System Settings');
            return;
        }

        $attributes = [
            xPDOTransport::UNIQUE_KEY => 'key',
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['settings']),
            xPDOTransport::RELATED_OBJECTS => false,
        ];

        foreach ($settings as $name => $data) {
            $setting = $this->modx->newObject(modSystemSetting::class);
            $setting->fromArray(array_merge([
                'key' => $name,
                'namespace' => $this->config['name_lower'],
            ], $data), '', true, true);
            $vehicle = $this->builder->createVehicle($setting, $attributes);
            $this->builder->putVehicle($vehicle);
        }
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($settings) . ' System Settings');
    }

    private function plugins(): void
    {
        $pluginsFile = $this->config['elements'] . 'plugins.php';
        if (!file_exists($pluginsFile)) {
            return;
        }
        $plugins = include $pluginsFile;
        if (!is_array($plugins)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in Plugins');
            return;
        }

        $this->category_attributes[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Plugins'] = [
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['plugins']),
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                'PluginEvents' => [
                    xPDOTransport::PRESERVE_KEYS => true,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => ['pluginid', 'event'],
                ],
            ],
        ];

        $objects = [];
        foreach ($plugins as $name => $data) {
            $plugin = $this->modx->newObject(modPlugin::class);
            $filepath = $this->config['core'] . 'elements/plugins/' . $data['file'] . '.php';
            $plugin->fromArray(array_merge([
                'name' => $name,
                'category' => 0,
                'description' => $data['description'] ?? '',
                'plugincode' => $this->getFileContent($filepath),
                'static' => !empty($this->config['static']['plugins']),
                'source' => 1,
                'static_file' => 'core/components/' . $this->config['name_lower'] . '/elements/plugins/' . $data['file'] . '.php',
            ], $data), '', true, true);

            $events = [];
            if (!empty($data['events'])) {
                foreach ($data['events'] as $event_name) {
                    $event = $this->modx->newObject(modPluginEvent::class);
                    $event->fromArray([
                        'event' => $event_name,
                        'priority' => 0,
                        'propertyset' => 0,
                    ], '', true, true);
                    $events[] = $event;
                }
            }
            if (!empty($events)) {
                $plugin->addMany($events);
            }
            $objects[] = $plugin;
        }
        $this->category->addMany($objects);
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($objects) . ' Plugins');
    }

    private function events(): void
    {
        $eventsFile = $this->config['elements'] . 'events.php';
        if (!file_exists($eventsFile)) {
            return;
        }
        $events = include $eventsFile;
        if (!is_array($events)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in Events');
            return;
        }

        $attributes = [
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => $this->config['update']['events'] ?? false,
        ];

        foreach ($events as $name) {
            $event = $this->modx->newObject(modEvent::class);
            $event->fromArray([
                'name' => $name,
                'service' => 6,
                'groupname' => $this->config['name'],
            ], '', true, true);
            $vehicle = $this->builder->createVehicle($event, $attributes);
            $this->builder->putVehicle($vehicle);
        }
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($events) . ' Events');
    }

    private function readDocFile(string $filename): string
    {
        $filepath = $this->config['core'] . 'docs/' . $filename;

        if (!file_exists($filepath)) {
            $this->modx->log(modX::LOG_LEVEL_WARN, 'Documentation file not found: ' . $filepath);
            return '';
        }

        $content = file_get_contents($filepath);
        if ($content === false) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Failed to read documentation file: ' . $filepath);
            return '';
        }

        return $content;
    }

    private function getFileContent(string $filename): string
    {
        if (!file_exists($filename)) {
            $this->modx->log(modX::LOG_LEVEL_WARN, 'Element file not found: ' . $filename);
            return '';
        }

        $content = file_get_contents($filename);
        if ($content === false) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Failed to read element file: ' . $filename);
            return '';
        }

        $file = trim($content);

        return preg_match('#\<\?php(.*)#is', $file, $data)
            ? rtrim(rtrim(trim($data[1] ?? ''), '?>'))
            : $file;
    }
}

// Bootstrap
if (!file_exists(dirname(__FILE__) . '/config.inc.php')) {
    exit('Could not load config. Please check config.inc.php exists!');
}

$config = require dirname(__FILE__) . '/config.inc.php';
require_once MODX_CORE_PATH . 'vendor/autoload.php';

$modx = new modX();
$install = new MxEditorJsPackage($modx, $config);
$builder = $install->process();

if (!empty($config['download'])) {
    $name = $builder->getSignature() . '.transport.zip';
    $pkgPath = MODX_CORE_PATH . 'packages/' . $name;
    if (file_exists($pkgPath)) {
        $content = file_get_contents($pkgPath);
        if ($content !== false) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $name);
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . strlen($content));
            exit($content);
        }
    }
}
