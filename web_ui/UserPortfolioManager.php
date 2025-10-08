<?php
/**
 * User-Aware Portfolio Manager
 * Manages portfolios with user authentication and per-user data separation
 */

require_once __DIR__ . '/UserAuthDAO.php';
require_once __DIR__ . '/CommonDAO.php';
require_once __DIR__ . '/Logger.php';

require_once __DIR__ . '/CsvParser.php';
use App\CsvParser;

/**
 * UserPortfolioManager
 * Manages user portfolios with DB and CSV fallback, using robust CSV parser.
 *
 * @startuml UserPortfolioManager_Class_Diagram
 * class UserPortfolioManager {
 *   + readUserPortfolio($userId = null)
 *   + writeUserPortfolio($rows, $userId = null)
 *   + deleteUserPortfolio($userId)
 *   # readUserPortfolioCsv($userId)
 *   # writeUserPortfolioCsv($rows, $userId)
 *   # getUserTableName($userId)
 *   # getUserCsvPath($userId)
 * }
 * UserPortfolioManager --> CsvParser
 * UserPortfolioManager --> FileLogger
 * @enduml
 */
class UserPortfolioManager extends CommonDAO {
    /** @var UserAuthDAO */
    private $userAuth;
    /** @var string */
    private $baseTableName;
    /** @var string */
    private $baseCsvPath;
    /** @var CsvParser */
    private $csvParser;

