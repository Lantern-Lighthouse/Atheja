<?php

namespace lib;

use \DB\SQL;
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
        if (empty($role))
            return null;

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
/**
 * Self-hosted JWT Helper for Atheja
 * No external dependencies - pure PHP implementation
 */
class RibbitJWT
{
    private $f3;

    public function __construct()
    {
        $this->f3 = \Base::instance();
    }

    /**
     * Generate JWT token for user
     * @param mixed $uID
     * @param mixed $expiresInSeconds
     * @param mixed $additionalClaims
     * @throws \Exception
     * @return string
     */
    public function generate_token($uID, $expiresInSeconds = 3600, $additionalClaims = [])
    {
        $secret = $this->f3->get('JWT_SECRET');
        if (!$secret)
            throw new Exception('JWT_SECRET not configured');

        $now = time();

        // JWT Header
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        // JWT Payload
        $payload = array_merge([
            'iss' => $this->f3->get('JWT_ISSUER') ?? 'atheja-search', // Issuer
            'aud' => $this->f3->get('JWT_AUDIENCE') ?? 'atheja-api', // Audience
            'iat' => $now, // Issued at
            'nbf' => $now, // Not before
            'exp' => $now + $expiresInSeconds, // Expires
            'user_id' => $uID
        ], $additionalClaims);

        // Encode header and payload
        $headerEncoded = $this->base64_url_encode(json_encode($header));
        $payloadEncoded = $this->base64_url_encode(json_encode($payload));

        // Create signature
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true);
        $signatureEncoded = $this->base64_url_encode(json_encode($signature));

        // Return complete JWT
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Validate JWT token and return payload
     * @param mixed $token
     */
    public function validate_token($token)
    {
        try {
            $secret = $this->f3->get('JWT_SECRET');
            if (!$secret)
                return false;

            // Split JWT into parts
            $parts = explode('.', $token);
            if (count($parts) !== 3)
                return false;

            list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

            // Verify signature
            $expectedSignature = $this->base64_url_decode(
                hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true)
            );

            if (!hash_equals($expectedSignature, $signatureEncoded))
                return false; // Invalid signature

            // Decode payload
            $payload = json_decode($this->base64_url_decode($payloadEncoded), true);
            if (!$payload)
                return false;

            $now = time();

            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < $now)
                return false; // Token expired

            // Check not before
            if (isset($payload['nbf']) && $payload['nbf'] > $now)
                return false; // Token not yet valid

            // Check issued at (prevent future tokens)
            if (isset($payload['iat']) && $payload['iat'] > $now + 60) // 1 minute tolerance
                return false;

