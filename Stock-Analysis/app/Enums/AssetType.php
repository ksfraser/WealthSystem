<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Asset Type Enumeration
 * 
 * Defines the various asset types supported by the trading system:
 * - STOCK: Traditional equities
 * - ETF: Exchange-traded funds (traditional)
 * - CRYPTO_ETF: Cryptocurrency ETFs (spot or futures-based)
 * - CRYPTO_SPOT: Direct cryptocurrency holdings
 * 
 * @package App\Enums
 */
enum AssetType: string
{
    case STOCK = 'stock';
    case ETF = 'etf';
    case CRYPTO_ETF = 'crypto_etf';
    case CRYPTO_SPOT = 'crypto_spot';
    
    /**
     * Check if asset is cryptocurrency-related
     */
    public function isCrypto(): bool
    {
        return match($this) {
            self::CRYPTO_ETF, self::CRYPTO_SPOT => true,
            default => false
        };
    }
    
    /**
     * Check if asset is an ETF (any type)
     */
    public function isETF(): bool
    {
        return match($this) {
            self::ETF, self::CRYPTO_ETF => true,
            default => false
        };
    }
    
    /**
     * Get trading hours type
     */
    public function getTradingHours(): string
    {
        return match($this) {
            self::CRYPTO_SPOT => '24/7',
            self::STOCK, self::ETF, self::CRYPTO_ETF => 'market_hours'
        };
    }
    
    /**
     * Check if requires NAV tracking
     */
    public function requiresNAVTracking(): bool
    {
        return $this === self::CRYPTO_ETF;
    }
}
