<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\MarketFactorsService;

/**
 * Tests for MarketFactorsService
 * 
 * @covers \App\Services\MarketFactorsService
 */
class MarketFactorsServiceTest extends TestCase
{
    private MarketFactorsService $service;
    private string $testStoragePath;

    protected function setUp(): void
    {
        $this->testStoragePath = __DIR__ . '/../../storage/test_market_factors_' . time();
        $this->service = new MarketFactorsService($this->testStoragePath);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->testStoragePath);
    }

    public function testTrackIndicatorPrediction(): void
    {
        $predictionId = $this->service->trackIndicatorPrediction(
            'AAPL',
            'rsi',
            'BUY',
            0.85,
            ['rsi_value' => 35, 'price' => 150.00]
        );

        $this->assertNotEmpty($predictionId);
        $this->assertStringContainsString('rsi', $predictionId);
        $this->assertStringContainsString('AAPL', $predictionId);
    }

    public function testUpdateIndicatorAccuracy(): void
    {
        $predictionId = $this->service->trackIndicatorPrediction(
            'AAPL',
            'macd',
            'BUY',
            0.80
        );

        $result = $this->service->updateIndicatorAccuracy(
            $predictionId,
            'CORRECT',
            ['gain' => 0.05]
        );

        $this->assertTrue($result);
    }

    public function testUpdateIndicatorAccuracyReturnsFalseForInvalidId(): void
    {
        $result = $this->service->updateIndicatorAccuracy(
            'invalid_id',
            'CORRECT'
        );

        $this->assertFalse($result);
    }

    public function testCalculatePredictionAccuracy(): void
    {
        // Track predictions
        $pred1 = $this->service->trackIndicatorPrediction('AAPL', 'rsi', 'BUY', 0.85);
        $pred2 = $this->service->trackIndicatorPrediction('MSFT', 'rsi', 'SELL', 0.75);
        $pred3 = $this->service->trackIndicatorPrediction('GOOGL', 'rsi', 'BUY', 0.90);

        // Update outcomes
        $this->service->updateIndicatorAccuracy($pred1, 'CORRECT');
        $this->service->updateIndicatorAccuracy($pred2, 'INCORRECT');

        $accuracy = $this->service->calculatePredictionAccuracy('rsi');

        $this->assertEquals(3, $accuracy['total_predictions']);
        $this->assertEquals(1, $accuracy['correct']);
        $this->assertEquals(1, $accuracy['incorrect']);
        $this->assertEquals(1, $accuracy['pending']);
        $this->assertEquals(0.5, $accuracy['accuracy']); // 1 correct out of 2 completed
    }

    public function testCalculatePredictionAccuracyWithPartial(): void
    {
        $pred1 = $this->service->trackIndicatorPrediction('AAPL', 'macd', 'BUY', 0.80);
        $pred2 = $this->service->trackIndicatorPrediction('MSFT', 'macd', 'SELL', 0.70);

        $this->service->updateIndicatorAccuracy($pred1, 'CORRECT');
        $this->service->updateIndicatorAccuracy($pred2, 'PARTIAL');

        $accuracy = $this->service->calculatePredictionAccuracy('macd');

        $this->assertEquals(2, $accuracy['total_predictions']);
        $this->assertEquals(1, $accuracy['correct']);
        $this->assertEquals(1, $accuracy['partial']);
        $this->assertEquals(0.75, $accuracy['accuracy']); // 1 + (0.5 * 1) / 2 = 0.75
    }

    public function testGetIndicatorAccuracy(): void
    {
        $pred = $this->service->trackIndicatorPrediction('AAPL', 'rsi', 'BUY', 0.85);
        $this->service->updateIndicatorAccuracy($pred, 'CORRECT');

        $accuracy = $this->service->getIndicatorAccuracy('rsi');

        $this->assertArrayHasKey('accuracy', $accuracy);
        $this->assertArrayHasKey('total_predictions', $accuracy);
        $this->assertEquals('rsi', $accuracy['indicator']);
    }

    public function testGetIndicatorPerformanceScore(): void
    {
        $pred1 = $this->service->trackIndicatorPrediction('AAPL', 'rsi', 'BUY', 0.90);
        $pred2 = $this->service->trackIndicatorPrediction('MSFT', 'rsi', 'SELL', 0.85);

        $this->service->updateIndicatorAccuracy($pred1, 'CORRECT');
        $this->service->updateIndicatorAccuracy($pred2, 'CORRECT');

        $score = $this->service->getIndicatorPerformanceScore('rsi');

        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(1.0, $score);
    }

    public function testGetAllIndicatorPerformance(): void
    {
        $pred1 = $this->service->trackIndicatorPrediction('AAPL', 'rsi', 'BUY', 0.85);
        $pred2 = $this->service->trackIndicatorPrediction('MSFT', 'macd', 'SELL', 0.80);

        $this->service->updateIndicatorAccuracy($pred1, 'CORRECT');
        $this->service->updateIndicatorAccuracy($pred2, 'CORRECT');

        $performance = $this->service->getAllIndicatorPerformance();

        $this->assertIsArray($performance);
        $this->assertArrayHasKey('rsi', $performance);
        $this->assertArrayHasKey('macd', $performance);
    }

    public function testCalculateWeightedScore(): void
    {
        // Setup performance
        $pred1 = $this->service->trackIndicatorPrediction('AAPL', 'rsi', 'BUY', 0.90);
        $pred2 = $this->service->trackIndicatorPrediction('AAPL', 'macd', 'BUY', 0.85);
        $this->service->updateIndicatorAccuracy($pred1, 'CORRECT');
        $this->service->updateIndicatorAccuracy($pred2, 'CORRECT');

        $signals = [
            'rsi' => ['prediction' => 'BUY', 'confidence' => 0.85],
            'macd' => ['prediction' => 'BUY', 'confidence' => 0.80]
        ];

        $score = $this->service->calculateWeightedScore($signals);

        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(1.0, $score);
    }

    public function testCalculateWeightedScoreWithMixedSignals(): void
    {
        $pred1 = $this->service->trackIndicatorPrediction('AAPL', 'rsi', 'BUY', 0.90);
        $pred2 = $this->service->trackIndicatorPrediction('AAPL', 'macd', 'SELL', 0.85);
        $this->service->updateIndicatorAccuracy($pred1, 'CORRECT');
        $this->service->updateIndicatorAccuracy($pred2, 'CORRECT');

        $signals = [
            'rsi' => ['prediction' => 'BUY', 'confidence' => 0.85],
            'macd' => ['prediction' => 'SELL', 'confidence' => 0.80]
        ];

        $score = $this->service->calculateWeightedScore($signals);

        $this->assertGreaterThanOrEqual(-1.0, $score);
        $this->assertLessThanOrEqual(1.0, $score);
    }

    public function testGenerateRecommendation(): void
    {
        $signals = [
            'rsi' => ['prediction' => 'BUY', 'confidence' => 0.85],
            'macd' => ['prediction' => 'BUY', 'confidence' => 0.80]
        ];

        $recommendation = $this->service->generateRecommendation('AAPL', $signals);

        $this->assertArrayHasKey('signal', $recommendation);
        $this->assertArrayHasKey('confidence', $recommendation);
        $this->assertArrayHasKey('weighted_score', $recommendation);
        $this->assertEquals('AAPL', $recommendation['symbol']);
        $this->assertContains($recommendation['signal'], ['BUY', 'SELL', 'HOLD']);
    }

    public function testGenerateRecommendationBuySignal(): void
    {
        // Setup high-performing indicators
        $pred1 = $this->service->trackIndicatorPrediction('TEST', 'rsi', 'BUY', 0.90);
        $pred2 = $this->service->trackIndicatorPrediction('TEST', 'macd', 'BUY', 0.85);
        $this->service->updateIndicatorAccuracy($pred1, 'CORRECT');
        $this->service->updateIndicatorAccuracy($pred2, 'CORRECT');

        $signals = [
            'rsi' => ['prediction' => 'BUY', 'confidence' => 0.90],
            'macd' => ['prediction' => 'BUY', 'confidence' => 0.85],
            'moving_average' => ['prediction' => 'BUY', 'confidence' => 0.80]
        ];

        $recommendation = $this->service->generateRecommendation('AAPL', $signals);

        $this->assertEquals('BUY', $recommendation['signal']);
        $this->assertGreaterThan(0, $recommendation['confidence']);
    }

    public function testCalculateConfidence(): void
    {
        $pred = $this->service->trackIndicatorPrediction('AAPL', 'rsi', 'BUY', 0.90);
        $this->service->updateIndicatorAccuracy($pred, 'CORRECT');

        $signals = [
            'rsi' => ['prediction' => 'BUY', 'confidence' => 0.85]
        ];

        $confidence = $this->service->calculateConfidence($signals, 0.8);

        $this->assertGreaterThan(0, $confidence);
        $this->assertLessThanOrEqual(1.0, $confidence);
    }

    public function testCalculateRiskLevel(): void
    {
        $recommendation = [
            'signal' => 'BUY',
            'confidence' => 0.85
        ];

        $marketContext = ['volatility' => 0.3];

        $riskLevel = $this->service->calculateRiskLevel($recommendation, $marketContext);

        $this->assertContains($riskLevel, ['LOW', 'MEDIUM', 'HIGH']);
    }

    public function testCalculateRiskLevelLowConfidenceHighRisk(): void
    {
        $recommendation = ['signal' => 'BUY', 'confidence' => 0.3];
        $marketContext = ['volatility' => 0.8];

        $riskLevel = $this->service->calculateRiskLevel($recommendation, $marketContext);

        $this->assertEquals('HIGH', $riskLevel);
    }

    public function testCalculateRiskLevelHighConfidenceLowRisk(): void
    {
        $recommendation = ['signal' => 'BUY', 'confidence' => 0.9];
        $marketContext = ['volatility' => 0.2];

        $riskLevel = $this->service->calculateRiskLevel($recommendation, $marketContext);

        $this->assertEquals('LOW', $riskLevel);
    }

    public function testGetMarketSummary(): void
    {
        $symbols = [
            'AAPL' => ['signal' => 'BUY', 'confidence' => 0.85],
            'MSFT' => ['signal' => 'BUY', 'confidence' => 0.80],
            'GOOGL' => ['signal' => 'SELL', 'confidence' => 0.75],
            'AMZN' => ['signal' => 'HOLD', 'confidence' => 0.60]
        ];

        $summary = $this->service->getMarketSummary($symbols);

        $this->assertEquals(4, $summary['total_symbols']);
        $this->assertEquals(2, $summary['buy_signals']);
        $this->assertEquals(1, $summary['sell_signals']);
        $this->assertEquals(1, $summary['hold_signals']);
        $this->assertArrayHasKey('sentiment', $summary);
        $this->assertArrayHasKey('avg_confidence', $summary);
    }

    public function testGetSectorSummary(): void
    {
        $symbolData = [
            'AAPL' => ['signal' => 'BUY', 'confidence' => 0.85],
            'MSFT' => ['signal' => 'BUY', 'confidence' => 0.80]
        ];

        $summary = $this->service->getSectorSummary('Technology', $symbolData);

        $this->assertEquals('Technology', $summary['sector']);
        $this->assertEquals(2, $summary['total_symbols']);
        $this->assertEquals(2, $summary['buy_signals']);
    }

    public function testGetIndexSummary(): void
    {
        $indexData = [
            'AAPL' => ['signal' => 'BUY', 'confidence' => 0.85],
            'MSFT' => ['signal' => 'SELL', 'confidence' => 0.75]
        ];

        $summary = $this->service->getIndexSummary('S&P 500', $indexData);

        $this->assertEquals('S&P 500', $summary['index']);
        $this->assertEquals(2, $summary['total_symbols']);
    }

    public function testGetForexSummary(): void
    {
        $forexPairs = [
            'EUR/USD' => ['signal' => 'BUY', 'confidence' => 0.80],
            'GBP/USD' => ['signal' => 'SELL', 'confidence' => 0.75]
        ];

        $summary = $this->service->getForexSummary($forexPairs);

        $this->assertEquals('forex', $summary['type']);
        $this->assertEquals(2, $summary['total_symbols']);
    }

    public function testGetEconomicsSummary(): void
    {
        $economicData = [
            'gdp_growth' => 2.5,
            'inflation' => 3.2,
            'unemployment' => 4.1
        ];

        $summary = $this->service->getEconomicsSummary($economicData);

        $this->assertArrayHasKey('indicators', $summary);
        $this->assertEquals($economicData, $summary['indicators']);
    }

    public function testTrackCorrelation(): void
    {
        $this->service->trackCorrelation('rsi', 'macd', 0.75);

        $matrix = $this->service->getCorrelationMatrix();

        $this->assertNotEmpty($matrix);
    }

    public function testGetCorrelationMatrix(): void
    {
        $this->service->trackCorrelation('rsi', 'macd', 0.75);
        $this->service->trackCorrelation('rsi', 'volume', 0.60);

        $matrix = $this->service->getCorrelationMatrix();

        $this->assertCount(2, $matrix);
    }

    public function testCalculateMarketSentimentBullish(): void
    {
        $signals = ['buy' => 60, 'sell' => 20, 'hold' => 20];

        $sentiment = $this->service->calculateMarketSentiment($signals);

        $this->assertEquals('BULLISH', $sentiment);
    }

    public function testCalculateMarketSentimentBearish(): void
    {
        $signals = ['buy' => 20, 'sell' => 60, 'hold' => 20];

        $sentiment = $this->service->calculateMarketSentiment($signals);

        $this->assertEquals('BEARISH', $sentiment);
    }

    public function testCalculateMarketSentimentNeutral(): void
    {
        $signals = ['buy' => 30, 'sell' => 30, 'hold' => 40];

        $sentiment = $this->service->calculateMarketSentiment($signals);

        $this->assertEquals('NEUTRAL', $sentiment);
    }

    public function testExportDataPredictions(): void
    {
        $this->service->trackIndicatorPrediction('AAPL', 'rsi', 'BUY', 0.85);

        $data = $this->service->exportData('predictions');

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    public function testExportDataPerformance(): void
    {
        $pred = $this->service->trackIndicatorPrediction('AAPL', 'rsi', 'BUY', 0.85);
        $this->service->updateIndicatorAccuracy($pred, 'CORRECT');

        $data = $this->service->exportData('performance');

        $this->assertIsArray($data);
    }

    public function testExportDataAll(): void
    {
        $this->service->trackIndicatorPrediction('AAPL', 'rsi', 'BUY', 0.85);

        $data = $this->service->exportData('all');

        $this->assertArrayHasKey('predictions', $data);
        $this->assertArrayHasKey('performance', $data);
        $this->assertArrayHasKey('correlations', $data);
    }

    public function testImportData(): void
    {
        $importData = [
            'test_pred' => [
                'symbol' => 'AAPL',
                'indicator' => 'rsi',
                'prediction' => 'BUY'
            ]
        ];

        $result = $this->service->importData($importData, 'predictions');

        $this->assertTrue($result);
    }

    public function testDataPersistence(): void
    {
        // Create first service instance
        $service1 = new MarketFactorsService($this->testStoragePath);
        $predictionId = $service1->trackIndicatorPrediction('AAPL', 'rsi', 'BUY', 0.85);

        // Create second service instance (should load saved data)
        $service2 = new MarketFactorsService($this->testStoragePath);
        $result = $service2->updateIndicatorAccuracy($predictionId, 'CORRECT');

        $this->assertTrue($result);
    }

    /**
     * Helper: Recursively delete directory
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }
}
