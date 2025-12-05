<?php

declare(strict_types=1);

namespace Tests\Repositories;

use App\Repositories\AlertRepository;
use App\Models\Alert;
use App\Database\Connection;
use PHPUnit\Framework\TestCase;
use PDO;

/**
 * AlertRepository Test Suite
 * 
 * Tests database persistence for alerts including:
 * - CRUD operations (Create, Read, Update, Delete)
 * - Find by user
 * - Find by symbol
 * - Active/inactive filtering
 * - Bulk operations
 * 
 * @package Tests\Repositories
 */
class AlertRepositoryTest extends TestCase
{
    private AlertRepository $repository;
    private PDO $pdo;
    
    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create alerts table
        $this->pdo->exec('
            CREATE TABLE alerts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                symbol TEXT,
                condition_type TEXT NOT NULL,
                threshold REAL NOT NULL,
                email TEXT,
                throttle_minutes INTEGER DEFAULT 0,
                active INTEGER DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT
            )
        ');
        
        $connection = $this->createMock(Connection::class);
        $connection->method('getPDO')->willReturn($this->pdo);
        
        $this->repository = new AlertRepository($connection);
    }
    
    /**
     * @test
     */
    public function itCreatesAlert(): void
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
        
        $savedAlert = $this->repository->save($alert);
        
        $this->assertNotNull($savedAlert->getId());
        $this->assertEquals('AAPL Price Alert', $savedAlert->getName());
        $this->assertEquals('AAPL', $savedAlert->getSymbol());
    }
    
    /**
     * @test
     */
    public function itFindsAlertById(): void
    {
        // Create alert first
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'symbol' => 'GOOGL',
            'condition_type' => 'price_below',
            'threshold' => 100.0,
            'active' => true
        ]);
        
        $savedAlert = $this->repository->save($alert);
        $alertId = $savedAlert->getId();
        
        // Find by ID
        $foundAlert = $this->repository->findById($alertId);
        
        $this->assertNotNull($foundAlert);
        $this->assertEquals($alertId, $foundAlert->getId());
        $this->assertEquals('Test Alert', $foundAlert->getName());
        $this->assertEquals('GOOGL', $foundAlert->getSymbol());
    }
    
    /**
     * @test
     */
    public function itReturnsNullForNonExistentAlert(): void
    {
        $alert = $this->repository->findById(999);
        
        $this->assertNull($alert);
    }
    
    /**
     * @test
     */
    public function itFindsAllAlerts(): void
    {
        // Create multiple alerts
        $this->repository->save(new Alert([
            'user_id' => 1,
            'name' => 'Alert 1',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'active' => true
        ]));
        
        $this->repository->save(new Alert([
            'user_id' => 2,
            'name' => 'Alert 2',
            'symbol' => 'GOOGL',
            'condition_type' => 'price_below',
            'threshold' => 100.0,
            'active' => true
        ]));
        
        $alerts = $this->repository->findAll();
        
        $this->assertCount(2, $alerts);
    }
    
    /**
     * @test
     */
    public function itFindsByUserId(): void
    {
        // Create alerts for different users
        $this->repository->save(new Alert([
            'user_id' => 1,
            'name' => 'User 1 Alert 1',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'active' => true
        ]));
        
        $this->repository->save(new Alert([
            'user_id' => 1,
            'name' => 'User 1 Alert 2',
            'symbol' => 'GOOGL',
            'condition_type' => 'price_below',
            'threshold' => 100.0,
            'active' => true
        ]));
        
        $this->repository->save(new Alert([
            'user_id' => 2,
            'name' => 'User 2 Alert',
            'symbol' => 'MSFT',
            'condition_type' => 'price_above',
            'threshold' => 200.0,
            'active' => true
        ]));
        
        $userAlerts = $this->repository->findByUserId(1);
        
        $this->assertCount(2, $userAlerts);
        foreach ($userAlerts as $alert) {
            $this->assertEquals(1, $alert->getUserId());
        }
    }
    
    /**
     * @test
     */
    public function itFindsBySymbol(): void
    {
        $this->repository->save(new Alert([
            'user_id' => 1,
            'name' => 'AAPL Alert 1',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'active' => true
        ]));
        
        $this->repository->save(new Alert([
            'user_id' => 2,
            'name' => 'AAPL Alert 2',
            'symbol' => 'AAPL',
            'condition_type' => 'price_below',
            'threshold' => 120.0,
            'active' => true
        ]));
        
        $this->repository->save(new Alert([
            'user_id' => 1,
            'name' => 'GOOGL Alert',
            'symbol' => 'GOOGL',
            'condition_type' => 'price_above',
            'threshold' => 100.0,
            'active' => true
        ]));
        
        $symbolAlerts = $this->repository->findBySymbol('AAPL');
        
        $this->assertCount(2, $symbolAlerts);
        foreach ($symbolAlerts as $alert) {
            $this->assertEquals('AAPL', $alert->getSymbol());
        }
    }
    
    /**
     * @test
     */
    public function itFindsActiveAlerts(): void
    {
        $this->repository->save(new Alert([
            'user_id' => 1,
            'name' => 'Active Alert',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'active' => true
        ]));
        
        $this->repository->save(new Alert([
            'user_id' => 1,
            'name' => 'Inactive Alert',
            'symbol' => 'GOOGL',
            'condition_type' => 'price_below',
            'threshold' => 100.0,
            'active' => false
        ]));
        
        $activeAlerts = $this->repository->findActive();
        
        $this->assertCount(1, $activeAlerts);
        $this->assertTrue($activeAlerts[0]->isActive());
    }
    
    /**
     * @test
     */
    public function itUpdatesAlert(): void
    {
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'Original Name',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'active' => true
        ]);
        
        $savedAlert = $this->repository->save($alert);
        $alertId = $savedAlert->getId();
        
        // Update alert
        $savedAlert->setName('Updated Name');
        $savedAlert->setThreshold(175.0);
        
        $updatedAlert = $this->repository->save($savedAlert);
        
        $this->assertEquals($alertId, $updatedAlert->getId());
        $this->assertEquals('Updated Name', $updatedAlert->getName());
        $this->assertEquals(175.0, $updatedAlert->getThreshold());
    }
    
    /**
     * @test
     */
    public function itDeletesAlert(): void
    {
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'active' => true
        ]);
        
        $savedAlert = $this->repository->save($alert);
        $alertId = $savedAlert->getId();
        
        $deleted = $this->repository->delete($alertId);
        
        $this->assertTrue($deleted);
        $this->assertNull($this->repository->findById($alertId));
    }
    
    /**
     * @test
     */
    public function itReturnsFalseWhenDeletingNonExistentAlert(): void
    {
        $deleted = $this->repository->delete(999);
        
        $this->assertFalse($deleted);
    }
    
    /**
     * @test
     */
    public function itActivatesAlert(): void
    {
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'active' => false
        ]);
        
        $savedAlert = $this->repository->save($alert);
        $alertId = $savedAlert->getId();
        
        $this->repository->activate($alertId);
        
        $activatedAlert = $this->repository->findById($alertId);
        $this->assertTrue($activatedAlert->isActive());
    }
    
    /**
     * @test
     */
    public function itDeactivatesAlert(): void
    {
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'active' => true
        ]);
        
        $savedAlert = $this->repository->save($alert);
        $alertId = $savedAlert->getId();
        
        $this->repository->deactivate($alertId);
        
        $deactivatedAlert = $this->repository->findById($alertId);
        $this->assertFalse($deactivatedAlert->isActive());
    }
    
    /**
     * @test
     */
    public function itDeletesByUserId(): void
    {
        $this->repository->save(new Alert([
            'user_id' => 1,
            'name' => 'User 1 Alert 1',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'active' => true
        ]));
        
        $this->repository->save(new Alert([
            'user_id' => 1,
            'name' => 'User 1 Alert 2',
            'symbol' => 'GOOGL',
            'condition_type' => 'price_below',
            'threshold' => 100.0,
            'active' => true
        ]));
        
        $this->repository->save(new Alert([
            'user_id' => 2,
            'name' => 'User 2 Alert',
            'symbol' => 'MSFT',
            'condition_type' => 'price_above',
            'threshold' => 200.0,
            'active' => true
        ]));
        
        $deletedCount = $this->repository->deleteByUserId(1);
        
        $this->assertEquals(2, $deletedCount);
        $this->assertEmpty($this->repository->findByUserId(1));
        $this->assertCount(1, $this->repository->findByUserId(2));
    }
    
    /**
     * @test
     */
    public function itCountsAlerts(): void
    {
        $this->repository->save(new Alert([
            'user_id' => 1,
            'name' => 'Alert 1',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'active' => true
        ]));
        
        $this->repository->save(new Alert([
            'user_id' => 1,
            'name' => 'Alert 2',
            'symbol' => 'GOOGL',
            'condition_type' => 'price_below',
            'threshold' => 100.0,
            'active' => true
        ]));
        
        $count = $this->repository->count();
        
        $this->assertEquals(2, $count);
    }
    
    /**
     * @test
     */
    public function itSetsUpdatedAtOnUpdate(): void
    {
        $alert = new Alert([
            'user_id' => 1,
            'name' => 'Test Alert',
            'symbol' => 'AAPL',
            'condition_type' => 'price_above',
            'threshold' => 150.0,
            'active' => true
        ]);
        
        $savedAlert = $this->repository->save($alert);
        $createdAt = $savedAlert->getCreatedAt();
        
        // Sleep to ensure time difference
        sleep(1);
        
        $savedAlert->setName('Updated Name');
        $updatedAlert = $this->repository->save($savedAlert);
        
        $this->assertEquals($createdAt, $updatedAlert->getCreatedAt());
        $this->assertNotNull($updatedAlert->getUpdatedAt());
        $this->assertGreaterThan($createdAt, $updatedAlert->getUpdatedAt());
    }
}
