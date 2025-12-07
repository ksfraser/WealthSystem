<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\AI\AIClient;
use App\AI\LLMTradingAssistantWithFundamentals;
use App\AI\Providers\OpenAIProvider;
use App\AI\Providers\AnthropicProvider;
use App\Data\AlphaVantageFundamentalProvider;
use App\Data\FundamentalDataService;

/**
 * Examples demonstrating fundamental data integration with trading assistant
 * 
 * Prerequisites:
 * - OPENAI_API_KEY or ANTHROPIC_API_KEY environment variable
 * - ALPHA_VANTAGE_API_KEY environment variable
 */

// Load environment variables
$openaiKey = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');
$anthropicKey = $_ENV['ANTHROPIC_API_KEY'] ?? getenv('ANTHROPIC_API_KEY');
$alphaVantageKey = $_ENV['ALPHA_VANTAGE_API_KEY'] ?? getenv('ALPHA_VANTAGE_API_KEY');

echo "=== LLM Trading Assistant with Fundamental Data Examples ===\n\n";

// ============================================================================
// Example 1: Basic fundamental data fetching
// ============================================================================

echo "Example 1: Fetching Fundamental Data\n";
echo str_repeat("=", 80) . "\n";

if ($alphaVantageKey) {
    $fundamentalProvider = new AlphaVantageFundamentalProvider($alphaVantageKey);
    $fundamentalService = new FundamentalDataService([$fundamentalProvider]);
    
    $ticker = 'AAPL';
    echo "Fetching fundamentals for {$ticker}...\n";
    
    $data = $fundamentalService->getFundamentals($ticker);
    
    if ($data->isValid()) {
        echo "Company: {$data->companyName}\n";
        echo "Sector: {$data->sector}\n";
        echo "Market Cap: $" . number_format($data->marketCap) . "\n";
        
        echo "\nKey Ratios:\n";
        foreach ($data->ratios as $ratio => $value) {
            echo "  {$ratio}: {$value}\n";
        }
        
        echo "\nGrowth Metrics:\n";
        foreach ($data->growth as $metric => $value) {
            echo "  {$metric}: {$value}\n";
        }
        
        echo "\nPrompt Format:\n";
        echo $data->toPromptString() . "\n";
    } else {
        echo "Error: {$data->error}\n";
    }
} else {
    echo "ALPHA_VANTAGE_API_KEY not set, skipping example\n";
}

echo "\n\n";

// ============================================================================
// Example 2: Trading recommendations with fundamental data
// ============================================================================

echo "Example 2: Trading Recommendations with Fundamentals\n";
echo str_repeat("=", 80) . "\n";

