<?php

namespace Models;

class User extends \DB\Cortex
{
    protected $db = 'DB', $table = 'users';

    protected $fieldConf = [
        'username' => [
            'type' => 'VARCHAR128',
            'required' => true,
            'unique' => true,
            'nullable' => false,
            'index' => true
        ],
        'displayname' => [
            'type' => 'VARCHAR128',
            'required' => true,
            'unique' => false,
            'nullable' => false,
            'index' => false
        ],
        'email' => [
            'type' => 'VARCHAR256',
            'required' => true,
            'unique' => true,
            'nullable' => false,
            'index' => true
        ],
        'password' => [
            'type' => 'VARCHAR256',
            'required' => true,
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
        'account_created' => [
            'type' => 'DATETIME',
            'default' => \DB\SQL\Schema::DF_CURRENT_TIMESTAMP
        ],
        'is_admin' => [
            'type' => 'BOOLEAN',
            'default' => 0,
            'required' => true
        ],
        'key' => [
            'type' => 'BLOB',
            'required' => true,
            'unique' => true,
            'nullable' => false,
            'index' => true
        ]
    ];
}

