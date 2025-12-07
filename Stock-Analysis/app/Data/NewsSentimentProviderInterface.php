<?php

namespace WealthSystem\StockAnalysis\Data;

/**
 * News Sentiment Data Value Object
 * 
 * Represents aggregated news sentiment for a ticker with individual articles
 * and overall sentiment metrics.
 */
readonly class NewsSentiment
{
    /**
     * @param string $ticker Stock ticker symbol
     * @param float|null $overallSentiment Overall sentiment score (-1.0 to 1.0, negative to positive)
     * @param string|null $sentimentLabel Human-readable sentiment label (Bearish, Neutral, Bullish)
     * @param int $articleCount Number of articles analyzed
     * @param array<int, array{
     *   title: string,
     *   source: string,
     *   url: string,
     *   publishedAt: \DateTimeImmutable,
     *   sentiment: float,
     *   sentimentLabel: string,
     *   relevanceScore: float,
     *   summary?: string
     * }> $articles Individual articles with sentiment
     * @param \DateTimeImmutable|null $timeRangeStart Start of time range for news
     * @param \DateTimeImmutable|null $timeRangeEnd End of time range for news
     * @param string $provider Provider name (e.g., 'alpha_vantage', 'newsapi')
     * @param \DateTimeImmutable $fetchedAt When this data was fetched
     * @param string|null $error Error message if fetch failed
     */
    public function __construct(
        public string $ticker,
        public ?float $overallSentiment = null,
        public ?string $sentimentLabel = null,
        public int $articleCount = 0,
        public array $articles = [],
        public ?\DateTimeImmutable $timeRangeStart = null,
        public ?\DateTimeImmutable $timeRangeEnd = null,
        public string $provider = 'unknown',
        public \DateTimeImmutable $fetchedAt = new \DateTimeImmutable(),
        public ?string $error = null
    ) {
    }

    /**
     * Check if the sentiment data is valid (no errors)
     */
    public function isValid(): bool
    {
        return $this->error === null;
    }

    /**
     * Get sentiment classification
     * 
     * @return string One of: 'Bullish', 'Bearish', 'Neutral', 'Unknown'
     */
    public function getSentimentClassification(): string
    {
        if ($this->sentimentLabel !== null) {
            return $this->sentimentLabel;
        }

        if ($this->overallSentiment === null) {
            return 'Unknown';
        }

        if ($this->overallSentiment >= 0.15) {
            return 'Bullish';
        } elseif ($this->overallSentiment <= -0.15) {
            return 'Bearish';
        } else {
            return 'Neutral';
        }
    }

    /**
     * Get sentiment strength
     * 
     * @return string One of: 'Strong', 'Moderate', 'Weak', 'Unknown'
     */
    public function getSentimentStrength(): string
    {
        if ($this->overallSentiment === null) {
            return 'Unknown';
        }

        $abs = abs($this->overallSentiment);
        if ($abs >= 0.35) {
            return 'Strong';
        } elseif ($abs >= 0.15) {
            return 'Moderate';
        } else {
            return 'Weak';
        }
    }

    /**
     * Get most recent articles (sorted by publish date, newest first)
     * 
     * @param int $limit Maximum number of articles to return
     * @return array
     */
    public function getRecentArticles(int $limit = 5): array
    {
        $sorted = $this->articles;
        usort($sorted, fn($a, $b) => $b['publishedAt'] <=> $a['publishedAt']);
        return array_slice($sorted, 0, $limit);
    }

    /**
     * Get most relevant articles (sorted by relevance score, highest first)
     * 
     * @param int $limit Maximum number of articles to return
     * @return array
     */
    public function getMostRelevantArticles(int $limit = 5): array
    {
        $sorted = $this->articles;
        usort($sorted, fn($a, $b) => ($b['relevanceScore'] ?? 0) <=> ($a['relevanceScore'] ?? 0));
        return array_slice($sorted, 0, $limit);
    }

    /**
     * Get articles with strong sentiment (abs sentiment >= 0.35)
     * 
     * @return array
     */
    public function getStrongSentimentArticles(): array
    {
        return array_filter(
            $this->articles,
            fn($article) => abs($article['sentiment']) >= 0.35
        );
    }

    /**
     * Get age of this data in seconds
     */
    public function getAge(): int
    {
        return time() - $this->fetchedAt->getTimestamp();
    }

    /**
     * Check if data is stale (older than given seconds)
     * 
     * @param int $maxAgeSeconds Maximum age in seconds (default: 1 hour)
     */
    public function isStale(int $maxAgeSeconds = 3600): bool
    {
        return $this->getAge() > $maxAgeSeconds;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'ticker' => $this->ticker,
            'overallSentiment' => $this->overallSentiment,
            'sentimentLabel' => $this->sentimentLabel ?? $this->getSentimentClassification(),
            'sentimentStrength' => $this->getSentimentStrength(),
            'articleCount' => $this->articleCount,
            'articles' => array_map(function ($article) {
                return [
                    'title' => $article['title'],
                    'source' => $article['source'],
                    'url' => $article['url'],
                    'publishedAt' => $article['publishedAt']->format('Y-m-d H:i:s'),
                    'sentiment' => $article['sentiment'],
                    'sentimentLabel' => $article['sentimentLabel'],
                    'relevanceScore' => $article['relevanceScore'],
                    'summary' => $article['summary'] ?? null,
                ];
            }, $this->articles),
            'timeRangeStart' => $this->timeRangeStart?->format('Y-m-d H:i:s'),
            'timeRangeEnd' => $this->timeRangeEnd?->format('Y-m-d H:i:s'),
            'provider' => $this->provider,
            'fetchedAt' => $this->fetchedAt->format('Y-m-d H:i:s'),
            'age' => $this->getAge(),
            'error' => $this->error,
        ];
    }

    /**
     * Format for LLM prompt inclusion
     * 
     * Returns a human-readable string suitable for including in trading assistant prompts
     */
    public function toPromptString(): string
    {
        if (!$this->isValid()) {
            return "News sentiment unavailable: {$this->error}";
        }

        $output = "News Sentiment for {$this->ticker}:\n";
        $output .= "  Overall Sentiment: {$this->getSentimentClassification()} ({$this->getSentimentStrength()})\n";
        $output .= "  Sentiment Score: " . ($this->overallSentiment !== null ? number_format($this->overallSentiment, 3) : 'N/A') . " (-1.0=Bearish, +1.0=Bullish)\n";
        $output .= "  Articles Analyzed: {$this->articleCount}\n";

        if ($this->timeRangeStart && $this->timeRangeEnd) {
            $output .= "  Time Range: {$this->timeRangeStart->format('M j')} - {$this->timeRangeEnd->format('M j, Y')}\n";
        }

        if (!empty($this->articles)) {
            $output .= "\n  Recent Headlines:\n";
            $recent = $this->getRecentArticles(5);
            foreach ($recent as $i => $article) {
                $sentiment = $article['sentiment'] >= 0 ? '+' . number_format($article['sentiment'], 2) : number_format($article['sentiment'], 2);
                $date = $article['publishedAt']->format('M j');
                $output .= "    " . ($i + 1) . ". [{$date}] {$article['title']} (Sentiment: {$sentiment}, {$article['source']})\n";
            }
        }

        $output .= "  Source: {$this->provider}";

        return $output;
    }
}

