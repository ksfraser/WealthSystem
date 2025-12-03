<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Services\Trading\SmallCapCatalystStrategyService;
use App\Services\Trading\IPlaceStrategyService;
use App\Services\Trading\MeanReversionStrategyService;
use App\Services\Trading\QualityDividendStrategyService;
use App\Services\Trading\MomentumQualityStrategyService;
use App\Services\Trading\ContrarianStrategyService;
use App\Services\Trading\StrategyWeightingEngine;
use App\Services\Trading\StrategyPerformanceAnalyzer;
use App\Services\Trading\BacktestingFramework;
use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

/**
 * Integration Tests for Trading Strategy System
 * 
 * Tests the interaction between multiple trading strategies, weighting engine,
 * performance analyzer, and backtesting framework to ensure the complete system
 * works as an integrated unit.
 */
class TradingStrategyIntegrationTest extends TestCase
{
    private MarketDataService $marketDataService;
    private MarketDataRepositoryInterface $repository;
    private StrategyWeightingEngine $weightingEngine;
    private StrategyPerformanceAnalyzer $performanceAnalyzer;
    private BacktestingFramework $backtestingFramework;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock repository
        $this->repository = $this->createMock(MarketDataRepositoryInterface::class);
        $this->marketDataService = new MarketDataService($this->repository);
        
