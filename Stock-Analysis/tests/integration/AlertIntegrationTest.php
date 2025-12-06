<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Alert\AlertEngine;
use App\Alert\AlertCondition;
use App\Repositories\AlertRepository;
use App\Models\Alert as AlertModel;
use App\Services\EmailService;
use App\Database\Connection;

/**
 * Alert Integration Tests
 * 
 * Tests integration between AlertEngine and AlertRepository
 * to ensure complete alert workflow functionality.
 *
 * @package Tests\Integration
 */
class AlertIntegrationTest extends TestCase
{
    private AlertEngine $engine;
    private AlertRepository $repository;
    private Connection $connection;
    private EmailService $emailService;
    
    protected function setUp(): void
    {
        $this->emailService = $this->createMock(EmailService::class);
        $this->engine = new AlertEngine($this->emailService);
        
        // Use in-memory SQLite for testing
        $this->connection = new Connection([
            'driver' => 'sqlite',
            'database' => ':memory:'
        ]);
        
        $this->repository = new AlertRepository($this->connection);
        
        // Create alerts table
        $this->createAlertsTable();
    }
    
    public function testLoadAlertsFromDatabase(): void
    {
        // Create alerts in database
        $alert1 = new AlertModel([
            'user_id' => 1,
            'name' => 'Price Alert',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'email' => 'user@example.com',
            'active' => true
        ]);
        
        $alert2 = new AlertModel([
            'user_id' => 1,
            'name' => 'Volume Alert',
            'symbol' => 'GOOGL',
            'condition_type' => 'volume_above',
            'threshold' => 1000000.0,
            'email' => 'user@example.com',
            'active' => true
        ]);
        
        $this->repository->save($alert1);
        $this->repository->save($alert2);
        
        // Load alerts into engine
        $activeAlerts = $this->repository->findActive();
        $loadedCount = $this->engine->loadAlertsFromRepository($activeAlerts);
        
        $this->assertEquals(2, $loadedCount);
        
        // Verify alerts are loaded
        $engineAlerts = $this->engine->getActiveAlerts(1);
        $this->assertCount(2, $engineAlerts);
    }
    
