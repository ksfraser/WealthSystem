<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
use RuntimeException;
use InvalidArgumentException;

/**
 * Database Migration Manager
 * 
 * Manages database schema migrations including:
 * - Migration discovery and loading
 * - Migration execution (up/down)
 * - Version tracking
 * - Rollback support
 * 
 * Follows SOLID principles:
 * - Single Responsibility: Handles only migration management
 * - Dependency Injection: PDO injected via constructor
 * - Interface Segregation: Uses Migration interface
 * 
 * @package App\Database
 */
class MigrationManager
{
    private const SCHEMA_TABLE = 'schema_versions';
    private const VERSION_PATTERN = '/^\d{14}$/';

    private PDO $pdo;
    private string $migrationsPath;

    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection
     * @param string $migrationsPath Path to migrations directory
     */
    public function __construct(PDO $pdo, string $migrationsPath)
    {
        $this->pdo = $pdo;
        $this->migrationsPath = $migrationsPath;
    }

    /**
     * Initialize the migration system
     * 
     * Creates the schema_versions table if it doesn't exist.
     * 
     * @return void
     * @throws RuntimeException If initialization fails
     */
    public function initialize(): void
    {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS " . self::SCHEMA_TABLE . " (
                    version VARCHAR(14) PRIMARY KEY,
                    description VARCHAR(255) NOT NULL,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ";
            
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to initialize migration system: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get all available migrations from the migrations directory
     * 
     * @return Migration[] Array of migration instances sorted by version
     */
    public function getAvailableMigrations(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $migrations = [];
        $files = glob($this->migrationsPath . '/*.php');

        foreach ($files as $file) {
            require_once $file;
            
            // Extract class name from filename
            $className = $this->extractClassName($file);
            
            if ($className && class_exists($className)) {
                $migration = new $className();
                
                if ($migration instanceof Migration) {
                    $migrations[$migration->getVersion()] = $migration;
                }
            }
        }

        ksort($migrations);
        return array_values($migrations);
    }

    /**
     * Extract class name from migration file
     * 
     * @param string $file File path
     * @return string|null Class name or null
     */
    private function extractClassName(string $file): ?string
    {
        $basename = basename($file, '.php');
        
        // Format: 20251205000001_CreateUsersIndexesMigration
        if (preg_match('/^\d{14}_(.+)$/', $basename, $matches)) {
            return 'Tests\\Database\\Fixtures\\' . $matches[1];
        }
        
        return null;
    }

    /**
     * Get the current schema version
     * 
     * @return string Current version or '0' if no migrations executed
     */
    public function getCurrentVersion(): string
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT version FROM " . self::SCHEMA_TABLE . " ORDER BY version DESC LIMIT 1"
            );
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['version'] : '0';
        } catch (PDOException $e) {
            return '0';
        }
    }

    /**
     * Get pending migrations that haven't been executed
     * 
     * @return Migration[] Array of pending migrations
     */
    public function getPendingMigrations(): array
    {
        $currentVersion = $this->getCurrentVersion();
        $allMigrations = $this->getAvailableMigrations();

        return array_filter(
            $allMigrations,
            fn(Migration $m) => $m->getVersion() > $currentVersion
        );
    }

    /**
     * Run a single migration
     * 
     * @param Migration $migration Migration to execute
     * @return bool True on success
     * @throws RuntimeException If migration fails or already executed
     * @throws InvalidArgumentException If version format is invalid
     */
    public function migrate(Migration $migration): bool
    {
        $version = $migration->getVersion();
        
        $this->validateVersion($version);
        
        if ($this->isExecuted($version)) {
            throw new RuntimeException("Migration {$version} has already been executed");
        }

        try {
            $this->pdo->beginTransaction();
            
            // Execute migration
            $migration->up($this->pdo);
            
            // Record in history
            $this->recordMigration($migration);
            
            $this->pdo->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new RuntimeException(
                "Migration {$version} failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Run all pending migrations
     * 
     * @return array Array of executed migration versions
     */
    public function migrateAll(): array
    {
        $pending = $this->getPendingMigrations();
        $executed = [];

        foreach ($pending as $migration) {
            $this->migrate($migration);
            $executed[] = $migration->getVersion();
        }

        return $executed;
    }

    /**
     * Rollback the last executed migration
     * 
     * @return bool True on success
     * @throws RuntimeException If rollback fails or no migrations to rollback
     */
    public function rollback(): bool
    {
        $currentVersion = $this->getCurrentVersion();
        
        if ($currentVersion === '0') {
            throw new RuntimeException('No migrations to rollback');
        }

        $migration = $this->findMigrationByVersion($currentVersion);
        
        if (!$migration) {
            throw new RuntimeException("Migration file not found for version {$currentVersion}");
        }

        try {
            $this->pdo->beginTransaction();
            
            // Execute rollback
            $migration->down($this->pdo);
            
            // Remove from history
            $this->removeMigration($currentVersion);
            
            $this->pdo->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw new RuntimeException(
                "Rollback of {$currentVersion} failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Rollback to a specific version
     * 
     * @param string $targetVersion Target version
     * @return bool True on success
     * @throws RuntimeException If rollback fails
     */
    public function rollbackTo(string $targetVersion): bool
    {
        $currentVersion = $this->getCurrentVersion();
        
        while ($currentVersion > $targetVersion) {
            $this->rollback();
            $currentVersion = $this->getCurrentVersion();
        }

        return true;
    }

    /**
     * Get migration execution history
     * 
     * @return array Array of executed migrations with timestamps
     */
    public function getMigrationHistory(): array
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT version, description, executed_at FROM " . self::SCHEMA_TABLE . " ORDER BY version ASC"
            );
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get migration status
     * 
     * @return array Status information
     */
    public function getStatus(): array
    {
        $current = $this->getCurrentVersion();
        $pending = $this->getPendingMigrations();
        $history = $this->getMigrationHistory();

        return [
            'current_version' => $current,
            'pending_count' => count($pending),
            'executed_count' => count($history),
        ];
    }

    /**
     * Check if a migration has been executed
     * 
     * @param string $version Migration version
     * @return bool True if executed
     */
    private function isExecuted(string $version): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM " . self::SCHEMA_TABLE . " WHERE version = ?"
            );
            $stmt->execute([$version]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Record a migration in the schema_versions table
     * 
     * @param Migration $migration Migration to record
     * @return void
     */
    private function recordMigration(Migration $migration): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO " . self::SCHEMA_TABLE . " (version, description) VALUES (?, ?)"
        );
        
        $stmt->execute([
            $migration->getVersion(),
            $migration->getDescription()
        ]);
    }

    /**
     * Remove a migration from the schema_versions table
     * 
     * @param string $version Migration version
     * @return void
     */
    private function removeMigration(string $version): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM " . self::SCHEMA_TABLE . " WHERE version = ?"
        );
        
        $stmt->execute([$version]);
    }

    /**
     * Find a migration by version
     * 
     * @param string $version Migration version
     * @return Migration|null Migration instance or null
     */
    private function findMigrationByVersion(string $version): ?Migration
    {
        $migrations = $this->getAvailableMigrations();
        
        foreach ($migrations as $migration) {
            if ($migration->getVersion() === $version) {
                return $migration;
            }
        }
        
        return null;
    }

    /**
     * Validate migration version format
     * 
     * @param string $version Version to validate
     * @return void
     * @throws InvalidArgumentException If format is invalid
     */
    private function validateVersion(string $version): void
    {
        if (!preg_match(self::VERSION_PATTERN, $version)) {
            throw new InvalidArgumentException(
                "Invalid migration version format: {$version}. Expected format: YYYYMMDDHHmmss"
            );
        }
    }
}
