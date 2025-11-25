<?php

namespace Ksfraser\StockInfo;

/**
 * StockInfo Model - represents the main stock information table
 * 
 * This model handles stock symbol information including:
 * - Basic stock details (symbol, exchange, company name)
 * - Price information (current, high, low, year high/low)
 * - Financial metrics (EPS, dividend, market cap, PE ratio)
 * - Trading data (volume, daily change)
 */
class StockInfo extends BaseModel
{
    protected $table = 'stockinfo';
    protected $primaryKey = 'idstockinfo';
    
    protected $fillable = [
        'stocksymbol',
        'stockexchange',
        'corporatename',
        'yearhigh',
        'yearlow',
        'high',
        'low',
        'averagevolume',
        'dailyvolume',
        'asofdate',
        'EPS',
        'annualdividendpershare',
        'currentprice',
        'dailychange',
        'createddate',
        'createduser',
        'reviseddate',
        'reviseduser',
        'marketcap',
        'peratio',
        'active'
    ];

    /**
     * Find stock by symbol
     */
    public function findBySymbol($symbol)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE stocksymbol = ? AND active = 1");
        $stmt->execute([$symbol]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    /**
     * Get all active stocks
     */
    public function getActiveStocks()
    {
        return $this->where(['active' => 1]);
    }

    /**
     * Get stocks by exchange
     */
    public function getStocksByExchange($exchangeId)
    {
        return $this->where(['stockexchange' => $exchangeId, 'active' => 1]);
    }

    /**
     * Search stocks by company name
     */
    public function searchByCompanyName($searchTerm, $limit = 50)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE corporatename LIKE ? AND active = 1 
                ORDER BY corporatename 
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['%' . $searchTerm . '%', $limit]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get stocks with dividend yield above threshold
     */
    public function getHighDividendStocks($minYield = 3.0)
    {
        $sql = "SELECT *, 
                CASE 
                    WHEN currentprice > 0 THEN (annualdividendpershare / currentprice) * 100 
                    ELSE 0 
                END as dividend_yield
                FROM {$this->table} 
                WHERE active = 1 
                AND currentprice > 0 
                AND annualdividendpershare > 0
                HAVING dividend_yield >= ?
                ORDER BY dividend_yield DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$minYield]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get stocks by market cap range
     */
    public function getStocksByMarketCap($minCap = null, $maxCap = null)
    {
        $conditions = ['active' => 1];
        $additionalWhere = [];
        $params = [1]; // for active = 1

        if ($minCap !== null) {
            $additionalWhere[] = "marketcap >= ?";
            $params[] = $minCap;
        }

        if ($maxCap !== null) {
            $additionalWhere[] = "marketcap <= ?";
            $params[] = $maxCap;
        }

        $sql = "SELECT * FROM {$this->table} WHERE active = ?";
        if (!empty($additionalWhere)) {
            $sql .= " AND " . implode(" AND ", $additionalWhere);
        }
        $sql .= " ORDER BY marketcap DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get top performing stocks by price change
     */
    public function getTopPerformers($limit = 20)
    {
        $sql = "SELECT *, 
                CASE 
                    WHEN yearlow > 0 THEN ((currentprice - yearlow) / yearlow) * 100 
                    ELSE 0 
                END as year_performance
                FROM {$this->table} 
                WHERE active = 1 
                AND currentprice > 0 
                AND yearlow > 0
                ORDER BY year_performance DESC 
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Get stocks with low PE ratios
     */
    public function getLowPEStocks($maxPE = 15.0, $limit = 50)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE active = 1 
                AND peratio > 0 
                AND peratio <= ?
                ORDER BY peratio ASC 
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$maxPE, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Update current price and related fields
     */
    public function updatePrice($stockSymbol, $price, $volume = null, $dailyChange = null)
    {
        $data = [
            'currentprice' => $price,
            'asofdate' => date('Y-m-d H:i:s'),
            'reviseddate' => date('Y-m-d H:i:s')
        ];

        if ($volume !== null) {
            $data['dailyvolume'] = $volume;
        }

        if ($dailyChange !== null) {
            $data['dailychange'] = $dailyChange;
        }

        $setClause = [];
        foreach (array_keys($data) as $field) {
            $setClause[] = "{$field} = :{$field}";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE stocksymbol = :symbol";
        $data['symbol'] = $stockSymbol;

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Get all stocks with pagination
     */
    public function getAll($limit = 50, $offset = 0)
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY {$this->primaryKey} DESC LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Count total stocks
     */
    public function count($conditions = [])
    {
        if (empty($conditions)) {
            $sql = "SELECT COUNT(*) FROM {$this->table}";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchColumn();
        }
        
        return parent::count($conditions);
    }

    /**
     * Create new stock
     */
    public function create($data)
    {
        // Map UI fields to database fields
        $mapped = [
            'stocksymbol' => $data['symbol'] ?? '',
            'corporatename' => $data['name'] ?? '',
            'stockexchange' => $data['exchange'] ?? '',
            'active' => 1,
            'createddate' => date('Y-m-d H:i:s'),
            'createduser' => 'system'
        ];

        // Add optional fields if provided
        if (isset($data['sector'])) $mapped['sector'] = $data['sector'];
        if (isset($data['industry'])) $mapped['industry'] = $data['industry'];
        if (isset($data['currency'])) $mapped['currency'] = $data['currency'];
        if (isset($data['country'])) $mapped['country'] = $data['country'];

        return parent::create($mapped);
    }

    /**
     * Update stock
     */
    public function update($id, $data)
    {
        // Map UI fields to database fields
        $mapped = [
            'stocksymbol' => $data['symbol'] ?? '',
            'corporatename' => $data['name'] ?? '',
            'stockexchange' => $data['exchange'] ?? '',
            'reviseddate' => date('Y-m-d H:i:s'),
            'reviseduser' => 'system'
        ];

        // Add optional fields if provided
        if (isset($data['sector'])) $mapped['sector'] = $data['sector'];
        if (isset($data['industry'])) $mapped['industry'] = $data['industry'];
        if (isset($data['currency'])) $mapped['currency'] = $data['currency'];
        if (isset($data['country'])) $mapped['country'] = $data['country'];

        return parent::update($id, $mapped);
    }

    /**
     * Soft delete stock (set active = 0)
     */
    public function delete($id)
    {
        return $this->update($id, ['active' => 0]);
    }

    /**
     * Get stock statistics
     */
    public function getStatistics()
    {
        $sql = "SELECT 
                COUNT(*) as total_stocks,
                COUNT(CASE WHEN active = 1 THEN 1 END) as active_stocks,
                AVG(currentprice) as avg_price,
                AVG(marketcap) as avg_market_cap,
                AVG(peratio) as avg_pe_ratio,
                AVG(CASE WHEN currentprice > 0 THEN (annualdividendpershare / currentprice) * 100 ELSE 0 END) as avg_dividend_yield
                FROM {$this->table}";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }
}
