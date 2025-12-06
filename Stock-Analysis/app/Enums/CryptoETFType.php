<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Crypto ETF Type Enumeration
 * 
 * Defines the structure types for cryptocurrency ETFs:
 * - SPOT: Holds actual cryptocurrency (e.g., BTCC.TO)
 * - FUTURES_BASED: Holds Bitcoin futures contracts (e.g., BITO)
 * - MIXED: Combination of spot and futures
 * 
 * Key differences:
 * - Spot ETFs track crypto prices more closely
 * - Futures-based ETFs have higher tracking error
 * - Spot ETFs typically have lower management fees
 * 
 * @package App\Enums
 */
enum CryptoETFType: string
{
    case SPOT = 'spot';
    case FUTURES_BASED = 'futures';
    case MIXED = 'mixed';
    
    /**
     * Get expected tracking error range (annual %)
     */
    public function getExpectedTrackingError(): array
    {
        return match($this) {
            self::SPOT => ['min' => 0.5, 'max' => 2.0],
            self::FUTURES_BASED => ['min' => 3.0, 'max' => 15.0],
            self::MIXED => ['min' => 1.5, 'max' => 8.0]
        };
    }
    
    /**
     * Check if requires futures roll tracking
     */
    public function requiresFuturesRollTracking(): bool
    {
        return match($this) {
            self::FUTURES_BASED, self::MIXED => true,
            self::SPOT => false
        };
    }
    
    /**
     * Get typical management fee range (annual %)
     */
    public function getTypicalFeeRange(): array
    {
        return match($this) {
            self::SPOT => ['min' => 0.20, 'max' => 0.95],
            self::FUTURES_BASED => ['min' => 0.65, 'max' => 1.50],
            self::MIXED => ['min' => 0.50, 'max' => 1.25]
        };
    }
}
