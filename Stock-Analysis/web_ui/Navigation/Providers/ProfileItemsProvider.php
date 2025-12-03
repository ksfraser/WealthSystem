<?php
require_once __DIR__ . '/../Models/MenuItem.php';
require_once __DIR__ . '/../Models/DashboardCard.php';
require_once __DIR__ . '/NavigationItemProvider.php';

/**
 * Profile Items Provider
 * Returns navigation items for user profile and settings
 */
class ProfileItemsProvider implements NavigationItemProvider {
    
    public function getMenuItems(): array {
        $profileMenu = new MenuItem(
            'profile',
            'Profile',
            'User profile and settings',
            '👤',
            '#',
            null,
            6
        );
        
        $profileMenu->addChild(new MenuItem(
            'profile.view',
            'View Profile',
            'View your profile',
            '👤',
            'profile.php',
            null,
            1
        ));
        
        $profileMenu->addChild(new MenuItem(
            'profile.settings',
            'Settings',
            'Account settings',
            '⚙️',
            'settings.php',
            null,
            2
        ));
        
        $profileMenu->addChild(new MenuItem(
            'profile.logout',
            'Logout',
            'Sign out of your account',
            '🚪',
            'logout.php',
            null,
            3
        ));
        
        return [$profileMenu];
    }
    
    public function getDashboardCards(): array {
        $card = new DashboardCard(
            'card.profile',
            '👤 Profile & Invitations',
            'Manage your profile, invite friends and advisors, and upgrade your account.',
            '👤',
            'profile.php',
            null,
            8
        );
        
        $card->setActions([
            ['url' => 'profile.php', 'label' => '⚙️ Edit Profile'],
            ['url' => 'profile.php#invitations', 'label' => '📧 Manage Invitations'],
            ['url' => 'profile.php#upgrade', 'label' => '🎓 Become an Advisor']
        ]);
        
        return [$card];
    }
}
