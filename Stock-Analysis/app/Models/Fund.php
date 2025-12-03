<?php

namespace App\Models;

/**
 * Fund Model
 * 
 * Represents ETF, mutual fund, or segregated fund with metadata.
 * Supports multiple fund codes for same underlying fund with different MER tiers.
 */
class Fund
{
    private ?int $id;
    private string $symbol;
    private string $name;
    private string $fundCode;
    private string $type; // ETF, MUTUAL_FUND, SEGREGATED_FUND, INDEX_FUND
    private string $fundFamily; // Fund company/provider
    private ?string $baseFundId; // Links variants of same fund
    private float $mer; // Management Expense Ratio
    private string $merTier; // RETAIL, PREFERRED, PREMIUM, INSTITUTIONAL
    private float $minimumInvestment;
    private float $minimumNetWorth;
    private bool $allowsFamilyAggregation;
    private bool $isInstitutional;
    private ?float $aum; // Assets Under Management
    private ?float $expenseRatio;
    private ?float $turnoverRate;
    private ?float $trackingError;
    private ?string $inceptionDate;
    private string $currency;
    private array $metadata;
    
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->symbol = $data['symbol'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->fundCode = $data['fund_code'] ?? '';
        $this->type = $data['type'] ?? 'ETF';
        $this->fundFamily = $data['fund_family'] ?? '';
        $this->baseFundId = $data['base_fund_id'] ?? null;
        $this->mer = $data['mer'] ?? 0.0;
        $this->merTier = $data['mer_tier'] ?? 'RETAIL';
        $this->minimumInvestment = $data['minimum_investment'] ?? 0.0;
        $this->minimumNetWorth = $data['minimum_net_worth'] ?? 0.0;
        $this->allowsFamilyAggregation = $data['allows_family_aggregation'] ?? true;
        $this->isInstitutional = $data['is_institutional'] ?? false;
        $this->aum = $data['aum'] ?? null;
        $this->expenseRatio = $data['expense_ratio'] ?? null;
        $this->turnoverRate = $data['turnover_rate'] ?? null;
        $this->trackingError = $data['tracking_error'] ?? null;
        $this->inceptionDate = $data['inception_date'] ?? null;
        $this->currency = $data['currency'] ?? 'USD';
        $this->metadata = $data['metadata'] ?? [];
    }
    
    // Getters
    public function getId(): ?int { return $this->id; }
    public function getSymbol(): string { return $this->symbol; }
    public function getName(): string { return $this->name; }
    public function getFundCode(): string { return $this->fundCode; }
    public function getType(): string { return $this->type; }
    public function getFundFamily(): string { return $this->fundFamily; }
    public function getBaseFundId(): ?string { return $this->baseFundId; }
    public function getMer(): float { return $this->mer; }
    public function getMerTier(): string { return $this->merTier; }
    public function getMinimumInvestment(): float { return $this->minimumInvestment; }
    public function getMinimumNetWorth(): float { return $this->minimumNetWorth; }
    public function allowsFamilyAggregation(): bool { return $this->allowsFamilyAggregation; }
    public function isInstitutional(): bool { return $this->isInstitutional; }
    public function getAum(): ?float { return $this->aum; }
    public function getExpenseRatio(): ?float { return $this->expenseRatio; }
    public function getTurnoverRate(): ?float { return $this->turnoverRate; }
    public function getTrackingError(): ?float { return $this->trackingError; }
    public function getInceptionDate(): ?string { return $this->inceptionDate; }
    public function getCurrency(): string { return $this->currency; }
    public function getMetadata(): array { return $this->metadata; }
    
    // Setters
    public function setId(?int $id): void { $this->id = $id; }
    public function setSymbol(string $symbol): void { $this->symbol = $symbol; }
    public function setName(string $name): void { $this->name = $name; }
    public function setFundCode(string $fundCode): void { $this->fundCode = $fundCode; }
    public function setType(string $type): void { $this->type = $type; }
    public function setFundFamily(string $fundFamily): void { $this->fundFamily = $fundFamily; }
    public function setBaseFundId(?string $baseFundId): void { $this->baseFundId = $baseFundId; }
    public function setMer(float $mer): void { $this->mer = $mer; }
    public function setMerTier(string $merTier): void { $this->merTier = $merTier; }
    public function setMinimumInvestment(float $min): void { $this->minimumInvestment = $min; }
    public function setMinimumNetWorth(float $min): void { $this->minimumNetWorth = $min; }
    public function setAllowsFamilyAggregation(bool $allows): void { $this->allowsFamilyAggregation = $allows; }
    public function setIsInstitutional(bool $is): void { $this->isInstitutional = $is; }
    public function setAum(?float $aum): void { $this->aum = $aum; }
    public function setExpenseRatio(?float $ratio): void { $this->expenseRatio = $ratio; }
    public function setTurnoverRate(?float $rate): void { $this->turnoverRate = $rate; }
    public function setTrackingError(?float $error): void { $this->trackingError = $error; }
    public function setInceptionDate(?string $date): void { $this->inceptionDate = $date; }
    public function setCurrency(string $currency): void { $this->currency = $currency; }
    public function setMetadata(array $metadata): void { $this->metadata = $metadata; }
    
    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'name' => $this->name,
            'fund_code' => $this->fundCode,
            'type' => $this->type,
            'fund_family' => $this->fundFamily,
            'base_fund_id' => $this->baseFundId,
            'mer' => $this->mer,
            'mer_tier' => $this->merTier,
            'minimum_investment' => $this->minimumInvestment,
            'minimum_net_worth' => $this->minimumNetWorth,
            'allows_family_aggregation' => $this->allowsFamilyAggregation,
            'is_institutional' => $this->isInstitutional,
            'aum' => $this->aum,
            'expense_ratio' => $this->expenseRatio,
            'turnover_rate' => $this->turnoverRate,
            'tracking_error' => $this->trackingError,
            'inception_date' => $this->inceptionDate,
            'currency' => $this->currency,
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
            'symbol' => $row['symbol'] ?? '',
            'name' => $row['name'] ?? '',
            'fund_code' => $row['fund_code'] ?? '',
            'type' => $row['type'] ?? 'ETF',
            'fund_family' => $row['fund_family'] ?? '',
            'base_fund_id' => $row['base_fund_id'] ?? null,
            'mer' => (float)($row['mer'] ?? 0.0),
            'mer_tier' => $row['mer_tier'] ?? 'RETAIL',
            'minimum_investment' => (float)($row['minimum_investment'] ?? 0.0),
            'minimum_net_worth' => (float)($row['minimum_net_worth'] ?? 0.0),
            'allows_family_aggregation' => (bool)($row['allows_family_aggregation'] ?? true),
            'is_institutional' => (bool)($row['is_institutional'] ?? false),
            'aum' => isset($row['aum']) ? (float)$row['aum'] : null,
            'expense_ratio' => isset($row['expense_ratio']) ? (float)$row['expense_ratio'] : null,
            'turnover_rate' => isset($row['turnover_rate']) ? (float)$row['turnover_rate'] : null,
            'tracking_error' => isset($row['tracking_error']) ? (float)$row['tracking_error'] : null,
            'inception_date' => $row['inception_date'] ?? null,
            'currency' => $row['currency'] ?? 'USD',
            'metadata' => isset($row['metadata']) ? json_decode($row['metadata'], true) : []
        ];
        
        return new self($data);
    }
}
