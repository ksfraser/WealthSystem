<?php

/**
 * KsFraser StockInfo Legacy Library - Usage Examples
 * 
 * This file demonstrates how to use the various features of the library
 */

require_once 'vendor/autoload.php';

use Ksfraser\StockInfo\StockInfoManager;

// Database configuration
$databaseConfig = [
    'host' => 'localhost',
    'database' => 'stock_market',
    'username' => 'your_username',
    'password' => 'your_password'
];

try {
    // Initialize the stock manager
    $stockManager = new StockInfoManager($databaseConfig);
    
    echo "=== KsFraser StockInfo Legacy Library Examples ===\n\n";

    // Example 1: Basic Stock Information
    echo "1. Getting Basic Stock Information:\n";
    echo "-----------------------------------\n";
    
    $stock = $stockManager->stockInfo()->findBySymbol('AAPL');
    if ($stock) {
        echo "Company: {$stock->corporatename}\n";
        echo "Symbol: {$stock->stocksymbol}\n";
        echo "Current Price: \${$stock->currentprice}\n";
        echo "Market Cap: \${$stock->marketcap}\n";
        echo "P/E Ratio: {$stock->peratio}\n";
        echo "Dividend: \${$stock->annualdividendpershare}\n\n";
    }

    // Example 2: Comprehensive Stock Analysis
    echo "2. Comprehensive Stock Analysis:\n";
    echo "--------------------------------\n";
    
    try {
        $analysis = $stockManager->getStockAnalysis('AAPL');
        echo "Stock: {$analysis['basic_info']->corporatename}\n";
        
        if ($analysis['evaluation']) {
            echo "Total Score: {$analysis['evaluation']->totalscore}\n";
            echo "Margin of Safety: \${$analysis['evaluation']->marginsafety}\n";
        }
        
        if ($analysis['technical_analysis']) {
            echo "RSI: {$analysis['technical_analysis']->rsi}\n";
            echo "20-day SMA: \${$analysis['technical_analysis']->sma_20}\n";
        }
        
        if ($analysis['calculated_metrics']['evaluation_grade']) {
            echo "Evaluation Grade: {$analysis['calculated_metrics']['evaluation_grade']}\n";
        }
        echo "\n";
    } catch (Exception $e) {
        echo "Analysis not available: {$e->getMessage()}\n\n";
    }

    // Example 3: Portfolio Analysis
    echo "3. Portfolio Analysis:\n";
    echo "---------------------\n";
    
    $portfolioData = $stockManager->getPortfolioAnalysis('kevin');
    if ($portfolioData['summary']) {
        $summary = $portfolioData['summary'];
        echo "Total Positions: {$summary->total_positions}\n";
        echo "Total Market Value: \${$summary->total_market_value}\n";
        echo "Total Book Value: \${$summary->total_book_value}\n";
        echo "Total P&L: \${$summary->total_profit_loss}\n";
        echo "Return %: {$summary->total_return_percent}%\n";
        
        if ($portfolioData['dividend_summary']) {
            $div = $portfolioData['dividend_summary'];
            echo "Annual Dividend Income: \${$div->total_annual_dividend}\n";
            echo "Portfolio Yield: {$div->portfolio_yield}%\n";
        }
        echo "\n";
    }

    // Example 4: Top Holdings
    echo "4. Top Portfolio Holdings:\n";
    echo "-------------------------\n";
    
    $topHoldings = $stockManager->portfolio()->getTopHoldings('kevin', 5);
    foreach ($topHoldings as $holding) {
        echo "{$holding->stocksymbol} - {$holding->corporatename}\n";
        echo "  Shares: {$holding->numbershares}\n";
        echo "  Market Value: \${$holding->marketvalue}\n";
        echo "  P&L: \${$holding->profitloss}\n\n";
    }

    // Example 5: Market Overview
    echo "5. Market Overview:\n";
    echo "------------------\n";
    
    $overview = $stockManager->getMarketOverview();
    if ($overview['stock_statistics']) {
        $stats = $overview['stock_statistics'];
        echo "Total Stocks: {$stats->total_stocks}\n";
        echo "Active Stocks: {$stats->active_stocks}\n";
        echo "Average Price: \${$stats->avg_price}\n";
        echo "Average Market Cap: \${$stats->avg_market_cap}\n";
        echo "Average P/E Ratio: {$stats->avg_pe_ratio}\n\n";
    }

    // Example 6: Top Rated Stocks
    echo "6. Top Rated Stocks:\n";
    echo "-------------------\n";
    
    $topRated = $stockManager->evalSummary()->getTopRatedStocks(5);
    foreach ($topRated as $stock) {
        echo "{$stock->stocksymbol} - {$stock->corporatename}\n";
        echo "  Score: {$stock->totalscore}\n";
        echo "  Price: \${$stock->currentprice}\n";
        echo "  Margin of Safety: \${$stock->marginsafety}\n\n";
    }

    // Example 7: High Dividend Stocks
    echo "7. High Dividend Yield Stocks (>4%):\n";
    echo "------------------------------------\n";
    
    $highDividend = $stockManager->stockInfo()->getHighDividendStocks(4.0);
    foreach (array_slice($highDividend, 0, 5) as $stock) {
        $yield = ($stock->annualdividendpershare / $stock->currentprice) * 100;
        echo "{$stock->stocksymbol} - {$stock->corporatename}\n";
        echo "  Dividend Yield: " . number_format($yield, 2) . "%\n";
        echo "  Annual Dividend: \${$stock->annualdividendpershare}\n";
        echo "  Current Price: \${$stock->currentprice}\n\n";
    }

    // Example 8: Technical Analysis Signals
    echo "8. Technical Analysis Signals:\n";
    echo "------------------------------\n";
    
    $today = date('Y-m-d');
    
    // Golden Cross signals
    $goldenCross = $stockManager->technicalAnalysis()->getGoldenCrossStocks($today);
    echo "Golden Cross Signals: " . count($goldenCross) . " stocks\n";
    
    // Oversold stocks
    $oversold = $stockManager->technicalAnalysis()->getOversoldStocks(30, $today);
    echo "Oversold Stocks (RSI < 30): " . count($oversold) . " stocks\n";
    
    // Overbought stocks
    $overbought = $stockManager->technicalAnalysis()->getOverboughtStocks(70, $today);
    echo "Overbought Stocks (RSI > 70): " . count($overbought) . " stocks\n\n";

    // Example 9: Transaction Analysis
    echo "9. Recent Transaction Activity:\n";
    echo "------------------------------\n";
    
    $recentTransactions = $stockManager->transaction()->getUserTransactions('kevin', 5);
    foreach ($recentTransactions as $transaction) {
        echo "Date: {$transaction->transactiondate}\n";
        echo "Stock: {$transaction->stocksymbol}\n";
        echo "Type: {$transaction->transactiontype}\n";
        echo "Shares: {$transaction->numbershares}\n";
        echo "Amount: \${$transaction->dollar}\n\n";
    }

    // Example 10: Search Functionality
    echo "10. Stock Search Examples:\n";
    echo "-------------------------\n";
    
    // Search by company name
    $searchResults = $stockManager->stockInfo()->searchByCompanyName('BANK', 3);
    echo "Stocks with 'BANK' in name:\n";
    foreach ($searchResults as $stock) {
        echo "  {$stock->stocksymbol} - {$stock->corporatename}\n";
    }
    echo "\n";

    // Search with multiple criteria
    $searchCriteria = [
        'evaluation' => [
            'min_total_score' => 5,
            'limit' => 5
        ],
        'max_pe_ratio' => 20,
        'min_dividend_yield' => 2.0
    ];
    
    $searchResults = $stockManager->searchStocks($searchCriteria);
    echo "Multi-criteria search results:\n";
    if (isset($searchResults['by_evaluation'])) {
        foreach ($searchResults['by_evaluation'] as $stock) {
            echo "  {$stock->stocksymbol} - Score: {$stock->totalscore}\n";
        }
    }
    echo "\n";

    // Example 11: Health Check
    echo "11. System Health Check:\n";
    echo "-----------------------\n";
    
    $healthCheck = $stockManager->healthCheck();
    echo "Database Status: {$healthCheck['database']['connection_status']}\n";
    echo "Database Version: {$healthCheck['database']['version']}\n";
    
    foreach ($healthCheck['models'] as $modelName => $status) {
        echo "Model {$modelName}: {$status['status']}";
        if ($status['status'] === 'OK') {
            echo " ({$status['record_count']} records)";
        }
        echo "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Please check your database configuration and ensure the database is accessible.\n";
}

echo "\n=== Examples Complete ===\n";
