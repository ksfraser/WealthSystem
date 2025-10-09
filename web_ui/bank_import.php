<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/MidCapBankImportDAO.php';
require_once __DIR__ . '/BankAccountsDAO.php';
require_once __DIR__ . '/NavigationService.php';

$dao = new MidCapBankImportDAO();
$bankDAO = new BankAccountsDAO();
$navService = new NavigationService();

// Get current user
$currentUser = getCurrentUser();
$userId = $currentUser['id'];

// Handle selecting existing account
if (isset($_POST['action']) && $_POST['action'] === 'select_existing') {
    $stagingFile = $_POST['staging_file'];
    $type = $_POST['csv_type'];
    $existingAccountId = (int)$_POST['existing_account_id'];

    // Get the existing account details
    $existingAccount = $bankDAO->getBankAccountById($existingAccountId);
    if (!$existingAccount) {
        echo '<h2 style="color:red;">Error</h2>';
        echo '<p>Selected bank account not found.</p>';
        echo '<a href="bank_import.php">Try Again</a>';
        exit;
    }

    $rows = [];
    if (($handle = fopen($stagingFile, 'r')) !== false) {
        $header = fgetcsv($handle, 0, ',', '"', '\\');
        while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $rows[] = array_combine($header, $data);
        }
        fclose($handle);
    }

    // Add bank/account info to each row
    foreach ($rows as &$row) {
        $row['bank_name'] = $existingAccount['bank_name'];
        $row['account_number'] = $existingAccount['account_number'];
    }

    try {
        $dao->importToMidCap($rows, $type, $userId, $existingAccountId);

        echo "<h2>Import Complete</h2>";
        echo "<p>Successfully imported " . count($rows) . " transactions to existing account '{$existingAccount['bank_name']} - {$existingAccount['account_number']}'.</p>";
        echo '<div style="margin-top:1em;">';
        echo '<h3>Next Actions</h3>';
        echo '<ul>';
        echo '<li><a href="view_imported_transactions.php?bank_account_id=' . $existingAccountId . '">View Imported Transactions</a></li>';
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

// Handle creating new account and importing
if (isset($_POST['action']) && $_POST['action'] === 'create_and_import') {
    $stagingFile = $_POST['staging_file'];
    $type = $_POST['csv_type'];
    $bank = $_POST['bank_name'];
    $acct = $_POST['account_number'];
    $nickname = $_POST['nickname'] ?? '';
    $accountType = $_POST['account_type'] ?? 'Investment Account';
    $currency = $_POST['currency'] ?? 'CAD';

    $rows = [];
    if (($handle = fopen($stagingFile, 'r')) !== false) {
        $header = fgetcsv($handle, 0, ',', '"', '\\');
        while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
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
        // Create bank account first to get the ID
        $bankAccountId = $bankDAO->createBankAccountIfNotExists($bank, $acct, $userId, $nickname, $accountType, $currency);

        $dao->importToMidCap($rows, $type, $userId, $bankAccountId);

        echo "<h2>Import Complete</h2>";
        echo "<p>Successfully imported " . count($rows) . " transactions.</p>";
        echo "<p>New bank account '{$bank} - {$acct}' has been created and you have been granted owner access.</p>";
        echo '<div style="margin-top:1em;">';
        echo '<h3>Next Actions</h3>';
        echo '<ul>';
        echo '<li><a href="view_imported_transactions.php?bank_account_id=' . $bankAccountId . '">View Imported Transactions</a></li>';
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

// Handle file upload and staging
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
            $prompt = 'Could not identify bank/account. Please assign to an account:';
        } else {
            $prompt = 'Bank/account identified: ' . htmlspecialchars(json_encode($bankInfo)) . '. Please confirm or assign to a different account:';
        }

        // Get user's accessible bank accounts
        $userBankAccounts = $bankDAO->getUserAccessibleBankAccounts($userId);

        // Show staging complete with account selection form
        echo "<h2>Staging Complete</h2>";
        echo "<p>$prompt</p>";
        echo "<p>Processed " . count($rows) . " rows from your CSV file.</p>";

        echo '<div style="margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9;">';
        echo '<h3>Assign to Existing Bank Account</h3>';
        echo '<form method="post" action="bank_import.php" style="margin-bottom: 20px;">';
        echo '<input type="hidden" name="staging_file" value="' . htmlspecialchars($stagingFile) . '">';
        echo '<input type="hidden" name="csv_type" value="' . htmlspecialchars($type) . '">';
        echo '<input type="hidden" name="action" value="select_existing">';

        echo '<label for="existing_account" style="display: block; margin-bottom: 10px; font-weight: bold;">Select Existing Bank Account:</label>';
        echo '<select name="existing_account_id" id="existing_account" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">';
        echo '<option value="">-- Choose an existing account --</option>';
        foreach ($userBankAccounts as $account) {
            $displayName = htmlspecialchars($account['bank_name'] . ' - ' . $account['account_number']);
            if (!empty($account['account_nickname'])) {
                $displayName .= ' (' . htmlspecialchars($account['account_nickname']) . ')';
            }
            echo '<option value="' . $account['id'] . '">' . $displayName . '</option>';
        }
        echo '</select>';

        echo '<button type="submit" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Select Account & Import</button>';
        echo '</form>';

        echo '<hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">';

        echo '<h3>Or Create New Bank Account</h3>';
        echo '<button type="button" id="showCreateForm" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-bottom: 15px;">Create New Account</button>';

        echo '<div id="createFormContainer" style="display: none; background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); border: 2px solid #2196f3; padding: 25px; border-radius: 8px; margin-top: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
        echo '<h3 style="color: #1976d2; margin-top: 0; text-align: center;">🏦 Create New Bank Account</h3>';
        echo '<p style="text-align: center; color: #666; margin-bottom: 20px;">Add a new bank account to track your transactions</p>';
        echo '<form method="post" action="bank_import.php" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; align-items: end;">';
        echo '<input type="hidden" name="staging_file" value="' . htmlspecialchars($stagingFile) . '">';
        echo '<input type="hidden" name="csv_type" value="' . htmlspecialchars($type) . '">';
        echo '<input type="hidden" name="action" value="create_and_import">';
        echo '<div>';
        echo '<label for="bank_name" style="display: block; margin-bottom: 5px; font-weight: bold;">Bank Name *</label>';
        echo '<input type="text" id="bank_name" name="bank_name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">';
        echo '</div>';
        echo '<div>';
        echo '<label for="account_number" style="display: block; margin-bottom: 5px; font-weight: bold;">Account Number *</label>';
        echo '<input type="text" id="account_number" name="account_number" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">';
        echo '</div>';
        echo '<div>';
        echo '<label for="nickname" style="display: block; margin-bottom: 5px; font-weight: bold;">Nickname (Optional)</label>';
        echo '<input type="text" id="nickname" name="nickname" placeholder="e.g., My RBC Account" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">';
        echo '</div>';
        echo '<div>';
        echo '<label for="account_type" style="display: block; margin-bottom: 5px; font-weight: bold;">Account Type</label>';
        echo '<select id="account_type" name="account_type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">';
        echo '<option value="Investment Account">Investment Account</option>';
        echo '<option value="Savings Account">Savings Account</option>';
        echo '<option value="Checking Account">Checking Account</option>';
        echo '<option value="Retirement Account">Retirement Account</option>';
        echo '<option value="Other">Other</option>';
        echo '</select>';
        echo '</div>';
        echo '<div>';
        echo '<label for="currency" style="display: block; margin-bottom: 5px; font-weight: bold;">Currency</label>';
        echo '<select id="currency" name="currency" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">';
        echo '<option value="CAD">CAD - Canadian Dollar</option>';
        echo '<option value="USD">USD - US Dollar</option>';
        echo '<option value="EUR">EUR - Euro</option>';
        echo '<option value="GBP">GBP - British Pound</option>';
        echo '</select>';
        echo '</div>';
        echo '<div style="grid-column: span 2;">';
        echo '<button type="submit" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 16px;">Create Account & Import</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>';

        echo '</div>';

        // Add JavaScript to toggle create form
        echo '<script>
        document.getElementById("showCreateForm").addEventListener("click", function() {
            const container = document.getElementById("createFormContainer");
            if (container.style.display === "none") {
                container.style.display = "block";
                this.textContent = "Hide Create Form";
            } else {
                container.style.display = "none";
                this.textContent = "Create New Account";
            }
        });
        </script>';

        exit;
    } catch (Throwable $e) {
        echo '<h2 style="color:red;">Error during staging</h2>';
        echo '<pre>' . htmlspecialchars($e) . '</pre>';
        exit;
    }
}

