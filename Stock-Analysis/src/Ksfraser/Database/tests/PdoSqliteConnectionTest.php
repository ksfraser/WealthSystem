<?php

namespace Ksfraser\Database\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\Database\PdoSqliteConnection;
use Ksfraser\Database\DatabaseConnectionInterface;
use Ksfraser\Database\DatabaseStatementInterface;

/**
 * Test suite for PdoSqliteConnection class
 */
class PdoSqliteConnectionTest extends TestCase
{
    /** @var PdoSqliteConnection */
    private $connection;

    protected function setUp(): void
    {
        $this->connection = new PdoSqliteConnection();
    }

    protected function tearDown(): void
    {
        $this->connection = null;
    }

    public function testImplementsInterface()
    {
        $this->assertInstanceOf(DatabaseConnectionInterface::class, $this->connection);
    }

    public function testPrepareStatement()
    {
        $stmt = $this->connection->prepare("SELECT 1 as test");
        $this->assertInstanceOf(DatabaseStatementInterface::class, $stmt);
    }

    public function testExecuteStatement()
    {
        $stmt = $this->connection->prepare("SELECT ? as test");
        $result = $stmt->execute([42]);
        $this->assertTrue($result);
        
        $row = $stmt->fetch();
        $this->assertEquals(42, $row['test']);
    }

    public function testFetchAll()
    {
        $stmt = $this->connection->prepare("SELECT 1 as first UNION SELECT 2 as first");
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertEquals(1, $results[0]['first']);
        $this->assertEquals(2, $results[1]['first']);
    }

    public function testFetchColumn()
    {
        $stmt = $this->connection->prepare("SELECT 42");
        $stmt->execute();
        $value = $stmt->fetchColumn();
        
        $this->assertEquals(42, $value);
    }

    public function testRowCount()
    {
        // Insert some data first
        $this->connection->exec("CREATE TABLE test_table (id INTEGER PRIMARY KEY, name TEXT)");
        
        $stmt = $this->connection->prepare("INSERT INTO test_table (name) VALUES (?)");
        $stmt->execute(['test']);
        
        $this->assertEquals(1, $stmt->rowCount());
    }

    public function testExec()
    {
        $result = $this->connection->exec("CREATE TABLE test_exec (id INTEGER PRIMARY KEY)");
        $this->assertIsInt($result);
        $this->assertEquals(0, $result); // CREATE TABLE returns 0 affected rows
    }

    public function testLastInsertId()
    {
        $this->connection->exec("CREATE TABLE test_insert (id INTEGER PRIMARY KEY, value TEXT)");
        $this->connection->exec("INSERT INTO test_insert (value) VALUES ('test')");
        
        $lastId = $this->connection->lastInsertId();
        $this->assertEquals('1', $lastId);
    }

    public function testTransactions()
    {
        $this->connection->exec("CREATE TABLE test_transaction (id INTEGER PRIMARY KEY, value TEXT)");
        
        // Test successful transaction
        $this->assertTrue($this->connection->beginTransaction());
        $this->connection->exec("INSERT INTO test_transaction (value) VALUES ('test1')");
        $this->assertTrue($this->connection->commit());
        
        // Verify data was committed
        $stmt = $this->connection->prepare("SELECT COUNT(*) as count FROM test_transaction");
        $stmt->execute();
        $result = $stmt->fetch();
        $this->assertEquals(1, $result['count']);
        
        // Test rollback
        $this->assertTrue($this->connection->beginTransaction());
        $this->connection->exec("INSERT INTO test_transaction (value) VALUES ('test2')");
        $this->assertTrue($this->connection->rollback());
        
        // Verify data was rolled back
        $stmt = $this->connection->prepare("SELECT COUNT(*) as count FROM test_transaction");
        $stmt->execute();
        $result = $stmt->fetch();
        $this->assertEquals(1, $result['count']); // Still only 1 record
    }

