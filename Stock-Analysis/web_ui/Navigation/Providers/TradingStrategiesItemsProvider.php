<?php
require_once __DIR__ . '/../Models/MenuItem.php';
require_once __DIR__ . '/../Models/DashboardCard.php';
require_once __DIR__ . '/NavigationItemProvider.php';

/**
 * Trading Strategies Items Provider
 * Returns navigation items for trading strategy configuration
 */
class TradingStrategiesItemsProvider implements NavigationItemProvider {
    
    public function getMenuItems(): array {
        // Trading strategies don't have a main menu item
        // They're accessed through the dashboard or other menus
        return [];
    }
    
    public function getDashboardCards(): array {
        $card = new DashboardCard(
            'card.trading_strategies',
            '⚙️ Trading Strategies',
            'Configure and manage your automated trading strategy parameters.',
            '⚙️',
            'strategy-config.php',
            null,
            7
        );
        
        $card->setActions([
            ['url' => 'strategy-config.php', 'label' => '🎯 Strategy Configuration'],
            ['url' => 'stock_analysis.php', 'label' => '📈 Stock Analysis'],
            ['url' => 'job_manager.php', 'label' => '⏱️ Job Manager']
        ]);
        
        return [$card];
    }
}
