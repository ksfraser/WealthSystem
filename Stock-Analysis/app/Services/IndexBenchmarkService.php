<?php

namespace App\Services;

use App\DAO\IndexDataDAO;

/**
 * Index Benchmark Service
 * 
 * Compares portfolio/stock performance against major market indexes.
 * Calculates alpha, beta, correlation, and risk-adjusted returns.
 * 
 * Design Principles:
 * - SRP: Only handles benchmark comparisons
 * - DI: Dependencies injected via constructor
 * - SOLID: Interface-based design
 * - DRY: Reusable calculation methods
 * 
 * Supported Indexes:
 * - SPX: S&P 500
 * - IXIC: NASDAQ Composite
 * - DJI: Dow Jones Industrial Average
 * - RUT: Russell 2000
 * 
 * @package App\Services
 * @version 1.0.0
 */
class IndexBenchmarkService
{
    private IndexDataDAO $dao;
    
    /**
     * Constructor with dependency injection
     * 
     * @param IndexDataDAO $dao Index data access object
     */
    public function __construct(IndexDataDAO $dao)
    {
        $this->dao = $dao;
    }
    
    /**
     * Fetch index data for specified period
     * 
     * @param string $indexSymbol Index symbol
     * @param string $period Time period
     * @return array Historical index data
     */
    public function fetchIndexData(string $indexSymbol, string $period): array
    {
        return $this->dao->getIndexData($indexSymbol, $period);
    }
    
    /**
     * Fetch data for multiple indexes
     * 
     * @param string[] $indexes Array of index symbols
     * @param string $period Time period
     * @return array<string, array> Symbol => Data mapping
     */
    public function fetchMultipleIndexes(array $indexes, string $period): array
    {
        $results = [];
        
        foreach ($indexes as $symbol) {
            try {
                $results[$symbol] = $this->dao->getIndexData($symbol, $period);
            } catch (\Exception $e) {
                $results[$symbol] = ['error' => $e->getMessage()];
            }
        }
        
        return $results;
    }
    
    /**
     * Calculate total return from array of periodic returns
     * 
     * Uses compound return formula: (1 + r1) * (1 + r2) * ... - 1
     * 
     * @param float[] $returns Array of periodic returns (%)
     * @return float Total return (%)
     */
    public function calculateTotalReturn(array $returns): float
    {
        $compoundFactor = 1.0;
        
        foreach ($returns as $return) {
            $compoundFactor *= (1 + ($return / 100));
        }
        
        return round(($compoundFactor - 1) * 100, 2);
    }
    
    /**
     * Calculate annualized return
     * 
     * Formula: ((1 + total_return) ^ (12/n)) - 1
     * 
     * @param float[] $returns Array of periodic returns
     * @param int $periods Number of periods (months)
     * @return float Annualized return (%)
     */
    public function calculateAnnualizedReturn(array $returns, int $periods): float
    {
        $totalReturn = $this->calculateTotalReturn($returns);
        
        if ($periods <= 12) {
            return $totalReturn; // Already annualized for <= 1 year
        }
        
        $years = $periods / 12;
        $annualized = (pow(1 + ($totalReturn / 100), 1 / $years) - 1) * 100;
        
        return round($annualized, 2);
    }
    
    /**
     * Calculate relative performance vs benchmark
     * 
     * @param float[] $portfolioReturns Portfolio periodic returns
     * @param float[] $indexReturns Index periodic returns
     * @return array{
     *   portfolio_return: float,
     *   index_return: float,
     *   excess_return: float,
     *   outperformance_periods: int
     * }
     */
    public function calculateRelativePerformance(
        array $portfolioReturns,
        array $indexReturns
    ): array {
        $portfolioTotal = $this->calculateTotalReturn($portfolioReturns);
        $indexTotal = $this->calculateTotalReturn($indexReturns);
        $excessReturn = $portfolioTotal - $indexTotal;
        
        // Count periods where portfolio outperformed
        $outperformance = 0;
        $count = min(count($portfolioReturns), count($indexReturns));
        
        for ($i = 0; $i < $count; $i++) {
            if ($portfolioReturns[$i] > $indexReturns[$i]) {
                $outperformance++;
            }
        }
        
        return [
            'portfolio_return' => $portfolioTotal,
            'index_return' => $indexTotal,
            'excess_return' => round($excessReturn, 2),
            'outperformance_periods' => $outperformance
        ];
    }
    
