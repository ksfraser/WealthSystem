<?php
/**
 * Enhanced Trading System Dashboard - Production Version with Fallback
 * 
 * This version includes robust error handling and graceful fallback for database connectivity issues
 */

// Set aggressive timeouts to prevent hanging
ini_set('default_socket_timeout', 3);
ini_set('mysql.connect_timeout', 3);
set_time_limit(15);

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the new UI rendering system
require_once 'UiRenderer.php';

/**
 * Production Authentication Service with Non-blocking Database Access
 */
class ProductionAuthenticationService {
    private $isAuthenticated = false;
    private $currentUser = null;
    private $isAdmin = false;
    private $authError = false;
    private $errorMessage = '';
    
    public function __construct() {
        $this->initializeAuthentication();
    }
    
    private function initializeAuthentication() {
        try {
            // Quick pre-flight check - don't hang if files are missing
            if (!$this->preFlightCheck()) {
                throw new Exception('Pre-flight check failed - missing required files');
            }
            
            // Try authentication with timeout protection
            if (!headers_sent()) {
                $this->attemptAuthentication();
            } else {
                throw new Exception('Headers already sent - cannot initialize session');
            }
        } catch (Exception $e) {
            $this->handleAuthFailure($e);
        } catch (Error $e) {
            $this->handleAuthFailure($e);
        }
    }
    
    private function preFlightCheck() {
        $requiredFiles = [
            __DIR__ . '/auth_check.php',
            __DIR__ . '/NavigationManager.php',
            __DIR__ . '/UserAuthDAO.php',
            __DIR__ . '/CommonDAO.php',
            __DIR__ . '/DbConfigClasses.php'
        ];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($file)) {
                $this->errorMessage = "Missing required file: " . basename($file);
                return false;
            }
        }
        
        return true;
    }
    
    private function attemptAuthentication() {
        // Wrap in timeout protection
        $startTime = time();
        $maxTime = 5; // 5 second maximum
        
        try {
            require_once 'auth_check.php';
            
            // Check if we're taking too long
            if (time() - $startTime > $maxTime) {
                throw new Exception('Authentication timeout exceeded');
            }
            
            require_once 'NavigationManager.php';
            
            // Another timeout check
            if (time() - $startTime > $maxTime) {
                throw new Exception('Navigation manager loading timeout');
            }
            
            $navManager = getNavManager();
            $this->currentUser = $navManager->getCurrentUser();
            $this->isAdmin = $navManager->isAdmin();
            $this->isAuthenticated = true;
            
        } catch (Exception $e) {
            throw $e; // Re-throw to be handled by parent
        }
    }
    
    private function handleAuthFailure($error) {
        $this->authError = true;
        $this->currentUser = ['username' => 'Guest (System Unavailable)'];
        $this->isAuthenticated = false;
        $this->isAdmin = false;
        $this->errorMessage = $error->getMessage();
        
        // Log the error
        error_log('Auth system failure: ' . $error->getMessage());
        error_log('Auth error file: ' . $error->getFile() . ':' . $error->getLine());
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
    
    public function getErrorMessage() {
        return $this->errorMessage;
    }
}

/**
 * Menu Service - Single Responsibility for menu generation
 */
class MenuService {
    public static function getMenuItems($currentPage, $isAdmin, $isAuthenticated) {
        $items = [];
        
        // Always show basic navigation
        $items[] = [
            'url' => 'index.php',
            'label' => '🏠 Dashboard',
            'active' => $currentPage === 'dashboard'
        ];
        
        if ($isAuthenticated) {
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
 * Dashboard Content Service - Enhanced system status reporting
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
            '🏠'
        );
        
        // System status component
        $components[] = $this->createSystemStatusCard();
        
        // Main feature cards
        $components[] = $this->createFeatureGrid();
        
        // Quick actions
        $components[] = $this->createQuickActionsCard();
        
        // System information
        $components[] = $this->createSystemInfoCard();
        
        return $components;
    }
    
