<?php

namespace App\Services\Interfaces;

/**
 * Navigation Service Interface
 * 
 * Defines navigation menu generation and management.
 * Handles dynamic menu building based on user permissions.
 */
interface NavigationServiceInterface
{
    /**
     * Get navigation menu for authenticated user
     */
    public function getNavigationMenu(): array;
    
    /**
     * Get breadcrumbs for current page
     */
    public function getBreadcrumbs(string $currentPage): array;
    
    /**
     * Check if menu item is active
     */
    public function isActive(string $menuItem, string $currentPage): bool;
    
    /**
     * Get user-specific menu items
     */
    public function getUserMenuItems(int $userId): array;
    
    /**
     * Get quick actions menu
     */
    public function getQuickActions(): array;
}