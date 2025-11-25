<?php

namespace Ksfraser\Analysis;

use Ksfraser\MotleyFool\MotleyFoolAnalyzer;
use Ksfraser\WarrenBuffett\BuffettAnalyzer;
use Ksfraser\IPlace\IPlaceAnalyzer;
use Ksfraser\Evaluation\EvaluationAnalyzer;
use Ksfraser\StockInfo\DatabaseFactory;

/**
 * Unified Investment Analysis Manager
 * 
 * Combines all investment methodologies to provide comprehensive stock analysis:
 * - Motley Fool 10-point criteria
 * - Warren Buffett tenets and valuation
 * - IPlace technical and fundamental metrics
 * - Comprehensive evaluation (business, financial, management, market)
 */
class UnifiedAnalyzer
{
    private MotleyFoolAnalyzer $motleyFoolAnalyzer;
    private BuffettAnalyzer $buffettAnalyzer;
    private IPlaceAnalyzer $iPlaceAnalyzer;
    private EvaluationAnalyzer $evaluationAnalyzer;
    private \PDO $db;

    public function __construct()
    {
        $this->motleyFoolAnalyzer = new MotleyFoolAnalyzer();
        $this->buffettAnalyzer = new BuffettAnalyzer();
        $this->iPlaceAnalyzer = new IPlaceAnalyzer();
        $this->evaluationAnalyzer = new EvaluationAnalyzer();
        $this->db = DatabaseFactory::getInstance()->getConnection();
    }

    /**
     * Get comprehensive analysis for a stock using all methodologies
     */
    public function getComprehensiveAnalysis(int $stockId): array
    {
        $stockInfo = $this->getStockInfo($stockId);
        if (!$stockInfo) {
            return ['error' => 'Stock not found'];
        }

        return [
            'stock_info' => $stockInfo,
            'motley_fool' => $this->getMotleyFoolAnalysis($stockId),
            'warren_buffett' => $this->getBuffettAnalysis($stockId),
            'iplace' => $this->getIPlaceAnalysis($stockId),
            'evaluation' => $this->getEvaluationAnalysis($stockId),
            'unified_score' => $this->calculateUnifiedScore($stockId),
            'consensus_recommendation' => $this->getConsensusRecommendation($stockId),
            'risk_assessment' => $this->assessOverallRisk($stockId)
        ];
    }

    /**
     * Get Motley Fool analysis
     */
    private function getMotleyFoolAnalysis(int $stockId): array
    {
        $recommendation = $this->motleyFoolAnalyzer->getRecommendation($stockId);
        $breakdown = $this->motleyFoolAnalyzer->getCriteriaBreakdown($stockId);
        
        return [
            'available' => !empty($recommendation),
            'score' => $recommendation['score'] ?? 0,
            'percentage' => $recommendation['percentage'] ?? 0,
            'recommendation' => $recommendation['recommendation'] ?? 'No data',
            'risk_level' => $recommendation['risk_level'] ?? 'Unknown',
            'criteria_breakdown' => $breakdown
        ];
    }

    /**
     * Get Warren Buffett analysis
     */
    private function getBuffettAnalysis(int $stockId): array
    {
        $recommendation = $this->buffettAnalyzer->getBuffettRecommendation($stockId);
        $breakdown = $this->buffettAnalyzer->getTenetsBreakdown($stockId);
        
        return [
            'available' => !empty($recommendation),
            'tenets_score' => $recommendation['tenets_score'] ?? 0,
            'tenets_percentage' => $recommendation['tenets_percentage'] ?? 0,
            'margin_of_safety' => $recommendation['margin_of_safety'] ?? null,
            'intrinsic_value' => $recommendation['intrinsic_value'] ?? null,
            'recommendation' => $recommendation['recommendation'] ?? 'No data',
            'risk_level' => $recommendation['risk_level'] ?? 'Unknown',
            'tenets_breakdown' => $breakdown
        ];
    }

