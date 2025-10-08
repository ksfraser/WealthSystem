<?php

namespace Tests\Unit\Finance\MarketFactors;

use Tests\Unit\TestBaseSimple;
use Ksfraser\Finance\MarketFactors\Services\MarketFactorsService;
use Ksfraser\Finance\MarketFactors\Entities\MarketFactor;
use Ksfraser\Finance\MarketFactors\Entities\SectorPerformance;
use Ksfraser\Finance\MarketFactors\Entities\IndexPerformance;
use Ksfraser\Finance\MarketFactors\Entities\ForexRate;
use Ksfraser\Finance\MarketFactors\Entities\EconomicIndicator;

/**
 * Unit tests for MarketFactorsService
 */
class MarketFactorsServiceTest extends TestBaseSimple
{
    /**
     * Test technical indicator prediction tracking and accuracy update
     */
    public function testTrackIndicatorPredictionAndAccuracy(): void
    {
        $predictionId = $this->service->trackIndicatorPrediction('RSI', 'AAPL', 'buy', 85.0, 150.0, '1d');
        $this->assertIsString($predictionId);

        // Update with correct outcome and price increase
        $result = $this->service->updateIndicatorAccuracy($predictionId, 'correct', 155.0);
        $this->assertTrue($result);

        // Check indicator performance data
        $accuracy = $this->service->getIndicatorAccuracy('RSI');
        $this->assertIsArray($accuracy);
        $this->assertArrayHasKey('average_accuracy', $accuracy);
        $this->assertGreaterThanOrEqual(0, $accuracy['average_accuracy']);

        // Check performance score
        $score = $this->service->getIndicatorPerformanceScore('RSI');
        $this->assertIsFloat($score);
        $this->assertGreaterThan(0, $score);

        // All indicator performance
        $allPerf = $this->service->getAllIndicatorPerformance();
        $this->assertIsArray($allPerf);
        $this->assertArrayHasKey('RSI', $allPerf);
    }

