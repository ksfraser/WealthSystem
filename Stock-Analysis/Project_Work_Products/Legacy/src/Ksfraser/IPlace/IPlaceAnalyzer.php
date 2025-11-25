<?php

namespace Ksfraser\IPlace;

use Ksfraser\StockInfo\BaseModel;
use Ksfraser\StockInfo\DatabaseFactory;

/**
 * IPlace Investment Analysis
 * 
 * Analyzes stocks based on IPlace calculation methodology including:
 * - Earnings growth and acceleration
 * - PE ratios and valuation metrics
 * - Trading volume and institutional interest
 * - Volatility and risk metrics
 * - Technical indicators
 */
class IPlaceAnalyzer extends BaseModel
{
    protected string $table = 'iplace_calc';
    protected string $primaryKey = 'idiplace_calc';
    protected \PDO $db;

    public function __construct()
    {
        $this->db = DatabaseFactory::getInstance()->getConnection();
    }

    /**
     * Get IPlace analysis for a specific stock
     */
    public function getStockAnalysis(int $stockId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE idstockinfo = :stockId ORDER BY date_updated DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Calculate IPlace composite score
     */
    public function calculateCompositeScore(int $stockId): array
    {
        $analysis = $this->getStockAnalysis($stockId);
        if (!$analysis) {
            return [];
        }

        $scores = [
            'growth_score' => $this->calculateGrowthScore($analysis),
            'valuation_score' => $this->calculateValuationScore($analysis),
            'momentum_score' => $this->calculateMomentumScore($analysis),
            'quality_score' => $this->calculateQualityScore($analysis),
            'risk_score' => $this->calculateRiskScore($analysis)
        ];

        $compositeScore = array_sum($scores) / count($scores);
        
        return [
            'composite_score' => round($compositeScore, 2),
            'individual_scores' => $scores,
            'recommendation' => $this->getIPlaceRecommendation($compositeScore),
            'analysis_date' => $analysis['date_updated']
        ];
    }

    /**
     * Calculate growth score based on earnings metrics
     */
    private function calculateGrowthScore(array $data): float
    {
        $score = 50; // Base score
        
        // Earnings growth rate
        if ($data['earnings_growth_rate'] > 25) $score += 20;
        elseif ($data['earnings_growth_rate'] > 15) $score += 15;
        elseif ($data['earnings_growth_rate'] > 10) $score += 10;
        elseif ($data['earnings_growth_rate'] > 5) $score += 5;
        elseif ($data['earnings_growth_rate'] < 0) $score -= 20;
        
        // Earnings acceleration
        if ($data['earnings_acceleration'] > 20) $score += 15;
        elseif ($data['earnings_acceleration'] > 10) $score += 10;
        elseif ($data['earnings_acceleration'] > 0) $score += 5;
        elseif ($data['earnings_acceleration'] < -10) $score -= 15;
        
        // Sales growth
        if ($data['sales_growth_rate'] > 15) $score += 10;
        elseif ($data['sales_growth_rate'] > 10) $score += 5;
        elseif ($data['sales_growth_rate'] < 0) $score -= 10;
        
        return max(0, min(100, $score));
    }

    /**
     * Calculate valuation score based on PE ratios
     */
    private function calculateValuationScore(array $data): float
    {
        $score = 50; // Base score
        
        // Current PE ratio
        $pe = $data['current_pe'];
        if ($pe > 0 && $pe < 15) $score += 20;
        elseif ($pe >= 15 && $pe < 25) $score += 10;
        elseif ($pe >= 25 && $pe < 35) $score += 0;
        elseif ($pe >= 35) $score -= 15;
        
        // PE vs industry average
        if ($data['pe_vs_industry'] < -20) $score += 15;
        elseif ($data['pe_vs_industry'] < -10) $score += 10;
        elseif ($data['pe_vs_industry'] < 0) $score += 5;
        elseif ($data['pe_vs_industry'] > 20) $score -= 15;
        
        // Forward PE
        if ($data['forward_pe'] > 0 && $data['forward_pe'] < $pe) $score += 10;
        
        return max(0, min(100, $score));
    }

    /**
     * Calculate momentum score based on trading metrics
     */
    private function calculateMomentumScore(array $data): float
    {
        $score = 50; // Base score
        
        // Volume surge
        if ($data['volume_surge'] > 100) $score += 20;
        elseif ($data['volume_surge'] > 50) $score += 15;
        elseif ($data['volume_surge'] > 25) $score += 10;
        elseif ($data['volume_surge'] > 10) $score += 5;
        
        // Institutional interest
        if ($data['institutional_interest'] > 75) $score += 15;
        elseif ($data['institutional_interest'] > 50) $score += 10;
        elseif ($data['institutional_interest'] > 25) $score += 5;
        elseif ($data['institutional_interest'] < 10) $score -= 10;
        
        // Order imbalance
        if ($data['order_imbalance'] > 0.1) $score += 10;
        elseif ($data['order_imbalance'] < -0.1) $score -= 10;
        
        return max(0, min(100, $score));
    }

    /**
     * Calculate quality score based on fundamental metrics
     */
    private function calculateQualityScore(array $data): float
    {
        $score = 50; // Base score
        
        // Dividend metrics
        if ($data['dividend_yield'] > 0) {
            if ($data['dividend_yield'] > 3 && $data['dividend_yield'] < 8) $score += 15;
            elseif ($data['dividend_yield'] > 1 && $data['dividend_yield'] <= 3) $score += 10;
            elseif ($data['dividend_yield'] >= 8) $score += 5; // Very high yield might be risky
        }
        
        // Dividend growth
        if ($data['dividend_growth'] > 10) $score += 10;
        elseif ($data['dividend_growth'] > 5) $score += 5;
        elseif ($data['dividend_growth'] < -10) $score -= 15;
        
        // Debt to equity (lower is better for quality)
        if ($data['debt_to_equity'] < 0.3) $score += 15;
        elseif ($data['debt_to_equity'] < 0.5) $score += 10;
        elseif ($data['debt_to_equity'] < 0.7) $score += 5;
        elseif ($data['debt_to_equity'] > 1.5) $score -= 15;
        
        return max(0, min(100, $score));
    }

    /**
     * Calculate risk score (higher score = lower risk)
     */
    private function calculateRiskScore(array $data): float
    {
        $score = 50; // Base score
        
        // Volatility (lower is better)
        if ($data['volatility'] < 0.15) $score += 20;
        elseif ($data['volatility'] < 0.25) $score += 15;
        elseif ($data['volatility'] < 0.35) $score += 10;
        elseif ($data['volatility'] < 0.45) $score += 5;
        elseif ($data['volatility'] > 0.6) $score -= 20;
        
        // Beta (closer to 1 is more stable)
        $beta = $data['beta'];
        if ($beta >= 0.8 && $beta <= 1.2) $score += 15;
        elseif ($beta >= 0.6 && $beta <= 1.5) $score += 10;
        elseif ($beta >= 0.4 && $beta <= 1.8) $score += 5;
        elseif ($beta > 2.0 || $beta < 0.2) $score -= 15;
        
        // Short interest (lower is better)
        if ($data['short_interest'] < 2) $score += 10;
        elseif ($data['short_interest'] < 5) $score += 5;
        elseif ($data['short_interest'] > 20) $score -= 15;
        elseif ($data['short_interest'] > 10) $score -= 10;
        
        return max(0, min(100, $score));
    }

    /**
     * Get IPlace recommendation based on composite score
     */
    public function getIPlaceRecommendation(float $score): string
    {
        if ($score >= 85) return 'Strong Buy - Excellent IPlace metrics';
        if ($score >= 75) return 'Buy - Good IPlace fundamentals';
        if ($score >= 65) return 'Moderate Buy - Above average metrics';
        if ($score >= 55) return 'Hold - Average performance';
        if ($score >= 45) return 'Weak Hold - Below average metrics';
        if ($score >= 35) return 'Sell - Poor fundamentals';
        return 'Strong Sell - Very poor metrics';
    }

    /**
     * Get detailed metrics breakdown
     */
    public function getMetricsBreakdown(int $stockId): array
    {
        $analysis = $this->getStockAnalysis($stockId);
        if (!$analysis) {
            return [];
        }

        return [
            'Growth Metrics' => [
                'earnings_growth_rate' => $analysis['earnings_growth_rate'],
                'earnings_acceleration' => $analysis['earnings_acceleration'],
                'sales_growth_rate' => $analysis['sales_growth_rate']
            ],
            'Valuation Metrics' => [
                'current_pe' => $analysis['current_pe'],
                'forward_pe' => $analysis['forward_pe'],
                'pe_vs_industry' => $analysis['pe_vs_industry'],
                'peg_ratio' => $analysis['peg_ratio']
            ],
            'Trading Metrics' => [
                'volume_surge' => $analysis['volume_surge'],
                'institutional_interest' => $analysis['institutional_interest'],
                'order_imbalance' => $analysis['order_imbalance']
            ],
            'Risk Metrics' => [
                'volatility' => $analysis['volatility'],
                'beta' => $analysis['beta'],
                'short_interest' => $analysis['short_interest']
            ],
            'Quality Metrics' => [
                'dividend_yield' => $analysis['dividend_yield'],
                'dividend_growth' => $analysis['dividend_growth'],
                'debt_to_equity' => $analysis['debt_to_equity']
            ]
        ];
    }

    /**
     * Find stocks with high IPlace scores
     */
    public function getTopIPlaceStocks(int $limit = 20): array
    {
        // This would need a custom calculation, but for now return top by earnings growth
        $sql = "SELECT i.*, s.symbol, s.name
                FROM {$this->table} i
                JOIN stockinfo s ON i.idstockinfo = s.idstockinfo
                WHERE i.earnings_growth_rate > 15
                AND i.current_pe > 0 AND i.current_pe < 30
                AND i.volatility < 0.4
                ORDER BY i.earnings_growth_rate DESC, i.current_pe ASC
                LIMIT :limit";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Analyze earnings momentum
     */
    public function analyzeEarningsMomentum(int $stockId): array
    {
        $sql = "SELECT earnings_growth_rate, earnings_acceleration, sales_growth_rate, date_updated
                FROM {$this->table}
                WHERE idstockinfo = :stockId
                ORDER BY date_updated DESC
                LIMIT 12"; // Last 12 periods
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        if (empty($data)) {
            return [];
        }

        $avgGrowth = array_sum(array_column($data, 'earnings_growth_rate')) / count($data);
        $avgAcceleration = array_sum(array_column($data, 'earnings_acceleration')) / count($data);
        
        return [
            'historical_data' => $data,
            'average_growth_rate' => round($avgGrowth, 2),
            'average_acceleration' => round($avgAcceleration, 2),
            'momentum_trend' => $this->determineMomentumTrend($data)
        ];
    }

    /**
     * Determine momentum trend
     */
    private function determineMomentumTrend(array $data): string
    {
        if (count($data) < 3) return 'Insufficient Data';
        
        $recent = array_slice($data, 0, 3);
        $earlier = array_slice($data, 3, 3);
        
        $recentAvg = array_sum(array_column($recent, 'earnings_growth_rate')) / count($recent);
        $earlierAvg = array_sum(array_column($earlier, 'earnings_growth_rate')) / count($earlier);
        
        if ($recentAvg > $earlierAvg + 5) return 'Accelerating';
        if ($recentAvg < $earlierAvg - 5) return 'Decelerating';
        return 'Stable';
    }

    /**
     * Screen stocks by IPlace criteria
     */
    public function screenByIPlaceCriteria(array $criteria = []): array
    {
        $conditions = [];
        $params = [];
        
        if (isset($criteria['min_earnings_growth'])) {
            $conditions[] = "earnings_growth_rate >= :min_earnings_growth";
            $params['min_earnings_growth'] = $criteria['min_earnings_growth'];
        }
        
        if (isset($criteria['max_pe'])) {
            $conditions[] = "current_pe <= :max_pe AND current_pe > 0";
            $params['max_pe'] = $criteria['max_pe'];
        }
        
        if (isset($criteria['max_volatility'])) {
            $conditions[] = "volatility <= :max_volatility";
            $params['max_volatility'] = $criteria['max_volatility'];
        }
        
        if (isset($criteria['min_institutional'])) {
            $conditions[] = "institutional_interest >= :min_institutional";
            $params['min_institutional'] = $criteria['min_institutional'];
        }
        
        $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
        
        $sql = "SELECT i.*, s.symbol, s.name
                FROM {$this->table} i
                JOIN stockinfo s ON i.idstockinfo = s.idstockinfo
                {$whereClause}
                ORDER BY i.earnings_growth_rate DESC
                LIMIT 50";
                
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
