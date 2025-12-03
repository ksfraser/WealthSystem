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
        $cards = [];
        
        // Account Management Card
        $accountCard = new DashboardCard(
            'card.admin.accounts',
            '🏦 Account Management',
            'Manage account types, brokerages, and bank accounts.',
            '🏦',
            'admin_bank_accounts.php',
            'admin',
            5
        );
        $accountCard->setActions([
            ['url' => 'admin_account_types.php', 'label' => '📋 Account Types'],
            ['url' => 'admin_brokerages.php', 'label' => '🏢 Brokerages'],
            ['url' => 'admin_bank_accounts.php', 'label' => '🏪 Bank Accounts']
        ]);
        $cards[] = $accountCard;
        
        // Admin Tools Card
        $toolsCard = new DashboardCard(
            'card.admin.tools',
            '🔧 Admin Tools',
            'Administrative functions for managing users and system settings.',
            '🔧',
            'admin_users.php',
            'admin',
            6
        );
        $toolsCard->setActions([
            ['url' => 'admin_users.php', 'label' => '👥 User Management'],
            ['url' => 'admin_system.php', 'label' => '⚙️ System Settings'],
            ['url' => 'database.php', 'label' => '🗄️ Database Management']
        ]);
        $cards[] = $toolsCard;
        
        return $cards;
    }
}
