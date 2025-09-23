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

    public static function protect_controllers(\Base $base)
    {
        self::init($base);

        // Override existing routes with RBAC protection
        $originalRun = $base->get('run');

        $base->route('POST /api/search/category/create', function ($base) {
            $guard = self::require_permission('category.create');
            if ($guard($base))
                (new \Controllers\Search())->postSearchCategoryCreate($base);
        });

        $base->route('POST /api/search/category/@category/edit', function ($base) {
            $guard = self::require_permission('category.update');
            if ($guard($base))
                (new \Controllers\Search())->postSearchCategoryEdit($base);
        });

        $base->route('POST /api/search/category/@category/delete', function ($base) {
            $guard = self::require_permission('category.delete');
            if ($guard($base))
                (new \Controllers\Search())->postSearchCategoryDelete($base);
        });

        $base->route('POST /api/search/tag/add', function ($base) {
            $guard = self::require_permission('tag.create');
            if ($guard($base))
                (new \Controllers\Search())->postSearchTagAdd($base);
        });

        $base->route('POST /api/search/tag/@tag/edit', function ($base) {
            $guard = self::require_permission('tag.update');
            if ($guard($base))
                (new \Controllers\Search())->postSearchTagEdit($base);
        });

        $base->route('POST /api/search/tag/@tag/delete', function ($base) {
            $guard = self::require_permission('tag.delete');
            if ($guard($base))
                (new \Controllers\Search())->postSearchTagDelete($base);
        });

        $base->route('POST /api/search/entry/create', function ($base) {
            $guard = self::require_permission('entry.create');
            if ($guard($base))
                (new \Controllers\Search())->postSearchEntryCreate($base);
        });

        $base->route('POST /api/search/entry/@entry/edit', function ($base) {
            $guard = self::require_ownership_or_admin(function ($base) {
                $entryModel = new \Models\Entry();
                $entry = $entryModel->findone(['id=?', $base->get('PARAMS.entry')]);
                return $entry ? $entry->author->id : null;
            });
            if ($guard($base))
                (new \Controllers\Search())->postSearchEntryEdit($base);
        });

        $base->route('POST /api/search/entry/@entry/delete', function ($base) {
            $guard = self::require_ownership_or_admin(function ($base) {
                $entryModel = new \Models\Entry();
                $entry = $entryModel->findone(['id=?', $base->get('PARAMS.entry')]);
                return $entry ? $entry->author->id : null;
            });
            if ($guard($base))
                (new \Controllers\Search())->postSearchEntryDelete($base);
        });

        $base->route('POST /api/search/entry/@entry/rate', function ($base) {
            $guard = self::require_permission('entry.rate');
            if ($guard($base))
                (new \Controllers\Search())->postSearchEntryRate($base);
        });

        $base->route('POST /api/user/create', function ($base) {
            // Allow public user creation if enabled, otherwise require permission
            if (!$base->get('ATH.PUBLIC_USER_CREATION')) {
                $guard = self::require_permission('user.create');
                if (!$guard($base))
                    return;
            }
            (new \Controllers\User())->postUserCreate($base);
        });

        $base->route('POST /api/user/@user/edit', function ($base) {
            $guard = self::require_ownership_or_admin(function ($base) {
                $model = new \Models\User();
                $user = $model->findone(['username=?', $base->get('PARAMS.user')]);
                return $user ? $user->author->id : null;
            });
            if ($guard($base))
                (new \Controllers\User())->postUserEdit($base);
        });

        $base->route('POST /api/user/@user/delete', function ($base) {
            $guard = self::require_any_permission(['user.delete', 'system.admin']);
            if ($guard($base))
                (new \Controllers\User())->postUserDelete($base);
        });
    }
}