    /**
     * Get IPlace analysis
     */
    private function getIPlaceAnalysis(int $stockId): array
    {
        $compositeScore = $this->iPlaceAnalyzer->calculateCompositeScore($stockId);
        $breakdown = $this->iPlaceAnalyzer->getMetricsBreakdown($stockId);
        
        return [
            'available' => !empty($compositeScore),
            'composite_score' => $compositeScore['composite_score'] ?? 0,
            'individual_scores' => $compositeScore['individual_scores'] ?? [],
            'recommendation' => $compositeScore['recommendation'] ?? 'No data',
            'metrics_breakdown' => $breakdown
        ];
    }

    /**
     * Get comprehensive evaluation analysis
     */
    private function getEvaluationAnalysis(int $stockId): array
    {
        $comprehensive = $this->evaluationAnalyzer->getComprehensiveEvaluation($stockId);
        
        return [
            'available' => !empty($comprehensive['overall_score']),
            'overall_score' => $comprehensive['overall_score']['overall_score'] ?? 0,
            'grade' => $comprehensive['overall_score']['grade'] ?? 'N/A',
            'individual_scores' => $comprehensive['overall_score']['individual_scores'] ?? [],
            'recommendation' => $comprehensive['recommendation'] ?? 'No data',
            'business' => $comprehensive['business'] ?? null,
            'financial' => $comprehensive['financial'] ?? null,
            'management' => $comprehensive['management'] ?? null,
            'market' => $comprehensive['market'] ?? null
        ];
    }

    /**
     * Calculate unified score across all methodologies
     */
    public function calculateUnifiedScore(int $stockId): array
    {
        $scores = [];
        $weights = [];
        
        // Motley Fool Score (0-100)
        $mfRecommendation = $this->motleyFoolAnalyzer->getRecommendation($stockId);
        if (!empty($mfRecommendation)) {
            $scores['motley_fool'] = $mfRecommendation['percentage'];
            $weights['motley_fool'] = 0.25;
        }
        
        // Buffett Score (0-100)
        $buffettRecommendation = $this->buffettAnalyzer->getBuffettRecommendation($stockId);
        if (!empty($buffettRecommendation)) {
            $scores['buffett'] = $buffettRecommendation['tenets_percentage'];
            $weights['buffett'] = 0.30;
        }
        
        // IPlace Score (0-100)
        $iPlaceScore = $this->iPlaceAnalyzer->calculateCompositeScore($stockId);
        if (!empty($iPlaceScore)) {
            $scores['iplace'] = $iPlaceScore['composite_score'];
            $weights['iplace'] = 0.25;
        }
        
        // Evaluation Score (0-100)
        $evalScore = $this->evaluationAnalyzer->calculateOverallScore($stockId);
        if (!empty($evalScore)) {
            $scores['evaluation'] = $evalScore['overall_score'];
            $weights['evaluation'] = 0.20;
        }
        
        // Calculate weighted average
        $totalScore = 0;
        $totalWeight = 0;
        
        foreach ($scores as $methodology => $score) {
            $weight = $weights[$methodology];
            $totalScore += $score * $weight;
            $totalWeight += $weight;
        }
        
        $unifiedScore = $totalWeight > 0 ? round($totalScore / $totalWeight, 1) : 0;
        
        return [
            'unified_score' => $unifiedScore,
            'individual_scores' => $scores,
            'weights_used' => $weights,
            'grade' => $this->getUnifiedGrade($unifiedScore),
            'confidence_level' => $this->getConfidenceLevel(count($scores))
        ];
    }

