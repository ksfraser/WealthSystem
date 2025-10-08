<?php
// view_imported_transactions.php
// Display all imported GL transactions for the current user (including opening balances and adjustments)
require_once __DIR__ . '/InvestGLDAO.php';
// TODO: Replace with real user/session context
$userId = 1;
$pdo = new PDO('mysql:host=localhost;dbname=test_db;charset=utf8mb4', 'test_user', 'test_pass'); // Update credentials
$dao = new InvestGLDAO($pdo);

// Handle match form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['match_opening'], $_POST['match_real'])) {
	$dummyId = (int)$_POST['match_opening'];
	$realId = (int)$_POST['match_real'];
	// Fetch both transactions
	$dummy = $pdo->query("SELECT * FROM gl_trans_invest WHERE id = $dummyId")->fetch(PDO::FETCH_ASSOC);
	$real = $pdo->query("SELECT * FROM gl_trans_invest WHERE id = $realId")->fetch(PDO::FETCH_ASSOC);
	if ($dummy && $real) {
		// If quantities/costs match, just match them
		if ($dummy['quantity'] == $real['quantity'] && $dummy['amount'] == $real['amount']) {
			$dao->matchTransactions($dummyId, $realId);
			$msg = 'Transactions matched.';
		} else {
			// Create adjustment for the difference
			$adjQty = $dummy['quantity'] - $real['quantity'];
			$adjAmt = $dummy['amount'] - $real['amount'];
			$desc = 'Auto adjustment for partial match';
			$dao->addOpeningBalanceAdjustment($userId, $dummy['gl_account'], $dummy['stock_symbol'], date('Y-m-d'), -$adjAmt, -$adjQty, $desc);
			$dao->matchTransactions($dummyId, $realId);
			$msg = 'Transactions matched with adjustment.';
		}
	} else {
		$msg = 'Invalid transaction selection.';
	}
}
$transactions = $dao->getTransactions($userId);

function statusLabel($row) {
	if ($row['tran_type'] === 'OPENING_BAL_ADJ') return 'Adj';
	if ($row['tran_type'] === 'OPENING_BAL') return $row['matched_tran_id'] ? 'Matched' : 'Unmatched';
	if ($row['matched_tran_id']) return 'Matched';
	return 'Unmatched';
}
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Imported Transactions</title>
	<style>
		table { border-collapse: collapse; width: 100%; }
		th, td { border: 1px solid #ccc; padding: 6px; }
		th { background: #eee; }
		.adj { background: #ffe0e0; }
		.unmatched { background: #fffbe0; }
		.matched { background: #e0ffe0; }
	</style>
</head>
<body>
<h2>Imported Transactions</h2>
<?php if (!empty($msg)) echo '<p style="color:green;">' . htmlspecialchars($msg) . '</p>'; ?>

<form method="post" style="margin-bottom:1em;">
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
<table>
	<tr>
		<th>ID</th><th>Date</th><th>Type</th><th>Symbol</th><th>Qty</th><th>Amount</th><th>Status</th><th>Description</th>
	</tr>
	<?php foreach ($transactions as $row): ?>
	<tr class="<?= strtolower(statusLabel($row)) ?>">
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
<a href="bank_import.php">Back to Bank Import</a>
</body>
</html>
