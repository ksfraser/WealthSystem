<?php

namespace App\Repositories;

use App\Repositories\Interfaces\BankAccountRepositoryInterface;
use App\Core\Interfaces\ModelInterface;
use App\Models\BankAccount;

/**
 * BankAccount Repository Implementation
 * 
 * Bridges between new MVC architecture and existing bank account management.
 * Works with existing admin_bank_accounts.php system.
 */
class BankAccountRepository implements BankAccountRepositoryInterface
{
    private ?\PDO $pdo = null;
    
    public function __construct()
    {
        try {
            // Use existing database configuration
            require_once __DIR__ . '/../../web_ui/DbConfigClasses.php';
            $this->pdo = \LegacyDatabaseConfig::createConnection();
        } catch (\Exception $e) {
            // Will work with limited functionality
        }
    }
    
    /**
     * Find record by ID
     */
    public function findById(int $id): ?ModelInterface
    {
        if (!$this->pdo) {
            return null;
        }
        
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM bank_accounts WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $data ? new BankAccount($data) : null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Find records by criteria
     */
    public function findBy(array $criteria): array
    {
        if (!$this->pdo) {
            return [];
        }
        
        $accounts = [];
        
        try {
            $conditions = [];
            $params = [];
            
            foreach ($criteria as $field => $value) {
                $conditions[] = "{$field} = ?";
                $params[] = $value;
            }
            
            $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
            $sql = "SELECT * FROM bank_accounts {$whereClause} ORDER BY bank_name, account_number";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($results as $data) {
                $accounts[] = new BankAccount($data);
            }
        } catch (\Exception $e) {
            // Log error but return empty array
        }
        
        return $accounts;
    }
    
    /**
     * Find one record by criteria
     */
    public function findOneBy(array $criteria): ?ModelInterface
    {
        $results = $this->findBy($criteria);
        return $results[0] ?? null;
    }
    
    /**
     * Find all records
     */
    public function findAll(): array
    {
        return $this->findBy([]);
    }
    
    /**
     * Create new record
     */
    public function create(array $data): ModelInterface
    {
        if (!$this->pdo) {
            throw new \Exception('Database connection not available');
        }
        
        $bankAccount = new BankAccount($data);
        
        if (!$bankAccount->isValid()) {
            throw new \Exception('Invalid bank account data: ' . implode(', ', $bankAccount->getErrors()));
        }
        
        try {
            $fields = ['bank_name', 'account_number', 'account_type', 'routing_number', 
                      'branch_code', 'currency', 'is_active', 'notes'];
            
            $placeholders = str_repeat('?,', count($fields) - 1) . '?';
            $sql = "INSERT INTO bank_accounts (" . implode(',', $fields) . ") VALUES ({$placeholders})";
            
            $values = [];
            foreach ($fields as $field) {
                $values[] = $bankAccount->{$field};
            }
            
            $stmt = $this->pdo->prepare($sql);
            
            if ($stmt->execute($values)) {
                $id = $this->pdo->lastInsertId();
                $result = $this->findById((int) $id);
                if ($result) {
                    return $result;
                }
            }
            
            throw new \Exception("Failed to create bank account");
        } catch (\Exception $e) {
            throw new \Exception("Failed to create bank account: " . $e->getMessage());
        }
    }
    
    /**
     * Update record by ID
     */
    public function update(int $id, array $data): bool
    {
        if (!$this->pdo) {
            return false;
        }
        
        try {
            $fields = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                if ($field !== 'id') {
                    $fields[] = "{$field} = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            // Add updated_at timestamp
            $fields[] = "updated_at = ?";
            $params[] = date('Y-m-d H:i:s');
            
            $params[] = $id;
            $sql = "UPDATE bank_accounts SET " . implode(',', $fields) . " WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Delete record by ID
     */
    public function delete(int $id): bool
    {
        if (!$this->pdo) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("DELETE FROM bank_accounts WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Count records
     */
    public function count(array $criteria = []): int
    {
        if (!$this->pdo) {
            return 0;
        }
        
        try {
            $conditions = [];
            $params = [];
            
            foreach ($criteria as $field => $value) {
                $conditions[] = "{$field} = ?";
                $params[] = $value;
            }
            
            $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
            $sql = "SELECT COUNT(*) FROM bank_accounts {$whereClause}";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return (int) $stmt->fetchColumn();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    // BankAccountRepositoryInterface specific methods
    
    /**
     * Get user accessible accounts (for now, return all active accounts)
     */
    public function getUserAccessibleAccounts(int $userId): array
    {
        // For now, return all active accounts
        // TODO: Implement user-specific account access when needed
        return $this->findBy(['is_active' => 1]);
    }
    
    /**
     * Find account by ID with user access check
     */
    public function findByIdWithUserAccess(int $id, int $userId): ?object
    {
        $account = $this->findById($id);
        
        if (!$account) {
            return null;
        }
        
        // For now, allow access to all accounts
        // TODO: Implement user access control when needed
        return $account->toUserArray();
    }
    
    /**
     * Create account if it doesn't exist
     */
    public function createIfNotExists(string $bankName, string $accountNumber): ?object
    {
        // Check if account already exists
        $existing = $this->findOneBy([
            'bank_name' => $bankName,
            'account_number' => $accountNumber
        ]);
        
        if ($existing) {
            return $existing->toArray();
        }
        
        // Create new account
        try {
            $newAccount = $this->create([
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'account_type' => 'Checking', // Default type
                'currency' => 'CAD',
                'is_active' => true,
                'notes' => 'Auto-created from import'
            ]);
            
            return $newAccount->toArray();
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Find account by bank and account number
     */
    public function findByBankAndAccount(string $bankName, string $accountNumber): ?object
    {
        $account = $this->findOneBy([
            'bank_name' => $bankName,
            'account_number' => $accountNumber
        ]);
        
        return $account ? $account->toArray() : null;
    }
    
    /**
     * Search accounts by bank name pattern
     */
    public function searchByBankName(string $bankNamePattern): array
    {
        if (!$this->pdo) {
            return [];
        }
        
        $accounts = [];
        
        try {
            $sql = "SELECT * FROM bank_accounts WHERE bank_name LIKE ? ORDER BY bank_name";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(["%{$bankNamePattern}%"]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($results as $data) {
                $accounts[] = new BankAccount($data);
            }
        } catch (\Exception $e) {
            // Log error but return empty array
        }
        
        return $accounts;
    }
    
    /**
     * Get unique bank names
     */
    public function getUniqueBankNames(): array
    {
        if (!$this->pdo) {
            return [];
        }
        
        try {
            $stmt = $this->pdo->query("SELECT DISTINCT bank_name FROM bank_accounts ORDER BY bank_name");
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            return [];
        }
    }
}