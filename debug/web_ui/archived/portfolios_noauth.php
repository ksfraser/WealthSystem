<?php
/**
 * Portfolio Management Controller - Test Version (No Auth)
 * Uses proper MVC pattern with component-based UI rendering
 */

// Bypass auth for testing
$currentUser = ['username' => 'test', 'user_id' => 1];
$user = $currentUser;
$isAdmin = false;

// Include UI system
require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';
require_once 'RefactoredPortfolioDAO.php';
require_once 'MenuService.php';

// Use the namespaced UI Factory
use Ksfraser\UIRenderer\Factories\UiFactory;

/**
 * Portfolio Content Service - Handles business logic for portfolio display
 */
class PortfolioContentService {
    /** @var array */
    private $isAuthenticated;
    /** @var bool */
    private $isAdmin;
    
    public function __construct($isAuthenticated = true, $isAdmin = false) {
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
    
    /**
     * Create header card with quick actions
     */
    private function createHeaderCard() {
        $content = '
            <div class="row">
                <div class="col-md-6">
                    <h5>📊 Investment Portfolios</h5>
                    <p class="text-muted">Track and manage your investment portfolios across micro-cap, blue-chip, and small-cap segments.</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group" role="group">
                        <a href="trades.php" class="btn btn-outline-primary btn-sm">💼 Trades</a>
                        <a href="analytics.php" class="btn btn-outline-success btn-sm">📈 Analytics</a>
                        <a href="automation.php" class="btn btn-outline-info btn-sm">🤖 Automation</a>
                    </div>
                </div>
            </div>';
        
        return UiFactory::createInfoCard('Portfolio Management', $content);
    }
    
    /**
     * Create micro-cap portfolio card with filtering
     */
    private function createMicroCapPortfolioCard() {
        $dao = new RefactoredPortfolioDAO();
        
        try {
            $portfolios = $dao->getMicroCapPortfolios();
            $errorMessage = '';
            
            if (empty($portfolios)) {
                $errorMessage = '⚠️ No micro-cap portfolio data available from database.';
            }
        } catch (Exception $e) {
            $portfolios = [];
            $errorMessage = '❌ Database Error: ' . htmlspecialchars($e->getMessage());
        }
        
        // Filter controls
        $filterControls = '
            <div class="row mb-3">
                <div class="col-md-8">
                    <div class="btn-group" role="group" aria-label="Portfolio filters">
                        <input type="radio" class="btn-check" name="microCapFilter" id="micro_all" value="all" checked>
                        <label class="btn btn-outline-primary btn-sm" for="micro_all">All</label>
                        
                        <input type="radio" class="btn-check" name="microCapFilter" id="micro_recent" value="recent">
                        <label class="btn btn-outline-success btn-sm" for="micro_recent">Recent</label>
                        
                        <input type="radio" class="btn-check" name="microCapFilter" id="micro_profitable" value="profitable">
                        <label class="btn btn-outline-warning btn-sm" for="micro_profitable">Profitable</label>
                        
                        <input type="radio" class="btn-check" name="microCapFilter" id="micro_large" value="large">
                        <label class="btn btn-outline-info btn-sm" for="micro_large">Large Positions</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select form-select-sm" id="microCapSort">
                        <option value="symbol">Sort by Symbol</option>
                        <option value="current_value">Sort by Value</option>
                        <option value="profit_loss">Sort by P&L</option>
                        <option value="percentage_gain_loss">Sort by % Change</option>
                        <option value="last_updated">Sort by Date</option>
                    </select>
                </div>
            </div>';
        
        // Portfolio table
        $tableContent = '';
        if (!empty($portfolios)) {
            $tableHeaders = ['Symbol', 'Shares', 'Avg Cost', 'Current Price', 'Current Value', 'P&L', '% Change', 'Last Updated'];
            $tableRows = [];
            
            foreach ($portfolios as $portfolio) {
                $profitLoss = $portfolio['profit_loss'] ?? 0;
                $profitClass = $profitLoss >= 0 ? 'text-success' : 'text-danger';
                $profitIcon = $profitLoss >= 0 ? '📈' : '📉';
                
                $percentageChange = $portfolio['percentage_gain_loss'] ?? 0;
                $percentageClass = $percentageChange >= 0 ? 'text-success' : 'text-danger';
                
                $tableRows[] = [
                    '<strong>' . htmlspecialchars($portfolio['symbol']) . '</strong>',
                    number_format($portfolio['shares'], 2),
                    '$' . number_format($portfolio['average_cost'], 2),
                    '$' . number_format($portfolio['current_price'], 2),
                    '$' . number_format($portfolio['current_value'], 2),
                    '<span class="' . $profitClass . '">' . $profitIcon . ' $' . number_format($profitLoss, 2) . '</span>',
                    '<span class="' . $percentageClass . '">' . number_format($percentageChange, 2) . '%</span>',
                    date('M j, Y', strtotime($portfolio['last_updated']))
                ];
            }
            
            $tableContent = UiFactory::createTable([
                'headers' => $tableHeaders,
                'rows' => $tableRows,
                'striped' => true,
                'id' => 'microCapTable'
            ]);
        } else {
            $tableContent = '<div class="alert alert-info">' . $errorMessage . '</div>';
        }
        
        return UiFactory::createCard([
            'title' => '🚀 Micro-Cap Portfolio (' . count($portfolios) . ' positions)',
            'content' => $filterControls . $tableContent,
            'variant' => 'primary'
        ]);
    }
    
    /**
     * Create blue-chip portfolio card
     */
    private function createBlueChipPortfolioCard() {
        // Check for CSV file
        $csvFile = __DIR__ . '/data_blue-chip_cap/chatgpt_portfolio_update.csv';
        $errorMessage = '';
        
        if (!file_exists($csvFile)) {
            $errorMessage = '❌ CSV file not found: ' . $csvFile;
        } elseif (!is_readable($csvFile)) {
            $errorMessage = '❌ CSV file not readable: ' . $csvFile;
        }
        
        $content = $errorMessage ? 
            '<div class="alert alert-warning">' . $errorMessage . '</div>' :
            '<div class="alert alert-info">📁 Blue-chip portfolio data available in CSV format.</div>';
            
        return UiFactory::createCard([
            'title' => '🏛️ Blue-Chip Portfolio',
            'content' => $content,
            'variant' => 'success'
        ]);
    }
    
    /**
     * Create small-cap portfolio card
     */
    private function createSmallCapPortfolioCard() {
        // Check for CSV file
        $csvFile = __DIR__ . '/data_small_cap/chatgpt_portfolio_update.csv';
        $errorMessage = '';
        
        if (!file_exists($csvFile)) {
            $errorMessage = '❌ CSV file not found: ' . $csvFile;
        } elseif (!is_readable($csvFile)) {
            $errorMessage = '❌ CSV file not readable: ' . $csvFile;
        }
        
        $content = $errorMessage ? 
            '<div class="alert alert-warning">' . $errorMessage . '</div>' :
            '<div class="alert alert-info">📁 Small-cap portfolio data available in CSV format.</div>';
            
        return UiFactory::createCard([
            'title' => '📊 Small-Cap Portfolio',
            'content' => $content,
            'variant' => 'warning'
        ]);
    }
    
    /**
     * Create Python command interface card
     */
    private function createPythonCommandCard() {
        $commands = [
            ['label' => 'Update Portfolios', 'cmd' => 'python automation.py update_portfolios'],
            ['label' => 'Generate Reports', 'cmd' => 'python analytics.py generate_reports'],
            ['label' => 'Sync Data', 'cmd' => 'python sync_data.py'],
            ['label' => 'Backup Database', 'cmd' => 'python backup.py create_backup']
        ];
        
        $commandButtons = '';
        foreach ($commands as $command) {
            $commandButtons .= '
                <button class="btn btn-outline-secondary btn-sm me-2 mb-2" 
                        onclick="runPythonCommand(\'' . htmlspecialchars($command['cmd']) . '\')">
                    ' . htmlspecialchars($command['label']) . '
                </button>';
        }
        
        return UiFactory::createCard([
            'title' => '🐍 Python Commands',
            'content' => '
                <div class="mb-3">
                    <h6>Quick Commands:</h6>
                    ' . $commandButtons . '
                </div>
                <div class="mb-3">
                    <label for="customCommand" class="form-label">Custom Command:</label>
                    <div class="input-group">
                        <input type="text" class="form-control form-control-sm" id="customCommand" 
                               placeholder="Enter Python command...">
                        <button class="btn btn-primary btn-sm" onclick="runCustomCommand()">Run</button>
                    </div>
                </div>
                <div id="commandOutput" class="mt-3" style="display: none;">
                    <h6>Output:</h6>
                    <pre class="bg-light p-2 border rounded"></pre>
                </div>
            ',
            'variant' => 'info'
        ]);
    }
    
    /**
     * Create quick actions card
     */
    private function createQuickActionsCard() {
        return UiFactory::createCard([
            'title' => '⚡ Quick Actions',
            'content' => '
                <div class="row">
                    <div class="col-md-6">
                        <h6>📈 Trading</h6>
                        <div class="d-grid gap-2 mb-3">
                            <a href="trades.php" class="btn btn-outline-primary btn-sm">View All Trades</a>
                            <a href="trades.php?action=add" class="btn btn-outline-success btn-sm">Add New Trade</a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>🔧 Management</h6>
                        <div class="d-grid gap-2 mb-3">
                            <a href="analytics.php" class="btn btn-outline-info btn-sm">View Analytics</a>
                            <a href="automation.php" class="btn btn-outline-warning btn-sm">Automation Settings</a>
                        </div>
                    </div>
                </div>
            ',
            'variant' => 'light'
        ]);
    }
}

/**
 * Initialize and render the portfolio page
 */
try {
    // Create content service
    $contentService = new PortfolioContentService(true, $isAdmin);
    
    // Create menu service
    $menuService = new MenuService();
    $menuItems = $menuService->getMenuItems(true, $isAdmin);
    
    // Generate page components
    $components = $contentService->createPortfolioComponents();
    
    // Create navigation
    $navigation = UiFactory::createNavigation([
        'brand' => 'Portfolio Dashboard',
        'items' => $menuItems
    ]);
    
    // Create page layout
    $pageContent = '';
    foreach ($components as $component) {
        $pageContent .= $component . "\n";
    }
    
    // Render complete page
    echo UiFactory::createPage([
        'title' => 'Portfolio Management',
        'navigation' => $navigation,
        'content' => $pageContent,
        'scripts' => '
        <script>
        // Portfolio filtering and sorting functionality
        document.addEventListener("DOMContentLoaded", function() {
            console.log("Portfolio page loaded - initializing filters");
            
            // Filter functionality
            const filterRadios = document.querySelectorAll("input[name=\'microCapFilter\']");
            const sortSelect = document.getElementById("microCapSort");
            const table = document.getElementById("microCapTable");
            
            if (filterRadios.length > 0) {
                filterRadios.forEach(radio => {
                    radio.addEventListener("change", applyFilters);
                });
                console.log("Filter radios attached:", filterRadios.length);
            }
            
            if (sortSelect) {
                sortSelect.addEventListener("change", applySorting);
                console.log("Sort select attached");
            }
            
            function applyFilters() {
                const selectedFilter = document.querySelector("input[name=\'microCapFilter\']:checked").value;
                console.log("Applying filter:", selectedFilter);
                
                if (!table) {
                    console.log("Table not found");
                    return;
                }
                
                const rows = table.querySelectorAll("tbody tr");
                console.log("Found rows:", rows.length);
                
                rows.forEach(row => {
                    let show = true;
                    
                    if (selectedFilter === "recent") {
                        // Show positions updated in last 7 days
                        const dateCell = row.cells[7]; // Last Updated column
                        if (dateCell) {
                            const dateText = dateCell.textContent;
                            const rowDate = new Date(dateText);
                            const weekAgo = new Date();
                            weekAgo.setDate(weekAgo.getDate() - 7);
                            show = rowDate > weekAgo;
                        }
                    } else if (selectedFilter === "profitable") {
                        // Show positions with positive P&L
                        const plCell = row.cells[5]; // P&L column
                        if (plCell) {
                            const plText = plCell.textContent;
                            const plValue = parseFloat(plText.replace(/[^-\d.]/g, ""));
                            show = plValue > 0;
                        }
                    } else if (selectedFilter === "large") {
                        // Show positions worth more than $1000
                        const valueCell = row.cells[4]; // Current Value column
                        if (valueCell) {
                            const valueText = valueCell.textContent;
                            const value = parseFloat(valueText.replace(/[^-\d.]/g, ""));
                            show = value > 1000;
                        }
                    }
                    
                    row.style.display = show ? "" : "none";
                });
                
                console.log("Filter applied:", selectedFilter);
            }
            
            function applySorting() {
                const sortBy = sortSelect.value;
                console.log("Sorting by:", sortBy);
                sortTable(table, sortBy);
            }
            
            function sortTable(table, sortBy) {
                if (!table) return;
                
                const tbody = table.querySelector("tbody");
                if (!tbody) return;
                
                const rows = Array.from(tbody.querySelectorAll("tr"));
                
                rows.sort((a, b) => {
                    let aVal, bVal;
                    
                    switch(sortBy) {
                        case "symbol":
                            aVal = a.cells[0].textContent.trim();
                            bVal = b.cells[0].textContent.trim();
                            return aVal.localeCompare(bVal);
                            
                        case "current_value":
                            aVal = parseFloat(a.cells[4].textContent.replace(/[^-\d.]/g, ""));
                            bVal = parseFloat(b.cells[4].textContent.replace(/[^-\d.]/g, ""));
                            return bVal - aVal; // Descending
                            
                        case "profit_loss":
                            aVal = parseFloat(a.cells[5].textContent.replace(/[^-\d.]/g, ""));
                            bVal = parseFloat(b.cells[5].textContent.replace(/[^-\d.]/g, ""));
                            return bVal - aVal; // Descending
                            
                        case "percentage_gain_loss":
                            aVal = parseFloat(a.cells[6].textContent.replace(/[^-\d.]/g, ""));
                            bVal = parseFloat(b.cells[6].textContent.replace(/[^-\d.]/g, ""));
                            return bVal - aVal; // Descending
                            
                        case "last_updated":
                            aVal = new Date(a.cells[7].textContent);
                            bVal = new Date(b.cells[7].textContent);
                            return bVal - aVal; // Most recent first
                            
                        default:
                            return 0;
                    }
                });
                
                // Re-append sorted rows
                rows.forEach(row => tbody.appendChild(row));
                console.log("Table sorted by:", sortBy);
            }
        });
        
        // Python command execution
        function runPythonCommand(command) {
            console.log("Running command:", command);
            
            const outputDiv = document.getElementById("commandOutput");
            const outputPre = outputDiv.querySelector("pre");
            
            outputPre.textContent = "Running: " + command + "\n\nPlease wait...";
            outputDiv.style.display = "block";
            
            // Simulate command execution (replace with actual AJAX call)
            setTimeout(() => {
                outputPre.textContent = "Command completed: " + command + "\n\nOutput would appear here in a real implementation.";
            }, 1000);
        }
        
        function runCustomCommand() {
            const input = document.getElementById("customCommand");
            const command = input.value.trim();
            
            if (command) {
                runPythonCommand(command);
                input.value = "";
            }
        }
        </script>
        '
    ]);
    
} catch (Exception $e) {
    // Error page
    echo UiFactory::createPage([
        'title' => 'Portfolio Error',
        'content' => UiFactory::createCard([
            'title' => '❌ Error Loading Portfolio',
            'content' => '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>',
            'variant' => 'danger'
        ])
    ]);
}
?>
