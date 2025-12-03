<?php
require_once __DIR__ . '/../Models/MenuItem.php';
require_once __DIR__ . '/../Models/DashboardCard.php';
require_once __DIR__ . '/NavigationItemProvider.php';

/**
 * Admin Items Provider
 * Returns navigation items for administrative functions
 */
class AdminItemsProvider implements NavigationItemProvider {
    
    public function getMenuItems(): array {
        $adminMenu = new MenuItem(
            'admin',
            'Administration',
            'Administrative functions',
            '⚙️',
            '#',
            'admin', // Requires admin role
            5
        );
        
        $adminMenu->addChild(new MenuItem(
            'admin.bank_accounts',
            'Bank Accounts',
            'Manage bank accounts',
            '🏦',
            'admin_bank_accounts.php',
            'admin',
            1
        ));
        
        $adminMenu->addChild(new MenuItem(
            'admin.brokerages',
            'Brokerages',
            'Manage brokerage accounts',
            '🏢',
            'admin_brokerages.php',
            'admin',
            2
        ));
        
        $adminMenu->addChild(new MenuItem(
            'admin.users',
            'User Management',
            'Manage system users',
            '👥',
            'admin_users.php',
            'admin',
            3
        ));
        
        return [$adminMenu];
    }
    
    public function getDashboardCards(): array {
        $card = new DashboardCard(
            'card.admin',
            '⚙️ Account Management',
            'Manage bank accounts, brokerages, and system settings.',
            '⚙️',
            'admin_bank_accounts.php',
            'admin',
            8
        );
        
        $card->setActions([
            ['url' => 'admin_bank_accounts.php', 'label' => 'Bank Accounts', 'class' => 'btn-primary'],
            ['url' => 'admin_brokerages.php', 'label' => 'Brokerages', 'class' => 'btn-secondary']
        ]);
        
        return [$card];
    }
}