    private function createSystemStatusCard() {
        if ($this->authService->hasAuthError()) {
            $errorMsg = $this->authService->getErrorMessage();
            $content = 'The system is currently running in safe mode due to authentication system issues. This may be caused by:<br>
                       • Database connectivity problems (external server unreachable)<br>
                       • Missing or corrupted system files<br>
                       • Network timeout issues<br><br>
                       <strong>Error Details:</strong> ' . htmlspecialchars($errorMsg) . '<br><br>
                       Basic functionality remains available, but user authentication and data persistence are limited.';
            
            $actions = [
                ['url' => 'debug_500.php', 'label' => 'Run Full Diagnostics', 'class' => 'btn btn-warning', 'icon' => '🔧'],
                ['url' => 'test_db_simple.php', 'label' => 'Test Database Connection', 'class' => 'btn', 'icon' => '🗄️'],
                ['url' => 'index_fallback.php', 'label' => 'Continue in Safe Mode', 'class' => 'btn btn-success', 'icon' => '✅']
            ];
            
            return UiFactory::createCard(
                '⚠️ System Status: Safe Mode',
                $content,
                'warning',
                '⚠️',
                $actions
            );
        } elseif ($this->authService->isAdmin()) {
            return UiFactory::createCard(
                '🔧 Administrator Access Active',
                'You have administrator privileges and full system access. All authentication and database systems are operational.',
                'success',
                '🔧',
                [
                    ['url' => 'admin_users.php', 'label' => 'User Management', 'class' => 'btn btn-warning', 'icon' => '👥'],
                    ['url' => 'system_status.php', 'label' => 'System Status', 'class' => 'btn btn-success', 'icon' => '📊'],
                    ['url' => 'database.php', 'label' => 'Database Management', 'class' => 'btn', 'icon' => '🗄️']
                ]
            );
        } else {
            return UiFactory::createCard(
                '✅ System Status: Fully Operational',
                'All systems are online and functioning normally. Authentication is active and database connectivity is established.',
                'success',
                '✅'
            );
        }
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
            $actions[] = ['url' => 'index_fallback.php', 'label' => 'Safe Mode', 'class' => 'btn btn-success', 'icon' => '✅'];
        } elseif ($this->authService->isAdmin()) {
            $actions[] = ['url' => 'admin_users.php', 'label' => 'Manage Users', 'class' => 'btn btn-warning', 'icon' => '👥'];
            $actions[] = ['url' => 'database.php', 'label' => 'Database Tools', 'class' => 'btn', 'icon' => '🗄️'];
        }
        