/**
 * News Sentiment Provider Interface
 * 
 * Defines contract for news sentiment data providers (Alpha Vantage, NewsAPI, etc.)
 */
interface NewsSentimentProviderInterface
{
    /**
     * Get news sentiment for a single ticker
     * 
     * @param string $ticker Stock ticker symbol
     * @param array $options Optional parameters:
     *   - 'time_from': string|null Start date for news (Y-m-d H:i:s format)
     *   - 'time_to': string|null End date for news (Y-m-d H:i:s format)
     *   - 'limit': int|null Maximum number of articles to fetch
     *   - 'topics': array|null Topics to filter by (e.g., ['earnings', 'ipo'])
     *   - 'sort': string|null Sort order ('LATEST', 'EARLIEST', 'RELEVANCE')
     * 
     * @return NewsSentiment
     * @throws \Exception on API errors (rate limits, network issues, etc.)
     */
    public function getSentiment(string $ticker, array $options = []): NewsSentiment;

    /**
     * Get news sentiment for multiple tickers
     * 
     * @param array<string> $tickers Array of ticker symbols
     * @param array $options Same options as getSentiment()
     * 
     * @return array<string, NewsSentiment> Ticker => NewsSentiment mapping
     */
    public function getBatchSentiment(array $tickers, array $options = []): array;

    /**
     * Check if this provider is available and properly configured
     * 
     * @return bool True if provider has API key and is ready to use
     */
    public function isAvailable(): bool;

    /**
     * Get provider name for identification
     * 
     * @return string Provider name (e.g., 'Alpha Vantage', 'NewsAPI')
     */
    public function getProviderName(): string;

    /**
     * Get rate limit information for this provider
     * 
     * @return array{calls_per_day: int, calls_per_minute: int, tier: string}
     */
    public function getRateLimits(): array;
}
