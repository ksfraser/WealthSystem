<?php

declare(strict_types=1);

namespace App\Crypto;

use App\Enums\CryptoETFType;

/**
 * Crypto ETF Tracking Analyzer
 * 
 * Analyzes how well cryptocurrency ETFs track their underlying assets.
 * 
 * Key Metrics:
 * - Tracking Error: Standard deviation of return differences
 * - Correlation: How closely ETF follows crypto
 * - Beta: ETF sensitivity to crypto moves
 * 
 * ETF Quality Assessment:
 * - Spot ETFs: Expected tracking error 0.5-2%
 * - Futures-based: Expected tracking error 3-15%
 * - High tracking error = poor ETF quality
 * 
 * @package App\Crypto
 */
class CryptoETFTrackingAnalyzer
{
    private CryptoDataService $cryptoService;
    
    public function __construct(CryptoDataService $cryptoService)
    {
        $this->cryptoService = $cryptoService;
    }
    
    /**
     * Calculate tracking error for an ETF
     * 
     * @param string $etfSymbol ETF ticker
     * @param string $cryptoSymbol Underlying crypto
     * @param int $days Analysis period
     * @return array Tracking error data
     */
    public function calculateTrackingError(
        string $etfSymbol,
        string $cryptoSymbol,
        int $days = 30
    ): array {
        // Get historical returns for both
        $etfReturns = $this->getHistoricalReturns($etfSymbol, $days, 'etf');
        $cryptoReturns = $this->getHistoricalReturns($cryptoSymbol, $days, 'crypto');
        
        // Calculate return differences
        $differences = [];
        $count = min(count($etfReturns), count($cryptoReturns));
        
        for ($i = 0; $i < $count; $i++) {
            $differences[] = $etfReturns[$i] - $cryptoReturns[$i];
        }
        
        // Tracking error = standard deviation of differences
        $trackingError = $this->calculateStdDev($differences);
        
        // Calculate total returns
        $etfReturn = array_sum($etfReturns);
        $cryptoReturn = array_sum($cryptoReturns);
        
        return [
            'etf_symbol' => $etfSymbol,
            'crypto_symbol' => $cryptoSymbol,
            'tracking_error_percent' => round($trackingError, 2),
            'etf_return' => round($etfReturn, 2),
            'crypto_return' => round($cryptoReturn, 2),
            'return_difference' => round($etfReturn - $cryptoReturn, 2),
            'correlation' => $this->calculateCorrelation($etfSymbol, $cryptoSymbol, $days),
            'period_days' => $days
        ];
    }
    
    /**
     * Check if tracking error is anomalous for ETF type
     * 
     * @param CryptoETFType $etfType Type of ETF
     * @param float $trackingError Tracking error percentage
     * @return bool True if anomalous
     */
    public function isAnomalousTracking(
        CryptoETFType $etfType,
        float $trackingError
    ): bool {
        $expectedRange = $etfType->getExpectedTrackingError();
        
        return $trackingError < $expectedRange['min'] || 
               $trackingError > $expectedRange['max'];
    }
    
    /**
     * Compare tracking quality across multiple ETFs
     * 
     * @param array $etfs Array of ETF data
     * @param int $days Analysis period
     * @return array Comparison results
     */
    public function compareETFTypes(array $etfs, int $days = 30): array
    {
        $results = [];
        
        foreach ($etfs as $symbol => $data) {
            $tracking = $this->calculateTrackingError(
                $symbol,
                $data['crypto'],
                $days
            );
            
            $results[] = [
                'etf_symbol' => $symbol,
                'etf_type' => $data['type']->value,
                'tracking_error' => $tracking['tracking_error_percent'],
                'correlation' => $tracking['correlation'],
                'is_anomalous' => $this->isAnomalousTracking(
                    $data['type'],
                    $tracking['tracking_error_percent']
                ),
                'quality_score' => $this->calculateQualityScore($tracking, $data['type'])
            ];
        }
        
        // Sort by quality (best first)
        usort($results, fn($a, $b) => $b['quality_score'] <=> $a['quality_score']);
        
        return $results;
    }
    
    /**
     * Calculate rolling tracking error
     * 
     * @param string $etfSymbol ETF ticker
     * @param string $cryptoSymbol Crypto symbol
     * @param int $windowDays Rolling window size
     * @param int $totalDays Total period
     * @return array Rolling tracking errors
     */
    public function calculateRollingTrackingError(
        string $etfSymbol,
        string $cryptoSymbol,
        int $windowDays = 7,
        int $totalDays = 30
    ): array {
        $results = [];
        
        // Simulate rolling calculation
        for ($i = 0; $i <= $totalDays - $windowDays; $i++) {
            $tracking = $this->calculateTrackingError(
                $etfSymbol,
                $cryptoSymbol,
                $windowDays
            );
            
            $results[] = [
                'day' => $i + $windowDays,
                'tracking_error' => $tracking['tracking_error_percent'],
                'date' => date('Y-m-d', strtotime("-" . ($totalDays - $i - $windowDays) . " days"))
            ];
        }
        
        return $results;
    }
    
