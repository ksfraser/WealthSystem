<?php
/**
 * View Imported Transactions Page
 *
 * Shows imported transactions for a specific bank account or all transactions for the current user.
 * Filters transactions based on bank_account_id parameter if provided.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/InvestGLDAO.php';
require_once __DIR__ . '/MidCapBankImportDAO.php';
require_once __DIR__ . '/NavigationService.php';

// Initialize services
$navService = new NavigationService();
$midCapDAO = new MidCapBankImportDAO();

// Get PDO connection using reflection (since it's protected)
try {
    $reflection = new ReflectionClass($midCapDAO);
    $pdoProperty = $reflection->getProperty('pdo');
    $pdoProperty->setAccessible(true);
    $pdo = $pdoProperty->getValue($midCapDAO);
    
    if (!$pdo) {
        throw new Exception('Database connection not available');
    }
    
    $investGLDAO = new InvestGLDAO($pdo);
} catch (Exception $e) {
    error_log('Failed to initialize database connection: ' . $e->getMessage());
    die('Database connection error. Please contact administrator.');
}

// Get current user
$currentUser = $navService->getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

$userId = $currentUser['id'];
$bankAccountId = isset($_GET['bank_account_id']) ? (int)$_GET['bank_account_id'] : null;
$bankAccount = null; // Initialize bank account variable

// Get bank account details if ID is provided
if ($bankAccountId) {
    try {
        $stmt = $pdo->prepare("SELECT bank_name, account_number FROM bank_accounts WHERE id = ?");
        $stmt->execute([$bankAccountId]);
        $bankAccount = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Failed to fetch bank account details: ' . $e->getMessage());
        $bankAccount = null;
    }
}

// Get navigation header
$navHeader = $navService->renderNavigationHeader('Imported Transactions');
$navCSS = $navService->getDashboardCSS();
$navScript = $navService->getNavigationScript();

// Handle filter and match form submission
$filter = $_GET['filter'] ?? 'all';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['match_opening'], $_POST['match_real'])) {
    $dummyId = (int)$_POST['match_opening'];
    $realId = (int)$_POST['match_real'];
    
    try {
        // Fetch both transactions
        $stmt = $pdo->prepare("SELECT * FROM gl_trans_invest WHERE id = ? AND user_id = ?");
        $stmt->execute([$dummyId, $userId]);
        $dummy = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->execute([$realId, $userId]);
        $real = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dummy && $real) {
            // If quantities/costs match, just match them
            if ($dummy['quantity'] == $real['quantity'] && $dummy['amount'] == $real['amount']) {
                $investGLDAO->matchTransactions($dummyId, $realId);
                $msg = 'Transactions matched.';
            } else {
                // Create adjustment for the difference
                $adjQty = $dummy['quantity'] - $real['quantity'];
                $adjAmt = $dummy['amount'] - $real['amount'];
                $desc = 'Auto adjustment for partial match';
                $investGLDAO->addOpeningBalanceAdjustment($userId, $dummy['gl_account'], $dummy['stock_symbol'], date('Y-m-d'), -$adjAmt, -$adjQty, $desc);
                $investGLDAO->matchTransactions($dummyId, $realId);
                $msg = 'Transactions matched with adjustment.';
            }
        } else {
            $msg = 'Invalid transaction selection.';
        }
    } catch (Exception $e) {
        error_log('Error matching transactions: ' . $e->getMessage());
        $msg = 'Error matching transactions. Please try again.';
    }
}

// Get transactions, optionally filtered by bank account
try {
    $transactions = $investGLDAO->getTransactions($userId);
} catch (Exception $e) {
    error_log('Failed to fetch transactions: ' . $e->getMessage());
    $transactions = [];
}

// If bank_account_id is provided, filter transactions to only those from that bank account
if ($bankAccountId && $bankAccount) {
    // Filter transactions by bank name and account number from midcap_transactions
    $filteredTransactions = [];
    foreach ($transactions as $transaction) {
        try {
            // Check if this GL transaction corresponds to a midcap transaction with matching bank details
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM midcap_transactions mt
                WHERE mt.bank_name = ? AND mt.account_number = ?
                AND mt.symbol = ? AND mt.shares = ? AND mt.amount = ?
                AND DATE(mt.txn_date) = ?
            ");
            $stmt->execute([
                $bankAccount['bank_name'],
                $bankAccount['account_number'],
                $transaction['stock_symbol'],
                $transaction['quantity'],
                $transaction['amount'],
                $transaction['tran_date']
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                $filteredTransactions[] = $transaction;
            }
        } catch (Exception $e) {
            error_log('Error filtering transaction: ' . $e->getMessage());
            // Skip this transaction if there's an error
        }
    }
    $transactions = $filteredTransactions;
}

// Apply additional filters
if ($filter === 'matched') {
    $transactions = array_filter($transactions, function($row) { return $row['matched_tran_id']; });
} elseif ($filter === 'unmatched') {
    $transactions = array_filter($transactions, function($row) { return !$row['matched_tran_id'] && $row['tran_type'] !== 'OPENING_BAL_ADJ'; });
} elseif ($filter === 'adj') {
    $transactions = array_filter($transactions, function($row) { return $row['tran_type'] === 'OPENING_BAL_ADJ'; });
}

function statusLabel($row) {
	if ($row['tran_type'] === 'OPENING_BAL_ADJ') return 'Adj';
	if ($row['tran_type'] === 'OPENING_BAL') return $row['matched_tran_id'] ? 'Matched' : 'Unmatched';
	if ($row['matched_tran_id']) return 'Matched';
	return 'Unmatched';
}

// Set page title
$pageTitle = 'Imported Transactions';
if ($bankAccountId && isset($bankAccount)) {
    $pageTitle .= " - {$bankAccount['bank_name']} {$bankAccount['account_number']}";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo htmlspecialchars($pageTitle); ?> - Enhanced Trading System</title>
	<link rel="stylesheet" href="css/nav-core.css">
	<link rel="stylesheet" href="css/nav-links.css">
	<link rel="stylesheet" href="css/dropdown-base.css">
	<link rel="stylesheet" href="css/user-dropdown.css">
	<link rel="stylesheet" href="css/portfolio-dropdown.css">
	<link rel="stylesheet" href="css/stocks-dropdown.css">
	<link rel="stylesheet" href="css/nav-responsive.css">
	<style>
		.transactions-container {
			max-width: 1200px;
			margin: 0 auto;
			padding: 20px;
		}

		.transactions-header {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			padding: 20px;
			border-radius: 8px;
			margin-bottom: 20px;
			text-align: center;
		}

		.header-actions {
			margin-top: 15px;
		}

		.import-button {
			display: inline-block;
			background: rgba(255, 255, 255, 0.2);
			color: white;
			padding: 10px 20px;
			text-decoration: none;
			border-radius: 4px;
			border: 1px solid rgba(255, 255, 255, 0.3);
			transition: background-color 0.3s ease;
		}

		.import-button:hover {
			background: rgba(255, 255, 255, 0.3);
			color: white;
			text-decoration: none;
		}

		.transactions-table {
			width: 100%;
			border-collapse: collapse;
			background: white;
			border-radius: 8px;
			overflow: hidden;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}

		.transactions-table th,
		.transactions-table td {
			padding: 12px 15px;
			text-align: left;
			border-bottom: 1px solid #ddd;
		}

		.transactions-table th {
			background: #f8f9fa;
			font-weight: 600;
			color: #333;
		}

		.transactions-table tr:hover {
			background: #f8f9fa;
		}

		.status-adj { background: #ffe0e0; }
		.status-unmatched { background: #fffbe0; }
		.status-matched { background: #e0ffe0; }

		.filter-form, .match-form {
			background: #f8f9fa;
			padding: 15px;
			border-radius: 8px;
			margin-bottom: 20px;
		}

		.filter-form select, .match-form select {
			padding: 8px;
			border: 1px solid #ddd;
			border-radius: 4px;
			margin-left: 10px;
		}

		.match-form button {
			background: #007bff;
			color: white;
			border: none;
			padding: 8px 16px;
			border-radius: 4px;
			cursor: pointer;
			margin-left: 10px;
		}

		.match-form button:hover {
			background: #0056b3;
		}

		.message {
			background: #d4edda;
			color: #155724;
			padding: 15px;
			border-radius: 4px;
			margin-bottom: 20px;
			border: 1px solid #c3e6cb;
		}

		.back-link {
			display: inline-block;
			background: #6c757d;
			color: white;
			padding: 10px 20px;
			text-decoration: none;
			border-radius: 4px;
			margin-top: 20px;
		}

		.back-link:hover {
			background: #545b62;
			color: white;
			text-decoration: none;
		}
	</style>
</head>
<body>
	<?php echo $navHeader; ?>

	<div class="transactions-container">
		<div class="transactions-header">
			<h1><?php echo htmlspecialchars($pageTitle); ?></h1>
			<p>View and manage your imported investment transactions</p>
			<div class="header-actions">
				<a href="bank_import.php" class="import-button">Import File</a>
			</div>
		</div>

		<?php if (!empty($msg)): ?>
			<div class="message"><?php echo htmlspecialchars($msg); ?></div>
		<?php endif; ?>

		<form method="get" class="filter-form">
			<label>Filter:
				<select name="filter" onchange="this.form.submit()">
					<option value="all"<?= $filter==='all'?' selected':'' ?>>All</option>
					<option value="matched"<?= $filter==='matched'?' selected':'' ?>>Matched</option>
					<option value="unmatched"<?= $filter==='unmatched'?' selected':'' ?>>Unmatched</option>
					<option value="adj"<?= $filter==='adj'?' selected':'' ?>>Adjustment</option>
				</select>
			</label>
			<?php if ($bankAccountId): ?>
				<input type="hidden" name="bank_account_id" value="<?php echo $bankAccountId; ?>">
			<?php endif; ?>
		</form>

		<form method="post" class="match-form">
			<label>Match Opening Balance:
				<select name="match_opening">
					<option value="">--Select--</option>
					<?php foreach ($transactions as $row) if ($row['tran_type']==='OPENING_BAL' && !$row['matched_tran_id']): ?>
						<option value="<?= $row['id'] ?>">[<?= $row['id'] ?>] <?= htmlspecialchars($row['stock_symbol']) ?> Qty:<?= $row['quantity'] ?> Amt:<?= $row['amount'] ?></option>
					<?php endif; ?>
				</select>
			</label>
			<label>With Real Transaction:
				<select name="match_real">
					<option value="">--Select--</option>
					<?php foreach ($transactions as $row) if ($row['tran_type']!=='OPENING_BAL' && !$row['matched_tran_id']): ?>
						<option value="<?= $row['id'] ?>">[<?= $row['id'] ?>] <?= htmlspecialchars($row['stock_symbol']) ?> Qty:<?= $row['quantity'] ?> Amt:<?= $row['amount'] ?> (<?= $row['tran_type'] ?>)</option>
					<?php endif; ?>
				</select>
			</label>
			<button type="submit">Match</button>
		</form>

		<table class="transactions-table">
			<tr>
				<th>ID</th><th>Date</th><th>Type</th><th>Symbol</th><th>Qty</th><th>Amount</th><th>Status</th><th>Description</th>
			</tr>
			<?php foreach ($transactions as $row): ?>
			<tr class="status-<?= strtolower(statusLabel($row)) ?>">
				<td><?= htmlspecialchars($row['id']) ?></td>
				<td><?= htmlspecialchars($row['tran_date']) ?></td>
				<td><?= htmlspecialchars($row['tran_type']) ?></td>
				<td><?= htmlspecialchars($row['stock_symbol']) ?></td>
				<td><?= htmlspecialchars($row['quantity']) ?></td>
				<td><?= htmlspecialchars($row['amount']) ?></td>
				<td><?= htmlspecialchars(statusLabel($row)) ?></td>
				<td><?= htmlspecialchars($row['description']) ?></td>
			</tr>
			<?php endforeach; ?>
		</table>

		<a href="user_bank_accounts.php" class="back-link">← Back to Bank Accounts</a>
	</div>

	<?php echo $navScript; ?>
</body>
</html>
