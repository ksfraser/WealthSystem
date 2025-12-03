<?php

namespace App\DAOs;

use App\Models\FundHolding;
use PDO;

/**
 * Fund Holding Data Access Object
 * 
 * Handles database operations for fund holdings.
 */
class FundHoldingDAO
{
    private PDO $db;
    
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDatabaseConnection();
    }
    
    /**
     * Save fund holding
     */
    public function save(FundHolding $holding): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO fund_holdings (
                    fund_symbol, holding_symbol, holding_name, weight,
                    shares, market_value, asset_class, sector, region,
                    as_of_date, metadata
                ) VALUES (
                    :fund_symbol, :holding_symbol, :holding_name, :weight,
                    :shares, :market_value, :asset_class, :sector, :region,
                    :as_of_date, :metadata
                )
            ");
            
            $stmt->execute([
                ':fund_symbol' => $holding->getFundSymbol(),
                ':holding_symbol' => $holding->getHoldingSymbol(),
                ':holding_name' => $holding->getHoldingName(),
                ':weight' => $holding->getWeight(),
                ':shares' => $holding->getShares(),
                ':market_value' => $holding->getMarketValue(),
                ':asset_class' => $holding->getAssetClass(),
                ':sector' => $holding->getSector(),
                ':region' => $holding->getRegion(),
                ':as_of_date' => $holding->getAsOfDate(),
                ':metadata' => json_encode($holding->getMetadata())
            ]);
            
            $holding->setId((int)$this->db->lastInsertId());
            return true;
            
        } catch (\PDOException $e) {
            error_log("Failed to save fund holding: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all holdings for a fund
     */
    public function getHoldingsByFund(string $fundSymbol, ?string $asOfDate = null): array
    {
        try {
            $sql = "
                SELECT * FROM fund_holdings 
                WHERE fund_symbol = :fund_symbol
            ";
            
            if ($asOfDate) {
                $sql .= " AND as_of_date = :as_of_date";
            } else {
                // Get most recent holdings
                $sql .= " AND as_of_date = (
                    SELECT MAX(as_of_date) FROM fund_holdings 
                    WHERE fund_symbol = :fund_symbol
                )";
            }
            
            $sql .= " ORDER BY weight DESC";
            
            $stmt = $this->db->prepare($sql);
            $params = [':fund_symbol' => $fundSymbol];
            
            if ($asOfDate) {
                $params[':as_of_date'] = $asOfDate;
            }
            
            $stmt->execute($params);
            
            $holdings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $holdings[] = $row; // Return as arrays for performance metrics
            }
            
            return $holdings;
            
        } catch (\PDOException $e) {
            error_log("Failed to get fund holdings: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get specific holding
     */
    public function getHolding(string $fundSymbol, string $holdingSymbol): ?FundHolding
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM fund_holdings 
                WHERE fund_symbol = :fund_symbol 
                AND holding_symbol = :holding_symbol
                AND as_of_date = (
                    SELECT MAX(as_of_date) FROM fund_holdings 
                    WHERE fund_symbol = :fund_symbol
                )
                LIMIT 1
            ");
            
            $stmt->execute([
                ':fund_symbol' => $fundSymbol,
                ':holding_symbol' => $holdingSymbol
            ]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? FundHolding::fromDatabaseRow($row) : null;
            
        } catch (\PDOException $e) {
            error_log("Failed to get holding: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get top N holdings by weight
     */
    public function getTopHoldings(string $fundSymbol, int $limit = 10): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM fund_holdings 
                WHERE fund_symbol = :fund_symbol
                AND as_of_date = (
                    SELECT MAX(as_of_date) FROM fund_holdings 
                    WHERE fund_symbol = :fund_symbol
                )
                ORDER BY weight DESC
                LIMIT :limit
            ");
            
            $stmt->bindValue(':fund_symbol', $fundSymbol, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $holdings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $holdings[] = FundHolding::fromDatabaseRow($row);
            }
            
            return $holdings;
            
        } catch (\PDOException $e) {
            error_log("Failed to get top holdings: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Bulk insert holdings (for batch imports)
     */
    public function bulkInsert(array $holdings): bool
    {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                INSERT INTO fund_holdings (
                    fund_symbol, holding_symbol, holding_name, weight,
                    shares, market_value, asset_class, sector, region,
                    as_of_date, metadata
                ) VALUES (
                    :fund_symbol, :holding_symbol, :holding_name, :weight,
                    :shares, :market_value, :asset_class, :sector, :region,
                    :as_of_date, :metadata
                )
            ");
            
            foreach ($holdings as $holding) {
                $stmt->execute([
                    ':fund_symbol' => $holding->getFundSymbol(),
                    ':holding_symbol' => $holding->getHoldingSymbol(),
                    ':holding_name' => $holding->getHoldingName(),
                    ':weight' => $holding->getWeight(),
                    ':shares' => $holding->getShares(),
                    ':market_value' => $holding->getMarketValue(),
                    ':asset_class' => $holding->getAssetClass(),
                    ':sector' => $holding->getSector(),
                    ':region' => $holding->getRegion(),
                    ':as_of_date' => $holding->getAsOfDate(),
                    ':metadata' => json_encode($holding->getMetadata())
                ]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Failed bulk insert: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete old holdings
     */
    public function deleteOld(string $fundSymbol, string $beforeDate): bool
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM fund_holdings 
                WHERE fund_symbol = :fund_symbol 
                AND as_of_date < :before_date
            ");
            
            return $stmt->execute([
                ':fund_symbol' => $fundSymbol,
                ':before_date' => $beforeDate
            ]);
            
        } catch (\PDOException $e) {
            error_log("Failed to delete old holdings: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get holdings count
     */
    public function getHoldingsCount(string $fundSymbol): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM fund_holdings 
                WHERE fund_symbol = :fund_symbol
                AND as_of_date = (
                    SELECT MAX(as_of_date) FROM fund_holdings 
                    WHERE fund_symbol = :fund_symbol
                )
            ");
            
            $stmt->execute([':fund_symbol' => $fundSymbol]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)($row['count'] ?? 0);
            
        } catch (\PDOException $e) {
            error_log("Failed to get holdings count: " . $e->getMessage());
            return 0;
        }
    }
}