            return $payload;
        } catch (Exception $e) {
            error_log('JWT validation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Refresh token if it's close to expiring
     * @param mixed $token
     * @param mixed $refreshThresholdSeconds
     */
    public function refresh_token_if_needed($token, $refreshThresholdSeconds = 300)
    {
        $payload = $this->validate_token($token);
        if (!$payload)
            return false;

        // Check if token expires within treshold
        $now = time();
        if (isset($payload['exp']) && ($payload['exp'] - $now) < $refreshThresholdSeconds) {
            // Generate new token with same claims but fresh expiration
            $userID = $payload['user_id'];
            $additionalClaims = $payload;
            unset($additionalClaims['iss'], $additionalClaims['aud'], $additionalClaims['iat'], $additionalClaims['nbf'], $additionalClaims['exp'], $additionalClaims['user_id']);
            return $this->generate_token($userID, 3600, $additionalClaims);
        }
        return $token;
    }

    /**
     * Extract user ID from token without full validation (for caching)
     * @param mixed $token
     * @return int|null
     */
    public function get_user_id_from_token($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3)
            return null;
        $payload = json_decode($this->base64_url_decode($parts[1]), true);
        return isset($payload['user_id']) ? (int)$payload['user_id']: null;
    }

    /**
     * Base64 URL-safe encode
     * @param mixed $data
     * @return string
     */
    private function base64_url_encode ($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL-safe decode
     * @param mixed $data
     * @return bool|string
     */
    private function base64_url_decode ($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    /**
     * Generate a secure random secret for JWT signing
     * @param mixed $length
     * @return string
     */
    public static function generate_secret($length = 64){
        return bin2hex(random_bytes($length / 2 ));
    }
}

/**
 * Permission Middleware for F3
 */
class RibbitMid
{
    private $f3;
    private $permission;

    public function __construct()
    {
        $this->f3 = \Base::instance();
        $this->permission = new RibbitPerms();
    }

    /**
     * Check permission middleware
     * @param mixed $f3
     * @param mixed $params
     * @return void
     */
    public function check($f3, $params)
    {
        $requiredPermission = $f3->get('PARAMS.permission') ?? $params['permission'] ?? null;
        if (!$requiredPermission) {
            $f3->error(500, 'Permission not specified in route');
            return;
        }

        $userID = $this->get_user_id_from_request($f3);
        if (!$userID) {
            $f3->error(401, 'Authentication required');
            return;
        }

        if (!$this->permission->has_permission($userID, $requiredPermission)) {
            $f3->error(403, 'Insufficient permissions');
            return;
        }

        $f3->set('CONTEXT.user_id', $userID);
    }

    /**
     * Admin-only middleware
     * @param mixed $f3
     * @param mixed $params
     * @return void
     */
    public function admin_only($f3, $params)
    {
        $userID = $this->get_user_id_from_request($f3);
        if (!$userID) {
            $f3->error(401, 'Authentication required');
            return;
        }

        if (!$this->permission->has_permission($userID, RibbitPerms::SYSTEM_ADMIN)) {
            $f3->error(403, 'System administrator access required');
            return;
        }

        $f3->set('CONTEXT.user_id', $userID);
    }

    /**
     * Check multiple permissions (user needs ANY of them)
     * @param mixed $f3
     * @param mixed $params
     * @return void
     */
    public function check_any($f3, $params)
    {
        $requiredPermissions = $params['permissions'] ?? [];

        if (empty($requiredPermissions)) {
            $f3->error(500, 'No permissions specified');
            return;
        }

        $userID = $this->get_user_id_from_request($f3);
        if (!$userID) {
            $f3->error(401, 'Authentication required');
            return;
        }

        if (!$this->permission->has_any_permissions($userID, $requiredPermissions)) {
            $f3->error(403, 'Insufficient permissions');
            return;
        }

        $f3->set('CONTEXT.user_id', $userID);
    }

    /**
     * Authenticated user middleware (any logged in user)
     * @param mixed $f3
     * @param mixed $params
     * @return void
     */
    public function auth($f3, $params)
    {
        $userID = $this->get_user_id_from_request($f3);
        if (!$userID) {
            $f3->error(401, 'Authentication required');
            return;
        }

        $f3->set('CONTEXT.user_id', $userID);
    }

    /**
     * Get user ID from request (API key, JWT, custom header, etc.)
     * @param mixed $f3
     */
    private function get_user_id_from_request($f3)
    {
        // Check API key in headers
        $apiKey = $f3->get('HEADERS.X-API-Key') ?: $f3->get('GET.api_key');
        if ($apiKey)
            return $this->get_user_id_from_api_key($apiKey);

        $bearerToken = $f3->get('HEADERS.Authorization');
        if ($bearerToken && strpos($bearerToken, 'Bearer ') === 0) {
            $token = substr($bearerToken, 7);
            return $this->get_user_id_from_jwt($token);
        }

        $userIdHeader = $f3->get('HEADERS.X-User-ID');
        if ($userIdHeader)
            return (int) $userIdHeader;

        return null;
    }

    /**
     * Get user ID from API key
     * @param mixed $apiKey
     */
    private function get_user_id_from_api_key($apiKey)
    {
        $db = $this->f3->get('DB');
        $result = $db->exec(
            'SELECT user_id FROM api_keys WHERE key_hash = ? AND active = 1
             AND (expires_at IS NULL OR expires_at > NOW()',
            [hash('sha256', $apiKey)]
        );

        if (!empty($result)) {
            $db->exec('UPDATE api_keys SET last_used = NOW() WHERE key_hash = ?', [hash('sha256', $apiKey)]);
            return $result[0]['user_id'];
        }
        return null;
    }
}
