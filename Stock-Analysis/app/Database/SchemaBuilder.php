<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Database Schema Builder
 * 
 * Provides fluent interface for schema modifications:
 * - Index creation and management
 * - Foreign key constraints
 * - Table introspection
 * 
 * Follows SOLID principles:
 * - Single Responsibility: Schema management only
 * - Dependency Injection: PDO injected via constructor
 * 
 * @package App\Database
 */
class SchemaBuilder
{
    private PDO $pdo;
    private string $driver;

    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Add an index to a table
     * 
     * @param string $table Table name
     * @param string|array $columns Column name(s)
     * @param string|null $indexName Index name (auto-generated if null)
     * @return void
     * @throws RuntimeException If index creation fails or already exists
     */
    public function addIndex(string $table, $columns, ?string $indexName = null): void
    {
        $columns = (array) $columns;
        $indexName = $indexName ?? $this->generateIndexName($table, $columns);

        if ($this->hasIndex($table, $indexName)) {
            throw new RuntimeException("Index {$indexName} already exists on table {$table}");
        }

        $columnList = implode(', ', $columns);
        $sql = "CREATE INDEX {$indexName} ON {$table} ({$columnList})";

        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to create index {$indexName}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Add a unique index to a table
     * 
     * @param string $table Table name
     * @param string|array $columns Column name(s)
     * @param string|null $indexName Index name (auto-generated if null)
     * @return void
     * @throws RuntimeException If index creation fails or already exists
     */
    public function addUniqueIndex(string $table, $columns, ?string $indexName = null): void
    {
        $columns = (array) $columns;
        $indexName = $indexName ?? $this->generateIndexName($table, $columns) . '_unique';

        if ($this->hasIndex($table, $indexName)) {
            throw new RuntimeException("Index {$indexName} already exists on table {$table}");
        }

        $columnList = implode(', ', $columns);
        $sql = "CREATE UNIQUE INDEX {$indexName} ON {$table} ({$columnList})";

        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to create unique index {$indexName}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Drop an index from a table
     * 
     * @param string $table Table name
     * @param string $indexName Index name
     * @return void
     * @throws RuntimeException If index drop fails
     */
    public function dropIndex(string $table, string $indexName): void
    {
        $sql = $this->driver === 'mysql'
            ? "DROP INDEX {$indexName} ON {$table}"
            : "DROP INDEX {$indexName}";

        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to drop index {$indexName}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Add a foreign key constraint
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @param string $referencedTable Referenced table name
     * @param string $referencedColumn Referenced column name
     * @param string|null $constraintName Constraint name (auto-generated if null)
     * @param string $onDelete ON DELETE action (CASCADE, SET NULL, RESTRICT)
     * @param string $onUpdate ON UPDATE action (CASCADE, SET NULL, RESTRICT)
     * @return void
     * @throws RuntimeException If constraint creation fails or already exists
     */
    public function addForeignKey(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        ?string $constraintName = null,
        string $onDelete = 'RESTRICT',
        string $onUpdate = 'RESTRICT'
    ): void {
        $constraintName = $constraintName ?? $this->generateForeignKeyName($table, $column, $referencedTable);

        if ($this->hasForeignKey($table, $constraintName)) {
            throw new RuntimeException("Foreign key {$constraintName} already exists on table {$table}");
        }

        if ($this->driver === 'sqlite') {
            // SQLite requires table recreation for foreign keys
            throw new RuntimeException("SQLite does not support adding foreign keys to existing tables");
        }

        $sql = "ALTER TABLE {$table} 
                ADD CONSTRAINT {$constraintName} 
                FOREIGN KEY ({$column}) 
                REFERENCES {$referencedTable}({$referencedColumn})
                ON DELETE {$onDelete}
                ON UPDATE {$onUpdate}";

        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to create foreign key {$constraintName}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Drop a foreign key constraint
     * 
     * @param string $table Table name
     * @param string $constraintName Constraint name
     * @return void
     * @throws RuntimeException If constraint drop fails
     */
    public function dropForeignKey(string $table, string $constraintName): void
    {
        if ($this->driver === 'sqlite') {
            throw new RuntimeException("SQLite does not support dropping foreign keys");
        }

        $sql = "ALTER TABLE {$table} DROP FOREIGN KEY {$constraintName}";

        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to drop foreign key {$constraintName}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check if an index exists on a table
     * 
     * @param string $table Table name
     * @param string $indexName Index name
     * @return bool True if index exists
     */
    public function hasIndex(string $table, string $indexName): bool
    {
        $indexes = $this->getIndexes($table);
        return in_array($indexName, array_column($indexes, 'name'));
    }

    /**
     * Check if a foreign key constraint exists
     * 
     * @param string $table Table name
     * @param string $constraintName Constraint name
     * @return bool True if foreign key exists
     */
    public function hasForeignKey(string $table, string $constraintName): bool
    {
        $foreignKeys = $this->getForeignKeys($table);
        return in_array($constraintName, array_column($foreignKeys, 'name'));
    }

    /**
     * Check if a table exists
     * 
     * @param string $table Table name
     * @return bool True if table exists
     */
    public function hasTable(string $table): bool
    {
        try {
            if ($this->driver === 'mysql') {
                $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                return $stmt->fetch(PDO::FETCH_NUM) !== false;
            } elseif ($this->driver === 'sqlite') {
                $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
                $stmt->execute([$table]);
                return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
            }
            
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get all indexes for a table
     * 
     * @param string $table Table name
     * @return array Array of index information
     */
    public function getIndexes(string $table): array
    {
        try {
            if ($this->driver === 'mysql') {
                $stmt = $this->pdo->prepare("SHOW INDEXES FROM {$table}");
                $stmt->execute();
                
                $indexes = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $indexes[] = [
                        'name' => $row['Key_name'],
                        'column' => $row['Column_name'],
                        'unique' => $row['Non_unique'] == 0,
                    ];
                }
                return $indexes;
            } elseif ($this->driver === 'sqlite') {
                $stmt = $this->pdo->prepare("PRAGMA index_list({$table})");
                $stmt->execute();
                
                $indexes = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $indexes[] = [
                        'name' => $row['name'],
                        'unique' => $row['unique'] == 1,
                    ];
                }
                return $indexes;
            }
            
            return [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get all foreign keys for a table
     * 
     * @param string $table Table name
     * @return array Array of foreign key information
     */
    public function getForeignKeys(string $table): array
    {
        try {
            if ($this->driver === 'mysql') {
                $sql = "
                    SELECT 
                        CONSTRAINT_NAME as name,
                        COLUMN_NAME as column_name,
                        REFERENCED_TABLE_NAME as referenced_table,
                        REFERENCED_COLUMN_NAME as referenced_column,
                        DELETE_RULE as on_delete,
                        UPDATE_RULE as on_update
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME = ?
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                ";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$table]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($this->driver === 'sqlite') {
                $stmt = $this->pdo->prepare("PRAGMA foreign_key_list({$table})");
                $stmt->execute();
                
                $foreignKeys = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $foreignKeys[] = [
                        'name' => "fk_{$table}_{$row['from']}_{$row['table']}",
                        'column_name' => $row['from'],
                        'referenced_table' => $row['table'],
                        'referenced_column' => $row['to'],
                        'on_delete' => $row['on_delete'],
                        'on_update' => $row['on_update'],
                    ];
                }
                return $foreignKeys;
            }
            
            return [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get all columns for a table
     * 
     * @param string $table Table name
     * @return array Array of column information
     */
    public function getColumns(string $table): array
    {
        try {
            if ($this->driver === 'mysql') {
                $stmt = $this->pdo->prepare("SHOW COLUMNS FROM {$table}");
                $stmt->execute();
                
                $columns = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $columns[] = [
                        'name' => $row['Field'],
                        'type' => $row['Type'],
                        'nullable' => $row['Null'] === 'YES',
                        'default' => $row['Default'],
                    ];
                }
                return $columns;
            } elseif ($this->driver === 'sqlite') {
                $stmt = $this->pdo->prepare("PRAGMA table_info({$table})");
                $stmt->execute();
                
                $columns = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $columns[] = [
                        'name' => $row['name'],
                        'type' => $row['type'],
                        'nullable' => $row['notnull'] == 0,
                        'default' => $row['dflt_value'],
                    ];
                }
                return $columns;
            }
            
            return [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Generate an index name
     * 
     * @param string $table Table name
     * @param array $columns Column names
     * @return string Generated index name
     */
    public function generateIndexName(string $table, array $columns): string
    {
        $columnStr = implode('_', $columns);
        return "idx_{$table}_{$columnStr}";
    }

    /**
     * Generate a foreign key constraint name
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @param string $referencedTable Referenced table name
     * @return string Generated constraint name
     */
    public function generateForeignKeyName(string $table, string $column, string $referencedTable): string
    {
        return "fk_{$table}_{$column}_{$referencedTable}";
    }
}
