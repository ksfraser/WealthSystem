<?php
require_once __DIR__ . '/../Models/MenuItem.php';
require_once __DIR__ . '/../Models/DashboardCard.php';
require_once __DIR__ . '/NavigationItemProvider.php';

/**
 * Reports Items Provider
 * Returns navigation items for reports and analytics
 */
class ReportsItemsProvider implements NavigationItemProvider {
    
    public function getMenuItems(): array {
        $menuItem = new MenuItem(
            'reports',
            'Reports',
            'View portfolio reports and analytics',
            '📊',
            'reports.php',
            null,
            4
        );
        
        return [$menuItem];
    }
    
    public function getDashboardCards(): array {
        $card = new DashboardCard(
            'card.reports',
            '📊 Reports',
            'View detailed portfolio reports and analytics.',
            '📊',
            'reports.php',
            null,
            7
        );
        
        $card->setActions([
            ['url' => 'reports.php', 'label' => 'View Reports', 'class' => 'btn-primary']
        ]);
        
        return [$card];
    }
}
