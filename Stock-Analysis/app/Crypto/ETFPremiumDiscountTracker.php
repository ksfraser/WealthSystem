<?php

declare(strict_types=1);

namespace App\Crypto;

/**
 * ETF Premium/Discount Tracker
 * 
 * Tracks and analyzes premium/discount to NAV for cryptocurrency ETFs.
 * 
 * Key concepts:
 * - Premium: ETF market price > NAV (typically sells signal)
 * - Discount: ETF market price < NAV (typically buy signal)
 * - Calculation: ((Market Price - NAV) / NAV) * 100
 * 
 * Features:
 * - Real-time premium/discount calculation
 * - Historical tracking and trend analysis
 * - Alert generation for excessive premiums/discounts
 * - Arbitrage opportunity identification
 * - Multi-ETF comparison
 * 
 * @package App\Crypto
 */
class ETFPremiumDiscountTracker
{
    private CryptoDataService $cryptoService;
    private array $history = [];
    
    public function __construct(CryptoDataService $cryptoService)
    {
        $this->cryptoService = $cryptoService;
    }
    
    /**
     * Calculate premium/discount for an ETF
     * 
     * @param string $etfSymbol ETF ticker symbol
     * @param float $marketPrice Current market price
     * @param float $nav Net Asset Value
     * @return array Premium/discount data
     */
    public function calculatePremiumDiscount(
        string $etfSymbol,
        float $marketPrice,
        float $nav
    ): array {
        $difference = $marketPrice - $nav;
        $percentDifference = ($difference / $nav) * 100;
        
        return [
            'etf_symbol' => $etfSymbol,
            'market_price' => $marketPrice,
            'nav' => $nav,
            'premium_discount_amount' => round($difference, 4),
            'premium_discount_percent' => round($percentDifference, 2),
            'is_premium' => $percentDifference > 0,
            'timestamp' => time()
        ];
    }
    
    /**
     * Record a premium/discount snapshot
     * 
     * @param string $etfSymbol ETF ticker
     * @param float $marketPrice Current market price
     * @param float $nav NAV
     */
    public function recordSnapshot(string $etfSymbol, float $marketPrice, float $nav): void
    {
        if (!isset($this->history[$etfSymbol])) {
            $this->history[$etfSymbol] = [];
        }
        
        $snapshot = $this->calculatePremiumDiscount($etfSymbol, $marketPrice, $nav);
        $this->history[$etfSymbol][] = $snapshot;
    }
    
    /**
     * Get historical premium/discount data
     * 
     * @param string $etfSymbol ETF ticker
     * @param int $days Number of days of history
     * @return array Historical snapshots
     */
    public function getHistory(string $etfSymbol, int $days = 30): array
    {
        if (!isset($this->history[$etfSymbol])) {
            return [];
        }
        
        $cutoffTime = time() - ($days * 86400);
        
        return array_filter(
            $this->history[$etfSymbol],
            fn($snapshot) => $snapshot['timestamp'] >= $cutoffTime
        );
    }
    
    /**
     * Calculate average premium/discount over a period
     * 
     * @param string $etfSymbol ETF ticker
     * @param int $days Number of days
     * @return float Average premium/discount percent
     */
    public function getAveragePremiumDiscount(string $etfSymbol, int $days = 30): float
    {
        $history = $this->getHistory($etfSymbol, $days);
        
        if (empty($history)) {
            return 0.0;
        }
        
        $sum = array_sum(array_column($history, 'premium_discount_percent'));
        return round($sum / count($history), 2);
    }
    
    /**
     * Check if premium exceeds threshold
     * 
     * @param float $premiumPercent Premium percentage
     * @param float $threshold Alert threshold
     * @return bool True if excessive
     */
    public function isExcessivePremium(float $premiumPercent, float $threshold = 2.0): bool
    {
        return $premiumPercent > $threshold;
    }
    
    /**
     * Check if discount exceeds threshold
     * 
     * @param float $discountPercent Discount percentage (negative)
     * @param float $threshold Alert threshold (negative)
     * @return bool True if excessive
     */
    public function isExcessiveDiscount(float $discountPercent, float $threshold = -2.0): bool
    {
        return $discountPercent < $threshold;
    }
    