    /**
     * Get consensus recommendation across all methodologies
     */
    public function getConsensusRecommendation(int $stockId): array
    {
        $recommendations = [];
        $buySignals = 0;
        $sellSignals = 0;
        $holdSignals = 0;
        
        // Collect recommendations from each methodology
        $mfRec = $this->motleyFoolAnalyzer->getRecommendation($stockId);
        if (!empty($mfRec)) {
            $recommendations['motley_fool'] = $mfRec['recommendation'];
            $buySignals += $this->isBuyRecommendation($mfRec['recommendation']) ? 1 : 0;
            $sellSignals += $this->isSellRecommendation($mfRec['recommendation']) ? 1 : 0;
            $holdSignals += $this->isHoldRecommendation($mfRec['recommendation']) ? 1 : 0;
        }
        
        $buffettRec = $this->buffettAnalyzer->getBuffettRecommendation($stockId);
        if (!empty($buffettRec)) {
            $recommendations['buffett'] = $buffettRec['recommendation'];
            $buySignals += $this->isBuyRecommendation($buffettRec['recommendation']) ? 1 : 0;
            $sellSignals += $this->isSellRecommendation($buffettRec['recommendation']) ? 1 : 0;
            $holdSignals += $this->isHoldRecommendation($buffettRec['recommendation']) ? 1 : 0;
        }
        
        $iPlaceRec = $this->iPlaceAnalyzer->calculateCompositeScore($stockId);
        if (!empty($iPlaceRec)) {
            $recommendations['iplace'] = $iPlaceRec['recommendation'];
            $buySignals += $this->isBuyRecommendation($iPlaceRec['recommendation']) ? 1 : 0;
            $sellSignals += $this->isSellRecommendation($iPlaceRec['recommendation']) ? 1 : 0;
            $holdSignals += $this->isHoldRecommendation($iPlaceRec['recommendation']) ? 1 : 0;
        }
        
        $evalRec = $this->evaluationAnalyzer->getOverallRecommendation($stockId);
        if (!empty($evalRec)) {
            $recommendations['evaluation'] = $evalRec;
            $buySignals += $this->isBuyRecommendation($evalRec) ? 1 : 0;
            $sellSignals += $this->isSellRecommendation($evalRec) ? 1 : 0;
            $holdSignals += $this->isHoldRecommendation($evalRec) ? 1 : 0;
        }
        
        // Determine consensus
        $totalSignals = $buySignals + $sellSignals + $holdSignals;
        $consensus = 'INSUFFICIENT DATA';
        
        if ($totalSignals > 0) {
            if ($buySignals > $sellSignals && $buySignals > $holdSignals) {
                $consensus = 'CONSENSUS BUY';
            } elseif ($sellSignals > $buySignals && $sellSignals > $holdSignals) {
                $consensus = 'CONSENSUS SELL';
            } elseif ($holdSignals >= $buySignals && $holdSignals >= $sellSignals) {
                $consensus = 'CONSENSUS HOLD';
            } else {
                $consensus = 'MIXED SIGNALS';
            }
        }
        
        return [
            'consensus' => $consensus,
            'individual_recommendations' => $recommendations,
            'signal_distribution' => [
                'buy' => $buySignals,
                'hold' => $holdSignals,
                'sell' => $sellSignals
            ],
            'confidence' => $this->calculateConsensusConfidence($buySignals, $holdSignals, $sellSignals)
        ];
    }

    /**
     * Assess overall risk across all methodologies
     */
    public function assessOverallRisk(int $stockId): array
    {
        $riskFactors = [];
        
        // Collect risk assessments
        $mfRec = $this->motleyFoolAnalyzer->getRecommendation($stockId);
        if (!empty($mfRec)) {
            $riskFactors['motley_fool'] = $mfRec['risk_level'];
        }
        
        $buffettRec = $this->buffettAnalyzer->getBuffettRecommendation($stockId);
        if (!empty($buffettRec)) {
            $riskFactors['buffett'] = $buffettRec['risk_level'];
        }
        
        // Convert to numeric and calculate average
        $riskScores = [];
        foreach ($riskFactors as $methodology => $riskLevel) {
            $riskScores[] = $this->convertRiskToNumeric($riskLevel);
        }
        
        $avgRiskScore = !empty($riskScores) ? array_sum($riskScores) / count($riskScores) : 3;
        
        return [
            'overall_risk' => $this->convertNumericToRisk($avgRiskScore),
            'individual_risks' => $riskFactors,
            'risk_score' => round($avgRiskScore, 1),
            'risk_factors_count' => count($riskFactors)
        ];
    }

