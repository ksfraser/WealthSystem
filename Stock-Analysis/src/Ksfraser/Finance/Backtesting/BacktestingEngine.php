<?php
namespace Ksfraser\Finance\Backtesting;

use Ksfraser\Finance\Backtesting\Interfaces\BacktestingEngineInterface;
use Ksfraser\Finance\Interfaces\TradingStrategyInterface;
use Ksfraser\Finance\Services\StockDataService;
use Ksfraser\LLM\Interfaces\LLMProviderInterface;

/**
 * Advanced Backtesting Engine with AI Scoring
 * 
 * Comprehensive backtesting system with performance analysis and AI-enhanced scoring
 */
class BacktestingEngine implements BacktestingEngineInterface
{
    private StockDataService $stockDataService;
    private ?LLMProviderInterface $llmProvider;
    private array $config;

    public function __construct(StockDataService $stockDataService, ?LLMProviderInterface $llmProvider = null, array $config = [])
    {
        $this->stockDataService = $stockDataService;
        $this->llmProvider = $llmProvider;
        $this->config = array_merge([
            'initial_capital' => 100000,
            'commission_rate' => 0.001, // 0.1%
            'slippage_rate' => 0.0005,  // 0.05%
            'risk_free_rate' => 0.02,   // 2% annual
            'max_position_size' => 0.1,  // 10% max position
            'transaction_cost' => 5.0    // $5 per trade
        ], $config);
    }

    public function runBacktest(string $strategyClass, array $parameters, array $marketData, array $options = []): array
    {
        $options = array_merge([
            'initial_capital' => $this->config['initial_capital'],
            'start_date' => null,
            'end_date' => null,
            'symbol' => 'UNKNOWN'
        ], $options);

        // Filter market data by date range if specified
        if ($options['start_date'] || $options['end_date']) {
            $marketData = $this->filterDataByDateRange($marketData, $options['start_date'], $options['end_date']);
        }

        if (empty($marketData)) {
            throw new \Exception('No market data available for backtesting');
        }

        // Create strategy instance
        $strategy = $this->createStrategyInstance($strategyClass, $parameters);
        
        // Initialize backtest state
        $state = [
            'capital' => $options['initial_capital'],
            'position' => 0,
            'position_value' => 0,
            'trades' => [],
            'portfolio_values' => [],
            'drawdowns' => [],
            'signals' => []
        ];

        // Run through historical data
        foreach ($marketData as $index => $dataPoint) {
            $historicalData = array_slice($marketData, 0, $index + 1);
            
            if (count($historicalData) < 20) {
                continue; // Need minimum data for indicators
            }

            // Generate signal
            $signal = $strategy->generateSignal($options['symbol'], $historicalData);
            
            if ($signal) {
                $state['signals'][] = array_merge($signal, [
                    'date' => $dataPoint['date'],
                    'index' => $index
                ]);

                // Execute trade
                $state = $this->executeTrade($state, $signal, $dataPoint, $options);
            }

            // Update portfolio value
            $portfolioValue = $state['capital'] + ($state['position'] * $dataPoint['close']);
            $state['portfolio_values'][] = [
                'date' => $dataPoint['date'],
                'value' => $portfolioValue,
                'price' => $dataPoint['close'],
                'position' => $state['position']
            ];

            // Track drawdown
            $peak = $this->calculatePeak($state['portfolio_values']);
            $drawdown = ($peak - $portfolioValue) / $peak;
            $state['drawdowns'][] = [
                'date' => $dataPoint['date'],
                'drawdown' => $drawdown,
                'peak' => $peak
            ];
        }

        // Calculate final metrics
        $metrics = $this->calculateMetrics($state['trades'], $options['initial_capital']);
        $score = $this->scoreStrategy([
            'trades' => $state['trades'],
            'portfolio_values' => $state['portfolio_values'],
            'metrics' => $metrics
        ]);

        return [
            'strategy_class' => $strategyClass,
            'parameters' => $parameters,
            'symbol' => $options['symbol'],
            'period' => [
                'start' => $marketData[0]['date'] ?? null,
                'end' => end($marketData)['date'] ?? null,
                'days' => count($marketData)
            ],
            'initial_capital' => $options['initial_capital'],
            'final_value' => end($state['portfolio_values'])['value'] ?? $options['initial_capital'],
            'trades' => $state['trades'],
            'signals' => $state['signals'],
            'portfolio_values' => $state['portfolio_values'],
            'drawdowns' => $state['drawdowns'],
            'metrics' => $metrics,
            'score' => $score,
            'ai_analysis' => $this->getAIAnalysis($state, $metrics)
        ];
    }

