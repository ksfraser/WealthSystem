<?php

namespace App\Repositories\Interfaces;

use App\Core\Interfaces\RepositoryInterface;

/**
 * User Repository Interface
 * 
 * Defines contract for user data access and authentication.
 * Follows Interface Segregation Principle (ISP).
 */
interface UserRepositoryInterface extends RepositoryInterface 
{
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?object;
    
    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?object;
    
    /**
     * Verify user credentials
     */
    public function verifyCredentials(string $identifier, string $password): ?object;
    
    /**
     * Update user's last login timestamp
     */
    public function updateLastLogin(int $userId): bool;
    
    /**
     * Check if user has role
     */
    public function hasRole(int $userId, string $role): bool;
    
    /**
     * Find user by ID
     */
    public function findById(int $id): ?object;
}