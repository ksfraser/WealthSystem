<?php

namespace Ksfraser\WarrenBuffett;

use Ksfraser\StockInfo\BaseModel;
use Ksfraser\StockInfo\DatabaseFactory;

/**
 * Warren Buffett Investment Analysis
 * 
 * Analyzes stocks based on Warren Buffett's investment tenets and detailed
 * valuation methodology including owner earnings, discount rates, and margin of safety.
 */
class BuffettAnalyzer extends BaseModel
{
    protected string $table = 'tenets';
    protected string $primaryKey = 'idtenets';
    protected \PDO $db;

    public function __construct()
    {
        $this->db = DatabaseFactory::getInstance()->getConnection();
    }

    /**
     * Get Buffett tenets analysis for a specific stock
     */
    public function getTenetsAnalysis(int $stockId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE idstockinfo = :stockId ORDER BY lastupdate DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get detailed Buffett evaluation (owner earnings, discount rate, etc.)
     */
    public function getDetailedEvaluation(int $stockId): ?array
    {
        $sql = "SELECT * FROM teneteval WHERE idstockinfo = :stockId ORDER BY lastupdate DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Calculate Buffett tenets score (0-12)
     */
    public function calculateTenetsScore(int $stockId): int
    {
        $tenets = $this->getTenetsAnalysis($stockId);
        if (!$tenets) {
            return 0;
        }

        $score = 0;
        $criteria = [
            'tenet1', 'tenet2', 'tenet3', 'tenet4', 'tenet5', 'tenet6',
            'tenet7', 'tenet8', 'tenet9', 'tenet10', 'tenet11', 'tenet12'
        ];

        foreach ($criteria as $criterion) {
            if (isset($tenets[$criterion]) && $tenets[$criterion] == 1) {
                $score++;
            }
        }

        return $score;
    }

    /**
     * Get investment recommendation based on Buffett methodology
     */
    public function getBuffettRecommendation(int $stockId): array
    {
        $tenetsScore = $this->calculateTenetsScore($stockId);
        $evaluation = $this->getDetailedEvaluation($stockId);
        $tenets = $this->getTenetsAnalysis($stockId);

        $recommendation = [
            'tenets_score' => $tenetsScore,
            'max_tenets_score' => 12,
            'tenets_percentage' => round(($tenetsScore / 12) * 100, 1),
            'owner_earnings' => $evaluation['ownerearnings'] ?? null,
            'discount_rate' => $evaluation['discountrate'] ?? null,
            'growth_rate' => $evaluation['incomegrowth'] ?? null,
            'intrinsic_value' => $evaluation['value'] ?? null,
            'margin_of_safety' => $evaluation['marginsafety'] ?? null,
            'market_cap' => $evaluation['marketcap'] ?? null,
            'recommendation' => $this->getBuffettRecommendationText($tenetsScore, $evaluation),
            'risk_level' => $this->getBuffettRiskLevel($tenetsScore, $evaluation),
            'analysis_date' => $tenets['lastupdate'] ?? null
        ];

        return $recommendation;
    }

    /**
     * Get detailed tenets breakdown
     */
    public function getTenetsBreakdown(int $stockId): array
    {
        $tenets = $this->getTenetsAnalysis($stockId);
        if (!$tenets) {
            return [];
        }

        return [
            'Business Tenets' => [
                'tenet1' => (bool)$tenets['tenet1'],
                'tenet2' => (bool)$tenets['tenet2'],
                'tenet3' => (bool)$tenets['tenet3'],
                'tenet4' => (bool)$tenets['tenet4']
            ],
            'Management Tenets' => [
                'tenet5' => (bool)$tenets['tenet5'],
                'tenet6' => (bool)$tenets['tenet6'],
                'tenet7' => (bool)$tenets['tenet7'],
                'tenet8' => (bool)$tenets['tenet8']
            ],
            'Financial Tenets' => [
                'tenet9' => (bool)$tenets['tenet9'],
                'tenet10' => (bool)$tenets['tenet10']
            ],
            'Value Tenets' => [
                'tenet11' => (bool)$tenets['tenet11'],
                'tenet12' => (bool)$tenets['tenet12']
            ]
        ];
    }

    /**
     * Calculate intrinsic value using owner earnings
     */
    public function calculateIntrinsicValue(int $stockId, float $discountRate = 10.0): ?array
    {
        $evaluation = $this->getDetailedEvaluation($stockId);
        if (!$evaluation) {
            return null;
        }

        $ownerEarnings = $evaluation['ownerearnings'];
        $growthRate = $evaluation['incomegrowth'] / 100; // Convert percentage
        $marketCap = $evaluation['marketcap'];
        
        // Gordon Growth Model for intrinsic value
        if ($discountRate <= $growthRate) {
            return null; // Invalid calculation
        }
        
        $intrinsicValue = $ownerEarnings / ($discountRate/100 - $growthRate);
        $marginOfSafety = ($intrinsicValue - $marketCap) / $intrinsicValue * 100;

        return [
            'owner_earnings' => $ownerEarnings,
            'growth_rate' => $growthRate * 100,
            'discount_rate' => $discountRate,
            'intrinsic_value' => $intrinsicValue,
            'market_cap' => $marketCap,
            'margin_of_safety' => $marginOfSafety,
            'recommendation' => $marginOfSafety > 25 ? 'BUY' : ($marginOfSafety > 0 ? 'HOLD' : 'SELL')
        ];
    }

    /**
     * Get stocks with high Buffett scores
     */
    public function getHighQualityBuffettStocks(int $minScore = 9): array
    {
        $sql = "SELECT t.*, s.symbol, s.name,
                (t.tenet1 + t.tenet2 + t.tenet3 + t.tenet4 + t.tenet5 + t.tenet6 + 
                 t.tenet7 + t.tenet8 + t.tenet9 + t.tenet10 + t.tenet11 + t.tenet12) as total_score
                FROM {$this->table} t
                JOIN stockinfo s ON t.idstockinfo = s.idstockinfo
                HAVING total_score >= :minScore
                ORDER BY total_score DESC, t.lastupdate DESC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':minScore', $minScore, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get stocks with positive margin of safety
     */
    public function getStocksWithMarginOfSafety(float $minMargin = 25.0): array
    {
        $sql = "SELECT te.*, t.*, s.symbol, s.name
                FROM teneteval te
                JOIN tenets t ON te.idstockinfo = t.idstockinfo
                JOIN stockinfo s ON te.idstockinfo = s.idstockinfo
                WHERE te.marginsafety >= :minMargin
                ORDER BY te.marginsafety DESC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':minMargin', $minMargin, \PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Analyze owner earnings trends
     */
    public function analyzeOwnerEarningsTrends(int $stockId): array
    {
        $sql = "SELECT ownerearnings, incomegrowth, lastupdate
                FROM teneteval 
                WHERE idstockinfo = :stockId
                ORDER BY lastupdate DESC
                LIMIT 10";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get recommendation text based on Buffett analysis
     */
    private function getBuffettRecommendationText(int $tenetsScore, ?array $evaluation): string
    {
        $marginOfSafety = $evaluation['marginsafety'] ?? 0;
        
        if ($tenetsScore >= 10 && $marginOfSafety > 25) {
            return 'Strong Buy - Excellent Buffett characteristics with margin of safety';
        }
        if ($tenetsScore >= 8 && $marginOfSafety > 15) {
            return 'Buy - Good Buffett fundamentals with adequate margin';
        }
        if ($tenetsScore >= 6) {
            return 'Hold - Mixed Buffett signals, monitor closely';
        }
        if ($tenetsScore >= 4) {
            return 'Caution - Below Buffett standards';
        }
        return 'Avoid - Poor Buffett fundamentals';
    }

    /**
     * Get risk level based on Buffett analysis
     */
    private function getBuffettRiskLevel(int $tenetsScore, ?array $evaluation): string
    {
        $marginOfSafety = $evaluation['marginsafety'] ?? 0;
        
        if ($tenetsScore >= 9 && $marginOfSafety > 20) return 'Very Low';
        if ($tenetsScore >= 7 && $marginOfSafety > 10) return 'Low';
        if ($tenetsScore >= 5) return 'Medium';
        if ($tenetsScore >= 3) return 'High';
        return 'Very High';
    }

    /**
     * Compare stock against Buffett's ideal criteria
     */
    public function compareToBuffettIdeal(int $stockId): array
    {
        $tenets = $this->getTenetsAnalysis($stockId);
        $evaluation = $this->getDetailedEvaluation($stockId);
        
        if (!$tenets || !$evaluation) {
            return [];
        }

        return [
            'tenets_compliance' => [
                'score' => $this->calculateTenetsScore($stockId),
                'percentage' => round(($this->calculateTenetsScore($stockId) / 12) * 100, 1)
            ],
            'financial_metrics' => [
                'owner_earnings_positive' => $evaluation['ownerearnings'] > 0,
                'growth_rate' => $evaluation['incomegrowth'],
                'margin_of_safety' => $evaluation['marginsafety'],
                'meets_margin_threshold' => $evaluation['marginsafety'] > 25
            ],
            'overall_rating' => $this->getOverallBuffettRating($stockId)
        ];
    }

    /**
     * Get overall Buffett rating
     */
    private function getOverallBuffettRating(int $stockId): string
    {
        $tenetsScore = $this->calculateTenetsScore($stockId);
        $evaluation = $this->getDetailedEvaluation($stockId);
        $marginOfSafety = $evaluation['marginsafety'] ?? 0;
        
        $totalPoints = 0;
        
        // Tenets score (max 60 points)
        $totalPoints += ($tenetsScore / 12) * 60;
        
        // Margin of safety (max 40 points)
        if ($marginOfSafety > 25) $totalPoints += 40;
        elseif ($marginOfSafety > 15) $totalPoints += 30;
        elseif ($marginOfSafety > 5) $totalPoints += 20;
        elseif ($marginOfSafety > 0) $totalPoints += 10;
        
        if ($totalPoints >= 90) return 'A+ (Buffett Ideal)';
        if ($totalPoints >= 80) return 'A (Excellent)';
        if ($totalPoints >= 70) return 'B+ (Very Good)';
        if ($totalPoints >= 60) return 'B (Good)';
        if ($totalPoints >= 50) return 'C+ (Average)';
        if ($totalPoints >= 40) return 'C (Below Average)';
        return 'D (Poor)';
    }
}
