<?php

namespace App\Services;

use App\Services\Interfaces\NavigationServiceInterface;
use App\Services\Interfaces\AuthenticationServiceInterface;

/**
 * Navigation Service
 * 
 * Handles navigation menu generation and management.
 * Provides role-based menu items and breadcrumb navigation.
 */
class NavigationService implements NavigationServiceInterface
{
    private AuthenticationServiceInterface $authService;
    
    // Menu configuration
    private const MENU_ITEMS = [
        'dashboard' => [
            'label' => 'Dashboard',
            'url' => '/',
            'icon' => 'dashboard',
            'permissions' => ['view_portfolio'],
            'order' => 10
        ],
        'portfolio' => [
            'label' => 'Portfolio',
            'url' => '/portfolio',
            'icon' => 'trending_up',
            'permissions' => ['view_portfolio'],
            'order' => 20
        ],
        'import' => [
            'label' => 'Import Data',
            'url' => '/import',
            'icon' => 'upload',
            'permissions' => ['import_data'],
            'order' => 30
        ],
        'reports' => [
            'label' => 'Reports',
            'url' => '/reports',
            'icon' => 'assessment',
            'permissions' => ['view_reports'],
            'order' => 40
        ],
        'settings' => [
            'label' => 'Settings',
            'url' => '/settings',
            'icon' => 'settings',
            'permissions' => ['manage_settings'],
            'order' => 50
        ]
    ];
    
    public function __construct(AuthenticationServiceInterface $authService)
    {
        $this->authService = $authService;
    }
    
    /**
     * Get navigation menu for authenticated user
     */
    public function getNavigationMenu(): array
    {
        if (!$this->authService->isAuthenticated()) {
            return [];
        }
        
        $menu = [];
        
        foreach (self::MENU_ITEMS as $key => $item) {
            if ($this->hasPermissionForMenuItem($item)) {
                $menu[] = [
                    'key' => $key,
                    'label' => $item['label'],
                    'url' => $item['url'],
                    'icon' => $item['icon'],
                    'order' => $item['order']
                ];
            }
        }
        
        // Sort by order
        usort($menu, function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });
        
        return $menu;
    }
    
    /**
     * Get breadcrumbs for current page
     */
    public function getBreadcrumbs(string $currentPage): array
    {
        $breadcrumbs = [
            ['label' => 'Home', 'url' => '/']
        ];
        
        $pageMap = [
            'dashboard' => [
                ['label' => 'Dashboard', 'url' => '/', 'current' => true]
            ],
            'portfolio' => [
                ['label' => 'Portfolio', 'url' => '/portfolio', 'current' => true]
            ],
            'import' => [
                ['label' => 'Import Data', 'url' => '/import', 'current' => true]
            ],
            'reports' => [
                ['label' => 'Reports', 'url' => '/reports', 'current' => true]
            ],
            'settings' => [
                ['label' => 'Settings', 'url' => '/settings', 'current' => true]
            ]
        ];
        
        if (isset($pageMap[$currentPage])) {
            // Remove the 'Home' breadcrumb if we're on a specific page
            if ($currentPage !== 'dashboard') {
                $breadcrumbs = array_merge($breadcrumbs, $pageMap[$currentPage]);
            } else {
                $breadcrumbs = $pageMap[$currentPage];
            }
        }
        
        return $breadcrumbs;
    }
    
    /**
     * Check if menu item is active
     */
    public function isActive(string $menuItem, string $currentPage): bool
    {
        return $menuItem === $currentPage;
    }
    
    /**
     * Get user-specific menu items
     */
    public function getUserMenuItems(int $userId): array
    {
        $baseMenu = $this->getNavigationMenu();
        
        // Add user-specific items
        $userMenu = [];
        
        if ($this->authService->hasRole('admin')) {
            $userMenu[] = [
                'label' => 'Admin Panel',
                'url' => '/admin',
                'icon' => 'admin_panel_settings',
                'order' => 100
            ];
        }
        
        return array_merge($baseMenu, $userMenu);
    }
    
    /**
     * Get quick actions menu
     */
    public function getQuickActions(): array
    {
        if (!$this->authService->isAuthenticated()) {
            return [];
        }
        
        $actions = [];
        
        if ($this->authService->hasPermission('import_data')) {
            $actions[] = [
                'label' => 'Import Bank Data',
                'url' => '/import',
                'icon' => 'upload_file'
            ];
        }
        
        if ($this->authService->hasPermission('view_reports')) {
            $actions[] = [
                'label' => 'Generate Report',
                'url' => '/reports/generate',
                'icon' => 'description'
            ];
        }
        
        $actions[] = [
            'label' => 'Refresh Data',
            'url' => '#',
            'icon' => 'refresh',
            'action' => 'refresh'
        ];
        
        return $actions;
    }
    
    /**
     * Check if user has permission for menu item
     */
    private function hasPermissionForMenuItem(array $menuItem): bool
    {
        if (!isset($menuItem['permissions']) || empty($menuItem['permissions'])) {
            return true; // No permissions required
        }
        
        foreach ($menuItem['permissions'] as $permission) {
            if ($this->authService->hasPermission($permission)) {
                return true;
            }
        }
        
        return false;
    }
}