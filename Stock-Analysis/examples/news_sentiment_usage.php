<?php

/**
 * News Sentiment Analysis - Usage Examples
 * 
 * Demonstrates how to integrate news sentiment analysis into your trading system.
 * These examples show various patterns for using the sentiment features.
 * 
 * Requirements:
 * - Alpha Vantage API key (free tier: 25 calls/day)
 * - Set environment variable: ALPHA_VANTAGE_API_KEY
 * 
 * Get your free API key: https://www.alphavantage.co/support/#api-key
 */

require_once __DIR__ . '/../vendor/autoload.php';

use WealthSystem\StockAnalysis\Data\AlphaVantageNewsProvider;
use WealthSystem\StockAnalysis\Data\NewsSentimentService;
use WealthSystem\StockAnalysis\Data\AlphaVantageFundamentalProvider;
use WealthSystem\StockAnalysis\Data\FundamentalDataService;
use WealthSystem\StockAnalysis\AI\AIClient;
use WealthSystem\StockAnalysis\AI\Providers\OpenAIProvider;
use WealthSystem\StockAnalysis\AI\Providers\AnthropicProvider;
use WealthSystem\StockAnalysis\AI\LLMTradingAssistantWithSentiment;

// Check for API keys
$alphaVantageKey = getenv('ALPHA_VANTAGE_API_KEY');
$openaiKey = getenv('OPENAI_API_KEY');
$anthropicKey = getenv('ANTHROPIC_API_KEY');

if (!$alphaVantageKey) {
    echo "⚠️  ALPHA_VANTAGE_API_KEY not set. Get one at: https://www.alphavantage.co/support/#api-key\n\n";
    exit(1);
}

echo "=== News Sentiment Analysis - Usage Examples ===\n\n";

// ============================================================================
// Example 1: Basic News Sentiment Fetching
// ============================================================================
echo "--- Example 1: Basic News Sentiment Fetching ---\n\n";

$provider = new AlphaVantageNewsProvider($alphaVantageKey);
$service = new NewsSentimentService([$provider]);

$sentiment = $service->getSentiment('AAPL');

if ($sentiment->isValid()) {
    echo "Ticker: {$sentiment->ticker}\n";
    echo "Overall Sentiment: {$sentiment->getSentimentClassification()} ({$sentiment->getSentimentStrength()})\n";
    echo "Sentiment Score: " . number_format($sentiment->overallSentiment ?? 0, 3) . "\n";
    echo "Articles Analyzed: {$sentiment->articleCount}\n\n";

    echo "Recent Headlines:\n";
    foreach ($sentiment->getRecentArticles(3) as $i => $article) {
        echo "  " . ($i + 1) . ". {$article['title']}\n";
        echo "     Sentiment: " . number_format($article['sentiment'], 2) . ", Relevance: " . number_format($article['relevanceScore'], 2) . "\n";
        echo "     Source: {$article['source']}, Date: {$article['publishedAt']->format('M j, Y')}\n\n";
    }
} else {
    echo "❌ Error: {$sentiment->error}\n\n";
}

// ============================================================================
// Example 2: Trading Recommendations with Sentiment + Fundamentals
// ============================================================================
echo "\n--- Example 2: Trading Recommendations with Sentiment + Fundamentals ---\n\n";

if ($openaiKey || $anthropicKey) {
    // Setup AI client with multiple providers
    $aiProviders = [];
    if ($openaiKey) {
        $aiProviders[] = new OpenAIProvider($openaiKey, 'gpt-4');
    }
    if ($anthropicKey) {
        $aiProviders[] = new AnthropicProvider($anthropicKey, 'claude-3-sonnet-20240229');
    }
    $aiClient = new AIClient($aiProviders);

    // Setup data services
    $fundamentalService = new FundamentalDataService([
        new AlphaVantageFundamentalProvider($alphaVantageKey)
    ]);
    $sentimentService = new NewsSentimentService([
        new AlphaVantageNewsProvider($alphaVantageKey)
    ]);

    // Create assistant with both capabilities
    $assistant = new LLMTradingAssistantWithSentiment(
        $aiClient,
        $fundamentalService,
        $sentimentService
    );

    // Example portfolio
    $holdings = [
        ['ticker' => 'AAPL', 'shares' => 100, 'averageCost' => 150.00],
        ['ticker' => 'MSFT', 'shares' => 50, 'averageCost' => 300.00],
    ];
    $cashBalance = 10000.00;
    $totalEquity = 40000.00;

    echo "Getting AI recommendations with sentiment + fundamentals...\n\n";

    $recommendations = $assistant->getRecommendations(
        $holdings,
        $cashBalance,
        $totalEquity,
        [
            'use_fundamentals' => true,
            'use_sentiment' => true,
            'sentiment_time_range' => '3 days ago', // Last 3 days of news
            'watchlist' => ['NVDA', 'AMD'], // Additional stocks to analyze
            'prompt_style' => 'enhanced',
        ]
    );

    echo "Confidence: " . ($recommendations->confidence * 100) . "%\n";
    echo "Risk Level: {$recommendations->riskLevel}\n";
    echo "Market Timing: {$recommendations->marketTiming}\n\n";

    echo "Recommended Trades:\n";
    foreach ($recommendations->trades as $trade) {
        echo "  • {$trade->action} {$trade->quantity} shares of {$trade->ticker}\n";
        echo "    Rationale: {$trade->rationale}\n\n";
    }

    echo "Analysis:\n{$recommendations->analysis}\n\n";
} else {
    echo "⚠️  OpenAI or Anthropic API key needed for trading recommendations\n\n";
}

