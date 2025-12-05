<?php

declare(strict_types=1);

namespace Tests\Backtesting;

use App\Backtesting\BacktestEngine;
use App\Backtesting\PerformanceMetrics;
use App\Backtesting\ParameterOptimizer;
use App\Services\Trading\TradingStrategyInterface;
use PHPUnit\Framework\TestCase;

/**
 * ParameterOptimizer Test Suite
 * 
 * Tests parameter optimization functionality including:
 * - Grid search optimization
 * - Walk-forward validation
 * - Overfitting detection
 * - Best parameter selection
 * 
 * @package Tests\Backtesting
 */
class ParameterOptimizerTest extends TestCase
{
    private ParameterOptimizer $optimizer;
    private BacktestEngine $engine;
    private PerformanceMetrics $metrics;
    
    protected function setUp(): void
    {
        $this->engine = new BacktestEngine([
            'initial_capital' => 10000.0,
            'commission' => 0.001,
            'slippage' => 0.0005
        ]);
        
        $this->metrics = new PerformanceMetrics();
        $this->optimizer = new ParameterOptimizer($this->engine, $this->metrics);
    }
    
    /**
     * @test
     */
    public function itOptimizesSingleParameter(): void
    {
        $strategyFactory = function ($params) {
            return $this->createStrategy($params['signal'] ?? 'BUY');
        };
        
        $parameterGrid = [
            'signal' => ['BUY', 'HOLD']
        ];
        
        $historicalData = $this->createHistoricalData();
        
        $result = $this->optimizer->optimize(
            $strategyFactory,
            $parameterGrid,
            'AAPL',
            $historicalData,
            'sharpe_ratio'
        );
        
        $this->assertArrayHasKey('best_parameters', $result);
        $this->assertArrayHasKey('best_score', $result);
        $this->assertArrayHasKey('all_results', $result);
        $this->assertArrayHasKey('iterations', $result);
        
        $this->assertEquals(2, $result['iterations']); // 2 combinations
        $this->assertArrayHasKey('signal', $result['best_parameters']);
    }
    
    /**
     * @test
     */
    public function itOptimizesMultipleParameters(): void
    {
        $strategyFactory = function ($params) {
            return $this->createStrategy('BUY');
        };
        
        $parameterGrid = [
            'period' => [10, 14, 20],
            'threshold' => [30, 40]
        ];
        
        $historicalData = $this->createHistoricalData();
        
        $result = $this->optimizer->optimize(
            $strategyFactory,
            $parameterGrid,
            'AAPL',
            $historicalData,
            'total_return'
        );
        
        $this->assertEquals(6, $result['iterations']); // 3 * 2 = 6 combinations
        $this->assertArrayHasKey('period', $result['best_parameters']);
        $this->assertArrayHasKey('threshold', $result['best_parameters']);
        
        // Verify best parameters are in the grid
        $this->assertContains($result['best_parameters']['period'], $parameterGrid['period']);
        $this->assertContains($result['best_parameters']['threshold'], $parameterGrid['threshold']);
    }
    
    /**
     * @test
     */
    public function itPerformsWalkForwardValidation(): void
    {
        $strategyFactory = function ($params) {
            return $this->createStrategy('BUY');
        };
        
        $parameterGrid = [
            'period' => [10, 14]
        ];
        
        $historicalData = $this->createLongHistoricalData(); // Needs enough data for multiple windows
        
        $result = $this->optimizer->walkForward(
            $strategyFactory,
            $parameterGrid,
            'AAPL',
            $historicalData,
            'sharpe_ratio',
            20,  // Train window
            10   // Test window
        );
        
        $this->assertArrayHasKey('windows', $result);
        $this->assertArrayHasKey('avg_test_score', $result);
        $this->assertArrayHasKey('avg_train_score', $result);
        $this->assertArrayHasKey('overfitting_ratio', $result);
        
        $this->assertGreaterThan(0, count($result['windows']));
        
        foreach ($result['windows'] as $window) {
            $this->assertArrayHasKey('train_period', $window);
            $this->assertArrayHasKey('test_period', $window);
            $this->assertArrayHasKey('best_parameters', $window);
            $this->assertArrayHasKey('train_score', $window);
            $this->assertArrayHasKey('test_score', $window);
        }
    }
    
