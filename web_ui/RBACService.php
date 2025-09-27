<?php
/**
 * RBAC Service - Role-Based Access Control Management
 * 
 * This service provides a clean, SRP-compliant interface for managing
 * roles and permissions using the Symfony Security Component.
 * 
 * Key Features:
 * - Multi-role support (users can have multiple roles)
 * - Granular permissions system
 * - Advisor-client relationship management
 * - Symfony Security Component integration
 * - Backward compatibility with existing is_admin checks
 */

require_once __DIR__ . '/UserAuthDAO.php';

// Check if Symfony Security is available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class RBACService {
    private $pdo;
    private $userAuthDAO;
    private $currentUserId;
    
    // Cache for performance
    private $userRolesCache = [];
    private $userPermissionsCache = [];
    private $rolePermissionsCache = [];
    
    public function __construct() {
        $this->userAuthDAO = new UserAuthDAO();
        
        // Get PDO connection from UserAuthDAO
        $reflection = new ReflectionClass($this->userAuthDAO);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $this->pdo = $pdoProperty->getValue($this->userAuthDAO);
        
        // Get current user ID if logged in
        $this->currentUserId = $this->userAuthDAO->getCurrentUserId();
    }
    
    /**
     * Check if current user has a specific permission
     * 
     * @param string $permission Permission name (e.g., 'portfolio.view.own')
     * @param int|null $userId Optional user ID (defaults to current user)
     * @return bool
     */
    public function hasPermission(string $permission, ?int $userId = null): bool {
        $userId = $userId ?? $this->currentUserId;
        
        if (!$userId) {
            return false;
        }
        
        $permissions = $this->getUserPermissions($userId);
        return in_array($permission, $permissions);
    }
    
    /**
     * Check if current user has a specific role
     * 
     * @param string $roleName Role name (e.g., 'admin', 'user', 'advisor')
     * @param int|null $userId Optional user ID (defaults to current user)
     * @return bool
     */
    public function hasRole(string $roleName, ?int $userId = null): bool {
        $userId = $userId ?? $this->currentUserId;
        
        if (!$userId) {
            return false;
        }
        
        $roles = $this->getUserRoles($userId);
        return in_array($roleName, $roles);
    }
    
    /**
     * Backward compatibility: Check if user is admin
     * This replaces the old isAdmin() checks
     * 
     * @param int|null $userId Optional user ID (defaults to current user)
     * @return bool
     */
    public function isAdmin(?int $userId = null): bool {
        return $this->hasRole('admin', $userId);
    }
    
    /**
     * Check if user can access another user's data
     * Handles admin access and advisor-client relationships
     * 
     * @param int $targetUserId The user whose data is being accessed
     * @param string $permission The permission being checked
     * @param int|null $userId Optional user ID (defaults to current user)
     * @return bool
     */
    public function canAccessUserData(int $targetUserId, string $permission, ?int $userId = null): bool {
        $userId = $userId ?? $this->currentUserId;
        
        if (!$userId) {
            return false;
        }
        
        // Users can always access their own data if they have the permission
        if ($userId === $targetUserId && $this->hasPermission($permission, $userId)) {
            return true;
        }
        
        // Admins can access all data if they have user management permissions
        if ($this->hasPermission('user.manage.all', $userId)) {
            return true;
        }
        
        // Check advisor-client relationships
        if ($this->isAdvisorForClient($userId, $targetUserId)) {
            // Advisors can access client data based on their permission level
            $advisorPermission = str_replace('.own', '.client', $permission);
            return $this->hasPermission($advisorPermission, $userId);
        }
        
        return false;
    }
    
    /**
     * Get all roles for a user
     * 
     * @param int $userId User ID
     * @return array Array of role names
     */
    public function getUserRoles(int $userId): array {
        if (isset($this->userRolesCache[$userId])) {
            return $this->userRolesCache[$userId];
        }
        
        $stmt = $this->pdo->prepare("
            SELECT r.name 
            FROM user_roles ur
            JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = ? 
            AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
        ");
        
        $stmt->execute([$userId]);
        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $this->userRolesCache[$userId] = $roles;
        return $roles;
    }
    
    /**
     * Get all permissions for a user (aggregated from all roles)
     * 
     * @param int $userId User ID
     * @return array Array of permission names
     */
    public function getUserPermissions(int $userId): array {
        if (isset($this->userPermissionsCache[$userId])) {
            return $this->userPermissionsCache[$userId];
        }
        
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT p.name 
            FROM user_roles ur
            JOIN roles r ON ur.role_id = r.id
            JOIN role_permissions rp ON r.id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = ? 
            AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
        ");
        
        $stmt->execute([$userId]);
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $this->userPermissionsCache[$userId] = $permissions;
        return $permissions;
    }
    
    /**
     * Assign a role to a user
     * 
     * @param int $userId User ID
     * @param string|int $roleNameOrId Role name or role ID
     * @param int|null $grantedBy User ID who granted the role
     * @param string|null $notes Optional notes
     * @param string|null $expiresAt Optional expiration date (Y-m-d H:i:s format)
     * @return bool Success status
     */
    public function assignRole(int $userId, $roleNameOrId, ?int $grantedBy = null, ?string $notes = null, ?string $expiresAt = null): bool {
        try {
            // Handle both role name and role ID
            if (is_string($roleNameOrId)) {
                // Role name provided
                $roleName = $roleNameOrId;
                $roleId = $this->getRoleId($roleName);
                if (!$roleId) {
                    throw new Exception("Role '$roleName' not found");
                }
            } else {
                // Role ID provided
                $roleId = (int)$roleNameOrId;
                $roleName = $this->getRoleName($roleId);
                if (!$roleName) {
                    throw new Exception("Role with ID '$roleId' not found");
                }
            }
            
            // Check if role is already assigned
            if ($this->hasRole($roleName, $userId)) {
                return true; // Already has role
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO user_roles (user_id, role_id, granted_by, notes, expires_at) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([$userId, $roleId, $grantedBy, $notes, $expiresAt]);
            
            // Clear cache
            unset($this->userRolesCache[$userId]);
            unset($this->userPermissionsCache[$userId]);
            
            return $result;
            
        } catch (Exception $e) {
            $roleIdentifier = is_string($roleNameOrId) ? $roleNameOrId : "ID:$roleNameOrId";
            error_log("RBAC: Failed to assign role '$roleIdentifier' to user $userId: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove a role from a user
     * 
     * @param int $userId User ID
     * @param string|int $roleNameOrId Role name or ID
     * @return bool Success status
     */
    public function revokeRole(int $userId, $roleNameOrId): bool {
        try {
            // Handle both role name and role ID
            if (is_string($roleNameOrId)) {
                // Role name provided
                $roleName = $roleNameOrId;
                $roleId = $this->getRoleId($roleName);
                if (!$roleId) {
                    return false;
                }
            } else {
                // Role ID provided
                $roleId = (int)$roleNameOrId;
                $roleName = $this->getRoleName($roleId);
                if (!$roleName) {
                    return false;
                }
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM user_roles WHERE user_id = ? AND role_id = ?");
            $result = $stmt->execute([$userId, $roleId]);
            
            // Clear cache
            unset($this->userRolesCache[$userId]);
            unset($this->userPermissionsCache[$userId]);
            
            return $result;
            
        } catch (Exception $e) {
            $roleIdentifier = is_string($roleNameOrId) ? $roleNameOrId : "ID:$roleNameOrId";
            error_log("RBAC: Failed to revoke role '$roleIdentifier' from user $userId: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user is advisor for a client
     * 
     * @param int $advisorId Advisor user ID
     * @param int $clientId Client user ID
     * @return bool
     */
    public function isAdvisorForClient(int $advisorId, int $clientId): bool {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM user_advisors 
            WHERE advisor_id = ? AND user_id = ? AND status = 'active'
        ");
        
        $stmt->execute([$advisorId, $clientId]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get advisor permission level for a client
     * 
     * @param int $advisorId Advisor user ID
     * @param int $clientId Client user ID
     * @return string|null Permission level ('read' or 'read_write') or null if not advisor
     */
    public function getAdvisorPermissionLevel(int $advisorId, int $clientId): ?string {
        $stmt = $this->pdo->prepare("
            SELECT permission_level 
            FROM user_advisors 
            WHERE advisor_id = ? AND user_id = ? AND status = 'active'
        ");
        
        $stmt->execute([$advisorId, $clientId]);
        $result = $stmt->fetchColumn();
        return $result ?: null;
    }
    
    /**
     * Get all available roles
     * 
     * @return array Array of role data
     */
    public function getAllRoles(): array {
        return $this->pdo->query("SELECT * FROM roles ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all available permissions
     * 
     * @return array Array of permission data
     */
    public function getAllPermissions(): array {
        return $this->pdo->query("SELECT * FROM permissions ORDER BY resource, action, name")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Clear all caches (useful after bulk operations)
     */
    public function clearCache(): void {
        $this->userRolesCache = [];
        $this->userPermissionsCache = [];
        $this->rolePermissionsCache = [];
    }
    
    /**
     * Generate menu items based on user permissions
     * This integrates with NavigationService
     * 
     * @param int|null $userId Optional user ID (defaults to current user)
     * @return array Menu items the user can access
     */
    public function getPermittedMenuItems(?int $userId = null): array {
        $userId = $userId ?? $this->currentUserId;
        
        if (!$userId) {
            return [];
        }
        
        $menuItems = [];
        
        // Dashboard - always available to logged in users
        $menuItems[] = ['name' => 'Dashboard', 'url' => 'dashboard.php', 'icon' => '🏠'];
        
        // Portfolio access
        if ($this->hasPermission('portfolio.view.own', $userId)) {
            $menuItems[] = ['name' => 'My Portfolio', 'url' => 'MyPortfolio.php', 'icon' => '📊'];
            $menuItems[] = ['name' => 'Portfolios', 'url' => 'portfolios.php', 'icon' => '💼'];
        }
        
        // Analytics
        if ($this->hasPermission('analytics.view.own', $userId)) {
            $menuItems[] = ['name' => 'Analytics', 'url' => 'analytics.php', 'icon' => '📈'];
        }
        
        // Admin features
        if ($this->hasPermission('user.manage.all', $userId)) {
            $menuItems[] = ['name' => 'User Management', 'url' => 'admin_users.php', 'icon' => '👥'];
        }
        
        if ($this->hasPermission('system.status.view', $userId)) {
            $menuItems[] = ['name' => 'System Status', 'url' => 'system_status.php', 'icon' => '🔧'];
        }
        
        // Advisor features
        if ($this->hasRole('advisor', $userId)) {
            $menuItems[] = ['name' => 'Client Management', 'url' => 'advisor_clients.php', 'icon' => '🤝'];
        }
        
        return $menuItems;
    }
    
    /**
     * Helper: Get role ID by name
     * 
     * @param string $roleName Role name
     * @return int|null Role ID or null if not found
     */
    private function getRoleId(string $roleName): ?int {
        $stmt = $this->pdo->prepare("SELECT id FROM roles WHERE name = ?");
        $stmt->execute([$roleName]);
        $result = $stmt->fetchColumn();
        return $result ?: null;
    }
    
    /**
     * Helper: Get role name by ID
     * 
     * @param int $roleId Role ID
     * @return string|null Role name or null if not found
     */
    public function getRoleName(int $roleId): ?string {
        $stmt = $this->pdo->prepare("SELECT name FROM roles WHERE id = ?");
        $stmt->execute([$roleId]);
        $result = $stmt->fetchColumn();
        return $result ?: null;
    }
}
?>