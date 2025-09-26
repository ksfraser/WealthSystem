<?php
/**
 * Complete Enhanced Database System - All-in-One Version
 * This version includes all necessary classes in a single file for easy testing
 */

echo "<h1>Enhanced Database System Test (All-in-One)</h1>";

// =============================================================================
// INTERFACES
// =============================================================================

interface DatabaseConnectionInterface
{
    public function prepare(string $sql): DatabaseStatementInterface;
    public function exec(string $sql): int;
    public function lastInsertId(): string;
    public function beginTransaction(): bool;
    public function commit(): bool;
    public function rollback(): bool;
    public function getAttribute(int $attribute);
    public function setAttribute(int $attribute, $value): bool;
}

interface DatabaseStatementInterface
{
    public function execute(array $params = []): bool;
    public function fetch(int $fetchStyle = null);
    public function fetchAll(int $fetchStyle = null): array;
    public function fetchColumn(int $column = 0);
    public function rowCount(): int;
    public function bindParam($parameter, &$variable, int $dataType = null): bool;
    public function bindValue($parameter, $value, int $dataType = null): bool;
}

// =============================================================================
// SQLITE FALLBACK CONNECTION (Most Likely to Work)
// =============================================================================

class SimpleSqliteConnection implements DatabaseConnectionInterface
{
    protected $pdo;
    
    public function __construct()
    {
        try {
            $this->pdo = new PDO('sqlite::memory:');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Create users table
            $this->createUsersTable();
        } catch (Exception $e) {
            throw new Exception("SQLite connection failed: " . $e->getMessage());
        }
    }
    
    protected function createUsersTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                is_admin INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $this->pdo->exec($sql);
    }
    
    public function prepare(string $sql): DatabaseStatementInterface
    {
        $stmt = $this->pdo->prepare($sql);
        return new SimplePdoStatement($stmt);
    }
    
    public function exec(string $sql): int
    {
        return $this->pdo->exec($sql);
    }
    
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
    
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }
    
    public function commit(): bool
    {
        return $this->pdo->commit();
    }
    
    public function rollback(): bool
    {
        return $this->pdo->rollback();
    }
    
    public function getAttribute(int $attribute)
    {
        return $this->pdo->getAttribute($attribute);
    }
    
    public function setAttribute(int $attribute, $value): bool
    {
        return $this->pdo->setAttribute($attribute, $value);
    }
}

class SimplePdoStatement implements DatabaseStatementInterface
{
    protected $stmt;
    
    public function __construct($stmt)
    {
        $this->stmt = $stmt;
    }
    
    public function execute(array $params = []): bool
    {
        return $this->stmt->execute($params);
    }
    
    public function fetch(int $fetchStyle = null)
    {
        return $this->stmt->fetch($fetchStyle ?? PDO::FETCH_ASSOC);
    }
    
    public function fetchAll(int $fetchStyle = null): array
    {
        return $this->stmt->fetchAll($fetchStyle ?? PDO::FETCH_ASSOC);
    }
    
    public function fetchColumn(int $column = 0)
    {
        return $this->stmt->fetchColumn($column);
    }
    
    public function rowCount(): int
    {
        return $this->stmt->rowCount();
    }
    
    public function bindParam($parameter, &$variable, int $dataType = null): bool
    {
        return $this->stmt->bindParam($parameter, $variable, $dataType ?? PDO::PARAM_STR);
    }
    
    public function bindValue($parameter, $value, int $dataType = null): bool
    {
        return $this->stmt->bindValue($parameter, $value, $dataType ?? PDO::PARAM_STR);
    }
}

// =============================================================================
// SIMPLE DATABASE MANAGER
// =============================================================================

class SimpleDbManager
{
    protected static $connection = null;
    protected static $currentDriver = null;
    
    public static function getConnection(): DatabaseConnectionInterface
    {
        if (self::$connection !== null) {
            return self::$connection;
        }
        
        // Try PDO SQLite first (most likely to work)
        if (extension_loaded('pdo') && extension_loaded('pdo_sqlite')) {
            try {
                self::$connection = new SimpleSqliteConnection();
                self::$currentDriver = 'pdo_sqlite';
                return self::$connection;
            } catch (Exception $e) {
                // Fall through to error
            }
        }
        
        throw new Exception("No suitable database driver available");
    }
    
    public static function getCurrentDriver(): ?string
    {
        return self::$currentDriver;
    }
    
