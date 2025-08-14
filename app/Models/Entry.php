<?php

namespace Models;

class Entry extends \DB\Cortex
{
    protected $db = 'DB', $table = 'entries';

    protected $fieldConf = [
        'name' => [
            'type' => 'VARCHAR256',
            'required' => true,
            'unique' => false,
            'nullable' => false,
            'index' => true
        ],
        'description' => [
            'type' => 'TEXT',
            'required' => false,
            'unique' => false,
            'nullable' => false,
            'index' => false
        ],
        'url' => [
            'type' => 'VARCHAR256',
            'required' => true,
            'unique' => true,
            'nullable' => false,
            'index' => true
        ],
        'category' => [
            'belongs-to-one' => 'models\Category',
            'required' => true,
            'unique' => false,
            'nullable' => false,
            'index' => false,
            'default' => 1
        ],
        'favicon' => [
            'type' => 'BLOB',
            'required' => false,
            'unique' => false,
            'nullable' => false,
            'index' => false
        ],
        'karma' => [
            'type' => 'INT4',
            'required' => true,
            'unique' => false,
            'nullable' => false,
            'index' => false,
            'default' => 0
        ],
        'author' => [
            'belongs-to-one' => 'models\User',
            'required' => true,
            'index' => true
        ],
        'tags' => [
            'belongs-to-many' => 'models\Tag',
            'required' => true,
            'unique' => false,
            'index' => true
        ]
    ];
}

