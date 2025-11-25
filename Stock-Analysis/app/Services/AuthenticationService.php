<?php

namespace App\Services;

use App\Services\Interfaces\AuthenticationServiceInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;

/**
 * Authentication Service
 * 
 * Handles user authentication and authorization.
 * Implements session management and role-based access control.
 */
class AuthenticationService implements AuthenticationServiceInterface
{
    private UserRepositoryInterface $userRepository;
    private ?object $currentUser = null;
    
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->loadCurrentUser();
    }
    
    /**
     * Authenticate user with credentials
     */
    public function authenticate(string $identifier, string $password): bool
    {
        $user = $this->userRepository->verifyCredentials($identifier, $password);
        
        if ($user) {
            $this->setCurrentUser($user);
            $this->userRepository->updateLastLogin($user->id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get currently authenticated user
     */
    public function getCurrentUser(): ?object
    {
        return $this->currentUser;
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->currentUser !== null && isset($_SESSION['user_id']);
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        return $this->userRepository->hasRole($this->currentUser->id, $role);
    }
    
    /**
     * Check if user has permission
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        // Basic permission check - can be extended with more complex ACL
        $rolePermissions = [
            'admin' => ['*'], // Admin has all permissions
            'user' => ['view_portfolio', 'import_data', 'view_reports'],
            'viewer' => ['view_portfolio', 'view_reports']
        ];
        
        $userRole = $this->currentUser->role ?? 'user';
        $permissions = $rolePermissions[$userRole] ?? [];
        
        return in_array('*', $permissions) || in_array($permission, $permissions);
    }
    
    /**
     * Logout current user
     */
    public function logout(): bool
    {
        $this->currentUser = null;
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        return true;
    }
    
    /**
     * Get user ID of current user
     */
    public function getCurrentUserId(): ?int
    {
        return $this->currentUser !== null ? $this->currentUser->id : null;
    }
    
    /**
     * Set current user and create session
     */
    private function setCurrentUser(object $user): void
    {
        $this->currentUser = $user;
        
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_role'] = $user->role ?? 'user';
    }
    
    /**
     * Load current user from session
     */
    private function loadCurrentUser(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (isset($_SESSION['user_id'])) {
            $this->currentUser = $this->userRepository->findById($_SESSION['user_id']);
        }
    }
}