<?php
namespace Ksfraser\Finance\MarketFactors\Entities;

/**
 * Market Factor Entity
 * 
 * Represents various market factors that influence stock prices
 */
class MarketFactor
{
    private string $symbol;
    private string $name;
    private string $type; // sector, index, forex, economic, earnings, etc.
    private float $value;
    private float $change;
    private float $changePercent;
    private \DateTime $timestamp;
    private array $metadata;

    public function __construct(
        string $symbol,
        string $name,
        string $type,
        float $value,
        float $change = 0.0,
        float $changePercent = 0.0,
        ?\DateTime $timestamp = null,
        array $metadata = []
    ) {
        $this->symbol = $symbol;
        $this->name = $name;
        $this->type = $type;
        $this->value = $value;
        $this->change = $change;
        $this->changePercent = $changePercent;
        $this->timestamp = $timestamp ?? new \DateTime();
        $this->metadata = $metadata;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getChange(): float
    {
        return $this->change;
    }

    public function getChangePercent(): float
    {
        return $this->changePercent;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setValue(float $value): void
    {
        $this->value = $value;
    }

    public function setChange(float $change): void
    {
        $this->change = $change;
    }

    public function setChangePercent(float $changePercent): void
    {
        $this->changePercent = $changePercent;
    }

    public function setTimestamp(\DateTime $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function addMetadata(string $key, $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function getMetadataValue(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Convert to array for database storage or API response
     */
    public function toArray(): array
    {
        return [
            'symbol' => $this->symbol,
            'name' => $this->name,
            'type' => $this->type,
            'value' => $this->value,
            'change' => $this->change,
            'change_percent' => $this->changePercent,
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s'),
            'metadata' => json_encode($this->metadata)
        ];
    }

    /**
     * Create from array (database or API response)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['symbol'],
            $data['name'],
            $data['type'],
            (float)$data['value'],
            (float)($data['change'] ?? 0.0),
            (float)($data['change_percent'] ?? 0.0),
            isset($data['timestamp']) ? new \DateTime($data['timestamp']) : null,
            isset($data['metadata']) ? json_decode($data['metadata'], true) ?? [] : []
        );
    }

    /**
     * Check if this factor is positive/bullish
     */
    public function isBullish(): bool
    {
        return $this->changePercent > 0;
    }

    /**
     * Check if this factor is negative/bearish
     */
    public function isBearish(): bool
    {
        return $this->changePercent < 0;
    }

    /**
     * Get strength of the signal (0-1 scale)
     */
    public function getSignalStrength(): float
    {
        $absChange = abs($this->changePercent);
        
        // Scale based on typical market movements
        if ($absChange >= 5.0) return 1.0;  // Very strong
        if ($absChange >= 3.0) return 0.8;  // Strong
        if ($absChange >= 2.0) return 0.6;  // Moderate
        if ($absChange >= 1.0) return 0.4;  // Weak
        if ($absChange >= 0.5) return 0.2;  // Very weak
        
        return 0.1; // Minimal
    }

    /**
     * Get age of data in minutes
     */
    public function getDataAge(): int
    {
        $now = new \DateTime();
        $diff = $now->getTimestamp() - $this->timestamp->getTimestamp();
        return (int)($diff / 60);
    }

    /**
     * Check if data is stale (older than specified minutes)
     */
    public function isStale(int $maxAgeMinutes = 60): bool
    {
        return $this->getDataAge() > $maxAgeMinutes;
    }
}
