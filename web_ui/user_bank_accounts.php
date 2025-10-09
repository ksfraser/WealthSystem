<?php
/**
 * User Bank Accounts Page
 *
 * Shows users the bank accounts they have access to based on RBAC permissions.
 * Users can view account details but cannot manage access (admin-only).
 *
 * Features:
 * - Shows only bank accounts user has access to
 * - Displays permission level for each account
 * - Links to view transactions for each account
 * - Clean, responsive UI consistent with other pages
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/BankAccountsDAO.php';
require_once __DIR__ . '/NavigationService.php';

// Initialize services
$navService = new NavigationService();
$bankDAO = new BankAccountsDAO();

// Get current user
$currentUser = $navService->getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

$userId = $currentUser['id'];

// Get bank accounts user has access to
try {
    $userBankAccounts = $bankDAO->getUserAccessibleBankAccounts($userId);
} catch (Exception $e) {
    $userBankAccounts = [];
    $error = "Error loading bank accounts: " . $e->getMessage();
}

// Handle bank account creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_account'])) {
    $bankName = trim($_POST['bank_name'] ?? '');
    $accountNumber = trim($_POST['account_number'] ?? '');
    $nickname = trim($_POST['nickname'] ?? '');
    $accountType = $_POST['account_type'] ?? 'Investment Account';
    $currency = $_POST['currency'] ?? 'CAD';

    try {
        $bankAccountId = $bankDAO->createBankAccountIfNotExists($bankName, $accountNumber, $userId, $nickname, $accountType, $currency);
        $successMessage = "Bank account '{$bankName} - {$accountNumber}' has been created successfully and you have been granted owner access.";

        // Refresh the accounts list
        $userBankAccounts = $bankDAO->getUserAccessibleBankAccounts($userId);
    } catch (Exception $e) {
        $error = "Error creating bank account: " . $e->getMessage();
    }
}

// Get navigation header
$navHeader = $navService->renderNavigationHeader('My Bank Accounts');
$navCSS = $navService->getDashboardCSS();
$navScript = $navService->getNavigationScript();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bank Accounts - Enhanced Trading System</title>
    <link rel="stylesheet" href="css/nav-core.css">
    <link rel="stylesheet" href="css/nav-links.css">
    <link rel="stylesheet" href="css/dropdown-base.css">
    <link rel="stylesheet" href="css/user-dropdown.css">
    <link rel="stylesheet" href="css/portfolio-dropdown.css">
    <link rel="stylesheet" href="css/stocks-dropdown.css">
    <link rel="stylesheet" href="css/nav-responsive.css">
    <style>
        .bank-accounts-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .bank-accounts-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .bank-accounts-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .bank-accounts-table th,
        .bank-accounts-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .bank-accounts-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .bank-accounts-table tr:hover {
            background: #f8f9fa;
        }

        .permission-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .permission-owner {
            background: #28a745;
            color: white;
        }

        .permission-read-write {
            background: #007bff;
            color: white;
        }

        .permission-read {
            background: #6c757d;
            color: white;
        }

        .view-transactions-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .view-transactions-btn:hover {
            background: #0056b3;
            text-decoration: none;
            color: white;
        }

        .no-accounts {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .account-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .account-info h3 {
            margin-top: 0;
            color: #495057;
        }
    </style>
</head>
<body>
    <?php echo $navHeader; ?>

    <div class="bank-accounts-container">
        <div class="bank-accounts-header">
            <h1>My Bank Accounts</h1>
            <p>View the bank accounts you have access to</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="account-info">
            <h3>Account Access Information</h3>
            <p>You have access to <strong><?php echo count($userBankAccounts); ?></strong> bank account(s).</p>
            <ul>
                <li><strong>Owner</strong>: Full access including managing who can access this account</li>
                <li><strong>Read/Write</strong>: Can view and modify account data</li>
                <li><strong>Read</strong>: Can only view account data</li>
            </ul>
        </div>

        <?php if (isset($successMessage)): ?>
            <div class="success-message" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <div class="create-account-section" style="background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); border: 2px solid #2196f3; padding: 25px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <h3 style="color: #1976d2; margin-top: 0; text-align: center;">🏦 Create New Bank Account</h3>
            <p style="text-align: center; color: #666; margin-bottom: 20px;">Add a new bank account to track your transactions</p>
            <form method="post" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; align-items: end;">
                <div>
                    <label for="bank_name" style="display: block; margin-bottom: 5px; font-weight: bold;">Bank Name *</label>
                    <input type="text" id="bank_name" name="bank_name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div>
                    <label for="account_number" style="display: block; margin-bottom: 5px; font-weight: bold;">Account Number *</label>
                    <input type="text" id="account_number" name="account_number" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div>
                    <label for="nickname" style="display: block; margin-bottom: 5px; font-weight: bold;">Nickname (Optional)</label>
                    <input type="text" id="nickname" name="nickname" placeholder="e.g., My RBC Account" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div>
                    <label for="account_type" style="display: block; margin-bottom: 5px; font-weight: bold;">Account Type</label>
                    <select id="account_type" name="account_type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="Investment Account">Investment Account</option>
                        <option value="Savings Account">Savings Account</option>
                        <option value="Checking Account">Checking Account</option>
                        <option value="Retirement Account">Retirement Account</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label for="currency" style="display: block; margin-bottom: 5px; font-weight: bold;">Currency</label>
                    <select id="currency" name="currency" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="CAD">CAD - Canadian Dollar</option>
                        <option value="USD">USD - US Dollar</option>
                        <option value="EUR">EUR - Euro</option>
                        <option value="GBP">GBP - British Pound</option>
                    </select>
                </div>
                <div style="grid-column: span 2;">
                    <button type="submit" name="create_account" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 16px;">
                        Create Bank Account
                    </button>
                </div>
            </form>
        </div>

        <?php if (empty($userBankAccounts)): ?>
            <div class="no-accounts">
                <h3>No Bank Accounts Found</h3>
                <p>You don't have access to any bank accounts yet. Use the form above to create your first bank account, or import transactions from a CSV file.</p>
            </div>
        <?php else: ?>
            <table class="bank-accounts-table">
                <thead>
                    <tr>
                        <th>Bank Name</th>
                        <th>Account Number</th>
                        <th>Nickname</th>
                        <th>Account Type</th>
                        <th>Currency</th>
                        <th>Permission Level</th>
                        <th>Granted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userBankAccounts as $account): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($account['bank_name']); ?></td>
                            <td><?php echo htmlspecialchars($account['account_number']); ?></td>
                            <td><?php echo htmlspecialchars($account['account_nickname'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($account['account_type'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($account['currency']); ?></td>
                            <td>
                                <span class="permission-badge permission-<?php echo str_replace('_', '-', $account['permission_level']); ?>">
                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', '/', $account['permission_level']))); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars(date('M j, Y', strtotime($account['granted_at']))); ?>
                                <?php if ($account['granted_by_username']): ?>
                                    <br><small>by <?php echo htmlspecialchars($account['granted_by_username']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view_imported_transactions.php?bank_account_id=<?php echo $account['id']; ?>"
                                   class="view-transactions-btn">
                                    View Transactions
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php echo $navScript; ?>
</body>
</html>