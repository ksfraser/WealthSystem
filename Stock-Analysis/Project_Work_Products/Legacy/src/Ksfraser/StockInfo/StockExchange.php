<?php

namespace Ksfraser\StockInfo;

/**
 * StockExchange Model - represents stock exchange information
 * 
 * This model handles exchange data including:
 * - Exchange names and identifiers
 * - Exchange-specific information
 * - Currency and timezone details
 */
class StockExchange extends BaseModel
{
    protected $table = 'stockexchange';
    protected $primaryKey = 'idstockexchange';
    
    protected $fillable = [
        'name',
        'description',
        'currency',
        'timezone',
        'country',
        'active'
    ];

    /**
     * Get all active exchanges
     */
    public function getActiveExchanges()
    {
        return $this->where(['active' => 1]);
    }

    /**
     * Find exchange by name
     */
    public function findByName($name)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE name = ?");
        $stmt->execute([$name]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    /**
     * Get exchanges by country
     */
    public function getExchangesByCountry($country)
    {
        return $this->where(['country' => $country, 'active' => 1]);
    }

    /**
     * Get stocks count by exchange
     */
    public function getStocksCountByExchange()
    {
        $sql = "SELECT 
                e.idstockexchange,
                e.name as exchange_name,
                e.country,
                COUNT(s.idstockinfo) as stock_count,
                COUNT(CASE WHEN s.active = 1 THEN 1 END) as active_stock_count
                FROM {$this->table} e
                LEFT JOIN stockinfo s ON e.idstockexchange = s.stockexchange
                GROUP BY e.idstockexchange, e.name, e.country
                ORDER BY stock_count DESC";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
}
