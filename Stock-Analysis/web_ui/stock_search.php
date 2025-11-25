<?php
/**
 * Stock Search Interface
 * 
 * Search and browse stocks for analysis
 */

require_once __DIR__ . '/UserAuthDAO.php';
require_once __DIR__ . '/StockDAO.php';
require_once __DIR__ . '/StockPriceService.php';

// Check authentication using UserAuthDAO
$userAuth = new UserAuthDAO();
if (!$userAuth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get database connection
$pdo = $userAuth->getPDO();

$stockDAO = new StockDAO($pdo);
$priceService = new StockPriceService($stockDAO);

// Handle search and AJAX requests
$searchResults = [];
$searchQuery = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        
        switch ($_POST['action']) {
            case 'search':
                $query = trim($_POST['query'] ?? '');
                if (!empty($query)) {
                    // Search in stocks table
                    try {
                        $sql = "SELECT * FROM stocks WHERE 
                                symbol LIKE ? OR 
                                name LIKE ? OR 
                                sector LIKE ? OR 
                                industry LIKE ?
                                ORDER BY symbol LIMIT 50";
                        
                        $searchTerm = '%' . $query . '%';
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
                        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        echo json_encode(['success' => true, 'data' => $results]);
                    } catch (Exception $e) {
                        echo json_encode(['success' => false, 'error' => 'Search failed']);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'Query required']);
                }
                exit;
                
            case 'add_stock':
                $symbol = strtoupper(trim($_POST['symbol'] ?? ''));
                if (!empty($symbol)) {
                    // Initialize stock tables and try to fetch basic data
                    $success = $stockDAO->initializeStock($symbol);
                    if ($success) {
                        // Try to fetch current price to validate symbol
                        $price = $priceService->fetchCurrentPrice($symbol);
                        if ($price) {
                            // Add to stocks table if not exists
                            try {
                                $sql = "INSERT IGNORE INTO stocks (symbol, name, is_active) VALUES (?, ?, TRUE)";
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute([$symbol, $symbol]); // Use symbol as name initially
                                
                                echo json_encode(['success' => true, 'message' => 'Stock added successfully']);
                            } catch (Exception $e) {
                                echo json_encode(['success' => false, 'error' => 'Failed to add stock']);
                            }
                        } else {
                            echo json_encode(['success' => false, 'error' => 'Invalid symbol or data not available']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Failed to initialize stock']);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'Symbol required']);
                }
                exit;
        }
        
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
    }
    
    // Regular form submission
    $searchQuery = trim($_POST['query'] ?? '');
    if (!empty($searchQuery)) {
        try {
            $sql = "SELECT * FROM stocks WHERE 
                    symbol LIKE ? OR 
                    name LIKE ? OR 
                    sector LIKE ? OR 
                    industry LIKE ?
                    ORDER BY symbol LIMIT 100";
            
            $searchTerm = '%' . $searchQuery . '%';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = 'Search failed: ' . $e->getMessage();
        }
    }
}

// Get popular/trending stocks
try {
    $popularStocks = $stockDAO->searchStocks(['limit' => 20]);
} catch (Exception $e) {
    $popularStocks = [];
}

// Get recently analyzed stocks
try {
    $sql = "SELECT DISTINCT s.* FROM stocks s 
            INNER JOIN stock_prices sp ON CONCAT(s.symbol, '_prices') IN (
                SELECT table_name FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            )
            ORDER BY s.updated_at DESC LIMIT 10";
    $stmt = $pdo->query($sql);
    $recentStocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentStocks = [];
}

// Use NavigationService for consistent navigation
require_once 'NavigationService.php';
$navigationService = new NavigationService();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Search - AI Stock Analysis System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>
</head>
<body>

<?php
echo $navigationService->renderNavigationHeader('Stock Search & Analysis', 'stock_search');
?>

<div class="container-fluid">
    
    <!-- Search Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-search"></i> Stock Search & Analysis</h4>
                    <p class="mb-0 text-muted">Search for stocks to analyze, view charts, and get AI-powered insights</p>
                </div>
                <div class="card-body">
                    
                    <!-- Search Form -->
                    <form method="POST" action="" id="searchForm">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" 
                                           name="query" 
                                           id="searchInput"
                                           class="form-control form-control-lg" 
                                           placeholder="Search by symbol (AAPL), company name (Apple), sector, or industry..."
                                           value="<?= htmlspecialchars($searchQuery) ?>"
                                           autocomplete="off">
                                    <button class="btn btn-primary btn-lg" type="submit">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                                
                                <!-- Search Suggestions -->
                                <div id="searchSuggestions" class="list-group mt-2" style="display: none; position: absolute; z-index: 1000; width: 100%;"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" 
                                           id="addSymbolInput" 
                                           class="form-control" 
                                           placeholder="Add new symbol (e.g., TSLA)"
                                           style="text-transform: uppercase;">
                                    <button type="button" class="btn btn-outline-success" onclick="addNewStock()">
                                        <i class="fas fa-plus"></i> Add Stock
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                </div>
            </div>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <!-- Search Results -->
    <?php if (!empty($searchResults)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list"></i> Search Results (<?= count($searchResults) ?> found)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Symbol</th>
                                        <th>Company Name</th>
                                        <th>Sector</th>
                                        <th>Industry</th>
                                        <th>Market Cap</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($searchResults as $stock): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($stock['symbol']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($stock['name']) ?></td>
                                            <td>
                                                <?php if ($stock['sector']): ?>
                                                    <span class="badge badge-secondary"><?= htmlspecialchars($stock['sector']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= htmlspecialchars($stock['industry'] ?? 'N/A') ?></small>
                                            </td>
                                            <td>
                                                <?php if ($stock['market_cap']): ?>
                                                    $<?= number_format($stock['market_cap'] / 1e9, 2) ?>B
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="stock_analysis.php?symbol=<?= urlencode($stock['symbol']) ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-chart-line"></i> Analyze
                                                </a>
                                                <button class="btn btn-outline-info btn-sm" 
                                                        onclick="quickView('<?= htmlspecialchars($stock['symbol']) ?>')">
                                                    <i class="fas fa-eye"></i> Quick View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Stock Categories -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-layer-group"></i> Browse by Category</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-primary btn-block" onclick="searchByCategory('Technology')">
                                <i class="fas fa-microchip"></i><br>Technology
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-success btn-block" onclick="searchByCategory('Healthcare')">
                                <i class="fas fa-heartbeat"></i><br>Healthcare
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-info btn-block" onclick="searchByCategory('Financial')">
                                <i class="fas fa-university"></i><br>Financial
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-warning btn-block" onclick="searchByCategory('Energy')">
                                <i class="fas fa-bolt"></i><br>Energy
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-secondary btn-block" onclick="searchByCategory('Industrial')">
                                <i class="fas fa-industry"></i><br>Industrial
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-danger btn-block" onclick="searchByCategory('Consumer')">
                                <i class="fas fa-shopping-cart"></i><br>Consumer
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-dark btn-block" onclick="searchByCategory('Materials')">
                                <i class="fas fa-hammer"></i><br>Materials
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-primary btn-block" onclick="searchByCategory('Utilities')">
                                <i class="fas fa-plug"></i><br>Utilities
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Popular/Trending and Recent Stocks -->
    <div class="row">
        
        <!-- Popular Stocks -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-fire"></i> Popular Stocks</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($popularStocks)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($popularStocks, 0, 10) as $stock): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($stock['symbol']) ?></strong>
                                        <small class="text-muted d-block"><?= htmlspecialchars($stock['name']) ?></small>
                                    </div>
                                    <div>
                                        <a href="stock_analysis.php?symbol=<?= urlencode($stock['symbol']) ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-info-circle"></i><br>
                            No stocks available. Add some stocks to get started.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recently Analyzed -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-history"></i> Recently Analyzed</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentStocks)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentStocks as $stock): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($stock['symbol']) ?></strong>
                                        <small class="text-muted d-block"><?= htmlspecialchars($stock['name']) ?></small>
                                    </div>
                                    <div>
                                        <a href="stock_analysis.php?symbol=<?= urlencode($stock['symbol']) ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-info-circle"></i><br>
                            No recently analyzed stocks.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    </div>
    
</div>

<!-- Quick View Modal -->
<div class="modal fade" id="quickViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick View - <span id="quickViewSymbol"></span></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="quickViewContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i><br>
                        Loading...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <a id="fullAnalysisLink" href="#" class="btn btn-primary">
                    <i class="fas fa-chart-line"></i> Full Analysis
                </a>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
let searchTimeout;

// Real-time search suggestions
document.getElementById('searchInput').addEventListener('input', function() {
    const query = this.value.trim();
    
    if (query.length >= 2) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchSuggestions(query);
        }, 300);
    } else {
        hideSuggestions();
    }
});

// Search suggestions
function searchSuggestions(query) {
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ajax=1&action=search&query=${encodeURIComponent(query)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.length > 0) {
            showSuggestions(data.data.slice(0, 8)); // Show top 8 results
        } else {
            hideSuggestions();
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        hideSuggestions();
    });
}

