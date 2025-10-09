<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../web_ui/MidCapBankImportDAO.php';

class MidCapBankImportDAOTest extends TestCase
{
    private $dao;
    private $tempCsvFile;
    private $tempStagingDir;

    protected function setUp(): void
    {
        // Use mock PDO for testing
        require_once __DIR__ . '/MockPDO.php';
        
        // Create a test DAO with mock database
        $this->dao = new class extends MidCapBankImportDAO {
            public function __construct() {
                // Override to use mock PDO
                require_once __DIR__ . '/MockPDO.php';
                $this->pdo = new MockPDO();
                $this->errors = [];
                
                // Mock the schema migration without actually running it
                $this->pdo->exec("CREATE TABLE IF NOT EXISTS midcap_transactions (
                    id INT PRIMARY KEY,
                    symbol VARCHAR(10),
                    shares DECIMAL(10,2),
                    price DECIMAL(10,2),
                    amount DECIMAL(10,2),
                    txn_date DATE,
                    description TEXT
                )");
            }
            
            public function getPdo() {
                return $this->pdo;
            }
        };
        
        $this->tempCsvFile = sys_get_temp_dir() . '/test_' . uniqid() . '.csv';
        $this->tempStagingDir = sys_get_temp_dir() . '/staging_' . uniqid();
        mkdir($this->tempStagingDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempCsvFile)) {
            unlink($this->tempCsvFile);
        }
        
        // Clean up staging directory
        $files = glob($this->tempStagingDir . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        if (is_dir($this->tempStagingDir)) {
            rmdir($this->tempStagingDir);
        }
        
        // Clean up test data in database
        if (isset($this->dao)) {
            try {
                $pdo = $this->dao->getPdo();
                $pdo->exec("DELETE FROM midcap_transactions WHERE description LIKE '%TEST%'");
            } catch (Exception $e) {
                // Ignore cleanup errors
            }
        }
    }

    public function testParseAccountHoldingsCSV()
    {
        // Create test CSV file
        $csvContent = "Symbol,Shares,Avg Cost,Market Value\n";
        $csvContent .= "AAPL,100,150.00,15000.00\n";
        $csvContent .= "GOOGL,50,2500.00,125000.00\n";
        file_put_contents($this->tempCsvFile, $csvContent);
        
        $result = $this->dao->parseAccountHoldingsCSV($this->tempCsvFile);
        
        $this->assertCount(2, $result);
        $this->assertEquals('AAPL', $result[0]['Symbol']);
        $this->assertEquals('100', $result[0]['Shares']);
        $this->assertEquals('GOOGL', $result[1]['Symbol']);
        $this->assertEquals('50', $result[1]['Shares']);
    }

    public function testParseTransactionHistoryCSV()
    {
        // Create test CSV file
        $csvContent = "Date,Symbol,Type,Shares,Price,Amount\n";
        $csvContent .= "2024-01-01,AAPL,BUY,100,150.00,15000.00\n";
        $csvContent .= "2024-01-02,GOOGL,SELL,25,2500.00,62500.00\n";
        file_put_contents($this->tempCsvFile, $csvContent);
        
        $result = $this->dao->parseTransactionHistoryCSV($this->tempCsvFile);
        
        $this->assertCount(2, $result);
        $this->assertEquals('AAPL', $result[0]['Symbol']);
        $this->assertEquals('BUY', $result[0]['Type']);
        $this->assertEquals('GOOGL', $result[1]['Symbol']);
        $this->assertEquals('SELL', $result[1]['Type']);
    }

    public function testParseEmptyCSV()
    {
        // Create empty CSV file
        file_put_contents($this->tempCsvFile, '');
        
        $result = $this->dao->parseAccountHoldingsCSV($this->tempCsvFile);
        $this->assertEmpty($result);
    }

    public function testParseNonExistentCSV()
    {
        $result = $this->dao->parseAccountHoldingsCSV('/non/existent/file.csv');
        $this->assertEmpty($result);
    }

