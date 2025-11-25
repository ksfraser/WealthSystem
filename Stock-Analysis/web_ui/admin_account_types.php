<?php
require_once __DIR__ . '/auth_check.php';
try {
    requireAdmin();
} catch (Exception $e) {
    // Redirect to a generic 'access denied' page or the dashboard
    header('Location: /dashboard.php?error=access_denied');
    exit;
}

/**
 * Admin Account Types Management
 * Handles table creation, prepopulation, and CRUD operations for account types
 */

require_once __DIR__ . '/EnhancedCommonDAO.php';
require_once __DIR__ . '/SimpleValidators.php';

/**
 * Account Types DAO that extends the centralized system
 */
class AccountTypesDAO extends EnhancedCommonDAO
{
    public function __construct($dbConfigClass = 'LegacyDatabaseConfig')
    {
        $logger = new SimpleLogger(__DIR__ . '/logs/account_types.log');
        parent::__construct($dbConfigClass, null, $logger);
    }

    /**
     * Ensure account_types table exists
     */
    public function ensureTableExists()
    {
        if (!$this->hasValidConnection()) {
            throw new RuntimeException('No database connection available');
        }

        try {
            $sql = "CREATE TABLE IF NOT EXISTS account_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(64) UNIQUE NOT NULL,
                description TEXT NULL,
                currency VARCHAR(3) DEFAULT 'CAD',
                is_registered BOOLEAN DEFAULT FALSE,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            
            $this->executeQuery($sql);
            $this->logger->info('Account types table verified/created');
            return true;
        } catch (Exception $e) {
            $this->logError('Failed to create account_types table: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Prepopulate account types with standard Canadian account types
     */
    public function prepopulateAccountTypes()
    {
        if (!$this->hasValidConnection()) {
            throw new RuntimeException('No database connection available');
        }

        // Ensure table exists before trying to populate it
        $this->ensureTableExists();

        $accountTypes = [
            // CAD Accounts
            ['name' => 'Cash', 'description' => 'Regular cash account', 'currency' => 'CAD', 'is_registered' => false],
            ['name' => 'Margin', 'description' => 'Margin trading account', 'currency' => 'CAD', 'is_registered' => false],
            ['name' => 'TFSA', 'description' => 'Tax-Free Savings Account', 'currency' => 'CAD', 'is_registered' => true],
            ['name' => 'RRSP', 'description' => 'Registered Retirement Savings Plan', 'currency' => 'CAD', 'is_registered' => true],
            ['name' => 'RRIF', 'description' => 'Registered Retirement Income Fund', 'currency' => 'CAD', 'is_registered' => true],
            ['name' => 'RESP', 'description' => 'Registered Education Savings Plan', 'currency' => 'CAD', 'is_registered' => true],
            ['name' => 'LIRA', 'description' => 'Locked-in Retirement Account', 'currency' => 'CAD', 'is_registered' => true],
            ['name' => 'LIF', 'description' => 'Life Income Fund', 'currency' => 'CAD', 'is_registered' => true],
            ['name' => 'Corporate', 'description' => 'Corporate investment account', 'currency' => 'CAD', 'is_registered' => false],
            ['name' => 'Joint', 'description' => 'Joint investment account', 'currency' => 'CAD', 'is_registered' => false],
            ['name' => 'Trust', 'description' => 'Trust account', 'currency' => 'CAD', 'is_registered' => false],
            
            // USD Accounts
            ['name' => 'USD Cash', 'description' => 'US Dollar cash account', 'currency' => 'USD', 'is_registered' => false],
            ['name' => 'USD Margin', 'description' => 'US Dollar margin account', 'currency' => 'USD', 'is_registered' => false],
            ['name' => 'USD TFSA', 'description' => 'US Dollar TFSA', 'currency' => 'USD', 'is_registered' => true],
            ['name' => 'USD RRSP', 'description' => 'US Dollar RRSP', 'currency' => 'USD', 'is_registered' => true],
            ['name' => 'USD RRIF', 'description' => 'US Dollar RRIF', 'currency' => 'USD', 'is_registered' => true],
            ['name' => 'USD RESP', 'description' => 'US Dollar RESP', 'currency' => 'USD', 'is_registered' => true],
            ['name' => 'USD LIRA', 'description' => 'US Dollar LIRA', 'currency' => 'USD', 'is_registered' => true],
            ['name' => 'USD LIF', 'description' => 'US Dollar LIF', 'currency' => 'USD', 'is_registered' => true],
            ['name' => 'USD Corporate', 'description' => 'US Dollar corporate account', 'currency' => 'USD', 'is_registered' => false],
            ['name' => 'USD Joint', 'description' => 'US Dollar joint account', 'currency' => 'USD', 'is_registered' => false],
            ['name' => 'USD Trust', 'description' => 'US Dollar trust account', 'currency' => 'USD', 'is_registered' => false],
            
            // Other
            ['name' => 'Other', 'description' => 'Other account type', 'currency' => 'CAD', 'is_registered' => false]
        ];

        $insertedCount = 0;
        $existingCount = 0;

        foreach ($accountTypes as $accountType) {
            try {
                // Check if the account type already exists
                $checkSql = "SELECT COUNT(*) FROM account_types WHERE name = :name";
                $stmt = $this->executeQuery($checkSql, ['name' => $accountType['name']]);
                $exists = $stmt->fetchColumn() > 0;

                if ($exists) {
                    $existingCount++;
                    continue;
                }

                // If not, insert it
                $sql = "INSERT INTO account_types (name, description, currency, is_registered) 
                        VALUES (:name, :description, :currency, :is_registered)";
                
                $this->executeQuery($sql, $accountType);
                $insertedCount++;
                $this->logger->info('Account type inserted', ['name' => $accountType['name']]);

            } catch (Exception $e) {
                $this->logError('Failed to process account type: ' . $accountType['name'] . ' - ' . $e->getMessage());
            }
        }

        $this->logger->info('Account types prepopulation completed', [
            'inserted' => $insertedCount,
            'existing' => $existingCount
        ]);

        return ['inserted' => $insertedCount, 'existing' => $existingCount];
    }

    /**
     * Get all account types
     */
    public function getAllAccountTypes()
    {
        if (!$this->hasValidConnection()) {
            return [];
        }

        try {
            $sql = "SELECT * FROM account_types ORDER BY currency, name";
            $stmt = $this->executeQuery($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            $this->logError('Failed to fetch account types: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Add new account type
     */
    public function addAccountType($name, $description = '', $currency = 'CAD', $isRegistered = false)
    {
        if (!$this->hasValidConnection()) {
            throw new RuntimeException('No database connection available');
        }

        $name = trim($name);
        if (empty($name)) {
            throw new InvalidArgumentException('Account type name cannot be empty');
        }

        $sql = "INSERT INTO account_types (name, description, currency, is_registered) 
                VALUES (:name, :description, :currency, :is_registered)";
        
        $params = [
            'name' => $name,
            'description' => trim($description),
            'currency' => strtoupper($currency),
            'is_registered' => $isRegistered ? 1 : 0
        ];

        try {
            $this->executeQuery($sql, $params);
            $insertId = $this->getLastInsertId();
            
            $this->logger->info('Account type added', [
                'id' => $insertId,
                'name' => $name,
                'currency' => $currency
            ]);
            
            return $insertId;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                throw new InvalidArgumentException("Account type '$name' already exists");
            }
            throw $e;
        }
    }

    /**
     * Update account type
     */
    public function updateAccountType($id, $name, $description = '', $currency = 'CAD', $isRegistered = false, $isActive = true)
    {
        if (!$this->hasValidConnection()) {
            throw new RuntimeException('No database connection available');
        }

        $name = trim($name);
        if (empty($name)) {
            throw new InvalidArgumentException('Account type name cannot be empty');
        }

        $sql = "UPDATE account_types 
                SET name = :name, description = :description, currency = :currency, 
                    is_registered = :is_registered, is_active = :is_active
                WHERE id = :id";
        
        $params = [
            'id' => $id,
            'name' => $name,
            'description' => trim($description),
            'currency' => strtoupper($currency),
            'is_registered' => $isRegistered ? 1 : 0,
            'is_active' => $isActive ? 1 : 0
        ];

        $stmt = $this->executeQuery($sql, $params);
        $rowsAffected = $stmt->rowCount();

        if ($rowsAffected > 0) {
            $this->logger->info('Account type updated', ['id' => $id, 'name' => $name]);
            return true;
        }
        return false;
    }

    /**
     * Delete account type
     */
    public function deleteAccountType($id)
    {
        if (!$this->hasValidConnection()) {
            throw new RuntimeException('No database connection available');
        }

        $sql = "DELETE FROM account_types WHERE id = :id";
        $stmt = $this->executeQuery($sql, ['id' => $id]);
        
        $rowsAffected = $stmt->rowCount();
        if ($rowsAffected > 0) {
            $this->logger->info('Account type deleted', ['id' => $id]);
            return true;
        }
        return false;
    }

    /**
     * Get account type by ID
     */
    public function getAccountTypeById($id)
    {
        if (!$this->hasValidConnection()) {
            return null;
        }

        $sql = "SELECT * FROM account_types WHERE id = :id";
        $stmt = $this->executeQuery($sql, ['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Check if table exists and has data
     */
    public function getTableStatus()
    {
        if (!$this->hasValidConnection()) {
            return ['exists' => false, 'count' => 0, 'populated' => false];
        }

        try {
            // Check if table exists
            $sql = "SHOW TABLES LIKE 'account_types'";
            $stmt = $this->executeQuery($sql);
            $tableExists = $stmt->rowCount() > 0;

            if (!$tableExists) {
                return ['exists' => false, 'count' => 0, 'populated' => false];
            }

            // Count records
            $sql = "SELECT COUNT(*) as count FROM account_types";
            $stmt = $this->executeQuery($sql);
            $result = $stmt->fetch();
            $count = $result['count'];

            return [
                'exists' => true,
                'count' => $count,
                'populated' => $count > 0
            ];
        } catch (Exception $e) {
            $this->logError('Failed to get table status: ' . $e->getMessage());
            return ['exists' => false, 'count' => 0, 'populated' => false, 'error' => $e->getMessage()];
        }
    }
}

// Initialize DAO and handle operations
$dao = new AccountTypesDAO();
$connectionInfo = $dao->getConnectionInfo();
$message = '';
$messageType = '';
$editingAccountType = null;

// Handle form submissions
if ($_POST) {
    if (isset($_POST['create_table']) && $dao->hasValidConnection()) {
        try {
            $dao->ensureTableExists();
            $message = "Account types table created successfully!";
            $messageType = 'success';
        } catch (Exception $e) {
            $message = "Error creating table: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if (isset($_POST['prepopulate_data']) && $dao->hasValidConnection()) {
        try {
            $result = $dao->prepopulateAccountTypes();
            $message = "Prepopulation completed! Inserted: {$result['inserted']}, Existing: {$result['existing']}";
            $messageType = 'success';
        } catch (Exception $e) {
            $message = "Error prepopulating data: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if (isset($_POST['add_account_type']) && $dao->hasValidConnection()) {
        try {
            $dao->addAccountType(
                $_POST['name'],
                $_POST['description'] ?? '',
                $_POST['currency'] ?? 'CAD',
                isset($_POST['is_registered'])
            );
            $message = "Account type '{$_POST['name']}' added successfully!";
            $messageType = 'success';
        } catch (Exception $e) {
            $message = "Error adding account type: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if (isset($_POST['update_account_type']) && $dao->hasValidConnection()) {
        try {
            $updated = $dao->updateAccountType(
                $_POST['id'],
                $_POST['name'],
                $_POST['description'] ?? '',
                $_POST['currency'] ?? 'CAD',
                isset($_POST['is_registered']),
                isset($_POST['is_active'])
            );
            if ($updated) {
                $message = "Account type updated successfully!";
                $messageType = 'success';
            } else {
                $message = "Account type not found or no changes made.";
                $messageType = 'warning';
            }
        } catch (Exception $e) {
            $message = "Error updating account type: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if (isset($_POST['delete_account_type']) && $dao->hasValidConnection()) {
        try {
            $deleted = $dao->deleteAccountType($_POST['account_type_id']);
            if ($deleted) {
                $message = "Account type deleted successfully!";
                $messageType = 'success';
            } else {
                $message = "Account type not found.";
                $messageType = 'warning';
            }
        } catch (Exception $e) {
            $message = "Error deleting account type: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Handle edit request
if (isset($_GET['edit']) && $dao->hasValidConnection()) {
    $editingAccountType = $dao->getAccountTypeById($_GET['edit']);
}

// Get current data
$tableStatus = $dao->getTableStatus();
$accountTypes = $dao->getAllAccountTypes();
$errors = $dao->getErrors();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Types Management</title>
    <?php require_once 'UiStyles.php'; ?>
    <?php UiStyles::render(); ?>
    <style>
        .setup-section { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #dee2e6; }
        .status-indicator { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-success { background: #d1edff; color: #0066cc; }
        .status-warning { background: #fff3cd; color: #856404; }
        .status-error { background: #f8d7da; color: #721c24; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; 
        }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input[type="checkbox"] { width: auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .currency-cad { color: #0066cc; }
        .currency-usd { color: #009900; }
        .registered-yes { background: #e8f5e8; color: #006600; padding: 2px 6px; border-radius: 3px; }
        .registered-no { background: #f8f8f8; color: #666; padding: 2px 6px; border-radius: 3px; }
        .action-buttons { display: flex; gap: 5px; }
        .btn-small { padding: 4px 8px; font-size: 12px; }
        .edit-form { background: #fff3cd; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #ffeaa7; }
    </style>
</head>
<body>
    <div class="container">
        <?php require_once 'QuickActions.php'; ?>
        <?php QuickActions::render(); ?>
        
        <h1>Account Types Management</h1>
        
        <!-- Connection Status -->
        <div class="card">
            <h3>Database Connection Status</h3>
            <?php if ($connectionInfo['connected']): ?>
                <p class="status-indicator status-success">✅ Connected</p>
                <p><strong>Server:</strong> <?= htmlspecialchars($connectionInfo['server_version'] ?? 'Unknown') ?></p>
                <p><strong>Configuration:</strong> <?= htmlspecialchars($connectionInfo['config_class']) ?></p>
            <?php else: ?>
                <p class="status-indicator status-error">❌ Not Connected</p>
                <p><strong>Configuration:</strong> <?= htmlspecialchars($connectionInfo['config_class']) ?></p>
                <?php if (!empty($errors)): ?>
                    <p><strong>Errors:</strong> <?= htmlspecialchars(implode(', ', $errors)) ?></p>
                <?php endif; ?>
                <p><a href="database.php" class="btn">Go to Database Management</a></p>
            <?php endif; ?>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="card <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($dao->hasValidConnection()): ?>
            <!-- Table Setup Section -->
            <div class="setup-section">
                <h3>Database Setup</h3>
                <div class="form-grid">
                    <div>
                        <h4>Table Status</h4>
                        <p><strong>Table Exists:</strong> 
                            <span class="status-indicator <?= $tableStatus['exists'] ? 'status-success' : 'status-error' ?>">
                                <?= $tableStatus['exists'] ? 'Yes' : 'No' ?>
                            </span>
                        </p>
                        <?php if ($tableStatus['exists']): ?>
                            <p><strong>Record Count:</strong> <?= $tableStatus['count'] ?></p>
                            <p><strong>Prepopulated:</strong> 
                                <span class="status-indicator <?= $tableStatus['populated'] ? 'status-success' : 'status-warning' ?>">
                                    <?= $tableStatus['populated'] ? 'Yes' : 'No' ?>
                                </span>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h4>Setup Actions</h4>
                        <?php if (!$tableStatus['exists']): ?>
                            <form method="post" style="margin-bottom: 10px;">
                                <button type="submit" name="create_table" class="btn">Create Table</button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($tableStatus['exists']): ?>
                            <form method="post">
                                <button type="submit" name="prepopulate_data" class="btn" 
                                        onclick="return confirm('This will add standard Canadian account types. Continue?')">
                                    Prepopulate Standard Account Types
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Edit Form (if editing) -->
            <?php if ($editingAccountType): ?>
                <div class="edit-form">
                    <h3>Edit Account Type</h3>
                    <form method="post">
                        <input type="hidden" name="id" value="<?= $editingAccountType['id'] ?>">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="edit_name">Name:</label>
                                <input type="text" id="edit_name" name="name" value="<?= htmlspecialchars($editingAccountType['name']) ?>" required maxlength="64">
                            </div>
                            <div class="form-group">
                                <label for="edit_currency">Currency:</label>
                                <select id="edit_currency" name="currency">
                                    <option value="CAD" <?= $editingAccountType['currency'] === 'CAD' ? 'selected' : '' ?>>CAD</option>
                                    <option value="USD" <?= $editingAccountType['currency'] === 'USD' ? 'selected' : '' ?>>USD</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_description">Description:</label>
                            <textarea id="edit_description" name="description" rows="3"><?= htmlspecialchars($editingAccountType['description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-grid">
                            <div class="checkbox-group">
                                <input type="checkbox" id="edit_is_registered" name="is_registered" <?= $editingAccountType['is_registered'] ? 'checked' : '' ?>>
                                <label for="edit_is_registered">Registered Account</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="edit_is_active" name="is_active" <?= $editingAccountType['is_active'] ? 'checked' : '' ?>>
                                <label for="edit_is_active">Active</label>
                            </div>
                        </div>
                        <div style="margin-top: 20px;">
                            <button type="submit" name="update_account_type" class="btn">Update Account Type</button>
                            <a href="admin_account_types.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Add New Account Type -->
            <div class="card">
                <h3>Add New Account Type</h3>
                <form method="post">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" id="name" name="name" required maxlength="64" placeholder="e.g., TFSA, RRSP, Cash">
                        </div>
                        <div class="form-group">
                            <label for="currency">Currency:</label>
                            <select id="currency" name="currency">
                                <option value="CAD">CAD</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="3" placeholder="Optional description of the account type"></textarea>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_registered" name="is_registered">
                        <label for="is_registered">Registered Account (tax-advantaged)</label>
                    </div>
                    <div style="margin-top: 15px;">
                        <button type="submit" name="add_account_type" class="btn">Add Account Type</button>
                    </div>
                </form>
            </div>

            <!-- Account Types List -->
            <div class="card">
                <h3>Existing Account Types (<?= count($accountTypes) ?>)</h3>
                <?php if (empty($accountTypes)): ?>
                    <p><em>No account types found. Create the table and prepopulate, or add them manually.</em></p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Currency</th>
                                <th>Registered</th>
                                <th>Status</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accountTypes as $accountType): ?>
                                <tr>
                                    <td><?= $accountType['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($accountType['name']) ?></strong></td>
                                    <td class="currency-<?= strtolower($accountType['currency']) ?>">
                                        <?= htmlspecialchars($accountType['currency']) ?>
                                    </td>
                                    <td>
                                        <span class="<?= $accountType['is_registered'] ? 'registered-yes' : 'registered-no' ?>">
                                            <?= $accountType['is_registered'] ? 'Yes' : 'No' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-indicator <?= $accountType['is_active'] ? 'status-success' : 'status-warning' ?>">
                                            <?= $accountType['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($accountType['description'] ?? '') ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="admin_account_types.php?edit=<?= $accountType['id'] ?>" class="btn btn-small">Edit</a>
                                            <form method="post" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this account type?')">
                                                <input type="hidden" name="account_type_id" value="<?= $accountType['id'] ?>">
                                                <button type="submit" name="delete_account_type" class="btn btn-danger btn-small">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <div class="card error">
                <h3>Database Functionality Unavailable</h3>
                <p>Account types management requires a working database connection.</p>
                <p>Please resolve the database connection issues to use this feature.</p>
                <p><a href="database.php" class="btn">Go to Database Management</a> for diagnosis and setup.</p>
            </div>
        <?php endif; ?>

        <!-- Back Navigation -->
        <div style="margin-top: 30px;">
            <a href="database.php" class="btn btn-secondary">← Back to Database Management</a>
        </div>
    </div>
</body>
</html>
