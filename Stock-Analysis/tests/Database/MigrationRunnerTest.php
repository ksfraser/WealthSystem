<?php

declare(strict_types=1);

namespace Tests\Database;

use App\Database\MigrationRunner;
use App\Database\Connection;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

/**
 * MigrationRunner Test Suite
 * 
 * Tests database migration system including:
 * - Migration tracking (version management)
 * - Up migrations (schema updates)
 * - Down migrations (rollback)
 * - Pending migration detection
 * - Migration history
 * 
 * @package Tests\Database
 */
class MigrationRunnerTest extends TestCase
{
    private MigrationRunner $runner;
    private PDO $pdo;
    
    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $connection = $this->createMock(Connection::class);
        $connection->method('getPDO')->willReturn($this->pdo);
        
        $this->runner = new MigrationRunner($connection);
    }
    
    /**
     * @test
     */
    public function itCreatesMigrationsTable(): void
    {
        $this->runner->initialize();
        
        // Check if migrations table exists
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='migrations'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($result);
        $this->assertEquals('migrations', $result['name']);
    }
    
    /**
     * @test
     */
    public function itTracksExecutedMigrations(): void
    {
        $this->runner->initialize();
        $this->runner->recordMigration('2024_12_05_create_alerts_table', 1);
        
        $executed = $this->runner->getExecutedMigrations();
        
        $this->assertContains('2024_12_05_create_alerts_table', $executed);
    }
    
    /**
     * @test
     */
    public function itReturnsBatchNumber(): void
    {
        $this->runner->initialize();
        $this->runner->recordMigration('2024_12_05_create_alerts_table', 1);
        
        $batch = $this->runner->getLastBatchNumber();
        
        $this->assertEquals(1, $batch);
    }
    
    /**
     * @test
     */
    public function itIdentifiesPendingMigrations(): void
    {
        $this->runner->initialize();
        $this->runner->recordMigration('2024_12_05_create_alerts_table', 1);
        
        $allMigrations = [
            '2024_12_05_create_alerts_table',
            '2024_12_05_create_alert_logs_table',
            '2024_12_06_add_throttle_to_alerts'
        ];
        
        $pending = $this->runner->getPendingMigrations($allMigrations);
        
        $this->assertCount(2, $pending);
        $this->assertContains('2024_12_05_create_alert_logs_table', $pending);
        $this->assertContains('2024_12_06_add_throttle_to_alerts', $pending);
    }
    
    /**
     * @test
     */
    public function itRunsUpMigration(): void
    {
        $this->runner->initialize();
        
        $migration = new class {
            public function up(PDO $pdo): void
            {
                $pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
            }
            
            public function down(PDO $pdo): void
            {
                $pdo->exec('DROP TABLE test_table');
            }
        };
        
        $this->runner->runUp('2024_12_05_test_migration', $migration);
        
        // Verify table was created
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='test_table'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($result);
        $this->assertEquals('test_table', $result['name']);
    }
    
    /**
     * @test
     */
    public function itRunsDownMigration(): void
    {
        $this->runner->initialize();
        
        // Create table first
        $this->pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
        $this->runner->recordMigration('2024_12_05_test_migration', 1);
        
        $migration = new class {
            public function up(PDO $pdo): void
            {
                $pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)');
            }
            
            public function down(PDO $pdo): void
            {
                $pdo->exec('DROP TABLE test_table');
            }
        };
        
        $this->runner->runDown('2024_12_05_test_migration', $migration);
        
        // Verify table was dropped
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='test_table'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertFalse($result);
    }
    
    /**
     * @test
     */
    public function itRollsBackLastBatch(): void
    {
        $this->runner->initialize();
        
        // Create two tables in same batch
        $this->pdo->exec('CREATE TABLE table1 (id INTEGER PRIMARY KEY)');
        $this->pdo->exec('CREATE TABLE table2 (id INTEGER PRIMARY KEY)');
        $this->runner->recordMigration('2024_12_05_create_table1', 1);
        $this->runner->recordMigration('2024_12_05_create_table2', 1);
        
        $migrations = $this->runner->getLastBatchMigrations();
        
        $this->assertCount(2, $migrations);
        $this->assertEquals('2024_12_05_create_table2', $migrations[0]['migration']);
        $this->assertEquals('2024_12_05_create_table1', $migrations[1]['migration']);
    }
    
    /**
     * @test
     */
    public function itHandlesTransactionsForMigrations(): void
    {
        $this->runner->initialize();
        
        $migration = new class {
            public function up(PDO $pdo): void
            {
                $pdo->exec('CREATE TABLE test_table (id INTEGER PRIMARY KEY)');
                // Simulate error after table creation
                throw new \RuntimeException('Migration failed');
            }
            
            public function down(PDO $pdo): void
            {
                $pdo->exec('DROP TABLE test_table');
            }
        };
        
        try {
            $this->runner->runUp('2024_12_05_test_migration', $migration);
            $this->fail('Expected RuntimeException to be thrown');
        } catch (\RuntimeException $e) {
            // Expected exception
        }
        
        // Verify table was NOT created (transaction rolled back)
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='test_table'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertFalse($result);
    }
    
    /**
     * @test
     */
    public function itProvidesMigrationStatus(): void
    {
        $this->runner->initialize();
        $this->runner->recordMigration('2024_12_05_create_alerts_table', 1);
        $this->runner->recordMigration('2024_12_05_create_alert_logs_table', 1);
        
        $status = $this->runner->getStatus([
            '2024_12_05_create_alerts_table',
            '2024_12_05_create_alert_logs_table',
            '2024_12_06_add_throttle_to_alerts'
        ]);
        
        $this->assertCount(3, $status);
        $this->assertEquals('Executed', $status['2024_12_05_create_alerts_table']);
        $this->assertEquals('Executed', $status['2024_12_05_create_alert_logs_table']);
        $this->assertEquals('Pending', $status['2024_12_06_add_throttle_to_alerts']);
    }
    
    /**
     * @test
     */
    public function itOrdersMigrationsByFilename(): void
    {
        $migrations = [
            '2024_12_06_add_throttle_to_alerts',
            '2024_12_05_create_alerts_table',
            '2024_12_05_create_alert_logs_table'
        ];
        
        $ordered = $this->runner->orderMigrations($migrations);
        
        // Alphabetical order: alert_logs comes before alerts_table
        $this->assertEquals([
            '2024_12_05_create_alert_logs_table',
            '2024_12_05_create_alerts_table',
            '2024_12_06_add_throttle_to_alerts'
        ], $ordered);
    }
    
    /**
     * @test
     */
    public function itRemovesMigrationRecord(): void
    {
        $this->runner->initialize();
        $this->runner->recordMigration('2024_12_05_create_alerts_table', 1);
        
        $this->runner->removeMigration('2024_12_05_create_alerts_table');
        
        $executed = $this->runner->getExecutedMigrations();
        $this->assertNotContains('2024_12_05_create_alerts_table', $executed);
    }
    
    /**
     * @test
     */
    public function itResetsMigrations(): void
    {
        $this->runner->initialize();
        $this->runner->recordMigration('2024_12_05_create_alerts_table', 1);
        $this->runner->recordMigration('2024_12_05_create_alert_logs_table', 2);
        
        $this->runner->reset();
        
        $executed = $this->runner->getExecutedMigrations();
        $this->assertEmpty($executed);
    }
    
    /**
     * @test
     */
    public function itRecordsMigrationExecutionTime(): void
    {
        $this->runner->initialize();
        $this->runner->recordMigration('2024_12_05_create_alerts_table', 1);
        
        $history = $this->runner->getMigrationHistory();
        
        $this->assertCount(1, $history);
        $this->assertArrayHasKey('executed_at', $history[0]);
        $this->assertNotEmpty($history[0]['executed_at']);
    }
}