    /**
     * Check alert conditions and generate alert if needed
     * 
     * @param string $etfSymbol ETF ticker
     * @param float $marketPrice Current market price
     * @param float $nav NAV
     * @param float $threshold Alert threshold
     * @return array|null Alert data or null
     */
    public function checkAlertConditions(
        string $etfSymbol,
        float $marketPrice,
        float $nav,
        float $threshold = 2.0
    ): ?array {
        $data = $this->calculatePremiumDiscount($etfSymbol, $marketPrice, $nav);
        $percent = $data['premium_discount_percent'];
        
        if ($this->isExcessivePremium($percent, $threshold)) {
            return [
                'alert_type' => 'EXCESSIVE_PREMIUM',
                'severity' => 'high',
                'message' => sprintf(
                    '%s trading at %.2f%% premium to NAV (threshold: %.2f%%)',
                    $etfSymbol,
                    $percent,
                    $threshold
                ),
                'data' => $data,
                'recommendation' => 'SELL or AVOID - ETF is overpriced'
            ];
        }
        
        if ($this->isExcessiveDiscount($percent, -$threshold)) {
            return [
                'alert_type' => 'EXCESSIVE_DISCOUNT',
                'severity' => 'high',
                'message' => sprintf(
                    '%s trading at %.2f%% discount to NAV (threshold: %.2f%%)',
                    $etfSymbol,
                    abs($percent),
                    $threshold
                ),
                'data' => $data,
                'recommendation' => 'BUY - ETF is underpriced'
            ];
        }
        
        return null;
    }
    
    /**
     * Calculate premium/discount volatility
     * 
     * @param string $etfSymbol ETF ticker
     * @param int $days Number of days
     * @return float Standard deviation of premium/discount
     */
    public function getPremiumVolatility(string $etfSymbol, int $days = 30): float
    {
        $history = $this->getHistory($etfSymbol, $days);
        
        if (count($history) < 2) {
            return 0.0;
        }
        
        $values = array_column($history, 'premium_discount_percent');
        $mean = array_sum($values) / count($values);
        
        $squaredDiffs = array_map(fn($val) => pow($val - $mean, 2), $values);
        $variance = array_sum($squaredDiffs) / count($squaredDiffs);
        
        return round(sqrt($variance), 2);
    }
    
    /**
     * Identify premium/discount trend
     * 
     * @param string $etfSymbol ETF ticker
     * @param int $days Number of days
     * @return string Trend: 'widening', 'narrowing', or 'stable'
     */
    public function getPremiumTrend(string $etfSymbol, int $days = 30): string
    {
        $history = $this->getHistory($etfSymbol, $days);
        
        if (count($history) < 3) {
            return 'stable';
        }
        
        $values = array_column($history, 'premium_discount_percent');
        $recent = array_slice($values, -3);
        
        // Simple trend detection: compare first and last of recent values
        $change = end($recent) - reset($recent);
        
        if ($change > 1.0) {
            return 'widening'; // Premium increasing or discount decreasing
        } elseif ($change < -1.0) {
            return 'narrowing'; // Premium decreasing or discount increasing
        }
        
        return 'stable';
    }
    
    /**
     * Evaluate arbitrage opportunity
     * 
     * @param string $etfSymbol ETF ticker
     * @param float $marketPrice Current market price
     * @param float $nav NAV
     * @return array Opportunity analysis
     */
    public function evaluateArbitrageOpportunity(
        string $etfSymbol,
        float $marketPrice,
        float $nav
    ): array {
        $data = $this->calculatePremiumDiscount($etfSymbol, $marketPrice, $nav);
        $percent = $data['premium_discount_percent'];
        
        // Calculate opportunity score (0-100)
        $score = min(100, abs($percent) * 10);
        
        $action = $percent > 2.0 ? 'SELL' : ($percent < -2.0 ? 'BUY' : 'HOLD');
        
        return [
            'etf_symbol' => $etfSymbol,
            'opportunity_score' => round($score, 1),
            'action' => $action,
            'expected_profit_percent' => abs($percent),
            'premium_discount_data' => $data
        ];
    }
    
