<?php

declare(strict_types=1);

namespace Tests\Alert;

use PHPUnit\Framework\TestCase;
use App\Alert\AlertEngine;
use App\Alert\AlertCondition;
use App\Services\EmailService;

/**
 * Test suite for AlertEngine
 * 
 * Tests advanced alert functionality including:
 * - Custom alert conditions
 * - Alert rule evaluation
 * - Multi-condition alerts
 * - Alert throttling
 * - Alert history tracking
 * 
 * @covers \App\Alert\AlertEngine
 */
class AlertEngineTest extends TestCase
{
    private ?AlertEngine $engine = null;
    private ?EmailService $emailService = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock email service
        $this->emailService = $this->createMock(EmailService::class);
        
        $this->engine = new AlertEngine($this->emailService);
    }

    /**
     * @test
     * @group alerts
     */
    public function itCreatesBasicAlert(): void
    {
        $alertId = $this->engine->createAlert([
            'user_id' => 1,
            'name' => 'Price Alert',
            'condition_type' => 'price_above',
            'symbol' => 'AAPL',
            'threshold' => 150.00
        ]);
        
        $this->assertIsInt($alertId);
        $this->assertGreaterThan(0, $alertId);
    }

    /**
     * @test
     * @group alerts
     */
    public function itEvaluatesPriceAboveCondition(): void
    {
        $condition = new AlertCondition('price_above', 150.00);
        
        $this->assertTrue($condition->evaluate(['price' => 155.00]));
        $this->assertFalse($condition->evaluate(['price' => 145.00]));
    }

    /**
     * @test
     * @group alerts
     */
    public function itEvaluatesPriceBelowCondition(): void
    {
        $condition = new AlertCondition('price_below', 100.00);
        
        $this->assertTrue($condition->evaluate(['price' => 95.00]));
        $this->assertFalse($condition->evaluate(['price' => 105.00]));
    }

    /**
     * @test
     * @group alerts
     */
    public function itEvaluatesPercentageChangeCondition(): void
    {
        $condition = new AlertCondition('percent_change', 5.0);
        
        $this->assertTrue($condition->evaluate([
            'previous_price' => 100.00,
            'current_price' => 106.00
        ]));
        
        $this->assertFalse($condition->evaluate([
            'previous_price' => 100.00,
            'current_price' => 103.00
        ]));
    }

    /**
     * @test
     * @group alerts
     */
    public function itEvaluatesVolumeThresholdCondition(): void
    {
        $condition = new AlertCondition('volume_above', 1000000);
        
        $this->assertTrue($condition->evaluate(['volume' => 1500000]));
        $this->assertFalse($condition->evaluate(['volume' => 800000]));
    }

    /**
     * @test
     * @group alerts
     */
    public function itEvaluatesMultipleConditions(): void
    {
        $conditions = [
            new AlertCondition('price_above', 150.00),
            new AlertCondition('volume_above', 1000000)
        ];
        
        $allMet = $this->engine->evaluateConditions($conditions, [
            'price' => 155.00,
            'volume' => 1500000
        ]);
        
        $this->assertTrue($allMet);
    }

    /**
     * @test
     * @group alerts
     */
    public function itFailsWhenAnyConditionNotMet(): void
    {
        $conditions = [
            new AlertCondition('price_above', 150.00),
            new AlertCondition('volume_above', 1000000)
        ];
        
        $allMet = $this->engine->evaluateConditions($conditions, [
            'price' => 155.00,
            'volume' => 500000 // Below threshold
        ]);
        
        $this->assertFalse($allMet);
    }

    /**
     * @test
     * @group alerts
     */
    public function itTriggersAlertWhenConditionMet(): void
    {
        $alertId = $this->engine->createAlert([
            'user_id' => 1,
            'name' => 'AAPL Price Alert',
            'condition_type' => 'price_above',
            'symbol' => 'AAPL',
            'threshold' => 150.00,
            'email' => 'user@example.com'
        ]);
        
        $this->emailService
            ->expects($this->once())
            ->method('sendAlert');
        
        $this->engine->checkAlerts([
            'AAPL' => ['price' => 155.00, 'volume' => 1000000]
        ]);
    }

    /**
     * @test
     * @group alerts
     */
    public function itDoesNotTriggerWhenConditionNotMet(): void
    {
        $alertId = $this->engine->createAlert([
            'user_id' => 1,
            'name' => 'AAPL Price Alert',
            'condition_type' => 'price_above',
            'symbol' => 'AAPL',
            'threshold' => 150.00,
            'email' => 'user@example.com'
        ]);
        
        $this->emailService
            ->expects($this->never())
            ->method('sendAlert');
        
        $this->engine->checkAlerts([
            'AAPL' => ['price' => 145.00, 'volume' => 1000000]
        ]);
    }

    /**
     * @test
     * @group alerts
     */
    public function itThrottlesRepeatedAlerts(): void
    {
        $alertId = $this->engine->createAlert([
            'user_id' => 1,
            'name' => 'Price Alert',
            'condition_type' => 'price_above',
            'symbol' => 'AAPL',
            'threshold' => 150.00,
            'email' => 'user@example.com',
            'throttle_minutes' => 60
        ]);
        
        // First alert should send
        $this->emailService
            ->expects($this->once())
            ->method('sendAlert');
        
        $marketData = ['AAPL' => ['price' => 155.00, 'volume' => 1000000]];
        
        // Trigger twice
        $this->engine->checkAlerts($marketData);
        $this->engine->checkAlerts($marketData);
    }

    /**
     * @test
     * @group alerts
     */
    public function itRecordsAlertHistory(): void
    {
        $alertId = $this->engine->createAlert([
            'user_id' => 1,
            'name' => 'Price Alert',
            'condition_type' => 'price_above',
            'symbol' => 'AAPL',
            'threshold' => 150.00,
            'email' => 'user@example.com'
        ]);
        
        $this->engine->checkAlerts([
            'AAPL' => ['price' => 155.00, 'volume' => 1000000]
        ]);
        
        $history = $this->engine->getAlertHistory($alertId);
        
        $this->assertNotEmpty($history);
        $this->assertArrayHasKey('triggered_at', $history[0]);
        $this->assertArrayHasKey('data', $history[0]);
    }

    /**
     * @test
     * @group alerts
     */
    public function itGetsActiveAlertsForUser(): void
    {
        $this->engine->createAlert([
            'user_id' => 1,
            'name' => 'Alert 1',
            'condition_type' => 'price_above',
            'symbol' => 'AAPL',
            'threshold' => 150.00
        ]);
        
        $this->engine->createAlert([
            'user_id' => 1,
            'name' => 'Alert 2',
            'condition_type' => 'price_below',
            'symbol' => 'GOOGL',
            'threshold' => 100.00
        ]);
        
        $alerts = $this->engine->getActiveAlerts(1);
        
        $this->assertCount(2, $alerts);
    }

    /**
     * @test
     * @group alerts
     */
    public function itDeletesAlert(): void
    {
        $alertId = $this->engine->createAlert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'condition_type' => 'price_above',
            'symbol' => 'AAPL',
            'threshold' => 150.00
        ]);
        
        $this->engine->deleteAlert($alertId);
        
        $alerts = $this->engine->getActiveAlerts(1);
        $this->assertEmpty($alerts);
    }

    /**
     * @test
     * @group alerts
     */
    public function itUpdatesAlert(): void
    {
        $alertId = $this->engine->createAlert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'condition_type' => 'price_above',
            'symbol' => 'AAPL',
            'threshold' => 150.00
        ]);
        
        $this->engine->updateAlert($alertId, [
            'threshold' => 160.00
        ]);
        
        $alert = $this->engine->getAlert($alertId);
        $this->assertEquals(160.00, $alert['threshold']);
    }

    /**
     * @test
     * @group alerts
     */
    public function itValidatesRequiredFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->engine->createAlert([
            'user_id' => 1,
            'name' => 'Invalid Alert'
            // Missing required fields
        ]);
    }

    /**
     * @test
     * @group alerts
     */
    public function itSupportsComplexConditions(): void
    {
        $alertId = $this->engine->createAlert([
            'user_id' => 1,
            'name' => 'Complex Alert',
            'symbol' => 'AAPL',
            'conditions' => [
                ['type' => 'price_above', 'value' => 150.00],
                ['type' => 'volume_above', 'value' => 1000000],
                ['type' => 'percent_change', 'value' => 5.0]
            ],
            'email' => 'user@example.com'
        ]);
        
        $this->emailService
            ->expects($this->once())
            ->method('sendAlert');
        
        $this->engine->checkAlerts([
            'AAPL' => [
                'price' => 155.00,
                'volume' => 1500000,
                'previous_price' => 145.00,
                'current_price' => 155.00
            ]
        ]);
    }

    /**
     * @test
     * @group alerts
     */
    public function itProvidesAlertStatistics(): void
    {
        $this->engine->createAlert([
            'user_id' => 1,
            'name' => 'Alert 1',
            'condition_type' => 'price_above',
            'symbol' => 'AAPL',
            'threshold' => 150.00,
            'email' => 'user@example.com'
        ]);
        
        // Trigger alert
        $this->engine->checkAlerts([
            'AAPL' => ['price' => 155.00, 'volume' => 1000000]
        ]);
        
        $stats = $this->engine->getStatistics(1);
        
        $this->assertArrayHasKey('total_alerts', $stats);
        $this->assertArrayHasKey('triggered_count', $stats);
        $this->assertArrayHasKey('last_triggered', $stats);
    }
}
