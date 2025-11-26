<?php
/**
 * Unit tests for InvestGLDAO (double-entry investment GL logic)
 */
use PHPUnit\Framework\TestCase;
require_once dirname(__DIR__, 2) . '/web_ui/InvestGLDAO.php';

class InvestGLDAOTest extends TestCase {
    private $pdo;
    private $dao;


    // --- MySQL test DB config ---
    private $dbHost = 'localhost';
    private $dbName = 'test_db'; // CHANGE to your test DB
    private $dbUser = 'test_user'; // CHANGE to your test DB user
    private $dbPass = 'test_pass'; // CHANGE to your test DB password

    protected function setUp(): void {
        $dsn = "mysql:host={$this->dbHost};dbname={$this->dbName};charset=utf8mb4";
        $this->pdo = new PDO($dsn, $this->dbUser, $this->dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        // Ensure table exists (idempotent)
        $schema = "CREATE TABLE IF NOT EXISTS gl_trans_invest (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            gl_account VARCHAR(20) NOT NULL,
            stock_symbol VARCHAR(20) DEFAULT NULL,
            tran_date DATE NOT NULL,
            tran_type VARCHAR(20) NOT NULL,
            amount DECIMAL(18,4) NOT NULL,
            quantity DECIMAL(18,6) DEFAULT NULL,
            price DECIMAL(18,6) DEFAULT NULL,
            fees DECIMAL(18,4) DEFAULT 0,
            cost_basis DECIMAL(18,4) DEFAULT NULL,
            description TEXT,
            matched_tran_id INT DEFAULT NULL,
            is_opening_balance TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_symbol_date (user_id, stock_symbol, tran_date),
            INDEX idx_gl_account (gl_account),
            INDEX idx_matched_tran_id (matched_tran_id)
        );";
        $this->pdo->exec($schema);
        // Truncate table before each test
        $this->pdo->exec('TRUNCATE TABLE gl_trans_invest');
        $this->dao = new InvestGLDAO($this->pdo);
    }

    public function testAddOpeningBalance() {
        $id = $this->dao->addOpeningBalance(1, '1100', 'AAPL', '2025-10-08', 10, 150, 1500, 'Opening balance');
        $row = $this->pdo->query('SELECT * FROM gl_trans_invest WHERE id = ' . (int)$id)->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('OPENING_BAL', $row['tran_type']);
        $this->assertEquals(1, $row['is_opening_balance']);
        $this->assertEquals(10, $row['quantity']);
        $this->assertEquals(1500, $row['amount']);
    }

    public function testAddTransactionAndAdjustment() {
        $id1 = $this->dao->addOpeningBalance(1, '1100', 'AAPL', '2025-10-08', 10, 150, 1500, 'Opening balance');
        $id2 = $this->dao->addTransaction(1, '1100', 'AAPL', '2025-10-09', 'BUY', 1000, 7, 142.86, 0, 1000, 'Buy 7 shares');
        $adjId = $this->dao->addOpeningBalanceAdjustment(1, '1100', 'AAPL', '2025-10-09', -700, -7, 'Adj for matched buy');
        $row = $this->pdo->query('SELECT * FROM gl_trans_invest WHERE id = ' . (int)$adjId)->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('OPENING_BAL_ADJ', $row['tran_type']);
        $this->assertEquals(-7, $row['quantity']);
        $this->assertEquals(-700, $row['amount']);
    }

    public function testMatchTransactions() {
        $id1 = $this->dao->addOpeningBalance(1, '1100', 'AAPL', '2025-10-08', 10, 150, 1500, 'Opening balance');
        $id2 = $this->dao->addTransaction(1, '1100', 'AAPL', '2025-10-09', 'BUY', 1500, 10, 150, 0, 1500, 'Buy 10 shares');
        $this->dao->matchTransactions($id1, $id2);
        $row1 = $this->pdo->query('SELECT * FROM gl_trans_invest WHERE id = ' . (int)$id1)->fetch(PDO::FETCH_ASSOC);
        $row2 = $this->pdo->query('SELECT * FROM gl_trans_invest WHERE id = ' . (int)$id2)->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($id2, $row1['matched_tran_id']);
        $this->assertEquals($id1, $row2['matched_tran_id']);
    }

    public function testGetTransactions() {
        $this->dao->addOpeningBalance(1, '1100', 'AAPL', '2025-10-08', 10, 150, 1500, 'Opening balance');
        $this->dao->addTransaction(1, '1100', 'AAPL', '2025-10-09', 'BUY', 1000, 7, 142.86, 0, 1000, 'Buy 7 shares');
        $all = $this->dao->getTransactions(1, 'AAPL');
        $this->assertCount(2, $all);
        $unmatched = $this->dao->getTransactions(1, 'AAPL', 'unmatched');
        $this->assertCount(2, $unmatched);
    }
}
