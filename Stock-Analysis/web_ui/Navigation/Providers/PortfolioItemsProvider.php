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
        
        // My Portfolio Card
        $myPortfolioCard = new DashboardCard(
            'card.portfolio.my',
            '🏠 My Portfolio',
            'View and manage your investment portfolio.',
            '🏠',
            'MyPortfolio.php',
            null,
            1
        );
        $myPortfolioCard->setActions([
            ['url' => 'MyPortfolio.php', 'label' => 'View Portfolio', 'class' => 'btn-primary']
        ]);
        $cards[] = $myPortfolioCard;
        
        // Manage Portfolios Card
        $manageCard = new DashboardCard(
            'card.portfolio.manage',
            '📈 Manage Portfolios',
            'Create and manage multiple portfolios.',
            '📈',
            'portfolios.php',
            null,
            2
        );
        $manageCard->setActions([
            ['url' => 'portfolios.php', 'label' => 'Manage', 'class' => 'btn-primary']
        ]);
        $cards[] = $manageCard;
        
        // Trades Card
        $tradesCard = new DashboardCard(
            'card.portfolio.trades',
            '📋 Trades',
            'View your trading history and performance.',
            '📋',
            'trades.php',
            null,
            3
        );
        $tradesCard->setActions([
            ['url' => 'trades.php', 'label' => 'View Trades', 'class' => 'btn-primary']
        ]);
        $cards[] = $tradesCard;
        
        // Automation Card
        $automationCard = new DashboardCard(
            'card.portfolio.automation',
            '🤖 Automation',
            'Set up and manage automated trading strategies.',
            '🤖',
            'automation_scripts.php',
            null,
            4
        );
        $automationCard->setActions([
            ['url' => 'automation_scripts.php', 'label' => 'Manage Scripts', 'class' => 'btn-primary']
        ]);
        $cards[] = $automationCard;
        
        return $cards;
    }
}
