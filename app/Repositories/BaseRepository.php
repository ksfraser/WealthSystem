<?php

namespace App\Repositories;

use App\Core\Interfaces\RepositoryInterface;
use App\Core\Interfaces\ModelInterface;
use PDO;
use Exception;

/**
 * Base Repository
 * 
 * Provides common database operations and connection management.
 * Follows Repository Pattern and implements basic CRUD operations.
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected PDO $db;
    protected string $table;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    /**
     * Create model instance from data
     */
    abstract protected function createModel(array $data = []): ModelInterface;
    
    /**
     * Find record by ID
     */
    public function findById(int $id): ?ModelInterface
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->createModel($result) : null;
    }
    
    /**
     * Find records by criteria
     */
    public function findBy(array $criteria): array
    {
        $conditions = [];
        $params = [];
        
        foreach ($criteria as $field => $value) {
            $conditions[] = "{$field} = ?";
            $params[] = $value;
        }
        
        $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
        $sql = "SELECT * FROM {$this->table} {$whereClause}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($row) {
            return $this->createModel($row);
        }, $results);
    }
    
    /**
     * Find one record by criteria
     */
    public function findOneBy(array $criteria): ?ModelInterface
    {
        $results = $this->findBy($criteria);
        return $results[0] ?? null;
    }
    
    /**
     * Find all records
     */
    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($row) {
            return $this->createModel($row);
        }, $results);
    }
    
    /**
     * Create new record
     */
    public function create(array $data): ModelInterface
    {
        $fields = array_keys($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        $sql = "INSERT INTO {$this->table} (" . implode(',', $fields) . ") VALUES ({$placeholders})";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute(array_values($data))) {
            $id = $this->db->lastInsertId();
            $result = $this->findById((int) $id);
            if ($result) {
                return $result;
            }
        }
        
        throw new Exception("Failed to create record");
    }
    
    /**
     * Update record by ID
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $params[] = $value;
        }
        
        $params[] = $id;
        
        $sql = "UPDATE {$this->table} SET " . implode(',', $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete record by ID
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Count records
     */
    public function count(array $criteria = []): int
    {
        $conditions = [];
        $params = [];
        
        foreach ($criteria as $field => $value) {
            $conditions[] = "{$field} = ?";
            $params[] = $value;
        }
        
        $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
        $sql = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Begin transaction
     */
    protected function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    protected function commit(): bool
    {
        return $this->db->commit();
    }
    
    /**
     * Rollback transaction
     */
    protected function rollback(): bool
    {
        return $this->db->rollBack();
    }
    
    /**
     * Execute custom query and return models
     */
    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($row) {
            return $this->createModel($row);
        }, $results);
    }
    
    /**
     * Execute custom query and return single model
     */
    protected function queryOne(string $sql, array $params = []): ?ModelInterface
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->createModel($result) : null;
    }
}