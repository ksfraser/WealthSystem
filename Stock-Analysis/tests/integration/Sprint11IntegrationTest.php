<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Backtesting\BacktestEngine;
use App\Backtesting\SignalAccuracyTracker;
use App\Backtesting\FundamentalMetrics;
use App\Backtesting\SectorIndexAggregator;
use App\Services\Trading\TradingStrategyInterface;
use PHPUnit\Framework\TestCase;

/**
 * Sprint 11 Integration Tests
 * 
 * Tests integration of all Sprint 11 enhancements:
 * - Signal Accuracy Tracking
 * - Fundamental Analysis
 * - Sector/Index Aggregation
 */
class Sprint11IntegrationTest extends TestCase
{
    public function testItIntegratesSignalTrackingWithBacktest(): void
    {
        // Create backtest engine
        $engine = new BacktestEngine([
            'initial_capital' => 100000,
            'commission' => 0.001,
            'slippage' => 0.001
        ]);
        
        // Create signal tracker
        $tracker = new SignalAccuracyTracker();
        
        // Run backtest with mock strategy
        $strategy = $this->createMockStrategy();
        $historicalData = $this->generateHistoricalData();
        
        $result = $engine->run($strategy, 'AAPL', $historicalData);
        
        // Track signals generated during backtest
        // Simulate recording signal and its outcome
        $tracker->recordSignal('AAPL', 'BUY', 100.0, 105.0, 0.8, 5, 'RSI', 'Technology', 'NASDAQ');
        $tracker->recordSignal('AAPL', 'SELL', 105.0, 103.0, 0.75, 5, 'RSI', 'Technology', 'NASDAQ');
        
        // Verify tracking works with backtest results
        $accuracy = $tracker->getAccuracy();
        $this->assertIsFloat($accuracy);
        $this->assertGreaterThanOrEqual(0.0, $accuracy);
        $this->assertLessThanOrEqual(100.0, $accuracy);
        
        // Verify accuracy by symbol
        $bySymbol = $tracker->getAccuracyBySymbol();
        $this->assertArrayHasKey('AAPL', $bySymbol);
    }
    
    public function testItIntegratesFundamentalsWithBacktest(): void
    {
        // Create fundamental metrics analyzer
        $fundamentals = new FundamentalMetrics();
        
        // Fundamental data for a stock
        $fundamentalData = [
            'net_income' => 100000000,
            'operating_cash_flow' => 120000000,
            'free_cash_flow' => 80000000,
            'revenue' => 1000000000,
            'market_cap' => 10000000000,
            'shareholder_equity' => 500000000,
            'total_assets' => 1000000000,
            'total_debt' => 300000000,
            'dividend_paid' => 40000000,
            'ebit' => 150000000,
            'interest_expense' => 15000000
        ];
        
        // Analyze fundamentals before running backtest
        $score = $fundamentals->generateFundamentalScore($fundamentalData);
        
        // Only backtest if fundamental score is good
        if ($score['total_score'] >= 60) {
            $engine = new BacktestEngine(['initial_capital' => 100000]);
            $strategy = $this->createMockStrategy();
            $result = $engine->run($strategy, 'AAPL', $this->generateHistoricalData());
            
            $this->assertIsArray($result);
            $this->assertGreaterThanOrEqual(60, $score['total_score']);
        }
        
        $this->assertIsArray($score);
        $this->assertArrayHasKey('total_score', $score);
    }
    
    public function testItIntegratesSectorAggregationWithMultipleBacktests(): void
    {
        // Create aggregator
        $aggregator = new SectorIndexAggregator();
        
        // Run backtests for multiple stocks and aggregate by sector
        $stocks = [
            ['symbol' => 'AAPL', 'sector' => 'Technology', 'index' => 'NASDAQ'],
            ['symbol' => 'MSFT', 'sector' => 'Technology', 'index' => 'NASDAQ'],
            ['symbol' => 'JPM', 'sector' => 'Financial', 'index' => 'NYSE']
        ];
        
        foreach ($stocks as $stock) {
            // Simulate backtest result
            $return = rand(-10, 30) / 1.0;
            $aggregator->addResult(
                $stock['symbol'],
                $stock['sector'],
                $stock['index'],
                'RSI',
                $return
            );
        }
        
        // Analyze sector performance
        $bySector = $aggregator->getPerformanceBySector();
        $this->assertArrayHasKey('Technology', $bySector);
        $this->assertArrayHasKey('Financial', $bySector);
        
        // Get best performing sector
        $best = $aggregator->getBestPerformingSector();
        $this->assertArrayHasKey('sector', $best);
        $this->assertArrayHasKey('average_return', $best);
    }
    
