<?php

namespace App\Core\Interfaces;

use App\Core\Interfaces\ModelInterface;

/**
 * Repository Interface
 * 
 * Defines the contract for data repositories following Repository Pattern.
 * Follows Dependency Inversion Principle (DIP) - high-level modules depend on abstraction.
 */
interface RepositoryInterface 
{
    /**
     * Find entity by ID
     */
    public function findById(int $id): ?ModelInterface;
    
    /**
     * Find entity by criteria
     */
    public function findBy(array $criteria): array;
    
    /**
     * Find one entity by criteria
     */
    public function findOneBy(array $criteria): ?ModelInterface;
    
    /**
     * Find all entities
     */
    public function findAll(): array;
    
    /**
     * Create new entity
     */
    public function create(array $data): ModelInterface;
    
    /**
     * Update entity by ID
     */
    public function update(int $id, array $data): bool;
    
    /**
     * Delete entity by ID
     */
    public function delete(int $id): bool;
    
    /**
     * Count entities
     */
    public function count(array $criteria = []): int;
}