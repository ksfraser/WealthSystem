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
            'Search stocks, get AI-powered recommendations, analyze sentiment, and view technical indicators with individual stock databases.',
            '🔍',
            'stock_analysis.php',
            null,
            2
        );
        
        $card->setActions([
            ['url' => 'stock_search.php', 'label' => '🔍 Stock Search'],
            ['url' => 'stock_analysis.php', 'label' => '🤖 Stock Analysis'],
            ['url' => 'stock_analysis.php?demo=1', 'label' => '🎯 Demo Analysis']
        ]);
        
        return [$card];
    }
}
