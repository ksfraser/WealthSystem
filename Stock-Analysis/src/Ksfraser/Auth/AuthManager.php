<?php

namespace Ksfraser\Auth;

use PDO;
use Exception;

/**
 * Ksfraser Authentication System
 * 
 * A reusable, secure authentication library providing:
 * - User registration and login
 * - Password hashing and verification  
 * - Session management
 * - CSRF protection
 * - Role-based access control
 * - JWT token support
 * 
 * @package Ksfraser\Auth
 * @version 1.0.0
 * @author Fraser
 */
class AuthManager
{
    private $pdo;
    private $config;
    private $sessionKey = 'ksfraser_auth_user';
    private $csrfKey = 'ksfraser_auth_csrf';
    private $usersTable = 'users';
    
    /**
     * Initialize AuthManager
     * 
     * @param PDO $pdo Database connection
     * @param array $config Configuration options
     */
    public function __construct(PDO $pdo = null, array $config = [])
    {
        $this->pdo = $pdo;
        $this->config = array_merge([
            'session_name' => 'KSFRASER_AUTH_SESSION',
            'session_lifetime' => 3600, // 1 hour
            'password_min_length' => 8,
            'csrf_token_name' => 'csrf_token',
            'users_table' => 'users',
            'auto_start_session' => true,
            'password_options' => [
                'cost' => 12
            ]
        ], $config);
        
        $this->usersTable = $this->config['users_table'];
        
        if ($this->config['auto_start_session'] && session_status() === PHP_SESSION_NONE) {
            session_name($this->config['session_name']);
            session_start();
        }
    }
    
