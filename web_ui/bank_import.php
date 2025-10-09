<?php

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/MidCapBankImportDAO.php';
require_once __DIR__ . '/BankAccountsDAO.php';

$dao = new MidCapBankImportDAO();
$bankDAO = new BankAccountsDAO();

// Get current user
$currentUser = getCurrentUser();
$userId = $currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $type = $_POST['csv_type'] ?? '';
    $tmpName = $_FILES['csv_file']['tmp_name'];
    try {
        if ($type === 'holdings') {
            $rows = $dao->parseAccountHoldingsCSV($tmpName);
        } else {
            $rows = $dao->parseTransactionHistoryCSV($tmpName);
        }
        $stagingFile = $dao->saveStagingCSV($rows, $type);
        $bankInfo = $dao->identifyBankAccount($rows);
        if ($bankInfo === null) {
            $prompt = 'Could not identify bank/account. Please enter details:';
        } else {
            $prompt = 'Bank/account identified: ' . htmlspecialchars(json_encode($bankInfo));
        }
        // Show confirmation form
        echo "<h2>Staging Complete</h2>";
        echo "<p>$prompt</p>";
        echo '<form method="post" action="bank_import.php">';
        echo '<input type="hidden" name="staging_file" value="' . htmlspecialchars($stagingFile) . '">';
        echo '<input type="text" name="bank_name" placeholder="Bank Name" required> ';
        echo '<input type="text" name="account_number" placeholder="Account Number" required> ';
        echo '<input type="hidden" name="csv_type" value="' . htmlspecialchars($type) . '">';
        echo '<button type="submit" name="confirm_import">Import</button>';
        echo '</form>';
        exit;
    } catch (Throwable $e) {
        echo '<h2 style="color:red;">Error during staging</h2>';
        echo '<pre>' . htmlspecialchars($e) . '</pre>';
        exit;
    }
}

if (isset($_POST['confirm_import'])) {
    $stagingFile = $_POST['staging_file'];
    $type = $_POST['csv_type'];
    $bank = $_POST['bank_name'];
    $acct = $_POST['account_number'];
    $rows = [];
    if (($handle = fopen($stagingFile, 'r')) !== false) {
        $header = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = array_combine($header, $data);
        }
        fclose($handle);
    }
    // Add bank/account info to each row
    foreach ($rows as &$row) {
        $row['bank_name'] = $bank;
        $row['account_number'] = $acct;
    }
    try {
        $dao->importToMidCap($rows, $type);

        // Create bank account and grant access
        $bankAccountId = $bankDAO->createBankAccountIfNotExists($bank, $acct, $userId, '', 'Investment Account', 'CAD');

        echo "<h2>Import Complete</h2>";
        echo "<p>Successfully imported " . count($rows) . " transactions.</p>";
        echo "<p>Bank account '{$bank} - {$acct}' has been created and you have been granted owner access.</p>";
        echo '<div style="margin-top:1em;">';
        echo '<h3>Next Actions</h3>';
        echo '<ul>';
        echo '<li><a href="view_imported_transactions.php">View Imported Transactions</a></li>';
        echo '<li><a href="user_bank_accounts.php">View Your Bank Accounts</a></li>';
        echo '<li><a href="reconcile_ledger.php">Reconcile with Ledger/Journal</a></li>';
        echo '<li><a href="bank_import.php">Import Another File</a></li>';
        echo '<li><a href="dashboard.php">Return to Dashboard</a></li>';
        echo '<li><a href="download_import_log.php?file=' . urlencode($stagingFile) . '">Download Import Log/Report</a></li>';
        echo '</ul>';
        echo '</div>';
        exit;
    } catch (Throwable $e) {
        echo '<h2 style="color:red;">Error during import</h2>';
        echo '<pre>' . htmlspecialchars($e) . '</pre>';
        exit;
    }
}

// Upload form
?>
<h2>Import Bank CSV</h2>
<form method="post" enctype="multipart/form-data">
    <label>CSV Type:
        <select name="csv_type">
            <option value="holdings">Account Holdings</option>
            <option value="transactions">Transaction History</option>
        </select>
    </label><br><br>
    <input type="file" name="csv_file" required><br><br>
    <button type="submit">Upload</button>
</form>
