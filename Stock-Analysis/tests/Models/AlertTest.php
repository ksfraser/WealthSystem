<?php

declare(strict_types=1);

namespace Tests\Models;

use App\Models\Alert;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * Alert Model Test Suite
 * 
 * Tests alert data model including:
 * - Property getters/setters
 * - Validation rules
 * - Array conversion
 * - Condition types
 * - Default values
 * 
 * @package Tests\Models
 */
class AlertTest extends TestCase
{
    /**
     * @test
     */
    public function itCreatesAlertWithRequiredFields(): void
    {
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'AAPL Price Alert',
            'condition_type' => 'price_above',
            'threshold' => 150.0
        ]);
        
        $this->assertEquals(1, $alert->getUserId());
        $this->assertEquals('AAPL Price Alert', $alert->getName());
        $this->assertEquals('price_above', $alert->getConditionType());
        $this->assertEquals(150.0, $alert->getThreshold());
    }
    
    /**
     * @test
     */
    public function itSetsOptionalFields(): void
    {
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'email' => 'user@example.com',
            'throttle_minutes' => 60
        ]);
        
        $this->assertEquals('AAPL', $alert->getSymbol());
        $this->assertEquals('user@example.com', $alert->getEmail());
        $this->assertEquals(60, $alert->getThrottleMinutes());
    }
    
    /**
     * @test
     */
    public function itDefaultsToActiveTrue(): void
    {
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'condition_type' => 'price_above',
            'threshold' => 150.0
        ]);
        
        $this->assertTrue($alert->isActive());
    }
    
    /**
     * @test
     */
    public function itSetsActiveFlag(): void
    {
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'active' => false
        ]);
        
        $this->assertFalse($alert->isActive());
    }
    
    /**
     * @test
     */
    public function itValidatesConditionType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid condition type');
        
        new Alert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'condition_type' => 'invalid_condition',
            'threshold' => 150.0
        ]);
    }
    
    /**
     * @test
     */
    public function itAcceptsValidConditionTypes(): void
    {
        $validTypes = [
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
        
        foreach ($validTypes as $type) {
            $alert = new Alert([
                'user_id' => 1,
                'name' => 'Test Alert',
                'condition_type' => $type,
                'threshold' => 50.0
            ]);
            
            $this->assertEquals($type, $alert->getConditionType());
        }
    }
    
    /**
     * @test
     */
    public function itRequiresUserId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('user_id is required');
        
        new Alert([
            'name' => 'Test Alert',
            'condition_type' => 'price_above',
            'threshold' => 150.0
        ]);
    }
    
    /**
     * @test
     */
    public function itRequiresName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name is required');
        
        new Alert([
            'user_id' => 1,
            'condition_type' => 'price_above',
            'threshold' => 150.0
        ]);
    }
    
    /**
     * @test
     */
    public function itRequiresConditionType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('condition_type is required');
        
        new Alert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'threshold' => 150.0
        ]);
    }
    
    /**
     * @test
     */
    public function itRequiresThreshold(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('threshold is required');
        
        new Alert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'condition_type' => 'price_above'
        ]);
    }
    
    /**
     * @test
     */
    public function itConvertsToArray(): void
    {
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'AAPL Price Alert',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'email' => 'user@example.com',
            'throttle_minutes' => 60,
            'active' => true
        ]);
        
        $array = $alert->toArray();
        
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('symbol', $array);
        $this->assertArrayHasKey('condition_type', $array);
        $this->assertArrayHasKey('threshold', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('throttle_minutes', $array);
        $this->assertArrayHasKey('active', $array);
    }
    
    /**
     * @test
     */
    public function itSetsId(): void
    {
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'condition_type' => 'price_above',
            'threshold' => 150.0
        ]);
        
        $alert->setId(42);
        
        $this->assertEquals(42, $alert->getId());
    }
    
    /**
     * @test
     */
    public function itUpdatesProperties(): void
    {
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'Original Name',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0
        ]);
        
        $alert->setName('Updated Name');
        $alert->setSymbol('GOOGL');
        $alert->setThreshold(175.0);
        $alert->setEmail('new@example.com');
        $alert->setThrottleMinutes(120);
        $alert->setActive(false);
        
        $this->assertEquals('Updated Name', $alert->getName());
        $this->assertEquals('GOOGL', $alert->getSymbol());
        $this->assertEquals(175.0, $alert->getThreshold());
        $this->assertEquals('new@example.com', $alert->getEmail());
        $this->assertEquals(120, $alert->getThrottleMinutes());
        $this->assertFalse($alert->isActive());
    }
    
    /**
     * @test
     */
    public function itTracksCreatedAt(): void
    {
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'condition_type' => 'price_above',
            'threshold' => 150.0
        ]);
        
        $this->assertNotNull($alert->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $alert->getCreatedAt());
    }
    
    /**
     * @test
     */
    public function itTracksUpdatedAt(): void
    {
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'condition_type' => 'price_above',
            'threshold' => 150.0
        ]);
        
        $alert->setUpdatedAt(new \DateTime());
        
        $this->assertNotNull($alert->getUpdatedAt());
        $this->assertInstanceOf(\DateTime::class, $alert->getUpdatedAt());
    }
    
    /**
     * @test
     */
    public function itValidatesThrottleMinutesIsNonNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('throttle_minutes must be non-negative');
        
        new Alert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'throttle_minutes' => -10
        ]);
    }
    
    /**
     * @test
     */
    public function itDefaultsThrottleMinutesToZero(): void
    {
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'condition_type' => 'price_above',
            'threshold' => 150.0
        ]);
        
        $this->assertEquals(0, $alert->getThrottleMinutes());
    }
    
    /**
     * @test
     */
    public function itAllowsNullSymbol(): void
    {
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'General Alert',
            'condition_type' => 'percent_change',
            'threshold' => 5.0
        ]);
        
        $this->assertNull($alert->getSymbol());
    }
    
    /**
     * @test
     */
    public function itProvidesMeaningfulToString(): void
    {
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'AAPL Price Alert',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0
        ]);
        
        $string = (string) $alert;
        
        $this->assertStringContainsString('AAPL Price Alert', $string);
        $this->assertStringContainsString('price_above', $string);
        $this->assertStringContainsString('150', $string);
    }
}