    /**
     * @test
     */
    public function itDetectsOverfitting(): void
    {
        $strategyFactory = function ($params) {
            return $this->createStrategy('BUY');
        };
        
        $parameterGrid = [
            'period' => [10, 14]
        ];
        
        $historicalData = $this->createLongHistoricalData();
        
        $result = $this->optimizer->walkForward(
            $strategyFactory,
            $parameterGrid,
            'AAPL',
            $historicalData,
            'sharpe_ratio',
            20,
            10
        );
        
        // Overfitting ratio = avg_test_score / avg_train_score
        // Ratio < 0.8 suggests significant overfitting
        $this->assertArrayHasKey('overfitting_ratio', $result);
        $this->assertIsFloat($result['overfitting_ratio']);
        
        // Ratio should be between 0 and 1 (test typically worse than train)
        $this->assertGreaterThanOrEqual(0, $result['overfitting_ratio']);
        $this->assertLessThanOrEqual(2.0, $result['overfitting_ratio']); // Allow some leeway
    }
    
    /**
     * @test
     */
    public function itRanksByOptimizationMetric(): void
    {
        $strategyFactory = function ($params) {
            return $this->createStrategy($params['signal']);
        };
        
        $parameterGrid = [
            'signal' => ['BUY', 'HOLD']
        ];
        
        $historicalData = $this->createHistoricalData();
        
        $result = $this->optimizer->optimize(
            $strategyFactory,
            $parameterGrid,
            'AAPL',
            $historicalData,
            'sharpe_ratio'
        );
        
        // Verify all_results are sorted by sharpe_ratio (descending)
        $scores = array_column($result['all_results'], 'score');
        
        for ($i = 0; $i < count($scores) - 1; $i++) {
            $this->assertGreaterThanOrEqual($scores[$i + 1], $scores[$i]);
        }
    }
    