// ============================================================================
// Example 3: Batch Sentiment with Time Range Filtering
// ============================================================================
echo "\n--- Example 3: Batch Sentiment with Time Range Filtering ---\n\n";

$tickers = ['AAPL', 'MSFT', 'GOOGL'];

echo "Fetching sentiment for last 7 days...\n\n";

$sentiments = $service->getBatchSentiment($tickers, [
    'time_from' => '7 days ago',
    'limit' => 20, // Max 20 articles per ticker
]);

foreach ($sentiments as $ticker => $sentiment) {
    if ($sentiment->isValid() && $sentiment->articleCount > 0) {
        echo "{$ticker}:\n";
        echo "  Sentiment: {$sentiment->getSentimentClassification()} (Score: " . number_format($sentiment->overallSentiment ?? 0, 2) . ")\n";
        echo "  Articles: {$sentiment->articleCount}\n";
        
        $strong = $sentiment->getStrongSentimentArticles();
        if (!empty($strong)) {
            echo "  Strong sentiment articles: " . count($strong) . "\n";
        }
        echo "\n";
    } else {
        echo "{$ticker}: {$sentiment->error}\n\n";
    }
}

// ============================================================================
// Example 4: Comparing Multiple Time Ranges
// ============================================================================
echo "\n--- Example 4: Comparing Multiple Time Ranges ---\n\n";

$ticker = 'AAPL';

echo "Analyzing sentiment trends for {$ticker}...\n\n";

// Recent sentiment (last 3 days)
$recent = $service->getSentiment($ticker, ['time_from' => '3 days ago'], false);

// Older sentiment (4-7 days ago)
$older = $service->getSentiment($ticker, [
    'time_from' => '7 days ago',
    'time_to' => '4 days ago',
], false);

if ($recent->isValid() && $older->isValid()) {
    echo "Recent (last 3 days):\n";
    echo "  Sentiment: {$recent->getSentimentClassification()}\n";
    echo "  Score: " . number_format($recent->overallSentiment ?? 0, 3) . "\n";
    echo "  Articles: {$recent->articleCount}\n\n";

    echo "Older (4-7 days ago):\n";
    echo "  Sentiment: {$older->getSentimentClassification()}\n";
    echo "  Score: " . number_format($older->overallSentiment ?? 0, 3) . "\n";
    echo "  Articles: {$older->articleCount}\n\n";

    $recentScore = $recent->overallSentiment ?? 0;
    $olderScore = $older->overallSentiment ?? 0;
    $change = $recentScore - $olderScore;

    echo "Trend: ";
    if ($change > 0.1) {
        echo "📈 Improving (+" . number_format($change, 3) . ")\n";
    } elseif ($change < -0.1) {
        echo "📉 Declining (" . number_format($change, 3) . ")\n";
    } else {
        echo "➡️  Stable (" . number_format($change, 3) . ")\n";
    }
    echo "\n";
}

// ============================================================================
// Example 5: Cache Statistics and Management
// ============================================================================
echo "\n--- Example 5: Cache Statistics and Management ---\n\n";

echo "Initial cache stats:\n";
$stats = $service->getCacheStats();
echo "  Total: {$stats['total']}\n";
echo "  Valid: {$stats['valid']}\n";
echo "  Stale: {$stats['stale']}\n";
echo "  Invalid: {$stats['invalid']}\n\n";

// Adjust cache TTL (news changes frequently, so shorter TTL)
$service->setCacheTTL(1800); // 30 minutes
echo "Set cache TTL to 30 minutes (news data changes frequently)\n\n";

// Clear specific cache entry
$service->clearCache('AAPL');
echo "Cleared cache for AAPL\n\n";

// Clear all cache
$service->clearCache();
echo "Cleared all cache\n\n";

// ============================================================================
// Example 6: Provider Rate Limit Information
// ============================================================================
echo "\n--- Example 6: Provider Rate Limit Information ---\n\n";

echo "Available Providers:\n";
foreach ($service->getAvailableProviders() as $name) {
    echo "  ✓ {$name}\n";
}
echo "\n";

echo "Rate Limits:\n";
foreach ($service->getProviderRateLimits() as $provider => $limits) {
    echo "  {$provider}:\n";
    echo "    - {$limits['calls_per_day']} calls per day\n";
    echo "    - {$limits['calls_per_minute']} calls per minute\n";
    echo "    - Tier: {$limits['tier']}\n";
}
echo "\n";

echo "💡 Tip: With Alpha Vantage free tier (25 calls/day), use caching aggressively!\n";
echo "    For a 5-stock portfolio with sentiment analysis, that's 5 API calls.\n";
echo "    Cache TTL of 30 minutes is recommended for news data.\n\n";

echo "=== Examples Complete ===\n";