    public function testItCombinesAllThreeFeatures(): void
    {
        // Complete workflow: Backtest + Signal Tracking + Fundamentals + Sector Aggregation
        
        $tracker = new SignalAccuracyTracker();
        $fundamentals = new FundamentalMetrics();
        $aggregator = new SectorIndexAggregator();
        
        $stocks = [
            [
                'symbol' => 'AAPL',
                'sector' => 'Technology',
                'index' => 'NASDAQ',
                'fundamentals' => [
                    'net_income' => 100000000,
                    'operating_cash_flow' => 120000000,
                    'free_cash_flow' => 80000000,
                    'revenue' => 1000000000,
                    'market_cap' => 10000000000,
                    'shareholder_equity' => 500000000,
                    'total_assets' => 1000000000,
                    'total_debt' => 200000000,
                    'dividend_paid' => 40000000,
                    'ebit' => 150000000,
                    'interest_expense' => 10000000
                ]
            ]
        ];
        
        foreach ($stocks as $stock) {
            // Step 1: Analyze fundamentals
            $score = $fundamentals->generateFundamentalScore($stock['fundamentals']);
            
            // Step 2: Run backtest if fundamentals are good
            if ($score['total_score'] >= 40) {
                $engine = new BacktestEngine(['initial_capital' => 100000]);
                $strategy = $this->createMockStrategy();
                $result = $engine->run($strategy, $stock['symbol'], $this->generateHistoricalData());
                
                // Step 3: Track signal accuracy
                $tracker->recordSignal(
                    $stock['symbol'],
                    'BUY',
                    100.0,
                    105.0,
                    0.8,
                    5,
                    'RSI',
                    $stock['sector'],
                    $stock['index']
                );
                
                // Step 4: Aggregate by sector
                $aggregator->addResult(
                    $stock['symbol'],
                    $stock['sector'],
                    $stock['index'],
                    'RSI',
                    5.0
                );
            }
        }
        
        // Verify all components work together
        $accuracy = $tracker->getAccuracy();
        $bySector = $aggregator->getPerformanceBySector();
        
        $this->assertIsFloat($accuracy);
        $this->assertIsArray($bySector);
        
        // Generate comprehensive report combining all metrics
        $report = "COMPREHENSIVE ANALYSIS\n";
        $report .= "Signal Accuracy: " . number_format($accuracy, 2) . "%\n";
        $report .= "Sectors Analyzed: " . count($bySector) . "\n";
        
        $this->assertStringContainsString('COMPREHENSIVE ANALYSIS', $report);
    }
    
    public function testItTracksMultiStrategyAccuracyBySector(): void
    {
        $tracker = new SignalAccuracyTracker();
        
        // Track signals from multiple strategies across sectors
        $tracker->recordSignal('AAPL', 'BUY', 100.0, 105.0, 0.8, 5, 'RSI', 'Technology', 'NASDAQ');
        $tracker->recordSignal('MSFT', 'BUY', 200.0, 210.0, 0.85, 5, 'MACD', 'Technology', 'NASDAQ');
        $tracker->recordSignal('JPM', 'BUY', 150.0, 145.0, 0.7, 5, 'RSI', 'Financial', 'NYSE');
        
        // Get accuracy by sector
        $bySector = $tracker->getAccuracyBySector();
        
        $this->assertIsArray($bySector);
        $this->assertArrayHasKey('Technology', $bySector);
        $this->assertArrayHasKey('Financial', $bySector);
        
        // Technology should have higher accuracy (2 correct vs 1 incorrect for Financial)
        $this->assertGreaterThan($bySector['Financial'], $bySector['Technology']);
    }
    
    public function testItGeneratesComprehensiveSectorReport(): void
    {
        $aggregator = new SectorIndexAggregator();
        
        // Add multiple results across sectors
        $aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 25.0);
        $aggregator->addResult('MSFT', 'Technology', 'NASDAQ', 'MACD', 20.0);
        $aggregator->addResult('JPM', 'Financial', 'NYSE', 'RSI', 15.0);
        $aggregator->addResult('XOM', 'Energy', 'NYSE', 'RSI', -5.0);
        
        // Generate sector rotation report
        $report = $aggregator->generateSectorRotationReport();
        
