<?php

namespace Ksfraser\StockInfo;

use PDO;
use PDOException;

/**
 * Base Model class for legacy stock information models
 * 
 * Provides common database connection and CRUD operations
 */
abstract class BaseModel
{
    protected $pdo;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Find a record by primary key
     */
    public function find($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Find all records
     */
    public function all()
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Find records with WHERE conditions
     */
    public function where($conditions = [], $limit = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }

        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Create a new record
     */
    public function create(array $data)
    {
        $filteredData = array_intersect_key($data, array_flip($this->fillable));
        
        $fields = implode(',', array_keys($filteredData));
        $placeholders = ':' . implode(', :', array_keys($filteredData));
        
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($filteredData);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new \Exception("Failed to create record: " . $e->getMessage());
        }
    }

    /**
     * Update a record
     */
    public function update($id, array $data)
    {
        $filteredData = array_intersect_key($data, array_flip($this->fillable));
        
        $setClause = [];
        foreach (array_keys($filteredData) as $field) {
            $setClause[] = "{$field} = :{$field}";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE {$this->primaryKey} = :id";
        $filteredData['id'] = $id;
        
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($filteredData);
        } catch (PDOException $e) {
            throw new \Exception("Failed to update record: " . $e->getMessage());
        }
    }

    /**
     * Delete a record
     */
    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Execute custom query
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get total count of records
     */
    public function count($conditions = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result->count;
    }
}
