<?php

require_once __DIR__ . '/../../web_ui/MidCapBankImportDAO.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the streamlined MidCapBankImportDAO
 * 
 * Tests the complete data flow from parsed transactions to database insertion.
 * Verifies that the DAO properly handles standardized transaction data.
 */
class MidCapBankImportDAOTest extends TestCase {
    
    private $dao;
    private $mockPDO;
    private $mockStmt;
    
    protected function setUp(): void {
        // Create mock PDO and statement
        $this->mockPDO = $this->createMock(PDO::class);
        $this->mockStmt = $this->createMock(PDOStatement::class);
        
        // Mock the PDO methods that SchemaMigrator will call
        $this->mockStmt->method('fetchAll')->willReturn([]);
        $this->mockPDO->method('query')->willReturn($this->mockStmt);
        $this->mockPDO->method('prepare')->willReturn($this->mockStmt);
        $this->mockStmt->method('execute')->willReturn(true);
        
        // Create DAO with mocked PDO
        $this->dao = new MidCapBankImportDAO($this->mockPDO);
    }
    
    /**
     * Test CSV staging functionality
     */
    public function testSaveStagingCSV(): void {
        $testRows = [
            [
                'date' => '2025-01-01',
                'description' => 'Test Transaction',
                'amount' => 100.50,
                'symbol' => 'TEST',
                'bank_name' => 'Test Bank',
                'account_number' => '12345'
            ]
        ];
        
        $stagingFile = $this->dao->saveStagingCSV($testRows, 'test_transactions');
        
        // Verify file was created
        $this->assertFileExists($stagingFile);
        $this->assertStringContainsString('staging_test_transactions_', $stagingFile);
        
        // Verify file content
        $content = file_get_contents($stagingFile);
        $this->assertStringContainsString('Test Transaction', $content);
        $this->assertStringContainsString('100.5', $content);
        
        // Clean up
        unlink($stagingFile);
    }
    
    /**
     * Test aliased value helper method
     */
    public function testGetAliasedValue(): void {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->dao);
        $method = $reflection->getMethod('getAliasedValue');
        $method->setAccessible(true);
        
        $testRow = [
            'symbol' => 'AAPL',
            'stock_symbol' => 'MSFT',
            'price' => 150.25
        ];
        
        // Test first alias match
        $result = $method->invoke($this->dao, $testRow, ['symbol', 'ticker'], 'DEFAULT');
        $this->assertEquals('AAPL', $result);
        
        // Test fallback to second alias
        $result = $method->invoke($this->dao, $testRow, ['ticker', 'stock_symbol'], 'DEFAULT');
        $this->assertEquals('MSFT', $result);
        
        // Test default value when no match
        $result = $method->invoke($this->dao, $testRow, ['nonexistent', 'missing'], 'DEFAULT');
        $this->assertEquals('DEFAULT', $result);
        
        // Test numeric value
        $result = $method->invoke($this->dao, $testRow, ['price'], 0);
        $this->assertEquals(150.25, $result);
    }
    
    /**
     * Test transaction validation
     */
    public function testValidateTransactionData(): void {
        $reflection = new ReflectionClass($this->dao);
        $method = $reflection->getMethod('validateTransactionData');
        $method->setAccessible(true);
        
        // Valid transaction data
        $validRow = [
            'bank_name' => 'Test Bank',
            'account_number' => '12345',
            'symbol' => 'AAPL'
        ];
        
        $result = $method->invoke($this->dao, $validRow);
        $this->assertTrue($result);
        
        // Invalid transaction data - missing bank_name
        $invalidRow = [
            'account_number' => '12345',
            'symbol' => 'AAPL'
        ];
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: bank_name');
        $method->invoke($this->dao, $invalidRow);
    }
    
    /**
     * Test that parsing dependencies are removed
     */
    public function testNoParsigDependencies(): void {
        $reflection = new ReflectionClass($this->dao);
        
        // Verify old parsing methods are removed
        $this->assertFalse($reflection->hasMethod('parseAccountHoldingsCSV'));
        $this->assertFalse($reflection->hasMethod('parseTransactionHistoryCSV'));
        $this->assertFalse($reflection->hasMethod('identifyBankAccount'));
        
        // Verify no CSV parser dependency
        $properties = $reflection->getProperties();
        $propertyNames = array_map(function($prop) { return $prop->getName(); }, $properties);
        $this->assertNotContains('csvParser', $propertyNames);
    }
    
    /**
     * Test DAO follows SRP - only database operations
     */
    public function testSingleResponsibilityPrinciple(): void {
        $reflection = new ReflectionClass($this->dao);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        $allowedMethods = [
            '__construct',
            'saveStagingCSV',
            'importToMidCap'
        ];
        
        foreach ($methods as $method) {
            $methodName = $method->getName();
            // Skip inherited methods from parent classes
            if ($method->getDeclaringClass()->getName() === 'MidCapBankImportDAO') {
                $this->assertContains($methodName, $allowedMethods, 
                    "Method {$methodName} violates SRP - DAO should only handle data persistence");
            }
        }
    }
    
    /**
     * Test staging file directory creation
     */
    public function testStagingDirectoryCreation(): void {
        // Ensure clean test environment
        $stagingDir = __DIR__ . '/../../web_ui/../bank_imports';
        if (is_dir($stagingDir)) {
            // Create a test file to ensure directory exists
            $testRows = [['test' => 'data']];
            $stagingFile = $this->dao->saveStagingCSV($testRows, 'directory_test');
            
            $this->assertFileExists($stagingFile);
            $this->assertStringContainsString('bank_imports', $stagingFile);
            
            // Clean up
            unlink($stagingFile);
        } else {
            // Test directory creation
            $testRows = [['test' => 'data']];
            $stagingFile = $this->dao->saveStagingCSV($testRows, 'directory_creation_test');
            
            $this->assertFileExists($stagingFile);
            $this->assertTrue(is_dir($stagingDir));
            
            // Clean up
            unlink($stagingFile);
        }
    }
}