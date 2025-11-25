<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/MidCapBankImportDAO.php';
require_once __DIR__ . '/BankAccountsDAO.php';
require_once __DIR__ . '/NavigationService.php';
require_once __DIR__ . '/parsers/ParserFactory.php';
require_once __DIR__ . '/parsers/CsvFileReader.php';

$dao = new MidCapBankImportDAO();
$bankDAO = new BankAccountsDAO();
$navService = new NavigationService();
$parserFactory = new ParserFactory();
$csvReader = new CsvFileReader();

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

// Handle file upload and staging with new parser system
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $selectedBankAccountId = $_POST['bank_account_id'] ?? '';
    $selectedParser = $_POST['parser_type'] ?? '';
    $tmpName = $_FILES['csv_file']['tmp_name'];
    
    try {
        // Validate uploaded file first
        $uploadedFile = $_FILES['csv_file'];
        $validation = $parserFactory->validateUploadedFile($uploadedFile);
        if (!$validation['valid']) {
            throw new InvalidArgumentException($validation['message']);
        }
        
        // Validate bank account selection
        if (empty($selectedBankAccountId)) {
            throw new Exception('Please select a bank account for this import.');
        }
        
        // Read CSV content after validation
        $csvLines = $csvReader->readCsvLines($tmpName);
        
        // Get the selected bank account
        $selectedAccount = $bankDAO->getBankAccountById($selectedBankAccountId);
        if (!$selectedAccount) {
            throw new Exception('Selected bank account not found.');
        }
        
        // Validate parser selection and file format
        if (empty($selectedParser)) {
            // Try to auto-detect parser
            $detectedParser = $parserFactory->detectParser($csvLines);
            if ($detectedParser) {
                $selectedParser = $detectedParser;
                $detectionMessage = "Auto-detected format: " . $parserFactory->getAvailableParsers()[$detectedParser]['name'];
            } else {
                throw new Exception('Could not determine file format. Please select a parser type.');
            }
        } else {
            // Validate selected parser can handle the file
            if (!$parserFactory->validateFile($csvLines, $selectedParser)) {
                $availableParsers = $parserFactory->getAvailableParsers();
                $selectedParserName = $availableParsers[$selectedParser]['name'] ?? $selectedParser;
                
                // Try to suggest a compatible parser
                $detectedParser = $parserFactory->detectParser($csvLines);
                if ($detectedParser) {
                    $suggestedParserName = $availableParsers[$detectedParser]['name'];
                    throw new Exception("The selected format '{$selectedParserName}' cannot process this file. Try '{$suggestedParserName}' instead.");
                } else {
                    throw new Exception("The selected format '{$selectedParserName}' cannot process this file, and no compatible format was detected.");
                }
            }
            $detectionMessage = "Using selected format: " . $parserFactory->getAvailableParsers()[$selectedParser]['name'];
        }
        
        // Parse the file using the appropriate parser
        $transactions = $parserFactory->parseWithParser($csvLines, $selectedParser);
        
        if (empty($transactions)) {
            throw new Exception('No valid transactions found in the uploaded file.');
        }
        
        // Validate transaction data quality
        $validTransactions = [];
        $skippedTransactions = [];
        
        foreach ($transactions as $index => $transaction) {
            // Basic validation - ensure required fields are present
            if (empty($transaction['date']) || empty($transaction['description']) || !isset($transaction['amount'])) {
                $skippedTransactions[] = [
                    'row' => $index + 1,
                    'reason' => 'Missing required fields (date, description, or amount)',
                    'data' => $transaction
                ];
                continue;
            }
            
            // Validate date format
            $date = DateTime::createFromFormat('Y-m-d', $transaction['date']);
            if (!$date) {
                $skippedTransactions[] = [
                    'row' => $index + 1,
                    'reason' => 'Invalid date format: ' . $transaction['date'],
                    'data' => $transaction
                ];
                continue;
            }
            
            // Validate amount is numeric
            if (!is_numeric($transaction['amount'])) {
                $skippedTransactions[] = [
                    'row' => $index + 1,
                    'reason' => 'Amount is not numeric: ' . $transaction['amount'],
                    'data' => $transaction
                ];
                continue;
            }
            
            $validTransactions[] = $transaction;
        }
        
        // Check if we have any valid transactions
        if (empty($validTransactions)) {
            $message = 'No valid transactions found after validation.';
            if (!empty($skippedTransactions)) {
                $message .= ' ' . count($skippedTransactions) . ' transactions were skipped due to data quality issues.';
            }
            throw new Exception($message);
        }
        
        // Convert transactions to legacy format for the existing DAO
        $rows = [];
        foreach ($validTransactions as $transaction) {
            $rows[] = array_merge($transaction, [
                'bank_name' => $selectedAccount['bank_name'],
                'account_number' => $selectedAccount['account_number']
            ]);
        }
        
        // Import transactions to the selected bank account using existing DAO
        $dao->importToMidCap($rows, 'transactions', $userId, $selectedBankAccountId);
        
        // Display success message with comprehensive results
        echo '<div style="max-width: 800px; margin: 20px auto; padding: 20px; font-family: Arial, sans-serif;">';
        echo '<div style="background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center;">';
        echo '<h1 style="margin: 0 0 10px 0; font-size: 24px;">✅ Import Successful!</h1>';
        echo '<p style="margin: 0; font-size: 16px;">' . $detectionMessage . '</p>';
        echo '</div>';
        
        echo '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">';
        echo '<h2 style="color: #333; margin-top: 0;">Import Summary</h2>';
        echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">';
        
        echo '<div style="padding: 15px; background: #e8f5e8; border-left: 4px solid #28a745; border-radius: 4px;">';
        echo '<h3 style="margin: 0 0 5px 0; color: #155724;">✓ Successfully Imported</h3>';
        echo '<p style="margin: 0; font-size: 18px; font-weight: bold; color: #155724;">' . count($validTransactions) . ' transactions</p>';
        echo '</div>';
        
        if (!empty($skippedTransactions)) {
            echo '<div style="padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">';
            echo '<h3 style="margin: 0 0 5px 0; color: #856404;">⚠ Skipped</h3>';
            echo '<p style="margin: 0; font-size: 18px; font-weight: bold; color: #856404;">' . count($skippedTransactions) . ' transactions</p>';
            echo '</div>';
        }
        
        echo '</div>';
        
        echo '<div style="margin-bottom: 20px;">';
        echo '<h3 style="color: #333;">Target Account</h3>';
        $accountDisplay = htmlspecialchars($selectedAccount['bank_name'] . ' - ' . $selectedAccount['account_number']);
        if (!empty($selectedAccount['account_nickname'])) {
            $accountDisplay .= ' (' . htmlspecialchars($selectedAccount['account_nickname']) . ')';
        }
        echo '<p style="font-size: 16px; background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 0;">' . $accountDisplay . '</p>';
        echo '</div>';
        
        // Show skipped transactions if any
        if (!empty($skippedTransactions)) {
            echo '<div style="margin-bottom: 20px;">';
            echo '<h3 style="color: #856404;">⚠ Skipped Transactions</h3>';
            echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 15px;">';
            echo '<p style="margin: 0 0 10px 0; color: #856404;">The following transactions were skipped due to data quality issues:</p>';
            echo '<ul style="margin: 0; padding-left: 20px;">';
            foreach ($skippedTransactions as $skipped) {
                echo '<li style="margin-bottom: 5px; color: #856404;">';
                echo '<strong>Row ' . $skipped['row'] . ':</strong> ' . htmlspecialchars($skipped['reason']);
                echo '</li>';
            }
            echo '</ul>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Action buttons
        echo '<div style="text-align: center; margin: 20px 0;">';
        echo '<a href="view_imported_transactions.php?bank_account_id=' . $selectedBankAccountId . '" style="background: #007bff; color: white; text-decoration: none; padding: 12px 24px; border-radius: 4px; margin: 0 10px; display: inline-block;">View Imported Transactions</a>';
        echo '<a href="user_bank_accounts.php" style="background: #6c757d; color: white; text-decoration: none; padding: 12px 24px; border-radius: 4px; margin: 0 10px; display: inline-block;">Manage Bank Accounts</a>';
        echo '<a href="bank_import.php" style="background: #28a745; color: white; text-decoration: none; padding: 12px 24px; border-radius: 4px; margin: 0 10px; display: inline-block;">Import Another File</a>';
        echo '<a href="dashboard.php" style="background: #17a2b8; color: white; text-decoration: none; padding: 12px 24px; border-radius: 4px; margin: 0 10px; display: inline-block;">Return to Dashboard</a>';
        echo '</div>';
        echo '</div>';
        
        exit;
        
    } catch (Exception $e) {
        echo '<div style="max-width: 800px; margin: 20px auto; padding: 20px; font-family: Arial, sans-serif;">';
        echo '<div style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center;">';
        echo '<h1 style="margin: 0 0 10px 0; font-size: 24px;">❌ Import Failed</h1>';
        echo '<p style="margin: 0; font-size: 16px;">There was an error processing your file</p>';
        echo '</div>';
        
        echo '<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">';
        echo '<h2 style="color: #dc3545; margin-top: 0;">Error Details</h2>';
        echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 15px;">';
        echo '<p style="margin: 0; color: #721c24;">' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
        echo '</div>';
        
        echo '<div style="text-align: center; margin: 20px 0;">';
        echo '<a href="bank_import.php" style="background: #28a745; color: white; text-decoration: none; padding: 12px 24px; border-radius: 4px; margin: 0 10px; display: inline-block;">Try Again</a>';
        echo '<a href="dashboard.php" style="background: #6c757d; color: white; text-decoration: none; padding: 12px 24px; border-radius: 4px; margin: 0 10px; display: inline-block;">Return to Dashboard</a>';
        echo '</div>';
        echo '</div>';
        exit;
    }
}

