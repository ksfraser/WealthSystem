<?php

namespace WealthSystem\StockAnalysis\AI;

use WealthSystem\StockAnalysis\Data\FundamentalDataService;
use WealthSystem\StockAnalysis\Data\NewsSentimentService;
use Psr\Log\LoggerInterface;

/**
 * LLM Trading Assistant with Fundamentals AND News Sentiment
 * 
 * Extends LLMTradingAssistantWithFundamentals to also include news sentiment analysis.
 * Combines both fundamental data and market sentiment for comprehensive AI recommendations.
 * 
 * Features:
 * - All fundamental data capabilities from parent class
 * - Real-time news sentiment analysis
 * - Combined sentiment + fundamentals in AI prompt
 * - Configurable (can disable either feature independently)
 * - Backward compatible with base assistant
 * 
 * Configuration options:
 * - 'use_fundamentals' => bool (default: true)
 * - 'use_sentiment' => bool (default: true)
 * - 'sentiment_time_range' => string (e.g., '2 days ago', '1 week ago')
 * - 'sentiment_limit' => int (max articles per ticker, default: 50)
 * - 'watchlist' => array (additional tickers to analyze)
 * 
 * Example usage:
 * ```php
 * $assistant = new LLMTradingAssistantWithSentiment(
 *     $aiClient,
 *     $fundamentalService,
 *     $sentimentService
 * );
 * 
 * $recommendations = $assistant->getRecommendations(
 *     $holdings,
 *     $cashBalance,
 *     $totalEquity,
 *     [
 *         'use_fundamentals' => true,
 *         'use_sentiment' => true,
 *         'sentiment_time_range' => '3 days ago',
 *         'watchlist' => ['NVDA', 'AMD'],
 *     ]
 * );
 * ```
 */
class LLMTradingAssistantWithSentiment extends LLMTradingAssistantWithFundamentals
{
    public function __construct(
        AIClient $aiClient,
        ?FundamentalDataService $fundamentalService = null,
        private readonly ?NewsSentimentService $sentimentService = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($aiClient, $fundamentalService, $logger);
    }

    /**
     * Generate recommendations with fundamentals AND sentiment
     * 
     * {@inheritdoc}
     */
    public function getRecommendations(
        array $holdings,
        float $cashBalance,
        float $totalEquity,
        array $config = []
    ): TradingRecommendation {
        // Check if sentiment is enabled
        $useSentiment = $config['use_sentiment'] ?? true;

        if (!$useSentiment || $this->sentimentService === null) {
            // Fall back to parent (fundamentals only)
            return parent::getRecommendations($holdings, $cashBalance, $totalEquity, $config);
        }

        // Extract tickers from holdings
        $holdingTickers = array_map(fn($holding) => $holding['ticker'], $holdings);

        // Add watchlist tickers if provided
        $watchlist = $config['watchlist'] ?? [];
        $allTickers = array_unique(array_merge($holdingTickers, $watchlist));

        // Prepare sentiment options
        $sentimentOptions = [];
        if (!empty($config['sentiment_time_range'])) {
            $sentimentOptions['time_from'] = $config['sentiment_time_range'];
        }
        if (!empty($config['sentiment_limit'])) {
            $sentimentOptions['limit'] = $config['sentiment_limit'];
        }

        // Fetch news sentiment (with caching)
        $this->logger->info("Fetching news sentiment for " . count($allTickers) . " tickers");
        $sentiments = [];
        if ($this->sentimentService->hasAvailableProvider()) {
            $sentiments = $this->sentimentService->getBatchSentiment($allTickers, $sentimentOptions);
        } else {
            $this->logger->warning("No sentiment providers available");
        }

        // Store sentiment in config for prompt enhancement
        $config['sentiments'] = $sentiments;

        // Call parent (which handles fundamentals and calls generatePromptEnhanced)
        return parent::getRecommendations($holdings, $cashBalance, $totalEquity, $config);
    }

    /**
     * Generate enhanced prompt with fundamentals AND sentiment
     * 
     * {@inheritdoc}
     */
    protected function generatePromptEnhanced(
        array $holdings,
        float $cashBalance,
        float $totalEquity,
        array $config = []
    ): string {
        // Get base prompt with fundamentals from parent
        $prompt = parent::generatePromptEnhanced($holdings, $cashBalance, $totalEquity, $config);

        // Add sentiment data if available
        $sentiments = $config['sentiments'] ?? [];
        if (!empty($sentiments)) {
            $prompt = $this->enhancePromptWithSentiment($prompt, $sentiments);
        }

        return $prompt;
    }

    /**
     * Enhance prompt with news sentiment data
     */
    private function enhancePromptWithSentiment(string $prompt, array $sentiments): string
    {
        $sentimentText = "\n\n=== NEWS SENTIMENT ANALYSIS ===\n\n";
        $sentimentText .= "Recent news sentiment for each ticker. Use this to gauge market mood and potential catalysts.\n";
        $sentimentText .= "Sentiment scores range from -1.0 (Bearish) to +1.0 (Bullish).\n";
        $sentimentText .= "DO NOT make up news or sentiment. Use ONLY the data provided below.\n\n";

        $hasAnySentiment = false;

        foreach ($sentiments as $ticker => $sentiment) {
            if ($sentiment->isValid() && $sentiment->articleCount > 0) {
                $hasAnySentiment = true;
                $sentimentText .= $sentiment->toPromptString() . "\n\n";
            } elseif ($sentiment->isValid() && $sentiment->articleCount === 0) {
                $sentimentText .= "{$ticker}: No recent news coverage\n\n";
            } else {
                $sentimentText .= "{$ticker}: Sentiment unavailable - {$sentiment->error}\n\n";
            }
        }

        // Only add section if we have at least some sentiment data
        if ($hasAnySentiment) {
            $sentimentText .= "=== TRADING INSTRUCTIONS ===\n\n";
            $sentimentText .= "When making recommendations:\n";
            $sentimentText .= "1. Consider BOTH fundamental data AND news sentiment\n";
            $sentimentText .= "2. Strong positive sentiment + strong fundamentals = Higher confidence BUY\n";
            $sentimentText .= "3. Strong negative sentiment despite good fundamentals = Caution (wait or reduce)\n";
            $sentimentText .= "4. Sentiment contradicting fundamentals = Potential opportunity or warning\n";
            $sentimentText .= "5. No news coverage = Less market attention, higher risk for small caps\n";
            $sentimentText .= "6. Always cite specific news headlines or sentiment scores when relevant\n\n";

            return $prompt . $sentimentText;
        }

        return $prompt;
    }

    /**
     * Check if sentiment service is available
     */
    public function hasSentimentService(): bool
    {
        return $this->sentimentService !== null && $this->sentimentService->hasAvailableProvider();
    }

    /**
     * Get sentiment service (for direct access if needed)
     */
    public function getSentimentService(): ?NewsSentimentService
    {
        return $this->sentimentService;
    }
}
