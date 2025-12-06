<?php

declare(strict_types=1);

namespace Tests\Enums;

use App\Enums\CryptoETFType;
use PHPUnit\Framework\TestCase;

/**
 * CryptoETFType Enum Test Suite
 * 
 * Tests crypto ETF type enumeration functionality including:
 * - Tracking error expectations
 * - Futures roll requirements
 * - Management fee ranges
 * 
 * @package Tests\Enums
 */
class CryptoETFTypeTest extends TestCase
{
    public function testItHasSpotType(): void
    {
        $this->assertEquals('spot', CryptoETFType::SPOT->value);
    }
    
    public function testItHasFuturesBasedType(): void
    {
        $this->assertEquals('futures', CryptoETFType::FUTURES_BASED->value);
    }
    
    public function testItHasMixedType(): void
    {
        $this->assertEquals('mixed', CryptoETFType::MIXED->value);
    }
    
    public function testItReturnsTrackingErrorForSpot(): void
    {
        $error = CryptoETFType::SPOT->getExpectedTrackingError();
        
        $this->assertIsArray($error);
        $this->assertArrayHasKey('min', $error);
        $this->assertArrayHasKey('max', $error);
        $this->assertEquals(0.5, $error['min']);
        $this->assertEquals(2.0, $error['max']);
    }
    
    public function testItReturnsTrackingErrorForFuturesBased(): void
    {
        $error = CryptoETFType::FUTURES_BASED->getExpectedTrackingError();
        
        $this->assertIsArray($error);
        $this->assertEquals(3.0, $error['min']);
        $this->assertEquals(15.0, $error['max']);
    }
    
    public function testItReturnsTrackingErrorForMixed(): void
    {
        $error = CryptoETFType::MIXED->getExpectedTrackingError();
        
        $this->assertIsArray($error);
        $this->assertEquals(1.5, $error['min']);
        $this->assertEquals(8.0, $error['max']);
    }
    
    public function testItIdentifiesFuturesRollRequirement(): void
    {
        $this->assertTrue(CryptoETFType::FUTURES_BASED->requiresFuturesRollTracking());
        $this->assertTrue(CryptoETFType::MIXED->requiresFuturesRollTracking());
        $this->assertFalse(CryptoETFType::SPOT->requiresFuturesRollTracking());
    }
    
    public function testItReturnsTypicalFeeRangeForSpot(): void
    {
        $fees = CryptoETFType::SPOT->getTypicalFeeRange();
        
        $this->assertIsArray($fees);
        $this->assertArrayHasKey('min', $fees);
        $this->assertArrayHasKey('max', $fees);
        $this->assertEquals(0.20, $fees['min']);
        $this->assertEquals(0.95, $fees['max']);
    }
    
    public function testItReturnsTypicalFeeRangeForFuturesBased(): void
    {
        $fees = CryptoETFType::FUTURES_BASED->getTypicalFeeRange();
        
        $this->assertIsArray($fees);
        $this->assertEquals(0.65, $fees['min']);
        $this->assertEquals(1.50, $fees['max']);
    }
    
    public function testItReturnsTypicalFeeRangeForMixed(): void
    {
        $fees = CryptoETFType::MIXED->getTypicalFeeRange();
        
        $this->assertIsArray($fees);
        $this->assertEquals(0.50, $fees['min']);
        $this->assertEquals(1.25, $fees['max']);
    }
}