    public function testSaveTriggeredAlertToDatabase(): void
    {
        // Create alert in engine
        $alertId = $this->engine->createAlert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 100.0,
            'email' => 'user@example.com'
        ]);
        
        // Save to database
        $alert = $this->engine->getAlert($alertId);
        $alertModel = new AlertModel([
            'user_id' => $alert['user_id'],
            'name' => $alert['name'],
            'symbol' => $alert['symbol'],
            'condition_type' => $alert['conditions'][0]['type'],
            'threshold' => $alert['conditions'][0]['value'],
            'email' => $alert['email'],
            'throttle_minutes' => $alert['throttle_minutes'],
            'active' => $alert['active']
        ]);
        
        $saved = $this->repository->save($alertModel);
        
        $this->assertNotNull($saved->getId());
        $this->assertEquals('Test Alert', $saved->getName());
        
        // Verify can retrieve from database
        $retrieved = $this->repository->findById($saved->getId());
        $this->assertNotNull($retrieved);
        $this->assertEquals('AAPL', $retrieved->getSymbol());
    }
    
    public function testSyncEngineWithRepository(): void
    {
        // Create alert in database
        $alert = new AlertModel([
            'user_id' => 1,
            'name' => 'Sync Test',
            'symbol' => 'MSFT',
            'condition_type' => 'price_below',
            'threshold' => 200.0,
            'email' => 'user@example.com',
            'active' => true
        ]);
        
        $saved = $this->repository->save($alert);
        
        // Load into engine
        $activeAlerts = $this->repository->findActive();
        $this->engine->loadAlertsFromRepository($activeAlerts);
        
        // Trigger alert in engine
        $marketData = [
            'MSFT' => [
                'price' => 180.0,
                'volume' => 5000000
            ]
        ];
        
        $this->emailService->expects($this->once())
            ->method('sendAlert');
        
        $this->engine->checkAlerts($marketData);
        
        // Verify alert history exists in engine
        $engineAlerts = $this->engine->getActiveAlerts(1);
        $this->assertCount(1, $engineAlerts);
    }
    
    public function testUpdateAlertInBothSystems(): void
    {
        // Create in database
        $alert = new AlertModel([
            'user_id' => 1,
            'name' => 'Original Name',
            'symbol' => 'TSLA',
            'condition_type' => 'price_above',
            'threshold' => 500.0,
            'email' => 'user@example.com',
            'active' => true
        ]);
        
        $saved = $this->repository->save($alert);
        $dbAlertId = $saved->getId();
        
        // Load into engine
        $activeAlerts = $this->repository->findActive();
        $engineAlertId = $this->engine->loadAlertsFromRepository($activeAlerts);
        
        // Update in engine
        $this->engine->updateAlert($engineAlertId, [
            'name' => 'Updated Name',
            'threshold' => 550.0
        ]);
        
        // Update in database
        $alert->setName('Updated Name');
        $alert->setThreshold(550.0);
        $this->repository->save($alert);
        
        // Verify both updated
        $engineAlert = $this->engine->getAlert($engineAlertId);
        $this->assertEquals('Updated Name', $engineAlert['name']);
        
        $dbAlert = $this->repository->findById($dbAlertId);
        $this->assertEquals('Updated Name', $dbAlert->getName());
        $this->assertEquals(550.0, $dbAlert->getThreshold());
    }
    
    public function testDeleteAlertFromBothSystems(): void
    {
        // Create in database
        $alert = new AlertModel([
            'user_id' => 1,
            'name' => 'To Delete',
            'symbol' => 'NFLX',
            'condition_type' => 'volume_above',
            'threshold' => 10000000.0,
            'email' => 'user@example.com',
            'active' => true
        ]);
        
        $saved = $this->repository->save($alert);
        $dbAlertId = $saved->getId();
        
        // Load into engine
        $activeAlerts = $this->repository->findActive();
        $engineAlertId = $this->engine->loadAlertsFromRepository($activeAlerts);
        
        // Delete from both
        $this->engine->deleteAlert($engineAlertId);
        $this->repository->delete($dbAlertId);
        
        // Verify deleted from engine
        $engineAlerts = $this->engine->getActiveAlerts(1);
        $this->assertCount(0, $engineAlerts);
        
        // Verify deleted from database
        $dbAlert = $this->repository->findById($dbAlertId);
        $this->assertNull($dbAlert);
    }
    
    public function testLoadOnlyActiveAlerts(): void
    {
        // Create active alert
        $active = new AlertModel([
            'user_id' => 1,
            'name' => 'Active Alert',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'email' => 'user@example.com',
            'active' => true
        ]);
        
        // Create inactive alert
        $inactive = new AlertModel([
            'user_id' => 1,
            'name' => 'Inactive Alert',
            'symbol' => 'GOOGL',
            'condition_type' => 'price_above',
            'threshold' => 2000.0,
            'email' => 'user@example.com',
            'active' => false
        ]);
        
        $this->repository->save($active);
        $this->repository->save($inactive);
        
        // Load only active
        $activeAlerts = $this->repository->findActive();
        $loadedCount = $this->engine->loadAlertsFromRepository($activeAlerts);
        
        $this->assertEquals(1, $loadedCount);
        
        $engineAlerts = $this->engine->getActiveAlerts(1);
        $this->assertCount(1, $engineAlerts);
        $this->assertEquals('Active Alert', $engineAlerts[0]['name']);
    }
    
    public function testLoadAlertsForMultipleUsers(): void
    {
        // Create alerts for different users
        $user1Alert = new AlertModel([
            'user_id' => 1,
            'name' => 'User 1 Alert',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'email' => 'user1@example.com',
            'active' => true
        ]);
        
        $user2Alert = new AlertModel([
            'user_id' => 2,
            'name' => 'User 2 Alert',
            'symbol' => 'GOOGL',
            'condition_type' => 'price_above',
            'threshold' => 2000.0,
            'email' => 'user2@example.com',
            'active' => true
        ]);
        
        $this->repository->save($user1Alert);
        $this->repository->save($user2Alert);
        
        // Load all active alerts
        $activeAlerts = $this->repository->findActive();
        $loadedCount = $this->engine->loadAlertsFromRepository($activeAlerts);
        
        $this->assertEquals(2, $loadedCount);
        
        // Verify each user sees only their alerts
        $user1Alerts = $this->engine->getActiveAlerts(1);
        $this->assertCount(1, $user1Alerts);
        $this->assertEquals('User 1 Alert', $user1Alerts[0]['name']);
        
        $user2Alerts = $this->engine->getActiveAlerts(2);
        $this->assertCount(1, $user2Alerts);
        $this->assertEquals('User 2 Alert', $user2Alerts[0]['name']);
    }
    
    public function testBulkLoadPerformance(): void
    {
        // Create many alerts
        for ($i = 1; $i <= 100; $i++) {
            $alert = new AlertModel([
                'user_id' => $i % 10 + 1,  // 10 different users
                'name' => "Alert {$i}",
                'symbol' => 'AAPL',
                'condition_type' => 'price_above',
                'threshold' => 100.0 + $i,
                'email' => "user{$i}@example.com",
                'active' => true
            ]);
            
            $this->repository->save($alert);
        }
        
        // Load all at once
        $startTime = microtime(true);
        $activeAlerts = $this->repository->findActive();
        $loadedCount = $this->engine->loadAlertsFromRepository($activeAlerts);
        $endTime = microtime(true);
        
        $this->assertEquals(100, $loadedCount);
        
        // Should be fast (< 1 second)
        $duration = $endTime - $startTime;
        $this->assertLessThan(1.0, $duration);
    }
    
    public function testReloadAlertsReplacesExisting(): void
    {
        // Initial load
        $alert1 = new AlertModel([
            'user_id' => 1,
            'name' => 'Alert 1',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'email' => 'user@example.com',
            'active' => true
        ]);
        
        $this->repository->save($alert1);
        
        $activeAlerts = $this->repository->findActive();
        $this->engine->loadAlertsFromRepository($activeAlerts);
        
        $initialCount = count($this->engine->getActiveAlerts(1));
        $this->assertEquals(1, $initialCount);
        
        // Add another alert and reload
        $alert2 = new AlertModel([
            'user_id' => 1,
            'name' => 'Alert 2',
            'symbol' => 'GOOGL',
            'condition_type' => 'price_above',
            'threshold' => 2000.0,
            'email' => 'user@example.com',
            'active' => true
        ]);
        
        $this->repository->save($alert2);
        
        $activeAlerts = $this->repository->findActive();
        $this->engine->loadAlertsFromRepository($activeAlerts, true);  // Replace existing
        
        $newCount = count($this->engine->getActiveAlerts(1));
        $this->assertEquals(2, $newCount);
    }
    
    /**
     * Create alerts table in test database
     */
    private function createAlertsTable(): void
    {
        $pdo = $this->connection->getPDO();
        
        $pdo->exec("
            CREATE TABLE alerts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                name VARCHAR(255) NOT NULL,
                symbol VARCHAR(10),
                condition_type VARCHAR(50) NOT NULL,
                threshold DECIMAL(15, 4) NOT NULL,
                email VARCHAR(255),
                throttle_minutes INTEGER DEFAULT 0,
                active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME
            )
        ");
    }
}
