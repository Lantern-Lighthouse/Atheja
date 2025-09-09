<?php

namespace lib;

use Exception;

/**
 * Role-Based Access Control System
 */

class RibbitPerms
{
    private $db, $f3;

    // Perms
    const SEARCH_READ = 'search.read';
    const SEARCH_ADMIN = 'search.admin';
    const INDEX_MANAGE = 'index.manage';
    const USER_MANAGE = 'user.manage';
    const SYSTEM_ADMIN = 'system.admin';
    const CONTENT_MODERATE = 'content.moderate';
    const API_ACCESS = 'api.access';
    const CRAWL_MANAGE = 'crawl.manage';

    public function __construct()
    {
        $this->f3 = \Base::instance();
        $this->db = $this->f3->get('DB');
    }

    /**
     * Check if user has specific permission
     * @param mixed $uID User ID
     * @param mixed $permission
     * @return bool
     */
    public function has_permission($uID, $permission)
    {
        $sql = "SELECT COUNT(*) as count FROM user_permissions up
                JOIN roles r ON up.role_id = r.id
                JOIN role_permissions rp ON r.id = rp.role_id
                JOIN permissions p ON rp.permission_id = p.id
                WHERE up.user_id = ? AND p.name = ? AND r.active = 1";

        $result = $this->db->exec($sql, [$uID, $permission]);
        return $result[0]['count'] > 0;
    }

    /**
     * Check if user has any of the specified permissions
     * @param mixed $uID User ID
     * @param array $permissions
     * @return bool
     */
    public function has_any_permissions($uID, array $permissions)
    {
        if (empty($permissions))
            return false;

        $placeholders = str_repeat('?,', count($permissions) - 1) . '?';
        $sql = "SELECT COUNT(*) as count FROM user_permissions up
                JOIN roles r ON up.role_id = r.id
                JOIN role_permissions rp ON r.id = rp.role_id
                JOIN permissions p ON rp.permission_id = p.id
                WHERE up.user_id = ? AND p.name IN ($placeholders) AND r.active = 1";

        $params = array_merge([$uID], $permissions);
        $result = $this->db->exec($sql, $params);
        return $result[0]['count'] > 0;
    }

    /**
     * Get all permissions for a user
     * @param mixed $uID User ID
     */
    public function get_user_permissions($uID)
    {
        $sql = "SELECT DISTINCT p.name, p.description, p.category
                FROM user_permissions up
                JOIN roles r ON up.role_id = r.id
                JOIN role_permissions rp ON r.id = rp.role_id
                JOIN permissions p ON rp.permission_id = p.id
                WHERE up.user_id = ? AND r.active = 1
                ORDER BY p.category, p.name";

        return $this->db->exec($sql, [$uID]);
    }

    /**
     * Get user roles
     * @param mixed $uID User ID
     */
    public function get_user_roles($uID)
    {
        $sql = "SELECT r.id, r.name, r.description
                FROM user_permissions up
                JOIN roles r ON up.role_id = r.id
                WHERE up.user_id = ? AND r.active = 1";

        return $this->db->exec($sql, [$uID]);
    }

    /**
     * Assign role to user
     * @param mixed $uID User ID
     * @param mixed $rID Role ID
     * @param mixed $grantedBy
     * @return bool Assigned
     */
    public function assign_role($uID, $rID, $grantedBy = null)
    {
        try {
            $existing = $this->db->exec(
                "SELECT id FROM user_permissions WHERE user_id = ? AND role_id = ?",
                [$uID, $rID]
            );

            if (!empty($existing))
                return false; // Role already assigned

            $sql = "INSERT INTO user_permissions (user_id, role_id, granted_by, granted_at)
                    VALUES (?, ?, ?, NOW())";
            $this->db->exec($sql, [$uID, $rID, $grantedBy]);

            $this->log_permission_change($uID, 'grant', $rID, $grantedBy);
            return true; // Role assigned
        } catch (Exception $e) {
            $this->f3->error(500, 'Failed to assign role: ' . $e->getMessage());
            return false; // Role already failed
        }
    }

    /**
     * Remove role from user
     * @param mixed $uID User ID
     * @param mixed $rID Role ID
     * @param mixed $revokedBy
     */
    public function remove_role($uID, $rID, $revokedBy = null)
    {
        try {
            $sql = "DELETE FROM user_permissions WHERE user_id = ? AND role_id = ?";
            $result = $this->db->exec($sql, [$uID, $rID]);

            if ($result)
                $this->log_permission_change($uID, 'revoke', $rID, $revokedBy);

            return $result;
        } catch (Exception $e) {
            $this->f3->error(500, 'Failed to remove role: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create new role
     * @param string $name Role name
     * @param string $description Role description
     * @param array $permissions
     */
    public function create_role($name, $description, array $permissions = [])
    {
        try {
            $this->db->begin();

            // Create role
            $sql = "INSERT INTO roles (name, description, created_at) VALUES (?, ?, NOW())";
            $this->db->exec($sql, [$name, $description]);
            $roleId = $this->db->lastinsertid();

            // Assign permissions to role
            foreach ($permissions as $permissionName)
                $this->assign_permission_to_role($roleId, $permissionName);

            $this->db->commit();
            return $roleId;
        } catch (Exception $e) {
            $this->db->rollback();
            $this->f3->error(500, 'Failed to create role: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update role permissions
     * @param mixed $rID Role ID
     * @param array $permissions
     * @return bool
     */
    public function update_role_permissions($rID, array $permissions)
    {
        try {
            $this->db->begin();

            $this->db->exec("DELETE FROM role_permissions WHERE role_id = ?", [$rID]);

            foreach ($permissions as $permissionName)
                $this->assign_permission_to_role($rID, $permissionName);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            $this->f3->error(500, "Failed to update role permissions: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all available permissions
     */
    public function get_all_permissions()
    {
        $sql = "SELECT * FROM permissions ORDER BY category, name";
        return $this->db->exec($sql);
    }

    /**
     * Get all roles
     */
    public function get_all_roles()
    {
        $sql = "SELECT * FROM roles WHERE active = 1 ORDER BY name";
        return $this->db->exec($sql);
    }

    /**
     * Get role with permissions
     * @param mixed $rID Role ID
     */
    public function get_role_with_permissions($rID)
    {
        $role = $this->db->exec("SELECT * FROM roles WHERE id = ?" . [$rID]);
        if (empty($role)) return null;

        $permissions = $this->db->exec(
            "SELECT p.* FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = ?",
            [$rID]
        );

        $role[0]['permissions'] = $permissions;
        return $role[0];
    }

    /**
     * Assign permission to role (private helper)
     * @param mixed $rID Role ID
     * @param mixed $permissionName
     */
    private function assign_permission_to_role($rID, $permissionName)
    {
        $sql = "INSERT INTO role_permissions (role_id, permission_id, created_at)
                SELECT ?, id, NOW() FROM permissions WHERE name = ?";
        return $this->db->exec($sql, [$rID, $permissionName]);
    }

    /**
     * Log permission changes for audit trail
     * @param mixed $uID User ID
     * @param mixed $action
     * @param mixed $rID Role ID
     * @param mixed $performedBy
     * @param mixed $reason
     */
    private function log_permission_change($uID, $action, $rID, $performedBy, $reason = null)
    {
        $sql = "INSERT INTO permission_audit (user_id, action, role_id, performed_by, reason, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";
        $this->db->exec($sql, [$uID, $action, $rID, $performedBy, $reason]);
    }
}
