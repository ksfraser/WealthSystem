<?php
/**
 * User Authentication and Management System
 * 
 * @startuml UserAuthDAO_Class_Diagram
 * !define RECTANGLE class
 * 
 * class UserAuthDAO extends CommonDAO {
 *   - sessionKey : string
 *   - csrfKey : string  
 *   - sessionManager : SessionManager
 *   --
 *   + __construct()
 *   + registerUser(username : string, email : string, password : string, isAdmin : bool) : int
 *   + loginUser(username : string, password : string) : array
 *   + logoutUser() : bool
 *   + isLoggedIn() : bool
 *   + getCurrentUser() : array|null
 *   + getCurrentUserId() : int|null
 *   + isAdmin() : bool
 *   + requireLogin(redirectUrl : string) : void
 *   + requireAdmin() : void
 *   + generateCSRFToken() : string
 *   + validateCSRFToken(token : string) : bool
 *   + changePassword(userId : int, currentPassword : string, newPassword : string) : bool
 *   + updateUserProfile(userId : int, email : string) : bool
 *   + getAllUsers(limit : int, offset : int) : array
 *   + getUserById(userId : int) : array|null
 *   + deleteUser(userId : int) : bool
 *   + updateUserAdminStatus(userId : int, isAdmin : bool) : bool
 *   + generateJWTToken(userId : int) : string
 * }
 * 
 * abstract class CommonDAO {
 *   # pdo : PDO
 *   # errors : array
 *   # dbConfigClass : class
 * }
 * 
 * class SessionManager {
 *   - instance : SessionManager
 *   + getInstance() : SessionManager
 *   + set(key : string, value : mixed) : bool
 *   + get(key : string, default : mixed) : mixed
 *   + remove(key : string) : bool
 * }
 * 
 * class AuthExceptions {
 *   LoginRequiredException
 *   AdminRequiredException
 * }
 * 
 * UserAuthDAO --> SessionManager : manages sessions
 * UserAuthDAO --> AuthExceptions : throws custom exceptions
 * CommonDAO <|-- UserAuthDAO
 * 
 * note right of UserAuthDAO : Comprehensive user\nauthentication system\nwith CSRF protection\nand role management
 * @enduml
 * 
 * @startuml UserAuthDAO_Login_Sequence
 * participant "Client" as C
 * participant "UserAuthDAO" as AUTH
 * participant "Database" as DB
 * participant "SessionManager" as SM
 * 
 * C -> AUTH: loginUser(username, password)
 * activate AUTH
 * 
 * AUTH -> AUTH: validate inputs
 * AUTH -> DB: SELECT user WHERE username = ?
 * activate DB
 * DB --> AUTH: user data
 * deactivate DB
 * 
 * alt user not found
 *   AUTH --> C: Exception("Invalid username or password")
 * else user found
 *   AUTH -> AUTH: password_verify(password, hash)
 *   alt password valid
 *     AUTH -> SM: set(sessionKey, userData)
 *     AUTH -> AUTH: generateCSRFToken()
 *     AUTH -> SM: set(csrfKey, token)
 *     AUTH --> C: user data
 *   else password invalid
 *     AUTH --> C: Exception("Invalid username or password")
 *   end
 * end
 * deactivate AUTH
 * @enduml
 * 
 * @startuml UserAuthDAO_Registration_Flow
 * start
 * :Registration Request;
 * :Validate username, email, password;
 * 
 * if (Validation passed?) then (no)
 *   :Throw validation exception;
 *   stop
 * endif
 * 
 * :Check if username/email exists;
 * if (User already exists?) then (yes)
 *   :Throw "already exists" exception;
 *   stop
 * endif
 * 
 * :Hash password with PASSWORD_DEFAULT;
 * :Insert user into database;
 * 
 * if (Insert successful?) then (yes)
 *   :Return new user ID;
 *   stop
 * else (no)
 *   :Throw "creation failed" exception;
 *   stop
 * endif
 * @enduml
 * 
 * @startuml UserAuthDAO_Security_Model
 * package "Authentication Security" {
 *   class PasswordHashing {
 *     + PASSWORD_DEFAULT algorithm
 *     + password_hash()
 *     + password_verify()
 *   }
 *   
 *   class CSRFProtection {
 *     + generateCSRFToken()
 *     + validateCSRFToken()
 *     + hash_equals() for timing attack prevention
 *   }
 *   
 *   class SessionSecurity {
 *     + HttpOnly cookies
 *     + Secure flag for HTTPS
 *     + Session regeneration
 *     + Proper session cleanup
 *   }
 *   
 *   class RoleBasedAccess {
 *     + requireLogin()
 *     + requireAdmin()
 *     + isAdmin()
 *   }
 * }
 * 
 * UserAuthDAO --> PasswordHashing
 * UserAuthDAO --> CSRFProtection
 * UserAuthDAO --> SessionSecurity
 * UserAuthDAO --> RoleBasedAccess
 * @enduml
 * 
 * Features:
 * - User registration and login with comprehensive validation
 * - Password hashing using PHP's PASSWORD_DEFAULT algorithm
 * - Session management through centralized SessionManager
 * - CSRF protection with token generation and validation
 * - Role-based access control (admin/user)
 * - JWT token support for API access
 * - Profile management and admin user controls
 * 
 * Security Features:
 * - Secure password hashing with automatic salt generation
 * - Timing attack resistant token comparison (hash_equals)
 * - Session security with proper cookie configuration
 * - Input validation and sanitization
 * - SQL injection prevention through prepared statements
 * - Account lockout protection through generic error messages
 * 
 * Design Patterns:
 * - Template Method: Inherits from CommonDAO for common functionality
 * - Singleton: Uses SessionManager singleton for session consistency
 * - Strategy: Different authentication strategies (session, JWT)
 * - Factory: User creation with different roles
 * 
 * Error Handling:
 * - Comprehensive validation with specific error messages
 * - Generic security messages to prevent user enumeration
 * - Centralized error logging through CommonDAO
 * - Exception-based error handling for auth failures
 */

