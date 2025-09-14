<?php

namespace Models;

class RbacPermission extends \DB\Cortex
{
    protected $db = 'DB', $table = 'rbac_permissions';

    protected $fieldConf = [
        'name' => [
            'type' => 'VARCHAR128',
            'required' => true,
            'unique' => true,
            'nullable' => false,
            'index' => true
        ],
        'display_name' => [
            'type' => 'VARCHAR128',
            'required' => true,
            'unique' => false,
            'nullable' => false,
            'index' => false
        ],
        'description' => [
            'type' => 'TEXT',
            'required' => false,
            'unique' => false,
            'nullable' => true,
            'index' => false
        ],
        'resource' => [
            'type' => 'VARCHAT64',
            'required' => true,
            'unique' => false,
            'nullable' => false,
            'index' => true
        ],
        'action' => [
            'type' => 'VARCHAT64',
            'required' => true,
            'unique' => false,
            'nullable' => false,
            'index' => true
        ],
        'is_system_permission' => [
            'type' => 'BOOLEAN',
            'default' => 0,
            'required' => true
        ],
        'created_at' => [
            'type' => 'DATETIME',
            'default' => \DB\SQL\Schema::DF_CURRENT_TIMESTAMP
        ]
    ];
}