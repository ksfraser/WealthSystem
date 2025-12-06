<?php

declare(strict_types=1);

namespace Tests\Enums;

use App\Enums\AssetType;
use PHPUnit\Framework\TestCase;

/**
 * AssetType Enum Test Suite
 * 
 * Tests asset type enumeration functionality including:
 * - Crypto detection
 * - ETF detection  
 * - Trading hours
 * - NAV tracking requirements
 * 
 * @package Tests\Enums
 */
class AssetTypeTest extends TestCase
{
    public function testItHasStockType(): void
    {
        $this->assertEquals('stock', AssetType::STOCK->value);
    }
    
    public function testItHasETFType(): void
    {
        $this->assertEquals('etf', AssetType::ETF->value);
    }
    
    public function testItHasCryptoETFType(): void
    {
        $this->assertEquals('crypto_etf', AssetType::CRYPTO_ETF->value);
    }
    
    public function testItHasCryptoSpotType(): void
    {
        $this->assertEquals('crypto_spot', AssetType::CRYPTO_SPOT->value);
    }
    
    public function testItDetectsCryptoAssets(): void
    {
        $this->assertTrue(AssetType::CRYPTO_ETF->isCrypto());
        $this->assertTrue(AssetType::CRYPTO_SPOT->isCrypto());
        $this->assertFalse(AssetType::STOCK->isCrypto());
        $this->assertFalse(AssetType::ETF->isCrypto());
    }
    
    public function testItDetectsETFs(): void
    {
        $this->assertTrue(AssetType::ETF->isETF());
        $this->assertTrue(AssetType::CRYPTO_ETF->isETF());
        $this->assertFalse(AssetType::STOCK->isETF());
        $this->assertFalse(AssetType::CRYPTO_SPOT->isETF());
    }
    
    public function testItReturnsTradingHours(): void
    {
        $this->assertEquals('24/7', AssetType::CRYPTO_SPOT->getTradingHours());
        $this->assertEquals('market_hours', AssetType::STOCK->getTradingHours());
        $this->assertEquals('market_hours', AssetType::ETF->getTradingHours());
        $this->assertEquals('market_hours', AssetType::CRYPTO_ETF->getTradingHours());
    }
    
    public function testItIdentifiesNAVTrackingRequirement(): void
    {
        $this->assertTrue(AssetType::CRYPTO_ETF->requiresNAVTracking());
        $this->assertFalse(AssetType::STOCK->requiresNAVTracking());
        $this->assertFalse(AssetType::ETF->requiresNAVTracking());
        $this->assertFalse(AssetType::CRYPTO_SPOT->requiresNAVTracking());
    }
}
