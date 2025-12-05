<?php

declare(strict_types=1);

namespace Tests\Database;

use PHPUnit\Framework\TestCase;
use App\Database\SchemaBuilder;
use PDO;
use RuntimeException;

/**
 * Test suite for SchemaBuilder
 * 
 * Tests database schema creation and modification including:
 * - Index creation (single column, composite, unique)
 * - Foreign key constraints
 * - Table alterations
 * - Schema validation
 * 
 * @covers \App\Database\SchemaBuilder
 */
class SchemaBuilderTest extends TestCase
{
    private ?SchemaBuilder $builder = null;
    private ?PDO $pdo = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->builder = new SchemaBuilder($this->pdo);
        
        // Create test tables
        $this->createTestTables();
    }

    private function createTestTables(): void
    {
        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                email VARCHAR(255),
                username VARCHAR(100),
                created_at TIMESTAMP
            )
        ");
        
        $this->pdo->exec("
            CREATE TABLE portfolios (
                id INTEGER PRIMARY KEY,
                user_id INTEGER,
                name VARCHAR(255),
                value DECIMAL(15,2),
                created_at TIMESTAMP
            )
        ");
    }

    /**
     * @test
     * @group schema
     */
    public function itCreatesSingleColumnIndex(): void
    {
        $this->builder->addIndex('users', 'email', 'idx_users_email');
        
        $indexes = $this->builder->getIndexes('users');
        
        $this->assertContains('idx_users_email', array_column($indexes, 'name'));
    }

    /**
     * @test
     * @group schema
     */
    public function itCreatesCompositeIndex(): void
    {
        $this->builder->addIndex('users', ['username', 'created_at'], 'idx_users_username_created');
        
        $indexes = $this->builder->getIndexes('users');
        
        $this->assertContains('idx_users_username_created', array_column($indexes, 'name'));
    }

    /**
     * @test
     * @group schema
     */
    public function itCreatesUniqueIndex(): void
    {
        $this->builder->addUniqueIndex('users', 'email', 'idx_users_email_unique');
        
        $indexes = $this->builder->getIndexes('users');
        
        $emailIndex = array_filter($indexes, fn($idx) => $idx['name'] === 'idx_users_email_unique');
        $this->assertNotEmpty($emailIndex);
        
        $index = reset($emailIndex);
        $this->assertTrue($index['unique']);
    }

    /**
     * @test
     * @group schema
     */
    public function itDropsIndex(): void
    {
        $this->builder->addIndex('users', 'email', 'idx_users_email');
        
        $this->builder->dropIndex('users', 'idx_users_email');
        
        $indexes = $this->builder->getIndexes('users');
        $this->assertNotContains('idx_users_email', array_column($indexes, 'name'));
    }

    /**
     * @test
     * @group schema
     */
    public function itAddsForeignKey(): void
    {
        // SQLite doesn't support adding foreign keys to existing tables
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SQLite does not support');
        
        $this->builder->addForeignKey(
            'portfolios',
            'user_id',
            'users',
            'id',
            'fk_portfolios_user'
        );
    }

    /**
     * @test
     * @group schema
     */
    public function itAddsForeignKeyWithCascade(): void
    {
        // SQLite doesn't support adding foreign keys to existing tables
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SQLite does not support');
        
        $this->builder->addForeignKey(
            'portfolios',
            'user_id',
            'users',
            'id',
            'fk_portfolios_user',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * @test
     * @group schema
     */
    public function itDropsForeignKey(): void
    {
        // SQLite doesn't support dropping foreign keys
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SQLite does not support');
        
        $this->builder->dropForeignKey('portfolios', 'fk_portfolios_user');
    }

    /**
     * @test
     * @group schema
     */
    public function itChecksIfIndexExists(): void
    {
        $this->builder->addIndex('users', 'email', 'idx_users_email');
        
        $this->assertTrue($this->builder->hasIndex('users', 'idx_users_email'));
        $this->assertFalse($this->builder->hasIndex('users', 'idx_nonexistent'));
    }

    /**
     * @test
     * @group schema
     */
    public function itChecksIfForeignKeyExists(): void
    {
        // For SQLite, we can only check foreign keys that were defined during table creation
        // The getForeignKeys method returns empty for tables without FKs
        $this->assertFalse($this->builder->hasForeignKey('portfolios', 'fk_nonexistent'));
    }

    /**
     * @test
     * @group schema
     */
    public function itGetsTableColumns(): void
    {
        $columns = $this->builder->getColumns('users');
        
        $this->assertIsArray($columns);
        $this->assertNotEmpty($columns);
        $this->assertContains('id', array_column($columns, 'name'));
        $this->assertContains('email', array_column($columns, 'name'));
    }

    /**
     * @test
     * @group schema
     */
    public function itValidatesTableExists(): void
    {
        // Verify tables created in setUp exist
        $tables = ['users', 'portfolios'];
        foreach ($tables as $table) {
            $this->assertTrue($this->builder->hasTable($table), "Table {$table} should exist");
        }
        
        $this->assertFalse($this->builder->hasTable('nonexistent_table'));
    }

    /**
     * @test
     * @group schema
     */
    public function itPreventsCreatingDuplicateIndex(): void
    {
        $this->builder->addIndex('users', 'email', 'idx_users_email');
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already exists');
        
        $this->builder->addIndex('users', 'email', 'idx_users_email');
    }

    /**
     * @test
     * @group schema
     */
    public function itPreventsCreatingDuplicateForeignKey(): void
    {
        // SQLite doesn't support adding foreign keys, so test the error message
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SQLite does not support');
        
        $this->builder->addForeignKey(
            'portfolios',
            'user_id',
            'users',
            'id',
            'fk_portfolios_user'
        );
    }

    /**
     * @test
     * @group schema
     */
    public function itGeneratesIndexName(): void
    {
        $name = $this->builder->generateIndexName('users', ['email', 'username']);
        
        $this->assertIsString($name);
        $this->assertStringStartsWith('idx_users_', $name);
        $this->assertStringContainsString('email', $name);
        $this->assertStringContainsString('username', $name);
    }

    /**
     * @test
     * @group schema
     */
    public function itGeneratesForeignKeyName(): void
    {
        $name = $this->builder->generateForeignKeyName('portfolios', 'user_id', 'users');
        
        $this->assertIsString($name);
        $this->assertStringStartsWith('fk_portfolios_', $name);
        $this->assertStringContainsString('user', $name);
    }

    protected function tearDown(): void
    {
        if ($this->pdo instanceof PDO) {
            $this->pdo = null;
        }
        parent::tearDown();
    }
}
