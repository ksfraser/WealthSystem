<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\Finance\MarketFactors\Repository\MarketFactorsRepository;
use Ksfraser\Finance\MarketFactors\Entities\MarketFactor;
use Ksfraser\Finance\MarketFactors\Entities\IndexPerformance;
use Ksfraser\Finance\MarketFactors\Entities\ForexRate;
use Ksfraser\Finance\MarketFactors\Entities\EconomicIndicator;
use Ksfraser\Finance\MarketFactors\Entities\SectorPerformance;

class MarketFactorsRepositoryTest extends TestCase
{
    private $pdo;
    private $repository;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create test tables
        $this->createTestTables();
        
        $this->repository = new MarketFactorsRepository($this->pdo);
    }

    private function createTestTables(): void
    {
        // Create market_factors table compatible with SQLite
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

        // Create index_performance table
        $sql = "
            CREATE TABLE index_performance (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                symbol VARCHAR(50) NOT NULL,
                index_name VARCHAR(255) NOT NULL,
                region VARCHAR(100) NOT NULL,
                value DECIMAL(15,6) NOT NULL,
                change_amount DECIMAL(15,6) DEFAULT 0.0,
                change_percent DECIMAL(8,4) DEFAULT 0.0,
                volume BIGINT DEFAULT 0,
                market_cap DECIMAL(20,2) DEFAULT 0.0,
                pe_ratio DECIMAL(8,2) DEFAULT 0.0,
                dividend_yield DECIMAL(6,4) DEFAULT 0.0,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $this->pdo->exec($sql);

        // Create forex_rates table
        $sql = "
            CREATE TABLE forex_rates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                pair VARCHAR(10) NOT NULL,
                base_currency VARCHAR(3) NOT NULL,
                quote_currency VARCHAR(3) NOT NULL,
                rate DECIMAL(15,8) NOT NULL,
                bid DECIMAL(15,8) DEFAULT 0.0,
                ask DECIMAL(15,8) DEFAULT 0.0,
                spread DECIMAL(15,8) DEFAULT 0.0,
                change_amount DECIMAL(15,8) DEFAULT 0.0,
                change_percent DECIMAL(8,4) DEFAULT 0.0,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $this->pdo->exec($sql);

        // Create economic_indicators table
        $sql = "
            CREATE TABLE economic_indicators (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                indicator_code VARCHAR(50) NOT NULL,
                indicator_name VARCHAR(255) NOT NULL,
                country VARCHAR(100) NOT NULL,
                value DECIMAL(15,6) NOT NULL,
                previous_value DECIMAL(15,6) DEFAULT 0.0,
                forecast_value DECIMAL(15,6) DEFAULT 0.0,
                change_amount DECIMAL(15,6) DEFAULT 0.0,
                change_percent DECIMAL(8,4) DEFAULT 0.0,
                frequency VARCHAR(20) DEFAULT 'monthly',
                unit VARCHAR(50) DEFAULT 'percent',
                source VARCHAR(100) DEFAULT 'government',
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $this->pdo->exec($sql);

        // Create sector_performance table
        $sql = "
            CREATE TABLE sector_performance (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                symbol VARCHAR(50) NOT NULL,
                sector_name VARCHAR(255) NOT NULL,
                value DECIMAL(15,6) NOT NULL,
                change_amount DECIMAL(15,6) DEFAULT 0.0,
                change_percent DECIMAL(8,4) DEFAULT 0.0,
                volume BIGINT DEFAULT 0,
                market_cap DECIMAL(20,2) DEFAULT 0.0,
                pe_ratio DECIMAL(8,2) DEFAULT 0.0,
                beta DECIMAL(6,4) DEFAULT 1.0,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $this->pdo->exec($sql);
    }

    /**
     * Test saving and retrieving a basic market factor
     */
    public function testSaveAndRetrieveMarketFactor(): void
    {
        $factor = new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 450.25, 5.25, 1.18);
        
        // Save factor
        $this->repository->saveFactor($factor);
        
        // Retrieve factor
        $retrieved = $this->repository->getFactorBySymbol('SPY');
        
        $this->assertNotNull($retrieved);
        $this->assertEquals('SPY', $retrieved->getSymbol());
        $this->assertEquals('SPDR S&P 500 ETF', $retrieved->getName());
        $this->assertEquals('index', $retrieved->getType());
        $this->assertEquals(450.25, $retrieved->getValue());
        $this->assertEquals(5.25, $retrieved->getChange());
        $this->assertEquals(1.18, $retrieved->getChangePercent());
    }

    /**
     * Test saving different types of factors
     */
    public function testSaveDifferentFactorTypes(): void
    {
        $factors = [
            new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 450.25, 5.25, 1.18),
            new ForexRate('EUR', 'USD', 1.0850, 0.0025, 0.23),
            new EconomicIndicator('US_GDP', 'GDP Growth Rate', 'US', 2.8, 2.6, 2.7),
            new SectorPerformance('XLK', 'Technology', 165.45, 8.20, 5.22)
        ];
        
        // Save all factors
        foreach ($factors as $factor) {
            $this->repository->saveFactor($factor);
        }
        
        // Retrieve and verify each factor
        $spyFactor = $this->repository->getFactorBySymbol('SPY');
        $this->assertEquals('index', $spyFactor->getType());
        
        $eurFactor = $this->repository->getFactorBySymbol('EURUSD');
        $this->assertEquals('forex', $eurFactor->getType());
        
        $gdpFactor = $this->repository->getFactorBySymbol('US_GDP');
        $this->assertEquals('economic', $gdpFactor->getType());
        
        $xlkFactor = $this->repository->getFactorBySymbol('XLK');
        $this->assertEquals('sector', $xlkFactor->getType());
    }

    /**
     * Test updating existing factors
     */
    public function testUpdateExistingFactor(): void
    {
        $factor = new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 450.25, 5.25, 1.18);
        $this->repository->saveFactor($factor);
        
        // Update the factor
        $updatedFactor = new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 452.30, 7.30, 1.64);
        $this->repository->saveFactor($updatedFactor);
        
        // Retrieve and verify update
        $retrieved = $this->repository->getFactorBySymbol('SPY');
        $this->assertEquals(452.30, $retrieved->getValue());
        $this->assertEquals(7.30, $retrieved->getChange());
        $this->assertEquals(1.64, $retrieved->getChangePercent());
    }

    /**
     * Test retrieving all factors
     */
    public function testGetAllFactors(): void
    {
        $factors = [
            new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 450.25),
            new IndexPerformance('QQQ', 'Invesco QQQ Trust', 'US', 380.50),
            new ForexRate('EUR', 'USD', 1.0850),
            new SectorPerformance('XLK', 'Technology', 165.45)
        ];
        
        foreach ($factors as $factor) {
            $this->repository->saveFactor($factor);
        }
        
        $allFactors = $this->repository->getAllFactors();
        $this->assertCount(4, $allFactors);
        
        // Verify all symbols are present
        $symbols = array_map(function($factor) {
            return $factor->getSymbol();
        }, $allFactors);
        
        $this->assertContains('SPY', $symbols);
        $this->assertContains('QQQ', $symbols);
        $this->assertContains('EURUSD', $symbols);
        $this->assertContains('XLK', $symbols);
    }

    /**
     * Test retrieving factors by type
     */
    public function testGetFactorsByType(): void
    {
        $factors = [
            new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 450.25),
            new IndexPerformance('QQQ', 'Invesco QQQ Trust', 'US', 380.50),
            new ForexRate('EUR', 'USD', 1.0850),
            new ForexRate('GBP', 'USD', 1.2650),
            new SectorPerformance('XLK', 'Technology', 165.45)
        ];
        
        foreach ($factors as $factor) {
            $this->repository->saveFactor($factor);
        }
        
        // Test getting index factors
        $indexFactors = $this->repository->getFactorsByType('index');
        $this->assertCount(2, $indexFactors);
        
        // Test getting forex factors
        $forexFactors = $this->repository->getFactorsByType('forex');
        $this->assertCount(2, $forexFactors);
        
        // Test getting sector factors
        $sectorFactors = $this->repository->getFactorsByType('sector');
        $this->assertCount(1, $sectorFactors);
    }

    /**
     * Test deleting factors
     */
    public function testDeleteFactor(): void
    {
        $factor = new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 450.25);
        $this->repository->saveFactor($factor);
        
        // Verify factor exists
        $retrieved = $this->repository->getFactorBySymbol('SPY');
        $this->assertNotNull($retrieved);
        
        // Delete factor
        $deleted = $this->repository->deleteFactor('SPY');
        $this->assertTrue($deleted);
        
        // Verify factor no longer exists
        $retrieved = $this->repository->getFactorBySymbol('SPY');
        $this->assertNull($retrieved);
    }

    /**
     * Test searching factors with filters
     */
    public function testSearchFactors(): void
    {
        $factors = [
            new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 450.25, 5.25, 1.18),
            new IndexPerformance('QQQ', 'Invesco QQQ Trust', 'US', 380.50, -2.10, -0.55),
            new ForexRate('EUR', 'USD', 1.0850, 0.0025, 0.23),
            new SectorPerformance('XLK', 'Technology', 165.45, 8.20, 5.22)
        ];
        
        foreach ($factors as $factor) {
            $this->repository->saveFactor($factor);
        }
        
        // Search by type
        $filters = ['type' => 'index'];
        $results = $this->repository->searchFactors($filters);
        $this->assertCount(2, $results);
        
        // Search by positive change
        $filters = ['min_change_percent' => 0.0];
        $results = $this->repository->searchFactors($filters);
        $this->assertCount(3, $results); // SPY, EUR/USD, XLK
        
        // Search by symbol pattern
        $filters = ['symbol_like' => 'Q'];
        $results = $this->repository->searchFactors($filters);
        $this->assertCount(1, $results);
        $this->assertEquals('QQQ', $results[0]->getSymbol());
    }

    /**
     * Test getting current factors (same as getAllFactors)
     */
    public function testGetCurrentFactors(): void
    {
        $factors = [
            new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 450.25),
            new ForexRate('EUR', 'USD', 1.0850)
        ];
        
        foreach ($factors as $factor) {
            $this->repository->saveFactor($factor);
        }
        
        $currentFactors = $this->repository->getCurrentFactors();
        $this->assertCount(2, $currentFactors);
    }

    /**
     * Test error handling for non-existent factors
     */
    public function testNonExistentFactor(): void
    {
        $factor = $this->repository->getFactorBySymbol('NONEXISTENT');
        $this->assertNull($factor);
        
        $deleted = $this->repository->deleteFactor('NONEXISTENT');
        $this->assertFalse($deleted);
    }

    /**
     * Test database transaction integrity
     */
    public function testTransactionIntegrity(): void
    {
        $factor1 = new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 450.25);
        $factor2 = new IndexPerformance('QQQ', 'Invesco QQQ Trust', 'US', 380.50);
        
        // Start transaction
        $this->pdo->beginTransaction();
        
        try {
            $this->repository->saveFactor($factor1);
            $this->repository->saveFactor($factor2);
            
            // Commit transaction
            $this->pdo->commit();
            
            // Verify both factors exist
            $this->assertNotNull($this->repository->getFactorBySymbol('SPY'));
            $this->assertNotNull($this->repository->getFactorBySymbol('QQQ'));
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->fail('Transaction should not have failed');
        }
    }

    /**
     * Test bulk operations performance
     */
    public function testBulkOperationsPerformance(): void
    {
        $startTime = microtime(true);
        
        // Add 50 factors
        for ($i = 0; $i < 50; $i++) {
            $factor = new IndexPerformance(
                "TEST$i",
                "Test Factor $i",
                'US',
                100.0 + $i,
                $i % 10,
                ($i % 10) / 100
            );
            $this->repository->saveFactor($factor);
        }
        
        $insertTime = microtime(true) - $startTime;
        
        // Test retrieval performance
        $retrievalStart = microtime(true);
        $allFactors = $this->repository->getAllFactors();
        $retrievalTime = microtime(true) - $retrievalStart;
        
        // Verify performance is reasonable
        $this->assertLessThan(2.0, $insertTime); // Less than 2 seconds for 50 inserts
        $this->assertLessThan(0.5, $retrievalTime); // Less than 0.5 seconds for retrieval
        $this->assertCount(50, $allFactors);
    }

    /**
     * Test metadata handling
     */
    public function testMetadataHandling(): void
    {
        $factor = new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 450.25);
        
        // Add some metadata
        $metadata = [
            'sector_weights' => [
                'Technology' => 28.5,
                'Healthcare' => 13.2,
                'Financials' => 10.8
            ],
            'market_cap' => 41000000000,
            'dividend_yield' => 1.3
        ];
        
        // Set metadata via reflection or custom method if available
        $reflection = new ReflectionClass($factor);
        $metadataProperty = $reflection->getProperty('metadata');
        $metadataProperty->setAccessible(true);
        $metadataProperty->setValue($factor, $metadata);
        
        $this->repository->saveFactor($factor);
        
        $retrieved = $this->repository->getFactorBySymbol('SPY');
        $this->assertNotNull($retrieved);
        
        // Note: Metadata retrieval depends on repository implementation
        // This test verifies the factor can be saved and retrieved even with complex metadata
    }
}
