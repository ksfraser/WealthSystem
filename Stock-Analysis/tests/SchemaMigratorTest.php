<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../web_ui/SchemaMigrator.php';

class SchemaMigratorTest extends TestCase
{
    private $pdo;
    private $tempSchemaDir;
    private $migrator;

    protected function setUp(): void
    {
        // Use mock PDO for testing
        require_once __DIR__ . '/MockPDO.php';
        $this->pdo = new MockPDO();
        
        // Create temporary schema directory
        $this->tempSchemaDir = sys_get_temp_dir() . '/schema_test_' . uniqid();
        mkdir($this->tempSchemaDir, 0777, true);
        
        $this->migrator = new SchemaMigrator($this->pdo, $this->tempSchemaDir);
    }

    protected function tearDown(): void
    {
        // Clean up test tables
        try {
            $this->pdo->exec("DROP TABLE IF EXISTS test_table");
            $this->pdo->exec("DROP TABLE IF EXISTS first");
            $this->pdo->exec("DROP TABLE IF EXISTS second");
            $this->pdo->exec("DROP TABLE IF EXISTS third");
            $this->pdo->exec("DROP TABLE IF EXISTS migration_order");
        } catch (Exception $e) {
            // Ignore cleanup errors
        }
        
        // Clean up temporary directory
        $files = glob($this->tempSchemaDir . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->tempSchemaDir);
    }

    public function testMigrationsTableCreated()
    {
        // Check that schema_migrations table exists
        $stmt = $this->pdo->query("SHOW TABLES LIKE 'schema_migrations'");
        $result = $stmt->fetch();
        $this->assertNotFalse($result);
    }

    public function testMigrateEmptyDirectory()
    {
        // Should complete without error even with no SQL files
        $this->migrator->migrate();
        
        // Check migrations table exists but is empty
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM schema_migrations");
        $result = $stmt->fetch();
        $this->assertEquals(0, $result['count']);
    }

    public function testMigrateSingleFile()
    {
        // Create a test migration file
        $sqlContent = "CREATE TABLE IF NOT EXISTS test_table (id INT PRIMARY KEY, name TEXT);";
        file_put_contents($this->tempSchemaDir . '/001_create_test.sql', $sqlContent);
        
        $this->migrator->migrate();
        
        // Check table was created
        $stmt = $this->pdo->query("SHOW TABLES LIKE 'test_table'");
        $result = $stmt->fetch();
        $this->assertNotFalse($result);
        
        // Check migration was recorded
        $stmt = $this->pdo->query("SELECT filename FROM schema_migrations WHERE filename = '001_create_test.sql'");
        $result = $stmt->fetch();
        $this->assertNotFalse($result);
    }

    public function testMigrateMultipleFiles()
    {
        // Create multiple migration files
        file_put_contents($this->tempSchemaDir . '/001_first.sql', "CREATE TABLE IF NOT EXISTS first (id INT PRIMARY KEY);");
        file_put_contents($this->tempSchemaDir . '/002_second.sql', "CREATE TABLE IF NOT EXISTS second (id INT PRIMARY KEY);");
        file_put_contents($this->tempSchemaDir . '/003_third.sql', "CREATE TABLE IF NOT EXISTS third (id INT PRIMARY KEY);");
        
        $this->migrator->migrate();
        
        // Check all tables were created
        $tables = ['first', 'second', 'third'];
        foreach ($tables as $table) {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
            $result = $stmt->fetch();
            $this->assertNotFalse($result, "Table $table should exist");
        }
        
        // Check all migrations were recorded
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM schema_migrations");
        $result = $stmt->fetch();
        $this->assertEquals(3, $result['count']);
    }

    public function testMigrateOnlyNewFiles()
    {
        // Create and run first migration
        file_put_contents($this->tempSchemaDir . '/001_first.sql', "CREATE TABLE IF NOT EXISTS first (id INT PRIMARY KEY);");
        $this->migrator->migrate();
        
        // Add second migration
        file_put_contents($this->tempSchemaDir . '/002_second.sql', "CREATE TABLE IF NOT EXISTS second (id INT PRIMARY KEY);");
        $this->migrator->migrate();
        
        // Check both tables exist
        $stmt = $this->pdo->query("SHOW TABLES LIKE 'first'");
        $this->assertNotFalse($stmt->fetch());
        
        $stmt = $this->pdo->query("SHOW TABLES LIKE 'second'");
        $this->assertNotFalse($stmt->fetch());
        
        // Check both migrations recorded
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM schema_migrations");
        $result = $stmt->fetch();
        $this->assertEquals(2, $result['count']);
    }

    public function testMigrateFilesInOrder()
    {
        // Create files in reverse order to test sorting
        file_put_contents($this->tempSchemaDir . '/003_third.sql', "INSERT INTO migration_order VALUES ('third');");
        file_put_contents($this->tempSchemaDir . '/001_first.sql', "CREATE TABLE IF NOT EXISTS migration_order (step TEXT);");
        file_put_contents($this->tempSchemaDir . '/002_second.sql', "INSERT INTO migration_order VALUES ('second');");
        
        $this->migrator->migrate();
        
        // Check order of execution
        $stmt = $this->pdo->query("SELECT step FROM migration_order ORDER BY step");
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $this->assertEquals(['second', 'third'], $results);
    }
}
