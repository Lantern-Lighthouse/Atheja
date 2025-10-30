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
            return \lib\Responsivity::respond("Unauthorized", \lib\Responsivity::HTTP_Unauthorized);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return \lib\Responsivity::respond('Insufficient permissions', \lib\Responsivity::HTTP_Forbidden);

        $roles = $this->manager->get_all_roles();
        \lib\Responsivity::respond($roles);
    }

    public function postRoleCreate(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return \lib\Responsivity::respond("Unauthorized", \lib\Responsivity::HTTP_Unauthorized);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return \lib\Responsivity::respond('Insufficient permissions', \lib\Responsivity::HTTP_Forbidden);

        $name = $base->get('POST.name');
        $displayName = $base->get('POST.display_name');
        $description = $base->get('POST.description') ?? '';

        if (!$name || !$displayName)
            return \lib\Responsivity::respond('Name and display name are required', \lib\Responsivity::HTTP_Bad_Request);

        if ($this->manager->create_role($name, $displayName, $description))
            \lib\Responsivity::respond('Role created successfully', \lib\Responsivity::HTTP_Created);
        else
            \lib\Responsivity::respond('Failed to create role', \lib\Responsivity::HTTP_Internal_Error);
    }

    public function postRoleEdit(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return \lib\Responsivity::respond("Unauthorized", \lib\Responsivity::HTTP_Unauthorized);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return \lib\Responsivity::respond('Insufficient permissions', \lib\Responsivity::HTTP_Forbidden);

        $roleName = $base->get('PARAMS.role');
        $model = new \Models\RbacRole();
        $role = $model->findone(['name=?', $roleName]);
        if (!$role)
            return \lib\Responsivity::respond('Role not found', \lib\Responsivity::HTTP_Not_Found);

        if ($role->is_system_role)
            return \lib\Responsivity::respond('Cannot edit system role', \lib\Responsivity::HTTP_Forbidden);

        $role->display_name = $base->get('POST.display_name') ?? $role->display_name;
        $role->description = $base->get('POST.description') ?? $role->description;

        try {
            $role->save();
            \lib\Responsivity::respond('Role updated successfully');
        } catch (Exception $e) {
            \lib\Responsivity::respond('Failed to update role', \lib\Responsivity::HTTP_Internal_Error);
        }
    }

    public function postRoleDelete(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return \lib\Responsivity::respond("Unauthorized", \lib\Responsivity::HTTP_Unauthorized);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return \lib\Responsivity::respond('Insufficient permissions', \lib\Responsivity::HTTP_Forbidden);

        $roleName = $base->get('PARAMS.role');
        if ($this->manager->delete_role($roleName))
            \lib\Responsivity::respond('Role deleted successfully');
        else
            \lib\Responsivity::respond('Failed to delete role', \lib\Responsivity::HTTP_Internal_Error);
    }

    //endregion

    //region Permissions managemet
    public function getPermissions(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return \lib\Responsivity::respond("Unauthorized", \lib\Responsivity::HTTP_Unauthorized);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return \lib\Responsivity::respond('Insufficient permissions', \lib\Responsivity::HTTP_Forbidden);

        $permissions = $this->manager->get_all_permissions();
        \lib\Responsivity::respond($permissions);
    }

    public function postPermissionCreate(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return \lib\Responsivity::respond("Unauthorized", \lib\Responsivity::HTTP_Unauthorized);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return \lib\Responsivity::respond('Insufficient permissions', \lib\Responsivity::HTTP_Forbidden);

        $name = $base->get('POST.name');
        $displayName = $base->get('POST.display_name');
        $resource = $base->get('POST.resource');
        $action = $base->get('POST.action');
        $description = $base->get('POST.description');
        if (!$name || !$displayName || !$resource || !$action)
            return \lib\Responsivity::respond('Name, display name, resource, and action are required', \lib\Responsivity::HTTP_Bad_Request);

        if ($this->manager->create_permission($name, $displayName, $resource, $action, $description))
            \lib\Responsivity::respond('Permission created successfully', \lib\Responsivity::HTTP_Created);
        else
            \lib\Responsivity::respond('Failed to create permission', \lib\Responsivity::HTTP_Internal_Error);
    }

    public function postPermissionDelete(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return \lib\Responsivity::respond("Unauthorized", \lib\Responsivity::HTTP_Unauthorized);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return \lib\Responsivity::respond('Insufficient permissions', \lib\Responsivity::HTTP_Bad_Request);

        $permissionName = $base->get('PARAMS.permission');
        if ($this->manager->delete_permission($permissionName))
            \lib\Responsivity::respond('Permission deleted successfully');
        else
            \lib\Responsivity::respond('Failed to delete permission', \lib\Responsivity::HTTP_Internal_Error);
    }
    //endregion

    //region Role-Permission Assigment
    public function postRolePermissionAssign(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return \lib\Responsivity::respond("Unauthorized", \lib\Responsivity::HTTP_Unauthorized);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return \lib\Responsivity::respond('Insufficient permissions', \lib\Responsivity::HTTP_Forbidden);

        $roleName = $base->get('PARAMS.role');
        $permissionName = $base->get('POST.permission');
        if (!$permissionName)
            \lib\Responsivity::respond('Permission name is required', \lib\Responsivity::HTTP_Bad_Request);

        if ($this->manager->assign_permission_to_role($roleName, $permissionName))
            \lib\Responsivity::respond('Permission assigned to role successfully');
        else
            \lib\Responsivity::respond('Failed to assign permission to role', \lib\Responsivity::HTTP_Internal_Error);
    }

    public function postRolePermissionRevoke(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return \lib\Responsivity::respond("Unauthorized", \lib\Responsivity::HTTP_Unauthorized);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return \lib\Responsivity::respond('Insufficient permissions', \lib\Responsivity::HTTP_Forbidden);

        $roleName = $base->get('PARAMS.role');
        $permissionName = $base->get('POST.permission');
        if (!$permissionName)
            return \lib\Responsivity::respond('Permission name is required', \lib\Responsivity::HTTP_Bad_Request);

        if ($this->manager->remove_permission_from_role($roleName, $permissionName))
            \lib\Responsivity::respond('Permission revoked from role successfully');
        else
            \lib\Responsivity::respond('Failed to revoke permission from role', \lib\Responsivity::HTTP_Internal_Error);
    }
    //endregion

    //region User-Role Assigment
    public function postUserRoleAssign(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return \lib\Responsivity::respond("Unauthorized", \lib\Responsivity::HTTP_Unauthorized);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return \lib\Responsivity::respond('Insufficient permissions', \lib\Responsivity::HTTP_Forbidden);

        $userID = $base->get('PARAMS.user');
        $roleName = $base->get('POST.role');
        if (!$roleName)
            return \lib\Responsivity::respond('Role name is required', \lib\Responsivity::HTTP_Bad_Request);

        if ($this->rbac->asign_role_to_user($userID, $roleName))
            \lib\Responsivity::respond('Role assigned to user successfully');
        else
            \lib\Responsivity::respond('Failed to assign role to user', \lib\Responsivity::HTTP_Internal_Error);
    }

    public function postUserRoleRevoke(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return \lib\Responsivity::respond("Unauthorized", \lib\Responsivity::HTTP_Unauthorized);

        $this->rbac->set_current_user($user);
        if (!$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return \lib\Responsivity::respond('Insufficient permissions', \lib\Responsivity::HTTP_Forbidden);

        $userID = (new \Models\User())->findone(['username=?', $base->get('PARAMS.user')])->id;
        if (!$userID) 
            return \lib\Responsivity::respond('User not found', \lib\Responsivity::HTTP_Not_Found);

        $roleName = $base->get('POST.role');
        if (!$roleName)
            \lib\Responsivity::respond('Role name is required', \lib\Responsivity::HTTP_Bad_Request);

        if ($this->rbac->remove_role_from_user($userID, $roleName))
            \lib\Responsivity::respond('Role revoked from user successfully');
        else
            \lib\Responsivity::respond('Failed to revoke role from user', \lib\Responsivity::HTTP_Internal_Error);
    }
    //endregion

    //region User RBAC Info
    public function getUserRbacInfo(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user)
            return \lib\Responsivity::respond("Unauthorized", \lib\Responsivity::HTTP_Unauthorized);

        $this->rbac->set_current_user($user);
        $model = new \Models\User();
        $targetUser = $model->findone(['username=?', $base->get('PARAMS.user')]);
        if (!$targetUser)
            \lib\Responsivity::respond('User not found', \lib\Responsivity::HTTP_Not_Found);

        $targetUserID = $targetUser->id;
        if ($user->id != $targetUserID && !$this->rbac->has_permission('system.rbac') && !$user->is_admin)
            return \lib\Responsivity::respond('Insufficient permissions', \lib\Responsivity::HTTP_Forbidden);

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

        \lib\Responsivity::respond($response);
    }
    //endregion

    //region Setup RBAC System
    public function postSetupRbac(\Base $base)
    {
        $user = VerifySessionToken($base);
        if (!$user || !$user->is_admin)
            return \lib\Responsivity::respond("Unauthorized - Admin access required", \lib\Responsivity::HTTP_Unauthorized);

        if ($this->manager->setup_default_roles_and_permissions())
            \lib\Responsivity::respond('RBAC system setup completed successfully');
        else
            \lib\Responsivity::respond('Failed to setup RBAC system', \lib\Responsivity::HTTP_Internal_Error);
    }
    //endregion
}