<?php

namespace Ksfraser\Evaluation;

use Ksfraser\StockInfo\BaseModel;
use Ksfraser\StockInfo\DatabaseFactory;

/**
 * Comprehensive Evaluation Analysis
 * 
 * Analyzes stocks based on the four evaluation categories:
 * - Business Evaluation (simple, regulated, needed product, etc.)
 * - Financial Evaluation (owner earnings, debt ratios, ROE, etc.)
 * - Management Evaluation (ownership, reinvestment, communication, etc.)
 * - Market Evaluation (intrinsic value, margin of safety, etc.)
 */
class EvaluationAnalyzer extends BaseModel
{
    protected \PDO $db;

    public function __construct()
    {
        $this->db = DatabaseFactory::getInstance()->getConnection();
    }

    /**
     * Get comprehensive evaluation for a specific stock
     */
    public function getComprehensiveEvaluation(int $stockId): array
    {
        return [
            'business' => $this->getBusinessEvaluation($stockId),
            'financial' => $this->getFinancialEvaluation($stockId),
            'management' => $this->getManagementEvaluation($stockId),
            'market' => $this->getMarketEvaluation($stockId),
            'overall_score' => $this->calculateOverallScore($stockId),
            'recommendation' => $this->getOverallRecommendation($stockId)
        ];
    }

    /**
     * Get business evaluation
     */
    public function getBusinessEvaluation(int $stockId): ?array
    {
        $sql = "SELECT * FROM evalbusiness WHERE idstockinfo = :stockId ORDER BY lasteval DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$result) return null;

        return [
            'data' => $result,
            'score' => $this->calculateBusinessScore($result),
            'criteria' => [
                'Simple Business' => (bool)$result['simple'],
                'Regulated Industry' => (bool)$result['regulated'],
                'Needed Product/Service' => (bool)$result['neededproduct'],
                'No Close Substitutes' => (bool)$result['noclosesubstitute'],
                'Consistent History' => (bool)$result['cosnsistanthistory']
            ]
        ];
    }

    /**
     * Get financial evaluation
     */
    public function getFinancialEvaluation(int $stockId): ?array
    {
        $sql = "SELECT * FROM evalfinancial WHERE idstockinfo = :stockId ORDER BY lasteval DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$result) return null;

        return [
            'data' => $result,
            'score' => $this->calculateFinancialScore($result),
            'metrics' => [
                'Owner Earnings' => $result['ownerearnings'],
                'Retained Earnings MV' => (bool)$result['retainearningsmv'],
                'Debt Ratio' => $result['debtratio'],
                'Acceptable Debt' => $result['acceptabledebt'],
                'ROE' => (bool)$result['roe'],
                'Low Cost Producer' => (bool)$result['lowcost']
            ]
        ];
    }

    /**
     * Get management evaluation
     */
    public function getManagementEvaluation(int $stockId): ?array
    {
        $sql = "SELECT * FROM evalmanagement WHERE idstockinfo = :stockId ORDER BY lasteval DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$result) return null;

        return [
            'data' => $result,
            'score' => $this->calculateManagementScore($result),
            'criteria' => [
                'Management Ownership' => (bool)$result['managementowners'],
                'Benefits Reinvestment' => (bool)$result['benefitreinvest'],
                'Expand by Purchase' => (bool)$result['expandbypurchase'],
                'Mimic Competition' => (bool)$result['mimiccompetition'],
                'Avoid Hyperactivity' => !(bool)$result['hyperactivity'], // Inverted
                'Consistent History' => (bool)$result['cosnsistanthistory'],
                'Communicate More Than GAAP' => (bool)$result['communicatemorethangaap'],
                'Public Confession' => (bool)$result['publicconfession'],
                'Avoid Frequent Reorg' => !(bool)$result['frfeqreorg'] // Inverted
            ]
        ];
    }

    /**
     * Get market evaluation
     */
    public function getMarketEvaluation(int $stockId): ?array
    {
        $sql = "SELECT * FROM evalmarket WHERE idstockinfo = :stockId ORDER BY lasteval DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$result) return null;

        return [
            'data' => $result,
            'score' => $this->calculateMarketScore($result),
            'metrics' => [
                'Owner Earnings' => $result['ownerearnings'],
                'Discount Rate' => $result['discountrate'],
                'Income Growth' => $result['incomegrowth'],
                'Intrinsic Value' => $result['value'],
                'Market Cap' => $result['marketcap'],
                'Margin of Safety' => $result['marginsafety'],
                'Net Value' => $result['netvalue']
            ]
        ];
    }

