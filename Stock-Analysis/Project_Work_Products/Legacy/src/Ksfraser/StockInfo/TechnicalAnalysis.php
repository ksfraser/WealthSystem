<?php

namespace Ksfraser\StockInfo;

/**
 * TechnicalAnalysis Model - represents technical analysis data
 * 
 * This model handles technical indicators and analysis including:
 * - Moving averages
 * - Technical indicators
 * - Chart patterns
 * - Trading signals
 */
class TechnicalAnalysis extends BaseModel
{
    protected $table = 'technicalanalysis';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'stocksymbol',
        'date',
        'sma_20',
        'sma_50',
        'sma_200',
        'ema_12',
        'ema_26',
        'macd',
        'macd_signal',
        'macd_histogram',
        'rsi',
        'bollinger_upper',
        'bollinger_middle',
        'bollinger_lower',
        'volume_sma',
        'price_above_sma20',
        'price_above_sma50',
        'price_above_sma200',
        'golden_cross',
        'death_cross',
        'analysis_date'
    ];

    /**
     * Get latest technical analysis for a symbol
     */
    public function getLatestAnalysis($stockSymbol)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE stocksymbol = ? 
                ORDER BY date DESC 
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$stockSymbol]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    /**
     * Get technical analysis history for a symbol
     */
    public function getAnalysisHistory($stockSymbol, $startDate = null, $endDate = null, $limit = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE stocksymbol = ?";
        $params = [$stockSymbol];

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
     * Get stocks with golden cross signal
     */
    public function getGoldenCrossStocks($date = null)
    {
        $date = $date ?: date('Y-m-d');
        
        $sql = "SELECT ta.*, s.corporatename, s.currentprice
                FROM {$this->table} ta
                JOIN stockinfo s ON ta.stocksymbol = s.stocksymbol
                WHERE ta.golden_cross = 1 
                AND ta.date = ?
                AND s.active = 1
                ORDER BY ta.stocksymbol";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$date]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get stocks with death cross signal
     */
    public function getDeathCrossStocks($date = null)
    {
        $date = $date ?: date('Y-m-d');
        
        $sql = "SELECT ta.*, s.corporatename, s.currentprice
                FROM {$this->table} ta
                JOIN stockinfo s ON ta.stocksymbol = s.stocksymbol
                WHERE ta.death_cross = 1 
                AND ta.date = ?
                AND s.active = 1
                ORDER BY ta.stocksymbol";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$date]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get overbought stocks (RSI > 70)
     */
    public function getOverboughtStocks($rsiThreshold = 70, $date = null)
    {
        $date = $date ?: date('Y-m-d');
        
        $sql = "SELECT ta.*, s.corporatename, s.currentprice
                FROM {$this->table} ta
                JOIN stockinfo s ON ta.stocksymbol = s.stocksymbol
                WHERE ta.rsi > ? 
                AND ta.date = ?
                AND s.active = 1
                ORDER BY ta.rsi DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$rsiThreshold, $date]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get oversold stocks (RSI < 30)
     */
    public function getOversoldStocks($rsiThreshold = 30, $date = null)
    {
        $date = $date ?: date('Y-m-d');
        
        $sql = "SELECT ta.*, s.corporatename, s.currentprice
                FROM {$this->table} ta
                JOIN stockinfo s ON ta.stocksymbol = s.stocksymbol
                WHERE ta.rsi < ? 
                AND ta.date = ?
                AND s.active = 1
                ORDER BY ta.rsi ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$rsiThreshold, $date]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get stocks breaking above moving averages
     */
    public function getStocksAboveMovingAverages($movingAverage = 'sma_20', $date = null)
    {
        $date = $date ?: date('Y-m-d');
        $validMAs = ['sma_20', 'sma_50', 'sma_200'];
        
        if (!in_array($movingAverage, $validMAs)) {
            throw new \InvalidArgumentException("Invalid moving average: {$movingAverage}");
        }

        $priceAboveColumn = 'price_above_' . $movingAverage;
        
        $sql = "SELECT ta.*, s.corporatename, s.currentprice
                FROM {$this->table} ta
                JOIN stockinfo s ON ta.stocksymbol = s.stocksymbol
                WHERE ta.{$priceAboveColumn} = 1 
                AND ta.date = ?
                AND s.active = 1
                ORDER BY ta.stocksymbol";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$date]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get MACD buy signals (MACD crosses above signal line)
     */
    public function getMACDBuySignals($date = null)
    {
        $date = $date ?: date('Y-m-d');
        
        $sql = "SELECT ta.*, s.corporatename, s.currentprice
                FROM {$this->table} ta
                JOIN stockinfo s ON ta.stocksymbol = s.stocksymbol
                WHERE ta.macd > ta.macd_signal 
                AND ta.macd_histogram > 0
                AND ta.date = ?
                AND s.active = 1
                ORDER BY ta.macd_histogram DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$date]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get Bollinger Band squeeze (price near middle band)
     */
    public function getBollingerBandSqueeze($tolerance = 0.02, $date = null)
    {
        $date = $date ?: date('Y-m-d');
        
        $sql = "SELECT ta.*, s.corporatename, s.currentprice,
                ABS(s.currentprice - ta.bollinger_middle) / ta.bollinger_middle as price_to_middle_ratio
                FROM {$this->table} ta
                JOIN stockinfo s ON ta.stocksymbol = s.stocksymbol
                WHERE ABS(s.currentprice - ta.bollinger_middle) / ta.bollinger_middle <= ?
                AND ta.date = ?
                AND s.active = 1
                ORDER BY price_to_middle_ratio ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$tolerance, $date]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get volume breakout stocks
     */
    public function getVolumeBreakouts($volumeMultiplier = 2, $date = null)
    {
        $date = $date ?: date('Y-m-d');
        
        $sql = "SELECT ta.*, s.corporatename, s.currentprice, sp.volume as current_volume,
                (sp.volume / ta.volume_sma) as volume_ratio
                FROM {$this->table} ta
                JOIN stockinfo s ON ta.stocksymbol = s.stocksymbol
                JOIN stockprices sp ON ta.stocksymbol = sp.symbol AND sp.date = ta.date
                WHERE (sp.volume / ta.volume_sma) >= ?
                AND ta.date = ?
                AND s.active = 1
                ORDER BY volume_ratio DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$volumeMultiplier, $date]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get technical strength score
     */
    public function getTechnicalStrengthScore($stockSymbol, $date = null)
    {
        $date = $date ?: date('Y-m-d');
        
        $analysis = $this->getLatestAnalysis($stockSymbol);
        if (!$analysis) {
            return null;
        }

        $score = 0;
        $maxScore = 10;

        // RSI scoring (2 points)
        if ($analysis->rsi >= 30 && $analysis->rsi <= 70) {
            $score += 2; // Healthy RSI range
        } elseif ($analysis->rsi > 70) {
            $score += 1; // Slightly overbought
        }

        // Moving average scoring (3 points)
        if ($analysis->price_above_sma20) $score += 1;
        if ($analysis->price_above_sma50) $score += 1;
        if ($analysis->price_above_sma200) $score += 1;

        // MACD scoring (2 points)
        if ($analysis->macd > $analysis->macd_signal) $score += 1;
        if ($analysis->macd_histogram > 0) $score += 1;

        // Golden/Death cross scoring (2 points)
        if ($analysis->golden_cross) $score += 2;
        if ($analysis->death_cross) $score -= 2;

        // Bollinger band position (1 point)
        // This would need current price data to calculate properly
        
        return [
            'score' => max(0, min($score, $maxScore)),
            'max_score' => $maxScore,
            'percentage' => (max(0, min($score, $maxScore)) / $maxScore) * 100,
            'analysis_date' => $analysis->date
        ];
    }

    /**
     * Insert or update technical analysis data
     */
    public function upsertAnalysis($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (stocksymbol, date, sma_20, sma_50, sma_200, ema_12, ema_26, macd, macd_signal, macd_histogram, 
                 rsi, bollinger_upper, bollinger_middle, bollinger_lower, volume_sma, price_above_sma20, 
                 price_above_sma50, price_above_sma200, golden_cross, death_cross, analysis_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                sma_20 = VALUES(sma_20), sma_50 = VALUES(sma_50), sma_200 = VALUES(sma_200),
                ema_12 = VALUES(ema_12), ema_26 = VALUES(ema_26), macd = VALUES(macd),
                macd_signal = VALUES(macd_signal), macd_histogram = VALUES(macd_histogram),
                rsi = VALUES(rsi), bollinger_upper = VALUES(bollinger_upper),
                bollinger_middle = VALUES(bollinger_middle), bollinger_lower = VALUES(bollinger_lower),
                volume_sma = VALUES(volume_sma), price_above_sma20 = VALUES(price_above_sma20),
                price_above_sma50 = VALUES(price_above_sma50), price_above_sma200 = VALUES(price_above_sma200),
                golden_cross = VALUES(golden_cross), death_cross = VALUES(death_cross),
                analysis_date = VALUES(analysis_date)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['stocksymbol'],
            $data['date'],
            $data['sma_20'] ?? null,
            $data['sma_50'] ?? null,
            $data['sma_200'] ?? null,
            $data['ema_12'] ?? null,
            $data['ema_26'] ?? null,
            $data['macd'] ?? null,
            $data['macd_signal'] ?? null,
            $data['macd_histogram'] ?? null,
            $data['rsi'] ?? null,
            $data['bollinger_upper'] ?? null,
            $data['bollinger_middle'] ?? null,
            $data['bollinger_lower'] ?? null,
            $data['volume_sma'] ?? null,
            $data['price_above_sma20'] ?? 0,
            $data['price_above_sma50'] ?? 0,
            $data['price_above_sma200'] ?? 0,
            $data['golden_cross'] ?? 0,
            $data['death_cross'] ?? 0,
            $data['analysis_date'] ?? date('Y-m-d H:i:s')
        ]);
    }
}