    private function createStrategyInstance(string $strategyClass, array $parameters): TradingStrategyInterface
    {
        // Map class names to actual classes with proper namespaces
        $classMap = [
            'TurtleStrategy' => 'Ksfraser\Finance\Strategies\Turtle\TurtleStrategy',
            'MovingAverageCrossoverStrategy' => 'Ksfraser\Finance\Strategies\TechnicalAnalysis\MovingAverageCrossoverStrategy',
            'SupportResistanceStrategy' => 'Ksfraser\Finance\Strategies\SupportResistance\SupportResistanceStrategy',
            'FourWeekRuleStrategy' => 'Ksfraser\Finance\Strategies\Breakout\FourWeekRuleStrategy'
        ];

        $fullClassName = $classMap[$strategyClass] ?? $strategyClass;

        if (!class_exists($fullClassName)) {
            throw new \Exception("Strategy class not found: {$fullClassName}");
        }

        return new $fullClassName($this->stockDataService, $parameters);
    }

    private function filterDataByDateRange(array $marketData, ?string $startDate, ?string $endDate): array
    {
        return array_filter($marketData, function($data) use ($startDate, $endDate) {
            $date = $data['date'];
            
            if ($startDate && $date < $startDate) {
                return false;
            }
            
            if ($endDate && $date > $endDate) {
                return false;
            }
            
            return true;
        });
    }

    private function executeTrade(array $state, array $signal, array $dataPoint, array $options): array
    {
        $action = $signal['action'];
        $price = $dataPoint['close'];
        $commission = $this->config['commission_rate'];
        $slippage = $this->config['slippage_rate'];
        $transactionCost = $this->config['transaction_cost'];

        // Apply slippage
        if ($action === 'BUY') {
            $executionPrice = $price * (1 + $slippage);
        } elseif ($action === 'SELL') {
            $executionPrice = $price * (1 - $slippage);
        } else {
            return $state; // HOLD - no action
        }

        $trade = [
            'date' => $dataPoint['date'],
            'action' => $action,
            'price' => $executionPrice,
            'signal_price' => $signal['price'],
            'confidence' => $signal['confidence'],
            'reasoning' => $signal['reasoning'] ?? ''
        ];

        if ($action === 'BUY' && $state['position'] <= 0) {
            // Calculate position size based on confidence and available capital
            $positionSizePercent = min(
                $signal['confidence'] * $this->config['max_position_size'],
                $this->config['max_position_size']
            );
            
            $positionValue = $state['capital'] * $positionSizePercent;
            $shares = floor(($positionValue - $transactionCost) / $executionPrice);
            
            if ($shares > 0) {
                $totalCost = ($shares * $executionPrice) * (1 + $commission) + $transactionCost;
                
                if ($totalCost <= $state['capital']) {
                    $state['capital'] -= $totalCost;
                    $state['position'] += $shares;
                    
                    $trade['shares'] = $shares;
                    $trade['total_cost'] = $totalCost;
                    $trade['commission'] = $shares * $executionPrice * $commission;
                    $trade['transaction_cost'] = $transactionCost;
                    
                    $state['trades'][] = $trade;
                }
            }
        } elseif ($action === 'SELL' && $state['position'] > 0) {
            // Sell all shares
            $shares = $state['position'];
            $grossProceeds = $shares * $executionPrice;
            $totalProceeds = $grossProceeds * (1 - $commission) - $transactionCost;
            
            $state['capital'] += $totalProceeds;
            $state['position'] = 0;
            
            $trade['shares'] = -$shares; // Negative for sell
            $trade['total_proceeds'] = $totalProceeds;
            $trade['commission'] = $grossProceeds * $commission;
            $trade['transaction_cost'] = $transactionCost;
            
            $state['trades'][] = $trade;
        }

        return $state;
    }

