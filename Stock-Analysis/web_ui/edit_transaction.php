<?php
/**
 * Edit Transaction Page
 *
 * Allows a user to edit an imported transaction.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/InvestGLDAO.php';
require_once __DIR__ . '/NavigationService.php';

// Initialize services
$navService = new NavigationService();
$investGLDAO = new InvestGLDAO($navService->getPDO());

// Get current user
$currentUser = $navService->getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}
$userId = $currentUser['id'];

$transactionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$transaction = null;
$error = '';
$success = '';

if ($transactionId > 0) {
    $transaction = $investGLDAO->getTransactionById($transactionId, $userId);
}

if (!$transaction) {
    die('Transaction not found or you do not have permission to edit it.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'tran_date' => $_POST['tran_date'] ?? $transaction['tran_date'],
        'tran_type' => $_POST['tran_type'] ?? $transaction['tran_type'],
        'stock_symbol' => $_POST['stock_symbol'] ?? $transaction['stock_symbol'],
        'quantity' => $_POST['quantity'] ?? $transaction['quantity'],
        'price' => $_POST['price'] ?? $transaction['price'],
        'amount' => $_POST['amount'] ?? $transaction['amount'],
        'description' => $_POST['description'] ?? $transaction['description'],
    ];

    try {
        if ($investGLDAO->updateTransaction($transactionId, $data, $userId)) {
            $success = 'Transaction updated successfully!';
            // Refresh transaction data
            $transaction = $investGLDAO->getTransactionById($transactionId, $userId);
        } else {
            $error = 'Failed to update transaction. Please try again.';
        }
    } catch (Exception $e) {
        $error = 'An error occurred: ' . $e->getMessage();
    }
}

$navHeader = $navService->renderNavigationHeader('Edit Transaction');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaction - Enhanced Trading System</title>
    <link rel="stylesheet" href="css/nav-core.css">
    <style>
        body { font-family: sans-serif; margin: 0; background: #f5f5f5; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { display: inline-block; padding: 10px 20px; border-radius: 4px; text-decoration: none; color: white; border: none; cursor: pointer; }
        .btn-primary { background: #007bff; }
        .btn-primary:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <?php echo $navHeader; ?>
    <div class="container">
        <h1>Edit Transaction #<?php echo htmlspecialchars($transactionId); ?></h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="tran_date">Date</label>
                <input type="date" id="tran_date" name="tran_date" value="<?php echo htmlspecialchars($transaction['tran_date']); ?>" required>
            </div>
            <div class="form-group">
                <label for="tran_type">Type</label>
                <input type="text" id="tran_type" name="tran_type" value="<?php echo htmlspecialchars($transaction['tran_type']); ?>" required>
            </div>
            <div class="form-group">
                <label for="stock_symbol">Symbol</label>
                <input type="text" id="stock_symbol" name="stock_symbol" value="<?php echo htmlspecialchars($transaction['stock_symbol']); ?>">
            </div>
            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" step="any" id="quantity" name="quantity" value="<?php echo htmlspecialchars($transaction['quantity']); ?>">
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" step="any" id="price" name="price" value="<?php echo htmlspecialchars($transaction['price']); ?>">
            </div>
            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" step="any" id="amount" name="amount" value="<?php echo htmlspecialchars($transaction['amount']); ?>">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($transaction['description']); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="view_imported_transactions.php?bank_account_id=<?php echo htmlspecialchars($transaction['bank_account_id']); ?>" class="btn btn-secondary">Back to List</a>
        </form>
    </div>
</body>
</html>
