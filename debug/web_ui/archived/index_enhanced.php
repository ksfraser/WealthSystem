<?php
/**
 * Enhanced Index.php - Non-blocking version with immediate fallback
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set aggressive timeouts
ini_set('default_socket_timeout', 2);
ini_set('mysql.connect_timeout', 2);
set_time_limit(30);

// Include the UI rendering system
require_once 'UiRenderer.php';

/**
 * Fast Authentication Service - Quick database check with immediate fallback
 */
class FastAuthenticationService {
    private $isAuthenticated = false;
    private $currentUser = null;
    private $isAdmin = false;
    private $authError = false;
    
    public function __construct() {
        $this->initializeAuthentication();
    }
    
    private function initializeAuthentication() {
        // Try database authentication with aggressive timeout
        $authSuccess = false;
        
        try {
            // First, quickly test if we can even load the database config
            if (!$this->quickDatabaseTest()) {
                throw new Exception('Database configuration test failed');
            }
            
            // Only try full auth if basic connectivity works
            if (!headers_sent()) {
                require_once 'auth_check.php';
                require_once 'NavigationManager.php';
                
                $navManager = getNavManager();
                $this->currentUser = $navManager->getCurrentUser();
                $this->isAdmin = $navManager->isAdmin();
                $this->isAuthenticated = true;
                $authSuccess = true;
            }
        } catch (Exception $e) {
            $this->handleAuthFailure($e);
        } catch (Error $e) {
            $this->handleAuthFailure($e);
        }
        
        // If auth failed, ensure we have fallback values
        if (!$authSuccess) {
            $this->enableFallbackMode();
        }
    }
    
