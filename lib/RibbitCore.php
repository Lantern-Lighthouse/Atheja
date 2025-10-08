<?php

namespace lib;

use Exception;

/**
 * RibbitCore - Core RBAC
 * Provides the main interface for role-based access control
 */
class RibbitCore
{
    private static $instance = null;
    private $base;
    private $currentUser = null;

    private function __construct(\Base $base)
    {
        $this->base = $base;
    }

    public static function get_instance(\Base $base = null): self
    {
        if (self::$instance === null) {
            if ($base === null)
                throw new Exception("Base instance required for first initialization");
            self::$instance = new self($base);
        }
        return self::$instance;
    }

    /**
     * Set the current user context for RBAC operations
     * @param mixed $user
     * @return void
     */
    public function set_current_user($user)
    {
        $this->currentUser = $user;
    }

    /**
     * Get current user
     */
    public function get_current_user()
    {
        return $this->currentUser;
    }

    /**
     * Check if current user has a specific permission
     * @param string $permission
     * @return bool
     */
    public function has_permission(string $permission)
    {
        if (!$this->currentUser){
            $guestPermissions = ['entry.read', 'category.read', 'tag.read', 'user.read', 'user.create'];
            return in_array($permission, $guestPermissions);
        }

        // Admin users have all permissions
        if ($this->currentUser->is_admin)
            return true;

        $model = new \Models\User();
        $user = $model->findone(['id=?', $this->currentUser->id]);
        if (!$user || !$user->roles)
            return false;

        foreach ($user->roles as $role)
            if ($role->permissions)
                foreach ($role->permissions as $perm)
                    if ($perm->name === $permission)
                        return true;

        return false;
    }

    /**
     * Check if current user has a specific role
     * @param string $roleName
     * @return bool
     */
    public function has_role(string $roleName)
    {
        if (!$this->currentUser)
            return false;

        $model = new \Models\User();
        $user = $model->findone(['id=?', $this->currentUser->id]);
        if (!$user || !$user->roles)
            return false;

        foreach ($user->roles as $role)
            if ($role->name === $roleName)
                return true;

        return false;
    }

    /**
     * Assign role to user
     * @param int $userId
     * @param string $roleName
     * @return bool
     */
    public function asign_role_to_user(int $userId, string $roleName)
    {
        try {
            $userModel = new \Models\User();
            $user = $userModel->findone(['id=?', $userId]);
            if (!$user)
                return false;

            $roleModel = new \Models\RbacRole();
            $role = $roleModel->findone(['name=?', $roleName]);
            if (!$role)
                return false;

            // Get current roles
            $currentRoles = [];
            if ($user->roles) {
                foreach ($user->roles as $existingRole)
                    $currentRoles[] = $existingRole->_id;
            }

            // Add new role if not already assigned
            if (!in_array($role->_id, $currentRoles)) {
                $currentRoles[] = $role->_id;
                $user->roles = $currentRoles;
                $user->save();
            }

            return true;
        } catch (Exception $e) {
            error_log("Error assigning role: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove role from user
     * @param int $userId
     * @param string $roleName
     * @return bool
     */
    public function remove_role_from_user(int $userId, string $roleName)
    {
        try {
            $userModel = new \Models\User();
            $user = $userModel->findone(['id=?', $userId]);
            if (!$user)
                return false;

            $roleModel = new \Models\RbacRole();
            $role = $roleModel->findone(['name=?', $roleName]);
            if (!$role)
                return false;

            // Get current roles
            $currentRoles = [];
            if ($user->roles)
                foreach ($user->roles as $existingRole)
                    if ($existingRole->_id !== $role->_id)
                        $currentRoles[] = $existingRole->_id;

            $user->roles = $currentRoles;
            $user->save();
            return true;
        } catch (Exception $e) {
            error_log("Error removing role: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all permissions for current user
     * @return array
     */
    public function get_user_permissions()
    {
        if (!$this->currentUser)
            return [];

        // Admin users have all permissions
        if ($this->currentUser->is_admin) {
            $permModel = new \Models\RbacPermission();
            $allPerms = $permModel->find();
            return array_map(function ($perm) {
                return $perm->name;
            }, $allPerms ?: []);
        }

        $permissions = [];
        $userModel = new \Models\User();
        $user = $userModel->findone(['id=?', $this->currentUser->id]);

        if ($user && $user->roles)
            foreach ($user->roles as $role)
                if ($role->permissions)
                    foreach ($role->permissions as $permission)
                        $permissions[] = $permission->name;

        return array_unique($permissions);
    }

    /**
     * Get all roles for current user
     * @return array
     */
    public function get_user_roles()
    {
        if (!$this->currentUser)
            return [];

        $roles = [];
        $userModel = new \Models\User();
        $user = $userModel->findone(['id=?', $this->currentUser->id]);

        if ($user && $user->roles)
            foreach ($user->roles as $role)
                $roles[] = $role->name;

        return $roles;
    }

    /**
     * Middleware function to check permissions
     * @param string $permission
     * @param callable $callback
     */
    public function require_permission(string $permission, callable $callback = null)
    {
        if (!$this->has_permission($permission)) {
            if ($callback)
                return $callback();

            JSON_response('Insufficient permissions', 403);
            return false;
        }
        return true;
    }

    public function require_role(string $role, callable $callback = null)
    {
        if (!$this->has_role($role)) {
            if ($callback)
                return $callback();

            JSON_response('Insufficient role privileges', 403);
            return false;
        }
        return true;
    }
}
