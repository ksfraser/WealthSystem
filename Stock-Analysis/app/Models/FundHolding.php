<?php

namespace App\Models;

/**
 * Fund Holding Model
 * 
 * Represents individual holding within a fund (stock, bond, etc.)
 */
class FundHolding
{
    private ?int $id;
    private string $fundSymbol;
    private string $holdingSymbol;
    private string $holdingName;
    private float $weight; // Percentage weight in fund
    private int $shares;
    private float $marketValue;
    private string $assetClass; // Equity, Bond, Cash, Other
    private ?string $sector;
    private ?string $region;
    private string $asOfDate;
    private array $metadata;
    
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->fundSymbol = $data['fund_symbol'] ?? '';
        $this->holdingSymbol = $data['holding_symbol'] ?? '';
        $this->holdingName = $data['holding_name'] ?? '';
        $this->weight = $data['weight'] ?? 0.0;
        $this->shares = $data['shares'] ?? 0;
        $this->marketValue = $data['market_value'] ?? 0.0;
        $this->assetClass = $data['asset_class'] ?? 'Equity';
        $this->sector = $data['sector'] ?? null;
        $this->region = $data['region'] ?? null;
        $this->asOfDate = $data['as_of_date'] ?? date('Y-m-d');
        $this->metadata = $data['metadata'] ?? [];
    }
    
    // Getters
    public function getId(): ?int { return $this->id; }
    public function getFundSymbol(): string { return $this->fundSymbol; }
    public function getHoldingSymbol(): string { return $this->holdingSymbol; }
    public function getHoldingName(): string { return $this->holdingName; }
    public function getWeight(): float { return $this->weight; }
    public function getShares(): int { return $this->shares; }
    public function getMarketValue(): float { return $this->marketValue; }
    public function getAssetClass(): string { return $this->assetClass; }
    public function getSector(): ?string { return $this->sector; }
    public function getRegion(): ?string { return $this->region; }
    public function getAsOfDate(): string { return $this->asOfDate; }
    public function getMetadata(): array { return $this->metadata; }
    
    // Setters
    public function setId(?int $id): void { $this->id = $id; }
    public function setFundSymbol(string $symbol): void { $this->fundSymbol = $symbol; }
    public function setHoldingSymbol(string $symbol): void { $this->holdingSymbol = $symbol; }
    public function setHoldingName(string $name): void { $this->holdingName = $name; }
    public function setWeight(float $weight): void { $this->weight = $weight; }
    public function setShares(int $shares): void { $this->shares = $shares; }
    public function setMarketValue(float $value): void { $this->marketValue = $value; }
    public function setAssetClass(string $class): void { $this->assetClass = $class; }
    public function setSector(?string $sector): void { $this->sector = $sector; }
    public function setRegion(?string $region): void { $this->region = $region; }
    public function setAsOfDate(string $date): void { $this->asOfDate = $date; }
    public function setMetadata(array $metadata): void { $this->metadata = $metadata; }
    
    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fund_symbol' => $this->fundSymbol,
            'holding_symbol' => $this->holdingSymbol,
            'holding_name' => $this->holdingName,
            'weight' => $this->weight,
            'shares' => $this->shares,
            'market_value' => $this->marketValue,
            'asset_class' => $this->assetClass,
            'sector' => $this->sector,
            'region' => $this->region,
            'as_of_date' => $this->asOfDate,
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
            'holding_symbol' => $row['holding_symbol'] ?? '',
            'holding_name' => $row['holding_name'] ?? '',
            'weight' => (float)($row['weight'] ?? 0.0),
            'shares' => (int)($row['shares'] ?? 0),
            'market_value' => (float)($row['market_value'] ?? 0.0),
            'asset_class' => $row['asset_class'] ?? 'Equity',
            'sector' => $row['sector'] ?? null,
            'region' => $row['region'] ?? null,
            'as_of_date' => $row['as_of_date'] ?? date('Y-m-d'),
            'metadata' => isset($row['metadata']) ? json_decode($row['metadata'], true) : []
        ];
        
        return new self($data);
    }
}