    public function testBindParam()
    {
        $this->connection->exec("CREATE TABLE test_bind (id INTEGER PRIMARY KEY, value TEXT)");
        
        $stmt = $this->connection->prepare("INSERT INTO test_bind (value) VALUES (?)");
        $testValue = 'bound_value';
        $result = $stmt->bindParam(1, $testValue);
        $this->assertTrue($result);
        
        $stmt->execute();
        
        // Verify the data was inserted
        $selectStmt = $this->connection->prepare("SELECT value FROM test_bind WHERE id = 1");
        $selectStmt->execute();
        $row = $selectStmt->fetch();
        $this->assertEquals('bound_value', $row['value']);
    }

    public function testBindValue()
    {
        $this->connection->exec("CREATE TABLE test_bind_value (id INTEGER PRIMARY KEY, value TEXT)");
        
        $stmt = $this->connection->prepare("INSERT INTO test_bind_value (value) VALUES (?)");
        $result = $stmt->bindValue(1, 'test_value');
        $this->assertTrue($result);
        
        $stmt->execute();
        
        // Verify the data was inserted
        $selectStmt = $this->connection->prepare("SELECT value FROM test_bind_value WHERE id = 1");
        $selectStmt->execute();
        $row = $selectStmt->fetch();
        $this->assertEquals('test_value', $row['value']);
    }

    public function testAttributes()
    {
        // Test setting and getting attributes
        $result = $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->assertTrue($result);
        
        $value = $this->connection->getAttribute(\PDO::ATTR_ERRMODE);
        $this->assertEquals(\PDO::ERRMODE_EXCEPTION, $value);
    }

    public function testUsersTableCreation()
    {
        // PdoSqliteConnection should automatically create users table
        $stmt = $this->connection->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        $this->assertNotFalse($result);
        $this->assertEquals('users', $result['name']);
    }

    public function testUsersTableStructure()
    {
        // Test that users table has the expected columns
        $stmt = $this->connection->prepare("PRAGMA table_info(users)");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        $this->assertGreaterThan(0, count($columns));
        
        $columnNames = array_column($columns, 'name');
        $this->assertContains('id', $columnNames);
        $this->assertContains('username', $columnNames);
        $this->assertContains('password_hash', $columnNames);
        $this->assertContains('email', $columnNames);
        $this->assertContains('created_at', $columnNames);
    }

    public function testCRUDOperations()
    {
        // Test Create
        $insertStmt = $this->connection->prepare(
            "INSERT INTO users (username, password_hash, email) VALUES (?, ?, ?)"
        );
        $result = $insertStmt->execute(['testuser', 'hashed_password', 'test@example.com']);
        $this->assertTrue($result);
        
        $userId = $this->connection->lastInsertId();
        $this->assertGreaterThan(0, (int)$userId);
        
        // Test Read
        $selectStmt = $this->connection->prepare("SELECT * FROM users WHERE id = ?");
        $selectStmt->execute([$userId]);
        $user = $selectStmt->fetch();
        
        $this->assertIsArray($user);
        $this->assertEquals('testuser', $user['username']);
        $this->assertEquals('hashed_password', $user['password_hash']);
        $this->assertEquals('test@example.com', $user['email']);
        
        // Test Update
        $updateStmt = $this->connection->prepare(
            "UPDATE users SET email = ? WHERE id = ?"
        );
        $result = $updateStmt->execute(['updated@example.com', $userId]);
        $this->assertTrue($result);
        $this->assertEquals(1, $updateStmt->rowCount());
        
        // Verify update
        $selectStmt->execute([$userId]);
        $updatedUser = $selectStmt->fetch();
        $this->assertEquals('updated@example.com', $updatedUser['email']);
        
        // Test Delete
        $deleteStmt = $this->connection->prepare("DELETE FROM users WHERE id = ?");
        $result = $deleteStmt->execute([$userId]);
        $this->assertTrue($result);
        $this->assertEquals(1, $deleteStmt->rowCount());
        
        // Verify deletion
        $selectStmt->execute([$userId]);
        $deletedUser = $selectStmt->fetch();
        $this->assertFalse($deletedUser);
    }
}