    private function quickDatabaseTest() {
        try {
            // Quick file existence check first
            if (!file_exists(__DIR__ . '/DbConfigClasses.php')) {
                return false;
            }
            
            // Try to load config without connecting
            require_once 'DbConfigClasses.php';
            $config = LegacyDatabaseConfig::load();
            
            // Basic config validation
            if (!isset($config['database']['host'])) {
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function handleAuthFailure($error) {
        error_log('Auth system failure: ' . $error->getMessage());
        error_log('Auth error file: ' . $error->getFile() . ':' . $error->getLine());
        $this->enableFallbackMode();
    }
    
    private function enableFallbackMode() {
        $this->authError = true;
        $this->currentUser = ['username' => 'Guest (Database Unavailable)'];
        $this->isAuthenticated = false;
        $this->isAdmin = false;
    }
    
    public function isAuthenticated() {
        return $this->isAuthenticated;
    }
    
    public function getCurrentUser() {
        return $this->currentUser;
    }
    
    public function isAdmin() {
        return $this->isAdmin;
    }
    
    public function hasAuthError() {
        return $this->authError;
    }
}

/**
 * Menu Service - Single Responsibility for menu generation
 */
class MenuService {
    public static function getMenuItems($currentPage, $isAdmin, $isAuthenticated) {
        $items = [];
        
        if ($isAuthenticated) {
            $items[] = [
                'url' => 'index.php',
                'label' => '🏠 Dashboard',
                'active' => $currentPage === 'dashboard'
            ];
            
            $items[] = [
                'url' => 'portfolios.php',
                'label' => '📈 Portfolios',
                'active' => $currentPage === 'portfolios'
            ];
            
            $items[] = [
                'url' => 'trades.php',
                'label' => '📋 Trades',
                'active' => $currentPage === 'trades'
            ];
            
            $items[] = [
                'url' => 'analytics.php',
                'label' => '📊 Analytics',
                'active' => $currentPage === 'analytics'
            ];
            
            if ($isAdmin) {
                $items[] = [
                    'url' => 'admin_users.php',
                    'label' => '👥 Users',
                    'active' => $currentPage === 'users',
                    'admin_only' => true
                ];
                
                $items[] = [
                    'url' => 'system_status.php',
                    'label' => '⚙️ System',
                    'active' => $currentPage === 'system',
                    'admin_only' => true
                ];
                
                $items[] = [
                    'url' => 'database.php',
                    'label' => '🗄️ Database',
                    'active' => $currentPage === 'database',
                    'admin_only' => true
                ];
            }
        } else {
            // Show basic navigation for unauthenticated users
            $items[] = [
                'url' => 'index.php',
                'label' => '🏠 Dashboard',
                'active' => $currentPage === 'dashboard'
            ];
            
            $items[] = [
                'url' => 'login.php',
                'label' => '🔐 Login',
                'active' => $currentPage === 'login'
            ];
        }
        
        return $items;
    }
}

/**
 * Dashboard Content Service - Enhanced with better fallback messaging
 */
class DashboardContentService {
    private $authService;
    
    public function __construct($authService) {
        $this->authService = $authService;
    }
    
    public function createDashboardComponents() {
        $components = [];
        
        // Header component
        $components[] = UiFactory::createCard(
            'Welcome to Your Trading Dashboard',
            'Manage your portfolios, track trades, and analyze performance across all market segments',
            'default',
            ''
        );
        
        // Database connectivity status
        if ($this->authService->hasAuthError()) {
            $components[] = UiFactory::createCard(
                '⚠️ System Status: Fallback Mode',
                'The system is currently running in fallback mode due to database connectivity issues. The external database server (fhsws001.ksfraser.com) may be unreachable. The system will continue to function with limited authentication and data persistence capabilities.',
                'warning',
                '⚠️',
                [
                    ['url' => 'debug_500.php', 'label' => 'Run Full Diagnostics', 'class' => 'btn btn-warning', 'icon' => '🔧'],
                    ['url' => 'test_db_simple.php', 'label' => 'Test Database Connection', 'class' => 'btn', 'icon' => '🗄️'],
                    ['url' => 'index_fallback.php', 'label' => 'Continue in Fallback Mode', 'class' => 'btn btn-success', 'icon' => '✅']
                ]
            );
        } elseif ($this->authService->isAdmin()) {
            $components[] = UiFactory::createCard(
                '🔧 Administrator Access',
                'You have administrator privileges. You can manage users, system settings, and access all system functions.',
                'success',
                '🔧',
                [
                    ['url' => 'admin_users.php', 'label' => 'User Management', 'class' => 'btn btn-warning', 'icon' => '👥'],
                    ['url' => 'system_status.php', 'label' => 'System Status', 'class' => 'btn btn-success', 'icon' => '📊'],
                    ['url' => 'database.php', 'label' => 'Database Management', 'class' => 'btn', 'icon' => '🗄️']
                ]
            );
        } else {
            $components[] = UiFactory::createCard(
                '✅ System Status: Online',
                'All systems are operational. Authentication is working and database connectivity is established.',
                'success',
                '✅'
            );
        }
        
        // Main feature cards
        $components[] = $this->createFeatureGrid();
        
        // Quick actions
        $components[] = $this->createQuickActionsCard();
        
        // System information
        $components[] = $this->createSystemInfoCard();
        
        return $components;
    }
    
    private function createFeatureGrid() {
        $gridHtml = '<div class="grid">
            <div class="card success">
                <h3>📈 Portfolio Overview</h3>
                <p>Monitor your investment performance across all market cap segments</p>
                <a href="portfolios.php" class="btn btn-success">View Portfolios</a>
            </div>

            <div class="card info">
                <h3>📋 Trade History</h3>
                <p>Review your trading activity and transaction history</p>
                <a href="trades.php" class="btn">View Trades</a>
            </div>

            <div class="card info">
                <h3>📊 Analytics & Reports</h3>
                <p>Analyze your trading patterns and portfolio performance</p>
                <a href="analytics.php" class="btn">View Analytics</a>
            </div>
        </div>';
        
        // Create a simple component that returns the grid HTML
        return new class($gridHtml) implements ComponentInterface {
            private $html;
            public function __construct($html) { $this->html = $html; }
            public function toHtml() { return $this->html; }
        };
    }
    
    private function createQuickActionsCard() {
        $actions = [
            ['url' => 'portfolios.php', 'label' => 'Add Portfolio', 'class' => 'btn btn-success', 'icon' => '📈'],
            ['url' => 'trades.php', 'label' => 'Record Trade', 'class' => 'btn', 'icon' => '📋']
        ];
        
        if ($this->authService->hasAuthError()) {
            $actions[] = ['url' => 'debug_500.php', 'label' => 'System Diagnostics', 'class' => 'btn btn-warning', 'icon' => '🔧'];
            $actions[] = ['url' => 'index_fallback.php', 'label' => 'Fallback Mode', 'class' => 'btn btn-success', 'icon' => '✅'];
        } elseif ($this->authService->isAdmin()) {
            $actions[] = ['url' => 'admin_users.php', 'label' => 'Manage Users', 'class' => 'btn btn-warning', 'icon' => '👥'];
            $actions[] = ['url' => 'database.php', 'label' => 'Database Tools', 'class' => 'btn', 'icon' => '🗄️'];
        }
        
        return UiFactory::createCard(
            '🎯 Quick Actions',
            'Access frequently used features and system management',
            'default',
            '🎯',
            $actions
        );
    }
    
    private function createSystemInfoCard() {
        $user = $this->authService->getCurrentUser();
        $authStatus = $this->authService->hasAuthError() 
            ? '⚠️ Fallback Mode (Database connectivity issue)' 
            : '✅ Active and Operational';
        $accessLevel = $this->authService->isAdmin() ? 'Administrator' : 'User';
        $mode = $this->authService->hasAuthError() ? 'Fallback mode (limited functionality)' : 'Full functionality';
        
        $content = '<strong>Database Architecture:</strong> Enhanced multi-driver system with PDO and MySQLi support<br>
                   <strong>User Management:</strong> Role-based access control with admin privileges<br>
                   <strong>Trading Features:</strong> Portfolio management, trade tracking, and performance analytics<br>
                   <strong>Authentication Status:</strong> ' . $authStatus . '<br>
                   <strong>Current User:</strong> ' . htmlspecialchars($user['username']) . '<br>
                   <strong>Access Level:</strong> ' . $accessLevel . '<br>
                   <strong>System Mode:</strong> ' . $mode;
        
        return UiFactory::createCard(
            '📋 System Information',
            $content,
            $this->authService->hasAuthError() ? 'warning' : 'info',
            '📋'
        );
    }
}

/**
 * Main Application Controller - Enhanced with better error handling
 */
class DashboardController {
    private $authService;
    private $contentService;
    
    public function __construct() {
        $this->authService = new FastAuthenticationService();
        $this->contentService = new DashboardContentService($this->authService);
    }
    
    public function renderPage() {
        // Create navigation component
        $user = $this->authService->getCurrentUser();
        $isAdmin = $this->authService->isAdmin();
        $isAuthenticated = $this->authService->isAuthenticated();
        
        $menuItems = MenuService::getMenuItems('dashboard', $isAdmin, $isAuthenticated);
        
        $title = $this->authService->hasAuthError() 
            ? 'Enhanced Trading System Dashboard (Fallback Mode)'
            : 'Enhanced Trading System Dashboard';
        
        $navigation = UiFactory::createNavigationComponent(
            $title,
            'dashboard',
            $user,
            $isAdmin,
            $menuItems,
            $isAuthenticated
        );
        
        // Create page content components
        $components = $this->contentService->createDashboardComponents();
        
        // Create and render the complete page
        $pageRenderer = UiFactory::createPageRenderer(
            'Dashboard - Enhanced Trading System',
            $navigation,
            $components
        );
        
        return $pageRenderer->render();
    }
}

// Application Entry Point - Enhanced error handling
try {
    $controller = new DashboardController();
    echo $controller->renderPage();
} catch (Exception $e) {
    // Comprehensive fallback error page
    echo '<!DOCTYPE html><html><head><title>System Error</title>';
    echo '<style>body{font-family:Arial,sans-serif;margin:40px;background:#f5f5f5;}';
    echo '.error-container{background:white;padding:30px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}';
    echo '.error-title{color:#dc3545;margin-bottom:20px;}';
    echo '.error-message{margin-bottom:15px;padding:15px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:4px;color:#721c24;}';
    echo '.error-details{background:#f8f9fa;padding:15px;border-radius:4px;font-family:monospace;font-size:14px;}';
    echo '.btn{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:4px;margin:5px;}';
    echo '</style></head><body>';
    echo '<div class="error-container">';
    echo '<h1 class="error-title">🚨 System Error</h1>';
    echo '<div class="error-message">The system encountered an error. This may be due to database connectivity issues or system configuration problems.</div>';
    echo '<div class="error-details">';
    echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '<br>';
    echo '<strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '<br>';
    echo '<strong>Line:</strong> ' . $e->getLine() . '<br>';
    echo '</div>';
    echo '<div style="margin-top:20px;">';
    echo '<a href="index_fallback.php" class="btn">Try Fallback Mode</a>';
    echo '<a href="debug_500.php" class="btn">Run Diagnostics</a>';
    echo '<a href="test_db_simple.php" class="btn">Test Database</a>';
    echo '</div>';
    echo '</div></body></html>';
} catch (Error $e) {
    echo '<!DOCTYPE html><html><head><title>Fatal Error</title></head><body>';
    echo '<h1>🚨 System Fatal Error</h1>';
    echo '<p>The system encountered a fatal error. Please contact support.</p>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
    echo '<p><strong>Line:</strong> ' . $e->getLine() . '</p>';
    echo '<p><a href="index_fallback.php">Try Fallback Mode</a></p>';
    echo '</body></html>';
}
?>
