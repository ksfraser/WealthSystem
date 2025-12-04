<?php

namespace App\Services;

/**
 * MenuService - Centralized menu generation for all pages
 * 
 * Single Responsibility: Generate navigation menu items based on user permissions
 * 
 * Design Principles:
 * - SRP: Only handles menu item generation
 * - Static methods for stateless menu generation
 * - Permission-based filtering
 * 
 * @package App\Services
 * @version 1.0.0
 */
class MenuService
{
    /**
     * Get menu items for navigation based on current page and user permissions
     * 
     * @param string $currentPage Current page identifier
     * @param bool $isAdmin Whether user has admin privileges
     * @param bool $isAuthenticated Whether user is logged in
     * @return array Array of menu items with url, label, active status
     */
    public static function getMenuItems(string $currentPage, bool $isAdmin, bool $isAuthenticated): array
    {
        $items = [];
        
        if ($isAuthenticated) {
            $items[] = [
                'url' => 'index.php',
                'label' => '🏠 Dashboard',
                'active' => $currentPage === 'dashboard'
            ];
            
            $items[] = [
                'url' => 'portfolios.php',
                'label' => '📈 Portfolios',
                'active' => $currentPage === 'portfolios'
            ];
            
            $items[] = [
                'url' => 'trades.php',
                'label' => '📋 Trades',
                'active' => $currentPage === 'trades'
            ];
            
            $items[] = [
                'url' => 'analytics.php',
                'label' => '📊 Analytics',
                'active' => $currentPage === 'analytics'
            ];
            
            $items[] = [
                'url' => 'sector_analysis.php',
                'label' => '🎯 Sector Analysis',
                'active' => $currentPage === 'sector_analysis'
            ];
            
            $items[] = [
                'url' => 'index_benchmark.php',
                'label' => '📉 Index Benchmark',
                'active' => $currentPage === 'index_benchmark'
            ];
            
            if ($isAdmin) {
                $items[] = [
                    'url' => 'admin_users.php',
                    'label' => '👥 Users',
                    'active' => $currentPage === 'users',
                    'admin_only' => true
                ];
                
                $items[] = [
                    'url' => 'system_status.php',
                    'label' => '⚙️ System',
                    'active' => $currentPage === 'system',
                    'admin_only' => true
                ];
                
                $items[] = [
                    'url' => 'database.php',
                    'label' => '💾 Database',
                    'active' => $currentPage === 'database',
                    'admin_only' => true
                ];
            }
        }
        
        return $items;
    }
    
    /**
     * Filter menu items based on admin status
     * 
     * @param array $items Menu items
     * @param bool $isAdmin Whether user is admin
     * @return array Filtered menu items
     */
    public static function filterAdminItems(array $items, bool $isAdmin): array
    {
        if ($isAdmin) {
            return $items;
        }
        
        return array_filter($items, function($item) {
            return !isset($item['admin_only']) || !$item['admin_only'];
        });
    }
    
    /**
     * Get active menu item from items array
     * 
     * @param array $items Menu items
     * @return array|null Active menu item or null
     */
    public static function getActiveItem(array $items): ?array
    {
        foreach ($items as $item) {
            if ($item['active'] ?? false) {
                return $item;
            }
        }
        
        return null;
    }
}
