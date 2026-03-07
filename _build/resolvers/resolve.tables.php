<?php
/**
 * Creates database tables for mxEditorJs
 *
 * @package mxeditorjs
 * @var \MODX\Revolution\Transport\modTransportPackage $transport
 * @var array $options
 */

use xPDO\Transport\xPDOTransport;

if (!$transport || !$transport->xpdo) {
    return false;
}

$modx =& $transport->xpdo;

switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:
        $corePath = $modx->getOption('core_path') . 'components/mxeditorjs/';
        $modx->addPackage('mxeditorjs', $corePath . 'model/');

        $manager = $modx->getManager();

        $tables = [
            'MxEditorJsContent',
            'MxEditorJsTvContent',
        ];

        foreach ($tables as $table) {
            $manager->createObjectContainer($table);
        }

        $modx->log(xPDO::LOG_LEVEL_INFO, 'Created/updated mxEditorJs tables.');
        break;

    case xPDOTransport::ACTION_UNINSTALL:
        $modx->log(xPDO::LOG_LEVEL_INFO, 'Uninstalling mxEditorJs. Tables preserved.');
        break;
}

return true;