        return UiFactory::createCard(
            '🎯 Quick Actions',
            'Access frequently used features and system management tools',
            'default',
            '🎯',
            $actions
        );
    }
    
    private function createSystemInfoCard() {
        $user = $this->authService->getCurrentUser();
        $authStatus = $this->authService->hasAuthError() 
            ? '⚠️ Safe Mode (Authentication system unavailable)' 
            : '✅ Active and Operational';
        $accessLevel = $this->authService->isAdmin() ? 'Administrator' : 'User';
        $mode = $this->authService->hasAuthError() ? 'Safe mode with limited functionality' : 'Full functionality active';
        
        $content = '<strong>System Architecture:</strong> Enhanced multi-driver database system with SOLID principles<br>
                   <strong>User Management:</strong> Role-based access control with administrative privileges<br>
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
 * Main Application Controller
 */
class DashboardController {
    private $authService;
    private $contentService;
    
    public function __construct() {
        $this->authService = new ProductionAuthenticationService();
        $this->contentService = new DashboardContentService($this->authService);
    }
    
    public function renderPage() {
        $user = $this->authService->getCurrentUser();
        $isAdmin = $this->authService->isAdmin();
        $isAuthenticated = $this->authService->isAuthenticated();
        
        $menuItems = MenuService::getMenuItems('dashboard', $isAdmin, $isAuthenticated);
        
        $title = $this->authService->hasAuthError() 
            ? 'Enhanced Trading System Dashboard (Safe Mode)'
            : 'Enhanced Trading System Dashboard';
        
        $navigation = UiFactory::createNavigationComponent(
            $title,
            'dashboard',
            $user,
            $isAdmin,
            $menuItems,
            $isAuthenticated
        );
        
        $components = $this->contentService->createDashboardComponents();
        
        $pageRenderer = UiFactory::createPageRenderer(
            'Dashboard - Enhanced Trading System',
            $navigation,
            $components
        );
        
        return $pageRenderer->render();
    }
}

// Application Entry Point with Comprehensive Error Handling
try {
    $controller = new DashboardController();
    echo $controller->renderPage();
} catch (Exception $e) {
    // Production-quality error page
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>System Error - Enhanced Trading System</title>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 40px; background: #f5f5f5; }
        .error-container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .error-header { color: #dc3545; margin-bottom: 30px; display: flex; align-items: center; gap: 15px; }
        .error-title { font-size: 28px; margin: 0; }
        .error-message { background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 6px; padding: 20px; margin: 20px 0; color: #721c24; }
        .error-details { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 20px; font-family: "Courier New", monospace; font-size: 14px; margin: 20px 0; }
        .actions { margin-top: 30px; display: flex; gap: 15px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 6px; font-weight: 500; transition: background-color 0.3s; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .recovery-info { background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 6px; padding: 20px; margin: 20px 0; color: #0c5460; }
    </style></head><body>';
    echo '<div class="error-container">';
    echo '<div class="error-header"><span style="font-size: 40px;">🚨</span><h1 class="error-title">System Error</h1></div>';
    echo '<div class="error-message">The Enhanced Trading System encountered an error and could not complete your request. This may be due to database connectivity issues or system configuration problems.</div>';
    echo '<div class="recovery-info"><strong>🔧 Recovery Options:</strong><br>The system includes several fallback modes and diagnostic tools to help resolve this issue. You can continue using the system with limited functionality while the problem is resolved.</div>';
    echo '<div class="error-details">';
    echo '<strong>Error Message:</strong> ' . htmlspecialchars($e->getMessage()) . '<br>';
    echo '<strong>Error Location:</strong> ' . htmlspecialchars(basename($e->getFile())) . ' (Line ' . $e->getLine() . ')<br>';
    echo '<strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '<br>';
    echo '</div>';
    echo '<div class="actions">';
    echo '<a href="index_fallback.php" class="btn btn-success">Continue in Safe Mode</a>';
    echo '<a href="debug_500.php" class="btn btn-warning">Run System Diagnostics</a>';
    echo '<a href="test_db_simple.php" class="btn">Test Database Connection</a>';
    echo '<a href="javascript:history.back()" class="btn">Go Back</a>';
    echo '</div>';
    echo '</div></body></html>';
} catch (Error $e) {
    // Fatal error fallback
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Fatal Error</title></head><body>';
    echo '<h1>🚨 Fatal System Error</h1>';
    echo '<p>The Enhanced Trading System encountered a fatal error and cannot continue.</p>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><a href="index_fallback.php">Try Safe Mode</a> | <a href="debug_500.php">Run Diagnostics</a></p>';
    echo '</body></html>';
}
?>
    
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
        }
        
        return $items;
    }
}

/**
 * Dashboard Content Service - Single Responsibility for dashboard content
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
        
        // Auth error warning if needed
        if ($this->authService->hasAuthError()) {
            $components[] = UiFactory::createCard(
                '⚠️ Database Connectivity Notice',
                'The system is running in fallback mode due to database connectivity issues. This may be because the external database server (fhsws001.ksfraser.com) is unreachable or experiencing issues. Core UI functionality is available, but authentication and data persistence are limited.',
                'warning',
                '⚠️',
                [
                    ['url' => 'debug_500.php', 'label' => 'Run Diagnostics', 'class' => 'btn btn-warning', 'icon' => '�'],
                    ['url' => 'test_db_simple.php', 'label' => 'Test Database Connection', 'class' => 'btn', 'icon' => '🗄️'],
                    ['url' => 'index_fallback.php', 'label' => 'Continue in Fallback Mode', 'class' => 'btn btn-success', 'icon' => '✅']
                ]
            );
        } elseif ($this->authService->isAdmin()) {
            $components[] = UiFactory::createCard(
                '🔧 Administrator Access',
                'You have administrator privileges. You can manage users, system settings, and access all system functions.',
                'warning',
                '🔧',
                [
                    ['url' => 'admin_users.php', 'label' => 'User Management', 'class' => 'btn btn-warning', 'icon' => '👥'],
                    ['url' => 'system_status.php', 'label' => 'System Status', 'class' => 'btn btn-success', 'icon' => '📊'],
                    ['url' => 'database.php', 'label' => 'Database Management', 'class' => 'btn', 'icon' => '🗄️']
                ]
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
        
        if (!$this->authService->hasAuthError() && $this->authService->isAdmin()) {
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
        $authStatus = $this->authService->hasAuthError() ? '❌ Unavailable (Database connectivity issue)' : '✅ Active';
        $accessLevel = $this->authService->isAdmin() ? 'Administrator' : 'User';
        
        $content = '<strong>Database Architecture:</strong> Enhanced multi-driver system with PDO and MySQLi support<br>
                   <strong>User Management:</strong> Role-based access control with admin privileges<br>
                   <strong>Trading Features:</strong> Portfolio management, trade tracking, and performance analytics<br>
                   <strong>Authentication Status:</strong> ' . $authStatus . '<br>
                   <strong>Current User:</strong> ' . htmlspecialchars($user['username']) . '<br>
                   <strong>Access Level:</strong> ' . $accessLevel;
        
        return UiFactory::createCard(
            '📋 System Information',
            $content,
            'info',
            '📋'
        );
    }
}

/**
 * Main Application Controller - Orchestrates the entire page
 * Follows Single Responsibility and Dependency Injection
 */
class DashboardController {
    private $authService;
    private $contentService;
    
    public function __construct() {
        $this->authService = new AuthenticationService();
        $this->contentService = new DashboardContentService($this->authService);
    }
    
    public function renderPage() {
        // Create navigation component
        $user = $this->authService->getCurrentUser();
        $isAdmin = $this->authService->isAdmin();
        $isAuthenticated = $this->authService->isAuthenticated();
        
        $menuItems = MenuService::getMenuItems('dashboard', $isAdmin, $isAuthenticated);
        
        $navigation = UiFactory::createNavigationComponent(
            'Enhanced Trading System Dashboard',
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

// Application Entry Point - Clean and simple
try {
    $controller = new DashboardController();
    echo $controller->renderPage();
} catch (Exception $e) {
    // Fallback error page
    echo '<!DOCTYPE html><html><head><title>Error</title></head><body>';
    echo '<h1>System Error</h1>';
    echo '<p>The system encountered an error. Please try again later.</p>';
    echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</body></html>';
} catch (Error $e) {
    echo '<!DOCTYPE html><html><head><title>Fatal Error</title></head><body>';
    echo '<h1>System Fatal Error</h1>';
    echo '<p>The system encountered a fatal error. Please contact support.</p>';
    echo '</body></html>';
}
?>
