<?php

declare(strict_types=1);

namespace App\Backtesting;

use App\Services\Trading\TradingStrategyInterface;
use InvalidArgumentException;

/**
 * Parameter Optimizer
 * 
 * Optimizes trading strategy parameters using grid search and walk-forward validation.
 * Helps find optimal parameter values while detecting overfitting.
 * 
 * Features:
 * - Grid search optimization
 * - Walk-forward validation
 * - Overfitting detection
 * - Multi-parameter optimization
 * 
 * @package App\Backtesting
 */
class ParameterOptimizer
{
    private BacktestEngine $engine;
    private PerformanceMetrics $metrics;
    
    /**
     * Valid optimization metrics
     */
    private const VALID_METRICS = [
        'total_return',
        'sharpe_ratio',
        'sortino_ratio',
        'max_drawdown',
        'win_rate',
        'profit_factor'
    ];
    
    /**
     * Create new parameter optimizer
     *
     * @param BacktestEngine $engine Backtesting engine
     * @param PerformanceMetrics $metrics Performance metrics calculator
     */
    public function __construct(BacktestEngine $engine, PerformanceMetrics $metrics)
    {
        $this->engine = $engine;
        $this->metrics = $metrics;
    }
    
    /**
     * Optimize strategy parameters using grid search
     *
     * @param callable $strategyFactory Factory function that creates strategy from parameters
     * @param array<string, array<int, mixed>> $parameterGrid Parameter name => array of values
     * @param string $symbol Stock symbol
     * @param array<int, array<string, mixed>> $historicalData Historical OHLCV data
     * @param string $optimizationMetric Metric to optimize for
     * @return array<string, mixed> Optimization results
     * @throws InvalidArgumentException If invalid parameters
     */
    public function optimize(
        callable $strategyFactory,
        array $parameterGrid,
        string $symbol,
        array $historicalData,
        string $optimizationMetric
    ): array {
        if (empty($parameterGrid)) {
            throw new InvalidArgumentException('Parameter grid cannot be empty');
        }
        
        if (!in_array($optimizationMetric, self::VALID_METRICS)) {
            throw new InvalidArgumentException(
                "Invalid optimization metric: {$optimizationMetric}. Valid metrics: " . 
                implode(', ', self::VALID_METRICS)
            );
        }
        
        // Generate all parameter combinations
        $combinations = $this->generateCombinations($parameterGrid);
        
        $results = [];
        
        // Test each combination
        foreach ($combinations as $params) {
            $strategy = $strategyFactory($params);
            
            // Run backtest
            $backtestResult = $this->engine->run($strategy, $symbol, $historicalData);
            $performanceMetrics = $this->metrics->generateSummary($backtestResult);
            
            $score = $performanceMetrics[$optimizationMetric];
            
            $results[] = [
                'parameters' => $params,
                'score' => $score,
                'metrics' => $performanceMetrics
            ];
        }
        
        // Sort by score (descending for most metrics, ascending for max_drawdown)
        usort($results, function ($a, $b) use ($optimizationMetric) {
            if ($optimizationMetric === 'max_drawdown') {
                return $b['score'] <=> $a['score']; // Ascending (least negative first)
            }
            return $b['score'] <=> $a['score']; // Descending
        });
        
        // Calculate summary statistics
        $scores = array_column($results, 'score');
        $bestScore = $scores[0];
        $worstScore = $scores[count($scores) - 1];
        $avgScore = array_sum($scores) / count($scores);
        
        return [
            'best_parameters' => $results[0]['parameters'],
            'best_score' => $bestScore,
            'worst_score' => $worstScore,
            'avg_score' => $avgScore,
            'all_results' => $results,
            'iterations' => count($results)
        ];
    }
    
    /**
     * Perform walk-forward validation
     *
     * @param callable $strategyFactory Factory function that creates strategy from parameters
     * @param array<string, array<int, mixed>> $parameterGrid Parameter name => array of values
     * @param string $symbol Stock symbol
     * @param array<int, array<string, mixed>> $historicalData Historical OHLCV data
     * @param string $optimizationMetric Metric to optimize for
     * @param int $trainWindow Number of bars for training
     * @param int $testWindow Number of bars for testing
     * @return array<string, mixed> Walk-forward results
     * @throws InvalidArgumentException If insufficient data
     */
    public function walkForward(
        callable $strategyFactory,
        array $parameterGrid,
        string $symbol,
        array $historicalData,
        string $optimizationMetric,
        int $trainWindow,
        int $testWindow
    ): array {
        $totalBars = count($historicalData);
        $minRequired = $trainWindow + $testWindow;
        
        if ($totalBars < $minRequired) {
            throw new InvalidArgumentException(
                "Insufficient historical data. Need at least {$minRequired} bars, got {$totalBars}"
            );
        }
        
        $windows = [];
        $position = 0;
        
        // Walk forward through data
        while ($position + $trainWindow + $testWindow <= $totalBars) {
            // Split data
            $trainData = array_slice($historicalData, $position, $trainWindow);
            $testData = array_slice($historicalData, $position + $trainWindow, $testWindow);
            
            // Optimize on training data
            $trainResult = $this->optimize(
                $strategyFactory,
                $parameterGrid,
                $symbol,
                $trainData,
                $optimizationMetric
            );
            
            $bestParams = $trainResult['best_parameters'];
            $trainScore = $trainResult['best_score'];
            
            // Test on out-of-sample data
            $strategy = $strategyFactory($bestParams);
            $testBacktest = $this->engine->run($strategy, $symbol, $testData);
            $testMetrics = $this->metrics->generateSummary($testBacktest);
            $testScore = $testMetrics[$optimizationMetric];
            
            $windows[] = [
                'train_period' => [
                    'start' => $trainData[0]['date'],
                    'end' => $trainData[count($trainData) - 1]['date']
                ],
                'test_period' => [
                    'start' => $testData[0]['date'],
                    'end' => $testData[count($testData) - 1]['date']
                ],
                'best_parameters' => $bestParams,
                'train_score' => $trainScore,
                'test_score' => $testScore
            ];
            
            // Move forward by test window size
            $position += $testWindow;
        }
        
        // Calculate averages
        $trainScores = array_column($windows, 'train_score');
        $testScores = array_column($windows, 'test_score');
        
        $avgTrainScore = array_sum($trainScores) / count($trainScores);
        $avgTestScore = array_sum($testScores) / count($testScores);
        
        // Calculate overfitting ratio
        // Ratio close to 1.0 = good generalization
        // Ratio < 0.8 = potential overfitting
        $overfittingRatio = $avgTrainScore != 0 ? $avgTestScore / $avgTrainScore : 0.0;
        
        return [
            'windows' => $windows,
            'avg_train_score' => $avgTrainScore,
            'avg_test_score' => $avgTestScore,
            'overfitting_ratio' => $overfittingRatio
        ];
    }
    
    /**
     * Generate all parameter combinations from grid
     *
     * @param array<string, array<int, mixed>> $parameterGrid Parameter grid
     * @return array<int, array<string, mixed>> All combinations
     */
    private function generateCombinations(array $parameterGrid): array
    {
        $keys = array_keys($parameterGrid);
        $values = array_values($parameterGrid);
        
        $combinations = [[]];
        
        foreach ($values as $index => $paramValues) {
            $newCombinations = [];
            
            foreach ($combinations as $combination) {
                foreach ($paramValues as $value) {
                    $newCombination = $combination;
                    $newCombination[$keys[$index]] = $value;
                    $newCombinations[] = $newCombination;
                }
            }
            
            $combinations = $newCombinations;
        }
        
        return $combinations;
    }
}
