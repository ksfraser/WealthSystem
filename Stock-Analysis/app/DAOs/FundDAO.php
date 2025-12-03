<?php

namespace App\DAOs;

use App\Models\Fund;
use PDO;

/**
 * Fund Data Access Object
 * 
 * Handles database operations for funds (ETF, mutual funds, seg funds).
 */
class FundDAO
{
    private PDO $db;
    
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDatabaseConnection();
    }
    
    /**
     * Save or update fund
     */
    public function save(Fund $fund): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO funds (
                    symbol, name, fund_code, type, fund_family, base_fund_id,
                    mer, mer_tier, minimum_investment, minimum_net_worth,
                    allows_family_aggregation, is_institutional, aum,
                    expense_ratio, turnover_rate, tracking_error,
                    inception_date, currency, metadata
                ) VALUES (
                    :symbol, :name, :fund_code, :type, :fund_family, :base_fund_id,
                    :mer, :mer_tier, :minimum_investment, :minimum_net_worth,
                    :allows_family_aggregation, :is_institutional, :aum,
                    :expense_ratio, :turnover_rate, :tracking_error,
                    :inception_date, :currency, :metadata
                )
            ");
            
            $stmt->execute([
                ':symbol' => $fund->getSymbol(),
                ':name' => $fund->getName(),
                ':fund_code' => $fund->getFundCode(),
                ':type' => $fund->getType(),
                ':fund_family' => $fund->getFundFamily(),
                ':base_fund_id' => $fund->getBaseFundId(),
                ':mer' => $fund->getMer(),
                ':mer_tier' => $fund->getMerTier(),
                ':minimum_investment' => $fund->getMinimumInvestment(),
                ':minimum_net_worth' => $fund->getMinimumNetWorth(),
                ':allows_family_aggregation' => $fund->allowsFamilyAggregation() ? 1 : 0,
                ':is_institutional' => $fund->isInstitutional() ? 1 : 0,
                ':aum' => $fund->getAum(),
                ':expense_ratio' => $fund->getExpenseRatio(),
                ':turnover_rate' => $fund->getTurnoverRate(),
                ':tracking_error' => $fund->getTrackingError(),
                ':inception_date' => $fund->getInceptionDate(),
                ':currency' => $fund->getCurrency(),
                ':metadata' => json_encode($fund->getMetadata())
            ]);
            
            $fund->setId((int)$this->db->lastInsertId());
            return true;
            
        } catch (\PDOException $e) {
            error_log("Failed to save fund: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get fund by symbol
     */
    public function getBySymbol(string $symbol): ?Fund
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM funds WHERE symbol = :symbol LIMIT 1");
            $stmt->execute([':symbol' => $symbol]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? Fund::fromDatabaseRow($row) : null;
            
        } catch (\PDOException $e) {
            error_log("Failed to get fund: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get fund by fund code
     */
    public function getByFundCode(string $fundCode): ?Fund
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM funds WHERE fund_code = :code LIMIT 1");
            $stmt->execute([':code' => $fundCode]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? Fund::fromDatabaseRow($row) : null;
            
        } catch (\PDOException $e) {
            error_log("Failed to get fund by code: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all variants of same underlying fund (different MER tiers)
     */
    public function getByBaseFund(string $baseFundId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM funds 
                WHERE base_fund_id = :base_fund_id 
                ORDER BY mer ASC
            ");
            $stmt->execute([':base_fund_id' => $baseFundId]);
            
            $funds = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $funds[] = Fund::fromDatabaseRow($row);
            }
            
            return $funds;
            
        } catch (\PDOException $e) {
            error_log("Failed to get fund variants: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get retail version of fund (highest MER)
     */
    public function getRetailVersion(string $baseFundId): ?Fund
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM funds 
                WHERE base_fund_id = :base_fund_id 
                AND mer_tier = 'RETAIL'
                LIMIT 1
            ");
            $stmt->execute([':base_fund_id' => $baseFundId]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? Fund::fromDatabaseRow($row) : null;
            
        } catch (\PDOException $e) {
            error_log("Failed to get retail fund: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get funds by type
     */
    public function getByType(string $type): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM funds WHERE type = :type");
            $stmt->execute([':type' => $type]);
            
            $funds = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $funds[] = Fund::fromDatabaseRow($row);
            }
            
            return $funds;
            
        } catch (\PDOException $e) {
            error_log("Failed to get funds by type: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get funds by family (fund company)
     */
    public function getByFamily(string $family): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM funds WHERE fund_family = :family");
            $stmt->execute([':family' => $family]);
            
            $funds = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $funds[] = Fund::fromDatabaseRow($row);
            }
            
            return $funds;
            
        } catch (\PDOException $e) {
            error_log("Failed to get funds by family: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search funds by name or symbol
     */
    public function search(string $query): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM funds 
                WHERE name LIKE :query 
                OR symbol LIKE :query 
                OR fund_code LIKE :query
                LIMIT 50
            ");
            $stmt->execute([':query' => "%$query%"]);
            
            $funds = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $funds[] = Fund::fromDatabaseRow($row);
            }
            
            return $funds;
            
        } catch (\PDOException $e) {
            error_log("Failed to search funds: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update fund
     */
    public function update(Fund $fund): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE funds SET
                    name = :name,
                    fund_code = :fund_code,
                    type = :type,
                    fund_family = :fund_family,
                    base_fund_id = :base_fund_id,
                    mer = :mer,
                    mer_tier = :mer_tier,
                    minimum_investment = :minimum_investment,
                    minimum_net_worth = :minimum_net_worth,
                    allows_family_aggregation = :allows_family_aggregation,
                    is_institutional = :is_institutional,
                    aum = :aum,
                    expense_ratio = :expense_ratio,
                    turnover_rate = :turnover_rate,
                    tracking_error = :tracking_error,
                    inception_date = :inception_date,
                    currency = :currency,
                    metadata = :metadata
                WHERE symbol = :symbol
            ");
            
            return $stmt->execute([
                ':symbol' => $fund->getSymbol(),
                ':name' => $fund->getName(),
                ':fund_code' => $fund->getFundCode(),
                ':type' => $fund->getType(),
                ':fund_family' => $fund->getFundFamily(),
                ':base_fund_id' => $fund->getBaseFundId(),
                ':mer' => $fund->getMer(),
                ':mer_tier' => $fund->getMerTier(),
                ':minimum_investment' => $fund->getMinimumInvestment(),
                ':minimum_net_worth' => $fund->getMinimumNetWorth(),
                ':allows_family_aggregation' => $fund->allowsFamilyAggregation() ? 1 : 0,
                ':is_institutional' => $fund->isInstitutional() ? 1 : 0,
                ':aum' => $fund->getAum(),
                ':expense_ratio' => $fund->getExpenseRatio(),
                ':turnover_rate' => $fund->getTurnoverRate(),
                ':tracking_error' => $fund->getTrackingError(),
                ':inception_date' => $fund->getInceptionDate(),
                ':currency' => $fund->getCurrency(),
                ':metadata' => json_encode($fund->getMetadata())
            ]);
            
        } catch (\PDOException $e) {
            error_log("Failed to update fund: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete fund
     */
    public function delete(string $symbol): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM funds WHERE symbol = :symbol");
            return $stmt->execute([':symbol' => $symbol]);
            
        } catch (\PDOException $e) {
            error_log("Failed to delete fund: " . $e->getMessage());
            return false;
        }
    }
}