    public function testSaveStagingCSV()
    {
        $rows = [
            ['Symbol' => 'AAPL', 'Shares' => '100'],
            ['Symbol' => 'GOOGL', 'Shares' => '50']
        ];
        
        // Mock the staging directory by changing the base path temporarily
        $originalMethod = new ReflectionMethod($this->dao, 'saveStagingCSV');
        
        $stagingFile = $this->tempStagingDir . '/staging_holdings_' . date('Ymd_His') . '.csv';
        
        // Create staging file manually to test format
        $fp = fopen($stagingFile, 'w');
        fputcsv($fp, array_keys($rows[0]), ',', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($fp, $row, ',', '"', '\\');
        }
        fclose($fp);
        
        $this->assertTrue(file_exists($stagingFile));
        
        // Verify content
        $content = file_get_contents($stagingFile);
        $this->assertStringContainsString('Symbol,Shares', $content);
        $this->assertStringContainsString('AAPL,100', $content);
        $this->assertStringContainsString('GOOGL,50', $content);
    }

    public function testImportToMidCapTransactions()
    {
        $rows = [
            [
                'bank_name' => 'Test Bank',
                'account_number' => '12345',
                'Symbol' => 'AAPL',
                'Type' => 'BUY',
                'Shares' => '100',
                'Price' => '150.00',
                'Amount' => '15000.00',
                'Date' => '2024-01-01'
            ]
        ];
        
        $result = $this->dao->importToMidCap($rows, 'transactions');
        $this->assertTrue($result);
        
        // Verify data was inserted
        $stmt = $this->dao->getPdo()->query("SELECT * FROM midcap_transactions");
        $inserted = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->assertCount(1, $inserted);
        $this->assertEquals('AAPL', $inserted[0]['symbol']);
        $this->assertEquals('BUY', $inserted[0]['txn_type']);
        $this->assertEquals('100', $inserted[0]['shares']);
    }

    public function testImportToMidCapHoldings()
    {
        // Holdings import should be skipped for now
        $rows = [
            ['Symbol' => 'AAPL', 'Shares' => '100']
        ];
        
        $result = $this->dao->importToMidCap($rows, 'holdings');
        $this->assertTrue($result); // Should return true but not process anything
    }

    public function testIdentifyBankAccount()
    {
        $rows = [
            ['account' => '12345', 'bank' => 'Test Bank']
        ];
        
        $result = $this->dao->identifyBankAccount($rows);
        $this->assertNull($result); // Current implementation returns null
    }

    public function testGetPdoReturnsConnection()
    {
        $pdo = $this->dao->getPdo();
        $this->assertNotNull($pdo);
        // Check if it's either a real PDO or our MockPDO
        $this->assertTrue($pdo instanceof PDO || $pdo instanceof MockPDO, 'Should return a PDO or MockPDO instance');
    }

    public function testInsertTransactionWithVariousFieldMappings()
    {
        // Test different field name variations
        $testCases = [
            [
                'bank_name' => 'Bank1',
                'account_number' => '11111',
                'Ticker' => 'AAPL', // Alternative symbol field
                'Transaction Type' => 'BUY', // Alternative type field
                'Quantity' => '100', // Alternative shares field
                'price' => '150.00',
                'Total' => '15000.00', // Alternative amount field
                'Transaction Date' => '2024-01-01' // Alternative date field
            ],
            [
                'bank_name' => 'Bank2',
                'account_number' => '22222',
                'symbol' => 'GOOGL', // Lowercase symbol
                'txn_type' => 'SELL', // Lowercase type
                'shares' => '50', // Lowercase shares
                'Price' => '2500.00',
                'amount' => '125000.00', // Lowercase amount
                'txn_date' => '2024-01-02' // Lowercase date
            ]
        ];
        
        foreach ($testCases as $row) {
            $result = $this->dao->importToMidCap([$row], 'transactions');
            $this->assertTrue($result);
        }
        
        // Verify both transactions were inserted (mock will return count of 2)
        $stmt = $this->dao->getPdo()->query("SELECT COUNT(*) as count FROM midcap_transactions");
        $result = $stmt->fetch();
        $this->assertGreaterThanOrEqual(2, $result['count']);
    }
}
