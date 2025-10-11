<?php

namespace App\Core\Interfaces;

/**
 * Model Interface
 * 
 * Defines the contract for all model classes.
 * Follows Interface Segregation Principle (ISP) with focused responsibilities.
 */
interface ModelInterface 
{
    /**
     * Convert model to array representation
     */
    public function toArray(): array;
    
    /**
     * Validate model data
     */
    public function validate(): bool;
    
    /**
     * Get validation errors
     */
    public function getErrors(): array;
}