    /**
     * Compare multiple ETFs by premium/discount
     * 
     * @param array $etfs Array of ETF data [symbol => [market_price, nav]]
     * @return array Sorted comparison results
     */
    public function compareETFs(array $etfs): array
    {
        $results = [];
        
        foreach ($etfs as $symbol => $data) {
            $calc = $this->calculatePremiumDiscount(
                $symbol,
                $data['market_price'],
                $data['nav']
            );
            
            $results[] = [
                'symbol' => $symbol,
                'market_price' => $data['market_price'],
                'nav' => $data['nav'],
                'premium_discount_percent' => $calc['premium_discount_percent'],
                'opportunity' => $this->evaluateArbitrageOpportunity(
                    $symbol,
                    $data['market_price'],
                    $data['nav']
                )
            ];
        }
        
        // Sort by discount (biggest discount first = best buy opportunity)
        usort($results, fn($a, $b) => 
            $a['premium_discount_percent'] <=> $b['premium_discount_percent']
        );
        
        return $results;
    }
    
    /**
     * Calculate break-even price after fees
     * 
     * @param float $nav Current NAV
     * @param float $currentPremium Current premium percentage
     * @param float $fees Trading fees percentage
     * @return float Break-even price
     */
    public function calculateBreakEvenPrice(
        float $nav,
        float $currentPremium,
        float $fees = 0.25
    ): float {
        $entryPrice = $nav * (1 + $currentPremium / 100);
        $totalFees = $entryPrice * ($fees / 100) * 2; // Buy + sell fees
        
        return round($entryPrice + $totalFees, 4);
    }
    
    /**
     * Get intraday premium/discount changes
     * 
     * @param string $etfSymbol ETF ticker
     * @return array Intraday statistics
     */
    public function getIntraDayChanges(string $etfSymbol): array
    {
        $todayStart = strtotime('today');
        $todaySnapshots = array_filter(
            $this->history[$etfSymbol] ?? [],
            fn($s) => $s['timestamp'] >= $todayStart
        );
        
        if (empty($todaySnapshots)) {
            return [
                'max_premium' => 0.0,
                'min_premium' => 0.0,
                'current_premium' => 0.0,
                'range' => 0.0
            ];
        }
        
        $premiums = array_column($todaySnapshots, 'premium_discount_percent');
        $max = max($premiums);
        $min = min($premiums);
        $current = end($premiums);
        
        return [
            'max_premium' => $max,
            'min_premium' => $min,
            'current_premium' => $current,
            'range' => round($max - $min, 2)
        ];
    }
    
    /**
     * Generate comprehensive premium/discount report
     * 
     * @param string $etfSymbol ETF ticker
     * @param int $days Analysis period
     * @return array Comprehensive report
     */
    public function generateReport(string $etfSymbol, int $days = 30): array
    {
        $history = $this->getHistory($etfSymbol, $days);
        
        if (empty($history)) {
            return [
                'symbol' => $etfSymbol,
                'error' => 'No historical data available'
            ];
        }
        
        $current = end($history);
        $average = $this->getAveragePremiumDiscount($etfSymbol, $days);
        $volatility = $this->getPremiumVolatility($etfSymbol, $days);
        $trend = $this->getPremiumTrend($etfSymbol, $days);
        
        $recommendation = 'HOLD';
        if ($current['premium_discount_percent'] > 3.0) {
            $recommendation = 'SELL - High premium';
        } elseif ($current['premium_discount_percent'] < -3.0) {
            $recommendation = 'BUY - Significant discount';
        }
        
        return [
            'symbol' => $etfSymbol,
            'current_premium_discount' => $current['premium_discount_percent'],
            'average_premium_discount' => $average,
            'premium_volatility' => $volatility,
            'trend' => $trend,
            'data_points' => count($history),
            'period_days' => $days,
            'recommendation' => $recommendation,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
}
