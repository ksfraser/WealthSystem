<?php

declare(strict_types=1);

namespace App\User;

/**
 * Authorization service for checking user permissions
 */
class AuthorizationService
{
    /**
     * Check if user has a specific role
     *
     * @param User $user
     * @param string $role
     * @return bool
     */
    public function hasRole(User $user, string $role): bool
    {
        return $user->hasRole($role);
    }
    
    /**
     * Check if user has any of the specified roles
     *
     * @param User $user
     * @param array $roles
     * @return bool
     */
    public function hasAnyRole(User $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user has all of the specified roles
     *
     * @param User $user
     * @param array $roles
     * @return bool
     */
    public function hasAllRoles(User $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$user->hasRole($role)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Check if user can execute trades
     *
     * @param User $user
     * @return bool
     */
    public function canTrade(User $user): bool
    {
        return $user->isActive() && $this->hasAnyRole($user, ['trader', 'admin']);
    }
    
    /**
     * Check if user can view all portfolios
     *
     * @param User $user
     * @return bool
     */
    public function canViewAllPortfolios(User $user): bool
    {
        return $user->isActive() && $this->hasRole($user, 'admin');
    }
    
    /**
     * Check if user can manage other users
     *
     * @param User $user
     * @return bool
     */
    public function canManageUsers(User $user): bool
    {
        return $user->isActive() && $this->hasRole($user, 'admin');
    }
}
