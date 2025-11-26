<?php

namespace Ksfraser\Auth;

use PDO;
use Exception;

/**
 * Database connection factory for Ksfraser Auth
 * 
 * @package Ksfraser\Auth
 */
class AuthDatabase
{
    private static $instance = null;
    private $pdo = null;
    private $config = [];
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct(array $config = [])
    {
        $this->config = array_merge([
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'auth_db',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        ], $config);
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset={$this->config['charset']}";
            
            try {
                $this->pdo = new PDO(
                    $dsn,
                    $this->config['username'],
                    $this->config['password'],
                    $this->config['options']
                );
            } catch (Exception $e) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
        
        return $this->pdo;
    }
    
    /**
     * Test database connection
     */
    public function testConnection(): bool
    {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->query('SELECT 1');
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Close connection
     */
    public function closeConnection(): void
    {
        $this->pdo = null;
    }
    
    /**
     * Create database if it doesn't exist
     */
    public function createDatabase(): bool
    {
        try {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};charset={$this->config['charset']}";
            $pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
            
            $dbName = $this->config['database'];
            $sql = "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            
            return $pdo->exec($sql) !== false;
        } catch (Exception $e) {
            throw new Exception("Failed to create database: " . $e->getMessage());
        }
    }
    
    /**
     * Set up complete auth database schema
     */
    public function setupSchema(): bool
    {
        $pdo = $this->getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Users table
            $sql = "CREATE TABLE IF NOT EXISTS users (
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
            ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            
            // User sessions table (optional for database-based sessions)
            $sql = "CREATE TABLE IF NOT EXISTS user_sessions (
                id VARCHAR(128) PRIMARY KEY,
                user_id INT,
                data TEXT,
                expires_at TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            
            // Password reset tokens table
            $sql = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                used_at TIMESTAMP NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_token (token),
                INDEX idx_user_id (user_id),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            
            // Email verification tokens table
            $sql = "CREATE TABLE IF NOT EXISTS email_verification_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                used_at TIMESTAMP NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_token (token),
                INDEX idx_user_id (user_id),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            
            // User roles table (for more complex permission systems)
            $sql = "CREATE TABLE IF NOT EXISTS user_roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(64) UNIQUE NOT NULL,
                description TEXT,
                permissions JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_name (name)
            ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            
            // User role assignments table
            $sql = "CREATE TABLE IF NOT EXISTS user_role_assignments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                role_id INT NOT NULL,
                assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                assigned_by INT,
                UNIQUE KEY unique_user_role (user_id, role_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (role_id) REFERENCES user_roles(id) ON DELETE CASCADE,
                FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_role_id (role_id)
            ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            
            // Audit log table
            $sql = "CREATE TABLE IF NOT EXISTS auth_audit_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                action VARCHAR(64) NOT NULL,
                details JSON,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_action (action),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $pdo->rollback();
            throw new Exception("Failed to setup database schema: " . $e->getMessage());
        }
    }
    
    /**
     * Create default admin user
     */
    public function createDefaultAdmin(string $username = 'admin', string $email = 'admin@localhost', string $password = 'admin123'): bool
    {
        $pdo = $this->getConnection();
        
        // Check if admin user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return false; // Admin already exists
        }
        
        // Create admin user
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, is_admin, email_verified) VALUES (?, ?, ?, 1, 1)");
        
        return $stmt->execute([$username, $email, $passwordHash]);
    }
    
    /**
     * Create default roles
     */
    public function createDefaultRoles(): bool
    {
        $pdo = $this->getConnection();
        
        $roles = [
            [
                'name' => 'admin',
                'description' => 'Full system administrator access',
                'permissions' => json_encode([
                    'users' => ['create', 'read', 'update', 'delete'],
                    'roles' => ['create', 'read', 'update', 'delete'],
                    'system' => ['configure', 'backup', 'restore']
                ])
            ],
            [
                'name' => 'user',
                'description' => 'Standard user access',
                'permissions' => json_encode([
                    'profile' => ['read', 'update'],
                    'portfolio' => ['create', 'read', 'update', 'delete']
                ])
            ],
            [
                'name' => 'readonly',
                'description' => 'Read-only access',
                'permissions' => json_encode([
                    'profile' => ['read'],
                    'portfolio' => ['read']
                ])
            ]
        ];
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT IGNORE INTO user_roles (name, description, permissions) VALUES (?, ?, ?)");
            
            foreach ($roles as $role) {
                $stmt->execute([$role['name'], $role['description'], $role['permissions']]);
            }
            
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $pdo->rollback();
            throw new Exception("Failed to create default roles: " . $e->getMessage());
        }
    }
    
    /**
     * Get database configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }
    
    /**
     * Update database configuration
     */
    public function updateConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        $this->pdo = null; // Force reconnection with new config
    }
    
    /**
     * Check if required tables exist
     */
    public function tablesExist(): bool
    {
        try {
            $pdo = $this->getConnection();
            $tables = ['users', 'user_sessions', 'password_reset_tokens', 'email_verification_tokens', 'user_roles', 'user_role_assignments', 'auth_audit_log'];
            
            foreach ($tables as $table) {
                $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                if (!$stmt->fetch()) {
                    return false;
                }
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get database statistics
     */
    public function getStats(): array
    {
        try {
            $pdo = $this->getConnection();
            
            $stats = [];
            
            // User count
            $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(is_admin) as admins, SUM(is_active) as active FROM users");
            $userStats = $stmt->fetch();
            $stats['users'] = $userStats;
            
            // Session count
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_sessions WHERE expires_at > NOW()");
            $stats['active_sessions'] = $stmt->fetchColumn();
            
            // Recent registrations (last 7 days)
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stats['recent_registrations'] = $stmt->fetchColumn();
            
            // Recent logins (last 24 hours)
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 1 DAY)");
            $stats['recent_logins'] = $stmt->fetchColumn();
            
            return $stats;
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Cleanup expired data
     */
    public function cleanup(): bool
    {
        try {
            $pdo = $this->getConnection();
            $pdo->beginTransaction();
            
            // Clean expired sessions
            $pdo->exec("DELETE FROM user_sessions WHERE expires_at < NOW()");
            
            // Clean expired password reset tokens
            $pdo->exec("DELETE FROM password_reset_tokens WHERE expires_at < NOW() OR used_at IS NOT NULL");
            
            // Clean expired email verification tokens
            $pdo->exec("DELETE FROM email_verification_tokens WHERE expires_at < NOW() OR used_at IS NOT NULL");
            
            // Clean old audit logs (keep 90 days)
            $pdo->exec("DELETE FROM auth_audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
            
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }
            throw new Exception("Failed to cleanup database: " . $e->getMessage());
        }
    }
}
