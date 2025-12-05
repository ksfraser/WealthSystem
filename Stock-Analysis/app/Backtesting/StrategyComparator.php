<?php

declare(strict_types=1);

namespace App\Backtesting;

use App\Services\Trading\TradingStrategyInterface;
use InvalidArgumentException;

/**
 * Strategy Comparator
 * 
 * Compares multiple trading strategies side-by-side on historical data.
 * Enables data-driven strategy selection through quantitative comparison.
 * 
 * Features:
 * - Compare multiple strategies simultaneously
 * - Rank by different performance metrics
 * - Generate comparison reports
 * - Export to CSV format
 * 
 * @package App\Backtesting
 */
class StrategyComparator
{
    private BacktestEngine $engine;
    private PerformanceMetrics $metrics;
    
    /**
     * Valid ranking metrics
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
     * Create new strategy comparator
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
     * Compare multiple strategies on historical data
     *
     * @param array<string, TradingStrategyInterface> $strategies Strategy name => strategy instance
     * @param string $symbol Stock symbol
     * @param array<int, array<string, mixed>> $historicalData Historical OHLCV data
     * @return array<string, array<string, mixed>> Comparison results
     * @throws InvalidArgumentException If no strategies provided
     */
    public function compare(array $strategies, string $symbol, array $historicalData): array
    {
        if (empty($strategies)) {
            throw new InvalidArgumentException('At least one strategy required');
        }
        
        $results = [];
        
        foreach ($strategies as $name => $strategy) {
            // Run backtest
            $backtestResult = $this->engine->run($strategy, $symbol, $historicalData);
            
            // Calculate performance metrics
            $performanceMetrics = $this->metrics->generateSummary($backtestResult);
            
            // Store results
            $results[$name] = [
                'strategy_name' => $name,
                'backtest' => $backtestResult,
                'metrics' => $performanceMetrics
            ];
        }
        
        return $results;
    }
    
    /**
     * Compare and rank strategies by a specific metric
     *
     * @param array<string, TradingStrategyInterface> $strategies Strategy name => strategy instance
     * @param string $symbol Stock symbol
     * @param array<int, array<string, mixed>> $historicalData Historical OHLCV data
     * @param string $rankingMetric Metric to rank by (e.g., 'sharpe_ratio', 'total_return')
     * @return array<int, array<string, mixed>> Ranked results (best first)
     * @throws InvalidArgumentException If invalid ranking metric
     */
    public function rankBy(
        array $strategies,
        string $symbol,
        array $historicalData,
        string $rankingMetric
    ): array {
        if (!in_array($rankingMetric, self::VALID_METRICS)) {
            throw new InvalidArgumentException(
                "Invalid ranking metric: {$rankingMetric}. Valid metrics: " . 
                implode(', ', self::VALID_METRICS)
            );
        }
        
        // Compare strategies
        $results = $this->compare($strategies, $symbol, $historicalData);
        
        // Convert to indexed array for sorting
        $indexed = array_values($results);
        
        // Sort by metric
        usort($indexed, function ($a, $b) use ($rankingMetric) {
            $valueA = $a['metrics'][$rankingMetric];
            $valueB = $b['metrics'][$rankingMetric];
            
            // For max_drawdown, lower (less negative) is better
            if ($rankingMetric === 'max_drawdown') {
                return $valueB <=> $valueA; // Ascending (least negative first)
            }
            
            // For other metrics, higher is better
            return $valueB <=> $valueA; // Descending
        });
        
        // Add rank positions
        foreach ($indexed as $index => $result) {
            $indexed[$index]['rank'] = $index + 1;
        }
        
        return $indexed;
    }
    
