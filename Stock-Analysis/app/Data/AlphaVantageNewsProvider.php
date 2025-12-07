<?php

namespace WealthSystem\StockAnalysis\Data;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Alpha Vantage News Sentiment Provider
 * 
 * Implements news sentiment fetching using Alpha Vantage's NEWS_SENTIMENT API.
 * 
 * API Documentation: https://www.alphavantage.co/documentation/#news-sentiment
 * 
 * Free Tier Rate Limits:
 * - 25 API calls per day
 * - 5 API calls per minute
 * 
 * Premium Tier ($49.99/month):
 * - 75 API calls per minute
 * - No daily limit
 * 
 * Response includes:
 * - Overall sentiment score and label
 * - Individual articles with sentiment, relevance, and metadata
 * - Sentiment scores range from -1.0 (bearish) to +1.0 (bullish)
 * - Relevance scores indicate how related the article is to the ticker
 */
class AlphaVantageNewsProvider implements NewsSentimentProviderInterface
{
    private const API_URL = 'https://www.alphavantage.co/query';
    private const FREE_TIER_CALLS_PER_DAY = 25;
    private const FREE_TIER_CALLS_PER_MINUTE = 5;
    private const RATE_LIMIT_DELAY_SECONDS = 12; // 60 seconds / 5 calls = 12 seconds

