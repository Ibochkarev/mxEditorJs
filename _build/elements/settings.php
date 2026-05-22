<?php
/**
 * System settings for mxEditorJs package
 *
 * @package mxeditorjs
 */

return [
    'mxeditorjs.enabled' => [
        'value' => true,
        'xtype' => 'combo-boolean',
        'area' => 'mxeditorjs',
    ],
    'mxeditorjs.profile' => [
        'value' => 'default',
        'xtype' => 'textfield',
        'area' => 'mxeditorjs',
    ],
    'mxeditorjs.available_tools' => [
        'value' => 'paragraph,header,list,checklist,quote,table,code,raw,embed,image,gallery,attaches,delimiter,warning',
        'xtype' => 'textfield',
        'area' => 'mxeditorjs',
        'description' => 'Comma-separated list of all block tools. Use in mxeditorjs.profiles "tools" or mxeditorjs.enabled_tools.',
    ],
    'mxeditorjs.enabled_tools' => [
        'value' => '',
        'xtype' => 'textfield',
        'area' => 'mxeditorjs',
        'description' => 'Override profile: comma-separated tool keys. Empty = use profile tools. Example: paragraph,header,list,embed,image',
    ],
    'mxeditorjs.profiles' => [
        'value' => '{"default":{"tools":["paragraph","header","list","checklist","quote","table","code","raw","embed","image","gallery","attaches","delimiter","warning"]},"minimal":{"tools":["paragraph","header","list","image"]},"blog":{"tools":["paragraph","header","list","quote","image","gallery","embed","delimiter"]},"full":{"tools":["paragraph","header","list","checklist","quote","table","code","raw","embed","image","gallery","attaches","delimiter","warning"]}}',
        'xtype' => 'textarea',
        'area' => 'mxeditorjs',
    ],
    'mxeditorjs.image_mediasource' => [
        'value' => 1,
        'xtype' => 'textfield',
        'area' => 'mxeditorjs_media',
    ],
    'mxeditorjs.file_mediasource' => [
        'value' => 1,
        'xtype' => 'textfield',
        'area' => 'mxeditorjs_media',
    ],
    'mxeditorjs.image_upload_path' => [
        'value' => 'images/resources/{resource_id}/',
        'xtype' => 'textfield',
        'area' => 'mxeditorjs_media',
    ],
    'mxeditorjs.gallery_max_count' => [
        'value' => 0,
        'xtype' => 'numberfield',
        'area' => 'mxeditorjs_media',
    ],
    'mxeditorjs.file_upload_path' => [
        'value' => 'files/resources/{resource_id}/',
        'xtype' => 'textfield',
        'area' => 'mxeditorjs_media',
    ],
    'mxeditorjs.allowed_image_types' => [
        'value' => 'jpg,jpeg,png,gif,webp,svg',
        'xtype' => 'textfield',
        'area' => 'mxeditorjs_media',
    ],
    'mxeditorjs.max_upload_size' => [
        'value' => 5242880,
        'xtype' => 'numberfield',
        'area' => 'mxeditorjs_media',
    ],
    'mxeditorjs.image_class_presets' => [
        'value' => '{"default":"","full-width":"img-fluid w-100","thumbnail":"img-thumbnail","rounded":"rounded","circle":"rounded-circle","shadow":"shadow"}',
        'xtype' => 'textarea',
        'area' => 'mxeditorjs_presets',
    ],
    'mxeditorjs.link_class_presets' => [
        'value' => '{"default":"","button-primary":"btn btn-primary","button-secondary":"btn btn-secondary","button-outline":"btn btn-outline-primary","external":"external-link","download":"download-link"}',
        'xtype' => 'textarea',
        'area' => 'mxeditorjs_presets',
    ],
    'mxeditorjs.link_target_options' => [
        'value' => '{"_self":"Same window","_blank":"New window","_parent":"Parent frame","_top":"Top frame"}',
        'xtype' => 'textarea',
        'area' => 'mxeditorjs_presets',
    ],
    'mxeditorjs.link_rel_options' => [
        'value' => '{"":"None","nofollow":"nofollow","noopener":"noopener","noreferrer":"noreferrer","noopener noreferrer":"noopener noreferrer","sponsored":"sponsored","ugc":"ugc"}',
        'xtype' => 'textarea',
        'area' => 'mxeditorjs_presets',
    ],
];
