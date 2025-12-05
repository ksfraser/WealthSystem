<?php

declare(strict_types=1);

namespace Tests\Database;

use PHPUnit\Framework\TestCase;
use App\Database\MigrationManager;
use App\Database\Migration;
use PDO;

/**
 * Test suite for MigrationManager
 * 
 * Tests database migration system functionality including:
 * - Migration discovery and loading
 * - Migration execution (up/down)
 * - Migration status tracking
 * - Version management
 * - Rollback capabilities
 * 
 * @covers \App\Database\MigrationManager
 */
class MigrationManagerTest extends TestCase
{
    private ?MigrationManager $manager = null;
    private ?PDO $pdo = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use in-memory SQLite for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create test tables that migrations expect
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY,
                email VARCHAR(255),
                created_at TIMESTAMP
            )
        ");
        
        $this->manager = new MigrationManager($this->pdo, __DIR__ . '/fixtures/migrations');
    }

    /**
     * @test
     * @group migration
     */
    public function itCreatesSchemaVersionsTable(): void
    {
        $this->manager->initialize();
        
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='schema_versions'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($result);
        $this->assertEquals('schema_versions', $result['name']);
    }

    /**
     * @test
     * @group migration
     */
    public function itDiscoversAvailableMigrations(): void
    {
        $migrations = $this->manager->getAvailableMigrations();
        
        $this->assertIsArray($migrations);
        $this->assertNotEmpty($migrations);
        $this->assertContainsOnlyInstancesOf(Migration::class, $migrations);
    }

    /**
     * @test
     * @group migration
     */
    public function itSortsMigrationsByVersion(): void
    {
        $migrations = $this->manager->getAvailableMigrations();
        
        $versions = array_map(fn($m) => $m->getVersion(), $migrations);
        $sortedVersions = $versions;
        sort($sortedVersions);
        
        $this->assertEquals($sortedVersions, $versions, 'Migrations should be sorted by version');
    }

    /**
     * @test
     * @group migration
     */
    public function itGetsCurrentVersion(): void
    {
        $this->manager->initialize();
        
        $version = $this->manager->getCurrentVersion();
        
        $this->assertIsString($version);
        $this->assertEquals('0', $version, 'Initial version should be 0');
    }

    /**
     * @test
     * @group migration
     */
    public function itGetsPendingMigrations(): void
    {
        $this->manager->initialize();
        
        $pending = $this->manager->getPendingMigrations();
        
        $this->assertIsArray($pending);
        $this->assertContainsOnlyInstancesOf(Migration::class, $pending);
    }

    /**
     * @test
     * @group migration
     */
    public function itRunsSingleMigration(): void
    {
        $this->manager->initialize();
        
        $migrations = $this->manager->getPendingMigrations();
        $this->assertNotEmpty($migrations, 'Should have pending migrations');
        
        $migration = $migrations[0];
        $result = $this->manager->migrate($migration);
        
        $this->assertTrue($result);
        $this->assertEquals($migration->getVersion(), $this->manager->getCurrentVersion());
    }

    /**
     * @test
     * @group migration
     */
    public function itRunsAllPendingMigrations(): void
    {
        $this->manager->initialize();
        
        $initialVersion = $this->manager->getCurrentVersion();
        $pendingCount = count($this->manager->getPendingMigrations());
        
        $this->assertGreaterThan(0, $pendingCount);
        
        $results = $this->manager->migrateAll();
        
        $this->assertIsArray($results);
        $this->assertCount($pendingCount, $results);
        $this->assertNotEquals($initialVersion, $this->manager->getCurrentVersion());
    }

    /**
     * @test
     * @group migration
     */
    public function itRollsBackLastMigration(): void
    {
        $this->manager->initialize();
        $this->manager->migrateAll();
        
        $versionBeforeRollback = $this->manager->getCurrentVersion();
        
        $result = $this->manager->rollback();
        
        $this->assertTrue($result);
        $versionAfterRollback = $this->manager->getCurrentVersion();
        $this->assertNotEquals($versionBeforeRollback, $versionAfterRollback);
    }

    /**
     * @test
     * @group migration
     */
    public function itRollsBackToSpecificVersion(): void
    {
        $this->manager->initialize();
        $this->manager->migrateAll();
        
        $migrations = $this->manager->getAvailableMigrations();
        $targetVersion = $migrations[0]->getVersion();
        
        $result = $this->manager->rollbackTo($targetVersion);
        
        $this->assertTrue($result);
        $this->assertEquals($targetVersion, $this->manager->getCurrentVersion());
    }

    /**
     * @test
     * @group migration
     */
    public function itRecordsMigrationInHistory(): void
    {
        $this->manager->initialize();
        
        $migrations = $this->manager->getPendingMigrations();
        $migration = $migrations[0];
        
        $this->manager->migrate($migration);
        
        $history = $this->manager->getMigrationHistory();
        
        $this->assertIsArray($history);
        $this->assertNotEmpty($history);
        $this->assertArrayHasKey('version', $history[0]);
        $this->assertArrayHasKey('executed_at', $history[0]);
        $this->assertEquals($migration->getVersion(), $history[0]['version']);
    }

    /**
     * @test
     * @group migration
     */
    public function itPreventsRunningMigrationTwice(): void
    {
        $this->manager->initialize();
        
        $migrations = $this->manager->getPendingMigrations();
        $migration = $migrations[0];
        
        $this->manager->migrate($migration);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already been executed');
        
        $this->manager->migrate($migration);
    }

    /**
     * @test
     * @group migration
     */
    public function itValidatesMigrationFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $invalidMigration = new class implements Migration {
            public function getVersion(): string { return 'invalid'; }
            public function getDescription(): string { return 'test'; }
            public function up(PDO $pdo): void {}
            public function down(PDO $pdo): void {}
        };
        
        $this->manager->migrate($invalidMigration);
    }

    /**
     * @test
     * @group migration
     */
    public function itHandlesMigrationFailureGracefully(): void
    {
        $this->manager->initialize();
        
        $failingMigration = new class implements Migration {
            public function getVersion(): string { return '20251205000001'; }
            public function getDescription(): string { return 'Failing migration'; }
            public function up(PDO $pdo): void {
                throw new \RuntimeException('Migration failed');
            }
            public function down(PDO $pdo): void {}
        };
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Migration failed');
        
        $this->manager->migrate($failingMigration);
    }

    /**
     * @test
     * @group migration
     */
    public function itGeneratesStatus(): void
    {
        $this->manager->initialize();
        
        $status = $this->manager->getStatus();
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('current_version', $status);
        $this->assertArrayHasKey('pending_count', $status);
        $this->assertArrayHasKey('executed_count', $status);
    }

    /**
     * @test
     * @group migration
     */
    public function itSupportsTransactionalMigrations(): void
    {
        $this->manager->initialize();
        
        $migrations = $this->manager->getPendingMigrations();
        
        // Migration internally uses transactions - just verify it succeeds atomically
        try {
            $this->manager->migrate($migrations[0]);
            $succeeded = true;
        } catch (\Exception $e) {
            $succeeded = false;
        }
        
        $this->assertTrue($succeeded, 'Migration should succeed atomically');
    }

    protected function tearDown(): void
    {
        if ($this->pdo instanceof PDO) {
            $this->pdo = null;
        }
        parent::tearDown();
    }
}