// Show search suggestions
function showSuggestions(suggestions) {
    const container = document.getElementById('searchSuggestions');
    container.innerHTML = '';
    
    suggestions.forEach(stock => {
        const item = document.createElement('div');
        item.className = 'list-group-item list-group-item-action';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${stock.symbol}</strong> - ${stock.name}
                    ${stock.sector ? `<span class="badge badge-secondary ml-2">${stock.sector}</span>` : ''}
                </div>
                <small class="text-muted">
                    <i class="fas fa-chart-line"></i>
                </small>
            </div>
        `;
        
        item.addEventListener('click', function() {
            window.location.href = `stock_analysis.php?symbol=${encodeURIComponent(stock.symbol)}`;
        });
        
        container.appendChild(item);
    });
    
    container.style.display = 'block';
}

// Hide suggestions
function hideSuggestions() {
    document.getElementById('searchSuggestions').style.display = 'none';
}

// Hide suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#searchInput') && !e.target.closest('#searchSuggestions')) {
        hideSuggestions();
    }
});

// Add new stock
function addNewStock() {
    const symbolInput = document.getElementById('addSymbolInput');
    const symbol = symbolInput.value.trim().toUpperCase();
    
    if (!symbol) {
        alert('Please enter a stock symbol');
        return;
    }
    
    // Show loading state
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    btn.disabled = true;
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ajax=1&action=add_stock&symbol=${encodeURIComponent(symbol)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Stock added successfully!');
            symbolInput.value = '';
            // Optionally redirect to analysis page
            window.location.href = `stock_analysis.php?symbol=${encodeURIComponent(symbol)}`;
        } else {
            alert(data.error || 'Failed to add stock');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding stock');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Search by category
function searchByCategory(category) {
    document.getElementById('searchInput').value = category;
    document.getElementById('searchForm').submit();
}

// Quick view
function quickView(symbol) {
    document.getElementById('quickViewSymbol').textContent = symbol;
    document.getElementById('fullAnalysisLink').href = `stock_analysis.php?symbol=${encodeURIComponent(symbol)}`;
    
    // Reset modal content
    document.getElementById('quickViewContent').innerHTML = `
        <div class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x"></i><br>
            Loading quick view for ${symbol}...
        </div>
    `;
    
    // Show modal
    $('#quickViewModal').modal('show');
    
    // Load quick data (this would be implemented to show basic price/chart info)
    setTimeout(() => {
        document.getElementById('quickViewContent').innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Quick view functionality will display basic price information, mini chart, and key metrics here.
                <br><br>
                Click "Full Analysis" for comprehensive stock analysis.
            </div>
        `;
    }, 1000);
}

// Allow Enter key to add stock
document.getElementById('addSymbolInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        addNewStock();
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>