    /**
     * Identify tracking error trend
     * 
     * @param string $etfSymbol ETF ticker
     * @param string $cryptoSymbol Crypto symbol
     * @param int $days Analysis period
     * @return string Trend: improving, deteriorating, or stable
     */
    public function getTrackingTrend(
        string $etfSymbol,
        string $cryptoSymbol,
        int $days = 90
    ): string {
        $recent = $this->calculateTrackingError($etfSymbol, $cryptoSymbol, 30);
        $historical = $this->calculateTrackingError($etfSymbol, $cryptoSymbol, $days);
        
        $recentError = $recent['tracking_error_percent'];
        $historicalError = $historical['tracking_error_percent'];
        
        $change = $recentError - $historicalError;
        
        if ($change < -0.5) {
            return 'improving'; // Tracking error decreasing
        } elseif ($change > 0.5) {
            return 'deteriorating'; // Tracking error increasing
        }
        
        return 'stable';
    }
    
    /**
     * Check if tracking error warrants an alert
     * 
     * @param string $etfSymbol ETF ticker
     * @param CryptoETFType $etfType ETF type
     * @param string $cryptoSymbol Crypto symbol
     * @param float $threshold Alert threshold
     * @return array|null Alert or null
     */
    public function checkTrackingAlert(
        string $etfSymbol,
        CryptoETFType $etfType,
        string $cryptoSymbol,
        float $threshold = 2.0
    ): ?array {
        $tracking = $this->calculateTrackingError($etfSymbol, $cryptoSymbol, 30);
        $trackingError = $tracking['tracking_error_percent'];
        
        if (!$this->isAnomalousTracking($etfType, $trackingError)) {
            return null;
        }
        
        $expectedRange = $etfType->getExpectedTrackingError();
        
        return [
            'alert_type' => 'TRACKING_ERROR_ANOMALY',
            'etf_symbol' => $etfSymbol,
            'etf_type' => $etfType->value,
            'tracking_error' => $trackingError,
            'expected_range' => $expectedRange,
            'severity' => $trackingError > $expectedRange['max'] * 1.5 ? 'high' : 'medium',
            'message' => sprintf(
                '%s tracking error %.2f%% exceeds expected range (%.2f-%.2f%%)',
                $etfSymbol,
                $trackingError,
                $expectedRange['min'],
                $expectedRange['max']
            ),
            'recommendation' => 'AVOID - Poor tracking quality'
        ];
    }
    
    /**
     * Calculate correlation between ETF and crypto
     * 
     * @param string $etfSymbol ETF ticker
     * @param string $cryptoSymbol Crypto symbol
     * @param int $days Analysis period
     * @return float Correlation coefficient (-1 to 1)
     */
    public function calculateCorrelation(
        string $etfSymbol,
        string $cryptoSymbol,
        int $days = 30
    ): float {
        $etfReturns = $this->getHistoricalReturns($etfSymbol, $days, 'etf');
        $cryptoReturns = $this->getHistoricalReturns($cryptoSymbol, $days, 'crypto');
        
        $count = min(count($etfReturns), count($cryptoReturns));
        
        if ($count < 2) {
            return 0.0;
        }
        
        $etfMean = array_sum($etfReturns) / $count;
        $cryptoMean = array_sum($cryptoReturns) / $count;
        
        $numerator = 0;
        $etfSumSq = 0;
        $cryptoSumSq = 0;
        
        for ($i = 0; $i < $count; $i++) {
            $etfDiff = $etfReturns[$i] - $etfMean;
            $cryptoDiff = $cryptoReturns[$i] - $cryptoMean;
            
            $numerator += $etfDiff * $cryptoDiff;
            $etfSumSq += $etfDiff ** 2;
            $cryptoSumSq += $cryptoDiff ** 2;
        }
        
        $denominator = sqrt($etfSumSq * $cryptoSumSq);
        
        return $denominator > 0 ? round($numerator / $denominator, 4) : 0.0;
    }
    
    /**
     * Calculate beta (ETF sensitivity to crypto moves)
     * 
     * @param string $etfSymbol ETF ticker
     * @param string $cryptoSymbol Crypto symbol
     * @param int $days Analysis period
     * @return float Beta coefficient
     */
    public function calculateBeta(
        string $etfSymbol,
        string $cryptoSymbol,
        int $days = 30
    ): float {
        $etfReturns = $this->getHistoricalReturns($etfSymbol, $days, 'etf');
        $cryptoReturns = $this->getHistoricalReturns($cryptoSymbol, $days, 'crypto');
        
        $count = min(count($etfReturns), count($cryptoReturns));
        
        if ($count < 2) {
            return 1.0;
        }
        
        $cryptoMean = array_sum($cryptoReturns) / $count;
        
        $covariance = 0;
        $variance = 0;
        
        for ($i = 0; $i < $count; $i++) {
            $cryptoDiff = $cryptoReturns[$i] - $cryptoMean;
            $covariance += ($etfReturns[$i] - array_sum($etfReturns) / $count) * $cryptoDiff;
            $variance += $cryptoDiff ** 2;
        }
        
        return $variance > 0 ? round($covariance / $variance, 4) : 1.0;
    }
    
