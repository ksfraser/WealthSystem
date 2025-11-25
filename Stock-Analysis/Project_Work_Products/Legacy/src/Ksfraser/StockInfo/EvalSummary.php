<?php

namespace Ksfraser\StockInfo;

/**
 * EvalSummary Model - represents stock evaluation scores and analysis
 * 
 * This model handles evaluation data including:
 * - Management, financial, and business scores
 * - Margin of safety calculations
 * - Total evaluation scores
 * - Investment analysis metrics
 */
class EvalSummary extends BaseModel
{
    protected $table = 'evalsummary';
    protected $primaryKey = 'idstockinfo';
    
    protected $fillable = [
        'idstockinfo',
        'marginsafety',
        'managementscore',
        'financialscore',
        'businessscore',
        'reviseduser',
        'reviseddate',
        'totalscore',
        'ratioscore',
        'iplacecalcscore'
    ];

    /**
     * Get evaluation by stock ID
     */
    public function getEvaluationByStockId($stockInfoId)
    {
        return $this->find($stockInfoId);
    }

    /**
     * Get evaluation with stock info
     */
    public function getEvaluationWithStockInfo($stockInfoId)
    {
        $sql = "SELECT e.*, s.stocksymbol, s.corporatename, s.currentprice, s.marketcap
                FROM {$this->table} e
                JOIN stockinfo s ON e.idstockinfo = s.idstockinfo
                WHERE e.idstockinfo = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$stockInfoId]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    /**
     * Get top rated stocks by total score
     */
    public function getTopRatedStocks($limit = 20, $minScore = 0)
    {
        $sql = "SELECT e.*, s.stocksymbol, s.corporatename, s.currentprice, s.marketcap
                FROM {$this->table} e
                JOIN stockinfo s ON e.idstockinfo = s.idstockinfo
                WHERE e.totalscore > ? AND s.active = 1
                ORDER BY e.totalscore DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$minScore, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get stocks with high margin of safety
     */
    public function getHighMarginOfSafetyStocks($minMargin = 100000, $limit = 20)
    {
        $sql = "SELECT e.*, s.stocksymbol, s.corporatename, s.currentprice, s.marketcap
                FROM {$this->table} e
                JOIN stockinfo s ON e.idstockinfo = s.idstockinfo
                WHERE e.marginsafety > ? AND s.active = 1
                ORDER BY e.marginsafety DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$minMargin, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get evaluation scores breakdown
     */
    public function getScoreBreakdown($stockInfoId)
    {
        $eval = $this->find($stockInfoId);
        if (!$eval) {
            return null;
        }

        return [
            'management_score' => $eval->managementscore,
            'financial_score' => $eval->financialscore,
            'business_score' => $eval->businessscore,
            'ratio_score' => $eval->ratioscore,
            'iplace_calc_score' => $eval->iplacecalcscore,
            'total_score' => $eval->totalscore,
            'margin_safety' => $eval->marginsafety,
            'score_breakdown' => [
                'management_weight' => $eval->managementscore > 0 ? ($eval->managementscore / $eval->totalscore) * 100 : 0,
                'financial_weight' => $eval->financialscore > 0 ? ($eval->financialscore / $eval->totalscore) * 100 : 0,
                'business_weight' => $eval->businessscore > 0 ? ($eval->businessscore / $eval->totalscore) * 100 : 0,
            ]
        ];
    }

    /**
     * Search stocks by evaluation criteria
     */
    public function searchByEvaluationCriteria($criteria = [])
    {
        $sql = "SELECT e.*, s.stocksymbol, s.corporatename, s.currentprice, s.marketcap
                FROM {$this->table} e
                JOIN stockinfo s ON e.idstockinfo = s.idstockinfo
                WHERE s.active = 1";
        $params = [];

        if (isset($criteria['min_total_score'])) {
            $sql .= " AND e.totalscore >= ?";
            $params[] = $criteria['min_total_score'];
        }

        if (isset($criteria['min_management_score'])) {
            $sql .= " AND e.managementscore >= ?";
            $params[] = $criteria['min_management_score'];
        }

        if (isset($criteria['min_financial_score'])) {
            $sql .= " AND e.financialscore >= ?";
            $params[] = $criteria['min_financial_score'];
        }

        if (isset($criteria['min_business_score'])) {
            $sql .= " AND e.businessscore >= ?";
            $params[] = $criteria['min_business_score'];
        }

        if (isset($criteria['min_margin_safety'])) {
            $sql .= " AND e.marginsafety >= ?";
            $params[] = $criteria['min_margin_safety'];
        }

        if (isset($criteria['max_price'])) {
            $sql .= " AND s.currentprice <= ?";
            $params[] = $criteria['max_price'];
        }

        $sql .= " ORDER BY e.totalscore DESC";

        if (isset($criteria['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = $criteria['limit'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get evaluation statistics
     */
    public function getEvaluationStatistics()
    {
        $sql = "SELECT 
                COUNT(*) as total_evaluations,
                AVG(totalscore) as avg_total_score,
                MAX(totalscore) as max_total_score,
                MIN(totalscore) as min_total_score,
                AVG(managementscore) as avg_management_score,
                AVG(financialscore) as avg_financial_score,
                AVG(businessscore) as avg_business_score,
                AVG(marginsafety) as avg_margin_safety,
                COUNT(CASE WHEN totalscore > 0 THEN 1 END) as positive_scores,
                COUNT(CASE WHEN marginsafety > 0 THEN 1 END) as positive_margins
                FROM {$this->table}";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    /**
     * Get score distribution
     */
    public function getScoreDistribution($scoreType = 'totalscore')
    {
        $validScoreTypes = ['totalscore', 'managementscore', 'financialscore', 'businessscore'];
        if (!in_array($scoreType, $validScoreTypes)) {
            throw new \InvalidArgumentException("Invalid score type: {$scoreType}");
        }

        $sql = "SELECT 
                CASE 
                    WHEN {$scoreType} = 0 THEN '0'
                    WHEN {$scoreType} BETWEEN 1 AND 5 THEN '1-5'
                    WHEN {$scoreType} BETWEEN 6 AND 10 THEN '6-10'
                    WHEN {$scoreType} BETWEEN 11 AND 15 THEN '11-15'
                    WHEN {$scoreType} BETWEEN 16 AND 20 THEN '16-20'
                    ELSE '20+'
                END as score_range,
                COUNT(*) as count
                FROM {$this->table}
                GROUP BY score_range
                ORDER BY 
                CASE score_range
                    WHEN '0' THEN 0
                    WHEN '1-5' THEN 1
                    WHEN '6-10' THEN 2
                    WHEN '11-15' THEN 3
                    WHEN '16-20' THEN 4
                    ELSE 5
                END";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Update or create evaluation
     */
    public function updateEvaluation($stockInfoId, $evaluationData)
    {
        $existing = $this->find($stockInfoId);
        
        $evaluationData['reviseddate'] = date('Y-m-d H:i:s');
        
        if ($existing) {
            return $this->update($stockInfoId, $evaluationData);
        } else {
            $evaluationData['idstockinfo'] = $stockInfoId;
            return $this->create($evaluationData);
        }
    }

    /**
     * Calculate total score from component scores
     */
    public function calculateTotalScore($managementScore, $financialScore, $businessScore, $ratioScore = 0, $iplaceScore = 0)
    {
        return $managementScore + $financialScore + $businessScore + $ratioScore + $iplaceScore;
    }

    /**
     * Get comparative analysis
     */
    public function getComparativeAnalysis($stockInfoIds = [])
    {
        if (empty($stockInfoIds)) {
            return [];
        }

        $placeholders = str_repeat('?,', count($stockInfoIds) - 1) . '?';
        
        $sql = "SELECT e.*, s.stocksymbol, s.corporatename, s.currentprice, s.marketcap,
                RANK() OVER (ORDER BY e.totalscore DESC) as total_score_rank,
                RANK() OVER (ORDER BY e.marginsafety DESC) as margin_safety_rank
                FROM {$this->table} e
                JOIN stockinfo s ON e.idstockinfo = s.idstockinfo
                WHERE e.idstockinfo IN ({$placeholders})
                ORDER BY e.totalscore DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($stockInfoIds);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get recent evaluations
     */
    public function getRecentEvaluations($limit = 10)
    {
        $sql = "SELECT e.*, s.stocksymbol, s.corporatename, s.currentprice
                FROM {$this->table} e
                JOIN stockinfo s ON e.idstockinfo = s.idstockinfo
                WHERE s.active = 1
                ORDER BY e.reviseddate DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get undervalued stocks (high score, low price)
     */
    public function getUndervaluedStocks($minScore = 10, $maxPrice = 50, $limit = 20)
    {
        $sql = "SELECT e.*, s.stocksymbol, s.corporatename, s.currentprice, s.marketcap,
                (e.totalscore / NULLIF(s.currentprice, 0)) as score_to_price_ratio
                FROM {$this->table} e
                JOIN stockinfo s ON e.idstockinfo = s.idstockinfo
                WHERE e.totalscore >= ? 
                AND s.currentprice <= ? 
                AND s.currentprice > 0
                AND s.active = 1
                ORDER BY score_to_price_ratio DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$minScore, $maxPrice, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
