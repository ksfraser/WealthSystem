<?php
namespace Ksfraser\Finance\Services;

use Ksfraser\Finance\Backtesting\BacktestingEngine;
use Ksfraser\Finance\Services\StrategyService;
use Ksfraser\Finance\Services\StockDataService;
use Ksfraser\LLM\Providers\OpenAIProvider;
use Ksfraser\Finance\Interfaces\DataRepositoryInterface;

/**
 * Enhanced Strategy Analysis Service
 * 
 * Combines backtesting, AI analysis, and strategy scoring for comprehensive evaluation
 */
class StrategyAnalysisService
{
    private BacktestingEngine $backtestingEngine;
    private StrategyService $strategyService;
    private StockDataService $stockDataService;
    private OpenAIProvider $aiProvider;
    private DataRepositoryInterface $repository;

    public function __construct(
        BacktestingEngine $backtestingEngine,
        StrategyService $strategyService,
        StockDataService $stockDataService,
        OpenAIProvider $aiProvider,
        DataRepositoryInterface $repository
    ) {
        $this->backtestingEngine = $backtestingEngine;
        $this->strategyService = $strategyService;
        $this->stockDataService = $stockDataService;
        $this->aiProvider = $aiProvider;
        $this->repository = $repository;
    }

    /**
     * Comprehensive strategy analysis with backtesting and AI scoring
     */
    public function analyzeStrategy(string $symbol, string $strategyClass, array $parameters = [], array $options = []): array
    {
        $options = array_merge([
            'period' => '2y',
            'include_ai_analysis' => true,
            'include_news_analysis' => false,
            'save_results' => true
        ], $options);

        // Get historical market data
        $marketData = $this->stockDataService->getStockData($symbol, $options['period']);
        
        if (empty($marketData)) {
            throw new \Exception("No market data available for {$symbol}");
        }

        // Run backtest
        $backtestResults = $this->backtestingEngine->runBacktest(
            $strategyClass,
            $parameters,
            $marketData,
            [
                'symbol' => $symbol,
                'initial_capital' => $options['initial_capital'] ?? 100000
            ]
        );

        $analysis = [
            'symbol' => $symbol,
            'strategy_class' => $strategyClass,
            'parameters' => $parameters,
            'backtest_results' => $backtestResults,
            'analysis_date' => date('Y-m-d H:i:s')
        ];

        // Add AI analysis if enabled and available
        if ($options['include_ai_analysis'] && $this->aiProvider->isAvailable()) {
            $analysis['ai_analysis'] = $this->generateAIAnalysis($backtestResults, $symbol);
        }

        // Add news sentiment analysis if enabled
        if ($options['include_news_analysis']) {
            $analysis['news_analysis'] = $this->analyzeRecentNews($symbol);
        }

        // Generate comprehensive score
        $analysis['comprehensive_score'] = $this->generateComprehensiveScore($analysis);

        // Save results to database if enabled
        if ($options['save_results']) {
            $this->saveAnalysisResults($analysis);
        }

        return $analysis;
    }

    /**
     * Compare multiple strategies for a given symbol
     */
    public function compareStrategies(string $symbol, array $strategiesConfig, array $options = []): array
    {
        $results = [];
        $errors = [];

        foreach ($strategiesConfig as $config) {
            try {
                $result = $this->analyzeStrategy(
                    $symbol,
                    $config['class'],
                    $config['parameters'] ?? [],
                    array_merge($options, ['save_results' => false])
                );
                $results[] = $result;
            } catch (\Exception $e) {
                $errors[] = [
                    'strategy' => $config['class'],
                    'error' => $e->getMessage()
                ];
            }
        }

        $comparison = $this->backtestingEngine->compareStrategies(
            array_column($results, 'backtest_results')
        );

        return [
            'symbol' => $symbol,
            'comparison_date' => date('Y-m-d H:i:s'),
            'strategies_analyzed' => count($results),
            'errors' => $errors,
            'individual_results' => $results,
            'comparison' => $comparison,
            'recommendation' => $this->generateRecommendation($comparison, $results)
        ];
    }

