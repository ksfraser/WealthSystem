<?php
/**
 * Analytics Dashboard Controller - Clean UiRenderer Architecture
 * Uses proper MVC pattern with component-based UI rendering
 */

// Include authentication and UI system
require_once 'auth_check.php';
require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';
require_once 'MenuService.php';

// Use the namespaced UI Factory
use Ksfraser\UIRenderer\Factories\UiFactory;

/**
 * Analytics Content Service - Handles business logic for analytics display
 */
class AnalyticsContentService {
    /** @var array */
    private $isAuthenticated;
    /** @var bool */
    private $isAdmin;
    
    public function __construct($isAuthenticated = false, $isAdmin = false) {
        $this->isAuthenticated = $isAuthenticated;
        $this->isAdmin = $isAdmin;
    }
    
    /**
     * Create analytics dashboard components
     */
    public function createAnalyticsComponents() {
        $components = [];
        
        // Header with analytics overview
        $components[] = $this->createHeaderCard();
        
        // Analytics sections
        $components[] = $this->createArchitectureCard();
        $components[] = $this->createMetricsGrid();
        $components[] = $this->createPythonAccessCard();
        $components[] = $this->createChartGenerationCard();
        $components[] = $this->createDatabaseTablesCard();
        
        return $components;
    }
    
    /**
     * Create header card with analytics overview
     */
    private function createHeaderCard() {
        $content = '
            <h1>Analytics Dashboard</h1>
            <p>Portfolio performance analysis and trading metrics</p>
        ';
        
        return UiFactory::createInfoCard('Analytics Dashboard', $content);
    }
    
    /**
     * Create analytics architecture card
     */
    private function createArchitectureCard() {
        $content = '
            <p>The enhanced database now includes dedicated analytics tables:</p>
            <ul>
                <li><strong>portfolio_performance:</strong> Daily performance metrics by market cap</li>
                <li><strong>llm_interactions:</strong> AI/LLM decision tracking</li>
                <li><strong>trading_sessions:</strong> Session-based performance analysis</li>
            </ul>
        ';
        
        return UiFactory::createSuccessCard('📊 Analytics Architecture', $content);
    }
    
    /**
     * Create metrics grid
     */
    private function createMetricsGrid() {
        $performanceCard = UiFactory::createInfoCard('Performance Metrics', '
            <p>Available via Python analytics:</p>
            <ul>
                <li>Total Return</li>
                <li>Daily Returns</li>
                <li>Volatility</li>
                <li>Sharpe Ratio</li>
                <li>Max Drawdown</li>
            </ul>
        ');
        
        $riskCard = UiFactory::createInfoCard('Risk Analytics', '
            <p>Enhanced risk management:</p>
            <ul>
                <li>Position Sizing</li>
                <li>Stop Loss Tracking</li>
                <li>Portfolio Concentration</li>
                <li>Risk Score by Ticker</li>
            </ul>
        ');
        
        $llmCard = UiFactory::createInfoCard('LLM Analytics', '
            <p>AI decision tracking:</p>
            <ul>
                <li>Prompt Types</li>
                <li>Response Times</li>
                <li>Token Usage</li>
                <li>Cost Analysis</li>
            </ul>
        ');
        
        $gridContent = '
            <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                ' . $performanceCard->toHtml() . '
                ' . $riskCard->toHtml() . '
                ' . $llmCard->toHtml() . '
            </div>
        ';
        
        return UiFactory::createCard('Analytics Metrics', $gridContent);
    }
    
    /**
     * Create Python access card
     */
    private function createPythonAccessCard() {
        $content = '
            <p>Due to PHP MySQL limitations, advanced analytics are available via Python:</p>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; font-family: monospace;">
                <h4>Performance Analysis:</h4>
                <p># Run portfolio performance analysis<br>
                python -c "from enhanced_automation import *; engine = EnhancedAutomationEngine(\'micro\'); print(\'Analytics available\')"</p>
                
                <h4>Generate Reports:</h4>
                <p># Create performance charts and reports<br>
                python Generate_Graph.py</p>
                
                <h4>Database Analytics:</h4>
                <p># Query performance data directly<br>
                python -c "import mysql.connector; print(\'Direct database analysis available\')"</p>
            </div>
        ';
        
        return UiFactory::createWarningCard('🐍 Python Analytics Access', $content);
    }
    
    /**
     * Create chart generation card
     */
    private function createChartGenerationCard() {
        $content = '
            <p>Visual analytics are generated using Python matplotlib/plotly:</p>
            <ul>
                <li><strong>Performance Charts:</strong> Scripts/Generate_Graph.py</li>
                <li><strong>Risk Analysis:</strong> Enhanced automation reports</li>
                <li><strong>Trade Analysis:</strong> CSV and database querying</li>
            </ul>
        ';
        
        return UiFactory::createCard('📈 Chart Generation', $content);
    }
    
    /**
     * Create database tables card
     */
    private function createDatabaseTablesCard() {
        $content = '
            <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 0.9em;">
                <p><strong>stock_market_2 (Master Database):</strong></p>
                <ul>
                    <li>portfolio_performance - Daily metrics by portfolio type</li>
                    <li>llm_interactions - AI decision tracking</li>
                    <li>trading_sessions - Session performance</li>
                    <li>trades_enhanced - Multi-market cap trade data</li>
                </ul>
            </div>
        ';
        
        return UiFactory::createCard('Database Tables for Analytics', $content);
    }
}

/**
 * Analytics Controller - Main controller for analytics page
 */
class AnalyticsController {
    private $contentService;
    
    public function __construct() {
        // Get authentication data from auth_check.php globals
        global $currentUser, $isAdmin;
        $this->contentService = new AnalyticsContentService(true, $isAdmin);
    }
    
    /**
     * Render the complete analytics page
     */
    public function renderPage() {
        global $currentUser, $user, $isAdmin;
        
        // Create menu service
        $menuService = new MenuService();
        $menuItems = MenuService::getMenuItems('analytics', $isAdmin, true);
        
        // Generate page components
        $components = $this->contentService->createAnalyticsComponents();
        
        // Create navigation
        $navigation = UiFactory::createNavigation(
            'Analytics Dashboard',
            'analytics',
            $currentUser,
            $isAdmin,
            $menuItems,
            true
        );
        
        // Create page layout
        $pageRenderer = UiFactory::createPage(
            'Analytics Dashboard - Enhanced Trading System',
            $navigation,
            $components
        );
        
        return $pageRenderer->render();
    }
}

// Application Entry Point
try {
    $controller = new AnalyticsController();
    echo $controller->renderPage();
} catch (Exception $e) {
    // Use UiRenderer for error pages
    $errorNavigation = UiFactory::createNavigation(
        'Error - Analytics Dashboard',
        'error',
        ['username' => 'Guest'],
        false,
        [],
        false
    );
    
    $errorCard = UiFactory::createErrorCard(
        'Analytics System Error',
        '<p>The analytics system encountered an error.</p>' .
        '<div style="margin-top: 15px; padding: 10px; background-color: #f8f9fa; border-radius: 4px;">' .
        '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . 
        '</div>' .
        '<div style="margin-top: 15px;">' .
        '<a href="analytics.php" class="btn">Try Again</a> ' .
        '<a href="index.php" class="btn btn-secondary">Return to Dashboard</a>' .
        '</div>'
    );
    
    $pageRenderer = UiFactory::createPage(
        'Error - Analytics Dashboard',
        $errorNavigation,
        [$errorCard]
    );
    
    echo $pageRenderer->render();
}
?>
