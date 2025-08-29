<?php

namespace Models;

class Sessions extends \DB\Cortex
{
    protected $db = 'DB', $table = 'sessions';

    protected $fieldConf = [
        'user' => [
            'belongs-to-one' => 'Models\User'
        ],
        'key' => [
            'type' => 'VARCHAR256',
            'required' => true,
            'unique' => true,
            'nullable' => false,
            'index' => true
        ],
        'expires_at' => [
            'type' => 'DATETIME',
        ],
        'created_at' => [
            'type' => 'DATETIME',
            'default' => \DB\SQL\Schema::DF_CURRENT_TIMESTAMP
        ],
        'last_used_at' => [
            'type' => 'DATETIME'
        ]
    ];
}