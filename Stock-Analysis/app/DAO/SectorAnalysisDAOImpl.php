<?php

namespace App\DAO;

use PDO;
use PDOException;

/**
 * Sector Analysis DAO Implementation
 * 
 * Implements SectorAnalysisDAO interface for database access.
 * Retrieves portfolio sector data and S&P 500 benchmark weights.
 * 
 * Design Principles:
 * - DIP: Implements interface abstraction
 * - SRP: Only handles sector data access
 * - Error handling with exceptions
 * 
 * Database Tables:
 * - portfolio_positions: User holdings
 * - stock_fundamentals: Stock sector information
 * - sector_performance: S&P 500 sector weights
 * 
 * @package App\DAO
 * @version 1.0.0
 */
class SectorAnalysisDAOImpl implements SectorAnalysisDAO
{
    private PDO $pdo;
    
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
     * Get portfolio sector data for a user
     * 
     * Returns holdings grouped by sector with total values.
     * 
     * @param int $userId User ID
     * @return array Array of holdings with symbol, sector, value, shares
     * @throws \RuntimeException If database query fails
     */
    public function getPortfolioSectorData(int $userId): array
    {
        try {
            $sql = "
                SELECT 
                    pp.symbol,
                    COALESCE(sf.sector, 'Unknown') as sector,
                    pp.quantity as shares,
                    pp.position_value as value,
                    pp.current_price as price
                FROM portfolio_positions pp
                LEFT JOIN stock_fundamentals sf ON pp.symbol = sf.symbol
                WHERE pp.portfolio_id IN (
                    SELECT id FROM portfolios WHERE user_id = :user_id
                )
                AND pp.quantity > 0
                ORDER BY pp.position_value DESC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Failed to retrieve portfolio sector data: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * Get S&P 500 sector weights
     * 
     * Returns current S&P 500 sector allocation percentages.
     * Data sourced from sector_performance table.
     * 
     * @return array<string, float> Sector name => weight (%) mapping
     * @throws \RuntimeException If database query fails
     */
    public function getSP500SectorWeights(): array
    {
        try {
            // Try to get from sector_performance table first
            $sql = "
                SELECT 
                    sector_name as sector,
                    weight as percentage
                FROM sector_performance
                WHERE sector_code = 'SPX'
                AND timestamp = (
                    SELECT MAX(timestamp) 
                    FROM sector_performance 
                    WHERE sector_code = 'SPX'
                )
                ORDER BY weight DESC
            ";
            
            $stmt = $this->pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no data, return standard S&P 500 sector weights
            if (empty($results)) {
                return $this->getDefaultSP500Weights();
            }
            
            $weights = [];
            foreach ($results as $row) {
                $weights[$row['sector']] = (float) $row['percentage'];
            }
            
            return $weights;
        } catch (PDOException $e) {
            // Return defaults if query fails
            return $this->getDefaultSP500Weights();
        }
    }
    
    /**
     * Get sectors for specific symbols
     * 
     * @param string[] $symbols Array of stock symbols
     * @return array<string, string> Symbol => sector mapping
     * @throws \RuntimeException If database query fails
     */
    public function getSectorsBySymbols(array $symbols): array
    {
        if (empty($symbols)) {
            return [];
        }
        
        try {
            $placeholders = str_repeat('?,', count($symbols) - 1) . '?';
            $sql = "
                SELECT 
                    symbol,
                    COALESCE(sector, 'Unknown') as sector
                FROM stock_fundamentals
                WHERE symbol IN ($placeholders)
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($symbols);
            
            $results = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[$row['symbol']] = $row['sector'];
            }
            
            return $results;
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Failed to retrieve sectors by symbols: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
    
    /**
     * Get default S&P 500 sector weights
     * 
     * Returns standard GICS sector allocation for S&P 500.
     * Updated as of Q4 2025.
     * 
     * @return array<string, float> Sector => weight (%) mapping
     */
    private function getDefaultSP500Weights(): array
    {
        return [
            'Information Technology' => 28.5,
            'Financials' => 12.8,
            'Health Care' => 13.2,
            'Consumer Discretionary' => 10.5,
            'Industrials' => 8.7,
            'Communication Services' => 8.3,
            'Consumer Staples' => 6.9,
            'Energy' => 4.1,
            'Utilities' => 2.8,
            'Real Estate' => 2.5,
            'Materials' => 2.3
        ];
    }
}