    private function calculatePeak(array $portfolioValues): float
    {
        $peak = 0;
        foreach ($portfolioValues as $value) {
            $peak = max($peak, $value['value']);
        }
        return $peak;
    }

    public function calculateMetrics(array $trades, float $initialCapital): array
    {
        if (empty($trades)) {
            return $this->getEmptyMetrics();
        }

        $buyTrades = array_filter($trades, fn($t) => $t['action'] === 'BUY');
        $sellTrades = array_filter($trades, fn($t) => $t['action'] === 'SELL');
        
        $totalTrades = count($sellTrades); // Complete round trips
        $wins = 0;
        $losses = 0;
        $totalReturn = 0;
        $returns = [];

        // Calculate returns for each completed trade pair
        $openPositions = [];
        foreach ($trades as $trade) {
            if ($trade['action'] === 'BUY') {
                $openPositions[] = $trade;
            } elseif ($trade['action'] === 'SELL' && !empty($openPositions)) {
                $buyTrade = array_shift($openPositions);
                $profit = ($trade['price'] - $buyTrade['price']) * abs($trade['shares']);
                $returnPct = ($trade['price'] - $buyTrade['price']) / $buyTrade['price'];
                
                $returns[] = $returnPct;
                $totalReturn += $profit;
                
                if ($profit > 0) {
                    $wins++;
                } else {
                    $losses++;
                }
            }
        }

        $winRate = $totalTrades > 0 ? $wins / $totalTrades : 0;
        $avgReturn = !empty($returns) ? array_sum($returns) / count($returns) : 0;
        $stdReturn = $this->calculateStandardDeviation($returns);
        $sharpeRatio = $stdReturn > 0 ? ($avgReturn - $this->config['risk_free_rate'] / 252) / $stdReturn : 0;

        // Calculate maximum drawdown
        $maxDrawdown = 0;
        if (!empty($trades)) {
            $runningValue = $initialCapital;
            $peak = $runningValue;
            
            foreach ($trades as $trade) {
                if ($trade['action'] === 'SELL') {
                    $runningValue += ($trade['total_proceeds'] ?? 0);
                } elseif ($trade['action'] === 'BUY') {
                    $runningValue -= ($trade['total_cost'] ?? 0);
                }
                
                $peak = max($peak, $runningValue);
                $drawdown = ($peak - $runningValue) / $peak;
                $maxDrawdown = max($maxDrawdown, $drawdown);
            }
        }

        $profitFactor = $losses > 0 ? abs($totalReturn) / abs(array_sum(array_filter($returns, fn($r) => $r < 0))) : 0;

        return [
            'total_trades' => $totalTrades,
            'winning_trades' => $wins,
            'losing_trades' => $losses,
            'win_rate' => $winRate,
            'total_return' => $totalReturn,
            'total_return_pct' => $totalReturn / $initialCapital,
            'avg_return' => $avgReturn,
            'std_return' => $stdReturn,
            'sharpe_ratio' => $sharpeRatio,
            'max_drawdown' => $maxDrawdown,
            'profit_factor' => $profitFactor,
            'avg_trade_duration' => $this->calculateAvgTradeDuration($trades),
            'largest_win' => !empty($returns) ? max($returns) : 0,
            'largest_loss' => !empty($returns) ? min($returns) : 0
        ];
    }

