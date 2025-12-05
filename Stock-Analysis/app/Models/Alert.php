<?php

declare(strict_types=1);

namespace App\Models;

use DateTime;
use InvalidArgumentException;

/**
 * Alert Model
 * 
 * Represents a user-defined alert for market conditions.
 * Supports various condition types including price, volume,
 * percentage change, and technical indicator thresholds.
 * 
 * @package App\Models
 */
class Alert extends BaseModel
{
    private int $userId;
    private string $name;
    private ?string $symbol = null;
    private string $conditionType;
    private float $threshold;
    private ?string $email = null;
    private int $throttleMinutes = 0;
    private bool $active = true;
    private DateTime $createdAt;
    private ?DateTime $updatedAt = null;
    
    /**
     * Valid condition types
     */
    private const VALID_CONDITION_TYPES = [
        'price_above',
        'price_below',
        'percent_change',
        'volume_above',
        'volume_below',
        'rsi_above',
        'rsi_below',
        'macd_bullish',
        'macd_bearish'
    ];
    
    /**
     * Create new alert
     *
     * @param array<string, mixed> $data Alert data
     * @throws InvalidArgumentException If validation fails
     */
    public function __construct(array $data = [])
    {
        $this->validateAlert($data);
        
        $this->userId = (int) $data['user_id'];
        $this->name = $data['name'];
        $this->conditionType = $data['condition_type'];
        $this->threshold = (float) $data['threshold'];
        
        if (isset($data['symbol'])) {
            $this->symbol = $data['symbol'];
        }
        
        if (isset($data['email'])) {
            $this->email = $data['email'];
        }
        
        if (isset($data['throttle_minutes'])) {
            $this->setThrottleMinutes((int) $data['throttle_minutes']);
        }
        
        if (isset($data['active'])) {
            $this->active = (bool) $data['active'];
        }
        
        if (isset($data['created_at'])) {
            $this->createdAt = $data['created_at'] instanceof DateTime 
                ? $data['created_at'] 
                : new DateTime($data['created_at']);
        } else {
            $this->createdAt = new DateTime();
        }
        
        if (isset($data['updated_at'])) {
            $this->updatedAt = $data['updated_at'] instanceof DateTime 
                ? $data['updated_at'] 
                : new DateTime($data['updated_at']);
        }
        
        if (isset($data['id'])) {
            $this->setId((int) $data['id']);
        }
    }
    
    /**
     * Validate alert data
     *
     * @param array<string, mixed> $data Alert data
     * @return void
     * @throws InvalidArgumentException If validation fails
     */
    private function validateAlert(array $data): void
    {
        if (!isset($data['user_id'])) {
            throw new InvalidArgumentException('user_id is required');
        }
        
        if (!isset($data['name']) || empty($data['name'])) {
            throw new InvalidArgumentException('name is required');
        }
        
        if (!isset($data['condition_type'])) {
            throw new InvalidArgumentException('condition_type is required');
        }
        
        if (!in_array($data['condition_type'], self::VALID_CONDITION_TYPES, true)) {
            throw new InvalidArgumentException(
                "Invalid condition type: {$data['condition_type']}"
            );
        }
        
        if (!isset($data['threshold'])) {
            throw new InvalidArgumentException('threshold is required');
        }
        
        if (isset($data['throttle_minutes']) && $data['throttle_minutes'] < 0) {
            throw new InvalidArgumentException('throttle_minutes must be non-negative');
        }
    }
    
    /**
     * Get user ID
     *
     * @return int User ID
     */
    public function getUserId(): int
    {
        return $this->userId;
    }
    
    /**
     * Get alert name
     *
     * @return string Alert name
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Set alert name
     *
     * @param string $name Alert name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    
    /**
     * Get symbol
     *
     * @return string|null Symbol (null for general alerts)
     */
    public function getSymbol(): ?string
    {
        return $this->symbol;
    }
    
    /**
     * Set symbol
     *
     * @param string|null $symbol Symbol
     * @return void
     */
    public function setSymbol(?string $symbol): void
    {
        $this->symbol = $symbol;
    }
    
    /**
     * Get condition type
     *
     * @return string Condition type
     */
    public function getConditionType(): string
    {
        return $this->conditionType;
    }
    
    /**
     * Get threshold value
     *
     * @return float Threshold
     */
    public function getThreshold(): float
    {
        return $this->threshold;
    }
    
    /**
     * Set threshold value
     *
     * @param float $threshold Threshold
     * @return void
     */
    public function setThreshold(float $threshold): void
    {
        $this->threshold = $threshold;
    }
    
    /**
     * Get email for notifications
     *
     * @return string|null Email address
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }
    
    /**
     * Set email for notifications
     *
     * @param string|null $email Email address
     * @return void
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }
    
    /**
     * Get throttle minutes
     *
     * @return int Minutes between notifications
     */
    public function getThrottleMinutes(): int
    {
        return $this->throttleMinutes;
    }
    
    /**
     * Set throttle minutes
     *
     * @param int $throttleMinutes Minutes between notifications
     * @return void
     * @throws InvalidArgumentException If negative
     */
    public function setThrottleMinutes(int $throttleMinutes): void
    {
        if ($throttleMinutes < 0) {
            throw new InvalidArgumentException('throttle_minutes must be non-negative');
        }
        
        $this->throttleMinutes = $throttleMinutes;
    }
    
    /**
     * Check if alert is active
     *
     * @return bool True if active
     */
    public function isActive(): bool
    {
        return $this->active;
    }
    
    /**
     * Set active status
     *
     * @param bool $active Active status
     * @return void
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }
    
    /**
     * Get creation timestamp
     *
     * @return DateTime Creation time
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
    
    /**
     * Get last update timestamp
     *
     * @return DateTime|null Last update time
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }
    
    /**
     * Set last update timestamp
     *
     * @param DateTime $updatedAt Update time
     * @return void
     */
    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
    
    /**
     * Convert alert to array
     *
     * @return array<string, mixed> Alert data
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'user_id' => $this->userId,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'condition_type' => $this->conditionType,
            'threshold' => $this->threshold,
            'email' => $this->email,
            'throttle_minutes' => $this->throttleMinutes,
            'active' => $this->active,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Convert alert to string
     *
     * @return string Human-readable representation
     */
    public function __toString(): string
    {
        $symbol = $this->symbol ? " ({$this->symbol})" : '';
        return "{$this->name}{$symbol}: {$this->conditionType} {$this->threshold}";
    }
}
