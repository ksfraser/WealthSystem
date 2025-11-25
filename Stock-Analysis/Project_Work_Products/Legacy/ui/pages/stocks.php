<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../../../DatabaseConfig.php';

use Ksfraser\StockInfo\StockInfoManager;
use Ksfraser\StockInfo\StockInfo;
use Ksfraser\StockInfo\DatabaseFactory;

$currentPage = 'stocks';
$pageTitle = 'Stock Management';
$pageHeader = 'Stock Management';
$pageDescription = 'Manage stock information and basic data';

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
        case 'create':
            try {
                $data = [
                    'symbol' => strtoupper(trim($_POST['symbol'])),
                    'name' => trim($_POST['name']),
                    'sector' => trim($_POST['sector']),
                    'industry' => trim($_POST['industry']),
                    'exchange' => trim($_POST['exchange']),
                    'currency' => trim($_POST['currency'] ?? 'USD'),
                    'country' => trim($_POST['country'] ?? 'US')
                ];
                
                if ($stockModel->create($data)) {
                    $_SESSION['success'] = "Stock created successfully!";
                } else {
                    $_SESSION['error'] = "Failed to create stock";
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Error creating stock: " . $e->getMessage();
            }
            break;
            
        case 'update':
            try {
                $id = (int)$_POST['id'];
                $data = [
                    'symbol' => strtoupper(trim($_POST['symbol'])),
                    'name' => trim($_POST['name']),
                    'sector' => trim($_POST['sector']),
                    'industry' => trim($_POST['industry']),
                    'exchange' => trim($_POST['exchange']),
                    'currency' => trim($_POST['currency'] ?? 'USD'),
                    'country' => trim($_POST['country'] ?? 'US')
                ];
                
                if ($stockModel->update($id, $data)) {
                    $_SESSION['success'] = "Stock updated successfully!";
                } else {
                    $_SESSION['error'] = "Failed to update stock";
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Error updating stock: " . $e->getMessage();
            }
            break;
            
        case 'delete':
            try {
                $id = (int)$_POST['id'];
                if ($stockModel->delete($id)) {
                    $_SESSION['success'] = "Stock deleted successfully!";
                } else {
                    $_SESSION['error'] = "Failed to delete stock";
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Error deleting stock: " . $e->getMessage();
            }
            break;
    }
    
    header('Location: stocks.php');
    exit;
}

// Get stock for editing
$editStock = null;
if (isset($_GET['edit'])) {
    $editStock = $stockModel->find((int)$_GET['edit']);
}

// Get all stocks with pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;

try {
    $stocks = $stockModel->getAll($limit, $offset);
    $totalStocks = $stockModel->count();
    $totalPages = ceil($totalStocks / $limit);
} catch (Exception $e) {
    $stocks = [];
    $totalStocks = 0;
    $totalPages = 0;
}

include '../components/header.php';
?>

<!-- Add/Edit Stock Modal -->
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockModalTitle">Add New Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="stockForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="stockId" value="">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="symbol" class="form-label">Symbol *</label>
                                <input type="text" class="form-control" id="symbol" name="symbol" required maxlength="12">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="exchange" class="form-label">Exchange</label>
                                <select class="form-select" id="exchange" name="exchange">
                                    <option value="">Select Exchange</option>
                                    <option value="NYSE">NYSE</option>
                                    <option value="NASDAQ">NASDAQ</option>
                                    <option value="TSX">TSX</option>
                                    <option value="LSE">LSE</option>
                                    <option value="OTHER">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Company Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required maxlength="255">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sector" class="form-label">Sector</label>
                                <input type="text" class="form-control" id="sector" name="sector" maxlength="100">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="industry" class="form-label">Industry</label>
                                <input type="text" class="form-control" id="industry" name="industry" maxlength="100">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-select" id="currency" name="currency">
                                    <option value="USD">USD</option>
                                    <option value="CAD">CAD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GBP">GBP</option>
                                    <option value="JPY">JPY</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="country" class="form-label">Country</label>
                                <select class="form-select" id="country" name="country">
                                    <option value="US">United States</option>
                                    <option value="CA">Canada</option>
                                    <option value="GB">United Kingdom</option>
                                    <option value="DE">Germany</option>
                                    <option value="JP">Japan</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Stocks List -->
<div class="row mb-4">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>Stocks (<?= number_format($totalStocks) ?>)
                </h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#stockModal">
                    <i class="fas fa-plus me-2"></i>Add Stock
                </button>
            </div>
            <div class="card-body">
                <?php if (!empty($stocks)): ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="stocksTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Symbol</th>
                                <th>Name</th>
                                <th>Sector</th>
                                <th>Industry</th>
                                <th>Exchange</th>
                                <th>Currency</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stocks as $stock): ?>
                            <tr>
                                <td><?= $stock['idstockinfo'] ?></td>
                                <td><strong><?= htmlspecialchars($stock['symbol']) ?></strong></td>
                                <td><?= htmlspecialchars($stock['name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($stock['sector'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($stock['industry'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($stock['exchange'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($stock['currency'] ?? 'USD') ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editStock(<?= $stock['idstockinfo'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="viewStock(<?= $stock['idstockinfo'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="analyzeStock(<?= $stock['idstockinfo'] ?>)">
                                            <i class="fas fa-chart-line"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteStock(<?= $stock['idstockinfo'] ?>, '<?= htmlspecialchars($stock['symbol']) ?>')">
                                            <i class="fas fa-trash"></i>
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
                <nav aria-label="Stocks pagination">
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
                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No stocks found. Add some stocks to get started.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#stockModal">
                        <i class="fas fa-plus me-2"></i>Add First Stock
                    </button>
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
        $("#stocksTable").DataTable({
            pageLength: 25,
            responsive: true,
            order: [[0, "desc"]],
            columnDefs: [
                { targets: -1, orderable: false } // Actions column
            ]
        });
    });
    
    function editStock(id) {
        $.get("/Legacy/ui/api/get_stock.php", {id: id}, function(data) {
            if (data.status === "success") {
                const stock = data.stock;
                $("#stockModalTitle").text("Edit Stock");
                $("#formAction").val("update");
                $("#stockId").val(stock.idstockinfo);
                $("#symbol").val(stock.symbol);
                $("#name").val(stock.name);
                $("#sector").val(stock.sector);
                $("#industry").val(stock.industry);
                $("#exchange").val(stock.exchange);
                $("#currency").val(stock.currency);
                $("#country").val(stock.country);
                $("#saveBtn").text("Update Stock");
                $("#stockModal").modal("show");
            }
        });
    }
    
    function viewStock(id) {
        window.location.href = "/Legacy/ui/pages/stock_detail.php?id=" + id;
    }
    
    function analyzeStock(id) {
        $.post("/Legacy/ui/api/start_analysis.php", {type: "all", stockId: id}, function(data) {
            if (data.status === "success") {
                showAlert("Stock analysis started", "success");
                updateProgress(data.jobId);
            }
        });
    }
    
    function deleteStock(id, symbol) {
        if (confirm(`Are you sure you want to delete stock ${symbol}? This action cannot be undone.`)) {
            $.post("stocks.php", {action: "delete", id: id}, function() {
                location.reload();
            });
        }
    }
    
    // Reset form when modal is hidden
    $("#stockModal").on("hidden.bs.modal", function() {
        $("#stockForm")[0].reset();
        $("#stockModalTitle").text("Add New Stock");
        $("#formAction").val("create");
        $("#stockId").val("");
        $("#saveBtn").text("Save Stock");
    });
</script>
';

include '../components/footer.php';
?>
