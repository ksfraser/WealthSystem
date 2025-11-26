<?php

namespace Ksfraser\Auth;

use PDO;
use Exception;

/**
 * Ksfraser Authentication System Bootstrap
 * 
 * Main entry point for the authentication library
 * 
 * @package Ksfraser\Auth
 * @version 1.0.0
 * @author Fraser
 */
class Auth
{
    private static $instance = null;
    private $config;
    private $database;
    private $authManager;
    private $middleware;
    private $ui;
    
    /**
     * Initialize the Auth system
     */
    private function __construct(array $config = [])
    {
        // Load configuration
        $this->config = AuthConfig::getInstance($config);
        
        // Initialize database
        $this->database = AuthDatabase::getInstance($this->config->getDatabaseConfig());
        
        // Initialize auth manager
        $this->authManager = new AuthManager(
            $this->database->getConnection(),
            $this->config->getSessionConfig()
        );
        
        // Initialize middleware
        $this->middleware = new AuthMiddleware(
            $this->authManager,
            $this->config->get('middleware')
        );
        
        // Initialize UI helper
        $this->ui = new AuthUI($this->authManager);
    }
    
    /**
     * Get Auth instance (singleton)
     */
    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    /**
     * Quick setup with minimal configuration
     */
    public static function quickSetup(array $dbConfig): self
    {
        $config = [
            'database' => $dbConfig
        ];
        
        $auth = self::getInstance($config);
        $auth->setup();
        
        return $auth;
    }
    
    /**
     * Setup the authentication system
     */
    public function setup(): bool
    {
        try {
            // Create database if needed
            $this->database->createDatabase();
            
            // Setup database schema
            $this->database->setupSchema();
            
            // Create default roles
            $this->database->createDefaultRoles();
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to setup authentication system: " . $e->getMessage());
        }
    }
    
    /**
     * Create default admin user
     */
    public function createAdmin(string $username = 'admin', string $email = 'admin@localhost', string $password = null): array
    {
        if ($password === null) {
            $password = AuthUtils::generateToken(12);
        }
        
        $userId = $this->authManager->register($username, $email, $password, true);
        
        return [
            'user_id' => $userId,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'login_url' => $this->config->get('ui.login_url')
        ];
    }
    
    /**
     * Get AuthManager instance
     */
    public function manager(): AuthManager
    {
        return $this->authManager;
    }
    
    /**
     * Get AuthMiddleware instance
     */
    public function middleware(): AuthMiddleware
    {
        return $this->middleware;
    }
    
    /**
     * Get AuthUI instance
     */
    public function ui(): AuthUI
    {
        return $this->ui;
    }
    
    /**
     * Get AuthConfig instance
     */
    public function config(): AuthConfig
    {
        return $this->config;
    }
    
    /**
     * Get AuthDatabase instance
     */
    public function database(): AuthDatabase
    {
        return $this->database;
    }
    
    /**
     * Process authentication for current request
     */
    public function processRequest(): void
    {
        $this->middleware->processRequest();
    }
    
    /**
     * Require authentication
     */
    public function requireAuth(): void
    {
        $this->middleware->requireAuth();
    }
    
    /**
     * Require admin access
     */
    public function requireAdmin(): void
    {
        $this->middleware->requireAdmin();
    }
    
    /**
     * Get current user
     */
    public function user(): ?array
    {
        return $this->authManager->getCurrentUser();
    }
    
    /**
     * Check if user is logged in
     */
    public function check(): bool
    {
        return $this->authManager->isLoggedIn();
    }
    
    /**
     * Check if user is admin
     */
    public function admin(): bool
    {
        return $this->authManager->isAdmin();
    }
    
    /**
     * Login user
     */
    public function login(string $usernameOrEmail, string $password): array
    {
        return $this->authManager->login($usernameOrEmail, $password);
    }
    
    /**
     * Logout user
     */
    public function logout(): bool
    {
        return $this->authManager->logout();
    }
    
    /**
     * Register new user
     */
    public function register(string $username, string $email, string $password, bool $isAdmin = false): int
    {
        return $this->authManager->register($username, $email, $password, $isAdmin);
    }
    
    /**
     * Generate CSRF token
     */
    public function csrf(): string
    {
        return $this->authManager->getCSRFToken();
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRF(string $token): bool
    {
        return $this->authManager->validateCSRFToken($token);
    }
    
    /**
     * Get authentication status
     */
    public function status(): array
    {
        $user = $this->user();
        
        return [
            'authenticated' => $this->check(),
            'admin' => $this->admin(),
            'user' => $user ? AuthUtils::formatUserData($user) : null,
            'csrf_token' => $this->csrf()
        ];
    }
    
    /**
     * Handle login form submission
     */
    public function handleLogin(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['login'])) {
            return ['success' => false, 'message' => 'Invalid request'];
        }
        
