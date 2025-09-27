<?php
/**
 * Portfolio Management Controller - Clean UiRenderer Architecture
 * Uses proper MVC pattern with component-based UI rendering
 */

// Include authentication and UI system
require_once 'auth_check.php';
require_once 'UiRenderer.php';
require_once 'RefactoredPortfolioDAO.php';

/**
 * Portfolio Content Service - Handles business logic for portfolio display
 */
class PortfolioContentService {
    /** @var array */
    private $isAuthenticated;
    /** @var bool */
    private $isAdmin;
    
    public function __construct($isAuthenticated = false, $isAdmin = false) {
        $this->isAuthenticated = $isAuthenticated;
        $this->isAdmin = $isAdmin;
    }
    
    /**
     * Create portfolio dashboard components
     */
    public function createPortfolioComponents() {
        $components = [];
        
        // Header with quick actions
        $components[] = $this->createHeaderCard();
        
        // Portfolio sections
        $components[] = $this->createMicroCapPortfolioCard();
        $components[] = $this->createBlueChipPortfolioCard();
        $components[] = $this->createSmallCapPortfolioCard();
        
        // Python command interface
        $components[] = $this->createPythonCommandCard();
        
        // Quick navigation
        $components[] = $this->createQuickActionsCard();
        
        return $components;
    }
    
    private function createHeaderCard() {
        $content = '<p>View and manage trading portfolios across different market categories</p>';
        
        return UiFactory::createInfoCard(
            'Portfolio Management',
            $content
        );
    }
    
    private function createMicroCapPortfolioCard() {
        $csvPaths = [
            '../Scripts and CSV Files/chatgpt_portfolio_update.csv',
            '../Start Your Own/chatgpt_portfolio_update.csv',
            '../data_micro_cap/chatgpt_portfolio_update.csv',
        ];
        
        $dao = new PortfolioDAO($csvPaths);
        $portfolioRows = $dao->readPortfolio();
        $errors = $dao->getErrors();
        
        $content = '<div style="margin-bottom: 20px;">';
        $content .= '<p><strong>Purpose:</strong> CSV-mirrored original data</p>';
        $content .= '<p><strong>Data Directory:</strong> data_micro_cap/</p>';
        
        if ($portfolioRows && count($portfolioRows)) {
            $tableComponent = UiFactory::createTableComponent($portfolioRows, [], [
                'striped' => true,
                'hover' => true,
                'responsive' => true
            ]);
            $content .= $tableComponent->toHtml();
        } else {
            $content .= '<em>No recent micro-cap portfolio data found.</em>';
        }
        
        if ($errors && count($errors)) {
            $content .= '<div style="color:#b00;margin-top:10px;"><strong>Portfolio Data Warning:</strong><ul>';
            foreach ($errors as $err) {
                $content .= '<li>' . htmlspecialchars($err) . '</li>';
            }
            $content .= '</ul></div>';
        }
        
        // Add retry functionality
        $retryData = $dao->getRetryData();
        if ($retryData) {
            $content .= '<form method="post" style="margin-top:10px;">';
            $content .= '<input type="hidden" name="retry_microcap" value="1">';
            $content .= '<button class="btn btn-secondary" type="submit">Retry Last Failed Save</button>';
            $content .= '</form>';
        }
        
        // Handle retry POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['retry_microcap'])) {
            $success = $dao->retryLastOperation();
            if ($success) {
                $content .= '<div style="color:#080;margin-top:10px;">Retry successful!</div>';
            } else {
                $content .= '<div style="color:#b00;margin-top:10px;">Retry failed. Please check errors above.</div>';
            }
        }
        
        // Add performance chart
        $content .= '<div style="margin-top:20px;">';
        $content .= '<h5>Performance Chart</h5>';
        $content .= '<img src="serve_results_png.php?ts=' . time() . '" alt="Performance Results" style="max-width:100%;border:1px solid #ccc;box-shadow:0 2px 8px #aaa;">';
        $content .= '<br><button class="btn btn-secondary" onclick="regenGraph(this)">Regenerate Graph</button>';
        $content .= '<span id="regen-status" style="margin-left:10px;color:#007bff;"></span>';
        $content .= '</div>';
        $content .= '</div>';
        
