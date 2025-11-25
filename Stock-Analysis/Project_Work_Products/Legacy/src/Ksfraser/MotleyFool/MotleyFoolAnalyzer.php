<?php

namespace Ksfraser\MotleyFool;

use Ksfraser\StockInfo\BaseModel;
use Ksfraser\StockInfo\DatabaseFactory;

/**
 * Motley Fool Investment Analysis
 * 
 * Analyzes stocks based on Motley Fool's 10-point investment criteria:
 * 1. Simple business model validation
 * 2. Reasonable valuation assessment
 * 3. Core focus evaluation
 * 4. Double-digit sales growth
 * 5. Rising cash flow
 * 6. Book value analysis
 * 7. Margin assessment
 * 8. ROE evaluation
 * 9. Insider ownership
 * 10. Regular dividend policy
 */
class MotleyFoolAnalyzer extends BaseModel
{
    protected string $table = 'motleyfool';
    protected string $primaryKey = 'idmotleyfool';
    protected \PDO $db;

    public function __construct()
    {
        $this->db = DatabaseFactory::getInstance()->getConnection();
    }

    /**
     * Get Motley Fool analysis for a specific stock
     */
    public function getStockAnalysis(int $stockId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE idstockinfo = :stockId ORDER BY lastupdate DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Calculate Motley Fool score (0-10)
     */
    public function calculateScore(int $stockId): int
    {
        $analysis = $this->getStockAnalysis($stockId);
        if (!$analysis) {
            return 0;
        }

        $score = 0;
        $criteria = [
            'simplebusiness',
            'reasonablevaluation',
            'corefocus',
            'doubledigitsales',
            'risingcashflow',
            'bookvalue',
            'margins',
            'roe',
            'insiderownership',
            'regulardividend'
        ];

        foreach ($criteria as $criterion) {
            if (isset($analysis[$criterion]) && $analysis[$criterion] == 1) {
                $score++;
            }
        }

        return $score;
    }

    /**
     * Get investment recommendation based on Motley Fool criteria
     */
    public function getRecommendation(int $stockId): array
    {
        $score = $this->calculateScore($stockId);
        $analysis = $this->getStockAnalysis($stockId);

        $recommendation = [
            'score' => $score,
            'max_score' => 10,
            'percentage' => round(($score / 10) * 100, 1),
            'recommendation' => $this->getRecommendationText($score),
            'risk_level' => $this->getRiskLevel($score),
            'analysis_date' => $analysis['lastupdate'] ?? null
        ];

        return $recommendation;
    }

    /**
     * Get detailed criteria breakdown
     */
    public function getCriteriaBreakdown(int $stockId): array
    {
        $analysis = $this->getStockAnalysis($stockId);
        if (!$analysis) {
            return [];
        }

        return [
            'Business Model' => [
                'simple_business' => (bool)$analysis['simplebusiness'],
                'description' => 'Business model is simple and understandable'
            ],
            'Valuation' => [
                'reasonable_valuation' => (bool)$analysis['reasonablevaluation'],
                'description' => 'Stock is reasonably valued relative to earnings'
            ],
            'Focus' => [
                'core_focus' => (bool)$analysis['corefocus'],
                'description' => 'Company maintains focus on core business'
            ],
            'Growth Metrics' => [
                'double_digit_sales' => (bool)$analysis['doubledigitsales'],
                'description' => 'Achieving double-digit sales growth'
            ],
            'Cash Flow' => [
                'rising_cash_flow' => (bool)$analysis['risingcashflow'],
                'description' => 'Demonstrating rising cash flow trends'
            ],
            'Book Value' => [
                'book_value' => (bool)$analysis['bookvalue'],
                'description' => 'Strong book value fundamentals'
            ],
            'Profitability' => [
                'margins' => (bool)$analysis['margins'],
                'description' => 'Healthy profit margins'
            ],
            'Returns' => [
                'roe' => (bool)$analysis['roe'],
                'description' => 'Strong return on equity'
            ],
            'Ownership' => [
                'insider_ownership' => (bool)$analysis['insiderownership'],
                'description' => 'Significant insider ownership alignment'
            ],
            'Dividends' => [
                'regular_dividend' => (bool)$analysis['regulardividend'],
                'description' => 'Regular dividend payment history'
            ]
        ];
    }

    /**
     * Get stocks that meet all Motley Fool criteria
     */
    public function getPerfectScoreStocks(): array
    {
        $sql = "SELECT m.*, s.symbol, s.name 
                FROM {$this->table} m
                JOIN stockinfo s ON m.idstockinfo = s.idstockinfo
                WHERE m.simplebusiness = 1 
                AND m.reasonablevaluation = 1 
                AND m.corefocus = 1 
                AND m.doubledigitsales = 1 
                AND m.risingcashflow = 1 
                AND m.bookvalue = 1 
                AND m.margins = 1 
                AND m.roe = 1 
                AND m.insiderownership = 1 
                AND m.regulardividend = 1
                ORDER BY m.lastupdate DESC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get stocks by minimum score threshold
     */
    public function getStocksByMinScore(int $minScore = 7): array
    {
        $sql = "SELECT m.*, s.symbol, s.name,
                (m.simplebusiness + m.reasonablevaluation + m.corefocus + 
                 m.doubledigitsales + m.risingcashflow + m.bookvalue + 
                 m.margins + m.roe + m.insiderownership + m.regulardividend) as total_score
                FROM {$this->table} m
                JOIN stockinfo s ON m.idstockinfo = s.idstockinfo
                HAVING total_score >= :minScore
                ORDER BY total_score DESC, m.lastupdate DESC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':minScore', $minScore, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get recommendation text based on score
     */
    private function getRecommendationText(int $score): string
    {
        if ($score >= 9) return 'Strong Buy - Excellent Motley Fool fundamentals';
        if ($score >= 7) return 'Buy - Good Motley Fool characteristics';
        if ($score >= 5) return 'Hold - Mixed Motley Fool signals';
        if ($score >= 3) return 'Caution - Below average Motley Fool criteria';
        return 'Avoid - Poor Motley Fool fundamentals';
    }

    /**
     * Get risk level based on score
     */
    private function getRiskLevel(int $score): string
    {
        if ($score >= 8) return 'Low';
        if ($score >= 6) return 'Medium';
        if ($score >= 4) return 'High';
        return 'Very High';
    }

    /**
     * Analyze sector performance using Motley Fool criteria
     */
    public function analyzeSectorPerformance(): array
    {
        $sql = "SELECT s.sector,
                COUNT(*) as total_stocks,
                AVG(m.simplebusiness + m.reasonablevaluation + m.corefocus + 
                    m.doubledigitsales + m.risingcashflow + m.bookvalue + 
                    m.margins + m.roe + m.insiderownership + m.regulardividend) as avg_score,
                SUM(CASE WHEN (m.simplebusiness + m.reasonablevaluation + m.corefocus + 
                              m.doubledigitsales + m.risingcashflow + m.bookvalue + 
                              m.margins + m.roe + m.insiderownership + m.regulardividend) >= 7 
                         THEN 1 ELSE 0 END) as high_quality_count
                FROM {$this->table} m
                JOIN stockinfo s ON m.idstockinfo = s.idstockinfo
                WHERE s.sector IS NOT NULL AND s.sector != ''
                GROUP BY s.sector
                ORDER BY avg_score DESC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
