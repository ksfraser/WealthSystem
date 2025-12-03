<?php

namespace App\Models;

/**
 * Index Performance Model
 * 
 * Represents market index performance data.
 * 
 * @package App\Models
 */
class IndexPerformance
{
    private ?int $id;
    private string $indexSymbol;
    private string $indexName;
    private string $region;
    private string $assetClass;
    private float $value;
    private float $changePercent;
    private int $constituents;
    private float $marketCap;
    private string $currency;
    private string $timestamp;
    private ?array $metadata;
    
    /**
     * Constructor
     * 
     * @param array $data Index performance data
     */
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->indexSymbol = $data['index_symbol'] ?? '';
        $this->indexName = $data['index_name'] ?? '';
        $this->region = $data['region'] ?? 'US';
        $this->assetClass = $data['asset_class'] ?? 'equity';
        $this->value = $data['value'] ?? 0.0;
        $this->changePercent = $data['change_percent'] ?? 0.0;
        $this->constituents = $data['constituents'] ?? 0;
        $this->marketCap = $data['market_cap'] ?? 0.0;
        $this->currency = $data['currency'] ?? 'USD';
        $this->timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
        $this->metadata = $data['metadata'] ?? null;
    }
    
    // ========== GETTERS ==========
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getIndexSymbol(): string
    {
        return $this->indexSymbol;
    }
    
    public function getIndexName(): string
    {
        return $this->indexName;
    }
    
    public function getRegion(): string
    {
        return $this->region;
    }
    
    public function getAssetClass(): string
    {
        return $this->assetClass;
    }
    
    public function getValue(): float
    {
        return $this->value;
    }
    
    public function getChangePercent(): float
    {
        return $this->changePercent;
    }
    
    public function getConstituents(): int
    {
        return $this->constituents;
    }
    
    public function getMarketCap(): float
    {
        return $this->marketCap;
    }
    
    public function getCurrency(): string
    {
        return $this->currency;
    }
    
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }
    
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }
    
    // ========== SETTERS ==========
    
    public function setId(?int $id): void
    {
        $this->id = $id;
    }
    
    public function setIndexSymbol(string $indexSymbol): void
    {
        $this->indexSymbol = $indexSymbol;
    }
    
    public function setIndexName(string $indexName): void
    {
        $this->indexName = $indexName;
    }
    
    public function setRegion(string $region): void
    {
        $this->region = $region;
    }
    
    public function setAssetClass(string $assetClass): void
    {
        $this->assetClass = $assetClass;
    }
    
    public function setValue(float $value): void
    {
        $this->value = $value;
    }
    
    public function setChangePercent(float $changePercent): void
    {
        $this->changePercent = $changePercent;
    }
    
    public function setConstituents(int $constituents): void
    {
        $this->constituents = $constituents;
    }
    
    public function setMarketCap(float $marketCap): void
    {
        $this->marketCap = $marketCap;
    }
    
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }
    
    public function setTimestamp(string $timestamp): void
    {
        $this->timestamp = $timestamp;
    }
    
    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
    }
    
    // ========== CONVERSION METHODS ==========
    
    /**
     * Convert to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'index_symbol' => $this->indexSymbol,
            'index_name' => $this->indexName,
            'region' => $this->region,
            'asset_class' => $this->assetClass,
            'value' => $this->value,
            'change_percent' => $this->changePercent,
            'constituents' => $this->constituents,
            'market_cap' => $this->marketCap,
            'currency' => $this->currency,
            'timestamp' => $this->timestamp,
            'metadata' => $this->metadata
        ];
    }
    
    /**
     * Create from database row
     * 
     * @param array $row Database row
     * @return self
     */
    public static function fromDatabaseRow(array $row): self
    {
        $metadata = null;
        if (isset($row['metadata']) && is_string($row['metadata'])) {
            $metadata = json_decode($row['metadata'], true);
        }
        
        return new self([
            'id' => $row['id'] ?? null,
            'index_symbol' => $row['index_symbol'] ?? '',
            'index_name' => $row['index_name'] ?? '',
            'region' => $row['region'] ?? 'US',
            'asset_class' => $row['asset_class'] ?? 'equity',
            'value' => (float)($row['value'] ?? 0),
            'change_percent' => (float)($row['change_percent'] ?? 0),
            'constituents' => (int)($row['constituents'] ?? 0),
            'market_cap' => (float)($row['market_cap'] ?? 0),
            'currency' => $row['currency'] ?? 'USD',
            'timestamp' => $row['timestamp'] ?? date('Y-m-d H:i:s'),
            'metadata' => $metadata
        ]);
    }
}
