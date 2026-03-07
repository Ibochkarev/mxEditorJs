<?php
/**
 * Auto-generated map for MxEditorJsTvContent
 *
 * @package mxeditorjs
 */
$xpdo_meta_map['MxEditorJsTvContent'] = [
    'package' => 'mxeditorjs',
    'version' => '3.0',
    'table' => 'mxeditorjs_tv_content',
    'extends' => 'xPDOSimpleObject',
    'tableMeta' => [
        'engine' => 'InnoDB',
    ],
    'fields' => [
        'resource_id' => null,
        'tmplvar_id' => null,
        'content_json' => null,
        'content_version' => 1,
        'content_hash' => '',
        'schema_version' => '2.31',
        'created_at' => null,
        'updated_at' => null,
        'created_by' => 0,
        'updated_by' => 0,
    ],
    'fieldMeta' => [
        'resource_id' => [
            'dbtype' => 'int',
            'precision' => '10',
            'attributes' => 'unsigned',
            'phptype' => 'integer',
            'null' => false,
        ],
        'tmplvar_id' => [
            'dbtype' => 'int',
            'precision' => '10',
            'attributes' => 'unsigned',
            'phptype' => 'integer',
            'null' => false,
        ],
        'content_json' => [
            'dbtype' => 'mediumtext',
            'phptype' => 'json',
            'null' => false,
        ],
        'content_version' => [
            'dbtype' => 'int',
            'precision' => '10',
            'attributes' => 'unsigned',
            'phptype' => 'integer',
            'null' => false,
            'default' => 1,
        ],
        'content_hash' => [
            'dbtype' => 'varchar',
            'precision' => '64',
            'phptype' => 'string',
            'null' => false,
            'default' => '',
        ],
        'schema_version' => [
            'dbtype' => 'varchar',
            'precision' => '16',
            'phptype' => 'string',
            'null' => false,
            'default' => '2.31',
        ],
        'created_at' => [
            'dbtype' => 'datetime',
            'phptype' => 'datetime',
            'null' => false,
        ],
        'updated_at' => [
            'dbtype' => 'datetime',
            'phptype' => 'datetime',
            'null' => false,
        ],
        'created_by' => [
            'dbtype' => 'int',
            'precision' => '10',
            'attributes' => 'unsigned',
            'phptype' => 'integer',
            'null' => false,
            'default' => 0,
        ],
        'updated_by' => [
            'dbtype' => 'int',
            'precision' => '10',
            'attributes' => 'unsigned',
            'phptype' => 'integer',
            'null' => false,
            'default' => 0,
        ],
    ],
    'indexes' => [
        'resource_tmplvar' => [
            'alias' => 'resource_tmplvar',
            'primary' => false,
            'unique' => true,
            'type' => 'BTREE',
            'columns' => [
                'resource_id' => [
                    'length' => '',
                    'collation' => 'A',
                    'null' => false,
                ],
                'tmplvar_id' => [
                    'length' => '',
                    'collation' => 'A',
                    'null' => false,
                ],
            ],
        ],
        'resource_id' => [
            'alias' => 'resource_id',
            'primary' => false,
            'unique' => false,
            'type' => 'BTREE',
            'columns' => [
                'resource_id' => [
                    'length' => '',
                    'collation' => 'A',
                    'null' => false,
                ],
            ],
        ],
    ],
];
