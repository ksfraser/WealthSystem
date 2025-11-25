<?php
/**
 * Add New Stock to Registry
 * Simple form to add stocks to the stocks table
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../UserAuthDAO.php';

// Check authentication and admin status
$auth = new UserAuthDAO();
$auth->requireAdmin();

// Get database connection
$db = $auth->getPdo();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $symbol = trim(strtoupper($_POST['symbol'] ?? ''));
    $name = trim($_POST['name'] ?? '');
    $sector = trim($_POST['sector'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $exchange = trim($_POST['exchange'] ?? 'NASDAQ');
    
    if (!empty($symbol) && !empty($name)) {
        try {
            // Check if symbol already exists
            $checkStmt = $db->prepare("SELECT symbol FROM stocks WHERE symbol = ?");
            $checkStmt->execute([$symbol]);
            
            if ($checkStmt->fetch()) {
                $error = "Stock symbol '{$symbol}' already exists in the registry.";
            } else {
                // Insert new stock
                $insertStmt = $db->prepare("
                    INSERT INTO stocks (symbol, name, sector, industry, exchange, is_active) 
                    VALUES (?, ?, ?, ?, ?, 1)
                ");
                
                if ($insertStmt->execute([$symbol, $name, $sector, $industry, $exchange])) {
                    $message = "Stock '{$symbol}' - {$name} has been successfully added to the registry.";
                    
                    // Clear form
                    $_POST = [];
                } else {
                    $error = "Failed to add stock to registry.";
                }
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Symbol and Company Name are required fields.";
    }
}

// Get recent stocks for reference
try {
    $recentStocks = $db->query("SELECT symbol, name, sector, industry FROM stocks ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentStocks = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Stock to Registry - Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-width: 400px;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .back-link {
            margin-bottom: 20px;
        }
        .back-link a {
            color: #007bff;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .recent-stocks {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
        }
        .recent-stocks table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .recent-stocks th, .recent-stocks td {
            padding: 6px 8px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .recent-stocks th {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .required {
            color: #dc3545;
        }
        .info-text {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-link">
            <a href="progressive_data_loader.php">← Back to Progressive Data Loader</a>
        </div>
        
        <div class="header">
            <h1>Add Stock to Registry</h1>
            <p>Add a new stock symbol to the registry for historical data loading</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="section">
            <h3>Stock Information</h3>
            
            <form method="POST">
                <div class="form-group">
                    <label for="symbol">Stock Symbol <span class="required">*</span></label>
                    <input type="text" id="symbol" name="symbol" 
                           value="<?php echo htmlspecialchars($_POST['symbol'] ?? ''); ?>"
                           placeholder="e.g., AAPL" 
                           style="text-transform: uppercase;"
                           maxlength="10" required>
                    <div class="info-text">Enter the ticker symbol (1-10 characters, will be converted to uppercase)</div>
                </div>
                
                <div class="form-group">
                    <label for="name">Company Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                           placeholder="e.g., Apple Inc." 
                           maxlength="255" required>
                    <div class="info-text">Full company name as it appears in financial data</div>
                </div>
                
                <div class="form-group">
                    <label for="sector">Sector</label>
                    <input type="text" id="sector" name="sector" 
                           value="<?php echo htmlspecialchars($_POST['sector'] ?? ''); ?>"
                           placeholder="e.g., Technology" 
                           maxlength="100">
                    <div class="info-text">Business sector classification (optional)</div>
                </div>
                
                <div class="form-group">
                    <label for="industry">Industry</label>
                    <input type="text" id="industry" name="industry" 
                           value="<?php echo htmlspecialchars($_POST['industry'] ?? ''); ?>"
                           placeholder="e.g., Consumer Electronics" 
                           maxlength="100">
                    <div class="info-text">Specific industry classification (optional)</div>
                </div>
                
                <div class="form-group">
                    <label for="exchange">Exchange</label>
                    <select id="exchange" name="exchange">
                        <option value="NASDAQ" <?php echo ($_POST['exchange'] ?? 'NASDAQ') === 'NASDAQ' ? 'selected' : ''; ?>>NASDAQ</option>
                        <option value="NYSE" <?php echo ($_POST['exchange'] ?? '') === 'NYSE' ? 'selected' : ''; ?>>NYSE</option>
                        <option value="AMEX" <?php echo ($_POST['exchange'] ?? '') === 'AMEX' ? 'selected' : ''; ?>>AMEX</option>
                        <option value="OTC" <?php echo ($_POST['exchange'] ?? '') === 'OTC' ? 'selected' : ''; ?>>OTC</option>
                        <option value="TSX" <?php echo ($_POST['exchange'] ?? '') === 'TSX' ? 'selected' : ''; ?>>TSX (Toronto)</option>
                    </select>
                    <div class="info-text">Stock exchange where the symbol is traded</div>
                </div>
                
                <button type="submit" class="btn">Add Stock to Registry</button>
                <a href="progressive_data_loader.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>

        <?php if (!empty($recentStocks)): ?>
        <div class="section">
            <h3>Recently Added Stocks</h3>
            <div class="recent-stocks">
                <table>
                    <thead>
                        <tr>
                            <th>Symbol</th>
                            <th>Company Name</th>
                            <th>Sector</th>
                            <th>Industry</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentStocks as $stock): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($stock['symbol']); ?></strong></td>
                            <td><?php echo htmlspecialchars($stock['name']); ?></td>
                            <td><?php echo htmlspecialchars($stock['sector'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($stock['industry'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

<script>
// Auto-uppercase symbol input
document.getElementById('symbol').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const symbol = document.getElementById('symbol').value.trim();
    const name = document.getElementById('name').value.trim();
    
    if (!symbol || !name) {
        alert('Symbol and Company Name are required.');
        e.preventDefault();
        return false;
    }
    
    if (!/^[A-Z]{1,10}$/.test(symbol)) {
        alert('Symbol must be 1-10 uppercase letters only.');
        e.preventDefault();
        return false;
    }
});
</script>
</body>
</html>