    /**
     * Calculate business evaluation score
     */
    private function calculateBusinessScore(array $data): float
    {
        $criteria = ['simple', 'regulated', 'neededproduct', 'noclosesubstitute', 'cosnsistanthistory'];
        $total = 0;
        $count = 0;
        
        foreach ($criteria as $criterion) {
            if (isset($data[$criterion])) {
                $total += (int)$data[$criterion];
                $count++;
            }
        }
        
        return $count > 0 ? round(($total / $count) * 100, 1) : 0;
    }

    /**
     * Calculate financial evaluation score
     */
    private function calculateFinancialScore(array $data): float
    {
        $score = 0;
        $maxScore = 100;
        
        // ROE (20 points)
        if ($data['roe']) $score += 20;
        
        // Low cost producer (20 points)
        if ($data['lowcost']) $score += 20;
        
        // Retained earnings (15 points)
        if ($data['retainearningsmv']) $score += 15;
        
        // Debt ratio (25 points based on acceptable debt)
        if ($data['debtratio'] <= $data['acceptabledebt']) {
            $score += 25;
        } elseif ($data['debtratio'] <= $data['acceptabledebt'] * 1.5) {
            $score += 15;
        } elseif ($data['debtratio'] <= $data['acceptabledebt'] * 2) {
            $score += 5;
        }
        
        // Owner earnings (20 points)
        if ($data['ownerearnings'] > 0) $score += 20;
        elseif ($data['ownerearnings'] >= 0) $score += 10;
        
        return round($score, 1);
    }

    /**
     * Calculate management evaluation score
     */
    private function calculateManagementScore(array $data): float
    {
        $positivePoints = 0;
        $totalCriteria = 9;
        
        // Positive criteria
        $positiveCriteria = [
            'managementowners', 'benefitreinvest', 'expandbypurchase',
            'mimiccompetition', 'cosnsistanthistory', 'communicatemorethangaap',
            'publicconfession'
        ];
        
        foreach ($positiveCriteria as $criterion) {
            if ($data[$criterion]) $positivePoints++;
        }
        
        // Negative criteria (inverted scoring)
        if (!$data['hyperactivity']) $positivePoints++;
        if (!$data['frfeqreorg']) $positivePoints++;
        
        return round(($positivePoints / $totalCriteria) * 100, 1);
    }

    /**
     * Calculate market evaluation score
     */
    private function calculateMarketScore(array $data): float
    {
        $score = 50; // Base score
        
        // Margin of safety
        $marginOfSafety = $data['marginsafety'];
        if ($marginOfSafety > 25) $score += 30;
        elseif ($marginOfSafety > 15) $score += 20;
        elseif ($marginOfSafety > 5) $score += 10;
        elseif ($marginOfSafety > 0) $score += 5;
        elseif ($marginOfSafety < -25) $score -= 30;
        elseif ($marginOfSafety < -15) $score -= 20;
        elseif ($marginOfSafety < 0) $score -= 10;
        
        // Income growth
        if ($data['incomegrowth'] > 15) $score += 15;
        elseif ($data['incomegrowth'] > 10) $score += 10;
        elseif ($data['incomegrowth'] > 5) $score += 5;
        elseif ($data['incomegrowth'] < -10) $score -= 15;
        elseif ($data['incomegrowth'] < 0) $score -= 5;
        
        // Owner earnings
        if ($data['ownerearnings'] > 0) $score += 5;
        elseif ($data['ownerearnings'] < 0) $score -= 10;
        
        return round(max(0, min(100, $score)), 1);
    }

    /**
     * Calculate overall evaluation score
     */
    public function calculateOverallScore(int $stockId): array
    {
        $business = $this->getBusinessEvaluation($stockId);
        $financial = $this->getFinancialEvaluation($stockId);
        $management = $this->getManagementEvaluation($stockId);
        $market = $this->getMarketEvaluation($stockId);
        
        $scores = [];
        $weights = ['business' => 0.25, 'financial' => 0.30, 'management' => 0.25, 'market' => 0.20];
        $totalScore = 0;
        $totalWeight = 0;
        
        if ($business) {
            $scores['business'] = $business['score'];
            $totalScore += $business['score'] * $weights['business'];
            $totalWeight += $weights['business'];
        }
        
        if ($financial) {
            $scores['financial'] = $financial['score'];
            $totalScore += $financial['score'] * $weights['financial'];
            $totalWeight += $weights['financial'];
        }
        
        if ($management) {
            $scores['management'] = $management['score'];
            $totalScore += $management['score'] * $weights['management'];
            $totalWeight += $weights['management'];
        }
        
        if ($market) {
            $scores['market'] = $market['score'];
            $totalScore += $market['score'] * $weights['market'];
            $totalWeight += $weights['market'];
        }
        
        $overallScore = $totalWeight > 0 ? round($totalScore / $totalWeight, 1) : 0;
        
        return [
            'overall_score' => $overallScore,
            'individual_scores' => $scores,
            'grade' => $this->getGrade($overallScore)
        ];
    }

