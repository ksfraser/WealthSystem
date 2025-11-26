<?php

namespace Ksfraser\Database\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\Database\EnhancedDbManager;
use Ksfraser\Database\DatabaseConnectionInterface;
use Ksfraser\Database\PdoSqliteConnection;

/**
 * Test suite for EnhancedDbManager class
 * 
 * Tests the database manager's ability to:
 * - Load configurations from multiple sources
 * - Automatically select appropriate database drivers
 * - Provide fallback mechanisms
 * - Manage connections efficiently
 */
class EnhancedDbManagerTest extends TestCase
{
    /** @var string */
    private $testConfigDir;
    /** @var string */
    private $testConfigFile;

    protected function setUp(): void
    {
        // Reset the manager state before each test
        EnhancedDbManager::resetConnection();
        
        // Create temporary test configuration
        $this->testConfigDir = sys_get_temp_dir() . '/ksfraser_db_test_' . uniqid();
        mkdir($this->testConfigDir, 0777, true);
        
        $this->testConfigFile = $this->testConfigDir . '/db_config.ini';
        
        // Create test configuration file
        $testConfig = <<<INI
[database]
host = localhost
username = test_user
password = test_pass
database = test_db
port = 3306
charset = utf8mb4
INI;
        file_put_contents($this->testConfigFile, $testConfig);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (file_exists($this->testConfigFile)) {
            unlink($this->testConfigFile);
        }
        if (is_dir($this->testConfigDir)) {
            rmdir($this->testConfigDir);
        }
        
        // Reset the manager state after each test
        EnhancedDbManager::resetConnection();
    }

    public function testGetConnection()
    {
        // Test that we can get a connection (should fallback to SQLite)
        $connection = EnhancedDbManager::getConnection();
        
        $this->assertInstanceOf(DatabaseConnectionInterface::class, $connection);
        $this->assertInstanceOf(PdoSqliteConnection::class, $connection);
    }

    public function testConnectionSingleton()
    {
        // Test that connections are reused (singleton pattern)
        $connection1 = EnhancedDbManager::getConnection();
        $connection2 = EnhancedDbManager::getConnection();
        
        $this->assertSame($connection1, $connection2);
    }

    public function testGetCurrentDriver()
    {
        // After getting a connection, we should have a current driver
        EnhancedDbManager::getConnection();
        $driver = EnhancedDbManager::getCurrentDriver();
        
        $this->assertNotNull($driver);
        $this->assertIsString($driver);
        $this->assertEquals('pdo_sqlite', $driver); // Should fallback to SQLite in test environment
    }

    public function testResetConnection()
    {
        // Get a connection
        $connection1 = EnhancedDbManager::getConnection();
        
        // Reset and get another
        EnhancedDbManager::resetConnection();
        $connection2 = EnhancedDbManager::getConnection();
        
        // They should be different instances
        $this->assertNotSame($connection1, $connection2);
    }

    public function testFetchAllHelper()
    {
        // Test the fetchAll helper method
        $results = EnhancedDbManager::fetchAll("SELECT 1 as test");
        
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals(1, $results[0]['test']);
    }

    public function testFetchOneHelper()
    {
        // Test the fetchOne helper method
        $result = EnhancedDbManager::fetchOne("SELECT 1 as test, 'hello' as message");
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['test']);
        $this->assertEquals('hello', $result['message']);
    }

    public function testFetchValueHelper()
    {
        // Test the fetchValue helper method
        $value = EnhancedDbManager::fetchValue("SELECT 42");
        
        $this->assertEquals(42, $value);
    }

    public function testExecuteHelper()
    {
        // Test the execute helper method with a simple operation
        $rowCount = EnhancedDbManager::execute("SELECT 1");
        
        $this->assertIsInt($rowCount);
        $this->assertGreaterThanOrEqual(0, $rowCount);
    }

    public function testTransactionMethods()
    {
        // Test transaction methods
        $beginResult = EnhancedDbManager::beginTransaction();
        $this->assertTrue($beginResult);
        
        $rollbackResult = EnhancedDbManager::rollback();
        $this->assertTrue($rollbackResult);
        
        // Test commit
        $beginResult = EnhancedDbManager::beginTransaction();
        $this->assertTrue($beginResult);
        
        $commitResult = EnhancedDbManager::commit();
        $this->assertTrue($commitResult);
    }

    public function testParameterizedQueries()
    {
        // Test parameterized queries
        $results = EnhancedDbManager::fetchAll(
            "SELECT ? as param1, ? as param2", 
            ['test', 123]
        );
        
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('test', $results[0]['param1']);
        $this->assertEquals(123, $results[0]['param2']);
    }

    public function testGetConfigWithEnvironmentFallback()
    {
        // Set environment variables
        $_ENV['DB_HOST'] = 'env_test_host';
        $_ENV['DB_USERNAME'] = 'env_test_user';
        $_ENV['DB_DATABASE'] = 'env_test_db';
        
        // Reset connection to force config reload
        EnhancedDbManager::resetConnection();
        
        $config = EnhancedDbManager::getConfig();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('host', $config);
        $this->assertArrayHasKey('username', $config);
        $this->assertArrayHasKey('database', $config);
        
        // Clean up environment variables
        unset($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_DATABASE']);
    }

    public function testConfigWithDefaultValues()
    {
        // Test that config has sensible defaults
        $config = EnhancedDbManager::getConfig();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('host', $config);
        $this->assertArrayHasKey('port', $config);
        $this->assertArrayHasKey('username', $config);
        $this->assertArrayHasKey('password', $config);
        $this->assertArrayHasKey('database', $config);
        $this->assertArrayHasKey('charset', $config);
        
        // Test defaults
        $this->assertEquals('localhost', $config['host']);
        $this->assertEquals(3306, $config['port']);
        $this->assertEquals('utf8mb4', $config['charset']);
    }

    public function testSqliteConnection()
    {
        // Ensure we can work with SQLite connection
        $connection = EnhancedDbManager::getConnection();
        
        // Try to create a simple table and insert data
        $connection->exec("CREATE TABLE IF NOT EXISTS test_table (id INTEGER PRIMARY KEY, name TEXT)");
        
        $stmt = $connection->prepare("INSERT INTO test_table (name) VALUES (?)");
        $result = $stmt->execute(['test_name']);
        
        $this->assertTrue($result);
        
        // Verify the data was inserted
        $results = EnhancedDbManager::fetchAll("SELECT * FROM test_table WHERE name = ?", ['test_name']);
        $this->assertCount(1, $results);
        $this->assertEquals('test_name', $results[0]['name']);
    }

    public function testLastInsertId()
    {
        // Test last insert ID functionality
        $connection = EnhancedDbManager::getConnection();
        $connection->exec("CREATE TABLE IF NOT EXISTS test_auto_increment (id INTEGER PRIMARY KEY, value TEXT)");
        
        EnhancedDbManager::execute("INSERT INTO test_auto_increment (value) VALUES (?)", ['test_value']);
        
        $lastId = EnhancedDbManager::lastInsertId();
        $this->assertIsString($lastId);
        $this->assertGreaterThan(0, (int)$lastId);
    }
}
