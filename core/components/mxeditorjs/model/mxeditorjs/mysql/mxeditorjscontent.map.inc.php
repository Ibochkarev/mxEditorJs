<?php
/**
 * Auto-generated map for MxEditorJsContent
 *
 * @package mxeditorjs
 */
$xpdo_meta_map['MxEditorJsContent'] = [
    'package' => 'mxeditorjs',
    'version' => '3.0',
    'table' => 'mxeditorjs_content',
    'extends' => 'xPDOSimpleObject',
    'tableMeta' => [
        'engine' => 'InnoDB',
    ],
    'fields' => [
        'resource_id' => null,
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
            'index' => 'unique',
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
        'resource_id' => [
            'alias' => 'resource_id',
            'primary' => false,
            'unique' => true,
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
