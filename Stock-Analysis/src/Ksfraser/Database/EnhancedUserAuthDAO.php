<?php
/**
 * Enhanced User Authentication DAO using the new database abstraction layer
 * 
 * @package Ksfraser\Database
 */

namespace Ksfraser\Database;

use Exception;

/**
 * Enhanced User Authentication Data Access Object
 */
class EnhancedUserAuthDAO
{
    /** @var DatabaseConnectionInterface Database connection */
    protected $connection;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->connection = EnhancedDbManager::getConnection();
    }
    
    /**
     * Register a new user
     * 
     * @param string $username Username
     * @param string $email Email address
     * @param string $password Plain text password
     * @param bool $isAdmin Whether user is admin
     * @return int|false User ID on success, false on failure
     */
    public function registerUser(string $username, string $email, string $password, bool $isAdmin = false)
    {
        try {
            // Check if user already exists
            if ($this->userExists($username, $email)) {
                throw new Exception('Username or email already exists');
            }
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $sql = "
                INSERT INTO users (username, email, password_hash, is_admin, created_at, updated_at) 
                VALUES (?, ?, ?, ?, datetime('now'), datetime('now'))
            ";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$username, $email, $passwordHash, $isAdmin ? 1 : 0]);
            
            return (int)$this->connection->lastInsertId();
            
        } catch (Exception $e) {
            error_log("User registration failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user exists by username or email
     * 
     * @param string $username Username
     * @param string $email Email
     * @return bool True if user exists
     */
    protected function userExists(string $username, string $email): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE username = ? OR email = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$username, $email]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Authenticate user login
     * 
     * @param string $username Username
     * @param string $password Plain text password
     * @return array|false User data on success, false on failure
     */
    public function authenticateUser(string $username, string $password)
    {
        try {
            $sql = "SELECT * FROM users WHERE username = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$username]);
            
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Remove password hash from returned data
                unset($user['password_hash']);
                return $user;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("User authentication failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all users
     * 
     * @return array Array of user data
     */
    public function getAllUsers(): array
    {
        try {
            $sql = "SELECT id, username, email, is_admin, created_at, updated_at FROM users ORDER BY created_at DESC";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Failed to get all users: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user by ID
     * 
     * @param int $userId User ID
     * @return array|null User data or null
     */
    public function getUserById(int $userId): ?array
    {
        try {
            $sql = "SELECT id, username, email, is_admin, created_at, updated_at FROM users WHERE id = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$userId]);
            
            $user = $stmt->fetch();
            return $user ?: null;
            
        } catch (Exception $e) {
            error_log("Failed to get user by ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update user admin status
     * 
     * @param int $userId User ID
     * @param bool $isAdmin Admin status
     * @return bool Success status
     */
    public function updateUserAdminStatus(int $userId, bool $isAdmin): bool
    {
        try {
            $sql = "UPDATE users SET is_admin = ?, updated_at = datetime('now') WHERE id = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$isAdmin ? 1 : 0, $userId]);
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Failed to update user admin status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete user
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    public function deleteUser(int $userId): bool
    {
        try {
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$userId]);
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Failed to delete user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Start session if not already started
     */
    protected function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool True if logged in
     */
    public function isLoggedIn(): bool
    {
        $this->startSession();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Check if current user is admin
     * 
     * @return bool True if admin
     */
    public function isAdmin(): bool
    {
        $this->startSession();
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
    }
    
    /**
     * Get current user ID
     * 
     * @return int|null Current user ID or null
     */
    public function getCurrentUserId(): ?int
    {
        $this->startSession();
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Login user (set session)
     * 
     * @param array $user User data
     */
    public function loginUser(array $user): void
    {
        $this->startSession();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
    }
    
    /**
     * Logout user (clear session)
     */
    public function logoutUser(): void
    {
        $this->startSession();
        session_unset();
        session_destroy();
    }
    
    /**
     * Require user to be logged in
     * 
     * @throws Exception If user not logged in
     */
    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            throw new Exception('User must be logged in');
        }
    }
    
    /**
     * Require user to be admin
     * 
     * @throws Exception If user not admin
     */
    public function requireAdmin(): void
    {
        $this->requireLogin();
        
        if (!$this->isAdmin()) {
            throw new Exception('Admin access required');
        }
    }
    
    /**
     * Get database driver information
     * 
     * @return array Driver information
     */
    public function getDatabaseInfo(): array
    {
        return [
            'driver' => EnhancedDbManager::getCurrentDriver(),
            'connection_class' => get_class($this->connection),
        ];
    }
}
?>
