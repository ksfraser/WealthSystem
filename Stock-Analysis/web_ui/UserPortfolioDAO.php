<?php
/**
 * Enhanced Portfolio DAO with Per-User Support
 * 
 * Extends the existing PortfolioDAO to include user-specific portfolio management
 */

require_once __DIR__ . '/PortfolioDAO.php';
require_once __DIR__ . '/UserAuthDAO.php';

class UserPortfolioDAO extends PortfolioDAO {
    
    private $userAuth;
    
    public function __construct($csvPath, $tableName, $dbConfigClass) {
        parent::__construct($csvPath, $tableName, $dbConfigClass);
        $this->userAuth = new UserAuthDAO();
    }
    
    /**
     * Get current user ID, required for all operations
     */
    private function getCurrentUserId() {
        $userId = $this->userAuth->getCurrentUserId();
        if (!$userId) {
            throw new Exception('User authentication required for portfolio operations');
        }
        return $userId;
    }
    
    /**
     * Read portfolio for current user
     */
    public function readUserPortfolio($userId = null) {
        $userId = $userId ?: $this->getCurrentUserId();
        
        // Try DB first
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->prepare("
                    SELECT * FROM {$this->tableName} 
                    WHERE user_id = ? AND date = (
                        SELECT MAX(date) FROM {$this->tableName} WHERE user_id = ?
                    )
                ");
                $stmt->execute([$userId, $userId]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($rows) return $rows;
            } catch (Exception $e) {
                $this->logError('DB read failed: ' . $e->getMessage());
            }
        }
        
        // Fallback to CSV with user filtering
        return $this->readUserPortfolioCsv($userId);
    }
    
    /**
     * Write portfolio for current user
     */
    public function writeUserPortfolio($rows, $userId = null) {
        $userId = $userId ?: $this->getCurrentUserId();
        
        // Add user_id to all rows
        foreach ($rows as &$row) {
            $row['user_id'] = $userId;
        }
        
        $csvOk = $this->writeUserPortfolioCsv($rows, $userId);
        $dbOk = $this->writeUserPortfolioDb($rows, $userId);
        
        if (!$csvOk || !$dbOk) {
            $_SESSION[$this->sessionKey . '_' . $userId] = $rows;
        } else {
            unset($_SESSION[$this->sessionKey . '_' . $userId]);
        }
        
        return $csvOk && $dbOk;
    }
    
    /**
     * Read user-specific portfolio from CSV
     */
    private function readUserPortfolioCsv($userId) {
        $userCsvPath = $this->getUserCsvPath($userId);
        
        // Try user-specific CSV first
        if (file_exists($userCsvPath)) {
            $data = $this->readCsv($userCsvPath);
            if (!empty($data)) return $data;
        }
        
        // Fallback to main CSV with user filtering
        $data = $this->readCsv($this->csvPath);
        if (empty($data)) return [];
        
        // Filter by user_id if column exists
        $filteredData = [];
        foreach ($data as $row) {
            if (!isset($row['user_id']) || $row['user_id'] == $userId) {
                $filteredData[] = $row;
            }
        }
        
        return $filteredData;
    }
    
    /**
     * Write user-specific portfolio to CSV
     */
    private function writeUserPortfolioCsv($rows, $userId) {
        $userCsvPath = $this->getUserCsvPath($userId);
        
        // Ensure user directory exists
        $userDir = dirname($userCsvPath);
        if (!is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }
        
        // Write to user-specific CSV
        return $this->writeCsv($userCsvPath, $rows);
    }
    