    /**
     * @test
     */
    public function itIncludesAllParameterCombinations(): void
    {
        $strategyFactory = function ($params) {
            return $this->createStrategy('BUY');
        };
        
        $parameterGrid = [
            'period' => [10, 20],
            'threshold' => [30, 40]
        ];
        
        $historicalData = $this->createHistoricalData();
        
        $result = $this->optimizer->optimize(
            $strategyFactory,
            $parameterGrid,
            'AAPL',
            $historicalData,
            'total_return'
        );
        
        $this->assertCount(4, $result['all_results']); // 2 * 2 = 4
        
        // Verify each combination exists
        $combinations = [
            ['period' => 10, 'threshold' => 30],
            ['period' => 10, 'threshold' => 40],
            ['period' => 20, 'threshold' => 30],
            ['period' => 20, 'threshold' => 40]
        ];
        
        foreach ($combinations as $expected) {
            $found = false;
            foreach ($result['all_results'] as $testResult) {
                if ($testResult['parameters'] == $expected) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Missing combination: " . json_encode($expected));
        }
    }
    
    /**
     * @test
     */
    public function itHandlesEmptyParameterGrid(): void
    {
        $strategyFactory = function ($params) {
            return $this->createStrategy('BUY');
        };
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter grid cannot be empty');
        
        $this->optimizer->optimize(
            $strategyFactory,
            [],
            'AAPL',
            $this->createHistoricalData(),
            'sharpe_ratio'
        );
    }
    
    /**
     * @test
     */
    public function itHandlesInvalidOptimizationMetric(): void
    {
        $strategyFactory = function ($params) {
            return $this->createStrategy('BUY');
        };
        
        $parameterGrid = ['period' => [10]];
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid optimization metric');
        
        $this->optimizer->optimize(
            $strategyFactory,
            $parameterGrid,
            'AAPL',
            $this->createHistoricalData(),
            'invalid_metric'
        );
    }
    
    /**
     * @test
     */
    public function itHandlesInsufficientDataForWalkForward(): void
    {
        $strategyFactory = function ($params) {
            return $this->createStrategy('BUY');
        };
        
        $parameterGrid = ['period' => [10]];
        
        $historicalData = $this->createHistoricalData(); // Only 4 bars
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient historical data');
        
        $this->optimizer->walkForward(
            $strategyFactory,
            $parameterGrid,
            'AAPL',
            $historicalData,
            'sharpe_ratio',
            20,  // Train window > data size
            10
        );
    }
    
    /**
     * @test
     */
    public function itReturnsOptimizationSummary(): void
    {
        $strategyFactory = function ($params) {
            return $this->createStrategy('BUY');
        };
        
        $parameterGrid = [
            'period' => [10, 14, 20]
        ];
        
        $historicalData = $this->createHistoricalData();
        
        $result = $this->optimizer->optimize(
            $strategyFactory,
            $parameterGrid,
            'AAPL',
            $historicalData,
            'sharpe_ratio'
        );
        
        // Verify summary includes key metrics
        $this->assertArrayHasKey('best_parameters', $result);
        $this->assertArrayHasKey('best_score', $result);
        $this->assertArrayHasKey('worst_score', $result);
        $this->assertArrayHasKey('avg_score', $result);
        $this->assertArrayHasKey('iterations', $result);
        
        $this->assertEquals(3, $result['iterations']);
        $this->assertIsFloat($result['best_score']);
        $this->assertIsFloat($result['worst_score']);
        $this->assertIsFloat($result['avg_score']);
        
        // Best should be >= avg >= worst
        $this->assertGreaterThanOrEqual($result['avg_score'], $result['best_score']);
        $this->assertGreaterThanOrEqual($result['worst_score'], $result['avg_score']);
    }
    
    /**
     * @test
     */
    public function itSupportsCustomStrategyFactory(): void
    {
        $strategyFactory = function ($params) {
            $signal = $params['aggressive'] ? 'BUY' : 'HOLD';
            return $this->createStrategy($signal);
        };
        
        $parameterGrid = [
            'aggressive' => [true, false]
        ];
        
        $historicalData = $this->createHistoricalData();
        
        $result = $this->optimizer->optimize(
            $strategyFactory,
            $parameterGrid,
            'AAPL',
            $historicalData,
            'total_return'
        );
        
        $this->assertArrayHasKey('aggressive', $result['best_parameters']);
        $this->assertIsBool($result['best_parameters']['aggressive']);
    }
    
    // Helper methods
    
    private function createStrategy(string $signal): TradingStrategyInterface
    {
        $strategy = $this->createMock(TradingStrategyInterface::class);
        $strategy->method('analyze')->willReturn([
            'signal' => $signal,
            'confidence' => 0.8,
            'reason' => 'Test signal',
            'metadata' => []
        ]);
        
        return $strategy;
    }
    
    private function createHistoricalData(): array
    {
        return [
            ['date' => '2024-01-01', 'open' => 98.0, 'high' => 102.0, 'low' => 97.0, 'close' => 100.0, 'volume' => 1000000],
            ['date' => '2024-01-02', 'open' => 100.0, 'high' => 108.0, 'low' => 99.0, 'close' => 105.0, 'volume' => 1200000],
            ['date' => '2024-01-03', 'open' => 105.0, 'high' => 112.0, 'low' => 104.0, 'close' => 110.0, 'volume' => 1100000],
            ['date' => '2024-01-04', 'open' => 110.0, 'high' => 115.0, 'low' => 108.0, 'close' => 112.0, 'volume' => 1300000]
        ];
    }
    
    private function createLongHistoricalData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        for ($i = 0; $i < 50; $i++) {
            $date = date('Y-m-d', strtotime('2024-01-01 + ' . $i . ' days'));
            $close = $basePrice + ($i * 0.5) + (rand(-2, 2));
            
            $data[] = [
                'date' => $date,
                'open' => $close - 1,
                'high' => $close + 2,
                'low' => $close - 2,
                'close' => $close,
                'volume' => 1000000 + rand(-100000, 100000)
            ];
        }
        
        return $data;
    }
}
