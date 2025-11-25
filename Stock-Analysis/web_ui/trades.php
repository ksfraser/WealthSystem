<?php
/**
 * Trade History Controller - Clean UiRenderer Architecture
 * Uses proper MVC pattern with component-based UI rendering
 */

// Include authentication and UI system
require_once 'auth_check.php';
require_once 'UiRenderer.php';
require_once 'RefactoredTradeLogDAO.php';
require_once 'MenuService.php';

/**
 * Trade Content Service - Handles business logic for trade history display
 */
class TradeContentService {
    /** @var bool */
    private $isAuthenticated;
    /** @var bool */
    private $isAdmin;
    
    public function __construct($isAuthenticated = false, $isAdmin = false) {
        $this->isAuthenticated = $isAuthenticated;
        $this->isAdmin = $isAdmin;
    }
    
    /**
     * Create trade history dashboard components
     */
    public function createTradeComponents() {
        $components = [];
        
        // Header
        $components[] = $this->createHeaderCard();
        
        // Filter form
        $components[] = $this->createFilterCard();
        
        // Trade data sections
        $components[] = $this->createTradeDataCard();
        
        // Python command interface
        $components[] = $this->createPythonCommandCard();
        
        // File locations info
        $components[] = $this->createFileLocationsCard();
        
        // Quick navigation
        $components[] = $this->createQuickActionsCard();
        
        return $components;
    }
    
    private function createHeaderCard() {
        $content = '<p>View trading history and transaction logs across all market categories</p>';
        $content .= '<p>✅ Trade history is stored in multiple locations based on the enhanced database architecture</p>';
        
        return UiFactory::createSuccessCard(
            'Trade History Management',
            $content
        );
    }
    
    private function createFilterCard() {
        $filters = [
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'ticker' => $_GET['ticker'] ?? '',
            'cost_min' => $_GET['cost_min'] ?? '',
            'cost_max' => $_GET['cost_max'] ?? '',
        ];
        
        $content = '<form method="get" class="flex flex-wrap gap-2 align-center" style="margin-bottom:20px;">';
        $content .= '<div class="form-group">';
        $content .= '<label class="form-label">Date From</label>';
        $content .= '<input type="date" name="date_from" value="' . htmlspecialchars($filters['date_from']) . '" class="form-control">';
        $content .= '</div>';
        
        $content .= '<div class="form-group">';
        $content .= '<label class="form-label">Date To</label>';
        $content .= '<input type="date" name="date_to" value="' . htmlspecialchars($filters['date_to']) . '" class="form-control">';
        $content .= '</div>';
        
        $content .= '<div class="form-group">';
        $content .= '<label class="form-label">Ticker</label>';
        $content .= '<input type="text" name="ticker" value="' . htmlspecialchars($filters['ticker']) . '" placeholder="e.g. ABEO" class="form-control">';
        $content .= '</div>';
        
        $content .= '<div class="form-group">';
        $content .= '<label class="form-label">Cost Min</label>';
        $content .= '<input type="number" step="any" name="cost_min" value="' . htmlspecialchars($filters['cost_min']) . '" class="form-control">';
        $content .= '</div>';
        
        $content .= '<div class="form-group">';
        $content .= '<label class="form-label">Cost Max</label>';
        $content .= '<input type="number" step="any" name="cost_max" value="' . htmlspecialchars($filters['cost_max']) . '" class="form-control">';
        $content .= '</div>';
        
        $content .= '<div class="form-group" style="align-self: flex-end;">';
        $content .= '<button class="btn" type="submit">Filter</button>';
        $content .= '</div>';
        $content .= '</form>';
        
        return UiFactory::createInfoCard('Filter Trade Data', $content);
    }
    
    private function createTradeDataCard() {
        $filters = [
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'ticker' => $_GET['ticker'] ?? '',
            'cost_min' => $_GET['cost_min'] ?? '',
            'cost_max' => $_GET['cost_max'] ?? '',
        ];
        
        $content = '<div class="grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">';
        
        // Micro-Cap Trade Log
        $content .= '<div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background:#fafbfc;">';
        $content .= '<h4>Micro-Cap Trade Log (CSV)</h4>';
        
        $dao = new TradeLogDAO('../Scripts and CSV Files/chatgpt_trade_log.csv');
        $rows = $dao->readTradeLog($filters);
        $errors = $dao->getErrors();
        
        if ($rows && count($rows)) {
            $tableComponent = UiFactory::createTableComponent($rows, [], [
                'striped' => true,
                'hover' => true,
                'responsive' => true,
                'class' => 'table-sm'
            ]);
            $content .= $tableComponent->toHtml();
        } else {
            $content .= '<em>No micro-cap trade log data found.</em>';
        }
        
        if ($errors && count($errors)) {
            $content .= '<div style="color:#b00;margin-top:10px;"><strong>Warning:</strong><ul>';
            foreach ($errors as $err) {
                $content .= '<li>' . htmlspecialchars($err) . '</li>';
            }
            $content .= '</ul></div>';
        }
        
        $content .= '</div>';
        
        // Blue-Chip Trade Log
        $content .= '<div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background:#fafbfc;">';
        $content .= '<h4>Blue-Chip Trade Log (CSV)</h4>';
        
        $dao = new TradeLogDAO('../Scripts and CSV Files/blue_chip_cap_trade_log.csv');
        $rows = $dao->readTradeLog($filters);
        $errors = $dao->getErrors();
        
        if ($rows && count($rows)) {
            $tableComponent = UiFactory::createTableComponent($rows, [], [
                'striped' => true,
                'hover' => true,
                'responsive' => true,
                'class' => 'table-sm'
            ]);
            $content .= $tableComponent->toHtml();
        } else {
            $content .= '<em>No blue-chip trade log data found.</em>';
        }
        
        if ($errors && count($errors)) {
            $content .= '<div style="color:#b00;margin-top:10px;"><strong>Warning:</strong><ul>';
            foreach ($errors as $err) {
                $content .= '<li>' . htmlspecialchars($err) . '</li>';
            }
            $content .= '</ul></div>';
        }
        
        $content .= '</div>';
        $content .= '</div>';
        
        return UiFactory::createInfoCard('Trade Log Data', $content);
    }
    