    private function getEmptyMetrics(): array
    {
        return [
            'total_trades' => 0,
            'winning_trades' => 0,
            'losing_trades' => 0,
            'win_rate' => 0,
            'total_return' => 0,
            'total_return_pct' => 0,
            'avg_return' => 0,
            'std_return' => 0,
            'sharpe_ratio' => 0,
            'max_drawdown' => 0,
            'profit_factor' => 0,
            'avg_trade_duration' => 0,
            'largest_win' => 0,
            'largest_loss' => 0
        ];
    }

    private function calculateStandardDeviation(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / (count($values) - 1);
        
        return sqrt($variance);
    }

    private function calculateAvgTradeDuration(array $trades): float
    {
        // Simple approximation - would need more sophisticated pairing logic
        return count($trades) > 0 ? 5.0 : 0; // Average 5 days per trade
    }

    public function scoreStrategy(array $backtestResults): array
    {
        $metrics = $backtestResults['metrics'];
        
        // Performance Score (0-40 points)
        $performanceScore = $this->calculatePerformanceScore($metrics);
        
        // Risk Score (0-30 points)
        $riskScore = $this->calculateRiskScore($metrics);
        
        // Consistency Score (0-20 points)
        $consistencyScore = $this->calculateConsistencyScore($metrics);
        
        // Implementation Score (0-10 points)
        $implementationScore = $this->calculateImplementationScore($metrics);
        
        $totalScore = $performanceScore + $riskScore + $consistencyScore + $implementationScore;
        
        return [
            'total_score' => $totalScore,
            'performance_score' => $performanceScore,
            'risk_score' => $riskScore,
            'consistency_score' => $consistencyScore,
            'implementation_score' => $implementationScore,
            'grade' => $this->getGrade($totalScore),
            'recommendations' => $this->getRecommendations($metrics, $totalScore)
        ];
    }

    private function calculatePerformanceScore(array $metrics): float
    {
        $score = 0;
        
        // Return score (0-20)
        $returnScore = min(20, max(0, $metrics['total_return_pct'] * 100)); // 1% return = 1 point
        $score += $returnScore;
        
        // Win rate score (0-10)
        $winRateScore = $metrics['win_rate'] * 10;
        $score += $winRateScore;
        
        // Sharpe ratio score (0-10)
        $sharpeScore = min(10, max(0, $metrics['sharpe_ratio'] * 5)); // Sharpe 2.0 = 10 points
        $score += $sharpeScore;
        
        return $score;
    }

    private function calculateRiskScore(array $metrics): float
    {
        $score = 30; // Start with full points, deduct for risk
        
        // Drawdown penalty (0-15 points deducted)
        $drawdownPenalty = min(15, $metrics['max_drawdown'] * 50); // 30% DD = 15 points
        $score -= $drawdownPenalty;
        
        // Volatility penalty (0-10 points deducted)
        $volatilityPenalty = min(10, $metrics['std_return'] * 100); // High vol = penalty
        $score -= $volatilityPenalty;
        
        // Profit factor bonus (0-5 points)
        if ($metrics['profit_factor'] > 1) {
            $score += min(5, ($metrics['profit_factor'] - 1) * 2);
        }
        
        return max(0, $score);
    }

    private function calculateConsistencyScore(array $metrics): float
    {
        $score = 0;
        
        // Trade frequency (0-10)
        if ($metrics['total_trades'] > 0) {
            $frequencyScore = min(10, $metrics['total_trades'] / 10); // 100 trades = 10 points
            $score += $frequencyScore;
        }
        
        // Win/loss ratio consistency (0-10)
        if ($metrics['total_trades'] > 5) {
            $consistency = 1 - abs($metrics['win_rate'] - 0.5) * 2; // Closer to 50% = more consistent
            $score += $consistency * 10;
        }
        
        return $score;
    }

