<?php
/**
 * Enhanced Trading System Dashboard - Compatible with existing F30 Apache auth
 * 
 * Uses the same authentication system that works for the admin dashboard
 */

// Enable error reporting for debugging in development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the UI rendering system - use namespace version directly
require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';
require_once 'SessionManager.php';
require_once 'MenuService.php';
require_once 'DatabaseConnectivityNotice.php';

// Use the namespaced UI Factory
use Ksfraser\UIRenderer\Factories\UiFactory;
use Ksfraser\UIRenderer\Contracts\ComponentInterface;

/**
 * Compatible Authentication Service using existing working auth system
 */
class CompatibleAuthenticationService {
    private $isAuthenticated = false;
    private $currentUser = null;
    private $isAdmin = false;
    private $authError = false;
    private $errorMessage = '';
    private $userAuth = null;
    private $sessionManager = null;
    
    public function __construct() {
        $this->initializeAuthentication();
    }
    
    private function initializeAuthentication() {
        try {
            // Use centralized SessionManager instead of manual session handling
            $this->sessionManager = SessionManager::getInstance();
            
            // Check if session is properly initialized
            if (!$this->sessionManager->isSessionActive()) {
                $error = $this->sessionManager->getInitializationError();
                if ($error) {
                    throw new Exception("Session initialization failed: " . $error);
                }
            }
            
            // Initialize authentication via constructor
            require_once 'UserAuthDAO.php';
            $this->userAuth = new UserAuthDAO();
            
            // Attempt authentication
            if ($this->userAuth->isLoggedIn()) {
                $this->isAuthenticated = true;
                $this->currentUser = $this->userAuth->getCurrentUser();
                $this->isAdmin = $this->userAuth->isAdmin();
            } else {
                // Not authenticated - set mock user for compatibility
                $this->isAdmin = false;
            }
            
        } catch (Exception $e) {
            // Log error but continue with mock authentication for compatibility
            error_log("Authentication error: " . $e->getMessage());
            
            // Set fallback state with mock authentication
            $this->authError = true;
            $this->errorMessage = $e->getMessage();
            $this->isAdmin = false;
            $this->isAuthenticated = false;
            
            // Mock user for fallback mode
            $this->currentUser = [
                'id' => 0,
                'username' => 'demo_user',
                'email' => 'demo@example.com',
                'is_admin' => 0
            ];
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
    
    public function getErrorMessage() {
        return $this->errorMessage;
    }
}

/**
 * Dashboard Content Service - Creates dashboard components with proper SRP
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
        
        // Database connectivity notice - handled by dedicated class with proper SRP
        $dbNotice = DatabaseConnectivityNotice::createForUser($this->authService);
        $dbComponent = $dbNotice->getNoticeComponent();
        if ($dbComponent !== null) {
            $components[] = $dbComponent;
        }
        
        // Main feature cards
        $featureCards = $this->createFeatureGrid();
        foreach ($featureCards as $card) {
            $components[] = $card;
        }
        
        // Quick actions
        $components[] = $this->createQuickActionsCard();
        
        // User/System information (SRP compliant - delegates to SystemStatusService)
        $components[] = $this->createUserInfoCard();
        
        return $components;
    }
    
    private function createFeatureGrid() {
        $features = [
            [
                'title' => '📊 Portfolio Management',
                'description' => 'Track and manage your investment portfolios with real-time updates',
                'icon' => '📊',
                'actions' => [
                    ['url' => 'portfolio_overview.php', 'label' => 'View Portfolios', 'class' => 'btn btn-primary', 'icon' => '👁️']
                ]
            ],
            [
                'title' => '💹 Trade Execution',
                'description' => 'Execute trades and monitor market movements',
                'icon' => '💹',
                'actions' => [
                    ['url' => 'trading_interface.php', 'label' => 'Start Trading', 'class' => 'btn btn-success', 'icon' => '🚀']
                ]
            ],
            [
                'title' => '📈 Performance Analytics',
                'description' => 'Analyze your trading performance with advanced metrics',
                'icon' => '📈',
                'actions' => [
                    ['url' => 'analytics_dashboard.php', 'label' => 'View Analytics', 'class' => 'btn btn-info', 'icon' => '📊']
                ]
            ]
        ];
        
        // Return multiple cards as separate components instead of trying to render them as HTML
        $cards = [];
        foreach ($features as $feature) {
            $cards[] = UiFactory::createCard(
                $feature['title'],
                $feature['description'],
                'default',
                $feature['icon'],
                $feature['actions']
            );
        }
        
        return $cards;
    }
    
    private function createQuickActionsCard() {
        $actions = [
            ['url' => 'quick_trade.php', 'label' => 'Quick Trade', 'class' => 'btn btn-primary me-2', 'icon' => '⚡'],
            ['url' => 'market_scanner.php', 'label' => 'Market Scanner', 'class' => 'btn btn-secondary me-2', 'icon' => '🔍'],
            ['url' => 'watchlist.php', 'label' => 'Watchlist', 'class' => 'btn btn-outline-primary me-2', 'icon' => '👁️']
        ];
        
        return UiFactory::createCard(
            '⚡ Quick Actions',
            'Access frequently used trading tools and features',
            'default',
            '⚡',
            $actions
        );
    }
    
    private function createUserInfoCard() {
        // Use dedicated SystemStatusService for status information (SRP compliance)
        require_once 'SystemStatusService.php';
        $statusService = new SystemStatusService();
        
        // For regular users, show simple account info
        // For admins, they get system status info
        if ($this->authService->isAdmin()) {
            return $statusService->createSystemStatusCard($this->authService);
        } else {
            return $statusService->createUserInfoCard($this->authService);
        }
    }
}

/**
 * Main page renderer
 */
class TradingDashboard {
    private $authService;
    private $contentService;
    
    public function __construct() {
        $this->authService = new CompatibleAuthenticationService();
        $this->contentService = new DashboardContentService($this->authService);
    }
    
    public function render() {
        try {
            // Use SRP-compliant NavigationService for menu items
            require_once 'NavigationService.php';
            $navigationService = new NavigationService();
            
            // Get user data from our auth service
            $user = $this->authService->getCurrentUser();
            $isAdmin = $this->authService->isAdmin();
            $isAuthenticated = $this->authService->isAuthenticated();
            
            // Get menu items from NavigationService (SRP compliance)
            $menuItems = $navigationService->getMenuItems('dashboard');
            
            // Create navigation component using UiFactory
            $navigation = UiFactory::createNavigation(
                'Enhanced Trading Dashboard',
                'dashboard',
                $user,
                $isAdmin,
                $menuItems,
                $isAuthenticated
            );
            
            // Create dashboard content
            $components = $this->contentService->createDashboardComponents();
            
            // Build the page
            $title = $this->authService->hasAuthError() 
                ? 'Trading Dashboard (Fallback Mode)' 
                : 'Enhanced Trading Dashboard';
                
            $pageRenderer = UiFactory::createPage($title, $navigation, $components);
            
            return $pageRenderer->render();
            
        } catch (Exception $e) {
            return $this->renderErrorPage($e);
        }
    }
    
    private function renderErrorPage($e) {
        $errorNavigation = '<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <span class="navbar-brand">⚠️ System Error</span>
            </div>
        </nav>';
        
        $fatalErrorCard = UiFactory::createCard(
            '💥 Fatal Error',
            '<div style="color: #721c24; background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px;">' .
            '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '<br>' .
            'File: ' . htmlspecialchars($e->getFile()) . '<br>' .
            'Line: ' . $e->getLine() .
            '</div>' .
            '<div style="margin-top: 20px;">' .
            '<a href="index.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Reload Page</a>' .
            '</div>',
            'error'
        );
        
        $pageRenderer = UiFactory::createPage(
            'Fatal Error - Enhanced Trading System',
            $errorNavigation,
            [$fatalErrorCard]
        );
        
        return $pageRenderer->render();
    }
}

// Initialize and render the dashboard
$dashboard = new TradingDashboard();
echo $dashboard->render();
?>