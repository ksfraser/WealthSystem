<?php

/**
 * Mock PDO implementation for testing
 * Simulates basic PDO functionality without requiring an actual database
 */
class MockPDO
{
    private $tables = [];
    private $lastInsertId = 1;
    private $attributes = [];

    public function __construct()
    {
        $this->attributes[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $this->attributes[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
        $this->tables['_lastId'] = 0; // Track last insert ID
        
        // Pre-create schema_migrations table for testing
        $this->tables['schema_migrations'] = [];
    }

    public function exec($statement)
    {
        // Handle CREATE TABLE statements
        if (preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?(\w+)/i', $statement, $matches)) {
            $tableName = $matches[1];
            $this->tables[$tableName] = [];
            return 0;
        }

        // Handle DROP TABLE statements
        if (preg_match('/DROP TABLE\s+(?:IF EXISTS\s+)?(\w+)/i', $statement, $matches)) {
            $tableName = $matches[1];
            unset($this->tables[$tableName]);
            return 0;
        }

        // Handle INSERT statements
        if (preg_match('/INSERT INTO\s+(\w+)/i', $statement, $matches)) {
            $tableName = $matches[1];
            if (!isset($this->tables[$tableName])) {
                $this->tables[$tableName] = [];
            }
            
            // Extract VALUES if present
            $row = [];
            if (preg_match('/VALUES\s*\(([^)]+)\)/i', $statement, $valueMatches)) {
                $values = $valueMatches[1];
                // Simple parsing for quoted strings
                if (preg_match_all('/[\'"]([^\'"]+)[\'"]/', $values, $stringMatches)) {
                    // For migration_order table, assume single column 'step'
                    if ($tableName === 'migration_order') {
                        $row = ['step' => $stringMatches[1][0]];
                    } else if ($tableName === 'schema_migrations') {
                        // Handle schema_migrations filename column  
                        $row = ['filename' => $stringMatches[1][0]];
                    } else {
                        $this->lastInsertId++;
                        $row = array_merge(['id' => $this->lastInsertId], ['value' => $stringMatches[1][0]]);
                    }
                } else {
                    // Handle non-string values or use defaults
                    $this->lastInsertId++;
                    $row = ['id' => $this->lastInsertId];
                }
            } else {
                // Use defaults
                $this->lastInsertId++;
                $row = ['id' => $this->lastInsertId];
            }
            
            $this->tables[$tableName][] = $row;
            return 1;
        }

        // Handle DELETE statements
        if (preg_match('/DELETE FROM\s+(\w+)/i', $statement, $matches)) {
            $tableName = $matches[1];
            if (isset($this->tables[$tableName])) {
                $count = count($this->tables[$tableName]);
                $this->tables[$tableName] = [];
                return $count;
            }
            return 0;
        }

        return 0;
    }

    public function query($statement, $fetchMode = null, ...$args)
    {
        $stmt = new MockPDOStatement($this->tables, $statement);
        $stmt->execute(); // Auto-execute for query() calls
        return $stmt;
    }

    public function prepare($statement, $options = [])
    {
        return new MockPDOStatement($this->tables, $statement);
    }

    public function lastInsertId($name = null)
    {
        return $this->lastInsertId - 1;
    }

    public function setAttribute($attribute, $value)
    {
        $this->attributes[$attribute] = $value;
        return true;
    }

    public function getAttribute($attribute)
    {
        return $this->attributes[$attribute] ?? null;
    }

    public function beginTransaction()
    {
        return true;
    }

    public function commit()
    {
        return true;
    }

    public function rollBack()
    {
        return true;
    }

    public function inTransaction()
    {
        return false;
    }
}

/**
 * Mock PDO Statement implementation for testing
 */
class MockPDOStatement
{
    private $tables;
    private $statement;
    private $boundParams = [];
    private $results = [];
    private $currentRow = 0;
    private $executed = false;

    public function __construct(&$tables, $statement)
    {
        $this->tables = &$tables;
        $this->statement = $statement;
    }

    public function execute($params = null)
    {
        if ($params) {
            $this->boundParams = array_merge($this->boundParams, $params);
        }

        // Handle SELECT COUNT queries
        if (preg_match('/SELECT\s+COUNT\(\*\).*FROM\s+(\w+)/i', $this->statement, $matches)) {
            $tableName = $matches[1];
            $count = isset($this->tables[$tableName]) ? count($this->tables[$tableName]) : 0;
            $this->results = [['COUNT(*)' => $count, 'count' => $count]];
            $this->executed = true;
            return true;
        }

        // Handle SHOW TABLES queries
        if (preg_match('/SHOW TABLES LIKE/i', $this->statement)) {
            // Extract table name from the LIKE clause
            if (preg_match('/LIKE\s+[\'"](\w+)[\'"]/i', $this->statement, $matches)) {
                $tableName = $matches[1];
                if (isset($this->tables[$tableName])) {
                    $this->results = [['Tables_in_database' => $tableName]];
                } else {
                    $this->results = [];
                }
            } else {
                $this->results = [];
            }
            $this->executed = true;
            return true;
        }

        // Handle simple table existence queries
        if (preg_match('/SELECT.*FROM\s+(\w+)\s+LIMIT/i', $this->statement, $matches)) {
            $tableName = $matches[1];
            if (isset($this->tables[$tableName])) {
                $this->results = [['exists' => 1]];
            } else {
                $this->results = [];
            }
            $this->executed = true;
            return true;
        }

        // Handle SELECT queries from schema_migrations with WHERE
        if (preg_match('/SELECT.*FROM\s+schema_migrations.*WHERE/i', $this->statement)) {
            // For WHERE queries, check if any migration matches
            // This is a simplified approach - just check if any data exists
            if (count($this->tables['schema_migrations']) > 0) {
                $this->results = [['filename' => '001_create_test.sql']]; // Mock result
            } else {
                $this->results = [];
            }
            $this->executed = true;
            return true;
        }

        // Handle SELECT queries from schema_migrations
        if (preg_match('/SELECT.*filename.*FROM\s+schema_migrations/i', $this->statement)) {
            // Return filenames of applied migrations
            $filenames = [];
            foreach ($this->tables['schema_migrations'] as $row) {
                if (isset($row['filename'])) {
                    $filenames[] = $row['filename'];
                }
            }
            $this->results = array_map(function($fn) { return ['filename' => $fn]; }, $filenames);
            $this->executed = true;
            return true;
        }

        // Handle general SELECT queries
        if (preg_match('/SELECT.*FROM\s+(\w+)/i', $this->statement, $matches)) {
            $tableName = $matches[1];
            if (isset($this->tables[$tableName])) {
                $this->results = $this->tables[$tableName];
            } else {
                $this->results = [];
            }
            $this->executed = true;
            return true;
        }

        // Handle INSERT statements
        if (preg_match('/INSERT INTO\s+(\w+)/i', $this->statement, $matches)) {
            $tableName = $matches[1];
            if (!isset($this->tables[$tableName])) {
                $this->tables[$tableName] = [];
            }
            
            // Extract VALUES if present
            $row = [];
            if (preg_match('/VALUES\s*\(([^)]+)\)/i', $this->statement, $valueMatches)) {
                $values = $valueMatches[1];
                // Simple parsing for quoted strings
                if (preg_match_all('/[\'"]([^\'"]+)[\'"]/', $values, $stringMatches)) {
                    // For migration_order table, assume single column 'step'
                    if ($tableName === 'migration_order') {
                        $row = ['step' => $stringMatches[1][0]];
                    } else if ($tableName === 'schema_migrations') {
                        // Handle schema_migrations filename column
                        $row = ['filename' => $stringMatches[1][0]];
                    } else {
                        if (!isset($this->tables['_lastId'])) {
                            $this->tables['_lastId'] = 0;
                        }
                        $row = array_merge(['id' => ++$this->tables['_lastId']], ['value' => $stringMatches[1][0]]);
                    }
                } else {
                    // Handle non-string values or use defaults
                    if (!isset($this->tables['_lastId'])) {
                        $this->tables['_lastId'] = 0;
                    }
                    $row = ['id' => ++$this->tables['_lastId']];
                }
            } else {
                // Use bound parameters if available
                if (!isset($this->tables['_lastId'])) {
                    $this->tables['_lastId'] = 0;
                }
                $row = array_merge(['id' => ++$this->tables['_lastId']], $this->boundParams ?: ['mock' => 'data']);
            }
            
            $this->tables[$tableName][] = $row;
            $this->executed = true;
            return true;
        }

        $this->executed = true;
        return true;
    }

    public function fetch($mode = PDO::FETCH_DEFAULT, $orientation = PDO::FETCH_ORI_NEXT, $offset = 0)
    {
        if (!$this->executed) {
            $this->execute();
        }
        
        if ($this->currentRow < count($this->results)) {
            return $this->results[$this->currentRow++];
        }
        return false;
    }

    public function fetchAll($fetchMode = null, $fetchArgument = null, $constructorArgs = null)
    {
        if (!$this->executed) {
            $this->execute();
        }
        
        if ($fetchMode === PDO::FETCH_COLUMN) {
            // Return just the first column of each row, or specific column name
            if (is_string($fetchArgument)) {
                // Column name specified
                return array_map(function($row) use ($fetchArgument) {
                    return $row[$fetchArgument] ?? null;
                }, $this->results);
            } else {
                // First column
                return array_map(function($row) {
                    return is_array($row) ? array_values($row)[0] : $row;
                }, $this->results);
            }
        }
        return $this->results;
    }

    public function fetchColumn($column = 0)
    {
        if ($this->currentRow < count($this->results)) {
            $row = $this->results[$this->currentRow++];
            if (is_array($row)) {
                return array_values($row)[$column] ?? null;
            }
        }
        return false;
    }

    public function rowCount()
    {
        return count($this->results);
    }

    public function bindParam($param, &$var, $type = PDO::PARAM_STR, $maxLength = null, $driverOptions = null)
    {
        $this->boundParams[$param] = &$var;
        return true;
    }

    public function bindValue($param, $value, $type = PDO::PARAM_STR)
    {
        $this->boundParams[$param] = $value;
        return true;
    }

    public function closeCursor()
    {
        $this->currentRow = 0;
        return true;
    }
}
