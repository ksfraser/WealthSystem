<?php

namespace App\Models;

/**
 * Sector Performance Model
 * 
 * Represents sector-level performance data.
 * 
 * @package App\Models
 */
class SectorPerformance
{
    private ?int $id;
    private string $sectorCode;
    private string $sectorName;
    private string $classification;
    private float $performanceValue;
    private float $changePercent;
    private float $marketCapWeight;
    private string $timestamp;
    private ?array $metadata;
    
    /**
     * Constructor
     * 
     * @param array $data Sector performance data
     */
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->sectorCode = $data['sector_code'] ?? '';
        $this->sectorName = $data['sector_name'] ?? '';
        $this->classification = $data['classification'] ?? 'GICS';
        $this->performanceValue = $data['performance_value'] ?? 0.0;
        $this->changePercent = $data['change_percent'] ?? 0.0;
        $this->marketCapWeight = $data['market_cap_weight'] ?? 0.0;
        $this->timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
        $this->metadata = $data['metadata'] ?? null;
    }
    
    // ========== GETTERS ==========
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getSectorCode(): string
    {
        return $this->sectorCode;
    }
    
    public function getSectorName(): string
    {
        return $this->sectorName;
    }
    
    public function getClassification(): string
    {
        return $this->classification;
    }
    
    public function getPerformanceValue(): float
    {
        return $this->performanceValue;
    }
    
    public function getChangePercent(): float
    {
        return $this->changePercent;
    }
    
    public function getMarketCapWeight(): float
    {
        return $this->marketCapWeight;
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
    
    public function setSectorCode(string $sectorCode): void
    {
        $this->sectorCode = $sectorCode;
    }
    
    public function setSectorName(string $sectorName): void
    {
        $this->sectorName = $sectorName;
    }
    
    public function setClassification(string $classification): void
    {
        $this->classification = $classification;
    }
    
    public function setPerformanceValue(float $performanceValue): void
    {
        $this->performanceValue = $performanceValue;
    }
    
    public function setChangePercent(float $changePercent): void
    {
        $this->changePercent = $changePercent;
    }
    
    public function setMarketCapWeight(float $marketCapWeight): void
    {
        $this->marketCapWeight = $marketCapWeight;
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
            'sector_code' => $this->sectorCode,
            'sector_name' => $this->sectorName,
            'classification' => $this->classification,
            'performance_value' => $this->performanceValue,
            'change_percent' => $this->changePercent,
            'market_cap_weight' => $this->marketCapWeight,
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
            'sector_code' => $row['sector_code'] ?? '',
            'sector_name' => $row['sector_name'] ?? '',
            'classification' => $row['classification'] ?? 'GICS',
            'performance_value' => (float)($row['performance_value'] ?? 0),
            'change_percent' => (float)($row['change_percent'] ?? 0),
            'market_cap_weight' => (float)($row['market_cap_weight'] ?? 0),
            'timestamp' => $row['timestamp'] ?? date('Y-m-d H:i:s'),
            'metadata' => $metadata
        ]);
    }
}
