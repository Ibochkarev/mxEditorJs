<?php
/**
 * Migrates system settings on package upgrade (profiles, available_tools).
 *
 * @package mxeditorjs
 * @var \MODX\Revolution\Transport\modTransportPackage $transport
 * @var array $options
 */

use MODX\Revolution\modSystemSetting;
use MxEditorJs\Config\EditorTools;
use xPDO\Transport\xPDOTransport;

if (!$transport || !$transport->xpdo) {
    return false;
}

$modx =& $transport->xpdo;

if (($options[xPDOTransport::PACKAGE_ACTION] ?? null) !== xPDOTransport::ACTION_UPGRADE) {
    return true;
}

$corePath = $modx->getOption('core_path') . 'components/mxeditorjs/';
require_once $corePath . 'src/Config/EditorTools.php';

$availableSetting = $modx->getObject(modSystemSetting::class, ['key' => 'mxeditorjs.available_tools']);
if ($availableSetting instanceof modSystemSetting) {
    $next = EditorTools::migrateAvailableTools((string) $availableSetting->get('value'));
    if ($next !== (string) $availableSetting->get('value')) {
        $availableSetting->set('value', $next);
        $availableSetting->save();
        $modx->log(xPDO::LOG_LEVEL_INFO, '[mxEditorJs] Updated mxeditorjs.available_tools with gallery.');
    }
}

$profilesSetting = $modx->getObject(modSystemSetting::class, ['key' => 'mxeditorjs.profiles']);
if ($profilesSetting instanceof modSystemSetting) {
    $profiles = json_decode((string) $profilesSetting->get('value'), true);
    if (!is_array($profiles)) {
        $profiles = [];
    }

    $migrated = EditorTools::migrateProfiles($profiles);
    $encoded = json_encode($migrated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded !== false && $encoded !== (string) $profilesSetting->get('value')) {
        $profilesSetting->set('value', $encoded);
        $profilesSetting->save();
        $modx->log(xPDO::LOG_LEVEL_INFO, '[mxEditorJs] Migrated mxeditorjs.profiles (gallery in default/full/blog).');
    }
}

return true;
