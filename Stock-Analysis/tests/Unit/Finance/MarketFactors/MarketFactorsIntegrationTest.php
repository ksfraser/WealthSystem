<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\Finance\MarketFactors\Services\MarketFactorsService;
use Ksfraser\Finance\MarketFactors\Repository\MarketFactorsRepository;
use Ksfraser\Finance\MarketFactors\Entities\MarketFactor;
use Ksfraser\Finance\MarketFactors\Entities\IndexPerformance;
use Ksfraser\Finance\MarketFactors\Entities\ForexRate;
use Ksfraser\Finance\MarketFactors\Entities\EconomicIndicator;
use Ksfraser\Finance\MarketFactors\Entities\SectorPerformance;

class MarketFactorsIntegrationTest extends TestCase
{
    private $pdo;
    private $repository;
    private $service;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create test tables
        $this->createTestTables();
        
        $this->repository = new MarketFactorsRepository($this->pdo);
        $this->service = new MarketFactorsService($this->repository);
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
    }

    /**
     * Test complete workflow: create, save, retrieve, and analyze factors
     */
    public function testCompleteWorkflow(): void
    {
        // Step 1: Create various factor types
        $indexFactor = new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 450.25, 5.25, 1.18);
        $forexFactor = new ForexRate('EUR', 'USD', 1.0850, 0.0025, 0.23);
        $economicFactor = new EconomicIndicator('US_GDP', 'GDP Growth Rate', 'US', 2.8, 2.6, 2.7);
        $sectorFactor = new SectorPerformance('XLK', 'Technology', 165.45, 8.20, 5.22);
        
        // Step 2: Add factors to service (which should persist them)
        $this->service->addFactor($indexFactor);
        $this->service->addFactor($forexFactor);
        $this->service->addFactor($economicFactor);
        $this->service->addFactor($sectorFactor);
        
        // Step 3: Verify factors were saved
        $retrievedIndex = $this->service->getFactor('SPY');
        $this->assertNotNull($retrievedIndex);
        $this->assertEquals('SPY', $retrievedIndex->getSymbol());
        $this->assertEquals('index', $retrievedIndex->getType());
        
        $retrievedForex = $this->service->getFactor('EURUSD');
        $this->assertNotNull($retrievedForex);
        $this->assertEquals('EURUSD', $retrievedForex->getSymbol());
        $this->assertEquals('forex', $retrievedForex->getType());
        
        // Step 4: Test getting all factors
        $allFactors = $this->service->getAllFactors();
        $this->assertCount(4, $allFactors);
        
        // Step 5: Test filtering by type
        $indexFactors = $this->service->getFactorsByType('index');
        $this->assertCount(1, $indexFactors);
        $this->assertEquals('SPY', $indexFactors[0]->getSymbol());
        
        // Step 6: Test market summary calculation
        $summary = $this->service->getMarketSummary();
        $this->assertArrayHasKey('total_factors', $summary);
        $this->assertArrayHasKey('sentiment', $summary);
        $this->assertEquals(4, $summary['total_factors']);
        
        // Step 7: Test top performers
        $topPerformers = $this->service->getTopPerformers(2);
        $this->assertCount(2, $topPerformers);
        
        // Verify they're sorted by performance
        $this->assertGreaterThanOrEqual(
            $topPerformers[1]->getChangePercent(),
            $topPerformers[0]->getChangePercent()
        );
    }

    /**
     * Test persistence and retrieval across service instances
     */
    public function testPersistenceAcrossInstances(): void
    {
        // Create and save a factor with first service instance
        $factor = new IndexPerformance('QQQ', 'Invesco QQQ Trust', 'US', 380.50);
        $this->service->addFactor($factor);
        
        // Create new service instance with same repository
        $newService = new MarketFactorsService($this->repository);
        
        // Verify factor can be retrieved by new service instance
        $retrievedFactor = $newService->getFactor('QQQ');
        $this->assertNotNull($retrievedFactor);
        $this->assertEquals('QQQ', $retrievedFactor->getSymbol());
        $this->assertEquals('Invesco QQQ Trust', $retrievedFactor->getName());
    }

    /**
     * Test correlation tracking between factors
     */
    public function testCorrelationTracking(): void
    {
        // Add multiple factors
        $spy = new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 450.25, 5.25, 1.18);
        $qqq = new IndexPerformance('QQQ', 'Invesco QQQ Trust', 'US', 380.50, 8.10, 2.18);
        $vti = new IndexPerformance('VTI', 'Vanguard Total Stock Market', 'US', 220.75, 4.80, 2.22);
        
        $this->service->addFactor($spy);
        $this->service->addFactor($qqq);
        $this->service->addFactor($vti);
        
        // Track correlation between SPY and QQQ
        $this->service->trackCorrelation('SPY', 'QQQ', 0.85, 30);
        
        // Get correlation matrix
        $correlations = $this->service->getCorrelationMatrix();
        $this->assertArrayHasKey('SPY-QQQ', $correlations);
        $this->assertEquals(0.85, $correlations['SPY-QQQ']);
    }

    /**
     * Test market sentiment calculation
     */
    public function testMarketSentimentCalculation(): void
    {
        // Add factors with different performance levels
        $positive1 = new IndexPerformance('UP1', 'Positive Factor 1', 'US', 100.0, 5.0, 5.0);
        $positive2 = new IndexPerformance('UP2', 'Positive Factor 2', 'US', 100.0, 3.0, 3.0);
        $negative1 = new IndexPerformance('DOWN1', 'Negative Factor 1', 'US', 100.0, -2.0, -2.0);
        $neutral = new IndexPerformance('FLAT', 'Neutral Factor', 'US', 100.0, 0.1, 0.1);
        
        $this->service->addFactor($positive1);
        $this->service->addFactor($positive2);
        $this->service->addFactor($negative1);
        $this->service->addFactor($neutral);
        
        // Calculate market sentiment
        $sentiment = $this->service->calculateMarketSentiment();
        
        // Should be positive overall (6% positive vs 2% negative)
        $this->assertGreaterThan(50, $sentiment);
        $this->assertLessThanOrEqual(100, $sentiment);
    }

    /**
     * Test searching and filtering functionality
     */
    public function testSearchAndFiltering(): void
    {
        // Add diverse factors
        $factors = [
            new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 450.25, 5.25, 1.18),
            new IndexPerformance('QQQ', 'Invesco QQQ Trust', 'US', 380.50, -2.10, -0.55),
            new ForexRate('EUR', 'USD', 1.0850, 0.0025, 0.23),
            new SectorPerformance('XLK', 'Technology', 165.45, 8.20, 5.22),
            new EconomicIndicator('US_CPI', 'Consumer Price Index', 'US', 3.2, 3.0, 3.1)
        ];
        
        foreach ($factors as $factor) {
            $this->service->addFactor($factor);
        }
        
        // Test filtering by type
        $indexFactors = $this->service->getFactorsByType('index');
        $this->assertCount(2, $indexFactors);
        
        $forexFactors = $this->service->getFactorsByType('forex');
        $this->assertCount(1, $forexFactors);
        
        // Test search with filters
        $filters = [
            'type' => 'index',
            'min_change_percent' => 0.0
        ];
        $positiveIndexFactors = $this->service->searchFactors($filters);
        $this->assertCount(1, $positiveIndexFactors); // Only SPY has positive change
        
        // Test symbol search
        $symbolFilters = ['symbol_like' => 'Q'];
        $qFactors = $this->service->searchFactors($symbolFilters);
        $this->assertCount(1, $qFactors);
        $this->assertEquals('QQQ', $qFactors[0]->getSymbol());
    }

    /**
     * Test data export and import functionality
     */
    public function testDataExportImport(): void
    {
        // Add test factors
        $factors = [
            new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 450.25, 5.25, 1.18),
            new ForexRate('EUR', 'USD', 1.0850),
            new EconomicIndicator('US_GDP', 'GDP Growth Rate', 'US', 2.8)
        ];
        
        foreach ($factors as $factor) {
            $this->service->addFactor($factor);
        }
        
        // Export data
        $exportData = $this->service->exportData();
        $this->assertArrayHasKey('factors', $exportData);
        $this->assertArrayHasKey('correlations', $exportData);
        $this->assertArrayHasKey('metadata', $exportData);
        $this->assertCount(3, $exportData['factors']);
        
        // Clear service
        $newService = new MarketFactorsService($this->repository);
        
        // Import data to new service
        $importResult = $newService->importData($exportData);
        $this->assertTrue($importResult);
        
        // Verify data was imported correctly
        $importedFactors = $newService->getAllFactors();
        $this->assertCount(3, $importedFactors);
    }

    /**
     * Test concurrent access and data consistency
     */
    public function testConcurrentAccess(): void
    {
        // Create multiple service instances
        $service1 = new MarketFactorsService($this->repository);
        $service2 = new MarketFactorsService($this->repository);
        
        // Add factor through service1
        $factor1 = new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 450.25);
        $service1->addFactor($factor1);
        
        // Update same factor through service2
        $factor2 = new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 452.30, 2.05, 0.46);
        $service2->addFactor($factor2);
        
        // Verify both services see the updated data
        $retrieved1 = $service1->getFactor('SPY');
        $retrieved2 = $service2->getFactor('SPY');
        
        $this->assertEquals(452.30, $retrieved1->getValue());
        $this->assertEquals(452.30, $retrieved2->getValue());
        $this->assertEquals(0.46, $retrieved1->getChangePercent());
        $this->assertEquals(0.46, $retrieved2->getChangePercent());
    }

    /**
     * Test error handling and recovery
     */
    public function testErrorHandlingAndRecovery(): void
    {
        // Test with invalid factor data
        try {
            $invalidFactor = new MarketFactor('', '', '', 0); // Invalid empty symbol
            $this->service->addFactor($invalidFactor);
            $this->fail('Should have thrown an exception for invalid factor');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
        }
        
        // Verify service is still functional
        $validFactor = new IndexPerformance('SPY', 'SPDR S&P 500 ETF', 'US', 450.25);
        $this->service->addFactor($validFactor);
        
        $retrieved = $this->service->getFactor('SPY');
        $this->assertNotNull($retrieved);
        $this->assertEquals('SPY', $retrieved->getSymbol());
    }

    /**
     * Test performance with large datasets
     */
    public function testPerformanceWithLargeDatasets(): void
    {
        $startTime = microtime(true);
        
        // Add many factors
        for ($i = 0; $i < 100; $i++) {
            $factor = new IndexPerformance(
                "TEST$i",
                "Test Factor $i",
                'US',
                100.0 + rand(-50, 50),
                rand(-10, 10),
                rand(-20, 20) / 100
            );
            $this->service->addFactor($factor);
        }
        
        $addTime = microtime(true) - $startTime;
        
        // Test retrieval performance
        $retrievalStart = microtime(true);
        $allFactors = $this->service->getAllFactors();
        $retrievalTime = microtime(true) - $retrievalStart;
        
        // Verify performance is reasonable (less than 1 second for 100 factors)
        $this->assertLessThan(1.0, $addTime);
        $this->assertLessThan(0.5, $retrievalTime);
        $this->assertCount(100, $allFactors);
        
        // Test search performance
        $searchStart = microtime(true);
        $searchResults = $this->service->searchFactors(['type' => 'index']);
        $searchTime = microtime(true) - $searchStart;
        
        $this->assertLessThan(0.2, $searchTime);
        $this->assertCount(100, $searchResults);
    }

    /**
     * Test memory usage and cleanup
     */
    public function testMemoryUsageAndCleanup(): void
    {
        $initialMemory = memory_get_usage();
        
        // Add many factors
        for ($i = 0; $i < 50; $i++) {
            $factor = new IndexPerformance(
                "MEM_TEST$i",
                "Memory Test Factor $i",
                'US',
                100.0 + $i,
                $i % 10,
                ($i % 10) / 100
            );
            $this->service->addFactor($factor);
        }
        
        $afterAddMemory = memory_get_usage();
        
        // Clear service reference
        $this->service = null;
        
        // Force garbage collection
        gc_collect_cycles();
        
        $afterCleanupMemory = memory_get_usage();
        
        // Memory usage should not grow excessively
        $memoryGrowth = $afterAddMemory - $initialMemory;
        $this->assertLessThan(5 * 1024 * 1024, $memoryGrowth); // Less than 5MB growth
        
        // Memory should be freed after cleanup (allowing some tolerance)
        $memoryFreed = $afterAddMemory - $afterCleanupMemory;
        $this->assertGreaterThan($memoryGrowth * 0.5, $memoryFreed);
    }
}
