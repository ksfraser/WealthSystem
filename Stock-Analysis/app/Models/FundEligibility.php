<?php

namespace App\Models;

/**
 * Fund Eligibility Model
 * 
 * Defines eligibility rules for fund access based on net worth thresholds.
 * Used for admin configuration of fund access requirements.
 */
class FundEligibility
{
    private ?int $id;
    private string $fundSymbol;
    private string $merTier;
    private float $minimumNetWorth;
    private float $minimumInvestment;
    private bool $allowsFamilyAggregation;
    private bool $requiresAdvisorApproval;
    private ?string $eligibilityNotes;
    private string $effectiveDate;
    private ?string $expiryDate;
    private array $metadata;
    
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->fundSymbol = $data['fund_symbol'] ?? '';
        $this->merTier = $data['mer_tier'] ?? 'RETAIL';
        $this->minimumNetWorth = $data['minimum_net_worth'] ?? 0.0;
        $this->minimumInvestment = $data['minimum_investment'] ?? 0.0;
        $this->allowsFamilyAggregation = $data['allows_family_aggregation'] ?? true;
        $this->requiresAdvisorApproval = $data['requires_advisor_approval'] ?? false;
        $this->eligibilityNotes = $data['eligibility_notes'] ?? null;
        $this->effectiveDate = $data['effective_date'] ?? date('Y-m-d');
        $this->expiryDate = $data['expiry_date'] ?? null;
        $this->metadata = $data['metadata'] ?? [];
    }
    
    // Getters
    public function getId(): ?int { return $this->id; }
    public function getFundSymbol(): string { return $this->fundSymbol; }
    public function getMerTier(): string { return $this->merTier; }
    public function getMinimumNetWorth(): float { return $this->minimumNetWorth; }
    public function getMinimumInvestment(): float { return $this->minimumInvestment; }
    public function allowsFamilyAggregation(): bool { return $this->allowsFamilyAggregation; }
    public function requiresAdvisorApproval(): bool { return $this->requiresAdvisorApproval; }
    public function getEligibilityNotes(): ?string { return $this->eligibilityNotes; }
    public function getEffectiveDate(): string { return $this->effectiveDate; }
    public function getExpiryDate(): ?string { return $this->expiryDate; }
    public function getMetadata(): array { return $this->metadata; }
    
    // Setters
    public function setId(?int $id): void { $this->id = $id; }
    public function setFundSymbol(string $symbol): void { $this->fundSymbol = $symbol; }
    public function setMerTier(string $tier): void { $this->merTier = $tier; }
    public function setMinimumNetWorth(float $min): void { $this->minimumNetWorth = $min; }
    public function setMinimumInvestment(float $min): void { $this->minimumInvestment = $min; }
    public function setAllowsFamilyAggregation(bool $allows): void { $this->allowsFamilyAggregation = $allows; }
    public function setRequiresAdvisorApproval(bool $requires): void { $this->requiresAdvisorApproval = $requires; }
    public function setEligibilityNotes(?string $notes): void { $this->eligibilityNotes = $notes; }
    public function setEffectiveDate(string $date): void { $this->effectiveDate = $date; }
    public function setExpiryDate(?string $date): void { $this->expiryDate = $date; }
    public function setMetadata(array $metadata): void { $this->metadata = $metadata; }
    
    /**
     * Check if eligibility rule is currently active
     */
    public function isActive(): bool
    {
        $now = new \DateTime();
        $effective = new \DateTime($this->effectiveDate);
        
        if ($now < $effective) {
            return false;
        }
        
        if ($this->expiryDate) {
            $expiry = new \DateTime($this->expiryDate);
            if ($now > $expiry) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fund_symbol' => $this->fundSymbol,
            'mer_tier' => $this->merTier,
            'minimum_net_worth' => $this->minimumNetWorth,
            'minimum_investment' => $this->minimumInvestment,
            'allows_family_aggregation' => $this->allowsFamilyAggregation,
            'requires_advisor_approval' => $this->requiresAdvisorApproval,
            'eligibility_notes' => $this->eligibilityNotes,
            'effective_date' => $this->effectiveDate,
            'expiry_date' => $this->expiryDate,
            'metadata' => $this->metadata
        ];
    }
    
    /**
     * Create from database row
     */
    public static function fromDatabaseRow(array $row): self
    {
        $data = [
            'id' => $row['id'] ?? null,
            'fund_symbol' => $row['fund_symbol'] ?? '',
            'mer_tier' => $row['mer_tier'] ?? 'RETAIL',
            'minimum_net_worth' => (float)($row['minimum_net_worth'] ?? 0.0),
            'minimum_investment' => (float)($row['minimum_investment'] ?? 0.0),
            'allows_family_aggregation' => (bool)($row['allows_family_aggregation'] ?? true),
            'requires_advisor_approval' => (bool)($row['requires_advisor_approval'] ?? false),
            'eligibility_notes' => $row['eligibility_notes'] ?? null,
            'effective_date' => $row['effective_date'] ?? date('Y-m-d'),
            'expiry_date' => $row['expiry_date'] ?? null,
            'metadata' => isset($row['metadata']) ? json_decode($row['metadata'], true) : []
        ];
        
        return new self($data);
    }
}