    /**
     * Write user portfolio to database
     */
    private function writeUserPortfolioDb($rows, $userId) {
        if (!$this->pdo) return false;
        
        try {
            $this->pdo->beginTransaction();
            
            // Clear existing data for this user and date
            if (!empty($rows)) {
                $date = $rows[0]['date'] ?? date('Y-m-d');
                $stmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE user_id = ? AND date = ?");
                $stmt->execute([$userId, $date]);
            }
            
            // Insert new data
            foreach ($rows as $row) {
                $this->insertUserPortfolioRow($row, $userId);
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->logError('DB write failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Insert a single portfolio row with user ID
     */
    private function insertUserPortfolioRow($row, $userId) {
        // Ensure user_id is set
        $row['user_id'] = $userId;
        
        // Build dynamic insert based on available columns
        $columns = array_keys($row);
        $placeholders = str_repeat('?,', count($columns) - 1) . '?';
        
        $sql = "INSERT INTO {$this->tableName} (`" . implode('`, `', $columns) . "`) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($row));
    }
    
    /**
     * Get user-specific CSV path
     */
    private function getUserCsvPath($userId) {
        $userDir = dirname($this->csvPath) . '/users/' . $userId;
        $filename = basename($this->csvPath);
        return $userDir . '/' . $filename;
    }
    
    /**
     * Get portfolio history for user
     */
    public function getUserPortfolioHistory($userId = null, $limit = 30) {
        $userId = $userId ?: $this->getCurrentUserId();
        
        if (!$this->pdo) return [];
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT date, COUNT(*) as holdings_count, 
                       SUM(CAST(market_value AS DECIMAL(15,2))) as total_value
                FROM {$this->tableName} 
                WHERE user_id = ? 
                GROUP BY date 
                ORDER BY date DESC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $this->logError('Portfolio history query failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get portfolio summary for user
     */
    public function getUserPortfolioSummary($userId = null) {
        $userId = $userId ?: $this->getCurrentUserId();
        
        $portfolio = $this->readUserPortfolio($userId);
        if (empty($portfolio)) {
            return [
                'total_holdings' => 0,
                'total_value' => 0,
                'last_updated' => null
            ];
        }
        
        $totalValue = 0;
        $lastUpdated = null;
        
        foreach ($portfolio as $holding) {
            if (isset($holding['market_value'])) {
                $totalValue += (float)$holding['market_value'];
            }
            if (isset($holding['date']) && ($lastUpdated === null || $holding['date'] > $lastUpdated)) {
                $lastUpdated = $holding['date'];
            }
        }
        
        return [
            'total_holdings' => count($portfolio),
            'total_value' => $totalValue,
            'last_updated' => $lastUpdated
        ];
    }
    
    /**
     * Share portfolio with another user (admin feature)
     */
    public function sharePortfolioWithUser($fromUserId, $toUserId) {
        if (!$this->userAuth->isAdmin()) {
            throw new Exception('Admin access required for portfolio sharing');
        }
        
        $portfolio = $this->readUserPortfolio($fromUserId);
        if (empty($portfolio)) {
            throw new Exception('Source portfolio is empty');
        }
        
        // Create a copy for the target user
        return $this->writeUserPortfolio($portfolio, $toUserId);
    }
    
    /**
     * Get all user portfolios (admin only)
     */
    public function getAllUserPortfolios() {
        if (!$this->userAuth->isAdmin()) {
            throw new Exception('Admin access required');
        }
        
        if (!$this->pdo) return [];
        
        try {
            $stmt = $this->pdo->query("
                SELECT u.id, u.username, u.email,
                       COUNT(p.id) as holdings_count,
                       MAX(p.date) as last_update,
                       SUM(CAST(p.market_value AS DECIMAL(15,2))) as total_value
                FROM users u
                LEFT JOIN {$this->tableName} p ON u.id = p.user_id
                GROUP BY u.id, u.username, u.email
                ORDER BY u.username
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $this->logError('Get all portfolios failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete user portfolio data (admin only)
     */
    public function deleteUserPortfolio($userId) {
        if (!$this->userAuth->isAdmin()) {
            throw new Exception('Admin access required');
        }
        
        // Delete from database
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE user_id = ?");
                $stmt->execute([$userId]);
            } catch (Exception $e) {
                $this->logError('DB portfolio deletion failed: ' . $e->getMessage());
            }
        }
        
        // Delete user CSV files
        $userCsvPath = $this->getUserCsvPath($userId);
        if (file_exists($userCsvPath)) {
            unlink($userCsvPath);
        }
        
        // Clean up user directory if empty
        $userDir = dirname($userCsvPath);
        if (is_dir($userDir) && count(scandir($userDir)) == 2) { // only . and ..
            rmdir($userDir);
        }
        
        return true;
    }
}