    private function createPythonCommandCard() {
        $content = '<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace;">';
        
        $commands = [
            ['label' => 'View recent trades', 'cmd' => 'enhanced_trading_script', 'code' => 'python enhanced_trading_script.py'],
            ['label' => 'Database query', 'cmd' => 'enhanced_trading_script_db_query', 'code' => 'python -c "from enhanced_trading_script import *; engine = create_trading_engine(\'micro\'); print(\'Trade data available via Python\')"']
        ];
        
        foreach ($commands as $command) {
            $content .= '<div style="margin-bottom:10px;">';
            $content .= '<p><strong># ' . $command['label'] . '</strong><br>';
            $content .= '<code>' . $command['code'] . '</code>';
            $content .= '<button class="btn btn-secondary" onclick="runPy(\'' . $command['cmd'] . '\', this)">Run</button></p>';
            $content .= '</div>';
        }
        
        $content .= '<div id="py-output" style="margin-top:10px; color:#333;"></div>';
        $content .= '</div>';
        
        return UiFactory::createInfoCard('Python Command Line Access', $content);
    }
    
    private function createFileLocationsCard() {
        $content = '<ul>';
        $content .= '<li><strong>Micro-cap:</strong> data_micro_cap/micro_cap_trade_log.csv</li>';
        $content .= '<li><strong>Blue-chip:</strong> data_blue-chip_cap/blue-chip_cap_trade_log.csv</li>';
        $content .= '<li><strong>Small-cap:</strong> data_small_cap/small_cap_trade_log.csv</li>';
        $content .= '</ul>';
        
        return UiFactory::createInfoCard('File Locations', $content);
    }
    
    private function createQuickActionsCard() {
        $actions = [
            ['label' => 'Dashboard', 'url' => 'index.php', 'class' => 'btn-primary'],
            ['label' => 'View Portfolios', 'url' => 'portfolios.php', 'class' => 'btn-secondary'],
            ['label' => 'Analytics', 'url' => 'analytics.php', 'class' => 'btn-secondary'],
            ['label' => 'Database Manager', 'url' => 'database.php', 'class' => 'btn-secondary']
        ];
        
        return UiFactory::createCardComponent('Quick Actions', '', 'default', '', $actions);
    }
}

/**
 * Trade Controller - Clean MVC pattern
 */
class TradeController {
    /** @var TradeContentService */
    private $contentService;
    
    public function __construct() {
        // Get authentication state from auth_check.php
        global $user, $isAdmin;
        $isAuthenticated = isset($user) && !empty($user);
        
        $this->contentService = new TradeContentService($isAuthenticated, $isAdmin ?? false);
    }
    
    public function renderPage() {
        global $user, $isAdmin;
        
        $isAuthenticated = isset($user) && !empty($user);
        $menuItems = MenuService::getMenuItems('trades', $isAdmin ?? false, $isAuthenticated);
        
        $navigation = UiFactory::createNavigationComponent(
            'Enhanced Trading System - Trade History',
            'trades',
            $user ?? ['username' => 'Guest'],
            $isAdmin ?? false,
            $menuItems,
            $isAuthenticated
        );
        
        $components = $this->contentService->createTradeComponents();
        
        $additionalJs = '
        function runPy(cmdKey, btn) {
            btn.disabled = true;
            btn.innerText = "Running...";
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "run_python_command.php");
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                btn.disabled = false;
                btn.innerText = "Run";
                var out = document.getElementById("py-output");
                try {
                    var resp = JSON.parse(xhr.responseText);
                    out.innerText = resp.output || resp.error || "No output.";
                } catch(e) {
                    out.innerText = "Error: " + xhr.responseText;
                }
            };
            xhr.send("command_key=" + encodeURIComponent(cmdKey));
        }
        ';
        
        $pageRenderer = UiFactory::createPageRenderer(
            'Trade History - Enhanced Trading System',
            $navigation,
            $components,
            ['additionalJs' => $additionalJs]
        );
        
        return $pageRenderer->render();
    }
}

// Application Entry Point
try {
    $controller = new TradeController();
    echo $controller->renderPage();
} catch (Exception $e) {
    // Use UiRenderer for error pages
    $errorNavigation = UiFactory::createNavigationComponent(
        'Error - Trade History',
        'error',
        ['username' => 'Guest'],
        false,
        [],
        false
    );
    
    $errorCard = UiFactory::createErrorCard(
        'Trade System Error',
        '<p>The trade history system encountered an error.</p>' .
        '<div style="margin-top: 15px; padding: 10px; background-color: #f8f9fa; border-radius: 4px;">' .
        '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . 
        '</div>' .
        '<div style="margin-top: 15px;">' .
        '<a href="trades.php" class="btn">Try Again</a> ' .
        '<a href="index.php" class="btn btn-secondary">Return to Dashboard</a>' .
        '</div>'
    );
    
    $pageRenderer = UiFactory::createPageRenderer(
        'Error - Trade History',
        $errorNavigation,
        [$errorCard]
    );
    
    echo $pageRenderer->render();
}
?>