// Legacy staging logic (keeping for backward compatibility)
if (false) { // Disabled - using new direct import above
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
}
// End of legacy staging logic

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
                    <label for="bank_account_id">Target Bank Account:</label>
                    <select name="bank_account_id" id="bank_account_id" required>
                        <option value="">-- Select Bank Account --</option>
                        <?php 
                        $userBankAccounts = $bankDAO->getUserAccessibleBankAccounts($userId);
                        foreach ($userBankAccounts as $account): 
                            $displayName = htmlspecialchars($account['bank_name'] . ' - ' . $account['account_number']);
                            if (!empty($account['account_nickname'])) {
                                $displayName .= ' (' . htmlspecialchars($account['account_nickname']) . ')';
                            }
                        ?>
                            <option value="<?php echo $account['id']; ?>">
                                <?php echo $displayName; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="help-text">
                        Select the bank account where these transactions belong. 
                        <a href="user_bank_accounts.php" target="_blank">Manage your bank accounts</a>
                    </div>
                </div>

                <div class="form-group">
                    <label for="parser_type">File Format (Optional):</label>
                    <select name="parser_type" id="parser_type">
                        <option value="">-- Auto-detect format --</option>
                        <?php 
                        $availableParsers = $parserFactory->getAvailableParsers();
                        foreach ($availableParsers as $key => $parser): 
                        ?>
                            <option value="<?php echo htmlspecialchars($key); ?>">
                                <?php echo htmlspecialchars($parser['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="help-text">
                        Leave blank to auto-detect the file format, or select a specific format if you know it
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
            
            <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h3>Supported Formats</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($availableParsers as $parser): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($parser['name']); ?>:</strong>
                            <?php echo htmlspecialchars($parser['description']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <?php echo $navScript; ?>
</body>
</html>
