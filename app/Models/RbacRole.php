<?php

namespace Models;

class RbacRole extends \DB\Cortex
{
    protected $db = 'DB', $table = 'rbac_roles';

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
        'is_system_role' => [
            'type' => 'BOOLEAN',
            'default' => 0,
            'required' => true
        ],
        'permissions' => [
            'has-many' => [
                'Models\RbacPermission',
                'relTable' => 'rbac_role_permissions',
                'localKey' => '_id',
                'foreignKey' => 'permission_id',
                'relLocalKey' => 'role_id',
                'relForeignKey' => 'permission_id',
            ]
        ],
        'created_at' => [
            'type' => 'DATETIME',
            'default' => \DB\SQL\Schema::DF_CURRENT_TIMESTAMP
        ],
        'updated_at' => [
            'type' => 'DATETIME',
            'default' => \DB\SQL\Schema::DF_CURRENT_TIMESTAMP
        ]
    ];
}