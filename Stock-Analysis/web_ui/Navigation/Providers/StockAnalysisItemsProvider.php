<?php
require_once __DIR__ . '/../Models/MenuItem.php';
require_once __DIR__ . '/../Models/DashboardCard.php';
require_once __DIR__ . '/NavigationItemProvider.php';

/**
 * Stock Analysis Items Provider
 * Returns navigation items for stock analysis and market data features
 */
class StockAnalysisItemsProvider implements NavigationItemProvider {
    
    public function getMenuItems(): array {
        $menuItem = new MenuItem(
            'stock_analysis',
            'Stock Analysis',
            'AI-powered stock analysis and screening',
            '🔍',
            'stock_analysis.php',
            null,
            2
        );
        
        return [$menuItem];
    }
    
    public function getDashboardCards(): array {
        $card = new DashboardCard(
            'card.stock_analysis',
            '🔍 Stock Analysis',
            'AI-powered stock analysis and screening tools.',
            '🔍',
            'stock_analysis.php',
            null,
            5
        );
        
        $card->setActions([
            ['url' => 'stock_analysis.php', 'label' => 'Analyze Stocks', 'class' => 'btn-primary']
        ]);
        
        return [$card];
    }
}
