<?php

namespace App\Core\Interfaces;

/**
 * Model Interface
 * 
 * Defines the contract for model entities.
 * Follows Single Responsibility Principle (SRP) and Interface Segregation Principle (ISP).
 */
interface ModelInterface 
{
    /**
     * Get model ID
     */
    public function getId(): ?int;
    
    /**
     * Set model ID
     */
    public function setId(int $id): void;
    
    /**
     * Get model attributes as array
     */
    public function toArray(): array;
    
    /**
     * Set attributes from array
     */
    public function fromArray(array $data): void;
    
    /**
     * Validate model data
     */
    public function validate(): array;
    
    /**
     * Check if model is valid
     */
    public function isValid(): bool;
}