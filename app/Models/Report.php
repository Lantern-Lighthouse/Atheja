<?php

namespace Models;

class Report extends \DB\Cortex
{
    protected $db = 'DB', $table = 'reports';

    protected $fieldConf = [
        'reporter' => [
            'belongs-to-one' => 'Models\User',
            'required' => true,
            'index' => false
        ],
        'user_reported' => [
            'belongs-to-one' => 'Models\User',
            'required' => false,
            'unique' => false,
            'nullable' => true,
            'index' => true
        ],
        'entry_reported' => [
            'belongs-to-one' => 'Models\Entry',
            'required' => false,
            'unique' => false,
            'nullable' => true,
            'index' => true
        ],
        'reason' => [
            'type' => 'VARCHAR256',
            'required' => true,
            'unique' => false,
            'nullable' => false,
            'index' => false
        ],
        'created_at' => [
            'type' => 'DATETIME',
            'default' => \DB\SQL\Schema::DF_CURRENT_TIMESTAMP
        ],
        'updated_at' => [
            'type' => 'DATETIME'
        ],
        'resolver' => [
            'belongs-to-one' => 'Models\User',
            'required' => false,
            'unique' => false,
            'nullable' => false,
            'index' => false
        ],
        'resolved' => [
            'type' => 'BOOLEAN',
            'default' => 0,
            'required' => true
        ],
        'resolution' => [
            'type' => 'VARCHAR256',
            'required' => true,
            'unique' => false,
            'nullable' => true,
            'index' => false
        ]
    ];
}

