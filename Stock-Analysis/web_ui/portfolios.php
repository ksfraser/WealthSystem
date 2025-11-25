<?php
/**
 * Portfolio Management Controller - Clean UiRenderer Architecture
 * Uses proper MVC pattern with component-based UI rendering
 */

// Include authentication and UI system
require_once 'auth_check.php';
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
        
        // Add filter controls for micro cap
        $content .= '<div style="margin: 15px 0; padding: 10px; background-color: #f8f9fa; border-radius: 5px;">';
        $content .= '<h5>📊 Portfolio Filters & Options</h5>';
        $content .= '<div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">';
        $content .= '<label><input type="checkbox" id="filter-recent" checked> Show Recent Only</label>';
        $content .= '<label><input type="checkbox" id="filter-profitable"> Profitable Only</label>';
        $content .= '<label><input type="checkbox" id="filter-large-positions"> Large Positions (>$1000)</label>';
        $content .= '<select id="sort-by" style="padding: 5px; margin-left: 10px;">';
        $content .= '<option value="none">No Sorting</option>';
        $content .= '<option value="date">Sort by Date (Recent First)</option>';
        $content .= '<option value="value">Sort by Value (Highest First)</option>';
        $content .= '<option value="symbol">Sort by Symbol (A-Z)</option>';
        $content .= '</select>';
        $content .= '<button onclick="applyFilters()" class="btn btn-primary btn-sm" style="margin-left: 10px;">Apply Filters</button>';
        $content .= '<button onclick="resetFilters()" class="btn btn-secondary btn-sm" style="margin-left: 5px;">Reset</button>';
        $content .= '</div>';
        $content .= '<div id="filter-status" style="margin-top: 5px; font-size: 0.9em; color: #666;"></div>';
        $content .= '</div>';
        
        if ($portfolioRows && count($portfolioRows)) {
            $content .= '<div id="portfolio-table-container">';
            $tableComponent = UiFactory::createTable($portfolioRows, [], [
                'striped' => true,
                'hover' => true,
                'responsive' => true,
                'id' => 'micro-cap-table'
            ]);
            $content .= $tableComponent->toHtml();
            $content .= '</div>';
        } else {
            $content .= '<em>No recent micro-cap portfolio data found.</em>';
            
            // Try database fallback
            $content .= '<div style="margin-top: 15px; padding: 10px; background-color: #fff3cd; border-left: 4px solid #ffc107;">';
            $content .= '<strong>📋 CSV Data Status:</strong> No CSV files found<br>';
            $content .= '<strong>🔄 Fallback Option:</strong> Attempting database connection...<br>';
            $content .= '<button onclick="tryDatabaseFallback(\'micro-cap\')" class="btn btn-warning btn-sm">Try Database</button>';
            $content .= '</div>';
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
            $tableComponent = UiFactory::createTable($portfolioRows, [], [
                'striped' => true,
                'hover' => true,
                'responsive' => true
            ]);
            $content .= $tableComponent->toHtml();
        } else {
            $content .= '<div style="color:#856404;background-color:#fff3cd;border:1px solid #ffeaa7;padding:10px;border-radius:5px;margin:10px 0;">';
            $content .= '<strong>📁 CSV Files Not Found</strong><br>';
            $content .= 'Checked locations:<br>';
            foreach ($csvPaths as $path) {
                $exists = file_exists($path) ? '✅' : '❌';
                $content .= "• {$exists} " . htmlspecialchars($path) . "<br>";
            }
            $content .= '<br><strong>🔄 Available Options:</strong><br>';
            $content .= '• <button onclick="tryDatabaseFallback(\'blue-chip\')" class="btn btn-info btn-sm">Try Database Connection</button><br>';
            $content .= '• <a href="debug_500.php" class="btn btn-warning btn-sm">Run System Diagnostics</a><br>';
            $content .= '• <em>System will attempt to connect to stock_market_2 database</em>';
            $content .= '</div>';
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
            $tableComponent = UiFactory::createTable($portfolioRows, [], [
                'striped' => true,
                'hover' => true,
                'responsive' => true
            ]);
            $content .= $tableComponent->toHtml();
        } else {
            $content .= '<div style="color:#856404;background-color:#fff3cd;border:1px solid #ffeaa7;padding:10px;border-radius:5px;margin:10px 0;">';
            $content .= '<strong>📁 CSV Files Not Found</strong><br>';
            $content .= 'Checked locations:<br>';
            foreach ($csvPaths as $path) {
                $exists = file_exists($path) ? '✅' : '❌';
                $content .= "• {$exists} " . htmlspecialchars($path) . "<br>";
            }
            $content .= '<br><strong>🔄 Available Options:</strong><br>';
            $content .= '• <button onclick="tryDatabaseFallback(\'small-cap\')" class="btn btn-info btn-sm">Try Database Connection</button><br>';
            $content .= '• <a href="debug_500.php" class="btn btn-warning btn-sm">Run System Diagnostics</a><br>';
            $content .= '• <em>System will attempt to connect to stock_market_2 database</em>';
            $content .= '</div>';
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
        
        return UiFactory::createCard('Quick Actions', '', 'default', '', $actions);
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
        
        $navigation = UiFactory::createNavigation(
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
        
        function applyFilters() {
            const table = document.getElementById("micro-cap-table");
            if (!table) {
                console.log("Table not found: micro-cap-table");
                return;
            }
            
            const showRecent = document.getElementById("filter-recent").checked;
            const showProfitable = document.getElementById("filter-profitable").checked;
            const showLarge = document.getElementById("filter-large-positions").checked;
            const sortBy = document.getElementById("sort-by").value;
            
            const tbody = table.querySelector("tbody");
            if (!tbody) {
                console.log("Table body not found");
                return;
            }
            
            const rows = Array.from(tbody.querySelectorAll("tr"));
            console.log("Found " + rows.length + " rows to filter");
            
            // Filter rows
            rows.forEach(row => {
                let show = true;
                const cells = Array.from(row.querySelectorAll("td"));
                
                // Skip TOTAL rows from filtering
                const isTotal = cells.some(cell => cell.textContent.toLowerCase().includes("total"));
                if (isTotal) {
                    row.style.display = ""; // Always show TOTAL rows
                    return;
                }
                
                if (showProfitable && cells.length > 0) {
                    // Look for positive PnL values specifically
                    let isProfitable = false;
                    cells.forEach((cell, index) => {
                        const headerCells = table.querySelectorAll("thead th");
                        if (headerCells[index]) {
                            const headerText = headerCells[index].textContent.toLowerCase();
                            const cellText = cell.textContent.trim();
                            
                            // Check PnL column specifically - this is the main profit indicator
                            if (headerText.includes("pnl") || headerText.includes("profit") || headerText.includes("p&l")) {
                                const value = parseFloat(cellText.replace(/[^0-9.-]/g, ""));
                                if (!isNaN(value) && value > 0) {
                                    isProfitable = true;
                                }
                            }
                        }
                    });
                    if (!isProfitable) show = false;
                }
                
                if (showLarge && cells.length > 0) {
                    // Look for large monetary values in Total Value or Cost Basis columns
                    let hasLargePosition = false;
                    cells.forEach((cell, index) => {
                        const headerCells = table.querySelectorAll("thead th");
                        if (headerCells[index]) {
                            const headerText = headerCells[index].textContent.toLowerCase();
                            const cellText = cell.textContent.trim();
                            
                            if ((headerText.includes("value") || headerText.includes("cost") || headerText.includes("basis")) && 
                                cellText && cellText !== "") {
                                const value = parseFloat(cellText.replace(/[^0-9.-]/g, ""));
                                if (!isNaN(value) && Math.abs(value) > 1000) {
                                    hasLargePosition = true;
                                }
                            }
                        }
                    });
                    if (!hasLargePosition) show = false;
                }
                
                row.style.display = show ? "" : "none";
            });
            
            // Apply sorting after filtering
            const sortBy = document.getElementById("sort-by").value;
            if (sortBy && sortBy !== "none") {
                sortTable(table, sortBy);
            }
            
            // Show filter results
            const visibleRows = rows.filter(row => row.style.display !== "none");
            console.log("Showing " + visibleRows.length + " of " + rows.length + " rows");
            
            // Update button text to show results
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = "Showing " + visibleRows.length + " rows";
            
            // Update status
            const status = document.getElementById("filter-status");
            if (status) {
                status.textContent = "Showing " + visibleRows.length + " of " + rows.length + " entries";
            }
            
            setTimeout(() => {
                btn.textContent = originalText;
            }, 2000);
        }
        
        function resetFilters() {
            // Reset checkboxes
            document.getElementById("filter-recent").checked = true;
            document.getElementById("filter-profitable").checked = false;
            document.getElementById("filter-large-positions").checked = false;
            document.getElementById("sort-by").value = "none";
            
            // Show all rows
            const table = document.getElementById("micro-cap-table");
            if (table) {
                const rows = table.querySelectorAll("tbody tr");
                rows.forEach(row => {
                    row.style.display = "";
                });
                
                // Update status
                const status = document.getElementById("filter-status");
                if (status) {
                    status.textContent = "Showing all " + rows.length + " entries";
                }
            }
        }
        
        function sortTable(table, sortBy) {
            const tbody = table.querySelector("tbody");
            if (!tbody) return;
            
            const rows = Array.from(tbody.querySelectorAll("tr"));
            const headerCells = table.querySelectorAll("thead th");
            
            // Find the column index for sorting
            let sortColumnIndex = -1;
            headerCells.forEach((header, index) => {
                const headerText = header.textContent.toLowerCase();
                if (sortBy === "date" && headerText.includes("date")) {
                    sortColumnIndex = index;
                } else if (sortBy === "symbol" && (headerText.includes("ticker") || headerText.includes("symbol"))) {
                    sortColumnIndex = index;
                } else if (sortBy === "value" && (headerText.includes("value") || headerText.includes("price"))) {
                    sortColumnIndex = index;
                }
            });
            
            if (sortColumnIndex === -1) return; // Column not found
            
            // Sort rows (exclude TOTAL rows from sorting)
            const dataRows = rows.filter(row => {
                const cells = row.querySelectorAll("td");
                return !Array.from(cells).some(cell => cell.textContent.toLowerCase().includes("total"));
            });
            
            const totalRows = rows.filter(row => {
                const cells = row.querySelectorAll("td");
                return Array.from(cells).some(cell => cell.textContent.toLowerCase().includes("total"));
            });
            
            dataRows.sort((rowA, rowB) => {
                const cellA = rowA.querySelectorAll("td")[sortColumnIndex];
                const cellB = rowB.querySelectorAll("td")[sortColumnIndex];
                
                if (!cellA || !cellB) return 0;
                
                const valueA = cellA.textContent.trim();
                const valueB = cellB.textContent.trim();
                
                // Handle different sort types
                if (sortBy === "date") {
                    const dateA = new Date(valueA);
                    const dateB = new Date(valueB);
                    return dateB - dateA; // Most recent first
                } else if (sortBy === "value") {
                    const numA = parseFloat(valueA.replace(/[^0-9.-]/g, "")) || 0;
                    const numB = parseFloat(valueB.replace(/[^0-9.-]/g, "")) || 0;
                    return numB - numA; // Highest value first
                } else if (sortBy === "symbol") {
                    return valueA.localeCompare(valueB); // Alphabetical
                }
                
                return 0;
            });
            
            // Clear tbody and re-append sorted rows
            tbody.innerHTML = "";
            dataRows.forEach(row => tbody.appendChild(row));
            totalRows.forEach(row => tbody.appendChild(row)); // TOTAL rows always at the end
        }
        
        function tryDatabaseFallback(portfolioType) {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = "🔄 Connecting...";
            
            fetch("portfolio_rest_api.php", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({
                    action: "get_portfolio",
                    type: portfolioType
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    btn.textContent = "✅ Found " + data.data.length + " records";
                    btn.classList.remove("btn-info");
                    btn.classList.add("btn-success");
                    
                    // Reload page to show database data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    btn.textContent = "❌ No database data";
                    btn.classList.remove("btn-info");
                    btn.classList.add("btn-warning");
                }
            })
            .catch(error => {
                btn.textContent = "❌ Connection failed";
                btn.classList.remove("btn-info");
                btn.classList.add("btn-danger");
            })
            .finally(() => {
                setTimeout(() => {
                    btn.disabled = false;
                }, 2000);
            });
        }
        ';
        
        $pageRenderer = UiFactory::createPage(
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
    $errorNavigation = UiFactory::createNavigation(
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
    
    $pageRenderer = UiFactory::createPage(
        'Error - Portfolio Management',
        $errorNavigation,
        [$errorCard]
    );
    
    echo $pageRenderer->render();
}
?>