if ($alphaVantageKey && ($openaiKey || $anthropicKey)) {
    // Setup AI client
    $aiProviders = [];
    if ($openaiKey) {
        $aiProviders[] = new OpenAIProvider($openaiKey, 'gpt-4');
    }
    if ($anthropicKey) {
        $aiProviders[] = new AnthropicProvider($anthropicKey, 'claude-3-sonnet-20240229');
    }
    $aiClient = new AIClient($aiProviders);
    
    // Setup fundamental data service
    $fundamentalProvider = new AlphaVantageFundamentalProvider($alphaVantageKey);
    $fundamentalService = new FundamentalDataService([$fundamentalProvider]);
    
    // Create enhanced trading assistant
    $assistant = new LLMTradingAssistantWithFundamentals(
        $aiClient,
        $fundamentalService
    );
    
    // Example portfolio
    $holdings = [
        [
            'ticker' => 'AAPL',
            'shares' => 10,
            'average_price' => 150.00,
            'current_price' => 175.00,
            'unrealized_pnl' => 250.00,
            'unrealized_pnl_pct' => 16.67
        ]
    ];
    
    $cashBalance = 5000.00;
    $totalEquity = 6750.00;
    
    // Configuration with watchlist
    $config = [
        'prompt_style' => 'enhanced',
        'use_fundamentals' => true,
        'watchlist' => ['MSFT', 'GOOGL'], // Additional tickers to analyze
        'max_position_size_pct' => 10,
        'min_confidence' => 0.70,
        'focus' => 'large-cap tech', // For this example
    ];
    
    echo "Getting recommendations with fundamental data...\n";
    echo "Watchlist: " . implode(', ', $config['watchlist']) . "\n";
    echo "Holdings: AAPL (10 shares @ \$175)\n";
    echo "Cash: \${$cashBalance}\n\n";
    
    $recommendation = $assistant->getRecommendations(
        $holdings,
        $cashBalance,
        $totalEquity,
        $config
    );
    
    if ($recommendation->isValid()) {
        echo "Market Analysis:\n";
        echo $recommendation->analysis . "\n\n";
        
        echo "Recommended Trades:\n";
        foreach ($recommendation->trades as $trade) {
            echo "  Action: {$trade->action}\n";
            echo "  Ticker: {$trade->ticker}\n";
            echo "  Shares: {$trade->shares}\n";
            echo "  Entry Price: \${$trade->price}\n";
            echo "  Stop Loss: \${$trade->stopLoss} (" . 
                 number_format($trade->getStopLossPercent(), 2) . "%)\n";
            
            if ($trade->priceTarget > 0) {
                echo "  Price Target: \${$trade->priceTarget} (+" . 
                     number_format($trade->getUpsidePotential(), 2) . "%)\n";
                echo "  Risk/Reward: " . 
                     number_format($trade->getRiskRewardRatio(), 2) . ":1\n";
            }
            
            // Show fundamental analysis if present
            if ($trade->fundamentals) {
                echo "  Fundamentals:\n";
                foreach ($trade->fundamentals as $key => $value) {
                    if ($key !== 'note') {
                        echo "    {$key}: {$value}\n";
                    }
                }
            }
            
            if ($trade->bullCase) {
                echo "  Bull Thesis: {$trade->bullCase['thesis']}\n";
            }
            
            if ($trade->bearCase) {
                echo "  Bear Thesis: {$trade->bearCase['thesis']}\n";
            }
            
            echo "  Confidence: " . ($trade->tradeConfidence * 100) . "%\n";
            echo "\n";
        }
        
        echo "Overall Confidence: " . ($recommendation->confidence * 100) . "%\n";
    } else {
        echo "Error: {$recommendation->error}\n";
    }
} else {
    echo "API keys not set, skipping example\n";
    if (!$alphaVantageKey) echo "  Missing: ALPHA_VANTAGE_API_KEY\n";
    if (!$openaiKey && !$anthropicKey) echo "  Missing: OPENAI_API_KEY or ANTHROPIC_API_KEY\n";
}

echo "\n\n";

// ============================================================================
// Example 3: Batch fundamental data with caching
// ============================================================================

echo "Example 3: Batch Fundamental Data with Caching\n";
echo str_repeat("=", 80) . "\n";

if ($alphaVantageKey) {
    $fundamentalProvider = new AlphaVantageFundamentalProvider($alphaVantageKey);
    $fundamentalService = new FundamentalDataService([$fundamentalProvider]);
    
    $tickers = ['AAPL', 'MSFT', 'GOOGL'];
    echo "Fetching fundamentals for: " . implode(', ', $tickers) . "\n";
    echo "Note: Alpha Vantage free tier has rate limits (5/min, 25/day)\n";
    echo "This will take ~24 seconds due to rate limiting...\n\n";
    
    $startTime = microtime(true);
    $fundamentals = $fundamentalService->getBatchFundamentals($tickers);
    $duration = microtime(true) - $startTime;
    
    echo "Fetched in " . number_format($duration, 2) . " seconds\n\n";
    
    foreach ($fundamentals as $ticker => $data) {
        echo "{$ticker}: ";
        if ($data->isValid()) {
            echo "{$data->companyName} - {$data->sector}\n";
            echo "  Market Cap: $" . number_format($data->marketCap) . "\n";
            echo "  P/E Ratio: " . ($data->ratios['pe_ratio'] ?? 'N/A') . "\n";
            echo "  ROE: " . ($data->ratios['roe'] ?? 'N/A') . "%\n";
        } else {
            echo "Error: {$data->error}\n";
        }
        echo "\n";
    }
    
    // Cache statistics
    echo "Cache Statistics:\n";
    $stats = $fundamentalService->getCacheStats();
    echo "  Total Cached: {$stats['total_cached']}\n";
    echo "  Valid: {$stats['valid']}\n";
    echo "  Stale: {$stats['stale']}\n";
    echo "  Invalid: {$stats['invalid']}\n";
    echo "  TTL: {$stats['ttl_seconds']} seconds\n";
    
    // Fetch again (should be instant from cache)
    echo "\nFetching same data again (from cache)...\n";
    $startTime = microtime(true);
    $cachedFundamentals = $fundamentalService->getBatchFundamentals($tickers);
    $duration = microtime(true) - $startTime;
    echo "Fetched in " . number_format($duration, 4) . " seconds (from cache)\n";
} else {
    echo "ALPHA_VANTAGE_API_KEY not set, skipping example\n";
}