    /**
     * Test calculateWeightedScore and recommendation logic
     */
    public function testCalculateWeightedScoreAndRecommendation(): void
    {
        // Simulate some indicator performance
        $predictionId = $this->service->trackIndicatorPrediction('MACD', 'AAPL', 'buy', 90.0, 150.0, '1d');
        $this->service->updateIndicatorAccuracy($predictionId, 'correct', 160.0);

        // Add a correlation for factor weighting
        $this->service->setCorrelation('AAPL', 'SPY', 0.8);

        $marketFactors = [
            ['symbol' => 'SPY', 'value' => 1.5],
        ];
        $technicalIndicators = [
            ['name' => 'MACD', 'value' => 0.9],
        ];

        $result = $this->service->calculateWeightedScore('AAPL', $marketFactors, $technicalIndicators);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('symbol', $result);
        $this->assertArrayHasKey('combined_score', $result);
        $this->assertArrayHasKey('recommendation', $result);

        $rec = $result['recommendation'];
        $this->assertIsArray($rec);
        $this->assertArrayHasKey('action', $rec);
        $this->assertContains($rec['action'], ['buy', 'hold', 'sell']);
        $this->assertArrayHasKey('risk_level', $rec);
        $this->assertContains($rec['risk_level'], ['low', 'moderate', 'high']);

        // Confidence should be between 0.1 and 1.0
        $this->assertArrayHasKey('confidence', $result['combined_score']);
        $this->assertGreaterThanOrEqual(0.1, $result['combined_score']['confidence']);
        $this->assertLessThanOrEqual(1.0, $result['combined_score']['confidence']);
    }
    private MarketFactorsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MarketFactorsService();
    }

    /**
     * Test adding and retrieving factors
     */
    public function testAddAndGetFactor(): void
    {
        $factor = new MarketFactor('AAPL', 'Apple Inc.', 'stock', 150.0, 2.0, 1.35);
        
        $this->service->addFactor($factor);
        
        $retrieved = $this->service->getFactor('AAPL');
        $this->assertInstanceOf(MarketFactor::class, $retrieved);
        $this->assertEquals('AAPL', $retrieved->getSymbol());
        $this->assertEquals(150.0, $retrieved->getValue());
    }

    /**
     * Test getting non-existent factor
     */
    public function testGetNonExistentFactor(): void
    {
        $result = $this->service->getFactor('NONEXISTENT');
        $this->assertNull($result);
    }

    /**
     * Test getting all factors
     */
    public function testGetAllFactors(): void
    {
        $factor1 = new MarketFactor('AAPL', 'Apple', 'stock', 150.0);
        $factor2 = new MarketFactor('SPY', 'S&P 500', 'index', 4150.0);
        
        $this->service->addFactor($factor1);
        $this->service->addFactor($factor2);
        
        $allFactors = $this->service->getAllFactors();
        $this->assertCount(2, $allFactors);
        $this->assertArrayHasKey('AAPL', $allFactors);
        $this->assertArrayHasKey('SPY', $allFactors);
    }

    /**
     * Test getting factors by type
     */
    public function testGetFactorsByType(): void
    {
        $stock1 = new MarketFactor('AAPL', 'Apple', 'stock', 150.0);
        $stock2 = new MarketFactor('MSFT', 'Microsoft', 'stock', 280.0);
        $index = new MarketFactor('SPY', 'S&P 500', 'index', 4150.0);
        
        $this->service->addFactor($stock1);
        $this->service->addFactor($stock2);
        $this->service->addFactor($index);
        
        $stocks = $this->service->getFactorsByType('stock');
        $indices = $this->service->getFactorsByType('index');
        
        $this->assertCount(2, $stocks);
        $this->assertCount(1, $indices);
    }

    /**
     * Test getting specific factor types
     */
    public function testGetSpecificFactorTypes(): void
    {
        $sector = new SectorPerformance('XLK', 'Technology', 2.5);
        $index = new IndexPerformance('SPY', 'S&P 500', 'US', 4150.0);
        $forex = new ForexRate('USD', 'CAD', 1.35);
        $economic = new EconomicIndicator('US_GDP', 'GDP Growth', 'US', 3.2);
        
        $this->service->addFactor($sector);
        $this->service->addFactor($index);
        $this->service->addFactor($forex);
        $this->service->addFactor($economic);
        
        $sectors = $this->service->getSectorPerformances();
        $indices = $this->service->getIndexPerformances();
        $forexRates = $this->service->getForexRates();
        $indicators = $this->service->getEconomicIndicators();
        
        $this->assertCount(1, $sectors);
        $this->assertCount(1, $indices);
        $this->assertCount(1, $forexRates);
        $this->assertCount(1, $indicators);
        
        $this->assertInstanceOf(SectorPerformance::class, array_values($sectors)[0]);
        $this->assertInstanceOf(IndexPerformance::class, array_values($indices)[0]);
        $this->assertInstanceOf(ForexRate::class, array_values($forexRates)[0]);
        $this->assertInstanceOf(EconomicIndicator::class, array_values($indicators)[0]);
    }

    /**
     * Test filtering factors
     */
    public function testFilterFactors(): void
    {
        $bullish1 = new MarketFactor('BULL1', 'Bullish 1', 'stock', 100.0, 2.0, 2.0);
        $bullish2 = new MarketFactor('BULL2', 'Bullish 2', 'index', 100.0, 3.0, 3.0);
        $bearish = new MarketFactor('BEAR', 'Bearish', 'stock', 100.0, -2.0, -2.0);
        $oldFactor = new MarketFactor('OLD', 'Old Factor', 'stock', 100.0, 1.0, 1.0, new \DateTime('-2 hours'));
        
        $this->service->addFactor($bullish1);
        $this->service->addFactor($bullish2);
        $this->service->addFactor($bearish);
        $this->service->addFactor($oldFactor);
        
        // Filter by type
        $stocks = $this->service->filterFactors(['type' => 'stock']);
        $this->assertCount(3, $stocks);
        
        // Filter by bullish
        $bullishFactors = $this->service->filterFactors(['bullish' => true]);
        $this->assertCount(2, $bullishFactors);
        
        // Filter by bearish
        $bearishFactors = $this->service->filterFactors(['bullish' => false]);
        $this->assertCount(1, $bearishFactors);
        
        // Filter by minimum strength
    $strongFactors = $this->service->filterFactors(['min_strength' => 0.6]);
    $this->assertCount(3, $strongFactors); // bullish1 (2%), bullish2 (3%), and bearish (-2%) have strength >= 0.6
        
        // Filter by age (exclude stale data)
        $freshFactors = $this->service->filterFactors(['max_age_minutes' => 60]);
        $this->assertCount(3, $freshFactors); // Excludes the 2-hour old factor
    }

    /**
     * Test sorting factors
     */
    public function testSortFactors(): void
    {
        $high = new MarketFactor('HIGH', 'High Performer', 'stock', 100.0, 5.0, 5.0);
        $medium = new MarketFactor('MEDIUM', 'Medium Performer', 'stock', 100.0, 2.0, 2.0);
        $low = new MarketFactor('LOW', 'Low Performer', 'stock', 100.0, -3.0, -3.0);
        
        $factors = [$medium, $low, $high]; // Mixed order
        
        // Sort by change_percent descending (default)
        $sortedDesc = $this->service->sortFactors($factors);
        $this->assertEquals('HIGH', $sortedDesc[0]->getSymbol());
        $this->assertEquals('MEDIUM', $sortedDesc[1]->getSymbol());
        $this->assertEquals('LOW', $sortedDesc[2]->getSymbol());
        
        // Sort by change_percent ascending
        $sortedAsc = $this->service->sortFactors($factors, 'change_percent', 'asc');
        $this->assertEquals('LOW', $sortedAsc[0]->getSymbol());
        $this->assertEquals('MEDIUM', $sortedAsc[1]->getSymbol());
        $this->assertEquals('HIGH', $sortedAsc[2]->getSymbol());
        
        // Sort by symbol
        $sortedBySymbol = $this->service->sortFactors($factors, 'symbol', 'asc');
        $this->assertEquals('HIGH', $sortedBySymbol[0]->getSymbol());
        $this->assertEquals('LOW', $sortedBySymbol[1]->getSymbol());
        $this->assertEquals('MEDIUM', $sortedBySymbol[2]->getSymbol());
    }

    /**
     * Test top and worst performers
     */
    public function testTopAndWorstPerformers(): void
    {
        $this->service->addFactor(new MarketFactor('TOP1', 'Top 1', 'stock', 100.0, 5.0, 5.0));
        $this->service->addFactor(new MarketFactor('TOP2', 'Top 2', 'stock', 100.0, 3.0, 3.0));
        $this->service->addFactor(new MarketFactor('BOTTOM1', 'Bottom 1', 'stock', 100.0, -4.0, -4.0));
        $this->service->addFactor(new MarketFactor('BOTTOM2', 'Bottom 2', 'stock', 100.0, -2.0, -2.0));
        
        $topPerformers = $this->service->getTopPerformers(2);
        $worstPerformers = $this->service->getWorstPerformers(2);
        
        $this->assertCount(2, $topPerformers);
        $this->assertCount(2, $worstPerformers);
        
        $this->assertEquals('TOP1', $topPerformers[0]->getSymbol());
        $this->assertEquals('TOP2', $topPerformers[1]->getSymbol());
        
        $this->assertEquals('BOTTOM1', $worstPerformers[0]->getSymbol());
        $this->assertEquals('BOTTOM2', $worstPerformers[1]->getSymbol());
    }

    /**
     * Test market sentiment calculation
     */
    public function testGetMarketSentiment(): void
    {
        // Add factors with known sentiment
        $this->service->addFactor(new MarketFactor('BULL1', 'Bullish 1', 'stock', 100.0, 2.0, 2.0));
        $this->service->addFactor(new MarketFactor('BULL2', 'Bullish 2', 'stock', 100.0, 3.0, 3.0));
        $this->service->addFactor(new MarketFactor('BULL3', 'Bullish 3', 'stock', 100.0, 1.0, 1.0));
        $this->service->addFactor(new MarketFactor('BEAR1', 'Bearish 1', 'stock', 100.0, -2.0, -2.0));
        $this->service->addFactor(new MarketFactor('NEUTRAL', 'Neutral', 'stock', 100.0, 0.0, 0.0));
        
        $sentiment = $this->service->getMarketSentiment();
        
        $this->assertIsArray($sentiment);
        $this->assertArrayHasKey('sentiment', $sentiment);
        $this->assertArrayHasKey('confidence', $sentiment);
        $this->assertArrayHasKey('bullish_factors', $sentiment);
        $this->assertArrayHasKey('bearish_factors', $sentiment);
        $this->assertArrayHasKey('neutral_factors', $sentiment);
        $this->assertArrayHasKey('total_factors', $sentiment);
        $this->assertArrayHasKey('bullish_ratio', $sentiment);
        
    $this->assertEquals(3, $sentiment['bullish_factors']);
    $this->assertEquals(1, $sentiment['bearish_factors']);
    $this->assertEquals(1, $sentiment['neutral_factors']);
    $this->assertEquals(5, $sentiment['total_factors']);
    $this->assertEquals(0.6, $sentiment['bullish_ratio']); // 3/5 = 0.6
    $this->assertEquals('neutral', $sentiment['sentiment']); // == 0.6 ratio is neutral
    }

    /**
     * Test correlation management
     */
    public function testCorrelationManagement(): void
    {
        $this->service->setCorrelation('AAPL', 'MSFT', 0.75);
        $this->service->setCorrelation('AAPL', 'GOOGL', -0.25);
        
        $correlation1 = $this->service->analyzeCorrelation('AAPL', 'MSFT');
        $correlation2 = $this->service->analyzeCorrelation('AAPL', 'GOOGL');
        $correlation3 = $this->service->analyzeCorrelation('AAPL', 'UNKNOWN');
        
        $this->assertEquals(0.75, $correlation1);
        $this->assertEquals(-0.25, $correlation2);
        $this->assertNull($correlation3);
        
        // Test symmetric correlation
        $symmetricCorrelation = $this->service->analyzeCorrelation('MSFT', 'AAPL');
        $this->assertEquals(0.75, $symmetricCorrelation);
    }

    /**
     * Test correlated factors retrieval
     */
    public function testGetCorrelatedFactors(): void
    {
        $this->service->addFactor(new MarketFactor('AAPL', 'Apple', 'stock', 150.0));
        $this->service->addFactor(new MarketFactor('MSFT', 'Microsoft', 'stock', 280.0));
        $this->service->addFactor(new MarketFactor('GOOGL', 'Google', 'stock', 2500.0));
        
        $this->service->setCorrelation('AAPL', 'MSFT', 0.85);
        $this->service->setCorrelation('AAPL', 'GOOGL', 0.45);
        
        $correlatedFactors = $this->service->getCorrelatedFactors('AAPL', 0.7);
        
        $this->assertCount(1, $correlatedFactors); // Only MSFT has correlation >= 0.7
        $this->assertEquals('MSFT', $correlatedFactors[0]['symbol']);
        $this->assertEquals(0.85, $correlatedFactors[0]['correlation']);
        $this->assertInstanceOf(MarketFactor::class, $correlatedFactors[0]['factor']);
    }

    /**
     * Test updating factor values
     */
    public function testUpdateFactor(): void
    {
        $factor = new MarketFactor('AAPL', 'Apple', 'stock', 150.0);
        $this->service->addFactor($factor);
        
        $updateTime = new \DateTime('2023-06-15 10:30:00');
        $success = $this->service->updateFactor('AAPL', 155.5, $updateTime);
        
        $this->assertTrue($success);
        
        $updatedFactor = $this->service->getFactor('AAPL');
        $this->assertEquals(155.5, $updatedFactor->getValue());
        $this->assertEquals(5.5, $updatedFactor->getChange());
        $this->assertEqualsWithDelta(3.67, $updatedFactor->getChangePercent(), 0.01);
        $this->assertEquals($updateTime, $updatedFactor->getTimestamp());
        
        // Test updating non-existent factor
        $failureResult = $this->service->updateFactor('NONEXISTENT', 100.0);
        $this->assertFalse($failureResult);
    }

    /**
     * Test removing stale factors
     */
    public function testRemoveStaleFactors(): void
    {
        $fresh = new MarketFactor('FRESH', 'Fresh Factor', 'stock', 100.0);
        $stale = new MarketFactor('STALE', 'Stale Factor', 'stock', 100.0, 0.0, 0.0, new \DateTime('-3 hours'));
        
        $this->service->addFactor($fresh);
        $this->service->addFactor($stale);
        
        $this->assertCount(2, $this->service->getAllFactors());
        
        $removedCount = $this->service->removeStaleFactors(60); // Remove factors older than 1 hour
        
        $this->assertEquals(1, $removedCount);
        $this->assertCount(1, $this->service->getAllFactors());
        $this->assertNotNull($this->service->getFactor('FRESH'));
        $this->assertNull($this->service->getFactor('STALE'));
    }

    /**
     * Test market summary
     */
    public function testGetMarketSummary(): void
    {
        // Add various types of factors
        $this->service->addFactor(new SectorPerformance('XLK', 'Technology', 2.5, 1.0, 2.1));
        $this->service->addFactor(new IndexPerformance('SPY', 'S&P 500', 'US', 4150.0, 10.0, 1.5));
        $this->service->addFactor(new ForexRate('USD', 'CAD', 1.35, 0.02, 1.0));
        $this->service->addFactor(new EconomicIndicator('US_GDP', 'GDP Growth', 'US', 3.2, 3.0));
        
        $summary = $this->service->getMarketSummary();
        
        $this->assertIsArray($summary);
        $this->assertArrayHasKey('timestamp', $summary);
        $this->assertArrayHasKey('sentiment', $summary);
        $this->assertArrayHasKey('top_performers', $summary);
        $this->assertArrayHasKey('worst_performers', $summary);
        $this->assertArrayHasKey('sectors', $summary);
        $this->assertArrayHasKey('indices', $summary);
        $this->assertArrayHasKey('forex', $summary);
        $this->assertArrayHasKey('economics', $summary);
        $this->assertArrayHasKey('total_factors', $summary);
        $this->assertArrayHasKey('fresh_data_count', $summary);
        
        $this->assertEquals(4, $summary['total_factors']);
        $this->assertInstanceOf(\DateTime::class, $summary['timestamp']);
    }

    /**
     * Test import/export functionality
     */
    public function testImportExport(): void
    {
        $factor1 = new MarketFactor('AAPL', 'Apple', 'stock', 150.0, 2.0, 1.35);
        $factor2 = new MarketFactor('SPY', 'S&P 500', 'index', 4150.0, 50.0, 1.2);
        
        $this->service->addFactor($factor1);
        $this->service->addFactor($factor2);
        
        // Export
        $exportData = $this->service->exportToArray();
        $this->assertIsArray($exportData);
        $this->assertCount(2, $exportData);
        
        // Clear service
        $newService = new MarketFactorsService();
        $this->assertCount(0, $newService->getAllFactors());
        
        // Import
        $newService->importFromArray($exportData);
        $importedFactors = $newService->getAllFactors();
        
        $this->assertCount(2, $importedFactors);
        $this->assertArrayHasKey('AAPL', $importedFactors);
        $this->assertArrayHasKey('SPY', $importedFactors);
    }

    /**
     * Test empty service scenarios
     */
    public function testEmptyServiceScenarios(): void
    {
        $sentiment = $this->service->getMarketSentiment();
        $this->assertEquals('neutral', $sentiment['sentiment']);
        $this->assertEquals(0, $sentiment['confidence']);
        $this->assertEquals(0, $sentiment['total_factors']);
        
        $topPerformers = $this->service->getTopPerformers(5);
        $this->assertCount(0, $topPerformers);
        
        $worstPerformers = $this->service->getWorstPerformers(5);
        $this->assertCount(0, $worstPerformers);
        
        $correlatedFactors = $this->service->getCorrelatedFactors('NONEXISTENT');
        $this->assertCount(0, $correlatedFactors);
    }
}
