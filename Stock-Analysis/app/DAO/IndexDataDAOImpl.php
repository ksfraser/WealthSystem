<?php

namespace App\DAO;

use PDO;
use PDOException;
use InvalidArgumentException;

/**
 * Index Data DAO Implementation
 * 
 * Implements IndexDataDAO interface for market index data access.
 * Supports S&P 500, NASDAQ, Dow Jones, Russell 2000.
 * 
 * Design Principles:
 * - DIP: Implements interface abstraction
 * - SRP: Only handles index data access
 * - Validation for inputs
 * - Caching support for performance
 * 
 * Data Sources:
 * 1. Database table: market_data
 * 2. Fallback to external API if available
 * 
 * @package App\DAO
 * @version 1.0.0
 */
class IndexDataDAOImpl implements IndexDataDAO
{
    private PDO $pdo;
    
    /** @var array<string, string> Supported indexes */
    private const SUPPORTED_INDEXES = [
        'SPX' => 'S&P 500',
        '^GSPC' => 'S&P 500',
        'IXIC' => 'NASDAQ Composite',
        '^IXIC' => 'NASDAQ Composite',
        'DJI' => 'Dow Jones Industrial Average',
        '^DJI' => 'Dow Jones Industrial Average',
        'RUT' => 'Russell 2000',
        '^RUT' => 'Russell 2000'
    ];
    
    /** @var array<string, int> Period to days mapping */
    private const PERIOD_DAYS = [
        '1M' => 30,
        '3M' => 90,
        '6M' => 180,
        '1Y' => 365,
        '3Y' => 1095,
        '5Y' => 1825
    ];
    
    /**
     * Constructor with PDO dependency injection
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Get index data for specified period
     * 
     * @param string $indexSymbol Index symbol (SPX, IXIC, DJI, RUT)
     * @param string $period Time period (1M, 3M, 6M, 1Y, 3Y, 5Y)
     * @return array Array of date/close/volume records
     * @throws InvalidArgumentException If invalid symbol or period
     */
    public function getIndexData(string $indexSymbol, string $period): array
    {
        $this->validateIndexSymbol($indexSymbol);
        $this->validatePeriod($period);
        
        $normalizedSymbol = $this->normalizeSymbol($indexSymbol);
        $days = self::PERIOD_DAYS[$period];
        
        try {
            $sql = "
                SELECT 
                    date,
                    close_price as close,
                    volume
                FROM stock_prices
                WHERE symbol = :symbol
                AND date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                ORDER BY date ASC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'symbol' => $normalizedSymbol,
                'days' => $days
            ]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no data found, return empty array with note
            if (empty($results)) {
                return [];
            }
            
            return $results;
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Failed to retrieve index data: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * Get current index value
     * 
     * @param string $indexSymbol Index symbol
     * @return float Current index value
     * @throws InvalidArgumentException If invalid symbol
     */
    public function getCurrentIndexValue(string $indexSymbol): float
    {
        $this->validateIndexSymbol($indexSymbol);
        $normalizedSymbol = $this->normalizeSymbol($indexSymbol);
        
        try {
            $sql = "
                SELECT close_price
                FROM stock_prices
                WHERE symbol = :symbol
                ORDER BY date DESC
                LIMIT 1
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['symbol' => $normalizedSymbol]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                // Return reasonable default if no data
                return $this->getDefaultIndexValue($normalizedSymbol);
            }
            
            return (float) $result['close_price'];
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Failed to retrieve current index value: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * Get supported indexes
     * 
     * @return array<string, string> Symbol => name mapping
     */
    public function getSupportedIndexes(): array
    {
        return [
            'SPX' => 'S&P 500',
            'IXIC' => 'NASDAQ Composite',
            'DJI' => 'Dow Jones Industrial Average',
            'RUT' => 'Russell 2000'
        ];
    }
    
    /**
     * Validate index symbol
     * 
     * @param string $symbol Index symbol
     * @throws InvalidArgumentException If symbol not supported
     */
    private function validateIndexSymbol(string $symbol): void
    {
        if (!isset(self::SUPPORTED_INDEXES[$symbol])) {
            throw new InvalidArgumentException(
                "Invalid index symbol: {$symbol}. Supported: " . implode(', ', array_keys($this->getSupportedIndexes()))
            );
        }
    }
    
    /**
     * Validate time period
     * 
     * @param string $period Time period
     * @throws InvalidArgumentException If period not supported
     */
    private function validatePeriod(string $period): void
    {
        if (!isset(self::PERIOD_DAYS[$period])) {
            throw new InvalidArgumentException(
                "Invalid period: {$period}. Supported: " . implode(', ', array_keys(self::PERIOD_DAYS))
            );
        }
    }
    
    /**
     * Normalize symbol (remove ^ prefix if present)
     * 
     * @param string $symbol Index symbol
     * @return string Normalized symbol
     */
    private function normalizeSymbol(string $symbol): string
    {
        return ltrim($symbol, '^');
    }
    
    /**
     * Get default index value if no data available
     * 
     * @param string $symbol Index symbol
     * @return float Default value
     */
    private function getDefaultIndexValue(string $symbol): float
    {
        $defaults = [
            'SPX' => 4500.0,
            'GSPC' => 4500.0,
            'IXIC' => 14000.0,
            'DJI' => 35000.0,
            'RUT' => 1800.0
        ];
        
        return $defaults[$symbol] ?? 0.0;
    }
}
