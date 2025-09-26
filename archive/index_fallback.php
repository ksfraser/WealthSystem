<?php
/**
 * Index.php - Fallback Version (Database-Free)
 * 
 * This version works without database connectivity for testing purposes
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the UI rendering system
require_once 'UiRenderer.php';

/**
 * Mock Authentication Service - Single Responsibility for auth logic without DB
 */
class MockAuthenticationService {
    private $isAuthenticated = true; // Default to authenticated for testing
    private $currentUser = ['username' => 'TestUser'];
    private $isAdmin = false;
    private $authError = false;
    
    public function __construct() {
        // Simple mock authentication - no database required
        $this->isAuthenticated = true;
        $this->currentUser = ['username' => 'TestUser (Mock Auth)'];
        $this->isAdmin = false;
        $this->authError = false;
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
        
        // Database connectivity warning
        $components[] = UiFactory::createCard(
            '⚠️ Database Connectivity Notice',
            'The system is currently running in fallback mode without database connectivity. The external database server may be unreachable. Core functionality is available but authentication and data persistence are limited.',
            'warning',
            '⚠️',
            [
                ['url' => 'debug_500.php', 'label' => 'Run Diagnostics', 'class' => 'btn btn-warning', 'icon' => '🔧'],
                ['url' => 'test_db_simple.php', 'label' => 'Test Database', 'class' => 'btn', 'icon' => '🗄️']
            ]
        );
        
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
            ['url' => 'trades.php', 'label' => 'Record Trade', 'class' => 'btn', 'icon' => '📋'],
            ['url' => 'debug_500.php', 'label' => 'System Diagnostics', 'class' => 'btn btn-warning', 'icon' => '🔧']
        ];
        
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
        $authStatus = '⚠️ Mock Authentication (Database unavailable)';
        $accessLevel = $this->authService->isAdmin() ? 'Administrator' : 'User';
        
        $content = '<strong>Database Architecture:</strong> Enhanced multi-driver system with PDO and MySQLi support<br>
                   <strong>User Management:</strong> Role-based access control with admin privileges<br>
                   <strong>Trading Features:</strong> Portfolio management, trade tracking, and performance analytics<br>
                   <strong>Authentication Status:</strong> ' . $authStatus . '<br>
                   <strong>Current User:</strong> ' . htmlspecialchars($user['username']) . '<br>
                   <strong>Access Level:</strong> ' . $accessLevel . '<br>
                   <strong>Mode:</strong> Fallback mode (no database connectivity)';
        
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
        $this->authService = new MockAuthenticationService();
        $this->contentService = new DashboardContentService($this->authService);
    }
    
    public function renderPage() {
        // Create navigation component
        $user = $this->authService->getCurrentUser();
        $isAdmin = $this->authService->isAdmin();
        $isAuthenticated = $this->authService->isAuthenticated();
        
        $menuItems = MenuService::getMenuItems('dashboard', $isAdmin, $isAuthenticated);
        
        $navigation = UiFactory::createNavigationComponent(
            'Enhanced Trading System Dashboard (Fallback Mode)',
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
    echo '<p>File: ' . htmlspecialchars($e->getFile()) . '</p>';
    echo '<p>Line: ' . $e->getLine() . '</p>';
    echo '</body></html>';
} catch (Error $e) {
    echo '<!DOCTYPE html><html><head><title>Fatal Error</title></head><body>';
    echo '<h1>System Fatal Error</h1>';
    echo '<p>The system encountered a fatal error. Please contact support.</p>';
    echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>File: ' . htmlspecialchars($e->getFile()) . '</p>';
    echo '<p>Line: ' . $e->getLine() . '</p>';
    echo '</body></html>';
}
?>