    /**
     * Get top stocks using unified scoring
     */
    public function getTopUnifiedStocks(int $limit = 20): array
    {
        // Get stocks that have data in multiple methodologies
        $sql = "SELECT DISTINCT s.idstockinfo, s.symbol, s.name, s.sector
                FROM stockinfo s
                WHERE EXISTS (SELECT 1 FROM motleyfool mf WHERE mf.idstockinfo = s.idstockinfo)
                OR EXISTS (SELECT 1 FROM tenets t WHERE t.idstockinfo = s.idstockinfo)
                OR EXISTS (SELECT 1 FROM iplace_calc ic WHERE ic.idstockinfo = s.idstockinfo)
                OR EXISTS (SELECT 1 FROM evalbusiness eb WHERE eb.idstockinfo = s.idstockinfo)
                ORDER BY s.symbol
                LIMIT 100"; // Get larger sample to score
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stocks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $results = [];
        foreach ($stocks as $stock) {
            $unifiedScore = $this->calculateUnifiedScore($stock['idstockinfo']);
            if ($unifiedScore['unified_score'] > 0) {
                $results[] = [
                    'stock_info' => $stock,
                    'unified_score' => $unifiedScore['unified_score'],
                    'grade' => $unifiedScore['grade'],
                    'confidence' => $unifiedScore['confidence_level'],
                    'methodologies_count' => count($unifiedScore['individual_scores'])
                ];
            }
        }
        
        // Sort by unified score
        usort($results, function($a, $b) {
            return $b['unified_score'] <=> $a['unified_score'];
        });
        
        return array_slice($results, 0, $limit);
    }

    /**
     * Helper methods for recommendation classification
     */
    private function isBuyRecommendation(string $recommendation): bool
    {
        return strpos(strtolower($recommendation), 'buy') !== false;
    }

    private function isSellRecommendation(string $recommendation): bool
    {
        return strpos(strtolower($recommendation), 'sell') !== false || 
               strpos(strtolower($recommendation), 'avoid') !== false;
    }

    private function isHoldRecommendation(string $recommendation): bool
    {
        return strpos(strtolower($recommendation), 'hold') !== false ||
               strpos(strtolower($recommendation), 'caution') !== false;
    }

    private function convertRiskToNumeric(string $riskLevel): int
    {
        switch (strtolower($riskLevel)) {
            case 'very low': return 1;
            case 'low': return 2;
            case 'medium': return 3;
            case 'high': return 4;
            case 'very high': return 5;
            default: return 3;
        }
    }

    private function convertNumericToRisk(float $score): string
    {
        if ($score <= 1.5) return 'Very Low';
        if ($score <= 2.5) return 'Low';
        if ($score <= 3.5) return 'Medium';
        if ($score <= 4.5) return 'High';
        return 'Very High';
    }

    private function getUnifiedGrade(float $score): string
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

    private function getConfidenceLevel(int $methodologyCount): string
    {
        if ($methodologyCount >= 4) return 'Very High';
        if ($methodologyCount >= 3) return 'High';
        if ($methodologyCount >= 2) return 'Medium';
        if ($methodologyCount >= 1) return 'Low';
        return 'Very Low';
    }

    private function calculateConsensusConfidence(int $buy, int $hold, int $sell): string
    {
        $total = $buy + $hold + $sell;
        if ($total == 0) return 'No Data';
        
        $max = max($buy, $hold, $sell);
        $percentage = ($max / $total) * 100;
        
        if ($percentage >= 75) return 'High';
        if ($percentage >= 60) return 'Medium';
        return 'Low';
    }

    private function getStockInfo(int $stockId): ?array
    {
        $sql = "SELECT * FROM stockinfo WHERE idstockinfo = :stockId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