    /**
     * Generate comprehensive tracking report
     * 
     * @param string $etfSymbol ETF ticker
     * @param CryptoETFType $etfType ETF type
     * @param string $cryptoSymbol Crypto symbol
     * @param int $days Analysis period
     * @return array Tracking report
     */
    public function generateTrackingReport(
        string $etfSymbol,
        CryptoETFType $etfType,
        string $cryptoSymbol,
        int $days = 30
    ): array {
        $tracking = $this->calculateTrackingError($etfSymbol, $cryptoSymbol, $days);
        $trend = $this->getTrackingTrend($etfSymbol, $cryptoSymbol, 90);
        $beta = $this->calculateBeta($etfSymbol, $cryptoSymbol, $days);
        $qualityScore = $this->calculateQualityScore($tracking, $etfType);
        
        $qualityRating = 'Poor';
        if ($qualityScore >= 80) {
            $qualityRating = 'Excellent';
        } elseif ($qualityScore >= 60) {
            $qualityRating = 'Good';
        } elseif ($qualityScore >= 40) {
            $qualityRating = 'Fair';
        }
        
        return [
            'etf_symbol' => $etfSymbol,
            'etf_type' => $etfType->value,
            'crypto_symbol' => $cryptoSymbol,
            'tracking_error' => $tracking['tracking_error_percent'],
            'correlation' => $tracking['correlation'],
            'beta' => $beta,
            'trend' => $trend,
            'quality_score' => $qualityScore,
            'quality_rating' => $qualityRating,
            'recommendation' => $this->generateRecommendation($qualityScore, $trend),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get historical returns for asset
     * 
     * @param string $symbol Asset symbol
     * @param int $days Number of days
     * @param string $type Asset type ('etf' or 'crypto')
     * @return array Daily returns
     */
    private function getHistoricalReturns(string $symbol, int $days, string $type): array
    {
        if ($type === 'crypto') {
            $history = $this->cryptoService->getHistoricalPrices($symbol, $days);
        } else {
            // Mock ETF data - in production, fetch from market data API
            $history = [];
            $basePrice = 10.00;
            for ($i = 0; $i < $days; $i++) {
                $history[] = [
                    'price' => $basePrice * (1 + (mt_rand(-200, 200) / 10000))
                ];
            }
        }
        
        $returns = [];
        for ($i = 1; $i < count($history); $i++) {
            $returns[] = (($history[$i]['price'] - $history[$i-1]['price']) / $history[$i-1]['price']) * 100;
        }
        
        return $returns;
    }
    
    /**
     * Calculate standard deviation
     * 
     * @param array $values Array of values
     * @return float Standard deviation
     */
    private function calculateStdDev(array $values): float
    {
        if (count($values) < 2) {
            return 0.0;
        }
        
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(fn($val) => ($val - $mean) ** 2, $values);
        $variance = array_sum($squaredDiffs) / count($squaredDiffs);
        
        return sqrt($variance);
    }
    
    /**
     * Calculate quality score (0-100)
     * 
     * @param array $tracking Tracking data
     * @param CryptoETFType $etfType ETF type
     * @return int Quality score
     */
    private function calculateQualityScore(array $tracking, CryptoETFType $etfType): int
    {
        $expectedRange = $etfType->getExpectedTrackingError();
        $trackingError = $tracking['tracking_error_percent'];
        $correlation = $tracking['correlation'];
        
        // Score based on tracking error (0-60 points)
        $trackingScore = 0;
        if ($trackingError <= $expectedRange['max']) {
            $trackingScore = 60 - ($trackingError / $expectedRange['max'] * 20);
        }
        
        // Score based on correlation (0-40 points)
        $correlationScore = max(0, ($correlation - 0.8) * 200); // 0.8-1.0 = 0-40 points
        
        return min(100, max(0, (int) round($trackingScore + $correlationScore)));
    }
    
    /**
     * Generate recommendation based on quality
     * 
     * @param int $qualityScore Quality score
     * @param string $trend Tracking trend
     * @return string Recommendation
     */
    private function generateRecommendation(int $qualityScore, string $trend): string
    {
        if ($qualityScore >= 80) {
            return 'STRONG BUY - Excellent tracking quality';
        } elseif ($qualityScore >= 60) {
            return $trend === 'improving' 
                ? 'BUY - Good tracking, improving' 
                : 'HOLD - Good tracking quality';
        } elseif ($qualityScore >= 40) {
            return $trend === 'deteriorating'
                ? 'SELL - Fair tracking, deteriorating'
                : 'HOLD - Fair tracking quality';
        }
        
        return 'AVOID - Poor tracking quality';
    }
}
