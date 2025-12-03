<?php

/**
 * Trading System Integration Demo
 * 
 * Demonstrates the complete trading system workflow:
 * 1. Load strategies with custom parameters
 * 2. Analyze multiple symbols with portfolio weighting
 * 3. Run historical backtests
 * 4. Calculate performance metrics
 * 5. Find optimal strategy combinations
 * 6. Generate comprehensive reports
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Trading\SmallCapCatalystStrategyService;
use App\Services\Trading\IPlaceStrategyService;
use App\Services\Trading\MeanReversionStrategyService;
use App\Services\Trading\QualityDividendStrategyService;
use App\Services\Trading\MomentumQualityStrategyService;
use App\Services\Trading\ContrarianStrategyService;
use App\Services\Trading\StrategyWeightingEngine;
use App\Services\Trading\StrategyPerformanceAnalyzer;
use App\Services\Trading\BacktestingFramework;

class TradingSystemDemo
{
    private array $strategies = [];
    private StrategyWeightingEngine $weightingEngine;
    private StrategyPerformanceAnalyzer $performanceAnalyzer;
    private BacktestingFramework $backtestingFramework;
    
    public function __construct()
    {
        $this->initializeStrategies();
        $this->weightingEngine = new StrategyWeightingEngine($this->strategies);
        $this->performanceAnalyzer = new StrategyPerformanceAnalyzer();
        $this->backtestingFramework = new BacktestingFramework(
            initialCapital: 100000,
            commissionRate: 0.001,
            slippageRate: 0.0005
        );
    }
    
    /**
     * Initialize all trading strategies
     */
    private function initializeStrategies(): void
    {
        echo "Initializing trading strategies...\n\n";
        
        // Mock dependencies (in production, inject real services)
        $mockMarketData = $this->createMockMarketDataService();
        $mockRepository = $this->createMockRepository();
        
        $this->strategies = [
            'SmallCapCatalyst' => new SmallCapCatalystStrategyService($mockMarketData, $mockRepository),
            'IPlace' => new IPlaceStrategyService($mockMarketData, $mockRepository),
            'MeanReversion' => new MeanReversionStrategyService($mockMarketData, $mockRepository),
            'QualityDividend' => new QualityDividendStrategyService($mockMarketData, $mockRepository),
            'MomentumQuality' => new MomentumQualityStrategyService($mockMarketData, $mockRepository),
            'Contrarian' => new ContrarianStrategyService($mockMarketData, $mockRepository)
        ];
        
        echo "✓ Loaded 6 trading strategies\n";
    }
    
    /**
     * Demo 1: Single Symbol Analysis
     */
    public function demoSingleSymbolAnalysis(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "DEMO 1: Single Symbol Analysis\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $symbol = 'EXAMPLE';
        $date = '2024-12-01';
        $data = $this->generateSampleData($symbol);
        
        echo "Analyzing $symbol with all strategies...\n\n";
        
        foreach ($this->strategies as $name => $strategy) {
            echo "[$name]\n";
            
            // Simulate strategy analysis
            $result = [
                'action' => $this->getRandomAction(),
                'confidence' => rand(40, 95),
                'reasoning' => "Sample analysis for demo purposes"
            ];
            
            echo "  Action: {$result['action']}\n";
            echo "  Confidence: {$result['confidence']}%\n";
            echo "  Reasoning: {$result['reasoning']}\n\n";
        }
    }
    
    /**
     * Demo 2: Portfolio Weighting Analysis
     */
    public function demoPortfolioWeighting(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "DEMO 2: Portfolio Weighting Analysis\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $profiles = ['conservative', 'balanced', 'aggressive', 'growth', 'value', 'catalyst'];
        
        foreach ($profiles as $profile) {
            $this->weightingEngine->loadProfile($profile);
            $weights = $this->weightingEngine->getWeights();
            
            echo strtoupper($profile) . " Profile:\n";
            
            arsort($weights);
            foreach ($weights as $strategy => $weight) {
                $percentage = round($weight * 100, 1);
                $bar = str_repeat("█", (int)($percentage / 5));
                echo sprintf("  %-20s %5.1f%% %s\n", $strategy, $percentage, $bar);
            }
            echo "\n";
        }
    }
    
    /**
     * Demo 3: Historical Backtesting
     */
    public function demoBacktesting(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "DEMO 3: Historical Backtesting\n";
        echo str_repeat("=", 60) . "\n\n";
        
        echo "Generating synthetic historical data...\n";
        $historicalData = $this->generateHistoricalData(252); // 1 year
        
        echo "Running backtest with 10% position size, 10% stop loss...\n\n";
        
        // Simulate backtest results
        $results = [
            'initial_capital' => 100000,
            'final_capital' => 115000,
            'total_return' => 0.15,
            'trades' => $this->generateSampleTrades(25),
            'metrics' => [
                'total_trades' => 25,
                'winning_trades' => 17,
                'losing_trades' => 8,
                'win_rate' => 0.68,
                'average_return' => 0.006,
                'sharpe_ratio' => 1.85,
                'max_drawdown' => 0.12,
                'average_holding_days' => 18
            ]
        ];
        
        echo "BACKTEST RESULTS:\n";
        echo "  Initial Capital:    $" . number_format($results['initial_capital']) . "\n";
        echo "  Final Capital:      $" . number_format($results['final_capital']) . "\n";
        echo "  Total Return:       " . ($results['total_return'] * 100) . "%\n";
        echo "  Total Trades:       {$results['metrics']['total_trades']}\n";
        echo "  Win Rate:           " . ($results['metrics']['win_rate'] * 100) . "%\n";
        echo "  Sharpe Ratio:       {$results['metrics']['sharpe_ratio']}\n";
        echo "  Max Drawdown:       " . ($results['metrics']['max_drawdown'] * 100) . "%\n";
        echo "  Avg Holding Days:   {$results['metrics']['average_holding_days']}\n\n";
        
        echo "Top 5 Trades:\n";
        usort($results['trades'], fn($a, $b) => $b['return'] <=> $a['return']);
        
        foreach (array_slice($results['trades'], 0, 5) as $i => $trade) {
            $return = round($trade['return'] * 100, 2);
            $pl = $trade['return'] > 0 ? '+' : '';
            echo sprintf("  %d. %s: %s%s%% (%d days)\n", 
                $i + 1, 
                $trade['symbol'], 
                $pl, 
                $return,
                $trade['holding_days']
            );
        }
    }
    
    /**
     * Demo 4: Performance Analysis
     */
    public function demoPerformanceAnalysis(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "DEMO 4: Performance Analysis\n";
        echo str_repeat("=", 60) . "\n\n";
        
        // Load sample trades for multiple strategies
        $trades = $this->generateMultiStrategyTrades();
        $this->performanceAnalyzer->loadTradeHistory($trades);
        
        echo "Strategy Performance Comparison:\n\n";
        
        $comparison = $this->performanceAnalyzer->compareStrategies();
        
        echo sprintf("%-20s %10s %10s %10s %12s\n", 
            'Strategy', 'Win Rate', 'Avg Return', 'Sharpe', 'Expectancy'
        );
        echo str_repeat("-", 72) . "\n";
        
        foreach ($comparison as $metrics) {
            echo sprintf("%-20s %9.1f%% %9.1f%% %10.2f %11.2f%%\n",
                $metrics['strategy'],
                $metrics['win_rate'] * 100,
                $metrics['average_return'] * 100,
                $metrics['sharpe_ratio'],
                $metrics['expectancy'] * 100
            );
        }
    }
    
    /**
     * Demo 5: Strategy Correlation Analysis
     */
    public function demoCorrelationAnalysis(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "DEMO 5: Strategy Correlation Analysis\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $trades = $this->generateMultiStrategyTrades();
        $this->performanceAnalyzer->loadTradeHistory($trades);
        
        $correlations = $this->performanceAnalyzer->calculateStrategyCorrelations();
        
        if (empty($correlations)) {
            echo "Insufficient data for correlation analysis\n";
            return;
        }
        
        echo "Correlation Matrix:\n\n";
        
        $strategies = array_keys($correlations);
        
        // Header
        echo sprintf("%-20s", '');
        foreach ($strategies as $s) {
            echo sprintf("%8s", substr($s, 0, 7));
        }
        echo "\n" . str_repeat("-", 20 + (8 * count($strategies))) . "\n";
        
        // Matrix
        foreach ($strategies as $s1) {
            echo sprintf("%-20s", substr($s1, 0, 19));
            foreach ($strategies as $s2) {
                $corr = $correlations[$s1][$s2] ?? 0;
                $color = $corr > 0.7 ? '▓' : ($corr < -0.3 ? '░' : '▒');
                echo sprintf("%7.2f%s", $corr, $color);
            }
            echo "\n";
        }
        
        echo "\n";
        echo "Legend: ▓ High correlation (>0.7), ▒ Moderate, ░ Negative (<-0.3)\n";
    }
    
    /**
     * Demo 6: Optimal Strategy Combination
     */
    public function demoOptimalCombination(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "DEMO 6: Optimal Strategy Combination\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $trades = $this->generateMultiStrategyTrades();
        $this->performanceAnalyzer->loadTradeHistory($trades);
        
        echo "Finding optimal combination (max 4 strategies)...\n\n";
        
        $optimal = $this->performanceAnalyzer->findOptimalCombination(4);
        
        if (empty($optimal)) {
            echo "Unable to determine optimal combination\n";
            return;
        }
        
        echo "RECOMMENDED PORTFOLIO:\n\n";
        
        echo "Strategy Allocation:\n";
        arsort($optimal['weights']);
        foreach ($optimal['weights'] as $strategy => $weight) {
            $percentage = round($weight * 100, 1);
            $bar = str_repeat("█", (int)($percentage / 2));
            echo sprintf("  %-20s %5.1f%% %s\n", $strategy, $percentage, $bar);
        }
        
        echo "\nExpected Performance:\n";
        echo "  Portfolio Sharpe Ratio:    {$optimal['expected_sharpe']}\n";
        echo "  Diversification Benefit:   {$optimal['diversification_benefit']}%\n";
        
        echo "\nSelected Strategies:\n";
        foreach ($optimal['recommended_strategies'] as $i => $strategy) {
            echo sprintf("  %d. %s (Win Rate: %.1f%%, Sharpe: %.2f)\n",
                $i + 1,
                $strategy['strategy'],
                $strategy['win_rate'] * 100,
                $strategy['sharpe_ratio']
            );
        }
    }
    
    /**
     * Demo 7: Walk-Forward Analysis
     */
    public function demoWalkForwardAnalysis(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "DEMO 7: Walk-Forward Analysis\n";
        echo str_repeat("=", 60) . "\n\n";
        
        echo "Configuration:\n";
        echo "  Training Period:  252 days (1 year)\n";
        echo "  Testing Period:   63 days (3 months)\n";
        echo "  Step Size:        63 days (3 months)\n\n";
        
        echo "Running walk-forward analysis...\n\n";
        
        // Simulate walk-forward results
        $periods = [
            ['period' => 1, 'train_start' => '2023-01-01', 'train_end' => '2023-12-31', 
             'test_start' => '2024-01-01', 'test_end' => '2024-03-31', 
             'total_return' => 0.08, 'sharpe_ratio' => 1.5, 'max_drawdown' => 0.06],
            ['period' => 2, 'train_start' => '2023-04-01', 'train_end' => '2024-03-31',
             'test_start' => '2024-04-01', 'test_end' => '2024-06-30',
             'total_return' => 0.12, 'sharpe_ratio' => 2.1, 'max_drawdown' => 0.04],
            ['period' => 3, 'train_start' => '2023-07-01', 'train_end' => '2024-06-30',
             'test_start' => '2024-07-01', 'test_end' => '2024-09-30',
             'total_return' => 0.06, 'sharpe_ratio' => 1.2, 'max_drawdown' => 0.08],
        ];
        
        echo sprintf("%-8s %-12s %-12s %12s %10s %12s\n",
            'Period', 'Train Start', 'Test Start', 'Return', 'Sharpe', 'Max DD'
        );
        echo str_repeat("-", 75) . "\n";
        
        foreach ($periods as $p) {
            echo sprintf("%-8d %-12s %-12s %11.1f%% %10.2f %11.1f%%\n",
                $p['period'],
                $p['train_start'],
                $p['test_start'],
                $p['total_return'] * 100,
                $p['sharpe_ratio'],
                $p['max_drawdown'] * 100
            );
        }
        
        echo "\nSummary:\n";
        $avgReturn = array_sum(array_column($periods, 'total_return')) / count($periods);
        $avgSharpe = array_sum(array_column($periods, 'sharpe_ratio')) / count($periods);
        
        echo "  Average Return:     " . round($avgReturn * 100, 1) . "%\n";
        echo "  Average Sharpe:     " . round($avgSharpe, 2) . "\n";
        echo "  Profitable Periods: " . count(array_filter($periods, fn($p) => $p['total_return'] > 0)) . "/" . count($periods) . "\n";
    }
    
    /**
     * Demo 8: Monte Carlo Simulation
     */
    public function demoMonteCarloSimulation(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "DEMO 8: Monte Carlo Simulation\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $trades = $this->generateSampleTrades(50);
        
        echo "Running 1,000 simulations (100 trades each)...\n\n";
        
        $results = $this->backtestingFramework->monteCarloSimulation($trades, 1000, 100);
        
        echo "MONTE CARLO RESULTS:\n\n";
        echo "Return Distribution:\n";
        echo sprintf("  Best Case (95th):   %+.1f%%\n", $results['percentile_95'] * 100);
        echo sprintf("  Upper Quartile:     %+.1f%%\n", $results['percentile_75'] * 100);
        echo sprintf("  Median:             %+.1f%%\n", $results['median_return'] * 100);
        echo sprintf("  Mean:               %+.1f%%\n", $results['mean_return'] * 100);
        echo sprintf("  Lower Quartile:     %+.1f%%\n", $results['percentile_25'] * 100);
        echo sprintf("  Worst Case (5th):   %+.1f%%\n", $results['percentile_5'] * 100);
        
        echo "\nRisk Metrics:\n";
        echo sprintf("  Probability of Profit: %.1f%%\n", $results['probability_profit'] * 100);
        echo sprintf("  Best Scenario:         %+.1f%%\n", $results['best_case'] * 100);
        echo sprintf("  Worst Scenario:        %+.1f%%\n", $results['worst_case'] * 100);
        
        // Simple histogram
        echo "\nReturn Distribution Histogram:\n";
        $bins = [
            ['min' => -1.0, 'max' => -0.5, 'label' => '< -50%'],
            ['min' => -0.5, 'max' => -0.2, 'label' => '-50% to -20%'],
            ['min' => -0.2, 'max' => 0.0, 'label' => '-20% to 0%'],
            ['min' => 0.0, 'max' => 0.2, 'label' => '0% to 20%'],
            ['min' => 0.2, 'max' => 0.5, 'label' => '20% to 50%'],
            ['min' => 0.5, 'max' => 2.0, 'label' => '> 50%']
        ];
        
        foreach ($bins as $bin) {
            $count = count(array_filter($results['distribution'], 
                fn($r) => $r >= $bin['min'] && $r < $bin['max']
            ));
            $percentage = ($count / $results['simulations']) * 100;
            $bar = str_repeat("█", (int)($percentage / 2));
            echo sprintf("  %-15s %5.1f%% %s\n", $bin['label'], $percentage, $bar);
        }
    }
    
    /**
     * Run all demos
     */
    public function runAllDemos(): void
    {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║       TRADING STRATEGY SYSTEM - INTEGRATION DEMO          ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n";
        
        $this->demoSingleSymbolAnalysis();
        $this->demoPortfolioWeighting();
        $this->demoBacktesting();
        $this->demoPerformanceAnalysis();
        $this->demoCorrelationAnalysis();
        $this->demoOptimalCombination();
        $this->demoWalkForwardAnalysis();
        $this->demoMonteCarloSimulation();
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "Demo completed successfully!\n";
        echo str_repeat("=", 60) . "\n\n";
    }
    
    // Helper methods for demo data generation
    
    private function createMockMarketDataService(): object
    {
        return new class {
            public function getHistoricalPrices(string $symbol, string $start, string $end): array
            {
                return [];
            }
        };
    }
    
    private function createMockRepository(): object
    {
        return new class {
            public function getParameter(string $strategy, string $param): mixed
            {
                return null;
            }
        };
    }
    
    private function generateSampleData(string $symbol): array
    {
        return [
            'symbol' => $symbol,
            'close' => 150.00,
            'volume' => 1000000,
            'market_cap' => 500000000
        ];
    }
    
    private function generateHistoricalData(int $days): array
    {
        $data = [];
        $price = 100;
        
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $price *= (1 + (rand(-200, 200) / 10000));
            
            $data[$date] = [
                'close' => $price,
                'open' => $price * 0.99,
                'high' => $price * 1.02,
                'low' => $price * 0.98,
                'volume' => rand(500000, 2000000),
                'symbol' => 'DEMO'
            ];
        }
        
        return array_reverse($data);
    }
    
    private function generateSampleTrades(int $count): array
    {
        $trades = [];
        $symbols = ['AAPL', 'GOOGL', 'MSFT', 'TSLA', 'AMZN', 'NVDA', 'META'];
        
        for ($i = 0; $i < $count; $i++) {
            $return = (rand(-30, 50) / 100);
            $entryPrice = rand(50, 300);
            $exitPrice = $entryPrice * (1 + $return);
            
            $trades[] = [
                'symbol' => $symbols[array_rand($symbols)],
                'strategy' => 'Demo',
                'entry_date' => date('Y-m-d', strtotime("-" . rand(1, 100) . " days")),
                'entry_price' => $entryPrice,
                'exit_date' => date('Y-m-d', strtotime("-" . rand(1, 50) . " days")),
                'exit_price' => $exitPrice,
                'return' => $return,
                'holding_days' => rand(5, 45)
            ];
        }
        
        return $trades;
    }
    
    private function generateMultiStrategyTrades(): array
    {
        $trades = [];
        $strategies = array_keys($this->strategies);
        
        foreach ($strategies as $strategy) {
            $strategyTrades = $this->generateSampleTrades(rand(10, 20));
            
            foreach ($strategyTrades as $trade) {
                $trade['strategy'] = $strategy;
                $trades[] = $trade;
            }
        }
        
        return $trades;
    }
    
    private function getRandomAction(): string
    {
        $actions = ['BUY', 'HOLD', 'SELL'];
        return $actions[array_rand($actions)];
    }
}

// Run the demo
if (php_sapi_name() === 'cli') {
    $demo = new TradingSystemDemo();
    $demo->runAllDemos();
}
