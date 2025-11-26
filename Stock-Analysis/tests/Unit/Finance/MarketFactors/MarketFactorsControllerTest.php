<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\Finance\MarketFactors\Controllers\MarketFactorsController;
use Ksfraser\Finance\MarketFactors\Entities\MarketFactor;
use Ksfraser\Finance\MarketFactors\Entities\IndexPerformance;

class MarketFactorsControllerTest extends TestCase
{
    private $pdo;
    private $controller;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create test tables
        $this->createTestTables();
        
        $this->controller = new MarketFactorsController($this->pdo);
    }

    private function createTestTables(): void
    {
        // Create market_factors table
        $sql = "
            CREATE TABLE market_factors (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                symbol VARCHAR(50) NOT NULL,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(20) NOT NULL,
                value DECIMAL(15,6) NOT NULL,
                change_amount DECIMAL(15,6) DEFAULT 0.0,
                change_percent DECIMAL(8,4) DEFAULT 0.0,
                signal_strength DECIMAL(4,2) DEFAULT 0.0,
                age_hours INTEGER DEFAULT 0,
                metadata TEXT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $this->pdo->exec($sql);
        
        // Insert test data
        $this->insertTestData();
    }

    private function insertTestData(): void
    {
        $factors = [
            ['SPY', 'SPDR S&P 500 ETF', 'index', 450.25, 5.25, 1.18],
            ['QQQ', 'Invesco QQQ Trust', 'index', 380.50, -2.10, -0.55],
            ['EURUSD', 'Euro to US Dollar', 'forex', 1.0850, 0.0025, 0.23],
            ['XLK', 'Technology Sector', 'sector', 165.45, 8.20, 5.22],
            ['US_GDP', 'GDP Growth Rate', 'economic', 2.8, 0.2, 7.69]
        ];
        
        $stmt = $this->pdo->prepare("
            INSERT INTO market_factors (symbol, name, type, value, change_amount, change_percent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($factors as $factor) {
            $stmt->execute($factor);
        }
    }

    /**
     * Test getting all factors
     */
    public function testGetAllFactors(): void
    {
        $result = $this->controller->getAllFactors();
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertGreaterThan(0, $result['count']);
        $this->assertCount($result['count'], $result['data']);
    }

    /**
     * Test getting factors by type
     */
    public function testGetFactorsByType(): void
    {
        $result = $this->controller->getFactorsByType('index');
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('index', $result['type']);
        $this->assertGreaterThan(0, $result['count']);
        
        // Verify all returned factors are of the correct type
        foreach ($result['data'] as $factor) {
            $this->assertEquals('index', $factor['type']);
        }
    }

    /**
     * Test getting factor by symbol
     */
    public function testGetFactorBySymbol(): void
    {
        $result = $this->controller->getFactorBySymbol('SPY');
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('SPY', $result['data']['symbol']);
        $this->assertEquals('SPDR S&P 500 ETF', $result['data']['name']);
    }

    /**
     * Test getting non-existent factor by symbol
     */
    public function testGetNonExistentFactorBySymbol(): void
    {
        $result = $this->controller->getFactorBySymbol('NONEXISTENT');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('not found', $result['error']);
    }

    /**
     * Test search factors with filters
     */
    public function testSearchFactors(): void
    {
        $filters = ['type' => 'index'];
        $result = $this->controller->searchFactors($filters);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('filters', $result);
        $this->assertEquals($filters, $result['filters']);
        
        // Verify all results match the filter
        foreach ($result['data'] as $factor) {
            $this->assertEquals('index', $factor['type']);
        }
    }

    /**
     * Test search factors with symbol filter
     */
    public function testSearchFactorsWithSymbolFilter(): void
    {
        $filters = ['symbol_like' => 'SP'];
        $result = $this->controller->searchFactors($filters);
        
        $this->assertTrue($result['success']);
        
        // Verify all results contain 'SP' in symbol
        foreach ($result['data'] as $factor) {
            $this->assertStringContainsString('SP', $factor['symbol']);
        }
    }

    /**
     * Test get market summary
     */
    public function testGetMarketSummary(): void
    {
        $result = $this->controller->getMarketSummary();
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        
        $summary = $result['data'];
        $this->assertArrayHasKey('total_factors', $summary);
        $this->assertArrayHasKey('average_change', $summary);
        $this->assertArrayHasKey('top_performers', $summary);
        $this->assertArrayHasKey('worst_performers', $summary);
    }

    /**
     * Test get correlations
     */
    public function testGetCorrelations(): void
    {
        $result = $this->controller->getCorrelations();
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    /**
     * Test get top performers
     */
    public function testGetTopPerformers(): void
    {
        $result = $this->controller->getTopPerformers(3);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertEquals(3, $result['limit']);
        $this->assertLessThanOrEqual(3, count($result['data']));
        
        // Verify results are sorted by performance (descending)
        $data = $result['data'];
        for ($i = 1; $i < count($data); $i++) {
            $this->assertGreaterThanOrEqual($data[$i]['change_percent'], $data[$i-1]['change_percent']);
        }
    }

    /**
     * Test get worst performers
     */
    public function testGetWorstPerformers(): void
    {
        $result = $this->controller->getWorstPerformers(3);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertLessThanOrEqual(3, count($result['data']));
        
        // Verify results are sorted by performance (ascending)
        $data = $result['data'];
        for ($i = 1; $i < count($data); $i++) {
            $this->assertLessThanOrEqual($data[$i]['change_percent'], $data[$i-1]['change_percent']);
        }
    }

    /**
     * Test create or update factor with valid data
     */
    public function testCreateOrUpdateFactorValid(): void
    {
        $factorData = [
            'symbol' => 'TEST',
            'name' => 'Test Factor',
            'type' => 'index',
            'value' => 100.50,
            'change' => 2.25,
            'change_percent' => 2.29
        ];
        
        $result = $this->controller->createOrUpdateFactor($factorData);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('TEST', $result['data']['symbol']);
    }

    /**
     * Test create or update factor with invalid data
     */
    public function testCreateOrUpdateFactorInvalid(): void
    {
        $factorData = [
            'symbol' => 'TEST',
            'name' => '',  // Missing required field
            'type' => 'invalid_type',  // Invalid type
            'value' => 'not_a_number'  // Invalid value
        ];
        
        $result = $this->controller->createOrUpdateFactor($factorData);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test get statistics
     */
    public function testGetStatistics(): void
    {
        $result = $this->controller->getStatistics();
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        
        $stats = $result['data'];
        $this->assertArrayHasKey('total_factors', $stats);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertArrayHasKey('sentiment', $stats);
        $this->assertArrayHasKey('last_updated', $stats);
        
        // Check by_type structure
        $byType = $stats['by_type'];
        $this->assertArrayHasKey('index', $byType);
        $this->assertArrayHasKey('forex', $byType);
        $this->assertArrayHasKey('economic', $byType);
        $this->assertArrayHasKey('sector', $byType);
        $this->assertArrayHasKey('sentiment', $byType);
        $this->assertArrayHasKey('commodity', $byType);
    }

    /**
     * Test validate factor data method
     */
    public function testValidateFactorData(): void
    {
        // Test with missing required field
        $invalidData = ['symbol' => 'TEST'];
        $result = $this->controller->createOrUpdateFactor($invalidData);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Required field', $result['error']);
        
        // Test with invalid type
        $invalidTypeData = [
            'symbol' => 'TEST',
            'name' => 'Test',
            'type' => 'invalid',
            'value' => 100
        ];
        $result = $this->controller->createOrUpdateFactor($invalidTypeData);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid factor type', $result['error']);
        
        // Test with non-numeric value
        $invalidValueData = [
            'symbol' => 'TEST',
            'name' => 'Test',
            'type' => 'index',
            'value' => 'not_a_number'
        ];
        $result = $this->controller->createOrUpdateFactor($invalidValueData);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('must be numeric', $result['error']);
    }

    /**
     * Test factor creation from data
     */
    public function testCreateFactorFromData(): void
    {
        // Test index factor creation
        $indexData = [
            'symbol' => 'TEST_INDEX',
            'name' => 'Test Index',
            'type' => 'index',
            'value' => 150.75,
            'change' => 2.25,
            'change_percent' => 1.52,
            'volume' => 1000000,
            'market_cap' => 500000000,
            'volatility_category' => 'High'
        ];
        
        $result = $this->controller->createOrUpdateFactor($indexData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('TEST_INDEX', $result['data']['symbol']);
        $this->assertEquals('index', $result['data']['type']);
        
        // Test forex factor creation
        $forexData = [
            'symbol' => 'TESTCUR',
            'name' => 'Test Currency',
            'type' => 'forex',
            'value' => 1.2345,
            'base_currency' => 'TEST',
            'quote_currency' => 'CUR'
        ];
        
        $result = $this->controller->createOrUpdateFactor($forexData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('TESTCUR', $result['data']['symbol']);
        $this->assertEquals('forex', $result['data']['type']);
    }

    /**
     * Test error handling
     */
    public function testErrorHandling(): void
    {
        // Test with database error by closing connection
        $this->pdo = null;
        $controller = new MarketFactorsController(new PDO('sqlite::memory:'));
        
        $result = $controller->getAllFactors();
        
        // Should handle the error gracefully
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
    }
}
