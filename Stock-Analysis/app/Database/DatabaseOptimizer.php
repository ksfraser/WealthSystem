<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use InvalidArgumentException;

/**
 * Database Performance Optimizer
 * 
 * Analyzes database structure and query patterns to provide optimization
 * recommendations including index suggestions, slow query detection, and
 * performance statistics.
 * 
 * Features:
 * - Index analysis and recommendations
 * - Query performance monitoring
 * - Slow query detection
 * - Table statistics
 * - Duplicate/unused index detection
 * 
 * @package App\Database
 */
class DatabaseOptimizer
{
    private PDO $pdo;
    private array $queryLog = [];
    private array $queryPatterns = [];

    /**
     * Create new database optimizer
     *
     * @param PDO $pdo Database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Analyze indexes for a specific table
     *
     * @param string $table Table name
     * @return array<string, mixed> Analysis results
     * @throws InvalidArgumentException If table doesn't exist
     */
    public function analyzeTableIndexes(string $table): array
    {
        if (!$this->tableExists($table)) {
            throw new InvalidArgumentException("Table '{$table}' does not exist");
        }

        $indexes = $this->getTableIndexes($table);
        $columns = $this->getTableColumns($table);

        return [
            'table' => $table,
            'indexes' => $indexes,
            'columns' => $columns,
            'recommendations' => $this->getIndexRecommendations($table)
        ];
    }

    /**
     * Get index recommendations for a table
     *
     * @param string $table Table name
     * @return array<int, array<string, mixed>> Recommendations
     */
    public function getIndexRecommendations(string $table): array
    {
        $recommendations = [];
        $columns = $this->getTableColumns($table);
        $existingIndexes = $this->getTableIndexes($table);

        // Check for foreign key columns without indexes
        foreach ($columns as $column) {
            if ($this->isForeignKeyColumn($column) && !$this->hasIndexOnColumn($table, $column, $existingIndexes)) {
                $recommendations[] = [
                    'table' => $table,
                    'column' => $column,
                    'type' => 'single',
                    'reason' => 'Foreign key column should have an index',
                    'priority' => 'high'
                ];
            }
        }

        // Check for frequently queried columns
        $frequentColumns = $this->getFrequentlyQueriedColumns($table);
        foreach ($frequentColumns as $column => $count) {
            if (!$this->hasIndexOnColumn($table, $column, $existingIndexes)) {
                $recommendations[] = [
                    'table' => $table,
                    'column' => $column,
                    'type' => 'single',
                    'reason' => "Frequently queried column ({$count} times)",
                    'priority' => 'medium'
                ];
            }
        }

        // Check for composite index opportunities
        $compositeOpportunities = $this->findCompositeIndexOpportunities($table);
        foreach ($compositeOpportunities as $opportunity) {
            $recommendations[] = $opportunity;
        }

        return $recommendations;
    }

    /**
     * Track a query for analysis
     *
     * @param string $query SQL query
     * @param float $executionTime Execution time in seconds
     * @return void
     */
    public function trackQuery(string $query, float $executionTime = 0.0): void
    {
        $this->queryLog[] = [
            'query' => $query,
            'execution_time' => $executionTime,
            'timestamp' => time()
        ];

        // Extract query patterns
        $this->extractQueryPattern($query);
    }

    /**
     * Get slow queries above threshold
     *
     * @param float $thresholdSeconds Threshold in seconds
     * @return array<int, array<string, mixed>> Slow queries
     */
    public function getSlowQueries(float $thresholdSeconds): array
    {
        return array_filter($this->queryLog, function ($log) use ($thresholdSeconds) {
            return $log['execution_time'] > $thresholdSeconds;
        });
    }