        // Initialize system components
        $this->weightingEngine = new StrategyWeightingEngine();
        $this->performanceAnalyzer = new StrategyPerformanceAnalyzer();
        $this->backtestingFramework = new BacktestingFramework(100000.0, 0.001, 0.0005);
    }
    
    /**
     * Test 1: Multi-Strategy Portfolio Analysis
     * Verifies that all 6 strategies can analyze the same symbol simultaneously
     * and produce consensus recommendations through the weighting engine.
     */
    public function testMultiStrategyPortfolioAnalysis(): void
    {
        // Initialize all 6 strategies
        $strategies = [
            'SmallCapCatalyst' => new SmallCapCatalystStrategyService($this->marketDataService, $this->repository),
            'IPlace' => new IPlaceStrategyService($this->marketDataService, $this->repository),
            'MeanReversion' => new MeanReversionStrategyService($this->marketDataService, $this->repository),
            'QualityDividend' => new QualityDividendStrategyService($this->marketDataService, $this->repository),
            'MomentumQuality' => new MomentumQualityStrategyService($this->marketDataService, $this->repository),
            'Contrarian' => new ContrarianStrategyService($this->marketDataService, $this->repository)
        ];
        
        // Add all strategies to weighting engine
        foreach ($strategies as $name => $strategy) {
            $this->weightingEngine->addStrategy($name, $strategy);
        }
        
        // Load balanced portfolio allocation profile
        $this->weightingEngine->loadProfile('balanced');
        
        // Mock market data for a test symbol
        $this->setupMockMarketData('TEST', [
            'current_price' => 50.0,
            'market_cap' => 500000000,
            'volume' => 1000000,
            'pe_ratio' => 15.0,
            'dividend_yield' => 0.03,
            'rsi' => 35,
            'price_52w_high' => 60.0,
            'price_52w_low' => 40.0
        ]);
        
        // Analyze symbol with all strategies
        $result = $this->weightingEngine->analyzeSymbol('TEST');
        
        // Assertions
        $this->assertIsArray($result);
        $this->assertArrayHasKey('action', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('weighted_confidence', $result);
        $this->assertArrayHasKey('consensus', $result);
        $this->assertArrayHasKey('individual_results', $result);
        
        // Verify all 6 strategies contributed
        $this->assertCount(6, $result['individual_results']);
        
        // Verify consensus action is valid
        $this->assertContains($result['action'], ['BUY', 'SELL', 'HOLD']);
        
        // Verify confidence is within valid range
        $this->assertGreaterThanOrEqual(0, $result['confidence']);
        $this->assertLessThanOrEqual(100, $result['confidence']);
    }
    
    /**
     * Test 2: Strategy Weighting Engine with Different Profiles
     * Tests that different allocation profiles produce different recommendations.
     */
    public function testStrategyWeightingWithMultipleProfiles(): void
    {
        $strategies = $this->initializeAllStrategies();
        
        foreach ($strategies as $name => $strategy) {
            $this->weightingEngine->addStrategy($name, $strategy);
        }
        
        $this->setupMockMarketData('TEST', [
            'current_price' => 25.0,
            'market_cap' => 100000000, // Small cap
            'volume' => 500000,
            'pe_ratio' => 12.0,
            'dividend_yield' => 0.05,
            'rsi' => 28 // Oversold
        ]);
        
        $profiles = ['conservative', 'balanced', 'aggressive', 'growth', 'value'];
        $results = [];
        
        foreach ($profiles as $profile) {
            $this->weightingEngine->loadProfile($profile);
            $results[$profile] = $this->weightingEngine->analyzeSymbol('TEST');
        }
        
        // Verify each profile produces a result
        $this->assertCount(5, $results);
        
        // Conservative should favor quality dividend and mean reversion
        $this->assertArrayHasKey('conservative', $results);
        
        // Aggressive should favor small cap catalyst and momentum
        $this->assertArrayHasKey('aggressive', $results);
        
        // Results should differ based on profile
        $conservativeConfidence = $results['conservative']['weighted_confidence'];
        $aggressiveConfidence = $results['aggressive']['weighted_confidence'];
        
        // Not all profiles should produce identical confidence
        // (unless the stock perfectly fits all criteria)
        $this->assertTrue(
            $conservativeConfidence !== $aggressiveConfidence ||
            $results['conservative']['action'] !== $results['aggressive']['action']
        );
    }
    
    /**
     * Test 3: End-to-End Backtesting with Multiple Strategies
     * Tests the complete workflow: strategies → analysis → backtesting → performance metrics.
     */
    public function testEndToEndBacktestingWorkflow(): void
    {
        $strategy = new MeanReversionStrategyService($this->marketDataService, $this->repository);
        
        // Create realistic historical data
        $historicalData = $this->generateHistoricalData('TEST', 100);
        
        // Run backtest
        $backtestResult = $this->backtestingFramework->runBacktest(
            $strategy,
            $historicalData,
            [
                'position_size' => 0.10,
                'stop_loss' => 0.10,
                'take_profit' => 0.20,
                'max_holding_days' => 30
            ]
        );
        
        // Verify backtest results structure
        $this->assertArrayHasKey('trades', $backtestResult);
        $this->assertArrayHasKey('metrics', $backtestResult);
        $this->assertArrayHasKey('equity_curve', $backtestResult);
        
        // Load trades into performance analyzer
        foreach ($backtestResult['trades'] as $trade) {
            $this->performanceAnalyzer->recordTrade('TEST', 'MeanReversion', $trade);
        }
        
        // Analyze performance
        $performance = $this->performanceAnalyzer->analyzeStrategy('MeanReversion');
        
        // Verify performance metrics
        $this->assertArrayHasKey('win_rate', $performance);
        $this->assertArrayHasKey('total_return', $performance);
        $this->assertArrayHasKey('sharpe_ratio', $performance);
        $this->assertArrayHasKey('max_drawdown', $performance);
        $this->assertArrayHasKey('profit_factor', $performance);
        
        // Verify metrics are within valid ranges
        $this->assertGreaterThanOrEqual(0, $performance['win_rate']);
        $this->assertLessThanOrEqual(1, $performance['win_rate']);
    }
    
    /**
     * Test 4: Portfolio-Level Backtesting with Strategy Weighting
     * Tests backtesting a portfolio with multiple strategies simultaneously.
     */
    public function testPortfolioBacktestingWithMultipleStrategies(): void
    {
        $strategies = [
            'MeanReversion' => new MeanReversionStrategyService($this->marketDataService, $this->repository),
            'MomentumQuality' => new MomentumQualityStrategyService($this->marketDataService, $this->repository)
        ];
        
        // Create historical data for multiple symbols
        $portfolioData = [
            'STOCK1' => $this->generateHistoricalData('STOCK1', 100),
            'STOCK2' => $this->generateHistoricalData('STOCK2', 100)
        ];
        
        // Run portfolio backtest
        $result = $this->backtestingFramework->runPortfolioBacktest(
            $strategies,
            $portfolioData,
            ['conservative' => 0.5, 'aggressive' => 0.5],
            [
                'max_positions' => 5,
                'position_size' => 0.15,
                'rebalance_frequency' => 30
            ]
        );
        
        // Verify portfolio backtest results
        $this->assertArrayHasKey('portfolio_trades', $result);
        $this->assertArrayHasKey('portfolio_metrics', $result);
        $this->assertArrayHasKey('portfolio_equity_curve', $result);
        $this->assertArrayHasKey('strategy_breakdown', $result);
        
        // Verify strategy breakdown exists for both strategies
        $this->assertArrayHasKey('MeanReversion', $result['strategy_breakdown']);
        $this->assertArrayHasKey('MomentumQuality', $result['strategy_breakdown']);
    }
    
    /**
     * Test 5: Strategy Correlation Analysis
     * Tests that the performance analyzer can identify correlations between strategies.
     */
    public function testStrategyCorrelationAnalysis(): void
    {
        // Create correlated and uncorrelated trade histories
        $meanReversionTrades = [
            ['entry_date' => '2024-01-01', 'exit_date' => '2024-01-05', 'entry_price' => 50, 'exit_price' => 55, 'action' => 'BUY'],
            ['entry_date' => '2024-01-10', 'exit_date' => '2024-01-15', 'entry_price' => 60, 'exit_price' => 58, 'action' => 'BUY'],
            ['entry_date' => '2024-01-20', 'exit_date' => '2024-01-25', 'entry_price' => 55, 'exit_price' => 62, 'action' => 'BUY']
        ];
        
        $momentumTrades = [
            ['entry_date' => '2024-01-01', 'exit_date' => '2024-01-05', 'entry_price' => 100, 'exit_price' => 110, 'action' => 'BUY'],
            ['entry_date' => '2024-01-10', 'exit_date' => '2024-01-15', 'entry_price' => 110, 'exit_price' => 105, 'action' => 'BUY'],
            ['entry_date' => '2024-01-20', 'exit_date' => '2024-01-25', 'entry_price' => 105, 'exit_price' => 115, 'action' => 'BUY']
        ];
        
        // Load trades
        foreach ($meanReversionTrades as $trade) {
            $this->performanceAnalyzer->recordTrade('TEST1', 'MeanReversion', $trade);
        }
        
        foreach ($momentumTrades as $trade) {
            $this->performanceAnalyzer->recordTrade('TEST2', 'MomentumQuality', $trade);
        }
        
        // Calculate correlations
        $correlations = $this->performanceAnalyzer->calculateStrategyCorrelations();
        
        // Verify correlation matrix structure
        $this->assertIsArray($correlations);
        $this->assertArrayHasKey('MeanReversion', $correlations);
        $this->assertArrayHasKey('MomentumQuality', $correlations);
        
        // Verify correlation values are within valid range [-1, 1]
        foreach ($correlations as $strategy1 => $strategyCorrelations) {
            foreach ($strategyCorrelations as $strategy2 => $correlation) {
                $this->assertGreaterThanOrEqual(-1, $correlation);
                $this->assertLessThanOrEqual(1, $correlation);
            }
        }
    }
    
    /**
     * Test 6: Walk-Forward Analysis Integration
     * Tests that walk-forward analysis works across the system.
     */
    public function testWalkForwardAnalysisIntegration(): void
    {
        $strategy = new MeanReversionStrategyService($this->marketDataService, $this->repository);
        $historicalData = $this->generateHistoricalData('TEST', 200);
        
        // Run walk-forward analysis
        $result = $this->backtestingFramework->runWalkForwardAnalysis(
            $strategy,
            $historicalData,
            [
                'training_period' => 60,
                'testing_period' => 30,
                'step_size' => 30
            ]
        );
        
        // Verify walk-forward results
        $this->assertArrayHasKey('periods', $result);
        $this->assertArrayHasKey('aggregate_metrics', $result);
        $this->assertGreaterThan(0, count($result['periods']));
        
        // Verify each period has training and testing results
        foreach ($result['periods'] as $period) {
            $this->assertArrayHasKey('training_start', $period);
            $this->assertArrayHasKey('training_end', $period);
            $this->assertArrayHasKey('testing_start', $period);
            $this->assertArrayHasKey('testing_end', $period);
            $this->assertArrayHasKey('testing_metrics', $period);
        }
    }
    
    /**
     * Test 7: Optimal Strategy Combination Finder
     * Tests that the performance analyzer can identify optimal strategy combinations.
     */
    public function testOptimalStrategyCombinationFinder(): void
    {
        // Create diverse trade histories for multiple strategies
        $strategies = ['MeanReversion', 'MomentumQuality', 'QualityDividend', 'Contrarian'];
        
        foreach ($strategies as $strategy) {
            $trades = $this->generateRandomTrades($strategy, 20);
            foreach ($trades as $trade) {
                $this->performanceAnalyzer->recordTrade('TEST', $strategy, $trade);
            }
        }
        
        // Find optimal combination
        $optimal = $this->performanceAnalyzer->findOptimalCombination(
            ['return_weight' => 0.3, 'sharpe_weight' => 0.5, 'drawdown_weight' => 0.2]
        );
        
        // Verify optimal combination structure
        $this->assertArrayHasKey('weights', $optimal);
        $this->assertArrayHasKey('expected_sharpe', $optimal);
        $this->assertArrayHasKey('expected_return', $optimal);
        $this->assertArrayHasKey('expected_drawdown', $optimal);
        
        // Verify weights sum to approximately 1.0
        $weightSum = array_sum($optimal['weights']);
        $this->assertEqualsWithDelta(1.0, $weightSum, 0.01);
    }
    
    /**
     * Test 8: Real-Time Strategy Ranking
     * Tests that the weighting engine can rank multiple symbols.
     */
    public function testRealTimeStrategyRanking(): void
    {
        $strategies = $this->initializeAllStrategies();
        foreach ($strategies as $name => $strategy) {
            $this->weightingEngine->addStrategy($name, $strategy);
        }
        $this->weightingEngine->loadProfile('balanced');
        
        // Create mock data for multiple symbols
        $symbols = ['STOCK1', 'STOCK2', 'STOCK3'];
        foreach ($symbols as $symbol) {
            $this->setupMockMarketData($symbol, [
                'current_price' => rand(10, 100),
                'market_cap' => rand(50000000, 500000000),
                'volume' => rand(100000, 5000000),
                'rsi' => rand(20, 80)
            ]);
        }
        
        // Rank symbols
        $rankings = $this->weightingEngine->rankSymbols($symbols);
        
        // Verify rankings
        $this->assertIsArray($rankings);
        $this->assertCount(3, $rankings);
        
        // Verify each ranking has required fields
        foreach ($rankings as $ranking) {
            $this->assertArrayHasKey('symbol', $ranking);
            $this->assertArrayHasKey('weighted_confidence', $ranking);
            $this->assertArrayHasKey('action', $ranking);
            $this->assertArrayHasKey('rank', $ranking);
        }
        
        // Verify rankings are sorted by confidence (descending)
        $confidences = array_column($rankings, 'weighted_confidence');
        $sortedConfidences = $confidences;
        rsort($sortedConfidences);
        $this->assertEquals($sortedConfidences, $confidences);
    }
    
    // Helper Methods
    
    private function initializeAllStrategies(): array
    {
        return [
            'SmallCapCatalyst' => new SmallCapCatalystStrategyService($this->marketDataService, $this->repository),
            'IPlace' => new IPlaceStrategyService($this->marketDataService, $this->repository),
            'MeanReversion' => new MeanReversionStrategyService($this->marketDataService, $this->repository),
            'QualityDividend' => new QualityDividendStrategyService($this->marketDataService, $this->repository),
            'MomentumQuality' => new MomentumQualityStrategyService($this->marketDataService, $this->repository),
            'Contrarian' => new ContrarianStrategyService($this->marketDataService, $this->repository)
        ];
    }
    
    private function setupMockMarketData(string $symbol, array $data): void
    {
        $this->repository->method('getFundamentals')
            ->with($symbol)
            ->willReturn($data);
        
        $this->repository->method('getHistoricalPrices')
            ->with($symbol)
            ->willReturn($this->generatePriceHistory($data['current_price'] ?? 50.0, 100));
    }
    
    private function generatePriceHistory(float $basePrice, int $days): array
    {
        $prices = [];
        $date = new \DateTime('-' . $days . ' days');
        
        for ($i = 0; $i < $days; $i++) {
            $variation = (rand(-500, 500) / 100);
            $price = $basePrice + $variation;
            
            $prices[$date->format('Y-m-d')] = [
                'open' => $price,
                'high' => $price * 1.02,
                'low' => $price * 0.98,
                'close' => $price,
                'volume' => rand(500000, 2000000)
            ];
            
            $date->modify('+1 day');
        }
        
        return $prices;
    }
    
    private function generateHistoricalData(string $symbol, int $days): array
    {
        $data = [];
        $date = new \DateTime('-' . $days . ' days');
        $basePrice = 50.0;
        
        for ($i = 0; $i < $days; $i++) {
            $trend = sin($i / 10) * 5;
            $noise = (rand(-200, 200) / 100);
            $price = $basePrice + $trend + $noise;
            
            $data[$date->format('Y-m-d')] = [
                'symbol' => $symbol,
                'open' => $price,
                'high' => $price * 1.03,
                'low' => $price * 0.97,
                'close' => $price,
                'volume' => rand(500000, 2000000),
                'date' => $date->format('Y-m-d')
            ];
            
            $date->modify('+1 day');
        }
        
        return $data;
    }
    
    private function generateRandomTrades(string $strategy, int $count): array
    {
        $trades = [];
        $date = new \DateTime('-' . ($count * 7) . ' days');
        
        for ($i = 0; $i < $count; $i++) {
            $entryPrice = rand(40, 60);
            $exitPrice = $entryPrice + (rand(-10, 15));
            
            $trades[] = [
                'entry_date' => $date->format('Y-m-d'),
                'exit_date' => $date->modify('+5 days')->format('Y-m-d'),
                'entry_price' => $entryPrice,
                'exit_price' => $exitPrice,
                'action' => 'BUY',
                'confidence' => rand(50, 95)
            ];
            
            $date->modify('+2 days');
        }
        
        return $trades;
    }
}
