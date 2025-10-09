<?php
/**
 * Enhanced Bank Accounts Management System - DAO Class
 *
 * Features:
 * - Table creation and setup workflow
 * - Prepopulation with sample Canadian bank accounts
 * - Full CRUD operations (Create, Read, Update, Delete)
 * - Integration with bank import functionality
 * - Enhanced error handling and validation
 * - Modern UI with consistent styling
 * - Role-Based Access Control (RBAC) for per-account permissions
 *
 * Architecture:
 * - Extends EnhancedCommonDAO for modern database operations
 * - Uses SimpleValidators for input validation
 * - Integrates with existing schema migration system
 * - Follows existing centralized database patterns
 *
 * Requirements Implemented:
 * - FR-1: User Authentication and Authorization
 * - FR-2: Bank Account Access Management
 * - FR-3: Permission Levels
 * - FR-4: User Interface
 * - FR-5: Data Integrity and Audit
 * - NFR-1: Performance
 * - NFR-2: Security
 * - NFR-3: Usability
 * - NFR-4: Maintainability
 * - NFR-5: Compatibility
 */

require_once __DIR__ . '/EnhancedCommonDAO.php';
require_once __DIR__ . '/EnhancedUserAuthDAO.php';

/**
 * Simple validators for input validation
 */
class SimpleValidators {
    public function validateRequired($value) {
        return !empty(trim($value));
    }

    public function validateLength($value, $min, $max) {
        $len = strlen(trim($value));
        return $len >= $min && $len <= $max;
    }
}

