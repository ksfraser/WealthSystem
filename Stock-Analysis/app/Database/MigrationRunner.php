<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;

/**
 * Migration Runner
 * 
 * Manages database schema migrations with version tracking,
 * up/down migrations, batch management, and rollback support.
 * 
 * Features:
 * - Version tracking in migrations table
 * - Batch execution (group migrations together)
 * - Rollback support (reverse last batch)
 * - Pending migration detection
 * - Transaction support for safe execution
 * - Migration history and status
 * 
 * @package App\Database
 */
class MigrationRunner
{
    private Connection $connection;
    private string $table = 'migrations';
    
    /**
     * Create new migration runner
     *
     * @param Connection $connection Database connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    
    /**
     * Initialize migrations table
     *
     * Creates the migrations tracking table if it doesn't exist.
     *
     * @return void
     */
    public function initialize(): void
    {
        $pdo = $this->connection->getPDO();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration TEXT NOT NULL UNIQUE,
            batch INTEGER NOT NULL,
            executed_at TEXT NOT NULL
        )";
        
        $pdo->exec($sql);
    }
    
    /**
     * Record executed migration
     *
     * @param string $migration Migration name
     * @param int $batch Batch number
     * @return void
     */
    public function recordMigration(string $migration, int $batch): void
    {
        $pdo = $this->connection->getPDO();
        
        $stmt = $pdo->prepare(
            "INSERT INTO {$this->table} (migration, batch, executed_at) 
             VALUES (:migration, :batch, :executed_at)"
        );
        
        $stmt->execute([
            'migration' => $migration,
            'batch' => $batch,
            'executed_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get list of executed migrations
     *
     * @return array<int, string> Migration names
     */
    public function getExecutedMigrations(): array
    {
        $pdo = $this->connection->getPDO();
        
        $stmt = $pdo->query("SELECT migration FROM {$this->table} ORDER BY id");
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Get last batch number
     *
     * @return int Batch number (0 if no migrations)
     */
    public function getLastBatchNumber(): int
    {
        $pdo = $this->connection->getPDO();
        
        $stmt = $pdo->query("SELECT MAX(batch) as max_batch FROM {$this->table}");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int) ($result['max_batch'] ?? 0);
    }
    
    /**
     * Get pending migrations
     *
     * Returns migrations that haven't been executed yet.
     *
     * @param array<int, string> $allMigrations All available migration names
     * @return array<int, string> Pending migration names
     */
    public function getPendingMigrations(array $allMigrations): array
    {
        $executed = $this->getExecutedMigrations();
        
        return array_values(array_diff($allMigrations, $executed));
    }
    
    /**
     * Run up migration
     *
     * Executes a migration's up() method within a transaction.
     *
     * @param string $migrationName Migration name
     * @param object $migration Migration instance with up(PDO) method
     * @return void
     * @throws RuntimeException If migration fails
     */
    public function runUp(string $migrationName, object $migration): void
    {
        $pdo = $this->connection->getPDO();
        
        try {
            $pdo->beginTransaction();
            
            // Execute migration's up() method
            $migration->up($pdo);
            
            // Record migration
            $batch = $this->getLastBatchNumber() + 1;
            $this->recordMigration($migrationName, $batch);
            
            $pdo->commit();
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new RuntimeException(
                "Migration '{$migrationName}' failed: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
    
    /**
     * Run down migration
     *
     * Executes a migration's down() method to reverse it.
     *
     * @param string $migrationName Migration name
     * @param object $migration Migration instance with down(PDO) method
     * @return void
     * @throws RuntimeException If rollback fails
     */
    public function runDown(string $migrationName, object $migration): void
    {
        $pdo = $this->connection->getPDO();
        
        try {
            $pdo->beginTransaction();
            
            // Execute migration's down() method
            $migration->down($pdo);
            
            // Remove migration record
            $this->removeMigration($migrationName);
            
            $pdo->commit();
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new RuntimeException(
                "Rollback of '{$migrationName}' failed: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
    
    /**
     * Get migrations from last batch
     *
     * Returns migrations in reverse order (newest first) for rollback.
     *
     * @return array<int, array{migration: string, batch: int}> Migration records
     */
    public function getLastBatchMigrations(): array
    {
        $pdo = $this->connection->getPDO();
        $lastBatch = $this->getLastBatchNumber();
        
        $stmt = $pdo->prepare(
            "SELECT migration, batch FROM {$this->table} 
             WHERE batch = :batch 
             ORDER BY id DESC"
        );
        
        $stmt->execute(['batch' => $lastBatch]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get migration status
     *
     * Returns status (Executed/Pending) for each migration.
     *
     * @param array<int, string> $allMigrations All available migration names
     * @return array<string, string> Migration name => status
     */
    public function getStatus(array $allMigrations): array
    {
        $executed = $this->getExecutedMigrations();
        $status = [];
        
        foreach ($allMigrations as $migration) {
            $status[$migration] = in_array($migration, $executed, true) 
                ? 'Executed' 
                : 'Pending';
        }
        
        return $status;
    }
    
    /**
     * Order migrations by filename
     *
     * Sorts migrations chronologically based on filename prefix.
     *
     * @param array<int, string> $migrations Migration names
     * @return array<int, string> Sorted migration names
     */
    public function orderMigrations(array $migrations): array
    {
        sort($migrations);
        
        return array_values($migrations);
    }
    
    /**
     * Remove migration record
     *
     * @param string $migration Migration name
     * @return void
     */
    public function removeMigration(string $migration): void
    {
        $pdo = $this->connection->getPDO();
        
        $stmt = $pdo->prepare("DELETE FROM {$this->table} WHERE migration = :migration");
        $stmt->execute(['migration' => $migration]);
    }
    
    /**
     * Reset all migrations
     *
     * Removes all migration records (does not run down migrations).
     *
     * @return void
     */
    public function reset(): void
    {
        $pdo = $this->connection->getPDO();
        
        $pdo->exec("DELETE FROM {$this->table}");
    }
    
    /**
     * Get migration history
     *
     * Returns complete migration history with execution timestamps.
     *
     * @return array<int, array{id: int, migration: string, batch: int, executed_at: string}> Migration records
     */
    public function getMigrationHistory(): array
    {
        $pdo = $this->connection->getPDO();
        
        $stmt = $pdo->query("SELECT * FROM {$this->table} ORDER BY id");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
