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
        'roles' => [
            'belongs-to-many' => '\Models\RbacRole'
        ]
    ];

    /**
     * Get all permissions for this user through their roles
     * @return array
     */
    public function getPermissions()
    {
        $permissions = [];
        if ($this->is_admin) {
            $permModel = new RbacPermission();
            $allPerms = $permModel->find();
            if ($allPerms) {
                foreach ($allPerms as $perm)
                    $permissions[] = $perm->name;
                return array_unique($permissions);
            }
            return [];
        }

        if ($this->roles) {
            foreach ($this->roles as $role) {
                if ($role->permissions) {
                    foreach ($role->permissions as $permission) {
                        $permissions[] = $permission->name;
                    }
                }
            }
        }

        return array_unique($permissions);
    }

    /**
     * Check if user has specific permission
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission)
    {
        if ($this->is_admin)
            return true;

        $permissions = $this->getPermissions();
        return in_array($permission, $permissions);
    }

    public function hasRole(string $roleName)
    {
        if ($this->roles) {
            foreach ($this->roles as $role) {
                if ($role->name === $roleName) {
                    return true;
                }
            }
        }
        return false;
    }
}