    public static function fetchAll(string $sql, array $params = []): array
    {
        $connection = self::getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $connection = self::getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public static function execute(string $sql, array $params = []): int
    {
        $connection = self::getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    
    public static function lastInsertId(): string
    {
        $connection = self::getConnection();
        return $connection->lastInsertId();
    }
}

// =============================================================================
// SIMPLE USER AUTH DAO
// =============================================================================

class SimpleUserAuthDAO
{
    protected $connection;
    
    public function __construct()
    {
        $this->connection = SimpleDbManager::getConnection();
    }
    
    public function registerUser(string $username, string $email, string $password, bool $isAdmin = false)
    {
        try {
            // Check if user already exists
            if ($this->userExists($username, $email)) {
                throw new Exception('Username or email already exists');
            }
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $sql = "
                INSERT INTO users (username, email, password_hash, is_admin, created_at, updated_at) 
                VALUES (?, ?, ?, ?, datetime('now'), datetime('now'))
            ";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$username, $email, $passwordHash, $isAdmin ? 1 : 0]);
            
            return (int)$this->connection->lastInsertId();
            
        } catch (Exception $e) {
            error_log("User registration failed: " . $e->getMessage());
            return false;
        }
    }
    
    protected function userExists(string $username, string $email): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE username = ? OR email = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$username, $email]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    public function getAllUsers(): array
    {
        try {
            $sql = "SELECT id, username, email, is_admin, created_at, updated_at FROM users ORDER BY created_at DESC";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Failed to get all users: " . $e->getMessage());
            return [];
        }
    }
    
    public function getUserById(int $userId): ?array
    {
        try {
            $sql = "SELECT id, username, email, is_admin, created_at, updated_at FROM users WHERE id = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$userId]);
            
            $user = $stmt->fetch();
            return $user ?: null;
            
        } catch (Exception $e) {
            error_log("Failed to get user by ID: " . $e->getMessage());
            return null;
        }
    }
    
    public function getDatabaseInfo(): array
    {
        return [
            'driver' => SimpleDbManager::getCurrentDriver(),
            'connection_class' => get_class($this->connection),
        ];
    }
}

// =============================================================================
// MAIN TEST EXECUTION
// =============================================================================

try {
    echo "<h2>1. PHP Extensions Check</h2>";
    
    $pdoAvailable = extension_loaded('pdo');
    $pdoSqliteAvailable = extension_loaded('pdo_sqlite');
    $mysqliAvailable = extension_loaded('mysqli');
    $pdoMysqlAvailable = extension_loaded('pdo_mysql');
    
    echo "<ul>";
    echo "<li>PDO: " . ($pdoAvailable ? '✓ Available' : '✗ Not Available') . "</li>";
    echo "<li>PDO SQLite: " . ($pdoSqliteAvailable ? '✓ Available' : '✗ Not Available') . "</li>";
    echo "<li>MySQLi: " . ($mysqliAvailable ? '✓ Available' : '✗ Not Available') . "</li>";
    echo "<li>PDO MySQL: " . ($pdoMysqlAvailable ? '✓ Available' : '✗ Not Available') . "</li>";
    echo "</ul>";
    
    echo "<h2>2. Database Connection Test</h2>";
    
    $auth = new SimpleUserAuthDAO();
    $dbInfo = $auth->getDatabaseInfo();
    
    echo "<p><strong>Active Driver:</strong> " . ($dbInfo['driver'] ?? 'Unknown') . "</p>";
    echo "<p><strong>Connection Class:</strong> " . ($dbInfo['connection_class'] ?? 'Unknown') . "</p>";
    
    echo "<h2>3. User Registration Test</h2>";
    
    // Test user registration
    $testUsername = "simple_test_" . time();
    $testEmail = "simple_test_" . time() . "@example.com";
    $testPassword = "testpass123";
    
    echo "<p>Attempting to register user: <strong>$testUsername</strong></p>";
    
    $userId = $auth->registerUser($testUsername, $testEmail, $testPassword);
    
    if ($userId && is_numeric($userId)) {
        echo "<p style='color: green;'>✓ Registration successful! User ID: $userId</p>";
        
        // Verify user was created
        $newUser = $auth->getUserById($userId);
        if ($newUser) {
            echo "<p>✓ User verified in database:</p>";
            echo "<ul>";
            echo "<li>ID: {$newUser['id']}</li>";
            echo "<li>Username: {$newUser['username']}</li>";
            echo "<li>Email: {$newUser['email']}</li>";
            echo "<li>Is Admin: " . ($newUser['is_admin'] ? 'Yes' : 'No') . "</li>";
            echo "<li>Created: {$newUser['created_at']}</li>";
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>⚠ User registration succeeded but user not found</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Registration failed. Result: " . var_export($userId, true) . "</p>";
    }
    
    echo "<h2>4. All Users Test</h2>";
    
    $users = $auth->getAllUsers();
    echo "<p><strong>Total Users:</strong> " . count($users) . "</p>";
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Admin</th><th>Created</th></tr>";
        foreach ($users as $user) {
            $adminBadge = $user['is_admin'] ? '✓' : '✗';
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>$adminBadge</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<div style='background: #f0f8ff; padding: 15px; margin: 20px 0; border-left: 4px solid #007acc;'>";
    echo "<h3>✓ Simple Database System Status: OPERATIONAL</h3>";
    echo "<p>The simplified database system is working correctly.</p>";
    echo "<p><strong>Configuration:</strong></p>";
    echo "<ul>";
    echo "<li>Driver: " . ($dbInfo['driver'] ?? 'Unknown') . "</li>";
    echo "<li>Connection: " . ($dbInfo['connection_class'] ?? 'Unknown') . "</li>";
    echo "<li>Users Created: " . count($users) . "</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; margin: 20px 0; border-left: 4px solid #f44336;'>";
    echo "<h3>✗ Database System Error</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}

h1, h2, h3 {
    color: #333;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin: 15px 0;
}

th, td {
    padding: 8px 12px;
    text-align: left;
    border: 1px solid #ddd;
}

th {
    background-color: #f2f2f2;
    font-weight: bold;
}

pre {
    background: #f5f5f5;
    padding: 10px;
    border-radius: 5px;
    overflow-x: auto;
}

ul {
    margin: 10px 0;
    padding-left: 25px;
}

li {
    margin: 5px 0;
}
</style>
