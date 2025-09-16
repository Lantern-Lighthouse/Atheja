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
        if (!$this->currentUser)
            return false;

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

/**
 * RBAC Management utilities
 * Handles creation, deletion, and management of roles and permissions
 */
class RibbitManager
{
    private $base;

    public function __construct(\Base $base)
    {
        $this->base = $base;
    }

    /**
     * Create a new role
     * @param string $name
     * @param string $displayName
     * @param string $description
     * @param bool $isSystemRole
     * @throws \Exception
     * @return bool
     */
    public function create_role(string $name, string $displayName, string $description = '', bool $isSystemRole = false)
    {
        try {
            $roleModel = new \Models\RbacRole();

            // Check if role already exists
            if ($roleModel->findone(['name=?', $name])) {
                throw new Exception("Role '$name' already exists");
            }

            $roleModel->name = $name;
            $roleModel->display_name = $displayName;
            $roleModel->description = $description;
            $roleModel->is_system_role = $isSystemRole;
            $roleModel->save();

            return true;
        } catch (Exception $e) {
            error_log("Error creating role: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new permission
     * @param string $name
     * @param string $displayName
     * @param string $resource
     * @param string $action
     * @param string $description
     * @param bool $isSystemPermission
     * @throws \Exception
     * @return bool
     */
    public function create_permission(string $name, string $displayName, string $resource, string $action, string $description = '', bool $isSystemPermission = false)
    {
        try {
            $permModel = new \Models\RbacPermission();

            // Check if permission already exists
            if ($permModel->findone(['name=?', $name]))
                throw new Exception("Permission '$name' already exists");

            $permModel->name = $name;
            $permModel->display_name = $displayName;
            $permModel->resource = $resource;
            $permModel->action = $action;
            $permModel->is_system_permission = $isSystemPermission;

            $permModel->save();
            return true;
        } catch (Exception $e) {
            error_log("Error creating permission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Assign permission to role
     * @param string $roleName
     * @param string $permissionName
     * @throws \Exception
     * @return bool
     */
    public function assign_permission_to_role(string $roleName, string $permissionName)
    {
        try {
            $roleModel = new \Models\RbacRole();
            $role = $roleModel->findone(['name=?', $roleName]);
            if (!$role)
                throw new Exception("Role '$roleName' not found");

            $permModel = new \Models\RbacPermission();
            $permission = $permModel->find(['name=?', $permissionName]);
            if (!$permission)
                throw new Exception("Permission '$permissionName' not found");

            // Get current permissions
            $currentPerms = [];
            if ($role->permissions)
                foreach ($role->permissions as $existingPerm)
                    $currentPerms[] = $existingPerm->_id;

            // Add new permission if not already assigned
            if (!in_array($permission->_id, $currentPerms)) {
                $currentPerms[] = $permission->_id;
                $role->permissions = $currentPerms;
                $role->save();
            }

            return true;
        } catch (Exception $e) {
            error_log("Error assigning permission to role: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove permission from role
     * @param string $roleName
     * @param string $permissionName
     * @throws \Exception
     * @return bool
     */
    public function remove_permission_from_role(string $roleName, string $permissionName)
    {
        try {
            $roleModel = new \Models\RbacRole();
            $role = $roleModel->findone(['name=?', $roleName]);
            if (!$role)
                throw new Exception("Role '$roleName' not found");

            $permModel = new \Models\RbacPermission();
            $permission = $permModel->findone(['name=?', $permissionName]);
            if (!$permission)
                throw new Exception("Permission '$permissionName' not found");

            // Get current permissions
            $currentPerms = [];
            if ($role->permissons)
                foreach ($role->permissions as $existingPerm)
                    if ($existingPerm->_id !== $permission->_id)
                        $currentPerms[] = $existingPerm->_id;

            $role->permissions = $currentPerms;
            $role->save();
            return true;
        } catch (Exception $e) {
            error_log("Error remmoving permisson from role: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a role only if not system role and no users assigned
     * @param string $roleName
     * @throws \Exception
     * @return bool
     */
    public function delete_role(string $roleName)
    {
        try {
            $roleModel = new \Models\RbacRole();
            $role = $roleModel->findone(['name=?', $roleName]);
            if (!$role)
                throw new Exception("Role '$roleName' not found");

            if ($role->is_system_role)
                throw new Exception("Cannot delete system role");

            // Check if any users have this role
            $userModel = new \Models\User();
            $usersWithRole = $userModel->find(['roles LIKE ?', '%' . $role->_id . '%']);
            if ($usersWithRole && count($usersWithRole) > 0)
                throw new Exception("Cannot delete role - users are still assigned to it");

            $role->erase();
            return true;
        } catch (Exception $e) {
            error_log("Error deleting role: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a permission only if not system permission
     * @param string $permissionName
     * @throws \Exception
     * @return bool
     */
    public function delete_permission(string $permissionName)
    {
        try {
            $permModel = new \Models\RbacPermission();
            $permission = $permModel->findone(['name=?', $permissionName]);
            if (!$permission)
                throw new Exception("Permission '$permissionName' not found");

            if ($permission->is_system_permission)
                throw new Exception("Cannot delete system permission");

            $permission->erase();
            return true;
        } catch (Exception $e) {
            error_log("Error deleting permission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all roles
     * @return array<array|array{created_at: mixed, description: mixed, display_name: mixed, id: mixed, is_system_role: mixed, name: mixed, permissions: array>}
     */
    public function get_all_roles()
    {
        $roleModel = new \Models\RbacRole();
        $roles = $roleModel->find();
        if (!$roles)
            return [];

        $result = [];
        foreach ($roles as $role) {
            $permissions = [];
            if ($role->permissions)
                foreach ($role->permissions as $perm)
                    $permissions[] = [
                        'id' => $perm->_id,
                        'name' => $perm->name,
                        'display_name' => $perm->display_name,
                        'resource' => $perm->resource,
                        'action' => $perm->action
                    ];



            $result[] = [
                'id' => $role->_id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'description' => $role->description,
                'is_system_role' => $role->is_system_role,
                'permissions' => $permissions,
                'created_at' => $role->created_at
            ];
        }

        return $result;
    }

    /**
     * Get all permissions
     * @return array{action: mixed, created_at: mixed, description: mixed, display_name: mixed, id: mixed, is_system_permission: mixed, name: mixed, resource: mixed[]}
     */
    public function get_all_permissions()
    {
        $permModel = new \Models\RbacPermission();
        $permissions = $permModel->find();
        if (!$permissions)
            return [];

        $result = [];
        foreach ($permissions as $perm) {
            $result[] = [
                'id' => $perm->_id,
                'name' => $perm->name,
                'display_name' => $perm->display_name,
                'description' => $perm->description,
                'resource' => $perm->resource,
                'action' => $perm->action,
                'is_system_permission' => $perm->is_system_permission,
                'created_at' => $perm->created_at
            ];
        }

        return $result;
    }

    /**
     * Setup default system roles and permissions
     * @return bool
     */
    public function setup_default_roles_and_permissions()
    {
        try {
            // Create default permissions
            $defaultPermissions = [
                ['user.create', 'Create User', 'user', 'create', 'Create new user accounts'],
                ['user.read', 'Read User', 'user', 'read', 'View user information'],
                ['user.update', 'Update User', 'user', 'update', 'Update user information'],
                ['user.delete', 'Delete User', 'user', 'delete', 'Delete user accounts'],

                ['entry.create', 'Create Entries', 'entry', 'create', 'Create new entries'],
                ['entry.read', 'Read Entries', 'entry', 'read', 'View entries'],
                ['entry.update', 'Update Entries', 'entry', 'update', 'Update entries'],
                ['entry.delete', 'Create Entries', 'entry', 'delete', 'Delete entries'],
                ['entry.rate', 'Rate Entries', 'entry', 'rate', 'Vote on entries'],

                ['category.create', 'Create Categories', 'category', 'create', 'Create new categories'],
                ['category.read', 'Read Categories', 'category', 'read', 'View categories'],
                ['category.update', 'Update Categories', 'category', 'update', 'Update categories'],
                ['category.delete', 'Delete Categories', 'category', 'delete', 'Delete categories'],

                ['tag.create', 'Create Tags', 'tag', 'create', 'Create tags'],
                ['tag.read', 'Read Tags', 'tag', 'read', 'View tags'],
                ['tag.update', 'Update Tags', 'tag', 'update', 'Update tags'],
                ['tag.delete', 'Delete Tags', 'tag', 'delete', 'Delete tags'],

                ['system.admin', 'System Administration', 'system', 'admin', 'Full system administration'],
                ['system.rbac', 'Manage RBAC', 'system', 'rbac', 'Manage roles and permissions']
            ];

            foreach ($defaultPermissions as $perm)
                $this->create_permission($perm[0], $perm[1], $perm[2], $perm[3], $perm[4], true);

            // Create default roles
            $this->create_role('admin', 'Administrator', 'Full system administrator', true);
            $this->create_role('moderator', 'Moderator', 'Content moderator', true);
            $this->create_role('user', 'User', 'User', true);
            $this->create_role('guest', 'Guest', 'Limited access user', true);

            // Assign permissions to roles
            // Admin gets all permissions
            $adminPerms = array_column($defaultPermissions, 0);
            foreach ($adminPerms as $perm)
                $this->assign_permission_to_role('admin', $perm);

            // Moderator gets content management permissions
            $moderatorPerms = [
                'user.read',
                'user.update',
                'entry.create',
                'entry.read',
                'entry.update',
                'entry.delete',
                'entry.rate',
                'category.read',
                'category.update',
                'tag.create',
                'tag.read',
                'tag.update',
                'tag.delete'
            ];
            foreach ($moderatorPerms as $perm)
                $this->assign_permission_to_role('moderator', $perm);

            // User gets basic permissions
            $userPerms = [
                'user.read',
                'entry.create',
                'entry.read',
                'entry.update',
                'entry.rate',
                'category.read',
                'tag.create',
                'tag.read',
                'tag.update',
                'tag.delete'
            ];
            foreach ($userPerms as $perm)
                $this->assign_permission_to_role('user', $perm);

            // Guest gets read-only permissions
            $guestPerms = [
                'user.read',
                'entry.read',
                'category.read',
                'tag.read'
            ];
            foreach ($guestPerms as $perm)
                $this->assign_permission_to_role('guest', $perm);

            return true;
        } catch (Exception $e) {
            error_log("Error setting up default roles and permissions: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * RBAC Middleware
 * Provides middleware functions for protecting routes with role/permission checks
 */
class RibbitGuard
{
    private static $rbac;

    /**
     * Initialize the guard with RBAC instance
     * @param \Base $base
     * @return void
     */
    public static function init(\Base $base)
    {
        self::$rbac = RibbitCore::get_instance($base);
    }

    /**
     * Middleware to check if user has required permission
     * @param string $permission
     * @return (callable(\Base ):bool)
     */
    public static function require_permission(string $permission)
    {
        return function (\Base $base) use ($permission) {
            $user = VerifySessionToken($base);
            if (!$user) {
                JSON_response('Unauthorized', 401);
                return false;
            }

            self::$rbac->set_current_user($user);
            if (!self::$rbac->has_permission($permission)) {
                JSON_response('Insufficient permission: ' . $permission . ' required', 403);
                return false;
            }

            return true;
        };
    }

    /**
     * Middleware to check if user has required role
     * @param string $role
     * @return (callable(\Base ):bool)
     */
    public static function require_role(string $role)
    {
        return function (\Base $base) use ($role) {
            $user = VerifySessionToken($base);
            if (!$user) {
                JSON_response('Unauthorized', 401);
                return false;
            }

            self::$rbac->set_current_user($user);
            if (!self::$rbac->has_role($role)) {
                JSON_response('Insufficient role privileges: ' . $role . ' role required', 403);
                return false;
            }

            return true;
        };
    }

    /**
     * Middleware to check multiple permissions
     * (user needs at least one)
     * @param array $permissions
     * @return (callable(\Base ):bool)
     */
    public static function require_any_permission(array $permissions)
    {
        return function (\Base $base) use ($permissions) {
            $user = VerifySessionToken($base);
            if (!$user) {
                JSON_response('Unauthorized', 401);
                return false;
            }

            self::$rbac->set_current_user($user);
            foreach ($permissions as $permission) {
                if (self::$rbac->has_permission($permission))
                    return true;
            }

            JSON_response('Insufficient permissions: one of [' . implode(', ', $permissions) . '] required', 403);
            return false;
        };
    }

    /**
     * Middleware to check multiple roles
     * (user needs at least one)
     * @param array $roles
     * @return (callable(\Base ):bool)
     */
    public static function require_any_role(array $roles)
    {
        return function (\Base $base) use ($roles) {
            $user = VerifySessionToken($base);
            if (!$user) {
                JSON_response('Unauthorized', 401);
                return false;
            }

            self::$rbac->set_current_user($user);
            foreach ($roles as $role)
                if (self::$rbac->has_role($role))
                    return true;

            JSON_response('Insufficient role privileges: one of [' . implode(', ', $roles) . '] required', 403);
            return false;
        };
    }

    /**
     * Middleware to check resource ownership or admin permission
     * @param callable $getResourceOwner
     * @return (callable(\Base ):bool)
     */
    public static function require_ownership_or_admin(callable $getResourceOwner)
    {
        return function (\Base $base) use ($getResourceOwner) {
            $user = VerifySessionToken($base);
            if (!$user) {
                JSON_response('Unauthorized', 401);
                return false;
            }

            self::$rbac->set_current_user($user);

            // Admin user can access anythin
            if ($user->is_admin)
                return true;

            // Check ownership
            $ownerID = $getResourceOwner($base);
            if ($user->id == $ownerID)
                return true;

            JSON_response('Access denied: resource ownership or admin privileges required', 403);
            return false;
        };
    }
}
-