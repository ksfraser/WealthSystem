<?php
require_once __DIR__ . '/../Models/MenuItem.php';
require_once __DIR__ . '/../Models/DashboardCard.php';
require_once __DIR__ . '/NavigationItemProvider.php';

/**
 * Data Management Items Provider
 * Returns navigation items for data import and export
 */
class DataManagementItemsProvider implements NavigationItemProvider {
    
    public function getMenuItems(): array {
        $menuItem = new MenuItem(
            'data_management',
            'Data Management',
            'Import and export portfolio data',
            '📥',
            'import_export.php',
            null,
            3
        );
        
        return [$menuItem];
    }
    
    public function getDashboardCards(): array {
        $card = new DashboardCard(
            'card.data_management',
            '📥 Data Management',
            'Import and export your portfolio data.',
            '📥',
            'import_export.php',
            null,
            6
        );
        
        $card->setActions([
            ['url' => 'import_export.php', 'label' => 'Import/Export', 'class' => 'btn-primary']
        ]);
        
        return [$card];
    }
}
