<?php

namespace Controllers;

use Exception;

class Rbac
{
    private $rbac;
    private $manager;

    public function __construct()
    {
        $base = \Base::instance();
        $this->rbac = \lib\RibbitCore::get_instance($base);
        $this->manager = new \lib\RibbitManager($base);
    }

    //region Roles managament
    public function getRoles(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return JSON_response("Unauthorized", 401);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return JSON_response('Insufficient permissions', 403);

        $roles = $this->manager->get_all_roles();
        JSON_response($roles);
    }

    public function postRoleCreate(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return JSON_response("Unauthorized", 401);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return JSON_response('Insufficient permissions', 403);

        $name = $base->get('POST.name');
        $displayName = $base->get('POST.display_name');
        $description = $base->get('POST.description') ?? '';

        if (!$name || !$displayName)
            return JSON_response('Name and display name are required', 400);

        if ($this->manager->create_role($name, $displayName, $description))
            JSON_response('Role created successfully', 201);
        else
            JSON_response('Failed to create role', 500);
    }

    public function postRoleEdit(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return JSON_response("Unauthorized", 401);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return JSON_response('Insufficient permissions', 403);

        $roleName = $base->get('PARAMS.role');
        $model = new \Models\RbacRole();
        $role = $model->findone(['name=?', $roleName]);
        if (!$role)
            return JSON_response('Role not found', 404);

        if ($role->is_system_role)
            return JSON_response('Cannot edit system role', 403);

        $role->display_name = $base->get('POST.display_name') ?? $role->display_name;
        $role->description = $base->get('POST.description') ?? $role->description;

        try {
            $role->save();
            JSON_response('Role updated successfully');
        } catch (Exception $e) {
            JSON_response('Failed to update role', 500);
        }
    }

    public function postRoleDelete(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return JSON_response("Unauthorized", 401);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return JSON_response('Insufficient permissions', 403);

        $roleName = $base->get('PARAMS.role');
        if ($this->manager->delete_role($roleName))
            JSON_response('Role deleted successfully');
        else
            JSON_response('Failed to delete role', 500);
    }

    //endregion

    //region Permissions managemet
    public function getPermissions(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return JSON_response("Unauthorized", 401);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return JSON_response('Insufficient permissions', 403);

        $permissions = $this->manager->get_all_permissions();
        JSON_response($permissions);
    }

    public function postPermissionCreate(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return JSON_response("Unauthorized", 401);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return JSON_response('Insufficient permissions', 403);

        $name = $base->get('POST.name');
        $displayName = $base->get('POST.display_name');
        $resource = $base->get('POST.resource');
        $action = $base->get('POST.action');
        $description = $base->get('POST.description');
        if (!$name || !$displayName || !$resource || !$action)
            return JSON_response('Name, display name, resource, and action are required', 400);

        if ($this->manager->create_permission($name, $displayName, $resource, $action, $description))
            JSON_response('Permission created successfully', 201);
        else
            JSON_response('Failed to create permission', 500);
    }

    public function postPermissionDelete(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return JSON_response("Unauthorized", 401);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return JSON_response('Insufficient permissions', 403);

        $permissionName = $base->get('PARAMS.permission');
        if ($this->manager->delete_permission($permissionName))
            JSON_response('Permission deleted successfully');
        else
            JSON_response('Failed to delete permission', 500);
    }
    //endregion

    //region Role-Permission Assigment
    public function postRolePermissionAssign(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return JSON_response("Unauthorized", 401);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return JSON_response('Insufficient permissions', 403);

        $roleName = $base->get('PARAMS.role');
        $permissionName = $base->get('POST.permission');
        if (!$permissionName)
            JSON_response('Permission name is required', 400);

        if ($this->manager->assign_permission_to_role($roleName, $permissionName))
            JSON_response('Permission assigned to role successfully');
        else
            JSON_response('Failed to assign permission to role', 500);
    }

    public function postRolePermissionRevoke(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return JSON_response("Unauthorized", 401);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return JSON_response('Insufficient permissions', 403);

        $roleName = $base->get('PARAMS.role');
        $permissionName = $base->get('POST.permission');
        if (!$permissionName)
            return JSON_response('Permission name is required', 400);

        if ($this->manager->remove_permission_from_role($roleName, $permissionName))
            JSON_response('Permission revoked from role successfully');
        else
            JSON_response('Failed to revoke permission from role', 500);
    }
    //endregion

    //region User-Role Assigment
    public function postUserRoleAssigment(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return JSON_response("Unauthorized", 401);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return JSON_response('Insufficient permissions', 403);

        $userID = $base->get('PARAMS.user');
        $roleName = $base->get('POST.role');
        if (!$roleName)
            return JSON_response('Role name is required', 400);

        if ($this->rbac->asign_role_to_user($userID, $roleName))
            JSON_response('Role assigned to user successfully');
        else
            JSON_response('Failed to assign role to user', 500);
    }

    public function postUserRoleRevoke(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return JSON_response("Unauthorized", 401);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return JSON_response('Insufficient permissions', 403);

        $userID = (new \Models\User())->findone(['username=?', $base->get('PARAMS.user')])->id;
        if(!$userID)
            return JSON_response('User not found', 404);
        
        $roleName = $base->get('POST.role');
        if (!$roleName)
            JSON_response('Role name is required', 400);

        if ($this->rbac->remove_role_from_user($userID, $roleName))
            JSON_response('Role revoked from user successfully');
        else
            JSON_response('Failed to revoke role from user', 500);
    }
    //endregion

    //region User RBAC Info
    public function getUserRbacInfo(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return JSON_response("Unauthorized", 401);

        $this->rbac->set_current_user($user);
        $targetUserID = $base->get('PARAMS.user');
        if ($user->id != $targetUserID && !$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return JSON_response('Insufficient permissions', 403);

        $model = new \Models\User();
        $targetUser = $model->findone(['id=?', $targetUserID]);
        if ($targetUser)
            JSON_response('User not found', 404);

        $roles = [];
        if ($targetUser->roles) {
            foreach ($targetUser->roles as $role) {
                $permissions = [];
                if ($role->permissions) {
                    foreach ($role->permissions as $perm) {
                        $permissions[] = [
                            'name' => $perm->name,
                            'display_name' => $perm->display_name,
                            'resource' => $perm->resource,
                            'action' => $perm->action
                        ];
                    }
                }

                $roles[] = [
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'description' => $role->description,
                    'permissions' => $permissions
                ];
            }
        }

        $response = [
            'user_id' => $targetUser->id,
            'username' => $targetUser->username,
            'is_admin' => $targetUser->is_admin,
            'roles' => $roles,
            'all_permissions' => $targetUser->getPermissions()
        ];

        JSON_response($response);
    }
    //endregion

    //region Setup RBAC System
    public function postSetupRbac(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user || !$user->is_admin)
            return JSON_response("Unauthorized - Admin access required", 401);

        if ($this->manager->setup_default_roles_and_permissions())
            JSON_response('RBAC system setup completed successfully');
        else
            JSON_response('Failed to setup RBAC system', 500);
    }
    //endregion
}