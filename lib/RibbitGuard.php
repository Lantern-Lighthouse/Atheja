<?php

namespace lib;

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
                \Responsivity\Responsivity::respond('Unauthorized', \Responsivity\Responsivity::HTTP_Unauthorized);
                return false;
            }

            self::$rbac->set_current_user($user);
            if (!self::$rbac->has_permission($permission)) {
                \Responsivity\Responsivity::respond('Insufficient permission: ' . $permission . ' required', \Responsivity\Responsivity::HTTP_Forbidden);
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
                \Responsivity\Responsivity::respond('Unauthorized', \Responsivity\Responsivity::HTTP_Unauthorized);
                return false;
            }

            self::$rbac->set_current_user($user);
            if (!self::$rbac->has_role($role)) {
                \Responsivity\Responsivity::respond('Insufficient role privileges: ' . $role . ' role required', \Responsivity\Responsivity::HTTP_Forbidden);
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
                \Responsivity\Responsivity::respond('Unauthorized', \Responsivity\Responsivity::HTTP_Unauthorized);
                return false;
            }

            self::$rbac->set_current_user($user);
            foreach ($permissions as $permission) {
                if (self::$rbac->has_permission($permission))
                    return true;
            }

            \Responsivity\Responsivity::respond('Insufficient permissions: one of [' . implode(', ', $permissions) . '] required', \Responsivity\Responsivity::HTTP_Forbidden);
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
                \Responsivity\Responsivity::respond('Unauthorized', \Responsivity\Responsivity::HTTP_Unauthorized);
                return false;
            }

            self::$rbac->set_current_user($user);
            foreach ($roles as $role)
                if (self::$rbac->has_role($role))
                    return true;

            \Responsivity\Responsivity::respond('Insufficient role privileges: one of [' . implode(', ', $roles) . '] required', \Responsivity\Responsivity::HTTP_Forbidden);
            return false;
        };
    }

    /**
     * Middleware to check resource ownership or admin permission
     * @param callable $getResourceOwner
     * @return (callable(\Base ):bool)
     */
    public static function require_ownership_or_admin_and_call(callable $getResourceOwner)
    {
        return function (\Base $base) use ($getResourceOwner) {
            $user = VerifySessionToken($base);
            if (!$user) {
                \Responsivity\Responsivity::respond('Unauthorized', \Responsivity\Responsivity::HTTP_Unauthorized);
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

            \Responsivity\Responsivity::respond('Access denied: resource ownership or admin privileges required', \Responsivity\Responsivity::HTTP_Forbidden);
            return false;
        };
    }

    public static function require_ownership_or_admin($ownerID)
    {
        global $base;
        $user = VerifySessionToken($base);
        if (!$user) {
            \Responsivity\Responsivity::respond('Unauthorized', \Responsivity\Responsivity::HTTP_Unauthorized);
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

        \Responsivity\Responsivity::respond('Access denied: resource ownership or admin privileges required', \Responsivity\Responsivity::HTTP_Forbidden);
        return false;
    }
}
