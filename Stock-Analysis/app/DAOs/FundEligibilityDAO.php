<?php

namespace App\DAOs;

use App\Models\FundEligibility;
use PDO;

/**
 * Fund Eligibility Data Access Object
 * 
 * Handles database operations for fund eligibility rules.
 */
class FundEligibilityDAO
{
    private PDO $db;
    
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? getDatabaseConnection();
    }
    
    /**
     * Save eligibility rule
     */
    public function save(FundEligibility $eligibility): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO fund_eligibility (
                    fund_symbol, mer_tier, minimum_net_worth, minimum_investment,
                    allows_family_aggregation, requires_advisor_approval,
                    eligibility_notes, effective_date, expiry_date, metadata
                ) VALUES (
                    :fund_symbol, :mer_tier, :minimum_net_worth, :minimum_investment,
                    :allows_family_aggregation, :requires_advisor_approval,
                    :eligibility_notes, :effective_date, :expiry_date, :metadata
                )
            ");
            
            $stmt->execute([
                ':fund_symbol' => $eligibility->getFundSymbol(),
                ':mer_tier' => $eligibility->getMerTier(),
                ':minimum_net_worth' => $eligibility->getMinimumNetWorth(),
                ':minimum_investment' => $eligibility->getMinimumInvestment(),
                ':allows_family_aggregation' => $eligibility->allowsFamilyAggregation() ? 1 : 0,
                ':requires_advisor_approval' => $eligibility->requiresAdvisorApproval() ? 1 : 0,
                ':eligibility_notes' => $eligibility->getEligibilityNotes(),
                ':effective_date' => $eligibility->getEffectiveDate(),
                ':expiry_date' => $eligibility->getExpiryDate(),
                ':metadata' => json_encode($eligibility->getMetadata())
            ]);
            
            $eligibility->setId((int)$this->db->lastInsertId());
            return true;
            
        } catch (\PDOException $e) {
            error_log("Failed to save eligibility: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get active eligibility rules for fund
     */
    public function getByFund(string $fundSymbol): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM fund_eligibility 
                WHERE fund_symbol = :fund_symbol
                AND effective_date <= DATE('now')
                AND (expiry_date IS NULL OR expiry_date >= DATE('now'))
                ORDER BY mer_tier
            ");
            
            $stmt->execute([':fund_symbol' => $fundSymbol]);
            
            $rules = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $rules[] = FundEligibility::fromDatabaseRow($row);
            }
            
            return $rules;
            
        } catch (\PDOException $e) {
            error_log("Failed to get eligibility rules: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get eligibility rule by MER tier
     */
    public function getByFundAndTier(string $fundSymbol, string $merTier): ?FundEligibility
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM fund_eligibility 
                WHERE fund_symbol = :fund_symbol 
                AND mer_tier = :mer_tier
                AND effective_date <= DATE('now')
                AND (expiry_date IS NULL OR expiry_date >= DATE('now'))
                LIMIT 1
            ");
            
            $stmt->execute([
                ':fund_symbol' => $fundSymbol,
                ':mer_tier' => $merTier
            ]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? FundEligibility::fromDatabaseRow($row) : null;
            
        } catch (\PDOException $e) {
            error_log("Failed to get eligibility by tier: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all eligibility rules (for admin management)
     */
    public function getAll(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT * FROM fund_eligibility 
                ORDER BY fund_symbol, mer_tier
            ");
            
            $rules = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $rules[] = FundEligibility::fromDatabaseRow($row);
            }
            
            return $rules;
            
        } catch (\PDOException $e) {
            error_log("Failed to get all eligibility rules: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update eligibility rule
     */
    public function update(FundEligibility $eligibility): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE fund_eligibility SET
                    minimum_net_worth = :minimum_net_worth,
                    minimum_investment = :minimum_investment,
                    allows_family_aggregation = :allows_family_aggregation,
                    requires_advisor_approval = :requires_advisor_approval,
                    eligibility_notes = :eligibility_notes,
                    effective_date = :effective_date,
                    expiry_date = :expiry_date,
                    metadata = :metadata
                WHERE id = :id
            ");
            
            return $stmt->execute([
                ':id' => $eligibility->getId(),
                ':minimum_net_worth' => $eligibility->getMinimumNetWorth(),
                ':minimum_investment' => $eligibility->getMinimumInvestment(),
                ':allows_family_aggregation' => $eligibility->allowsFamilyAggregation() ? 1 : 0,
                ':requires_advisor_approval' => $eligibility->requiresAdvisorApproval() ? 1 : 0,
                ':eligibility_notes' => $eligibility->getEligibilityNotes(),
                ':effective_date' => $eligibility->getEffectiveDate(),
                ':expiry_date' => $eligibility->getExpiryDate(),
                ':metadata' => json_encode($eligibility->getMetadata())
            ]);
            
        } catch (\PDOException $e) {
            error_log("Failed to update eligibility: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete eligibility rule
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM fund_eligibility WHERE id = :id");
            return $stmt->execute([':id' => $id]);
            
        } catch (\PDOException $e) {
            error_log("Failed to delete eligibility: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Expire eligibility rule
     */
    public function expire(int $id, string $expiryDate): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE fund_eligibility 
                SET expiry_date = :expiry_date 
                WHERE id = :id
            ");
            
            return $stmt->execute([
                ':id' => $id,
                ':expiry_date' => $expiryDate
            ]);
            
        } catch (\PDOException $e) {
            error_log("Failed to expire eligibility: " . $e->getMessage());
            return false;
        }
    }
}