    private function calculateImplementationScore(array $metrics): float
    {
        // This is a simplified implementation score
        // In practice, would consider transaction costs, market impact, etc.
        return 8; // Default good implementation score
    }

    private function getGrade(float $score): string
    {
        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'A-';
        if ($score >= 75) return 'B+';
        if ($score >= 70) return 'B';
        if ($score >= 65) return 'B-';
        if ($score >= 60) return 'C+';
        if ($score >= 55) return 'C';
        if ($score >= 50) return 'C-';
        if ($score >= 45) return 'D';
        return 'F';
    }

    private function getRecommendations(array $metrics, float $score): array
    {
        $recommendations = [];
        
        if ($metrics['win_rate'] < 0.4) {
            $recommendations[] = "Low win rate ({$metrics['win_rate']}%) - consider tightening entry criteria";
        }
        
        if ($metrics['max_drawdown'] > 0.2) {
            $recommendations[] = "High maximum drawdown ({$metrics['max_drawdown']}%) - implement better risk management";
        }
        
        if ($metrics['sharpe_ratio'] < 1.0) {
            $recommendations[] = "Low Sharpe ratio ({$metrics['sharpe_ratio']}) - strategy may not compensate for risk";
        }
        
        if ($metrics['total_trades'] < 10) {
            $recommendations[] = "Few trades ({$metrics['total_trades']}) - results may not be statistically significant";
        }
        
        if ($score < 60) {
            $recommendations[] = "Overall score below 60 - consider major strategy revisions or different market conditions";
        }
        
        return $recommendations;
    }

    private function getAIAnalysis(array $state, array $metrics): ?array
    {
        if (!$this->llmProvider || !$this->llmProvider->isAvailable()) {
            return null;
        }

        try {
            // Prepare market conditions summary
            $marketConditions = [
                'total_periods' => count($state['portfolio_values']),
                'total_signals' => count($state['signals']),
                'avg_confidence' => $this->calculateAverageConfidence($state['signals']),
                'signal_distribution' => $this->getSignalDistribution($state['signals'])
            ];

            return $this->llmProvider->scoreStrategy(
                'Custom Strategy',
                $metrics,
                $marketConditions
            );
        } catch (\Exception $e) {
            return [
                'error' => 'AI analysis failed: ' . $e->getMessage()
            ];
        }
    }

    private function calculateAverageConfidence(array $signals): float
    {
        if (empty($signals)) {
            return 0;
        }

        $totalConfidence = array_sum(array_column($signals, 'confidence'));
        return $totalConfidence / count($signals);
    }

    private function getSignalDistribution(array $signals): array
    {
        $distribution = ['BUY' => 0, 'SELL' => 0, 'HOLD' => 0];
        
        foreach ($signals as $signal) {
            $action = $signal['action'] ?? 'HOLD';
            if (isset($distribution[$action])) {
                $distribution[$action]++;
            }
        }
        
        return $distribution;
    }

    public function compareStrategies(array $strategies): array
    {
        if (empty($strategies)) {
            return [];
        }

        // Sort strategies by total score
        usort($strategies, function($a, $b) {
            return ($b['score']['total_score'] ?? 0) <=> ($a['score']['total_score'] ?? 0);
        });

        $comparison = [
            'rankings' => [],
            'best_strategy' => $strategies[0],
            'summary' => []
        ];

        foreach ($strategies as $index => $strategy) {
            $rank = $index + 1;
            $comparison['rankings'][] = [
                'rank' => $rank,
                'strategy' => $strategy['strategy_class'],
                'score' => $strategy['score']['total_score'] ?? 0,
                'return' => $strategy['metrics']['total_return_pct'] ?? 0,
                'sharpe' => $strategy['metrics']['sharpe_ratio'] ?? 0,
                'max_dd' => $strategy['metrics']['max_drawdown'] ?? 0,
                'win_rate' => $strategy['metrics']['win_rate'] ?? 0
            ];
        }

        return $comparison;
    }
}