class BankAccountsDAO extends EnhancedCommonDAO {
    /**
     * Get all access records for a bank account
     *
     * @param int $bankAccountId The bank account ID
     * @return array Array of access records with user information
     *
     * Requirements Implemented:
     * - FR-2.5: Access grants must be auditable with timestamps and grantor information
     * - FR-4.1: Display current access permissions for each bank account
     * - FR-4.4: Display audit information (granted by, granted date) for each access record
     * - NFR-2.3: SQL injection prevention through prepared statements
     */
    public function getBankAccountAccess($bankAccountId) {
        $stmt = $this->pdo->prepare("SELECT baa.*, u.username, u.email FROM bank_account_access baa JOIN users u ON baa.user_id = u.id WHERE baa.bank_account_id = ? AND (baa.revoked_at IS NULL)");
        $stmt->execute([$bankAccountId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add or update access for a user to a bank account
     *
     * @param int $bankAccountId The bank account ID
     * @param int $userId The user ID to grant access to
     * @param string $permissionLevel Permission level (owner, read_write, read)
     * @param int $grantedBy User ID of the person granting access
     * @return bool Success status
     *
     * Requirements Implemented:
     * - FR-1.2: Users can only manage access for bank accounts they own
     * - FR-2.1: Owners can grant read, read_write, or owner access to other users
     * - FR-2.2: Owners can modify existing access permissions
     * - FR-2.4: System must prevent duplicate access grants for the same user-account combination
     * - FR-2.5: Access grants must be auditable with timestamps and grantor information
     * - FR-3.1: Owner permission allows full read/write access and access management
     * - FR-3.2: Read_Write permission allows viewing and modifying account data
     * - FR-3.3: Read permission allows viewing account data only
     * - FR-3.4: Permission levels must be enforced at the data access layer
     * - FR-5.1: All access changes must be logged with timestamps
     * - NFR-2.3: SQL injection prevention through prepared statements
     */
    public function setBankAccountAccess($bankAccountId, $userId, $permissionLevel, $grantedBy) {
        // Upsert logic
        $stmt = $this->pdo->prepare("INSERT INTO bank_account_access (bank_account_id, user_id, permission_level, granted_by, granted_at, revoked_at) VALUES (?, ?, ?, ?, NOW(), NULL) ON DUPLICATE KEY UPDATE permission_level=VALUES(permission_level), granted_by=VALUES(granted_by), granted_at=NOW(), revoked_at=NULL");
        return $stmt->execute([$bankAccountId, $userId, $permissionLevel, $grantedBy]);
    }

    /**
     * Revoke access for a user to a bank account
     *
     * @param int $bankAccountId The bank account ID
     * @param int $userId The user ID to revoke access from
     * @return bool Success status
     *
     * Requirements Implemented:
     * - FR-1.2: Users can only manage access for bank accounts they own
     * - FR-2.3: Owners can revoke access from any user except themselves
     * - FR-5.1: All access changes must be logged with timestamps
     * - FR-5.2: Soft delete access records (mark as revoked, don't remove)
     * - NFR-2.3: SQL injection prevention through prepared statements
     */
    public function revokeBankAccountAccess($bankAccountId, $userId) {
        $stmt = $this->pdo->prepare("UPDATE bank_account_access SET revoked_at=NOW() WHERE bank_account_id=? AND user_id=? AND revoked_at IS NULL");
        return $stmt->execute([$bankAccountId, $userId]);
    }

    public function __construct() {
        parent::__construct('LegacyDatabaseConfig');
    }

    /**
     * Check if bank_accounts table exists and is properly set up
     */
    public function checkTableStatus() {
        try {
            if (!$this->pdo) {
                return ['status' => 'no_connection', 'message' => 'Database connection not available'];
            }

            // Check if table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'bank_accounts'");
            if ($stmt->rowCount() === 0) {
                return ['status' => 'missing', 'message' => 'Bank accounts table does not exist'];
            }

            // Check if table has data
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM bank_accounts");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = (int)$result['count'];

            if ($count === 0) {
                return ['status' => 'empty', 'message' => 'Bank accounts table exists but is empty'];
            }

            return ['status' => 'ready', 'message' => "Bank accounts table ready with {$count} entries"];

        } catch (Exception $e) {
            $this->logError("Error checking table status: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error checking table status: ' . $e->getMessage()];
        }
    }

    /**
     * Create bank_accounts table using schema migration
     */
    public function createTable() {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }

            // Run schema migrations to ensure table is created
            require_once __DIR__ . '/SchemaMigrator.php';
            $schemaDir = __DIR__ . '/schema';
            $migrator = new SchemaMigrator($this->pdo, $schemaDir);
            $migrator->migrate();

            if ($this->logger) {
                $this->logger->info("Bank accounts table created successfully");
            }
            return true;

        } catch (Exception $e) {
            $this->logError("Error creating bank accounts table: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Prepopulate with sample Canadian bank accounts
     */
    public function prepopulateSampleAccounts() {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }

            $sampleAccounts = [
                ['bank' => 'Royal Bank of Canada', 'number' => 'SAMPLE-001', 'nickname' => 'RBC Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'TD Canada Trust', 'number' => 'SAMPLE-002', 'nickname' => 'TD Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'Bank of Nova Scotia', 'number' => 'SAMPLE-003', 'nickname' => 'Scotia Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'Bank of Montreal', 'number' => 'SAMPLE-004', 'nickname' => 'BMO Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'Canadian Imperial Bank of Commerce', 'number' => 'SAMPLE-005', 'nickname' => 'CIBC Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'National Bank of Canada', 'number' => 'SAMPLE-006', 'nickname' => 'NBC Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'Desjardins Group', 'number' => 'SAMPLE-007', 'nickname' => 'Desjardins Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'HSBC Bank Canada', 'number' => 'SAMPLE-008', 'nickname' => 'HSBC Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'Laurentian Bank', 'number' => 'SAMPLE-009', 'nickname' => 'Laurentian Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'Canadian Western Bank', 'number' => 'SAMPLE-010', 'nickname' => 'CWB Sample Account', 'type' => 'Investment Account'],
                ['bank' => 'Other', 'number' => 'SAMPLE-999', 'nickname' => 'Other Bank Sample', 'type' => 'Investment Account']
            ];

            $inserted = 0;
            foreach ($sampleAccounts as $account) {
                $stmt = $this->pdo->prepare('INSERT IGNORE INTO bank_accounts (bank_name, account_number, account_nickname, account_type, currency) VALUES (?, ?, ?, ?, ?)');
                if ($stmt->execute([$account['bank'], $account['number'], $account['nickname'], $account['type'], 'CAD'])) {
                    if ($stmt->rowCount() > 0) {
                        $inserted++;
                    }
                }
            }

            if ($this->logger) {
                $this->logger->info("Prepopulated {$inserted} sample bank accounts");
            }
            return $inserted;

        } catch (Exception $e) {
            $this->logError("Error prepopulating bank accounts: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all bank accounts with optional search and pagination
     */
    public function getAllBankAccounts($search = '', $limit = 100, $offset = 0) {
        try {
            if (!$this->pdo) {
                return [];
            }

            $sql = "SELECT * FROM bank_accounts";
            $params = [];

            if (!empty($search)) {
                $sql .= " WHERE bank_name LIKE ? OR account_number LIKE ? OR account_nickname LIKE ?";
                $searchTerm = "%{$search}%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }

            $sql .= " ORDER BY bank_name, account_number LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $this->logError("Error getting bank accounts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total count of bank accounts (for pagination)
     */
    public function getBankAccountsCount($search = '') {
        try {
            if (!$this->pdo) {
                return 0;
            }

            $sql = "SELECT COUNT(*) as count FROM bank_accounts";
            $params = [];

            if (!empty($search)) {
                $sql .= " WHERE bank_name LIKE ? OR account_number LIKE ? OR account_nickname LIKE ?";
                $searchTerm = "%{$search}%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)$result['count'];

        } catch (Exception $e) {
            $this->logError("Error counting bank accounts: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get bank account by ID
     */
    public function getBankAccountById($id) {
        try {
            if (!$this->pdo) {
                return null;
            }

            $stmt = $this->pdo->prepare("SELECT * FROM bank_accounts WHERE id = ?");
            $stmt->execute([$id]);

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $this->logError("Error getting bank account by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create new bank account
     */
    public function createBankAccount($bankName, $accountNumber, $nickname = '', $accountType = '', $currency = 'CAD', $isActive = true) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }

            // Validate input
            $validator = new SimpleValidators();
            if (!$validator->validateRequired($bankName)) {
                throw new Exception('Bank name is required');
            }

            if (!$validator->validateRequired($accountNumber)) {
                throw new Exception('Account number is required');
            }

            if (!$validator->validateLength($bankName, 1, 128)) {
                throw new Exception('Bank name must be between 1 and 128 characters');
            }

            if (!$validator->validateLength($accountNumber, 1, 64)) {
                throw new Exception('Account number must be between 1 and 64 characters');
            }

            $stmt = $this->pdo->prepare('INSERT INTO bank_accounts (bank_name, account_number, account_nickname, account_type, currency, is_active) VALUES (?, ?, ?, ?, ?, ?)');
            if ($stmt->execute([$bankName, $accountNumber, $nickname, $accountType, $currency, $isActive ? 1 : 0])) {
                $id = $this->pdo->lastInsertId();
                if ($this->logger) {
                    $this->logger->info("Created bank account: {$bankName} - {$accountNumber} (ID: {$id})");
                }
                return $id;
            }

            throw new Exception('Failed to create bank account');

        } catch (Exception $e) {
            $this->logError("Error creating bank account: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update bank account
     */
    public function updateBankAccount($id, $bankName, $accountNumber, $nickname = '', $accountType = '', $currency = 'CAD', $isActive = true) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }

            // Validate input
            $validator = new SimpleValidators();
            if (!$validator->validateRequired($bankName)) {
                throw new Exception('Bank name is required');
            }

            if (!$validator->validateRequired($accountNumber)) {
                throw new Exception('Account number is required');
            }

            if (!$validator->validateLength($bankName, 1, 128)) {
                throw new Exception('Bank name must be between 1 and 128 characters');
            }

            if (!$validator->validateLength($accountNumber, 1, 64)) {
                throw new Exception('Account number must be between 1 and 64 characters');
            }

            $stmt = $this->pdo->prepare('UPDATE bank_accounts SET bank_name = ?, account_number = ?, account_nickname = ?, account_type = ?, currency = ?, is_active = ? WHERE id = ?');
            if ($stmt->execute([$bankName, $accountNumber, $nickname, $accountType, $currency, $isActive ? 1 : 0, $id])) {
                if ($this->logger) {
                    $this->logger->info("Updated bank account ID {$id}: {$bankName} - {$accountNumber}");
                }
                return $stmt->rowCount() > 0;
            }

            throw new Exception('Failed to update bank account');

        } catch (Exception $e) {
            $this->logError("Error updating bank account: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete bank account (with safety checks)
     */
    public function deleteBankAccount($id) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }

            // Check if bank account is in use in transactions
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM midcap_transactions mt JOIN bank_accounts ba ON mt.bank_name = ba.bank_name AND mt.account_number = ba.account_number WHERE ba.id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ((int)$result['count'] > 0) {
                throw new Exception('Cannot delete bank account: it is referenced by existing transactions');
            }

            $stmt = $this->pdo->prepare('DELETE FROM bank_accounts WHERE id = ?');
            if ($stmt->execute([$id])) {
                if ($this->logger) {
                    $this->logger->info("Deleted bank account ID: {$id}");
                }
                return $stmt->rowCount() > 0;
            }

            throw new Exception('Failed to delete bank account');

        } catch (Exception $e) {
            $this->logError("Error deleting bank account: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get unique bank names for dropdown
     */
    public function getUniqueBankNames() {
        try {
            if (!$this->pdo) {
                return [];
            }

            $stmt = $this->pdo->query("SELECT DISTINCT bank_name FROM bank_accounts ORDER BY bank_name");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (Exception $e) {
            $this->logError("Error getting unique bank names: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find or suggest bank account from transaction data
     */
    public function findOrSuggestBankAccount($bankName, $accountNumber) {
        try {
            if (!$this->pdo) {
                return null;
            }

            // Exact match
            $stmt = $this->pdo->prepare("SELECT * FROM bank_accounts WHERE bank_name = ? AND account_number = ?");
            $stmt->execute([$bankName, $accountNumber]);
            $exact = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($exact) {
                return ['type' => 'exact', 'account' => $exact];
            }

            // Partial bank name match
            $stmt = $this->pdo->prepare("SELECT * FROM bank_accounts WHERE bank_name LIKE ? ORDER BY bank_name");
            $stmt->execute(["%{$bankName}%"]);
            $partial = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($partial)) {
                return ['type' => 'similar', 'accounts' => $partial];
            }

            return ['type' => 'new', 'suggested' => ['bank_name' => $bankName, 'account_number' => $accountNumber]];

        } catch (Exception $e) {
            $this->logError("Error finding bank account: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all bank accounts a user has access to
     *
     * @param int $userId The user ID
     * @return array Array of bank account data with access information
     */
    public function getUserAccessibleBankAccounts($userId) {
        try {
            if (!$this->pdo) {
                return [];
            }

            $stmt = $this->pdo->prepare("
                SELECT
                    ba.*,
                    baa.permission_level,
                    baa.granted_at,
                    grantor.username as granted_by_username
                FROM bank_accounts ba
                JOIN bank_account_access baa ON ba.id = baa.bank_account_id
                LEFT JOIN users grantor ON baa.granted_by = grantor.id
                WHERE baa.user_id = ? AND baa.revoked_at IS NULL
                ORDER BY ba.bank_name, ba.account_number
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $this->logError("Error getting user accessible bank accounts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create bank account if it doesn't exist and grant user access
     *
     * @param string $bankName Bank name
     * @param string $accountNumber Account number
     * @param int $userId User ID to grant owner access to
     * @param string $nickname Optional account nickname
     * @param string $accountType Optional account type
     * @param string $currency Currency (default CAD)
     * @return int Bank account ID
     */
    public function createBankAccountIfNotExists($bankName, $accountNumber, $userId, $nickname = '', $accountType = 'Investment Account', $currency = 'CAD') {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }

            // Check if account already exists
            $stmt = $this->pdo->prepare("SELECT id FROM bank_accounts WHERE bank_name = ? AND account_number = ?");
            $stmt->execute([$bankName, $accountNumber]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $bankAccountId = $existing['id'];

                // Check if user already has access
                $stmt = $this->pdo->prepare("SELECT id FROM bank_account_access WHERE bank_account_id = ? AND user_id = ? AND revoked_at IS NULL");
                $stmt->execute([$bankAccountId, $userId]);
                $access = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$access) {
                    // Grant owner access
                    $this->setBankAccountAccess($bankAccountId, $userId, 'owner', $userId);
                }

                return $bankAccountId;
            }

            // Create new bank account
            $bankAccountId = $this->createBankAccount($bankName, $accountNumber, $nickname, $accountType, $currency, true);

            // Grant owner access to the creator
            $this->setBankAccountAccess($bankAccountId, $userId, 'owner', $userId);

            return $bankAccountId;

        } catch (Exception $e) {
            $this->logError("Error creating bank account if not exists: " . $e->getMessage());
            throw $e;
        }
    }
}