<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\Finance\MarketFactors\Services\MarketFactorsService;
use Ksfraser\Finance\MarketFactors\Repository\MarketFactorsRepository;
use Ksfraser\Finance\MarketFactors\Controllers\MarketFactorsController;
use Ksfraser\Finance\MarketFactors\Entities\MarketFactor;
use Ksfraser\Finance\MarketFactors\Entities\IndexPerformance;
use Ksfraser\Finance\MarketFactors\Entities\ForexRate;
use Ksfraser\Finance\MarketFactors\Entities\EconomicIndicator;
use Ksfraser\Finance\MarketFactors\Entities\SectorPerformance;

class MarketFactorsEndToEndTest extends TestCase
{
    private $pdo;
    private $repository;
    private $service;
    private $controller;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create test tables
        $this->createTestTables();
        
        $this->repository = new MarketFactorsRepository($this->pdo);
        $this->service = new MarketFactorsService($this->repository);
        $this->controller = new MarketFactorsController($this->pdo);
    }

    private function createTestTables(): void
    {
        // Create market_factors table compatible with SQLite
        $sql = "
            CREATE TABLE market_factors (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                symbol VARCHAR(50) NOT NULL UNIQUE,
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
     * Test complete end-to-end workflow: Controller -> Service -> Repository -> Database
     */
    public function testCompleteEndToEndWorkflow(): void
    {
        // 1. Create various market factors via controller
        $spyData = [
            'symbol' => 'SPY',
            'name' => 'SPDR S&P 500 ETF',
            'type' => 'index',
            'value' => 450.25,
            'change' => 5.25,
            'change_percent' => 1.18
        ];
        
        $spyResponse = $this->controller->createOrUpdateFactor($spyData);
        $this->assertTrue($spyResponse['success']);
        $this->assertEquals('Factor created/updated successfully', $spyResponse['message']);
        
        // 2. Create forex factor
        $forexData = [
            'symbol' => 'EURUSD',
            'name' => 'EUR/USD',
            'type' => 'forex',
            'value' => 1.0850,
            'change' => 0.0025,
            'change_percent' => 0.23
        ];
        
        $forexResponse = $this->controller->createOrUpdateFactor($forexData);
        $this->assertTrue($forexResponse['success']);
        
        // 3. Get all factors via controller
        $allFactorsResponse = $this->controller->getAllFactors();
        $this->assertTrue($allFactorsResponse['success']);
        $this->assertCount(2, $allFactorsResponse['data']);
        
        // 4. Search factors by type
        $indexFactorsResponse = $this->controller->getFactorsByType('index');
        $this->assertTrue($indexFactorsResponse['success']);
        $this->assertCount(1, $indexFactorsResponse['data']);
        $this->assertEquals('SPY', $indexFactorsResponse['data'][0]['symbol']);
        
        // 5. Get specific factor by symbol
        $spyFactorResponse = $this->controller->getFactorBySymbol('SPY');
        $this->assertTrue($spyFactorResponse['success']);
        $this->assertEquals('SPY', $spyFactorResponse['data']['symbol']);
        $this->assertEquals(450.25, $spyFactorResponse['data']['value']);
        
        // 6. Get market summary
        $summaryResponse = $this->controller->getMarketSummary();
        $this->assertTrue($summaryResponse['success']);
        $this->assertArrayHasKey('sentiment', $summaryResponse['data']);
        $this->assertEquals(2, $summaryResponse['data']['total_factors']);
        
        // 7. Get statistics
        $statsResponse = $this->controller->getStatistics();
        $this->assertTrue($statsResponse['success']);
        $this->assertArrayHasKey('total_factors', $statsResponse['data']);
        $this->assertArrayHasKey('sentiment', $statsResponse['data']);
    }

    /**
     * Test error handling throughout the stack
     */
    public function testErrorHandlingEndToEnd(): void
    {
        // 1. Test invalid factor creation
        $invalidData = [
            'symbol' => '', // Invalid empty symbol
            'name' => 'Invalid Factor',
            'type' => 'index',
            'value' => 100.0
        ];
        
        $errorResponse = $this->controller->createOrUpdateFactor($invalidData);
        $this->assertFalse($errorResponse['success']);
        $this->assertStringContainsString('Required field', $errorResponse['error']);
        
        // 2. Test retrieving non-existent factor
        $notFoundResponse = $this->controller->getFactorBySymbol('NONEXISTENT');
        $this->assertFalse($notFoundResponse['success']);
        $this->assertStringContainsString('not found', $notFoundResponse['error']);
    }

    /**
     * Test data integrity across all layers
     */
    public function testDataIntegrityEndToEnd(): void
    {
        // Create factor with specific data
        $originalData = [
            'symbol' => 'INTEGRITY_TEST',
            'name' => 'Data Integrity Test Factor',
            'type' => 'index',
            'value' => 123.456,
            'change' => 7.89,
            'change_percent' => 6.82
        ];
        
        // 1. Create via controller
        $createResponse = $this->controller->createOrUpdateFactor($originalData);
        $this->assertTrue($createResponse['success']);
        
        // 2. Retrieve via controller
        $getResponse = $this->controller->getFactorBySymbol('INTEGRITY_TEST');
        $this->assertTrue($getResponse['success']);
        $retrievedData = $getResponse['data'];
        
        // 3. Verify data integrity
        $this->assertEquals($originalData['symbol'], $retrievedData['symbol']);
        $this->assertEquals($originalData['name'], $retrievedData['name']);
        $this->assertEquals($originalData['type'], $retrievedData['type']);
        $this->assertEquals($originalData['value'], $retrievedData['value']);
        $this->assertEquals($originalData['change'], $retrievedData['change']);
        $this->assertEquals($originalData['change_percent'], $retrievedData['change_percent']);
        
        // 4. Verify at service level
        $serviceFactor = $this->service->getFactor('INTEGRITY_TEST');
        $this->assertNotNull($serviceFactor);
        $this->assertEquals($originalData['value'], $serviceFactor->getValue());
        
        // 5. Verify at repository level
        $repoFactor = $this->repository->getFactorBySymbol('INTEGRITY_TEST');
        $this->assertNotNull($repoFactor);
        $this->assertEquals($originalData['value'], $repoFactor->getValue());
    }

    /**
     * Test complex market analysis workflow
     */
    public function testComplexMarketAnalysisWorkflow(): void
    {
        // 1. Add diverse market factors
        $factors = [
            ['symbol' => 'SPY', 'name' => 'S&P 500', 'type' => 'index', 'value' => 450.25, 'change_percent' => 1.18],
            ['symbol' => 'QQQ', 'name' => 'NASDAQ 100', 'type' => 'index', 'value' => 380.50, 'change_percent' => -0.55],
            ['symbol' => 'EURUSD', 'name' => 'EUR/USD', 'type' => 'forex', 'value' => 1.0850, 'change_percent' => 0.23],
            ['symbol' => 'GBPUSD', 'name' => 'GBP/USD', 'type' => 'forex', 'value' => 1.2650, 'change_percent' => -0.41],
            ['symbol' => 'XLK', 'name' => 'Technology', 'type' => 'sector', 'value' => 165.45, 'change_percent' => 2.15],
            ['symbol' => 'XLF', 'name' => 'Financials', 'type' => 'sector', 'value' => 38.20, 'change_percent' => -1.22],
            ['symbol' => 'US_GDP', 'name' => 'US GDP', 'type' => 'economic', 'value' => 2.8, 'change_percent' => 0.35]
        ];
        
        foreach ($factors as $factorData) {
            $response = $this->controller->createOrUpdateFactor($factorData);
            $this->assertTrue($response['success']);
        }
        
        // 2. Test top performers analysis
        $topPerformersResponse = $this->controller->getTopPerformers(3);
        $this->assertTrue($topPerformersResponse['success']);
        $topPerformers = $topPerformersResponse['data'];
        $this->assertGreaterThan(0, count($topPerformers));
        
        // Verify sorting (highest change_percent first)
        if (count($topPerformers) > 1) {
            $this->assertGreaterThanOrEqual(
                $topPerformers[1]['change_percent'],
                $topPerformers[0]['change_percent']
            );
        }
        
        // 3. Test worst performers analysis
        $worstPerformersResponse = $this->controller->getWorstPerformers(3);
        $this->assertTrue($worstPerformersResponse['success']);
        $worstPerformers = $worstPerformersResponse['data'];
        $this->assertGreaterThan(0, count($worstPerformers));
        
        // 4. Test filtering by type
        foreach (['index', 'forex', 'sector', 'economic'] as $type) {
            $typeResponse = $this->controller->getFactorsByType($type);
            $this->assertTrue($typeResponse['success']);
            
            // Verify all returned factors are of correct type
            foreach ($typeResponse['data'] as $factor) {
                $this->assertEquals($type, $factor['type']);
            }
        }
        
        // 5. Test market sentiment calculation
        $summaryResponse = $this->controller->getMarketSummary();
        $this->assertTrue($summaryResponse['success']);
        $sentiment = $summaryResponse['data']['sentiment'];
        
        $this->assertArrayHasKey('sentiment', $sentiment);
        $this->assertArrayHasKey('bullish_factors', $sentiment);
        $this->assertArrayHasKey('bearish_factors', $sentiment);
        $this->assertEquals(7, $sentiment['total_factors']);
    }

    /**
     * Test correlation tracking workflow
     */
    public function testCorrelationWorkflow(): void
    {
        // 1. Add correlated factors
        $factors = [
            ['symbol' => 'SPY', 'name' => 'S&P 500', 'type' => 'index', 'value' => 450.25, 'change_percent' => 1.18],
            ['symbol' => 'QQQ', 'name' => 'NASDAQ 100', 'type' => 'index', 'value' => 380.50, 'change_percent' => 1.35],
            ['symbol' => 'VTI', 'name' => 'Total Market', 'type' => 'index', 'value' => 220.75, 'change_percent' => 1.12]
        ];
        
        foreach ($factors as $factorData) {
            $response = $this->controller->createOrUpdateFactor($factorData);
            $this->assertTrue($response['success']);
        }
        
        // 2. Track correlations via service (simulated analysis)
        $this->service->trackCorrelation('SPY', 'QQQ', 0.85, 30);
        $this->service->trackCorrelation('SPY', 'VTI', 0.95, 30);
        $this->service->trackCorrelation('QQQ', 'VTI', 0.78, 30);
        
        // 3. Test correlation retrieval
        $correlations = $this->service->getCorrelationMatrix();
        
        $this->assertArrayHasKey('SPY-QQQ', $correlations);
        $this->assertArrayHasKey('SPY-VTI', $correlations);
        $this->assertArrayHasKey('QQQ-VTI', $correlations);
        
        $this->assertEquals(0.85, $correlations['SPY-QQQ']);
        $this->assertEquals(0.95, $correlations['SPY-VTI']);
        $this->assertEquals(0.78, $correlations['QQQ-VTI']);
        
        // 4. Test correlation endpoint
        $correlationResponse = $this->controller->getCorrelations();
        $this->assertTrue($correlationResponse['success']);
        $this->assertArrayHasKey('SPY-QQQ', $correlationResponse['data']);
    }

    /**
     * Test performance with multiple factors
     */
    public function testPerformanceEndToEnd(): void
    {
        $startTime = microtime(true);
        
        // Create 25 factors through the controller
        for ($i = 0; $i < 25; $i++) {
            $factorData = [
                'symbol' => "TEST$i",
                'name' => "Test Factor $i",
                'type' => 'index',
                'value' => 100.0 + $i,
                'change' => $i % 10,
                'change_percent' => ($i % 10) / 100
            ];
            
            $response = $this->controller->createOrUpdateFactor($factorData);
            $this->assertTrue($response['success']);
        }
        
        $creationTime = microtime(true) - $startTime;
        
        // Test retrieval performance
        $retrievalStart = microtime(true);
        $allFactorsResponse = $this->controller->getAllFactors();
        $retrievalTime = microtime(true) - $retrievalStart;
        
        // Verify performance and results
        $this->assertLessThan(3.0, $creationTime); // Less than 3 seconds for 25 creates
        $this->assertLessThan(0.5, $retrievalTime); // Less than 0.5 seconds for retrieval
        $this->assertTrue($allFactorsResponse['success']);
        $this->assertCount(25, $allFactorsResponse['data']);
    }

    /**
     * Test backup and restore functionality
     */
    public function testBackupAndRestore(): void
    {
        // 1. Create test data
        $testFactors = [
            ['symbol' => 'BACKUP1', 'name' => 'Backup Test 1', 'type' => 'index', 'value' => 100.0],
            ['symbol' => 'BACKUP2', 'name' => 'Backup Test 2', 'type' => 'forex', 'value' => 1.5],
            ['symbol' => 'BACKUP3', 'name' => 'Backup Test 3', 'type' => 'sector', 'value' => 200.0]
        ];
        
        foreach ($testFactors as $factorData) {
            $response = $this->controller->createOrUpdateFactor($factorData);
            $this->assertTrue($response['success']);
        }
        
        // 2. Export data
        $exportData = $this->service->exportData();
        
        $this->assertArrayHasKey('factors', $exportData);
        $this->assertArrayHasKey('correlations', $exportData);
        $this->assertArrayHasKey('metadata', $exportData);
        $this->assertCount(3, $exportData['factors']);
        
        // 3. Create new service and import data
        $newRepository = new MarketFactorsRepository($this->pdo);
        $newService = new MarketFactorsService($newRepository);
        
        $importResult = $newService->importData($exportData);
        $this->assertTrue($importResult);
        
        // 4. Verify data integrity after import
        $importedFactors = $newService->getAllFactors();
        $this->assertCount(3, $importedFactors);
        
        foreach ($testFactors as $originalFactor) {
            $imported = $newService->getFactor($originalFactor['symbol']);
            $this->assertNotNull($imported);
            $this->assertEquals($originalFactor['name'], $imported->getName());
            $this->assertEquals($originalFactor['value'], $imported->getValue());
        }
    }
}