    /**
     * Calculate beta (systematic risk)
     * 
     * Beta = Covariance(portfolio, index) / Variance(index)
     * 
     * @param float[] $portfolioReturns Portfolio returns
     * @param float[] $indexReturns Index returns
     * @return float Beta coefficient
     */
    public function calculateBeta(array $portfolioReturns, array $indexReturns): float
    {
        $count = min(count($portfolioReturns), count($indexReturns));
        
        if ($count < 2) {
            throw new \InvalidArgumentException('Need at least 2 data points for beta');
        }
        
        // Calculate means
        $portfolioMean = array_sum($portfolioReturns) / $count;
        $indexMean = array_sum($indexReturns) / $count;
        
        // Calculate covariance and variance
        $covariance = 0;
        $indexVariance = 0;
        
        for ($i = 0; $i < $count; $i++) {
            $portfolioDev = $portfolioReturns[$i] - $portfolioMean;
            $indexDev = $indexReturns[$i] - $indexMean;
            
            $covariance += $portfolioDev * $indexDev;
            $indexVariance += $indexDev * $indexDev;
        }
        
        if ($indexVariance == 0) {
            return 1.0; // Default to 1 if no variance
        }
        
        $beta = $covariance / $indexVariance;
        
        return round($beta, 3);
    }
    
    /**
     * Calculate alpha (excess return after adjusting for risk)
     * 
     * Alpha = Portfolio_Return - (Risk_Free_Rate + Beta * (Index_Return - Risk_Free_Rate))
     * 
     * @param float $portfolioReturn Portfolio total return (%)
     * @param float $indexReturn Index total return (%)
     * @param float $beta Portfolio beta
     * @param float $riskFreeRate Risk-free rate (%)
     * @return float Alpha (%)
     */
    public function calculateAlpha(
        float $portfolioReturn,
        float $indexReturn,
        float $beta,
        float $riskFreeRate
    ): float {
        $expectedReturn = $riskFreeRate + $beta * ($indexReturn - $riskFreeRate);
        $alpha = $portfolioReturn - $expectedReturn;
        
        return round($alpha, 2);
    }
    
    /**
     * Calculate correlation coefficient
     * 
     * Correlation = Covariance / (StdDev1 * StdDev2)
     * 
     * @param float[] $portfolioReturns Portfolio returns
     * @param float[] $indexReturns Index returns
     * @return float Correlation (-1 to 1)
     * @throws \InvalidArgumentException If insufficient data
     */
    public function calculateCorrelation(
        array $portfolioReturns,
        array $indexReturns
    ): float {
        $count = min(count($portfolioReturns), count($indexReturns));
        
        if ($count < 2) {
            throw new \InvalidArgumentException('Need at least 2 data points for correlation');
        }
        
        // Calculate means
        $portfolioMean = array_sum($portfolioReturns) / $count;
        $indexMean = array_sum($indexReturns) / $count;
        
        // Calculate covariance and standard deviations
        $covariance = 0;
        $portfolioSumSq = 0;
        $indexSumSq = 0;
        
        for ($i = 0; $i < $count; $i++) {
            $portfolioDev = $portfolioReturns[$i] - $portfolioMean;
            $indexDev = $indexReturns[$i] - $indexMean;
            
            $covariance += $portfolioDev * $indexDev;
            $portfolioSumSq += $portfolioDev * $portfolioDev;
            $indexSumSq += $indexDev * $indexDev;
        }
        
        $denominator = sqrt($portfolioSumSq * $indexSumSq);
        
        if ($denominator == 0) {
            return 0;
        }
        
        $correlation = $covariance / $denominator;
        
        return round($correlation, 3);
    }
    
    /**
     * Calculate Sharpe ratio (risk-adjusted return)
     * 
     * Sharpe = (Mean_Return - Risk_Free_Rate) / StdDev
     * 
     * @param float[] $returns Array of returns
     * @param float $riskFreeRate Risk-free rate (same period as returns)
     * @return float Sharpe ratio
     */
    public function calculateSharpeRatio(array $returns, float $riskFreeRate): float
    {
        $count = count($returns);
        
        if ($count < 2) {
            return 0;
        }
        
        $mean = array_sum($returns) / $count;
        $excessReturn = $mean - $riskFreeRate;
        
        // Calculate standard deviation
        $variance = 0;
        foreach ($returns as $return) {
            $variance += pow($return - $mean, 2);
        }
        $stdDev = sqrt($variance / $count);
        
        if ($stdDev == 0) {
            return 0;
        }
        
        $sharpe = $excessReturn / $stdDev;
        
        return round($sharpe, 2);
    }
    
