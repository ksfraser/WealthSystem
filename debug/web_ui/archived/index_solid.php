<?php
/**
 * Enhanced Trading System Dashboard - Refactored with SOLID Principles
 * 
 * This file demonstrates:
 * - Single Responsibility: Each class has one reason to change
 * - Open/Closed: Components can be extended without modification
 * - Liskov Substitution: All components implement their interfaces properly
 * - Interface Segregation: Separate interfaces for different concerns
 * - Dependency Injection: Dependencies are injected, not hard-coded
 */

// Include the new UI rendering system
require_once 'UiRenderer.php';

/**
 * Authentication Service - Single Responsibility for auth logic
 */
class AuthenticationService {
    private $userAuth;
    private $isAuthenticated = false;
    private $currentUser = null;
    private $isAdmin = false;
    private $authError = false;
    
    public function __construct() {
        $this->initializeAuthentication();
    }
    
    private function initializeAuthentication() {
        try {
            if (!headers_sent()) {
                require_once 'auth_check.php';
                require_once 'NavigationManager.php';
                
                $navManager = getNavManager();
                $this->currentUser = $navManager->getCurrentUser();
                $this->isAdmin = $navManager->isAdmin();
                $this->isAuthenticated = true;
            }
        } catch (Exception $e) {
            $this->authError = true;
            $this->currentUser = ['username' => 'Guest (Auth Unavailable)'];
            error_log('Auth error: ' . $e->getMessage());
        } catch (Error $e) {
            $this->authError = true;
            $this->currentUser = ['username' => 'Guest (Auth Unavailable)'];
            error_log('Auth fatal error: ' . $e->getMessage());
        }
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
        
        // Auth error warning if needed
        if ($this->authService->hasAuthError()) {
            $components[] = UiFactory::createCard(
                '⚠️ Authentication System Unavailable',
                'The authentication system is currently unavailable, likely due to database connectivity issues. Please check database connectivity. You can still access basic functionality.',
                'warning',
                '⚠️',
                [
                    ['url' => 'login.php', 'label' => 'Try Login Page', 'class' => 'btn', 'icon' => '🔐'],
                    ['url' => 'database.php', 'label' => 'Check Database Status', 'class' => 'btn btn-warning', 'icon' => '🗄️']
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
