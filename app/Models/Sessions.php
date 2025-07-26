<?php

namespace Models;

class Sessions extends \DB\Cortex
{
    protected $db = 'DB', $table = 'sessions';

    protected $fieldConf = [
        'user' => [
            'belongs-to-one' => 'models\User'
        ],
        'key' => [
            'type' => 'VARCHAR256',
            'required' => true,
            'unique' => true,
            'nullable' => false,
            'index' => true
        ],
        'last_login' => [
            'type' => 'TIMESTAMP',
            'default' => \DB\SQL\Schema::DF_CURRENT_TIMESTAMP
        ],
        'expires_at' => [
            'type' => 'DATETIME',
        ]
    ];
}