// Upload form
$navHeader = $navService->renderNavigationHeader('Import Bank CSV');
$navCSS = $navService->getDashboardCSS();
$navScript = $navService->getNavigationScript();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Bank CSV - Enhanced Trading System</title>
    <link rel="stylesheet" href="css/nav-core.css">
    <link rel="stylesheet" href="css/nav-links.css">
    <link rel="stylesheet" href="css/dropdown-base.css">
    <link rel="stylesheet" href="css/user-dropdown.css">
    <link rel="stylesheet" href="css/portfolio-dropdown.css">
    <link rel="stylesheet" href="css/stocks-dropdown.css">
    <link rel="stylesheet" href="css/nav-responsive.css">
    <style>
        .import-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .import-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .import-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        .form-group select,
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .submit-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }

        .submit-btn:hover {
            background: #218838;
        }

        .help-text {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php echo $navHeader; ?>

    <div class="import-container">
        <div class="import-header">
            <h1>Import Bank CSV</h1>
            <p>Upload and import your bank transaction data</p>
        </div>

        <div class="import-form">
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="csv_type">CSV Type:</label>
                    <select name="csv_type" id="csv_type">
                        <option value="holdings">Account Holdings</option>
                        <option value="transactions">Transaction History</option>
                    </select>
                    <div class="help-text">
                        Select "Account Holdings" for position data or "Transaction History" for trade records
                    </div>
                </div>

                <div class="form-group">
                    <label for="csv_file">CSV File:</label>
                    <input type="file" name="csv_file" id="csv_file" required accept=".csv">
                    <div class="help-text">
                        Choose a CSV file exported from your bank or brokerage
                    </div>
                </div>

                <button type="submit" class="submit-btn">Upload and Process</button>
            </form>
        </div>
    </div>

    <?php echo $navScript; ?>
</body>
</html>
