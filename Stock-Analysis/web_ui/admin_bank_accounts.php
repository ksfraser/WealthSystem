<?php
/**
 * Bank Accounts Admin Management Interface
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/BankAccountsDAO.php';
require_once __DIR__ . '/UserAuthDAO.php';

$auth = new UserAuthDAO();
$auth->requireAdmin();

$currentUser = $auth->getCurrentUser();
$bankDAO = new BankAccountsDAO();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_bank':
                $bankName = trim($_POST['bank_name'] ?? '');
                $accountNumber = trim($_POST['account_number'] ?? '');
                $accountName = trim($_POST['account_name'] ?? '');
                $accountType = trim($_POST['account_type'] ?? '');
                $currency = trim($_POST['currency'] ?? 'USD');
                $isActive = isset($_POST['is_active']);
                
                if (empty($bankName) || empty($accountNumber)) {
                    throw new Exception('Bank name and account number are required');
                }
                
                $accountId = $bankDAO->createBankAccount($bankName, $accountNumber, $accountName, $accountType, $currency, $isActive);
                $message = "Bank account added successfully with ID: $accountId";
                $messageType = 'success';
                break;
                
            case 'update_bank':
                $accountId = intval($_POST['account_id'] ?? 0);
                $bankName = trim($_POST['bank_name'] ?? '');
                $accountNumber = trim($_POST['account_number'] ?? '');
                $accountName = trim($_POST['account_name'] ?? '');
                $accountType = trim($_POST['account_type'] ?? '');
                $currency = trim($_POST['currency'] ?? 'USD');
                $isActive = isset($_POST['is_active']);
                
                if ($accountId <= 0) {
                    throw new Exception('Invalid account ID');
                }
                
                if (empty($bankName) || empty($accountNumber)) {
                    throw new Exception('Bank name and account number are required');
                }
                
                $success = $bankDAO->updateBankAccount($accountId, $bankName, $accountNumber, $accountName, $accountType, $currency, $isActive);
                if ($success) {
                    $message = 'Bank account updated successfully';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to update bank account');
                }
                break;
                
            case 'delete_bank':
                $accountId = intval($_POST['account_id'] ?? 0);
                if ($accountId <= 0) {
                    throw new Exception('Invalid account ID');
                }
                
                $success = $bankDAO->deleteBankAccount($accountId);
                if ($success) {
                    $message = 'Bank account deleted successfully';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to delete bank account');
                }
                break;
                
            default:
                throw new Exception('Unknown action');
        }
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
        error_log("Bank accounts admin error: " . $e->getMessage());
    }
}

// Get all bank accounts
try {
    $accounts = $bankDAO->getAllBankAccounts();
} catch (Exception $e) {
    $accounts = [];
    $message = 'Error loading bank accounts: ' . $e->getMessage();
    $messageType = 'error';
}

// Predefined account types and currencies
$accountTypes = ['Checking', 'Savings', 'Money Market', 'TFSA', 'RRSP', 'RESP', 'RRIF', 'Other'];
$currencies = ['USD', 'CAD', 'EUR', 'GBP', 'JPY', 'AUD'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Accounts Management - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .user-info {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .form-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #f85032 0%, #e73827 100%);
            color: white;
            padding: 8px 16px;
            font-size: 0.9rem;
        }
        
        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(248, 80, 50, 0.4);
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            padding: 8px 16px;
            font-size: 0.9rem;
            margin-right: 10px;
        }
        
        .btn-edit:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.4);
        }
        
        .accounts-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .accounts-table thead {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            color: white;
        }
        
        .accounts-table th,
        .accounts-table td {
            padding: 15px;
            text-align: left;
        }
        
        .accounts-table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .accounts-table tbody tr {
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }
        
        .accounts-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .no-accounts {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: #764ba2;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            color: #2c3e50;
        }
        
        .close {
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
            transition: color 0.3s;
        }
        
        .close:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏦 Bank Accounts Management</h1>
            <div class="user-info">Logged in as: <?php echo htmlspecialchars($currentUser['username']); ?> (Admin)</div>
        </div>
        
        <div class="content">
            <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
            
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Add New Bank Account Form -->
            <div class="form-section">
                <h2>Add New Bank Account</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add_bank">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="bank_name">Bank Name *</label>
                            <input type="text" id="bank_name" name="bank_name" required 
                                   placeholder="e.g., TD Bank, Chase, RBC">
                        </div>
                        
                        <div class="form-group">
                            <label for="account_number">Account Number *</label>
                            <input type="text" id="account_number" name="account_number" required 
                                   placeholder="e.g., ****1234">
                        </div>
                        
                        <div class="form-group">
                            <label for="account_name">Nickname (Optional)</label>
                            <input type="text" id="account_name" name="account_name" 
                                   placeholder="e.g., Main Checking">
                        </div>
                        
                        <div class="form-group">
                            <label for="account_type">Account Type</label>
                            <select id="account_type" name="account_type">
                                <option value="">Select Type</option>
                                <?php foreach ($accountTypes as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>">
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="currency">Currency</label>
                            <select id="currency" name="currency">
                                <?php foreach ($currencies as $curr): ?>
                                    <option value="<?php echo htmlspecialchars($curr); ?>" 
                                            <?php echo $curr === 'USD' ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($curr); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_active" name="is_active" checked>
                        <label for="is_active">Active Account</label>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Add Bank Account</button>
                    </div>
                </form>
            </div>
            
            <!-- Existing Bank Accounts Table -->
            <div class="form-section">
                <h2>Existing Bank Accounts (<?php echo count($accounts); ?>)</h2>
                
                <?php if (empty($accounts)): ?>
                    <div class="no-accounts">
                        No bank accounts found. Add your first account above.
                    </div>
                <?php else: ?>
                    <table class="accounts-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Bank Name</th>
                                <th>Account Number</th>
                                <th>Nickname</th>
                                <th>Type</th>
                                <th>Currency</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accounts as $account): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($account['id']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($account['bank_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($account['account_number']); ?></td>
                                    <td><?php echo htmlspecialchars($account['account_nickname'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($account['account_type'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($account['currency'] ?? 'USD'); ?></td>
                                    <td>
                                        <?php if ($account['is_active']): ?>
                                            <span class="status-badge status-active">Active</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($account['created_at'])); ?></td>
                                    <td class="actions">
                                        <button class="btn btn-edit" 
                                                onclick="editAccount(<?php echo htmlspecialchars(json_encode($account)); ?>)">
                                            Edit
                                        </button>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this bank account?');">
                                            <input type="hidden" name="action" value="delete_bank">
                                            <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Bank Account</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_bank">
                <input type="hidden" id="edit_account_id" name="account_id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_bank_name">Bank Name *</label>
                        <input type="text" id="edit_bank_name" name="bank_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_account_number">Account Number *</label>
                        <input type="text" id="edit_account_number" name="account_number" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_account_name">Nickname (Optional)</label>
                        <input type="text" id="edit_account_name" name="account_name">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_account_type">Account Type</label>
                        <select id="edit_account_type" name="account_type">
                            <option value="">Select Type</option>
                            <?php foreach ($accountTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>">
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_currency">Currency</label>
                        <select id="edit_currency" name="currency">
                            <?php foreach ($currencies as $curr): ?>
                                <option value="<?php echo htmlspecialchars($curr); ?>">
                                    <?php echo htmlspecialchars($curr); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="edit_is_active" name="is_active">
                    <label for="edit_is_active">Active Account</label>
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">Update Account</button>
                    <button type="button" class="btn" onclick="closeEditModal()" 
                            style="background: #6c757d; color: white;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editAccount(account) {
            document.getElementById('edit_account_id').value = account.id;
            document.getElementById('edit_bank_name').value = account.bank_name;
            document.getElementById('edit_account_number').value = account.account_number;
            document.getElementById('edit_account_name').value = account.account_nickname || '';
            document.getElementById('edit_account_type').value = account.account_type || '';
            document.getElementById('edit_currency').value = account.currency || 'USD';
            document.getElementById('edit_is_active').checked = account.is_active == 1;
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>
