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
            'Import transaction data and account holdings from your brokerages and banks.',
            '📥',
            'bank_import.php',
            null,
            3
        );
        
        $card->setActions([
            ['url' => 'bank_import.php', 'label' => '💾 Bank CSV Import'],
            ['url' => 'trades.php', 'label' => '📝 Trade Log']
        ]);
        
        return [$card];
    }
}
