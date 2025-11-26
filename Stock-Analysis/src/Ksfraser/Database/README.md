# Ksfraser Database Module

A robust, multi-driver database abstraction layer with automatic fallback support for PHP applications.

## Features

- **Multi-Driver Support**: PDO MySQL, MySQLi, SQLite with automatic fallback
- **Configuration Flexibility**: INI, YAML, and environment variable support
- **Connection Pooling**: Singleton pattern for efficient connection management
- **Transaction Support**: Full transaction management across all drivers
- **Error Handling**: Comprehensive error handling and logging
- **Testing Ready**: Built-in SQLite fallback for testing environments

## Installation

### Manual Installation

Copy the `src/Ksfraser/Database` directory to your project:

```bash
cp -r src/Ksfraser/Database /path/to/your/project/src/Ksfraser/
```

### Composer Installation (when available as separate repository)

```bash
composer require ksfraser/database
```

## Quick Start

```php
<?php
require_once 'src/Ksfraser/Database/EnhancedDbManager.php';

use Ksfraser\Database\EnhancedDbManager;

// Get a database connection (automatic driver selection)
$connection = EnhancedDbManager::getConnection();

// Execute a simple query
$results = EnhancedDbManager::fetchAll("SELECT * FROM users");

// Execute with parameters
$user = EnhancedDbManager::fetchOne("SELECT * FROM users WHERE id = ?", [123]);

// Execute a scalar query
$count = EnhancedDbManager::fetchValue("SELECT COUNT(*) FROM users");

// Execute an update/insert/delete
$affectedRows = EnhancedDbManager::execute("UPDATE users SET email = ? WHERE id = ?", [
    'new@example.com', 
    123
]);
```

## Configuration

### Configuration File (db_config.ini)

```ini
[database]
host = localhost
port = 3306
username = your_username
password = your_password
database = your_database
charset = utf8mb4
```

### Configuration File (db_config.yaml)

```yaml
database:
  host: localhost
  port: 3306
  username: your_username
  password: your_password
  database: your_database
  charset: utf8mb4
```

### Environment Variables

```bash
export DB_HOST=localhost
export DB_PORT=3306
export DB_USERNAME=your_username
export DB_PASSWORD=your_password
export DB_DATABASE=your_database
export DB_CHARSET=utf8mb4
```

## Configuration Search Order

The system searches for configuration in this order:

1. Provided configuration file path
2. `./db_config.ini`
3. `./db_config.yml`
4. `./db_config.yaml`
5. `./config/db_config.ini`
6. `./config/db_config.yml`
7. Environment variables (fallback)

## Driver Priority

The system attempts to connect using drivers in this order:

1. **PDO MySQL** - Primary choice for production environments
2. **MySQLi** - Fallback for when PDO MySQL is not available
3. **SQLite** - Final fallback for testing and development

## API Reference

### EnhancedDbManager

#### Static Methods

##### `getConnection(): DatabaseConnectionInterface`

Returns a database connection using the best available driver.

```php
$connection = EnhancedDbManager::getConnection();
```

##### `getConfig(?string $configFile = null): array`

Loads and returns database configuration.

```php
$config = EnhancedDbManager::getConfig('/path/to/config.ini');
```

##### `getCurrentDriver(): ?string`

Returns the name of the currently active driver.

```php
$driver = EnhancedDbManager::getCurrentDriver(); // 'pdo_mysql', 'mysqli', or 'pdo_sqlite'
```

##### `resetConnection(): void`

Resets the connection (useful for testing).

```php
EnhancedDbManager::resetConnection();
```

##### Helper Methods

```php
// Fetch multiple rows
$users = EnhancedDbManager::fetchAll("SELECT * FROM users WHERE active = ?", [1]);

// Fetch single row
$user = EnhancedDbManager::fetchOne("SELECT * FROM users WHERE id = ?", [123]);

// Fetch single value
$count = EnhancedDbManager::fetchValue("SELECT COUNT(*) FROM users");

// Execute statement (INSERT, UPDATE, DELETE)
$affected = EnhancedDbManager::execute("DELETE FROM users WHERE id = ?", [123]);

// Get last insert ID
$id = EnhancedDbManager::lastInsertId();

// Transaction management
EnhancedDbManager::beginTransaction();
EnhancedDbManager::commit();
EnhancedDbManager::rollback();
```

### DatabaseConnectionInterface

All connection drivers implement this interface:

```php
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
```