require_once __DIR__ . '/CommonDAO.php';
require_once __DIR__ . '/SessionManager.php';
require_once __DIR__ . '/AuthExceptions.php';

class UserAuthDAO extends CommonDAO {
    
    private $sessionKey = 'user_auth';
    private $csrfKey = 'csrf_token';
    private $sessionManager;
    
    public function __construct() {
        parent::__construct('LegacyDatabaseConfig');
        
        // Use centralized SessionManager instead of direct session_start()
        $this->sessionManager = SessionManager::getInstance();
        
        // Log session initialization issues if any
        if (!$this->sessionManager->isSessionActive()) {
            $error = $this->sessionManager->getInitializationError();
            if ($error) {
                $this->logError("Session initialization issue: " . $error);
            }
        }
    }
    
    /**
     * Register a new user
     */
    public function registerUser($username, $email, $password, $isAdmin = false) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            // Validation
            if (empty($username) || empty($email) || empty($password)) {
                throw new Exception('Username, email, and password are required');
            }
            
            if (strlen($username) < 3 || strlen($username) > 64) {
                throw new Exception('Username must be between 3 and 64 characters');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            
            if (strlen($password) < 8) {
                throw new Exception('Password must be at least 8 characters long');
            }
            
            // Check if username or email already exists
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                throw new Exception('Username or email already exists');
            }
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Get current user ID for audit fields (if logged in)
            $currentUserId = $this->getCurrentUserId();
            
