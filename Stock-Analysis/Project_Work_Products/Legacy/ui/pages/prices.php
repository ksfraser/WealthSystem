<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../../../DatabaseConfig.php';

use Ksfraser\StockInfo\StockInfoManager;
use Ksfraser\StockInfo\StockInfo;
use Ksfraser\StockInfo\DatabaseFactory;

$currentPage = 'prices';
$pageTitle = 'Price Management';
$pageHeader = 'Stock Price Management';
$pageDescription = 'Manage historical and current stock prices';

// Initialize database and models using centralized config
try {
    $legacyConfig = DatabaseConfig::getLegacyConfig();
    
    $stockManager = new StockInfoManager([
        'host' => $legacyConfig['host'],
        'dbname' => $legacyConfig['dbname'],
        'username' => $legacyConfig['username'],
        'password' => $legacyConfig['password']
    ]);
    
    $database = DatabaseFactory::getInstance($legacyConfig);
    $stockModel = new StockInfo($database->getConnection());
} catch (Exception $e) {
    $error = "Database connection failed: " . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_price':
            try {
                $stockId = (int)$_POST['stock_id'];
                $currentPrice = (float)$_POST['current_price'];
                $dailyVolume = isset($_POST['daily_volume']) ? (int)$_POST['daily_volume'] : null;
                $dailyChange = isset($_POST['daily_change']) ? (float)$_POST['daily_change'] : null;
                
                $stock = $stockModel->find($stockId);
                if ($stock) {
                    if ($stockModel->updatePrice($stock['stocksymbol'], $currentPrice, $dailyVolume, $dailyChange)) {
                        $_SESSION['success'] = "Price updated successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to update price";
                    }
                } else {
                    $_SESSION['error'] = "Stock not found";
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Error updating price: " . $e->getMessage();
            }
            break;
            
        case 'bulk_update':
            // Handle bulk price updates from API/CSV
            try {
                $uploaded = $_FILES['price_file'] ?? null;
                if ($uploaded && $uploaded['error'] === UPLOAD_ERR_OK) {
                    $handle = fopen($uploaded['tmp_name'], 'r');
                    $updated = 0;
                    $errors = [];
                    
                    // Skip header row
                    fgetcsv($handle);
                    
                    while (($data = fgetcsv($handle)) !== FALSE) {
                        try {
                            $symbol = trim($data[0]);
                            $price = (float)$data[1];
                            $volume = isset($data[2]) ? (int)$data[2] : null;
                            $change = isset($data[3]) ? (float)$data[3] : null;
                            
                            if ($stockModel->updatePrice($symbol, $price, $volume, $change)) {
                                $updated++;
                            }
                        } catch (Exception $e) {
                            $errors[] = "Row error: " . $e->getMessage();
                        }
                    }
                    
                    fclose($handle);
                    $_SESSION['success'] = "Updated {$updated} stock prices" . (count($errors) > 0 ? " with " . count($errors) . " errors" : "");
                    if (count($errors) > 0) {
                        $_SESSION['errors'] = $errors;
                    }
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Error processing file: " . $e->getMessage();
            }
            break;
    }
    
    header('Location: prices.php');
    exit;
}

// Get stocks with current prices
$page = (int)($_GET['page'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;

try {
    $stocks = $stockModel->getAll($limit, $offset);
    $totalStocks = $stockModel->count();
    $totalPages = ceil($totalStocks / $limit);
    
    // Get price statistics
    $priceStats = $stockModel->getStatistics();
} catch (Exception $e) {
    $stocks = [];
    $totalStocks = 0;
    $totalPages = 0;
    $priceStats = null;
}

include '../components/header.php';
?>

<!-- Price Update Modal -->
<div class="modal fade" id="priceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Stock Price</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_price">
                    <input type="hidden" name="stock_id" id="priceStockId">
                    
                    <div class="mb-3">
                        <label class="form-label">Stock Symbol</label>
                        <input type="text" class="form-control" id="priceSymbol" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="current_price" class="form-label">Current Price *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="current_price" id="current_price" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="daily_volume" class="form-label">Daily Volume</label>
                        <input type="number" class="form-control" name="daily_volume" id="daily_volume">
                    </div>
                    
                    <div class="mb-3">
                        <label for="daily_change" class="form-label">Daily Change</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="daily_change" id="daily_change" step="0.01">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Price</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Update Modal -->
<div class="modal fade" id="bulkUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Price Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="bulk_update">
                    
                    <div class="alert alert-info">
                        <strong>CSV Format:</strong> Symbol, Price, Volume (optional), Change (optional)<br>
                        Example: AAPL,150.25,1000000,2.50
                    </div>
                    
                    <div class="mb-3">
                        <label for="price_file" class="form-label">Upload CSV File</label>
                        <input type="file" class="form-control" name="price_file" id="price_file" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload & Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Price Statistics -->
<?php if ($priceStats): ?>
<div class="row mb-4">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>Price Statistics
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-primary"><?= number_format($priceStats->total_stocks) ?></h4>
                            <small class="text-muted">Total Stocks</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-success"><?= number_format($priceStats->active_stocks) ?></h4>
                            <small class="text-muted">Active Stocks</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-info">$<?= number_format($priceStats->avg_price ?? 0, 2) ?></h4>
                            <small class="text-muted">Average Price</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-warning"><?= number_format($priceStats->avg_pe_ratio ?? 0, 2) ?></h4>
                            <small class="text-muted">Average P/E</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Prices List -->
<div class="row mb-4">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-dollar-sign me-2"></i>Stock Prices
                </h5>
                <div>
                    <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#bulkUpdateModal">
                        <i class="fas fa-upload me-2"></i>Bulk Update
                    </button>
                    <button type="button" class="btn btn-primary" onclick="refreshAllPrices()">
                        <i class="fas fa-sync me-2"></i>Refresh Prices
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($stocks)): ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="pricesTable">
                        <thead>
                            <tr>
                                <th>Symbol</th>
                                <th>Company</th>
                                <th>Current Price</th>
                                <th>Daily Change</th>
                                <th>Volume</th>
                                <th>Market Cap</th>
                                <th>P/E Ratio</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stocks as $stock): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($stock['stocksymbol']) ?></strong></td>
                                <td><?= htmlspecialchars($stock['corporatename'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if ($stock['currentprice']): ?>
                                        <span class="fw-bold">$<?= number_format($stock['currentprice'], 2) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($stock['dailychange']): ?>
                                        <span class="badge bg-<?= $stock['dailychange'] >= 0 ? 'success' : 'danger' ?>">
                                            <?= ($stock['dailychange'] >= 0 ? '+' : '') . number_format($stock['dailychange'], 2) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $stock['dailyvolume'] ? number_format($stock['dailyvolume']) : '-' ?></td>
                                <td>
                                    <?php if ($stock['marketcap']): ?>
                                        $<?= number_format($stock['marketcap']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $stock['peratio'] ? number_format($stock['peratio'], 2) : '-' ?></td>
                                <td>
                                    <?php if ($stock['asofdate']): ?>
                                        <small><?= date('M j, Y H:i', strtotime($stock['asofdate'])) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="updatePrice(<?= $stock['idstockinfo'] ?>, '<?= htmlspecialchars($stock['stocksymbol']) ?>', <?= $stock['currentprice'] ?? 0 ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="refreshPrice('<?= htmlspecialchars($stock['stocksymbol']) ?>')">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="viewChart('<?= htmlspecialchars($stock['stocksymbol']) ?>')">
                                            <i class="fas fa-chart-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Prices pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-dollar-sign fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No stock prices found.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$additionalScripts = '
<script>
    // Initialize DataTable
    $(document).ready(function() {
        $("#pricesTable").DataTable({
            pageLength: 25,
            responsive: true,
            order: [[0, "asc"]],
            columnDefs: [
                { targets: -1, orderable: false }, // Actions column
                { targets: [2, 3, 4, 5, 6], className: "text-end" } // Numeric columns
            ]
        });
    });
    
    function updatePrice(stockId, symbol, currentPrice) {
        $("#priceStockId").val(stockId);
        $("#priceSymbol").val(symbol);
        $("#current_price").val(currentPrice);
        $("#daily_volume").val("");
        $("#daily_change").val("");
        $("#priceModal").modal("show");
    }
    
    function refreshPrice(symbol) {
        showAlert("Refreshing price for " + symbol + "...", "info");
        
        // TODO: Implement API call to refresh price from external source
        $.post("/Legacy/ui/api/refresh_price.php", {symbol: symbol}, function(data) {
            if (data.status === "success") {
                showAlert("Price refreshed successfully", "success");
                location.reload();
            } else {
                showAlert("Failed to refresh price: " + data.message, "error");
            }
        }).fail(function() {
            showAlert("Error refreshing price", "error");
        });
    }
    
    function refreshAllPrices() {
        if (confirm("This will refresh all stock prices. This may take a while. Continue?")) {
            showAlert("Refreshing all prices...", "info");
            
            $.post("/Legacy/ui/api/refresh_all_prices.php", function(data) {
                if (data.status === "success") {
                    showAlert("All prices refreshed successfully", "success");
                    location.reload();
                } else {
                    showAlert("Failed to refresh prices: " + data.message, "error");
                }
            }).fail(function() {
                showAlert("Error refreshing prices", "error");
            });
        }
    }
    
    function viewChart(symbol) {
        window.open("https://finance.yahoo.com/quote/" + symbol, "_blank");
    }
</script>
';

include '../components/footer.php';
?>
