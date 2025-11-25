<?php

namespace Ksfraser\StockInfo;

/**
 * Candlestick Patterns Model
 * 
 * Manages identified candlestick patterns with TA-Lib
 */
class CandlestickPatterns extends BaseModel
{
    protected string $table = 'candlestick_patterns';
    protected string $primaryKey = 'id';
    protected \PDO $db;

    protected array $fillable = [
        'idstockinfo', 'symbol', 'date', 'pattern_name', 'pattern_value',
        'action_recommendation', 'confidence'
    ];

    public function __construct(\PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    /**
     * Get patterns for a specific stock and date range
     */
    public function getPatternsForStock(int $stockId, string $startDate, string $endDate): array
    {
        $sql = "SELECT cp.*, ca.candlestick_detail, ca.candlestick_action 
                FROM {$this->table} cp
                LEFT JOIN candlestickactions ca ON cp.pattern_name = ca.candlestick_name
                WHERE cp.idstockinfo = :stockId 
                AND cp.date BETWEEN :startDate AND :endDate
                ORDER BY cp.date DESC, cp.confidence DESC";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->bindParam(':startDate', $startDate);
        $stmt->bindParam(':endDate', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get recent patterns across all stocks
     */
    public function getRecentPatterns(int $limit = 50): array
    {
        $sql = "SELECT cp.*, s.symbol, s.name, ca.candlestick_detail, ca.candlestick_action
                FROM {$this->table} cp
                JOIN stockinfo s ON cp.idstockinfo = s.idstockinfo
                LEFT JOIN candlestickactions ca ON cp.pattern_name = ca.candlestick_name
                ORDER BY cp.date DESC, cp.confidence DESC
                LIMIT :limit";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Save detected pattern
     */
    public function savePattern(array $data): bool
    {
        $sql = "INSERT INTO {$this->table} 
                (idstockinfo, symbol, date, pattern_name, pattern_value, action_recommendation, confidence)
                VALUES (:idstockinfo, :symbol, :date, :pattern_name, :pattern_value, :action_recommendation, :confidence)
                ON DUPLICATE KEY UPDATE
                pattern_value = VALUES(pattern_value),
                action_recommendation = VALUES(action_recommendation),
                confidence = VALUES(confidence),
                updated_at = CURRENT_TIMESTAMP";
                
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':idstockinfo' => $data['idstockinfo'],
            ':symbol' => $data['symbol'],
            ':date' => $data['date'],
            ':pattern_name' => $data['pattern_name'],
            ':pattern_value' => $data['pattern_value'],
            ':action_recommendation' => $data['action_recommendation'] ?? null,
            ':confidence' => $data['confidence'] ?? null
        ]);
    }

    /**
     * Get pattern statistics
     */
    public function getPatternStats(): array
    {
        $sql = "SELECT 
                    pattern_name,
                    COUNT(*) as total_occurrences,
                    AVG(confidence) as avg_confidence,
                    action_recommendation,
                    COUNT(CASE WHEN pattern_value > 0 THEN 1 END) as bullish_count,
                    COUNT(CASE WHEN pattern_value < 0 THEN 1 END) as bearish_count
                FROM {$this->table}
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                GROUP BY pattern_name, action_recommendation
                ORDER BY total_occurrences DESC";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