    /**
     * Calculate Sortino ratio (downside risk-adjusted return)
     * 
     * Sortino = (Mean_Return - Target_Return) / Downside_Deviation
     * 
     * @param float[] $returns Array of returns
     * @param float $targetReturn Target return (usually 0)
     * @return float Sortino ratio
     */
    public function calculateSortinoRatio(array $returns, float $targetReturn): float
    {
        $count = count($returns);
        
        if ($count < 2) {
            return 0;
        }
        
        $mean = array_sum($returns) / $count;
        $excessReturn = $mean - $targetReturn;
        
        // Calculate downside deviation (only negative returns)
        $downsideVariance = 0;
        $downsideCount = 0;
        
        foreach ($returns as $return) {
            if ($return < $targetReturn) {
                $downsideVariance += pow($return - $targetReturn, 2);
                $downsideCount++;
            }
        }
        
        if ($downsideCount == 0) {
            return PHP_FLOAT_MAX; // No downside = infinite Sortino
        }
        
        $downsideDeviation = sqrt($downsideVariance / $downsideCount);
        
        if ($downsideDeviation == 0) {
            return 0;
        }
        
        $sortino = $excessReturn / $downsideDeviation;
        
        return round($sortino, 2);
    }
    
    /**
     * Calculate maximum drawdown
     * 
     * @param float[] $cumulativeValues Cumulative portfolio values
     * @return float Maximum drawdown (%)
     */
    public function calculateMaxDrawdown(array $cumulativeValues): float
    {
        if (count($cumulativeValues) < 2) {
            return 0;
        }
        
        $maxDrawdown = 0;
        $peak = $cumulativeValues[0];
        
        foreach ($cumulativeValues as $value) {
            if ($value > $peak) {
                $peak = $value;
            }
            
            $drawdown = (($value - $peak) / $peak) * 100;
            
            if ($drawdown < $maxDrawdown) {
                $maxDrawdown = $drawdown;
            }
        }
        
        return round($maxDrawdown, 2);
    }
    
    /**
     * Compare performance across multiple time periods
     * 
     * @param string $symbol Stock/portfolio symbol
     * @param string $indexSymbol Benchmark index symbol
     * @param string[] $periods Array of time periods
     * @return array<string, array> Period => Metrics mapping
     */
    public function compareAcrossPeriods(
        string $symbol,
        string $indexSymbol,
        array $periods
    ): array {
        $results = [];
        
        foreach ($periods as $period) {
            // This would typically fetch real data
            // For now, return structure
            $results[$period] = [
                'portfolio_return' => 0,
                'index_return' => 0,
                'excess_return' => 0
            ];
        }
        
        return $results;
    }
    
    /**
     * Format data for performance line chart
     * 
     * @param array $portfolioData Portfolio data points
     * @param array $indexData Index data points
     * @param string $title Chart title
     * @return array Chart.js compatible format
     */
    public function formatForPerformanceChart(
        array $portfolioData,
        array $indexData,
        string $title
    ): array {
        $labels = array_column($portfolioData, 'date');
        $portfolioValues = array_column($portfolioData, 'value');
        $indexValues = array_column($indexData, 'value');
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Portfolio',
                    'data' => $portfolioValues,
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.1)',
                    'fill' => false,
                    'tension' => 0.1
                ],
                [
                    'label' => 'S&P 500',
                    'data' => $indexValues,
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.1)',
                    'fill' => false,
                    'tension' => 0.1
                ]
            ]
        ];
    }
    
    /**
     * Format comparison metrics for table display
     * 
     * @param array $metrics Portfolio metrics
     * @param array $indexMetrics Index metrics
     * @return array Table structure with headers and rows
     */
    public function formatForComparisonTable(array $metrics, array $indexMetrics): array
    {
        $rows = [];
        
        foreach ($metrics as $key => $value) {
            $rows[] = [
                'metric' => ucwords(str_replace('_', ' ', $key)),
                'portfolio' => is_numeric($value) ? number_format($value, 2) . '%' : $value,
                'index' => isset($indexMetrics[$key]) ? 
                    (is_numeric($indexMetrics[$key]) ? number_format($indexMetrics[$key], 2) . '%' : $indexMetrics[$key]) : 
                    'N/A'
            ];
        }
        
        return [
            'headers' => ['Metric', 'Portfolio', 'S&P 500'],
            'rows' => $rows
        ];
    }
    
    /**
     * Align portfolio and index data by date
     * 
     * @param array $portfolioData Portfolio data with dates
     * @param array $indexData Index data with dates
     * @return array{portfolio: array, index: array} Aligned data
     */
    public function alignDataByDate(array $portfolioData, array $indexData): array
    {
        // Create date-keyed arrays
        $portfolioDates = array_column($portfolioData, 'date');
        $indexDates = array_column($indexData, 'date');
        
        // Find common dates
        $commonDates = array_intersect($portfolioDates, $indexDates);
        
        // Filter to common dates
        $alignedPortfolio = array_filter($portfolioData, function($item) use ($commonDates) {
            return in_array($item['date'], $commonDates);
        });
        
        $alignedIndex = array_filter($indexData, function($item) use ($commonDates) {
            return in_array($item['date'], $commonDates);
        });
        
        return [
            'portfolio' => array_values($alignedPortfolio),
            'index' => array_values($alignedIndex)
        ];
    }
}
