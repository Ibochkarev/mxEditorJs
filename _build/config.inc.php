<?php
/**
 * mxEditorJs Build Configuration
 *
 * @package mxeditorjs
 */

$path = dirname(__FILE__, 2);
while (!file_exists($path . '/core/config/config.inc.php') && (strlen($path) > 1)) {
    $path = dirname($path);
}
if (!defined('MODX_CORE_PATH')) {
    define('MODX_CORE_PATH', $path . '/core/');
}

return [
    'name' => 'mxEditorJs',
    'name_lower' => 'mxeditorjs',
    'version' => '1.1.0',
    'release' => 'beta1',

    // Install package to site right after build
    'install' => false,

    // Which elements should be updated on package upgrade
    'update' => [
        'plugins' => true,
        'settings' => false,
        'events' => true,
    ],

    // Which elements should be static by default
    'static' => [
        'plugins' => false,
    ],

    // Log settings
    'log_level' => !empty($_REQUEST['download']) ? 0 : 3,
    'log_target' => php_sapi_name() === 'cli' ? 'ECHO' : 'HTML',

    // Download transport.zip after build
    'download' => !empty($_REQUEST['download']),
];
