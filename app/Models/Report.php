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
        'user-reported' => [
            'belongs-to-one' => 'Models\User',
            'required' => false,
            'unique' => false,
            'nullable' => false,
            'index' => true
        ],
        'entry-reported' => [
            'belongs-to-one' => 'Models\Entry',
            'required' => false,
            'unique' => false,
            'nullable' => false,
            'index' => true
        ],
        'report_created_at' => [
            'type' => 'DATETIME',
            'default' => \DB\SQL\Schema::DF_CURRENT_TIMESTAMP
        ],
        'report_updated_at' => [
            'type' => 'DATETIME',
            'default' => \DB\SQL\Schema::DF_CURRENT_TIMESTAMP
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
        ]
    ];
}