    /**
     * Get overall recommendation
     */
    public function getOverallRecommendation(int $stockId): string
    {
        $overallData = $this->calculateOverallScore($stockId);
        $score = $overallData['overall_score'];
        
        if ($score >= 85) return 'Strong Buy - Excellent across all evaluations';
        if ($score >= 75) return 'Buy - Strong fundamentals';
        if ($score >= 65) return 'Moderate Buy - Good overall metrics';
        if ($score >= 55) return 'Hold - Average performance';
        if ($score >= 45) return 'Weak Hold - Below average';
        if ($score >= 35) return 'Sell - Poor fundamentals';
        return 'Strong Sell - Very poor across evaluations';
    }

    /**
     * Get letter grade
     */
    private function getGrade(float $score): string
    {
        if ($score >= 95) return 'A+';
        if ($score >= 90) return 'A';
        if ($score >= 85) return 'A-';
        if ($score >= 80) return 'B+';
        if ($score >= 75) return 'B';
        if ($score >= 70) return 'B-';
        if ($score >= 65) return 'C+';
        if ($score >= 60) return 'C';
        if ($score >= 55) return 'C-';
        if ($score >= 50) return 'D+';
        if ($score >= 45) return 'D';
        return 'F';
    }

    /**
     * Find top rated stocks across all evaluations
     */
    public function getTopRatedStocks(int $limit = 20): array
    {
        $sql = "SELECT DISTINCT s.idstockinfo, s.symbol, s.name, s.sector
                FROM stockinfo s
                WHERE EXISTS (SELECT 1 FROM evalbusiness eb WHERE eb.idstockinfo = s.idstockinfo)
                AND EXISTS (SELECT 1 FROM evalfinancial ef WHERE ef.idstockinfo = s.idstockinfo)
                AND EXISTS (SELECT 1 FROM evalmanagement em WHERE em.idstockinfo = s.idstockinfo)
                AND EXISTS (SELECT 1 FROM evalmarket emk WHERE emk.idstockinfo = s.idstockinfo)
                LIMIT :limit";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        $stocks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $results = [];
        
        foreach ($stocks as $stock) {
            $overallData = $this->calculateOverallScore($stock['idstockinfo']);
            $results[] = [
                'stock_info' => $stock,
                'overall_score' => $overallData['overall_score'],
                'grade' => $overallData['grade'],
                'individual_scores' => $overallData['individual_scores']
            ];
        }
        
        // Sort by overall score
        usort($results, function($a, $b) {
            return $b['overall_score'] <=> $a['overall_score'];
        });
        
        return $results;
    }

    /**
     * Compare stocks side by side
     */
    public function compareStocks(array $stockIds): array
    {
        $comparison = [];
        
        foreach ($stockIds as $stockId) {
            $stockInfo = $this->getStockInfo($stockId);
            if ($stockInfo) {
                $comparison[$stockId] = [
                    'stock_info' => $stockInfo,
                    'evaluations' => $this->getComprehensiveEvaluation($stockId)
                ];
            }
        }
        
        return $comparison;
    }

    /**
     * Get stock basic info
     */
    private function getStockInfo(int $stockId): ?array
    {
        $sql = "SELECT * FROM stockinfo WHERE idstockinfo = :stockId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Analyze evaluation trends over time
     */
    public function analyzeEvaluationTrends(int $stockId): array
    {
        $trends = [];
        
        // Business evaluation trends
        $sql = "SELECT summary, lasteval FROM evalbusiness WHERE idstockinfo = :stockId ORDER BY lasteval";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->execute();
        $trends['business'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Financial evaluation trends
        $sql = "SELECT summary, lasteval FROM evalfinancial WHERE idstockinfo = :stockId ORDER BY lasteval";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->execute();
        $trends['financial'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Management evaluation trends
        $sql = "SELECT summary, lasteval FROM evalmanagement WHERE idstockinfo = :stockId ORDER BY lasteval";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->execute();
        $trends['management'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return $trends;
    }
}
