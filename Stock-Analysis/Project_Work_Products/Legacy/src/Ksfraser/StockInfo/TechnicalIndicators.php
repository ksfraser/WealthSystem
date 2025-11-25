<?php

namespace Ksfraser\StockInfo;

/**
 * Technical Indicators Model
 * 
 * Manages calculated technical indicators (RSI, MACD, SMA, EMA, etc.)
 */
class TechnicalIndicators extends BaseModel
{
    protected string $table = 'technical_indicators';
    protected string $primaryKey = 'id';
    protected \PDO $db;

    protected array $fillable = [
        'idstockinfo', 'symbol', 'date', 'indicator_type', 'indicator_value',
        'period', 'signal_line', 'histogram'
    ];

    public function __construct()
    {
        $this->db = DatabaseFactory::getInstance()->getConnection();
    }

    /**
     * Get indicators for a specific stock and date range
     */
    public function getIndicatorsForStock(int $stockId, string $startDate, string $endDate): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE idstockinfo = :stockId 
                AND date BETWEEN :startDate AND :endDate
                ORDER BY date DESC, indicator_type, period";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->bindParam(':startDate', $startDate);
        $stmt->bindParam(':endDate', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get specific indicator for stock
     */
    public function getIndicator(int $stockId, string $indicatorType, int $period = null, string $date = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE idstockinfo = :stockId 
                AND indicator_type = :indicatorType";
                
        if ($period !== null) {
            $sql .= " AND period = :period";
        }
        
        if ($date !== null) {
            $sql .= " AND date = :date";
        } else {
            $sql .= " ORDER BY date DESC LIMIT 1";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        $stmt->bindParam(':indicatorType', $indicatorType);
        
        if ($period !== null) {
            $stmt->bindParam(':period', $period, \PDO::PARAM_INT);
        }
        
        if ($date !== null) {
            $stmt->bindParam(':date', $date);
        }
        
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Save calculated indicator
     */
    public function saveIndicator(array $data): bool
    {
        $sql = "INSERT INTO {$this->table} 
                (idstockinfo, symbol, date, indicator_type, indicator_value, period, signal_line, histogram)
                VALUES (:idstockinfo, :symbol, :date, :indicator_type, :indicator_value, :period, :signal_line, :histogram)
                ON DUPLICATE KEY UPDATE
                indicator_value = VALUES(indicator_value),
                signal_line = VALUES(signal_line),
                histogram = VALUES(histogram),
                updated_at = CURRENT_TIMESTAMP";
                
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':idstockinfo' => $data['idstockinfo'],
            ':symbol' => $data['symbol'],
            ':date' => $data['date'],
            ':indicator_type' => $data['indicator_type'],
            ':indicator_value' => $data['indicator_value'] ?? null,
            ':period' => $data['period'] ?? null,
            ':signal_line' => $data['signal_line'] ?? null,
            ':histogram' => $data['histogram'] ?? null
        ]);
    }

    /**
     * Delete indicators for stock
     */
    public function deleteIndicatorsForStock(int $stockId, string $indicatorType = null): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE idstockinfo = :stockId";
        
        if ($indicatorType) {
            $sql .= " AND indicator_type = :indicatorType";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':stockId', $stockId, \PDO::PARAM_INT);
        
        if ($indicatorType) {
            $stmt->bindParam(':indicatorType', $indicatorType);
        }
        
        return $stmt->execute();
    }

    /**
     * Get recent indicators across all stocks
     */
    public function getRecentIndicators(int $limit = 50): array
    {
        $sql = "SELECT ti.*, s.symbol, s.corporatename as name
                FROM {$this->table} ti
                JOIN stockinfo s ON ti.idstockinfo = s.idstockinfo
                ORDER BY ti.calculation_date DESC, ti.date DESC
                LIMIT :limit";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
