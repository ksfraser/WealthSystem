<?php

namespace Ksfraser\StockInfo;

/**
 * StockPrices Model - represents historical stock price data
 * 
 * This model handles daily stock price information including:
 * - OHLC data (Open, High, Low, Close)
 * - Volume and bid/ask data
 * - Daily price changes
 */
class StockPrices extends BaseModel
{
    protected $table = 'stockprices';
    protected $primaryKey = ['symbol', 'date']; // Composite key
    
    protected $fillable = [
        'symbol',
        'date',
        'previous_close',
        'day_open',
        'day_low',
        'day_high',
        'day_close',
        'day_change',
        'bid',
        'ask',
        'volume',
        'idstockinfo'
    ];

    /**
     * Find by composite key - overrides parent find method
     */
    public function find($id)
    {
        // For composite key, $id should be an array [symbol, date]
        if (is_array($id) && count($id) == 2) {
            return $this->findBySymbolAndDate($id[0], $id[1]);
        }
        throw new \InvalidArgumentException("StockPrices requires composite key [symbol, date]");
    }

    /**
     * Find by symbol and date
     */
    public function findBySymbolAndDate($symbol, $date)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE symbol = ? AND date = ?");
        $stmt->execute([$symbol, $date]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    /**
     * Get price history for a symbol
     */
    public function getPriceHistory($symbol, $startDate = null, $endDate = null, $limit = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE symbol = ?";
        $params = [$symbol];

        if ($startDate) {
            $sql .= " AND date >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND date <= ?";
            $params[] = $endDate;
        }

        $sql .= " ORDER BY date DESC";

        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get latest price for a symbol
     */
    public function getLatestPrice($symbol)
    {
        $sql = "SELECT * FROM {$this->table} WHERE symbol = ? ORDER BY date DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$symbol]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    /**
     * Get price data for multiple symbols on a specific date
     */
    public function getPricesForDate($date, $symbols = [])
    {
        $sql = "SELECT * FROM {$this->table} WHERE date = ?";
        $params = [$date];

        if (!empty($symbols)) {
            $placeholders = str_repeat('?,', count($symbols) - 1) . '?';
            $sql .= " AND symbol IN ({$placeholders})";
            $params = array_merge($params, $symbols);
        }

        $sql .= " ORDER BY symbol";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Calculate moving average for a symbol
     */
    public function getMovingAverage($symbol, $days = 20, $endDate = null)
    {
        $endDate = $endDate ?: date('Y-m-d');
        
        $sql = "SELECT AVG(day_close) as moving_average
                FROM (
                    SELECT day_close 
                    FROM {$this->table} 
                    WHERE symbol = ? AND date <= ? 
                    ORDER BY date DESC 
                    LIMIT ?
                ) as recent_prices";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$symbol, $endDate, $days]);
        $result = $stmt->fetch(\PDO::FETCH_OBJ);
        return $result ? $result->moving_average : null;
    }

    /**
     * Get volatility (standard deviation of returns) for a symbol
     */
    public function getVolatility($symbol, $days = 30)
    {
        $sql = "SELECT 
                STDDEV(day_change) as volatility,
                AVG(day_change) as avg_change,
                COUNT(*) as trading_days
                FROM {$this->table} 
                WHERE symbol = ? 
                AND day_change IS NOT NULL 
                ORDER BY date DESC 
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$symbol, $days]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    /**
     * Get high and low prices for a period
     */
    public function getHighLow($symbol, $startDate, $endDate = null)
    {
        $endDate = $endDate ?: date('Y-m-d');
        
        $sql = "SELECT 
                MAX(day_high) as period_high,
                MIN(day_low) as period_low,
                AVG(volume) as avg_volume,
                SUM(volume) as total_volume
                FROM {$this->table} 
                WHERE symbol = ? 
                AND date BETWEEN ? AND ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$symbol, $startDate, $endDate]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    /**
     * Get top volume trading days
     */
    public function getHighVolumedays($symbol, $limit = 10)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE symbol = ? 
                AND volume IS NOT NULL 
                ORDER BY volume DESC 
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$symbol, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Insert or update price data
     */
    public function upsertPrice($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (symbol, date, previous_close, day_open, day_low, day_high, day_close, day_change, bid, ask, volume, idstockinfo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                previous_close = VALUES(previous_close),
                day_open = VALUES(day_open),
                day_low = VALUES(day_low),
                day_high = VALUES(day_high),
                day_close = VALUES(day_close),
                day_change = VALUES(day_change),
                bid = VALUES(bid),
                ask = VALUES(ask),
                volume = VALUES(volume),
                idstockinfo = VALUES(idstockinfo)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['symbol'],
            $data['date'],
            $data['previous_close'] ?? null,
            $data['day_open'] ?? null,
            $data['day_low'] ?? 0,
            $data['day_high'] ?? 0,
            $data['day_close'] ?? 0,
            $data['day_change'] ?? null,
            $data['bid'] ?? null,
            $data['ask'] ?? null,
            $data['volume'] ?? null,
            $data['idstockinfo'] ?? 0
        ]);
    }

    /**
     * Get price performance for a period
     */
    public function getPerformance($symbol, $startDate, $endDate = null)
    {
        $endDate = $endDate ?: date('Y-m-d');
        
        $sql = "SELECT 
                (SELECT day_close FROM {$this->table} WHERE symbol = ? AND date >= ? ORDER BY date ASC LIMIT 1) as start_price,
                (SELECT day_close FROM {$this->table} WHERE symbol = ? AND date <= ? ORDER BY date DESC LIMIT 1) as end_price";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$symbol, $startDate, $symbol, $endDate]);
        $result = $stmt->fetch(\PDO::FETCH_OBJ);
        
        if ($result && $result->start_price && $result->end_price) {
            $result->performance_percent = (($result->end_price - $result->start_price) / $result->start_price) * 100;
        }
        
        return $result;
    }

    /**
     * Get trading statistics for a symbol
     */
    public function getTradingStats($symbol, $days = 252) // 252 = typical trading days in a year
    {
        $sql = "SELECT 
                COUNT(*) as trading_days,
                AVG(day_close) as avg_close,
                AVG(volume) as avg_volume,
                MAX(day_high) as year_high,
                MIN(day_low) as year_low,
                STDDEV(day_close) as price_volatility
                FROM {$this->table} 
                WHERE symbol = ? 
                ORDER BY date DESC 
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$symbol, $days]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }
}
