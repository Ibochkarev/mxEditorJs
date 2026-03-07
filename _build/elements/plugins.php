<?php
/**
 * Plugin definitions for mxEditorJs package
 *
 * @package mxeditorjs
 */

return [
    'mxEditorJs' => [
        'file' => 'mxeditorjs.plugin',
        'description' => 'mxeditorjs_plugin_desc',
        'events' => [
            'OnRichTextEditorRegister',
            'OnDocFormPrerender',
            'OnBeforeDocFormSave',
            'OnResourceDelete',
        ],
    ],
];