        return UiFactory::createSuccessCard('Micro-Cap Portfolio', $content);
    }
    
    private function createBlueChipPortfolioCard() {
        $csvPaths = [
            '../Scripts and CSV Files/blue_chip_cap_portfolio.csv',
            '../Start Your Own/blue_chip_cap_portfolio.csv',
            '../data_blue_chip/blue_chip_cap_portfolio.csv',
        ];
        
        $dao = new PortfolioDAO($csvPaths);
        $portfolioRows = $dao->readPortfolio();
        $errors = $dao->getErrors();
        
        $content = '<div style="margin-bottom: 20px;">';
        $content .= '<p><strong>Database:</strong> stock_market_2</p>';
        $content .= '<p><strong>Purpose:</strong> Enhanced features</p>';
        $content .= '<p><strong>Data Directory:</strong> data_blue-chip_cap/</p>';
        
        if ($portfolioRows && count($portfolioRows)) {
            $tableComponent = UiFactory::createTableComponent($portfolioRows, [], [
                'striped' => true,
                'hover' => true,
                'responsive' => true
            ]);
            $content .= $tableComponent->toHtml();
        } else {
            $content .= '<em>No blue-chip portfolio data found.</em>';
        }
        
        if ($errors && count($errors)) {
            $content .= '<div style="color:#b00;margin-top:10px;"><strong>Portfolio Data Warning:</strong><ul>';
            foreach ($errors as $err) {
                $content .= '<li>' . htmlspecialchars($err) . '</li>';
            }
            $content .= '</ul></div>';
        }
        
        // Add retry functionality
        $retryData = $dao->getRetryData();
        if ($retryData) {
            $content .= '<form method="post" style="margin-top:10px;">';
            $content .= '<input type="hidden" name="retry_bluechip" value="1">';
            $content .= '<button class="btn btn-secondary" type="submit">Retry Last Failed Save</button>';
            $content .= '</form>';
        }
        
        // Handle retry POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['retry_bluechip'])) {
            $success = $dao->retryLastOperation();
            if ($success) {
                $content .= '<div style="color:#080;margin-top:10px;">Retry successful!</div>';
            } else {
                $content .= '<div style="color:#b00;margin-top:10px;">Retry failed. Please check errors above.</div>';
            }
        }
        
        $content .= '</div>';
        
        return UiFactory::createInfoCard('Blue-Chip Portfolio', $content);
    }
    
    private function createSmallCapPortfolioCard() {
        $csvPaths = [
            '../Scripts and CSV Files/small_cap_portfolio.csv',
            '../Start Your Own/small_cap_portfolio.csv',
            '../data_small_cap/small_cap_portfolio.csv',
        ];
        
        $dao = new PortfolioDAO($csvPaths);
        $portfolioRows = $dao->readPortfolio();
        $errors = $dao->getErrors();
        
        $content = '<div style="margin-bottom: 20px;">';
        $content .= '<p><strong>Database:</strong> stock_market_2</p>';
        $content .= '<p><strong>Purpose:</strong> Enhanced features</p>';
        $content .= '<p><strong>Data Directory:</strong> data_small_cap/</p>';
        
        if ($portfolioRows && count($portfolioRows)) {
            $tableComponent = UiFactory::createTableComponent($portfolioRows, [], [
                'striped' => true,
                'hover' => true,
                'responsive' => true
            ]);
            $content .= $tableComponent->toHtml();
        } else {
            $content .= '<em>No small-cap portfolio data found.</em>';
        }
        
        if ($errors && count($errors)) {
            $content .= '<div style="color:#b00;margin-top:10px;"><strong>Portfolio Data Warning:</strong><ul>';
            foreach ($errors as $err) {
                $content .= '<li>' . htmlspecialchars($err) . '</li>';
            }
            $content .= '</ul></div>';
        }
        
        // Add retry functionality
        $retryData = $dao->getRetryData();
        if ($retryData) {
            $content .= '<form method="post" style="margin-top:10px;">';
            $content .= '<input type="hidden" name="retry_smallcap" value="1">';
            $content .= '<button class="btn btn-secondary" type="submit">Retry Last Failed Save</button>';
            $content .= '</form>';
        }
        
        // Handle retry POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['retry_smallcap'])) {
            $success = $dao->retryLastOperation();
            if ($success) {
                $content .= '<div style="color:#080;margin-top:10px;">Retry successful!</div>';
            } else {
                $content .= '<div style="color:#b00;margin-top:10px;">Retry failed. Please check errors above.</div>';
            }
        }
        
        $content .= '</div>';
        
        return UiFactory::createWarningCard('Small-Cap Portfolio', $content);
    }
    
    private function createPythonCommandCard() {
        $content = '<p>Use these commands to manage portfolios with full database integration:</p>';
        $content .= '<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace;">';
        
        $commands = [
            ['label' => 'View micro-cap portfolio', 'cmd' => 'enhanced_trading_script', 'code' => 'python ../enhanced_trading_script.py'],
            ['label' => 'Test database connections', 'cmd' => 'test_database_connection', 'code' => 'python ../test_database_connection.py'],
            ['label' => 'Run enhanced automation', 'cmd' => 'enhanced_automation', 'code' => 'python ../enhanced_automation.py']
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
    
    private function createQuickActionsCard() {
        $actions = [
            ['label' => 'Dashboard', 'url' => 'index.php', 'class' => 'btn-primary'],
            ['label' => 'Trade History', 'url' => 'trades.php', 'class' => 'btn-secondary'],
            ['label' => 'Analytics', 'url' => 'analytics.php', 'class' => 'btn-secondary'],
            ['label' => 'Database Manager', 'url' => 'database.php', 'class' => 'btn-secondary']
        ];
        
        return UiFactory::createCardComponent('Quick Actions', '', 'default', '', $actions);
    }
}

/**
 * Portfolio Controller - Clean MVC pattern
 */
class PortfolioController {
    /** @var PortfolioContentService */
    private $contentService;
    
    public function __construct() {
        // Get authentication state from auth_check.php
        global $user, $isAdmin;
        $isAuthenticated = isset($user) && !empty($user);
        
        $this->contentService = new PortfolioContentService($isAuthenticated, $isAdmin ?? false);
    }
    
    public function renderPage() {
        global $user, $isAdmin;
        
        $isAuthenticated = isset($user) && !empty($user);
        $menuItems = MenuService::getMenuItems('portfolios', $isAdmin ?? false, $isAuthenticated);
        
        $navigation = UiFactory::createNavigationComponent(
            'Enhanced Trading System - Portfolios',
            'portfolios',
            $user ?? ['username' => 'Guest'],
            $isAdmin ?? false,
            $menuItems,
            $isAuthenticated
        );
        
        $components = $this->contentService->createPortfolioComponents();
        
        $additionalJs = '
        function regenGraph(btn) {
            btn.disabled = true;
            btn.innerText = "Regenerating...";
            var status = document.getElementById("regen-status");
            status.innerText = "";
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "run_python_command.php");
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                btn.disabled = false;
                btn.innerText = "Regenerate Graph";
                try {
                    var resp = JSON.parse(xhr.responseText);
                    status.innerText = resp.output ? "Graph regenerated!" : (resp.error || "No output.");
                    var img = document.querySelector("img[alt=\\"Performance Results\\"]");
                    if (img) img.src = "../Results.png?ts=" + new Date().getTime();
                } catch(e) {
                    status.innerText = "Error: " + xhr.responseText;
                }
            };
            xhr.send("command_key=scripts_and_csv_files_generate_graph");
        }
        
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
            'Portfolio Management - Enhanced Trading System',
            $navigation,
            $components,
            ['additionalJs' => $additionalJs]
        );
        
        return $pageRenderer->render();
    }
}

// Application Entry Point
try {
    $controller = new PortfolioController();
    echo $controller->renderPage();
} catch (Exception $e) {
    // Use UiRenderer for error pages
    $errorNavigation = UiFactory::createNavigationComponent(
        'Error - Portfolio Management',
        'error',
        ['username' => 'Guest'],
        false,
        [],
        false
    );
    
    $errorCard = UiFactory::createErrorCard(
        'Portfolio System Error',
        '<p>The portfolio system encountered an error.</p>' .
        '<div style="margin-top: 15px; padding: 10px; background-color: #f8f9fa; border-radius: 4px;">' .
        '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . 
        '</div>' .
        '<div style="margin-top: 15px;">' .
        '<a href="portfolios.php" class="btn">Try Again</a> ' .
        '<a href="index.php" class="btn btn-secondary">Return to Dashboard</a>' .
        '</div>'
    );
    
    $pageRenderer = UiFactory::createPageRenderer(
        'Error - Portfolio Management',
        $errorNavigation,
        [$errorCard]
    );
    
    echo $pageRenderer->render();
}
?>
