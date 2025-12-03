<?php
require_once __DIR__ . '/../Models/MenuItem.php';
require_once __DIR__ . '/../Models/DashboardCard.php';
require_once __DIR__ . '/NavigationItemProvider.php';

/**
 * Portfolio Items Provider
 * Returns navigation items for portfolio management features
 */
class PortfolioItemsProvider implements NavigationItemProvider {
    
    public function getMenuItems(): array {
        $portfolioMenu = new MenuItem(
            'portfolio',
            'Portfolio',
            'Portfolio management and tracking',
            '💼',
            '#',
            null, // Available to all users
            1
        );
        
        $portfolioMenu->addChild(new MenuItem(
            'portfolio.my',
            'My Portfolio',
            'View your portfolio',
            '🏠',
            'MyPortfolio.php',
            null,
            1
        ));
        
        $portfolioMenu->addChild(new MenuItem(
            'portfolio.manage',
            'Manage Portfolios',
            'Create and manage portfolios',
            '📈',
            'portfolios.php',
            null,
            2
        ));
        
        $portfolioMenu->addChild(new MenuItem(
            'portfolio.trades',
            'Trades',
            'View trading history',
            '📋',
            'trades.php',
            null,
            3
        ));
        
        $portfolioMenu->addChild(new MenuItem(
            'portfolio.automation',
            'Automation',
            'Automated trading scripts',
            '🤖',
            'automation_scripts.php',
            null,
            4
        ));
        
        return [$portfolioMenu];
    }
    
    public function getDashboardCards(): array {
        $cards = [];
        
        // Portfolio Management - Combined Card
        $portfolioCard = new DashboardCard(
            'card.portfolio.management',
            '📈 Portfolio Management',
            'View and manage your investment portfolios, track performance, and analyze holdings.',
            '📈',
            'MyPortfolio.php',
            null,
            1
        );
        $portfolioCard->setActions([
            ['url' => 'MyPortfolio.php', 'label' => '🏠 My Portfolio'],
            ['url' => 'portfolios.php', 'label' => '📈 Manage Portfolios'],
            ['url' => 'trades.php', 'label' => '📋 Trades'],
            ['url' => '../simple_automation.py', 'label' => '🤖 Automation']
        ]);
        $cards[] = $portfolioCard;
        
        return $cards;
    }
}