    public function __construct($baseCsvPath = '', $baseTableName = 'portfolios') {
        parent::__construct('LegacyDatabaseConfig');
        $this->userAuth = new UserAuthDAO();
        $this->baseTableName = $baseTableName;
        $this->baseCsvPath = $baseCsvPath ?: __DIR__ . '/../Scripts and CSV Files/chatgpt_portfolio_update.csv';
        $logger = new FileLogger(__DIR__ . '/../logs/user_portfolio_manager.log', 'info');
        $this->csvParser = new CsvParser($logger);
        // Use centralized SessionManager instead of direct session_start()
        require_once __DIR__ . '/SessionManager.php';
        SessionManager::getInstance();
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
     * Ensure user portfolio table exists
     */
    public function ensureUserPortfolioTable($userId = null) {
        $userId = $userId ?: $this->getCurrentUserId();
        
        if (!$this->pdo) return false;
        
        try {
            $tableName = $this->getUserTableName($userId);
            
            // Create user-specific portfolio table
            $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                symbol VARCHAR(10) NOT NULL,
                shares DECIMAL(15,4) DEFAULT 0,
                market_value DECIMAL(15,2) DEFAULT 0,
                book_cost DECIMAL(15,2) DEFAULT 0,
                gain_loss DECIMAL(15,2) DEFAULT 0,
                gain_loss_percent DECIMAL(8,4) DEFAULT 0,
                current_price DECIMAL(10,4) DEFAULT 0,
                date DATE NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_date (user_id, date),
                INDEX idx_symbol (symbol),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            
            $this->pdo->exec($sql);
            return true;
            
        } catch (Exception $e) {
            $this->logError("Failed to create user portfolio table: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user-specific table name
     */
    private function getUserTableName($userId) {
        return $this->baseTableName . '_user_' . $userId;
    }
    
    /**
     * Get user-specific CSV path
     */
    private function getUserCsvPath($userId) {
        $dir = dirname($this->baseCsvPath) . '/users/' . $userId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir . '/' . basename($this->baseCsvPath);
    }
    
    /**
     * Read user portfolio
     */
    public function readUserPortfolio($userId = null) {
        $userId = $userId ?: $this->getCurrentUserId();
        $tableName = $this->getUserTableName($userId);
        
        // Try database first
        if ($this->pdo) {
            try {
                $this->ensureUserPortfolioTable($userId);
                
                $stmt = $this->pdo->prepare("
                    SELECT * FROM `{$tableName}` 
                    WHERE date = (SELECT MAX(date) FROM `{$tableName}`)
                    ORDER BY symbol
                ");
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($rows) return $rows;
            } catch (Exception $e) {
                $this->logError('Database read failed: ' . $e->getMessage());
            }
        }
        
        // Fallback to CSV
        return $this->readUserPortfolioCsv($userId);
    }
    
    /**
     * Write user portfolio
     */
    public function writeUserPortfolio($rows, $userId = null) {
        $userId = $userId ?: $this->getCurrentUserId();
        
        // Add user_id to all rows
        foreach ($rows as &$row) {
            $row['user_id'] = $userId;
            if (!isset($row['date'])) {
                $row['date'] = date('Y-m-d');
            }
        }
        
        $csvSuccess = $this->writeUserPortfolioCsv($rows, $userId);
        $dbSuccess = $this->writeUserPortfolioDb($rows, $userId);
        
        return $csvSuccess && $dbSuccess;
    }
    
    /**
     * Read user portfolio from CSV
     */
    /**
     * Read user portfolio from CSV using CsvParser
     * @param int $userId
     * @return array
     */
    public function readUserPortfolioCsv($userId) {
        $csvPath = $this->getUserCsvPath($userId);
        if (!file_exists($csvPath)) {
            return [];
        }
        return $this->csvParser->parse($csvPath);
    }
    
    /**
     * Write user portfolio to CSV
     */
    /**
     * Write user portfolio to CSV using CsvParser
     * @param array $rows
     * @param int $userId
     * @return bool
     */
    public function writeUserPortfolioCsv($rows, $userId) {
        if (empty($rows)) return true;
        $csvPath = $this->getUserCsvPath($userId);
        return $this->csvParser->write($csvPath, $rows);
    }
    
    /**
     * Write user portfolio to database
     */
    private function writeUserPortfolioDb($rows, $userId) {
        if (!$this->pdo || empty($rows)) return false;
        
        try {
            $this->ensureUserPortfolioTable($userId);
            $tableName = $this->getUserTableName($userId);
            
            $this->pdo->beginTransaction();
            
            // Clear existing data for today
            $date = $rows[0]['date'] ?? date('Y-m-d');
            $stmt = $this->pdo->prepare("DELETE FROM `{$tableName}` WHERE user_id = ? AND date = ?");
            $stmt->execute([$userId, $date]);
            
            // Insert new data
            foreach ($rows as $row) {
                $this->insertPortfolioRow($tableName, $row);
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->logError('Database write failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Insert a single portfolio row
     */
    private function insertPortfolioRow($tableName, $row) {
        $columns = array_keys($row);
        $placeholders = str_repeat('?,', count($columns) - 1) . '?';
        
        $sql = "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($row));
    }
    
    /**
     * Get portfolio history for user
     */
    public function getUserPortfolioHistory($userId = null, $limit = 30) {
        $userId = $userId ?: $this->getCurrentUserId();
        
        if (!$this->pdo) return [];
        
        try {
            $tableName = $this->getUserTableName($userId);
            $this->ensureUserPortfolioTable($userId);
            
            $stmt = $this->pdo->prepare("
                SELECT date, 
                       COUNT(*) as holdings_count, 
                       SUM(market_value) as total_value,
                       SUM(gain_loss) as total_gain_loss
                FROM `{$tableName}` 
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
                'total_gain_loss' => 0,
                'last_updated' => null
            ];
        }
        
        $totalValue = 0;
        $totalGainLoss = 0;
        $lastUpdated = null;
        
        foreach ($portfolio as $holding) {
            $totalValue += (float)($holding['market_value'] ?? 0);
            $totalGainLoss += (float)($holding['gain_loss'] ?? 0);
            
            if (isset($holding['date']) && ($lastUpdated === null || $holding['date'] > $lastUpdated)) {
                $lastUpdated = $holding['date'];
            }
        }
        
        return [
            'total_holdings' => count($portfolio),
            'total_value' => $totalValue,
            'total_gain_loss' => $totalGainLoss,
            'gain_loss_percent' => $totalValue > 0 ? ($totalGainLoss / ($totalValue - $totalGainLoss)) * 100 : 0,
            'last_updated' => $lastUpdated
        ];
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
            // Get all users and their portfolio summary
            $users = $this->userAuth->getAllUsers();
            $portfolioSummaries = [];
            
            foreach ($users as $user) {
                try {
                    $summary = $this->getUserPortfolioSummary($user['id']);
                    $portfolioSummaries[] = array_merge($user, $summary);
                } catch (Exception $e) {
                    // User might not have a portfolio yet
                    $portfolioSummaries[] = array_merge($user, [
                        'total_holdings' => 0,
                        'total_value' => 0,
                        'total_gain_loss' => 0,
                        'last_updated' => null
                    ]);
                }
            }
            
            return $portfolioSummaries;
            
        } catch (Exception $e) {
            $this->logError('Get all portfolios failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Import portfolio from CSV file upload
     */
    public function importPortfolioFromCsv($uploadedFile, $userId = null) {
        $userId = $userId ?: $this->getCurrentUserId();
        
        if (!file_exists($uploadedFile)) {
            throw new Exception('Uploaded file not found');
        }
        
        $data = [];
        if (($handle = fopen($uploadedFile, 'r')) !== false) {
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) === count($headers)) {
                    $rowData = array_combine($headers, $row);
                    $rowData['user_id'] = $userId;
                    if (!isset($rowData['date'])) {
                        $rowData['date'] = date('Y-m-d');
                    }
                    $data[] = $rowData;
                }
            }
            fclose($handle);
        }
        
        if (empty($data)) {
            throw new Exception('No valid data found in uploaded file');
        }
        
        return $this->writeUserPortfolio($data, $userId);
    }
    
    /**
     * Delete user portfolio (admin only)
     */
    public function deleteUserPortfolio($userId) {
        if (!$this->userAuth->isAdmin()) {
            throw new Exception('Admin access required');
        }
        
        // Delete database table
        if ($this->pdo) {
            try {
                $tableName = $this->getUserTableName($userId);
                $this->pdo->exec("DROP TABLE IF EXISTS `{$tableName}`");
            } catch (Exception $e) {
                $this->logError('Failed to drop user portfolio table: ' . $e->getMessage());
            }
        }
        
        // Delete CSV files
        $csvPath = $this->getUserCsvPath($userId);
        if (file_exists($csvPath)) {
            unlink($csvPath);
        }
        
        // Clean up user directory if empty
        $userDir = dirname($csvPath);
        if (is_dir($userDir) && count(scandir($userDir)) == 2) { // only . and ..
            rmdir($userDir);
        }
        
        return true;
    }
}