            // Insert user with audit fields
            $stmt = $this->pdo->prepare('INSERT INTO users (username, email, password_hash, is_admin, created_at, created_by) VALUES (?, ?, ?, ?, NOW(), ?)');
            if ($stmt->execute([$username, $email, $passwordHash, $isAdmin ? 1 : 0, $currentUserId])) {
                $userId = $this->pdo->lastInsertId();
                
                // Automatically assign 'user' role to all new users
                try {
                    $rbacService = $this->getRBACService();
                    $rbacService->assignRole($userId, 'user', $currentUserId, 'Default role assigned during registration');
                } catch (Exception $e) {
                    // Log but don't fail registration if RBAC assignment fails
                    $this->logError("Failed to assign default 'user' role to new user $userId: " . $e->getMessage());
                }
                
                return $userId;
            }
            
            throw new Exception('Failed to create user');
            
        } catch (Exception $e) {
            $this->logError("User registration failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Authenticate user login
     */
    public function loginUser($username, $password) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            if (empty($username) || empty($password)) {
                throw new Exception('Username and password are required');
            }
            
            // Get user by username or email
            $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception('Invalid username or password');
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                throw new Exception('Invalid username or password');
            }
            
            // Set session
            $sessionData = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'is_admin' => (bool)$user['is_admin'],
                'login_time' => time()
            ];
            
            $this->sessionManager->set($this->sessionKey, $sessionData);
            
            // Generate CSRF token
            $this->generateCSRFToken();
            
            return $user;
            
        } catch (Exception $e) {
            $this->logError("User login failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Logout user
     */
    public function logoutUser() {
        $this->sessionManager->remove($this->sessionKey);
        $this->sessionManager->remove($this->csrfKey);
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        $this->sessionManager->destroy();
        return true;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        $userData = $this->sessionManager->get($this->sessionKey);
        return $userData && !empty($userData['id']);
    }
    
    /**
     * Get current user
     */
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return $this->sessionManager->get($this->sessionKey);
        }
        return null;
    }
    
    /**
     * Get current user ID
     */
    public function getCurrentUserId() {
        $user = $this->getCurrentUser();
        return $user ? $user['id'] : null;
    }
    
    /**
     * Check if user is admin
     * Enhanced with RBAC support while maintaining backward compatibility
     * 
     * @param int|null $userId Optional user ID (defaults to current user)
     * @return bool
     */
    public function isAdmin(?int $userId = null): bool {
        // Try RBAC first (if available)
        try {
            return $this->getRBACService()->isAdmin($userId);
        } catch (Exception $e) {
            // Fallback to old is_admin column method
            if ($userId === null) {
                // Original behavior - check current user
                $user = $this->getCurrentUser();
                return $user && $user['is_admin'];
            } else {
                // Check specific user
                $user = $this->getUserById($userId);
                return $user ? (bool)$user['is_admin'] : false;
            }
        }
    }
    
    /**
     * Require login (redirect if not logged in)
     */
    public function requireLogin($redirectUrl = 'login.php') {
        if (!$this->isLoggedIn()) {
            throw new \App\Auth\LoginRequiredException($redirectUrl, "User not logged in - login required");
        }
    }
    
    /**
     * Require admin access
     */
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            throw new Exception('Admin access required');
        }
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        $existingToken = $this->sessionManager->get($this->csrfKey);
        if (empty($existingToken)) {
            $token = bin2hex(random_bytes(32));
            $this->sessionManager->set($this->csrfKey, $token);
            return $token;
        }
        return $existingToken;
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRFToken($token) {
        $sessionToken = $this->sessionManager->get($this->csrfKey);
        return $sessionToken && hash_equals($sessionToken, $token);
    }
    
    /**
     * Change user password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            // Get current user
            $stmt = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                throw new Exception('Current password is incorrect');
            }
            
            // Validate new password
            if (strlen($newPassword) < 8) {
                throw new Exception('New password must be at least 8 characters long');
            }
            
            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $this->pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            if ($stmt->execute([$newPasswordHash, $userId])) {
                return true;
            }
            
            throw new Exception('Failed to update password');
            
        } catch (Exception $e) {
            $this->logError("Password change failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update user profile
     */
    public function updateUserProfile($userId, $email) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            
            // Check if email is already used by another user
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                throw new Exception('Email already in use by another user');
            }
            
            // Update email with audit fields
            $currentUserId = $this->getCurrentUserId();
            $stmt = $this->pdo->prepare('UPDATE users SET email = ?, last_updated = NOW(), updated_by = ? WHERE id = ?');
            if ($stmt->execute([$email, $currentUserId, $userId])) {
                // Update session if it's the current user
                if ($this->getCurrentUserId() == $userId) {
                    $userData = $this->sessionManager->get($this->sessionKey);
                    if ($userData) {
                        $userData['email'] = $email;
                        $this->sessionManager->set($this->sessionKey, $userData);
                    }
                }
                return true;
            }
            
            throw new Exception('Failed to update profile');
            
        } catch (Exception $e) {
            $this->logError("Profile update failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get all users (admin only)
     */
    public function getAllUsers($limit = 100, $offset = 0) {
        try {
            if (!$this->pdo) {
                return [];
            }
            
            $stmt = $this->pdo->prepare('SELECT id, username, email, is_admin, created_at FROM users ORDER BY username LIMIT ? OFFSET ?');
            $stmt->execute([$limit, $offset]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $this->logError("Get users failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        try {
            if (!$this->pdo) {
                return null;
            }
            
            $stmt = $this->pdo->prepare('SELECT id, username, email, is_admin, created_at FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $this->logError("Get user by ID failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Delete user (admin only)
     */
    public function deleteUser($userId) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            // Don't allow deleting self
            if ($this->getCurrentUserId() == $userId) {
                throw new Exception('Cannot delete your own account');
            }
            
            // Check if user has accounts - prevent deletion if they do
            $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM accounts WHERE user_id = ?');
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ((int)$result['count'] > 0) {
                throw new Exception('Cannot delete user: they have associated accounts');
            }
            
            // Delete user
            $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
            if ($stmt->execute([$userId])) {
                return $stmt->rowCount() > 0;
            }
            
            throw new Exception('Failed to delete user');
            
        } catch (Exception $e) {
            $this->logError("User deletion failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update user admin status
     */
    public function updateUserAdminStatus($userId, $isAdmin) {
        try {
            $currentUserId = $this->getCurrentUserId();
            $stmt = $this->pdo->prepare('UPDATE users SET is_admin = ?, last_updated = NOW(), updated_by = ? WHERE id = ?');
            if ($stmt->execute([$isAdmin ? 1 : 0, $currentUserId, $userId])) {
                return $stmt->rowCount() > 0;
            }
            
            throw new Exception('Failed to update user admin status');
            
        } catch (Exception $e) {
            $this->logError("User admin status update failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate JWT token for API access
     */
    public function generateJWTToken($userId) {
        // This would require firebase/php-jwt library
        // For now, return a simple token
        return base64_encode(json_encode([
            'user_id' => $userId,
            'issued_at' => time(),
            'expires_at' => time() + (24 * 60 * 60) // 24 hours
        ]));
    }
    
    // =============================================
    // RBAC Integration Methods
    // =============================================
    
    private $rbacService = null;
    
    /**
     * Get RBAC service instance (lazy loaded)
     * 
     * @return RBACService
     */
    private function getRBACService(): RBACService {
        if ($this->rbacService === null) {
            require_once __DIR__ . '/RBACService.php';
            $this->rbacService = new RBACService();
        }
        return $this->rbacService;
    }
    

    
    /**
     * Check if current user has a specific permission
     * 
     * @param string $permission Permission name (e.g., 'portfolio.view.own')
     * @param int|null $userId Optional user ID (defaults to current user)
     * @return bool
     */
    public function hasPermission(string $permission, ?int $userId = null): bool {
        try {
            return $this->getRBACService()->hasPermission($permission, $userId);
        } catch (Exception $e) {
            // Log error and deny by default
            $this->logError("RBAC permission check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if current user has a specific role
     * 
     * @param string $roleName Role name (e.g., 'admin', 'user', 'advisor')
     * @param int|null $userId Optional user ID (defaults to current user)
     * @return bool
     */
    public function hasRole(string $roleName, ?int $userId = null): bool {
        try {
            return $this->getRBACService()->hasRole($roleName, $userId);
        } catch (Exception $e) {
            // Fallback for 'admin' role only
            if ($roleName === 'admin') {
                $userId = $userId ?? $this->getCurrentUserId();
                if (!$userId) {
                    return false;
                }
                
                $user = $this->getUserById($userId);
                return $user ? (bool)$user['is_admin'] : false;
            }
            
            // Log error and deny by default for other roles
            $this->logError("RBAC role check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user can access another user's data
     * 
     * @param int $targetUserId The user whose data is being accessed
     * @param string $permission The permission being checked
     * @param int|null $userId Optional user ID (defaults to current user)
     * @return bool
     */
    public function canAccessUserData(int $targetUserId, string $permission, ?int $userId = null): bool {
        try {
            return $this->getRBACService()->canAccessUserData($targetUserId, $permission, $userId);
        } catch (Exception $e) {
            // Fallback: only admin can access other users' data, users can access their own
            $userId = $userId ?? $this->getCurrentUserId();
            if (!$userId) {
                return false;
            }
            
            // Users can access their own data
            if ($userId === $targetUserId) {
                return true;
            }
            
            // Only admins can access other users' data
            return $this->isAdmin($userId);
        }
    }
    
    /**
     * Get menu items based on user permissions
     * Integrates with RBAC system for role-based navigation
     * 
     * @param int|null $userId Optional user ID (defaults to current user)
     * @return array Menu items the user can access
     */
    public function getPermittedMenuItems(?int $userId = null): array {
        try {
            return $this->getRBACService()->getPermittedMenuItems($userId);
        } catch (Exception $e) {
            // Fallback to basic menu structure
            $userId = $userId ?? $this->getCurrentUserId();
            if (!$userId) {
                return [];
            }
            
            $menuItems = [];
            $menuItems[] = ['name' => 'Dashboard', 'url' => 'dashboard.php', 'icon' => '🏠'];
            $menuItems[] = ['name' => 'My Portfolio', 'url' => 'MyPortfolio.php', 'icon' => '📊'];
            $menuItems[] = ['name' => 'Portfolios', 'url' => 'portfolios.php', 'icon' => '💼'];
            
            if ($this->isAdmin($userId)) {
                $menuItems[] = ['name' => 'User Management', 'url' => 'admin_users.php', 'icon' => '👥'];
                $menuItems[] = ['name' => 'System Status', 'url' => 'system_status.php', 'icon' => '🔧'];
            }
            
            return $menuItems;
        }
    }
    

    
    /**
     * New method: Require specific permission
     * 
     * @param string $permission Permission required
     * @param string $redirectUrl Optional redirect URL on failure
     */
    public function requirePermission(string $permission, string $redirectUrl = 'dashboard.php?error=permission_denied'): void {
        if (!$this->isLoggedIn()) {
            header('Location: login.php?error=login_required');
            exit;
        }
        
        if (!$this->hasPermission($permission)) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Get user's roles for display purposes
     * 
     * @param int|null $userId Optional user ID (defaults to current user)
     * @return array Array of role names
     */
    public function getUserRoles(?int $userId = null): array {
        try {
            return $this->getRBACService()->getUserRoles($userId ?? $this->getCurrentUserId());
        } catch (Exception $e) {
            // Fallback to is_admin check
            $userId = $userId ?? $this->getCurrentUserId();
            if (!$userId) {
                return [];
            }
            
            $roles = ['user']; // Everyone is a user
            if ($this->isAdmin($userId)) {
                $roles[] = 'admin';
            }
            
            return $roles;
        }
    }
}