    /**
     * Generate comparison report
     *
     * @param array<string, TradingStrategyInterface> $strategies Strategy name => strategy instance
     * @param string $symbol Stock symbol
     * @param array<int, array<string, mixed>> $historicalData Historical OHLCV data
     * @param string $rankingMetric Metric to rank by
     * @return string Formatted comparison report
     */
    public function generateReport(
        array $strategies,
        string $symbol,
        array $historicalData,
        string $rankingMetric = 'sharpe_ratio'
    ): string {
        $ranked = $this->rankBy($strategies, $symbol, $historicalData, $rankingMetric);
        
        $report = [];
        $report[] = "=" . str_repeat("=", 79);
        $report[] = "Strategy Comparison Report";
        $report[] = "=" . str_repeat("=", 79);
        $report[] = "";
        $report[] = "Symbol: {$symbol}";
        $report[] = "Ranked by: " . $this->formatMetricName($rankingMetric);
        $report[] = "Number of strategies: " . count($ranked);
        $report[] = "";
        $report[] = str_repeat("-", 80);
        
        foreach ($ranked as $result) {
            $metrics = $result['metrics'];
            $rank = $result['rank'];
            $name = $result['strategy_name'];
            
            $report[] = "";
            $report[] = "#{$rank} - {$name}";
            $report[] = str_repeat("-", 80);
            $report[] = sprintf("Total Return:        %8.2f%%", $metrics['total_return']);
            $report[] = sprintf("Annualized Return:   %8.2f%%", $metrics['annualized_return']);
            $report[] = sprintf("Sharpe Ratio:        %8.2f", $metrics['sharpe_ratio']);
            $report[] = sprintf("Sortino Ratio:       %8.2f", $metrics['sortino_ratio']);
            $report[] = sprintf("Max Drawdown:        %8.2f%%", $metrics['max_drawdown']);
            $report[] = sprintf("Win Rate:            %8.2f%%", $metrics['win_rate']);
            $report[] = sprintf("Profit Factor:       %8.2f", $metrics['profit_factor']);
            $report[] = sprintf("Total Trades:        %8d", $metrics['total_trades']);
            $report[] = sprintf("Winning Trades:      %8d", $metrics['winning_trades']);
            $report[] = sprintf("Losing Trades:       %8d", $metrics['losing_trades']);
            $report[] = sprintf("Avg Win:             %8.2f", $metrics['avg_win']);
            $report[] = sprintf("Avg Loss:            %8.2f", $metrics['avg_loss']);
            $report[] = sprintf("Expectancy:          %8.2f", $metrics['expectancy']);
            $report[] = sprintf("Volatility:          %8.2f%%", $metrics['volatility']);
        }
        
        $report[] = "";
        $report[] = "=" . str_repeat("=", 79);
        
        return implode("\n", $report);
    }
    
    /**
     * Export comparison to CSV format
     *
     * @param array<string, TradingStrategyInterface> $strategies Strategy name => strategy instance
     * @param string $symbol Stock symbol
     * @param array<int, array<string, mixed>> $historicalData Historical OHLCV data
     * @param string $rankingMetric Metric to rank by
     * @return string CSV formatted comparison
     */
    public function exportToCSV(
        array $strategies,
        string $symbol,
        array $historicalData,
        string $rankingMetric = 'sharpe_ratio'
    ): string {
        $ranked = $this->rankBy($strategies, $symbol, $historicalData, $rankingMetric);
        
        $csv = [];
        
        // Header row
        $headers = [
            'Rank',
            'Strategy Name',
            'Total Return',
            'Annualized Return',
            'Sharpe Ratio',
            'Sortino Ratio',
            'Max Drawdown',
            'Win Rate',
            'Profit Factor',
            'Total Trades',
            'Winning Trades',
            'Losing Trades',
            'Avg Win',
            'Avg Loss',
            'Expectancy',
            'Volatility'
        ];
        
        $csv[] = $this->formatCSVRow($headers);
        
        // Data rows
        foreach ($ranked as $result) {
            $metrics = $result['metrics'];
            
            $row = [
                $result['rank'],
                $result['strategy_name'],
                round($metrics['total_return'], 2),
                round($metrics['annualized_return'], 2),
                round($metrics['sharpe_ratio'], 2),
                round($metrics['sortino_ratio'], 2),
                round($metrics['max_drawdown'], 2),
                round($metrics['win_rate'], 2),
                round($metrics['profit_factor'], 2),
                $metrics['total_trades'],
                $metrics['winning_trades'],
                $metrics['losing_trades'],
                round($metrics['avg_win'], 2),
                round($metrics['avg_loss'], 2),
                round($metrics['expectancy'], 2),
                round($metrics['volatility'], 2)
            ];
            
            $csv[] = $this->formatCSVRow($row);
        }
        
        return implode("\n", $csv);
    }
    
    /**
     * Format metric name for display
     *
     * @param string $metric Metric name
     * @return string Formatted name
     */
    private function formatMetricName(string $metric): string
    {
        $names = [
            'total_return' => 'Total Return',
            'sharpe_ratio' => 'Sharpe Ratio',
            'sortino_ratio' => 'Sortino Ratio',
            'max_drawdown' => 'Max Drawdown',
            'win_rate' => 'Win Rate',
            'profit_factor' => 'Profit Factor'
        ];
        
        return $names[$metric] ?? $metric;
    }
    
    /**
     * Format CSV row
     *
     * @param array<int, mixed> $fields Row fields
     * @return string CSV formatted row
     */
    private function formatCSVRow(array $fields): string
    {
        $escaped = array_map(function ($field) {
            // Escape double quotes and wrap in quotes if contains comma or quote
            if (is_string($field) && (strpos($field, ',') !== false || strpos($field, '"') !== false)) {
                return '"' . str_replace('"', '""', $field) . '"';
            }
            return $field;
        }, $fields);
        
        return implode(',', $escaped);
    }
}
