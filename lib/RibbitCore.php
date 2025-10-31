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
        $this->initialize_database(); // Initialize DB connection for F3 ORM
    }

    public static function get_instance(?\Base $base = null): self
    {
        if (self::$instance === null) {
            if ($base === null) {
                throw new Exception("Base instance required for first initialization");
            }
            self::$instance = new self($base);
        }
        return self::$instance;
    }

    /**
     * Initializes the database connection based on configured driver.
     * Integrates with Fat-Free Framework's DB object, making it available to models.
     *
     * Expected configuration variables (set in F3 registry, e.g., config.ini):
     * - DB_DRIVER: 'mysql' or 'pgsql'
     *
     * For PostgreSQL (if DB_DRIVER='pgsql'):
     * - DB_HOST_PG
     * - DB_PORT_PG (optional, defaults to 5432)
     * - DB_NAME_PG
     * - DB_USER_PG
     * - DB_PASS_PG
     *
     * For MySQL (if DB_DRIVER='mysql'):
     * - DB_HOST_MYSQL
     * - DB_PORT_MYSQL (optional, defaults to 3306)
     * - DB_NAME_MYSQL
     * - DB_USER_MYSQL
     * - DB_PASS_MYSQL
     * OR (for backward compatibility with older F3 setups):
     * - DB_DSN
     * - DB_USER
     * - DB_PASS
     *
     * @throws Exception If an unsupported database driver is configured or connection details are missing.
     * @return void
     */
    private function initialize_database(): void
    {
        $driver = $this->base->get('DB_DRIVER');
        $db = null;

        if ($driver === 'pgsql') {
            $host = $this->base->get('DB_HOST_PG');
            $port = $this->base->get('DB_PORT_PG') ?: 5432;
            $name = $this->base->get('DB_NAME_PG');
            $user = $this->base->get('DB_USER_PG');
            $pass = $this->base->get('DB_PASS_PG');

            if (!$host || !$name || !$user) {
                throw new Exception("PostgreSQL connection details (DB_HOST_PG, DB_NAME_PG, DB_USER_PG) are required for DB_DRIVER='{$driver}'.");
            }

            $dsn = "pgsql:host={$host};port={$port};dbname={$name}";
            $db = new \DB\SQL($dsn, $user, $pass);
        } elseif ($driver === 'mysql') {
            $host = $this->base->get('DB_HOST_MYSQL');
            $port = $this->base->get('DB_PORT_MYSQL') ?: 3306;
            $name = $this->base->get('DB_NAME_MYSQL');
            $user = $this->base->get('DB_USER_MYSQL');
            $pass = $this->base->get('DB_PASS_MYSQL');
            $dsn = '';

            if (!$host || !$name || !$user) {
                // Fallback to older F3-style DB_DSN if specific keys are not set,
                // for backward compatibility.
                if ($this->base->exists('DB_DSN') && $this->base->exists('DB_USER')) {
                    $dsn = $this->base->get('DB_DSN');
                    $user = $this->base->get('DB_USER');
                    $pass = $this->base->get('DB_PASS');
                } else {
                    throw new Exception("MySQL connection details (DB_HOST_MYSQL, DB_NAME_MYSQL, DB_USER_MYSQL) or legacy (DB_DSN, DB_USER) are required for DB_DRIVER='{$driver}'.");
                }
            } else {
                $dsn = "mysql:host={$host};port={$port};dbname={$name}";
            }
            $db = new \DB\SQL($dsn, $user, $pass);
        } else {
            throw new Exception("Unsupported or missing database driver configuration: DB_DRIVER='{$driver}'. Expected 'mysql' or 'pgsql'.");
        }

        // Assign the DB object to F3's global scope, making it available to models
        $this->base->set('DB', $db);
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
    public function assign_role_to_user(int $userId, string $roleName)
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

        return array_unique($roles);
    }
}