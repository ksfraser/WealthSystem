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
            'Generate reports, view performance charts, and analyze your investment strategy.',
            '📊',
            'reports.php',
            null,
            4
        );
        
        $card->setActions([
            ['url' => '../Scripts and CSV Files/Generate_Graph.py', 'label' => '📈 Performance Charts'],
            ['url' => 'reports.php', 'label' => '📋 Custom Reports']
        ]);
        
        return [$card];
    }
}