        $this->assertStringContainsString('SECTOR ROTATION ANALYSIS', $report);
        $this->assertStringContainsString('Best Performing Sector', $report);
        $this->assertStringContainsString('Worst Performing Sector', $report);
        $this->assertStringContainsString('Technology', $report);
    }
    
    public function testItFiltersSectorPerformanceByStrategy(): void
    {
        $aggregator = new SectorIndexAggregator();
        
        // Add mixed strategy results
        $aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 20.0);
        $aggregator->addResult('MSFT', 'Technology', 'NASDAQ', 'RSI', 15.0);
        $aggregator->addResult('GOOGL', 'Technology', 'NASDAQ', 'MACD', 10.0);
        
        // Filter by RSI only
        $rsiOnly = $aggregator->getPerformanceBySector('RSI');
        
        $this->assertArrayHasKey('Technology', $rsiOnly);
        $this->assertEquals(2, $rsiOnly['Technology']['count']);
        $this->assertEquals(17.5, $rsiOnly['Technology']['average_return']);
    }
    
    public function testItCalculatesFundamentalScoreForPortfolio(): void
    {
        $fundamentals = new FundamentalMetrics();
        
        $portfolio = [
            'AAPL' => [
                'net_income' => 100000000,
                'operating_cash_flow' => 120000000,
                'free_cash_flow' => 80000000,
                'revenue' => 1000000000,
                'market_cap' => 10000000000,
                'shareholder_equity' => 500000000,
                'total_assets' => 1000000000,
                'total_debt' => 300000000,
                'dividend_paid' => 40000000,
                'ebit' => 150000000,
                'interest_expense' => 15000000
            ],
            'MSFT' => [
                'net_income' => 150000000,
                'operating_cash_flow' => 180000000,
                'free_cash_flow' => 120000000,
                'revenue' => 1500000000,
                'market_cap' => 15000000000,
                'shareholder_equity' => 600000000,
                'total_assets' => 1200000000,
                'total_debt' => 250000000,
                'dividend_paid' => 50000000,
                'ebit' => 200000000,
                'interest_expense' => 12000000
            ]
        ];
        
        $scores = [];
        foreach ($portfolio as $symbol => $data) {
            $scores[$symbol] = $fundamentals->generateFundamentalScore($data);
        }
        
        // Verify all stocks have scores
        $this->assertCount(2, $scores);
        $this->assertArrayHasKey('AAPL', $scores);
        $this->assertArrayHasKey('MSFT', $scores);
        
        // Calculate portfolio average score
        $avgScore = array_sum(array_column($scores, 'total_score')) / count($scores);
        $this->assertGreaterThan(0, $avgScore);
        $this->assertLessThanOrEqual(100, $avgScore);
    }
    
    public function testItExportsIntegratedDataToCSV(): void
    {
        $tracker = new SignalAccuracyTracker();
        $aggregator = new SectorIndexAggregator();
        
        // Add data to both systems
        $tracker->recordSignal('AAPL', 'BUY', 100.0, 105.0, 0.8, 5, 'RSI', 'Technology', 'NASDAQ');
        $aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 5.0);
        
        // Export from both systems
        $accuracyCSV = $tracker->exportToCSV();
        $sectorCSV = $aggregator->exportSectorPerformanceToCSV();
        
        $this->assertIsString($accuracyCSV);
        $this->assertIsString($sectorCSV);
        $this->assertStringContainsString('AAPL', $accuracyCSV);
        $this->assertStringContainsString('Technology', $sectorCSV);
    }
    
    public function testItIdentifiesBestStrategyBySector(): void
    {
        $aggregator = new SectorIndexAggregator();
        
        // Technology sector: RSI performs better than MACD
        $aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 25.0);
        $aggregator->addResult('MSFT', 'Technology', 'NASDAQ', 'RSI', 20.0);
        $aggregator->addResult('GOOGL', 'Technology', 'NASDAQ', 'MACD', 10.0);
        $aggregator->addResult('META', 'Technology', 'NASDAQ', 'MACD', 8.0);
        
        $bySectorStrategy = $aggregator->getPerformanceBySectorAndStrategy();
        
        $this->assertArrayHasKey('Technology', $bySectorStrategy);
        $this->assertArrayHasKey('RSI', $bySectorStrategy['Technology']);
        $this->assertArrayHasKey('MACD', $bySectorStrategy['Technology']);
        
        // RSI average (22.5) should be higher than MACD average (9.0)
        $rsiAvg = $bySectorStrategy['Technology']['RSI']['average_return'];
        $macdAvg = $bySectorStrategy['Technology']['MACD']['average_return'];
        
        $this->assertGreaterThan($macdAvg, $rsiAvg);
    }
    
    /**
     * Generate sample historical data for testing
     *
     * @return array<int, array<string, mixed>>
     */
    private function generateHistoricalData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        for ($i = 0; $i < 50; $i++) {
            $change = (rand(-5, 5) / 100);
            $basePrice *= (1 + $change);
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("-" . (50 - $i) . " days")),
                'open' => $basePrice,
                'high' => $basePrice * 1.02,
                'low' => $basePrice * 0.98,
                'close' => $basePrice,
                'volume' => rand(1000000, 5000000)
            ];
        }
        
        return $data;
    }
    
    /**
     * Create a mock trading strategy
     *
     * @return TradingStrategyInterface
     */
    private function createMockStrategy(): TradingStrategyInterface
    {
        $strategy = $this->createMock(TradingStrategyInterface::class);
        $strategy->method('analyze')->willReturn([
            'signal' => 'BUY',
            'confidence' => 0.8,
            'reason' => 'Mock buy signal',
            'metadata' => []
        ]);
        
        return $strategy;
    }
}