### DatabaseStatementInterface

All statement wrappers implement this interface:

```php
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
```

## Connection Classes

### PdoConnection

Wrapper for PDO MySQL connections with enhanced statement handling.

```php
use Ksfraser\Database\PdoConnection;

$config = ['host' => 'localhost', 'username' => 'user', /* ... */];
$connection = new PdoConnection($config);
```

### MysqliConnection

Wrapper for MySQLi connections with PDO-compatible interface.

```php
use Ksfraser\Database\MysqliConnection;

$config = ['host' => 'localhost', 'username' => 'user', /* ... */];
$connection = new MysqliConnection($config);
```

### PdoSqliteConnection

SQLite connection for testing and development.

```php
use Ksfraser\Database\PdoSqliteConnection;

$connection = new PdoSqliteConnection(); // Uses in-memory database
```

## Testing

### Running Tests

```bash
# Using the simple test runner
php run_tests.php

# Using PHPUnit (if available)
./vendor/bin/phpunit src/Ksfraser/Database/tests/

# Run specific test suite
./vendor/bin/phpunit --testsuite Database
```

### Test Coverage

The test suite covers:

- Connection management and driver selection
- Configuration loading from multiple sources
- SQL execution and result handling
- Transaction management
- Error handling and recovery
- SQLite fallback functionality

## Error Handling

The system provides comprehensive error handling:

```php
try {
    $result = EnhancedDbManager::fetchOne("SELECT * FROM users WHERE id = ?", [123]);
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    // Handle error appropriately
}
```

## Best Practices

### 1. Use Parameterized Queries

```php
// Good
$users = EnhancedDbManager::fetchAll("SELECT * FROM users WHERE status = ?", ['active']);

// Bad - SQL injection risk
$users = EnhancedDbManager::fetchAll("SELECT * FROM users WHERE status = '$status'");
```

### 2. Handle Transactions Properly

```php
try {
    EnhancedDbManager::beginTransaction();
    
    EnhancedDbManager::execute("INSERT INTO users (name) VALUES (?)", ['John']);
    EnhancedDbManager::execute("INSERT INTO profiles (user_id) VALUES (?)", [EnhancedDbManager::lastInsertId()]);
    
    EnhancedDbManager::commit();
} catch (Exception $e) {
    EnhancedDbManager::rollback();
    throw $e;
}
```

### 3. Configuration Security

- Store database credentials securely
- Use environment variables in production
- Never commit credentials to version control
- Restrict file permissions on configuration files

### 4. Error Logging

```php
// The system automatically logs errors, but you can add custom logging
try {
    $result = EnhancedDbManager::fetchOne("SELECT * FROM users WHERE id = ?", [123]);
} catch (Exception $e) {
    error_log("Application error in user lookup: " . $e->getMessage());
    // Return appropriate user-facing error
}
```

## Troubleshooting

### Common Issues

1. **"No suitable database driver available"**
   - Install required PHP extensions: `php-pdo`, `php-mysql`, `php-mysqli`
   - Verify extensions are enabled in `php.ini`

2. **"could not find driver"**
   - Check PDO MySQL driver: `php -m | grep pdo_mysql`
   - Install: `sudo apt-get install php-mysql` (Ubuntu/Debian)

3. **Connection refused**
   - Verify database server is running
   - Check host, port, and firewall settings
   - Validate credentials

4. **Configuration not found**
   - Check configuration file paths
   - Verify file permissions
   - Set environment variables as fallback

### Debug Information

```php
// Get current driver information
$driver = EnhancedDbManager::getCurrentDriver();
echo "Current driver: $driver\n";

// Get configuration
$config = EnhancedDbManager::getConfig();
print_r($config);
```

## Performance Considerations

- Connection pooling reduces overhead
- Prepared statements are cached automatically
- SQLite is used for testing to avoid network overhead
- Transactions group operations for better performance

## Compatibility

- **PHP**: 7.4+ (8.0+ recommended)
- **MySQL**: 5.7+ / MariaDB 10.2+
- **Extensions**: PDO, PDO_MySQL, MySQLi, PDO_SQLite

## License

GNU General Public License v3.0

## Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## Changelog

### Version 1.0.0
- Initial release with multi-driver support
- Configuration flexibility
- Comprehensive test suite
- SQLite fallback for testing

## Support

For issues and questions:
- Check the troubleshooting section
- Review test cases for usage examples
- Submit issues to the repository issue tracker