    /**
     * Ensure users table exists
     */
    public function createUsersTable(): bool
    {
        if (!$this->pdo) {
            throw new Exception('Database connection required');
        }
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->usersTable}` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(64) UNIQUE NOT NULL,
            email VARCHAR(128) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            is_admin TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            email_verified TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            login_attempts INT DEFAULT 0,
            locked_until TIMESTAMP NULL,
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_active (is_active)
        )";
        
        return $this->pdo->exec($sql) !== false;
    }
    
    /**
     * Register a new user
     * 
     * @param string $username
     * @param string $email
     * @param string $password
     * @param bool $isAdmin
     * @return int User ID
     * @throws Exception
     */
    public function register(string $username, string $email, string $password, bool $isAdmin = false): int
    {
        if (!$this->pdo) {
            throw new Exception('Database connection required');
        }
        
        // Validation
        $this->validateRegistrationInput($username, $email, $password);
        
        // Check if username or email already exists
        $stmt = $this->pdo->prepare("SELECT id FROM `{$this->usersTable}` WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            throw new Exception('Username or email already exists');
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT, $this->config['password_options']);
        
        // Insert user
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->usersTable}` (username, email, password_hash, is_admin) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $passwordHash, $isAdmin ? 1 : 0])) {
            return (int)$this->pdo->lastInsertId();
        }
        
        throw new Exception('Failed to create user');
    }
    
    /**
     * Authenticate user login
     * 
     * @param string $usernameOrEmail
     * @param string $password
     * @return array User data
     * @throws Exception
     */
    public function login(string $usernameOrEmail, string $password): array
    {
        if (!$this->pdo) {
            throw new Exception('Database connection required');
        }
        
        if (empty($usernameOrEmail) || empty($password)) {
            throw new Exception('Username/email and password are required');
        }
        
        // Get user by username or email
        $stmt = $this->pdo->prepare("SELECT * FROM `{$this->usersTable}` WHERE (username = ? OR email = ?) AND is_active = 1");
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception('Invalid credentials');
        }
        
        // Check if account is locked
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            throw new Exception('Account is temporarily locked due to too many failed login attempts');
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $this->recordFailedLogin($user['id']);
            throw new Exception('Invalid credentials');
        }
        
        // Reset failed attempts and update last login
        $this->recordSuccessfulLogin($user['id']);
        
        // Set session
        $sessionData = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'is_admin' => (bool)$user['is_admin'],
            'login_time' => time()
        ];
        
        $_SESSION[$this->sessionKey] = $sessionData;
        
        // Generate CSRF token
        $this->generateCSRFToken();
        
        return $sessionData;
    }
    
    /**
     * Logout user
     */
    public function logout(): bool
    {
        unset($_SESSION[$this->sessionKey]);
        unset($_SESSION[$this->csrfKey]);
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        return true;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION[$this->sessionKey]) && !empty($_SESSION[$this->sessionKey]['id']);
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser(): ?array
    {
        return $this->isLoggedIn() ? $_SESSION[$this->sessionKey] : null;
    }
    
    /**
     * Get current user ID
     */
    public function getCurrentUserId(): ?int
    {
        $user = $this->getCurrentUser();
        return $user ? (int)$user['id'] : null;
    }
    
    /**
     * Check if current user is admin
     */
    public function isAdmin(): bool
    {
        $user = $this->getCurrentUser();
        return $user && $user['is_admin'];
    }
    
    /**
     * Require login (throw exception if not logged in)
     */
    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            throw new Exception('Authentication required');
        }
    }
    
    /**
     * Require admin access
     */
    public function requireAdmin(): void
    {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            throw new Exception('Admin access required');
        }
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken(): string
    {
        if (empty($_SESSION[$this->csrfKey])) {
            $_SESSION[$this->csrfKey] = bin2hex(random_bytes(32));
        }
        return $_SESSION[$this->csrfKey];
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRFToken(string $token): bool
    {
        return isset($_SESSION[$this->csrfKey]) && hash_equals($_SESSION[$this->csrfKey], $token);
    }
    
    /**
     * Get CSRF token for forms
     */
    public function getCSRFToken(): string
    {
        return $this->generateCSRFToken();
    }
    
    /**
     * Change user password
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        if (!$this->pdo) {
            throw new Exception('Database connection required');
        }
        
        // Get current user
        $stmt = $this->pdo->prepare("SELECT password_hash FROM `{$this->usersTable}` WHERE id = ?");
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
        if (strlen($newPassword) < $this->config['password_min_length']) {
            throw new Exception("Password must be at least {$this->config['password_min_length']} characters long");
        }
        
        // Hash new password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT, $this->config['password_options']);
        
        // Update password
        $stmt = $this->pdo->prepare("UPDATE `{$this->usersTable}` SET password_hash = ? WHERE id = ?");
        return $stmt->execute([$newPasswordHash, $userId]);
    }
    
    /**
     * Update user profile
     */
    public function updateProfile(int $userId, string $email): bool
    {
        if (!$this->pdo) {
            throw new Exception('Database connection required');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Check if email is already used by another user
        $stmt = $this->pdo->prepare("SELECT id FROM `{$this->usersTable}` WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            throw new Exception('Email already in use by another user');
        }
        
        // Update email
        $stmt = $this->pdo->prepare("UPDATE `{$this->usersTable}` SET email = ? WHERE id = ?");
        if ($stmt->execute([$email, $userId])) {
            // Update session if it's the current user
            if ($this->getCurrentUserId() == $userId) {
                $_SESSION[$this->sessionKey]['email'] = $email;
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Get user by ID
     */
    public function getUserById(int $userId): ?array
    {
        if (!$this->pdo) {
            return null;
        }
        
        $stmt = $this->pdo->prepare("SELECT id, username, email, is_admin, is_active, created_at, last_login FROM `{$this->usersTable}` WHERE id = ?");
        $stmt->execute([$userId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Get all users (admin only)
     */
    public function getAllUsers(int $limit = 100, int $offset = 0): array
    {
        if (!$this->pdo) {
            return [];
        }
        
        $stmt = $this->pdo->prepare("SELECT id, username, email, is_admin, is_active, created_at, last_login FROM `{$this->usersTable}` ORDER BY username LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Delete user (admin only)
     */
    public function deleteUser(int $userId): bool
    {
        if (!$this->pdo) {
            throw new Exception('Database connection required');
        }
        
        // Don't allow deleting self
        if ($this->getCurrentUserId() == $userId) {
            throw new Exception('Cannot delete your own account');
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM `{$this->usersTable}` WHERE id = ?");
        return $stmt->execute([$userId]) && $stmt->rowCount() > 0;
    }
    
    /**
     * Generate JWT token for API access
     */
    public function generateJWTToken(int $userId, int $expiresIn = 86400): string
    {
        $payload = [
            'user_id' => $userId,
            'issued_at' => time(),
            'expires_at' => time() + $expiresIn
        ];
        
        // For now, return a simple base64 encoded token
        // In production, use firebase/php-jwt for proper JWT tokens
        return base64_encode(json_encode($payload));
    }
    
    /**
     * Validate registration input
     */
    private function validateRegistrationInput(string $username, string $email, string $password): void
    {
        if (empty($username) || empty($email) || empty($password)) {
            throw new Exception('Username, email, and password are required');
        }
        
        if (strlen($username) < 3 || strlen($username) > 64) {
            throw new Exception('Username must be between 3 and 64 characters');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        if (strlen($password) < $this->config['password_min_length']) {
            throw new Exception("Password must be at least {$this->config['password_min_length']} characters long");
        }
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedLogin(int $userId): void
    {
        $stmt = $this->pdo->prepare("UPDATE `{$this->usersTable}` SET login_attempts = login_attempts + 1 WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Lock account after 5 failed attempts for 15 minutes
        $stmt = $this->pdo->prepare("SELECT login_attempts FROM `{$this->usersTable}` WHERE id = ?");
        $stmt->execute([$userId]);
        $attempts = $stmt->fetchColumn();
        
        if ($attempts >= 5) {
            $lockUntil = date('Y-m-d H:i:s', time() + 900); // 15 minutes
            $stmt = $this->pdo->prepare("UPDATE `{$this->usersTable}` SET locked_until = ? WHERE id = ?");
            $stmt->execute([$lockUntil, $userId]);
        }
    }
    
    /**
     * Record successful login
     */
    private function recordSuccessfulLogin(int $userId): void
    {
        $stmt = $this->pdo->prepare("UPDATE `{$this->usersTable}` SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
}
