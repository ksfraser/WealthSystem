<?php

namespace App\Services\Interfaces;

/**
 * Authentication Service Interface
 * 
 * Defines authentication and authorization operations.
 * Follows Single Responsibility Principle (SRP).
 */
interface AuthenticationServiceInterface
{
    /**
     * Authenticate user with credentials
     */
    public function authenticate(string $identifier, string $password): bool;
    
    /**
     * Get currently authenticated user
     */
    public function getCurrentUser(): ?object;
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool;
    
    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool;
    
    /**
     * Check if user has permission
     */
    public function hasPermission(string $permission): bool;
    
    /**
     * Logout current user
     */
    public function logout(): bool;
    
    /**
     * Get user ID of current user
     */
    public function getCurrentUserId(): ?int;
}