        try {
            // Validate CSRF
            if (!$this->validateCSRF($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid CSRF token');
            }
            
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                throw new Exception('Username and password are required');
            }
            
            $user = $this->login($username, $password);
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => AuthUtils::formatUserData($user),
                'redirect' => $this->config->get('ui.default_redirect', '/')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle registration form submission
     */
    public function handleRegister(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['register'])) {
            return ['success' => false, 'message' => 'Invalid request'];
        }
        
        try {
            // Validate CSRF
            if (!$this->validateCSRF($_POST['csrf_token'] ?? '')) {
                throw new Exception('Invalid CSRF token');
            }
            
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $isAdmin = !empty($_POST['is_admin']);
            
            if (empty($username) || empty($email) || empty($password)) {
                throw new Exception('All fields are required');
            }
            
            if ($password !== $confirmPassword) {
                throw new Exception('Passwords do not match');
            }
            
            $userId = $this->register($username, $email, $password, $isAdmin);
            
            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $userId,
                'redirect' => $this->config->get('ui.login_url', '/login.php')
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get system statistics
     */
    public function stats(): array
    {
        return $this->database->getStats();
    }
    
    /**
     * Cleanup expired data
     */
    public function cleanup(): bool
    {
        return $this->database->cleanup();
    }
    
    /**
     * Test system functionality
     */
    public function test(): array
    {
        $results = [
            'database_connection' => false,
            'tables_exist' => false,
            'config_valid' => false,
            'session_working' => false
        ];
        
        try {
            // Test database connection
            $results['database_connection'] = $this->database->testConnection();
            
            // Test if tables exist
            $results['tables_exist'] = $this->database->tablesExist();
            
            // Test configuration
            $results['config_valid'] = !empty($this->config->get('database.host'));
            
            // Test session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['test'] = 'working';
            $results['session_working'] = isset($_SESSION['test']);
            unset($_SESSION['test']);
            
        } catch (Exception $e) {
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Export configuration
     */
    public function exportConfig(bool $hideSensitive = true): array
    {
        return $this->config->export($hideSensitive);
    }
    
    /**
     * Create example pages
     */
    public function createExamplePages(string $outputDir): array
    {
        $files = [];
        
        // Create output directory
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Login page
        $loginPage = '<?php
require_once __DIR__ . \'/vendor/autoload.php\';

use Ksfraser\Auth\Auth;

$auth = Auth::getInstance();
$result = $auth->handleLogin();

if ($result[\'success\'] && !empty($result[\'redirect\'])) {
    header(\'Location: \' . $result[\'redirect\']);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <?= $auth->ui()->getCSS() ?>
</head>
<body>
    <h1>Login</h1>
    
    <?php if (!empty($result[\'message\'])): ?>
        <?= $auth->ui()->errorMessage($result[\'message\']) ?>
    <?php endif; ?>
    
    <?= $auth->ui()->loginForm() ?>
</body>
</html>';
        
        $files['login.php'] = $loginPage;
        file_put_contents($outputDir . '/login.php', $loginPage);
        
        // Register page
        $registerPage = '<?php
require_once __DIR__ . \'/vendor/autoload.php\';

use Ksfraser\Auth\Auth;

$auth = Auth::getInstance();
$result = $auth->handleRegister();

if ($result[\'success\'] && !empty($result[\'redirect\'])) {
    header(\'Location: \' . $result[\'redirect\']);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <?= $auth->ui()->getCSS() ?>
</head>
<body>
    <h1>Register</h1>
    
    <?php if (!empty($result[\'message\'])): ?>
        <?= $result[\'success\'] ? $auth->ui()->successMessage($result[\'message\']) : $auth->ui()->errorMessage($result[\'message\']) ?>
    <?php endif; ?>
    
    <?= $auth->ui()->registerForm() ?>
</body>
</html>';
        
        $files['register.php'] = $registerPage;
        file_put_contents($outputDir . '/register.php', $registerPage);
        
        // Dashboard page
        $dashboardPage = '<?php
require_once __DIR__ . \'/vendor/autoload.php\';

use Ksfraser\Auth\Auth;

$auth = Auth::getInstance();
$auth->requireAuth();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <?= $auth->ui()->getCSS() ?>
</head>
<body>
    <h1>Dashboard</h1>
    
    <?= $auth->ui()->userInfo() ?>
    
    <p>Welcome to your dashboard!</p>
</body>
</html>';
        
        $files['dashboard.php'] = $dashboardPage;
        file_put_contents($outputDir . '/dashboard.php', $dashboardPage);
        
        // Logout page
        $logoutPage = '<?php
require_once __DIR__ . \'/vendor/autoload.php\';

use Ksfraser\Auth\Auth;

$auth = Auth::getInstance();
$auth->logout();

header(\'Location: /login.php\');
exit;
?>';
        
        $files['logout.php'] = $logoutPage;
        file_put_contents($outputDir . '/logout.php', $logoutPage);
        
        return $files;
    }
}
