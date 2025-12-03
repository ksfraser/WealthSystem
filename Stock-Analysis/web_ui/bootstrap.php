<?php
/**
 * Web UI Bootstrap with Dependency Injection
 * 
 * This bootstrap file sets up the DI Container for web UI pages.
 * It provides access to:
 * - Stock Analysis services (StockAnalysisService, MarketDataService)
 * - Repository layer (AnalysisRepository, MarketDataRepository)
 * - Authentication services (UserAuthDAO, SessionManager)
 * - Navigation and UI services
 * 
 * Usage in web pages:
 * ```php
 * require_once __DIR__ . '/bootstrap.php';
 * 
 * $analysisService = $container->get(StockAnalysisService::class);
 * $marketData = $container->get(MarketDataService::class);
 * $auth = $container->get(UserAuthDAO::class);
 * ```
 */

// Load Composer's autoloader first
require_once __DIR__ . '/../vendor/autoload.php';

// Load the Stock-Analysis DI Container bootstrap
$container = require __DIR__ . '/../bootstrap.php';

// Define application constants
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (!defined('WEB_ROOT')) {
    define('WEB_ROOT', __DIR__);
}

if (!defined('STORAGE_ROOT')) {
    define('STORAGE_ROOT', APP_ROOT . '/storage');
}

// Error reporting for development
if (!defined('PRODUCTION')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Increase execution timeout for long-running operations
set_time_limit(300); // 5 minutes
ini_set('max_execution_time', '300');

// Web-specific service bindings (for legacy compatibility)
// These services are used by existing web_ui pages that haven't been refactored yet

// SessionManager (web UI specific)
if (!$container->has(App\Core\SessionManager::class)) {
    require_once __DIR__ . '/SessionManager.php';
    $container->singleton(App\Core\SessionManager::class, function() {
        return new App\Core\SessionManager();
    });
}

// UserAuthDAO (web UI authentication)
if (!$container->has(UserAuthDAO::class)) {
    require_once __DIR__ . '/UserAuthDAO.php';
    $container->singleton(UserAuthDAO::class, function($c) {
        return new UserAuthDAO();
    });
}

// AuthenticationService (required by NavigationService) - Simple mock for web UI
if (!$container->has(App\Services\Interfaces\AuthenticationServiceInterface::class)) {
    $container->singleton(App\Services\Interfaces\AuthenticationServiceInterface::class, function($c) {
        $authDAO = $c->get(UserAuthDAO::class);
        
        // Create a simple mock that wraps UserAuthDAO
        return new class($authDAO) implements App\Services\Interfaces\AuthenticationServiceInterface {
            private $authDAO;
            private $currentUser = null;
            
            public function __construct($authDAO) {
                $this->authDAO = $authDAO;
                if (isset($_SESSION['user_id'])) {
                    $this->currentUser = (object)$authDAO->getCurrentUser();
                }
            }
            
            public function authenticate(string $identifier, string $password): bool {
                $user = $this->authDAO->login($identifier, $password);
                if ($user) {
                    $this->currentUser = (object)$user;
                    return true;
                }
                return false;
            }
            
            public function logout(): bool {
                $this->authDAO->logout();
                $this->currentUser = null;
                return true;
            }
            
            public function isAuthenticated(): bool {
                return $this->currentUser !== null || isset($_SESSION['user_id']);
            }
            
            public function getCurrentUser(): ?object {
                return $this->currentUser;
            }
            
            public function hasRole(string $role): bool {
                if (!$this->isAuthenticated()) return false;
                $user = $this->authDAO->getCurrentUser();
                return isset($user['is_admin']) && $user['is_admin'] && $role === 'admin';
            }
            
            public function hasPermission(string $permission): bool {
                return $this->isAuthenticated();
            }
            
            public function getCurrentUserId(): ?int {
                $user = $this->getCurrentUser();
                return $user ? ($user->id ?? null) : null;
            }
        };
    });
}

// NavigationService (web UI navigation) - Use the web_ui version with full rendering capabilities
// Load the web_ui NavigationService which has renderNavigationHeader()
require_once __DIR__ . '/NavigationService.php';

if (!$container->has(App\Services\NavigationService::class)) {
    $container->singleton(App\Services\NavigationService::class, function($c) {
        // Create the web_ui NavigationService (in global namespace)
        return new NavigationService(
            $c->get(App\Services\Interfaces\AuthenticationServiceInterface::class)
        );
    });
}

// Return the configured container
return $container;