    /**
     * Analyze multiple symbols with a single strategy
     */
    public function analyzeMultipleSymbols(array $symbols, string $strategyClass, array $parameters = [], array $options = []): array
    {
        $results = [];
        $errors = [];

        foreach ($symbols as $symbol) {
            try {
                $result = $this->analyzeStrategy(
                    $symbol,
                    $strategyClass,
                    $parameters,
                    array_merge($options, ['save_results' => false])
                );
                $results[$symbol] = $result;
            } catch (\Exception $e) {
                $errors[$symbol] = $e->getMessage();
            }
        }

        // Rank symbols by strategy performance
        $rankings = $this->rankSymbolsByPerformance($results);

        return [
            'strategy_class' => $strategyClass,
            'parameters' => $parameters,
            'symbols_analyzed' => count($symbols),
            'successful_analyses' => count($results),
            'errors' => $errors,
            'results' => $results,
            'rankings' => $rankings,
            'analysis_date' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate AI-powered analysis
     */
    private function generateAIAnalysis(array $backtestResults, string $symbol): array
    {
        try {
            // Prepare comprehensive prompt for strategy analysis
            $metrics = $backtestResults['metrics'];
            $marketConditions = [
                'symbol' => $symbol,
                'period_days' => $backtestResults['period']['days'],
                'total_signals' => count($backtestResults['signals']),
                'market_trend' => $this->detectMarketTrend($backtestResults['portfolio_values'])
            ];

            $strategyAnalysis = $this->aiProvider->analyzeStrategy(
                $backtestResults['portfolio_values'],
                $metrics,
                $backtestResults['strategy_class']
            );

            $scoreAnalysis = $this->aiProvider->scoreStrategy(
                $backtestResults['strategy_class'],
                $metrics,
                $marketConditions
            );

            return [
                'strategy_analysis' => $strategyAnalysis,
                'score_analysis' => $scoreAnalysis,
                'market_regime_assessment' => $this->assessMarketRegime($backtestResults, $symbol)
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'AI analysis failed: ' . $e->getMessage(),
                'fallback_analysis' => $this->generateFallbackAnalysis($backtestResults)
            ];
        }
    }

    /**
     * Analyze recent news for sentiment
     */
    private function analyzeRecentNews(string $symbol): array
    {
        // This would integrate with news APIs
        // For now, return placeholder
        return [
            'sentiment' => 'neutral',
            'confidence' => 0.5,
            'news_count' => 0,
            'note' => 'News analysis not implemented yet'
        ];
    }

    /**
     * Generate comprehensive score combining multiple factors
     */
    private function generateComprehensiveScore(array $analysis): array
    {
        $backtestScore = $analysis['backtest_results']['score']['total_score'] ?? 0;
        $metrics = $analysis['backtest_results']['metrics'];
        
        // Weight different factors
        $weights = [
            'backtest_performance' => 0.4,
            'risk_management' => 0.3,
            'consistency' => 0.2,
            'ai_confidence' => 0.1
        ];

        $scores = [
            'backtest_performance' => $backtestScore,
            'risk_management' => $this->calculateRiskScore($metrics),
            'consistency' => $this->calculateConsistencyScore($metrics),
            'ai_confidence' => $this->extractAIConfidence($analysis)
        ];

        $weightedScore = 0;
        foreach ($weights as $factor => $weight) {
            $weightedScore += $scores[$factor] * $weight;
        }

        return [
            'comprehensive_score' => $weightedScore,
            'component_scores' => $scores,
            'weights' => $weights,
            'grade' => $this->scoreToGrade($weightedScore),
            'interpretation' => $this->interpretScore($weightedScore, $scores)
        ];
    }

    private function calculateRiskScore(array $metrics): float
    {
        $riskScore = 100;
        
        // Penalize high drawdown
        $riskScore -= min(30, $metrics['max_drawdown'] * 100);
        
        // Penalize high volatility
        $riskScore -= min(20, $metrics['std_return'] * 1000);
        
        // Reward good Sharpe ratio
        if ($metrics['sharpe_ratio'] > 1) {
            $riskScore += min(10, ($metrics['sharpe_ratio'] - 1) * 5);
        }
        
        return max(0, min(100, $riskScore));
    }

    private function calculateConsistencyScore(array $metrics): float
    {
        $consistencyScore = 50; // Base score
        
        // Reward reasonable win rate
        $optimalWinRate = 0.6;
        $winRateDeviation = abs($metrics['win_rate'] - $optimalWinRate);
        $consistencyScore += (1 - $winRateDeviation * 2) * 30;
        
        // Reward sufficient number of trades
        if ($metrics['total_trades'] > 10) {
            $consistencyScore += 20;
        } elseif ($metrics['total_trades'] > 5) {
            $consistencyScore += 10;
        }
        
        return max(0, min(100, $consistencyScore));
    }

    private function extractAIConfidence(array $analysis): float
    {
        // Extract confidence from AI analysis if available
        if (isset($analysis['ai_analysis']['score_analysis']['success'])) {
            return 70; // Assume good AI confidence
        }
        return 50; // Neutral confidence
    }

    private function scoreToGrade(float $score): string
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
        return 'D';
    }

    private function interpretScore(float $score, array $componentScores): string
    {
        if ($score >= 80) {
            return "Excellent strategy with strong performance across all metrics. Suitable for live trading.";
        } elseif ($score >= 70) {
            return "Good strategy with solid performance. Consider minor optimizations.";
        } elseif ($score >= 60) {
            return "Average strategy. May work in specific market conditions. Requires careful monitoring.";
        } elseif ($score >= 50) {
            return "Below average strategy. Significant improvements needed before live trading.";
        } else {
            return "Poor strategy performance. Consider alternative approaches or different market conditions.";
        }
    }

    private function detectMarketTrend(array $portfolioValues): string
    {
        if (count($portfolioValues) < 10) {
            return 'insufficient_data';
        }
        
        $firstValue = $portfolioValues[0]['value'];
        $lastValue = end($portfolioValues)['value'];
        $change = ($lastValue - $firstValue) / $firstValue;
        
        if ($change > 0.1) return 'strong_uptrend';
        if ($change > 0.05) return 'uptrend';
        if ($change > -0.05) return 'sideways';
        if ($change > -0.1) return 'downtrend';
        return 'strong_downtrend';
    }

    private function assessMarketRegime(array $backtestResults, string $symbol): array
    {
        // Analyze market regime during backtest period
        $portfolioValues = $backtestResults['portfolio_values'];
        $trend = $this->detectMarketTrend($portfolioValues);
        
        return [
            'trend' => $trend,
            'volatility' => $this->calculateVolatility($portfolioValues),
            'regime_suitability' => $this->assessRegimeSuitability($backtestResults, $trend)
        ];
    }

    private function calculateVolatility(array $portfolioValues): string
    {
        if (count($portfolioValues) < 2) {
            return 'unknown';
        }
        
        $returns = [];
        for ($i = 1; $i < count($portfolioValues); $i++) {
            $return = ($portfolioValues[$i]['value'] - $portfolioValues[$i-1]['value']) / $portfolioValues[$i-1]['value'];
            $returns[] = $return;
        }
        
        $variance = $this->calculateVariance($returns);
        $volatility = sqrt($variance) * sqrt(252); // Annualized
        
        if ($volatility > 0.3) return 'high';
        if ($volatility > 0.2) return 'medium';
        return 'low';
    }

    private function calculateVariance(array $values): float
    {
        if (count($values) < 2) return 0;
        
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(fn($x) => pow($x - $mean, 2), $values);
        
        return array_sum($squaredDiffs) / (count($values) - 1);
    }

    private function assessRegimeSuitability(array $backtestResults, string $trend): string
    {
        $strategy = $backtestResults['strategy_class'];
        $sharpe = $backtestResults['metrics']['sharpe_ratio'];
        
        // Simple heuristic - would be more sophisticated in practice
        if (strpos($strategy, 'Turtle') !== false && in_array($trend, ['uptrend', 'strong_uptrend'])) {
            return 'excellent';
        }
        
        if ($sharpe > 1.5) return 'good';
        if ($sharpe > 1.0) return 'fair';
        return 'poor';
    }

    private function generateFallbackAnalysis(array $backtestResults): array
    {
        return [
            'recommendation' => 'Manual review required',
            'key_metrics' => [
                'return' => $backtestResults['metrics']['total_return_pct'],
                'sharpe' => $backtestResults['metrics']['sharpe_ratio'],
                'max_dd' => $backtestResults['metrics']['max_drawdown']
            ]
        ];
    }

    private function generateRecommendation(array $comparison, array $results): array
    {
        if (empty($comparison['rankings'])) {
            return ['recommendation' => 'No valid strategies to compare'];
        }
        
        $bestStrategy = $comparison['rankings'][0];
        
        return [
            'recommended_strategy' => $bestStrategy['strategy'],
            'reason' => "Highest overall score ({$bestStrategy['score']}) with {$bestStrategy['return']}% return",
            'confidence' => $this->calculateRecommendationConfidence($bestStrategy, $results)
        ];
    }

    private function calculateRecommendationConfidence(array $bestStrategy, array $results): string
    {
        if ($bestStrategy['score'] > 80 && $bestStrategy['return'] > 0.1) {
            return 'high';
        } elseif ($bestStrategy['score'] > 60) {
            return 'medium';
        }
        return 'low';
    }

    private function rankSymbolsByPerformance(array $results): array
    {
        $rankings = [];
        
        foreach ($results as $symbol => $result) {
            $score = $result['comprehensive_score']['comprehensive_score'] ?? 0;
            $return = $result['backtest_results']['metrics']['total_return_pct'] ?? 0;
            
            $rankings[] = [
                'symbol' => $symbol,
                'score' => $score,
                'return' => $return,
                'sharpe' => $result['backtest_results']['metrics']['sharpe_ratio'] ?? 0,
                'max_dd' => $result['backtest_results']['metrics']['max_drawdown'] ?? 0
            ];
        }
        
        // Sort by comprehensive score
        usort($rankings, fn($a, $b) => $b['score'] <=> $a['score']);
        
        return $rankings;
    }

    private function saveAnalysisResults(array $analysis): void
    {
        // Save to database - simplified implementation
        try {
            $sql = "INSERT INTO strategy_analysis_results 
                    (symbol, strategy_class, parameters, results, analysis_date) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $this->repository->execute($sql, [
                $analysis['symbol'],
                $analysis['strategy_class'],
                json_encode($analysis['parameters']),
                json_encode($analysis),
                $analysis['analysis_date']
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the analysis
            error_log("Failed to save analysis results: " . $e->getMessage());
        }
    }
}
