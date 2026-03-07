<?php
/**
 * mxEditorJs bootstrap
 *
 * @package mxeditorjs
 */

/** @var \MODX\Revolution\modX $modx */
$corePath = $modx->getOption(
    'mxeditorjs.core_path',
    null,
    $modx->getOption('core_path') . 'components/mxeditorjs/'
);

$modx->addPackage('mxeditorjs', $corePath . 'model/');

if ($modx->lexicon) {
    $modx->lexicon->load('mxeditorjs:default');
}