    /**
     * Get table statistics
     *
     * @param string $table Table name
     * @return array<string, mixed> Statistics
     */
    public function getTableStatistics(string $table): array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            return $this->getSQLiteTableStats($table);
        } else {
            return $this->getMySQLTableStats($table);
        }
    }

    /**
     * Find missing indexes across all tables
     *
     * @return array<int, array<string, mixed>> Missing indexes
     */
    public function findMissingIndexes(): array
    {
        $tables = $this->getAllTables();
        $missing = [];

        foreach ($tables as $table) {
            $recommendations = $this->getIndexRecommendations($table);
            $missing = array_merge($missing, $recommendations);
        }

        return $missing;
    }

    /**
     * Generate SQL for creating an index
     *
     * @param array<string, mixed> $recommendation Index recommendation
     * @return string SQL statement
     */
    public function generateIndexSQL(array $recommendation): string
    {
        $table = $recommendation['table'];
        
        if (isset($recommendation['columns']) && is_array($recommendation['columns'])) {
            // Composite index
            $columns = implode(', ', $recommendation['columns']);
            $indexName = 'idx_' . $table . '_' . implode('_', $recommendation['columns']);
        } else {
            // Single column index
            $column = $recommendation['column'];
            $columns = $column;
            $indexName = 'idx_' . $table . '_' . $column;
        }

        return "CREATE INDEX {$indexName} ON {$table} ({$columns})";
    }

    /**
     * Get query performance report
     *
     * @return array<string, mixed> Performance report
     */
    public function getQueryPerformanceReport(): array
    {
        if (empty($this->queryLog)) {
            return [
                'total_queries' => 0,
                'average_time' => 0.0,
                'slowest_queries' => []
            ];
        }

        $totalTime = array_sum(array_column($this->queryLog, 'execution_time'));
        $avgTime = $totalTime / count($this->queryLog);

        // Sort by execution time
        $sortedLog = $this->queryLog;
        usort($sortedLog, function ($a, $b) {
            return $b['execution_time'] <=> $a['execution_time'];
        });

        return [
            'total_queries' => count($this->queryLog),
            'average_time' => $avgTime,
            'slowest_queries' => array_slice($sortedLog, 0, 10)
        ];
    }

    /**
     * Get index usage statistics
     *
     * @param string $table Table name
     * @return array<int, array<string, mixed>> Usage statistics
     */
    public function getIndexUsageStatistics(string $table): array
    {
        $indexes = $this->getTableIndexes($table);
        $usage = [];

        foreach ($indexes as $index) {
            $isUsed = $this->isIndexUsed($table, $index);
            
            $usage[] = [
                'index_name' => $index,
                'table' => $table,
                'is_used' => $isUsed
            ];
        }

        return $usage;
    }

    /**
     * Find unused indexes
     *
     * @return array<int, array<string, mixed>> Unused indexes
     */
    public function findUnusedIndexes(): array
    {
        $tables = $this->getAllTables();
        $unused = [];

        foreach ($tables as $table) {
            $stats = $this->getIndexUsageStatistics($table);
            
            foreach ($stats as $stat) {
                if (!$stat['is_used'] && !$this->isPrimaryKey($stat['index_name'])) {
                    $unused[] = $stat;
                }
            }
        }

        return $unused;
    }

    /**
     * Analyze index effectiveness
     *
     * @param string $table Table name
     * @return array<string, mixed> Effectiveness analysis
     */
    public function analyzeIndexEffectiveness(string $table): array
    {
        $indexes = $this->getTableIndexes($table);
        $usedCount = 0;

        foreach ($indexes as $index) {
            if ($this->isIndexUsed($table, $index)) {
                $usedCount++;
            }
        }

        $totalIndexes = count($indexes);
        $effectiveness = $totalIndexes > 0 ? ($usedCount / $totalIndexes) : 0;

        return [
            'total_indexes' => $totalIndexes,
            'used_indexes' => $usedCount,
            'unused_indexes' => $totalIndexes - $usedCount,
            'effectiveness_score' => round($effectiveness * 100, 2)
        ];
    }

    /**
     * Generate comprehensive optimization report
     *
     * @return array<string, mixed> Optimization report
     */
    public function generateOptimizationReport(): array
    {
        $tables = $this->getAllTables();

        return [
            'tables_analyzed' => count($tables),
            'recommendations' => $this->findMissingIndexes(),
            'slow_queries' => $this->getSlowQueries(0.1),
            'missing_indexes' => $this->findMissingIndexes(),
            'unused_indexes' => $this->findUnusedIndexes(),
            'query_performance' => $this->getQueryPerformanceReport()
        ];
    }

    /**
     * Find duplicate indexes
     *
     * @param string $table Table name
     * @return array<int, array<string, mixed>> Duplicate indexes
     */
    public function findDuplicateIndexes(string $table): array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $duplicates = [];

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query("PRAGMA index_list('{$table}')");
            $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $indexColumns = [];
            foreach ($indexes as $index) {
                $infoStmt = $this->pdo->query("PRAGMA index_info('{$index['name']}')");
                $columns = $infoStmt->fetchAll(PDO::FETCH_COLUMN, 2);
                $columnKey = implode(',', $columns);
                
                if (isset($indexColumns[$columnKey])) {
                    $duplicates[] = [
                        'index1' => $indexColumns[$columnKey],
                        'index2' => $index['name'],
                        'columns' => $columns
                    ];
                } else {
                    $indexColumns[$columnKey] = $index['name'];
                }
            }
        }

        return $duplicates;
    }

    // Private helper methods

    private function tableExists(string $table): bool
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
            $stmt->execute([$table]);
            return $stmt->fetch() !== false;
        } else {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return $stmt->fetch() !== false;
        }
    }

    private function getTableIndexes(string $table): array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query("PRAGMA index_list('{$table}')");
            return $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
        } else {
            $stmt = $this->pdo->query("SHOW INDEX FROM {$table}");
            return array_unique($stmt->fetchAll(PDO::FETCH_COLUMN, 2));
        }
    }

    private function getTableColumns(string $table): array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query("PRAGMA table_info('{$table}')");
            return $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
        } else {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM {$table}");
            return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        }
    }

    private function isForeignKeyColumn(string $column): bool
    {
        return str_ends_with($column, '_id') || $column === 'user_id' || $column === 'portfolio_id';
    }

    private function hasIndexOnColumn(string $table, string $column, array $existingIndexes): bool
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            foreach ($existingIndexes as $indexName) {
                $stmt = $this->pdo->query("PRAGMA index_info('{$indexName}')");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 2);
                if (in_array($column, $columns, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getFrequentlyQueriedColumns(string $table): array
    {
        $columns = [];

        foreach ($this->queryPatterns as $pattern => $count) {
            // Match table name case-insensitively
            if (str_contains(strtoupper($pattern), strtoupper($table))) {
                // Extract column from WHERE clause
                if (preg_match('/WHERE\s+(\w+)\s*=/', $pattern, $matches)) {
                    $column = strtolower($matches[1]);
                    $columns[$column] = ($columns[$column] ?? 0) + $count;
                }
            }
        }

        return $columns;
    }

    private function findCompositeIndexOpportunities(string $table): array
    {
        $opportunities = [];
        $multiColumnPatterns = [];

        // Analyze queries for multi-column WHERE clauses
        foreach ($this->queryLog as $log) {
            if (preg_match_all('/WHERE\s+(\w+)\s*=.*?AND\s+(\w+)\s*[>=<]/', $log['query'], $matches)) {
                if (count($matches[1]) > 0 && count($matches[2]) > 0) {
                    $columns = [$matches[1][0], $matches[2][0]];
                    sort($columns);
                    $key = implode(',', $columns);
                    $multiColumnPatterns[$key] = ($multiColumnPatterns[$key] ?? 0) + 1;
                }
            }
        }

        // Recommend composite indexes for frequent patterns
        foreach ($multiColumnPatterns as $columnKey => $count) {
            if ($count >= 2) {
                $opportunities[] = [
                    'table' => $table,
                    'columns' => explode(',', $columnKey),
                    'type' => 'composite',
                    'reason' => "Frequently queried together ({$count} times)",
                    'priority' => 'medium'
                ];
            }
        }

        return $opportunities;
    }

    private function extractQueryPattern(string $query): void
    {
        // Normalize query for pattern matching
        $pattern = preg_replace('/\s+/', ' ', strtoupper(trim($query)));
        $pattern = preg_replace('/=\s*[\'"].*?[\'"]/', '= ?', $pattern);
        $pattern = preg_replace('/=\s*\d+/', '= ?', $pattern);

        $this->queryPatterns[$pattern] = ($this->queryPatterns[$pattern] ?? 0) + 1;
    }

    private function getSQLiteTableStats(string $table): array
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$table}");
        $rowCount = $stmt->fetchColumn();

        $stmt = $this->pdo->query("PRAGMA index_list('{$table}')");
        $indexCount = count($stmt->fetchAll());

        return [
            'row_count' => (int)$rowCount,
            'table_size' => 0, // SQLite doesn't easily provide this
            'index_count' => $indexCount
        ];
    }

    private function getMySQLTableStats(string $table): array
    {
        $stmt = $this->pdo->query("SHOW TABLE STATUS LIKE '{$table}'");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'row_count' => (int)($stats['Rows'] ?? 0),
            'table_size' => (int)($stats['Data_length'] ?? 0),
            'index_count' => 0 // Will be calculated separately
        ];
    }

    private function getAllTables(): array
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $stmt = $this->pdo->query("SHOW TABLES");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }

    private function isIndexUsed(string $table, string $index): bool
    {
        // Check if any tracked query references this index
        foreach ($this->queryLog as $log) {
            if (str_contains(strtolower($log['query']), strtolower($table))) {
                // Simple heuristic: if query has WHERE clause on indexed column
                return true;
            }
        }

        return false;
    }

    private function isPrimaryKey(string $indexName): bool
    {
        return str_contains(strtolower($indexName), 'primary') || 
               str_contains(strtolower($indexName), 'pk_');
    }
}
