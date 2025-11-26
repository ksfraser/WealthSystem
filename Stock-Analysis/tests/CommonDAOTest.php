<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../web_ui/CommonDAO.php';

// Concrete implementation for testing
class TestDAO extends CommonDAO
{
    public function __construct($dbConfigClass = null)
    {
        if ($dbConfigClass === null) {
            // Use mock PDO for testing
            require_once __DIR__ . '/MockPDO.php';
            $this->pdo = new MockPDO();
            $this->errors = [];
        } else {
            parent::__construct($dbConfigClass);
        }
    }
    
    // Expose protected methods for testing
    public function testReadCsv($csvPath) {
        return $this->readCsv($csvPath);
    }
    
    public function testWriteCsv($csvPath, $rows) {
        return $this->writeCsv($csvPath, $rows);
    }
    
    public function testLogError($msg) {
        $this->logError($msg);
    }
    
    // Expose getPdo for testing
    public function getPdo() {
        return $this->pdo;
    }
}

class CommonDAOTest extends TestCase
{
    private $dao;
    private $tempCsvFile;

    protected function setUp(): void
    {
        try {
            $this->dao = new TestDAO();
            $this->tempCsvFile = sys_get_temp_dir() . '/test_' . uniqid() . '.csv';
        } catch (Exception $e) {
            $this->markTestSkipped('Database connection not available: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempCsvFile)) {
            unlink($this->tempCsvFile);
        }
    }

    public function testGetPdoReturnsConnection()
    {
        $pdo = $this->dao->getPdo();
        $this->assertNotNull($pdo);
        // Check if it's either a real PDO or our MockPDO
        $this->assertTrue($pdo instanceof PDO || $pdo instanceof MockPDO, 'Should return a PDO or MockPDO instance');
    }

    public function testWriteCsvCreatesFile()
    {
        $data = [
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25]
        ];
        
        $result = $this->dao->testWriteCsv($this->tempCsvFile, $data);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($this->tempCsvFile));
    }

    public function testReadCsvReturnsData()
    {
        $data = [
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25]
        ];
        
        // Write CSV first
        $this->dao->testWriteCsv($this->tempCsvFile, $data);
        
        // Read it back
        $result = $this->dao->testReadCsv($this->tempCsvFile);
        
        $this->assertCount(2, $result);
        $this->assertEquals('John', $result[0]['name']);
        $this->assertEquals('30', $result[0]['age']); // CSV values are strings
        $this->assertEquals('Jane', $result[1]['name']);
        $this->assertEquals('25', $result[1]['age']);
    }

    public function testReadCsvNonExistentFile()
    {
        $result = $this->dao->testReadCsv('/non/existent/file.csv');
        $this->assertEmpty($result);
    }

    public function testWriteCsvEmptyData()
    {
        $result = $this->dao->testWriteCsv($this->tempCsvFile, []);
        $this->assertFalse($result);
    }

    public function testErrorLogging()
    {
        $this->dao->testLogError('Test error message');
        $errors = $this->dao->getErrors();
        
        $this->assertCount(1, $errors);
        $this->assertEquals('Test error message', $errors[0]);
    }

    public function testMultipleErrors()
    {
        $this->dao->testLogError('Error 1');
        $this->dao->testLogError('Error 2');
        $errors = $this->dao->getErrors();
        
        $this->assertCount(2, $errors);
        $this->assertEquals('Error 1', $errors[0]);
        $this->assertEquals('Error 2', $errors[1]);
    }

    public function testWriteAndReadCsvRoundTrip()
    {
        $originalData = [
            ['symbol' => 'AAPL', 'price' => '150.00', 'volume' => '1000000'],
            ['symbol' => 'GOOGL', 'price' => '2500.00', 'volume' => '500000'],
            ['symbol' => 'MSFT', 'price' => '300.00', 'volume' => '750000']
        ];
        
        // Write data
        $writeResult = $this->dao->testWriteCsv($this->tempCsvFile, $originalData);
        $this->assertTrue($writeResult);
        
        // Read data back
        $readData = $this->dao->testReadCsv($this->tempCsvFile);
        
        // Compare
        $this->assertCount(3, $readData);
        $this->assertEquals($originalData, $readData);
    }

    public function testCsvWithSpecialCharacters()
    {
        $data = [
            ['name' => 'John "The Great"', 'description' => 'Contains, commas and "quotes"'],
            ['name' => 'Jane\'s Portfolio', 'description' => 'Contains\nnewlines\nand\ttabs']
        ];
        
        $this->dao->testWriteCsv($this->tempCsvFile, $data);
        $result = $this->dao->testReadCsv($this->tempCsvFile);
        
        $this->assertEquals($data, $result);
    }
}