    private readonly Client $httpClient;
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly string $apiKey,
        ?LoggerInterface $logger = null,
        ?Client $httpClient = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getSentiment(string $ticker, array $options = []): NewsSentiment
    {
        $this->logger->info("Fetching news sentiment for {$ticker}", ['options' => $options]);

        try {
            $queryParams = [
                'function' => 'NEWS_SENTIMENT',
                'tickers' => $ticker,
                'apikey' => $this->apiKey,
            ];

            // Add optional time range
            if (!empty($options['time_from'])) {
                $queryParams['time_from'] = $this->formatDateTime($options['time_from']);
            }
            if (!empty($options['time_to'])) {
                $queryParams['time_to'] = $this->formatDateTime($options['time_to']);
            }

            // Add optional limit
            if (!empty($options['limit'])) {
                $queryParams['limit'] = min((int)$options['limit'], 1000); // API max is 1000
            }

            // Add optional topics filter
            if (!empty($options['topics']) && is_array($options['topics'])) {
                $queryParams['topics'] = implode(',', $options['topics']);
            }

            // Add optional sort order
            if (!empty($options['sort'])) {
                $queryParams['sort'] = strtoupper($options['sort']);
            }

            $response = $this->httpClient->get(self::API_URL, [
                'query' => $queryParams,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // Check for rate limit error
            if (isset($data['Note']) && strpos($data['Note'], 'API call frequency') !== false) {
                $this->logger->warning("Rate limit hit for {$ticker}", ['message' => $data['Note']]);
                return new NewsSentiment(
                    ticker: $ticker,
                    provider: 'alpha_vantage',
                    error: 'Rate limit exceeded: ' . $data['Note']
                );
            }

            // Check for API errors
            if (isset($data['Error Message']) || isset($data['error'])) {
                $errorMsg = $data['Error Message'] ?? $data['error'] ?? 'Unknown API error';
                $this->logger->error("API error for {$ticker}", ['error' => $errorMsg]);
                return new NewsSentiment(
                    ticker: $ticker,
                    provider: 'alpha_vantage',
                    error: $errorMsg
                );
            }

            // Check if we have feed data
            if (!isset($data['feed']) || !is_array($data['feed'])) {
                $this->logger->warning("No news feed data for {$ticker}");
                return new NewsSentiment(
                    ticker: $ticker,
                    provider: 'alpha_vantage',
                    error: 'No news data available'
                );
            }

            return $this->parseResponse($ticker, $data);

        } catch (GuzzleException $e) {
            $this->logger->error("HTTP error fetching sentiment for {$ticker}", [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return new NewsSentiment(
                ticker: $ticker,
                provider: 'alpha_vantage',
                error: 'API request failed: ' . $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->logger->error("Unexpected error for {$ticker}", [
                'error' => $e->getMessage(),
            ]);

            return new NewsSentiment(
                ticker: $ticker,
                provider: 'alpha_vantage',
                error: 'Unexpected error: ' . $e->getMessage()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchSentiment(array $tickers, array $options = []): array
    {
        $this->logger->info("Fetching batch sentiment for " . count($tickers) . " tickers");

        $results = [];
        foreach ($tickers as $ticker) {
            $results[$ticker] = $this->getSentiment($ticker, $options);

            // Rate limit: 5 calls per minute, wait 12 seconds between calls
            if (count($results) < count($tickers)) {
                $this->logger->debug("Rate limit delay: sleeping for " . self::RATE_LIMIT_DELAY_SECONDS . " seconds");
                sleep(self::RATE_LIMIT_DELAY_SECONDS);
            }
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName(): string
    {
        return 'Alpha Vantage';
    }

    /**
     * {@inheritdoc}
     */
    public function getRateLimits(): array
    {
        return [
            'calls_per_day' => self::FREE_TIER_CALLS_PER_DAY,
            'calls_per_minute' => self::FREE_TIER_CALLS_PER_MINUTE,
            'tier' => 'free',
        ];
    }

    /**
     * Parse API response into NewsSentiment object
     */
    private function parseResponse(string $ticker, array $data): NewsSentiment
    {
        $feed = $data['feed'] ?? [];
        $articles = [];
        $sentimentScores = [];

        // Extract ticker-specific sentiment from each article
        foreach ($feed as $item) {
            if (!isset($item['ticker_sentiment']) || !is_array($item['ticker_sentiment'])) {
                continue;
            }

            // Find sentiment data for our specific ticker
            $tickerSentiment = null;
            foreach ($item['ticker_sentiment'] as $ts) {
                if (isset($ts['ticker']) && strtoupper($ts['ticker']) === strtoupper($ticker)) {
                    $tickerSentiment = $ts;
                    break;
                }
            }

            if ($tickerSentiment === null) {
                continue; // This article doesn't mention our ticker
            }

            $sentimentScore = (float)($tickerSentiment['ticker_sentiment_score'] ?? 0);
            $relevanceScore = (float)($tickerSentiment['relevance_score'] ?? 0);

            // Only include articles with meaningful relevance (>0.1)
            if ($relevanceScore < 0.1) {
                continue;
            }

            $sentimentScores[] = $sentimentScore * $relevanceScore; // Weight by relevance

            $articles[] = [
                'title' => $item['title'] ?? 'Untitled',
                'source' => $item['source'] ?? 'Unknown',
                'url' => $item['url'] ?? '',
                'publishedAt' => new \DateTimeImmutable($item['time_published'] ?? 'now'),
                'sentiment' => $sentimentScore,
                'sentimentLabel' => $tickerSentiment['ticker_sentiment_label'] ?? $this->getSentimentLabel($sentimentScore),
                'relevanceScore' => $relevanceScore,
                'summary' => $item['summary'] ?? null,
            ];
        }

        // Calculate overall sentiment (weighted average)
        $overallSentiment = null;
        if (!empty($sentimentScores)) {
            $overallSentiment = array_sum($sentimentScores) / count($sentimentScores);
        }

        // Determine time range from articles
        $timeRangeStart = null;
        $timeRangeEnd = null;
        if (!empty($articles)) {
            $timestamps = array_map(fn($a) => $a['publishedAt']->getTimestamp(), $articles);
            $timeRangeStart = new \DateTimeImmutable('@' . min($timestamps));
            $timeRangeEnd = new \DateTimeImmutable('@' . max($timestamps));
        }

        return new NewsSentiment(
            ticker: $ticker,
            overallSentiment: $overallSentiment,
            sentimentLabel: $overallSentiment !== null ? $this->getSentimentLabel($overallSentiment) : null,
            articleCount: count($articles),
            articles: $articles,
            timeRangeStart: $timeRangeStart,
            timeRangeEnd: $timeRangeEnd,
            provider: 'alpha_vantage'
        );
    }

    /**
     * Convert sentiment score to label
     */
    private function getSentimentLabel(float $score): string
    {
        if ($score >= 0.35) {
            return 'Bullish';
        } elseif ($score >= 0.15) {
            return 'Somewhat-Bullish';
        } elseif ($score <= -0.35) {
            return 'Bearish';
        } elseif ($score <= -0.15) {
            return 'Somewhat-Bearish';
        } else {
            return 'Neutral';
        }
    }

    /**
     * Format datetime for API (YYYYMMDDTHHMM format)
     */
    private function formatDateTime(string $datetime): string
    {
        try {
            $dt = new \DateTimeImmutable($datetime);
            return $dt->format('Ymd\THi');
        } catch (\Exception $e) {
            $this->logger->warning("Invalid datetime format: {$datetime}, using current time");
            return (new \DateTimeImmutable())->format('Ymd\THi');
        }
    }
}