echo "\n\n";

// ============================================================================
// Example 4: Comparing with and without fundamental data
// ============================================================================

echo "Example 4: Comparing With and Without Fundamental Data\n";
echo str_repeat("=", 80) . "\n";

if ($alphaVantageKey && ($openaiKey || $anthropicKey)) {
    // Setup
    $aiProviders = [];
    if ($openaiKey) {
        $aiProviders[] = new OpenAIProvider($openaiKey, 'gpt-4');
    }
    if ($anthropicKey) {
        $aiProviders[] = new AnthropicProvider($anthropicKey, 'claude-3-sonnet-20240229');
    }
    $aiClient = new AIClient($aiProviders);
    
    $fundamentalProvider = new AlphaVantageFundamentalProvider($alphaVantageKey);
    $fundamentalService = new FundamentalDataService([$fundamentalProvider]);
    
    $assistant = new LLMTradingAssistantWithFundamentals(
        $aiClient,
        $fundamentalService
    );
    
    $holdings = [];
    $cashBalance = 10000.00;
    $totalEquity = 10000.00;
    
    // WITHOUT fundamental data
    echo "Getting recommendations WITHOUT fundamental data...\n";
    $config1 = [
        'use_fundamentals' => false,
        'watchlist' => ['AAPL'],
        'prompt_style' => 'simple',
    ];
    
    $rec1 = $assistant->getRecommendations($holdings, $cashBalance, $totalEquity, $config1);
    echo "Trades recommended: " . count($rec1->trades) . "\n";
    echo "Analysis quality: " . ($rec1->confidence * 100) . "% confidence\n";
    echo "Contains 'Data unavailable': " . (strpos($rec1->analysis, 'Data unavailable') !== false ? 'Yes' : 'No') . "\n";
    echo "\n";
    
    // WITH fundamental data
    echo "Getting recommendations WITH fundamental data...\n";
    $config2 = [
        'use_fundamentals' => true,
        'watchlist' => ['AAPL'],
        'prompt_style' => 'enhanced',
    ];
    
    $rec2 = $assistant->getRecommendations($holdings, $cashBalance, $totalEquity, $config2);
    echo "Trades recommended: " . count($rec2->trades) . "\n";
    echo "Analysis quality: " . ($rec2->confidence * 100) . "% confidence\n";
    echo "Contains 'Data unavailable': " . (strpos($rec2->analysis, 'Data unavailable') !== false ? 'Yes' : 'No') . "\n";
    
    if (count($rec2->trades) > 0) {
        $trade = $rec2->trades[0];
        echo "\nFundamental data included: " . ($trade->fundamentals !== null ? 'Yes' : 'No') . "\n";
        if ($trade->fundamentals) {
            echo "Fundamental metrics:\n";
            foreach ($trade->fundamentals as $key => $value) {
                echo "  {$key}: {$value}\n";
            }
        }
    }
} else {
    echo "API keys not set, skipping example\n";
}

echo "\n\n";

// ============================================================================
// Example 5: Provider rate limit information
// ============================================================================

echo "Example 5: Provider Rate Limit Information\n";
echo str_repeat("=", 80) . "\n";

if ($alphaVantageKey) {
    $fundamentalProvider = new AlphaVantageFundamentalProvider($alphaVantageKey);
    $fundamentalService = new FundamentalDataService([$fundamentalProvider]);
    
    echo "Available Providers:\n";
    foreach ($fundamentalService->getAvailableProviders() as $providerName) {
        echo "  - {$providerName}\n";
    }
    
    echo "\nRate Limits:\n";
    foreach ($fundamentalService->getProviderRateLimits() as $provider => $limits) {
        echo "  {$provider}:\n";
        echo "    Calls per day: {$limits['calls_per_day']}\n";
        echo "    Calls per minute: {$limits['calls_per_minute']}\n";
    }
    
    echo "\nRecommendations:\n";
    echo "  - Cache fundamental data (1 hour TTL by default)\n";
    echo "  - Fetch data in batches with rate limit delays\n";
    echo "  - Use multiple providers for redundancy\n";
    echo "  - Consider paid tiers for higher limits\n";
} else {
    echo "ALPHA_VANTAGE_API_KEY not set, skipping example\n";
}

echo "\n\n";
echo "=== Examples Complete ===\n";
