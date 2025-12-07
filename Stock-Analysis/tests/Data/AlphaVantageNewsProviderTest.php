<?php

namespace WealthSystem\StockAnalysis\Tests\Data;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use WealthSystem\StockAnalysis\Data\AlphaVantageNewsProvider;
use WealthSystem\StockAnalysis\Data\NewsSentiment;

class AlphaVantageNewsProviderTest extends TestCase
{
    public function testGetSentimentWithSuccessfulResponse(): void
    {
        $mockResponse = [
            'items' => '50',
            'sentiment_score_definition' => 'x <= -0.35: Bearish; -0.35 < x <= -0.15: Somewhat-Bearish; -0.15 < x < 0.15: Neutral; 0.15 <= x < 0.35: Somewhat_Bullish; x >= 0.35: Bullish',
            'relevance_score_definition' => '0 < x <= 1, with a higher score indicating higher relevance.',
            'feed' => [
                [
                    'title' => 'Apple Announces Record Q4 Earnings',
                    'url' => 'https://example.com/article1',
                    'time_published' => '20231201T143000',
                    'source' => 'Reuters',
                    'summary' => 'Apple reported record quarterly earnings...',
                    'ticker_sentiment' => [
                        [
                            'ticker' => 'AAPL',
                            'relevance_score' => '0.892456',
                            'ticker_sentiment_score' => '0.456789',
                            'ticker_sentiment_label' => 'Bullish',
                        ],
                    ],
                ],
                [
                    'title' => 'Tech Sector Outlook Remains Strong',
                    'url' => 'https://example.com/article2',
                    'time_published' => '20231130T120000',
                    'source' => 'Bloomberg',
                    'summary' => 'Analysts remain bullish on tech stocks...',
                    'ticker_sentiment' => [
                        [
                            'ticker' => 'AAPL',
                            'relevance_score' => '0.654321',
                            'ticker_sentiment_score' => '0.234567',
                            'ticker_sentiment_label' => 'Somewhat-Bullish',
                        ],
                        [
                            'ticker' => 'MSFT',
                            'relevance_score' => '0.543210',
                            'ticker_sentiment_score' => '0.123456',
                            'ticker_sentiment_label' => 'Neutral',
                        ],
                    ],
                ],
            ],
        ];

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $provider = new AlphaVantageNewsProvider(
            apiKey: 'test_key',
            httpClient: $httpClient
        );

        $sentiment = $provider->getSentiment('AAPL');

        $this->assertInstanceOf(NewsSentiment::class, $sentiment);
        $this->assertTrue($sentiment->isValid());
        $this->assertEquals('AAPL', $sentiment->ticker);
        $this->assertEquals(2, $sentiment->articleCount);
        $this->assertGreaterThan(0, $sentiment->overallSentiment);
        $this->assertEquals('Bullish', $sentiment->getSentimentClassification());
        $this->assertCount(2, $sentiment->articles);
        $this->assertEquals('Apple Announces Record Q4 Earnings', $sentiment->articles[0]['title']);
    }

    public function testGetSentimentExtractsSentimentCorrectly(): void
    {
        $mockResponse = [
            'feed' => [
                [
                    'title' => 'Test Article',
                    'url' => 'https://example.com/test',
                    'time_published' => '20231201T120000',
                    'source' => 'Test Source',
                    'ticker_sentiment' => [
                        [
                            'ticker' => 'AAPL',
                            'relevance_score' => '0.5',
                            'ticker_sentiment_score' => '0.6',
                            'ticker_sentiment_label' => 'Bullish',
                        ],
                    ],
                ],
            ],
        ];

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $provider = new AlphaVantageNewsProvider(
            apiKey: 'test_key',
            httpClient: $httpClient
        );

        $sentiment = $provider->getSentiment('AAPL');

        $this->assertEquals(0.6, $sentiment->articles[0]['sentiment']);
        $this->assertEquals('Bullish', $sentiment->articles[0]['sentimentLabel']);
        $this->assertEquals(0.5, $sentiment->articles[0]['relevanceScore']);
    }

    public function testGetSentimentFiltersLowRelevanceArticles(): void
    {
        $mockResponse = [
            'feed' => [
                [
                    'title' => 'High Relevance Article',
                    'url' => 'https://example.com/high',
                    'time_published' => '20231201T120000',
                    'source' => 'Source1',
                    'ticker_sentiment' => [
                        [
                            'ticker' => 'AAPL',
                            'relevance_score' => '0.8', // High relevance
                            'ticker_sentiment_score' => '0.5',
                            'ticker_sentiment_label' => 'Bullish',
                        ],
                    ],
                ],
                [
                    'title' => 'Low Relevance Article',
                    'url' => 'https://example.com/low',
                    'time_published' => '20231201T130000',
                    'source' => 'Source2',
                    'ticker_sentiment' => [
                        [
                            'ticker' => 'AAPL',
                            'relevance_score' => '0.05', // Low relevance - should be filtered
                            'ticker_sentiment_score' => '0.3',
                            'ticker_sentiment_label' => 'Neutral',
                        ],
                    ],
                ],
            ],
        ];

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $provider = new AlphaVantageNewsProvider(
            apiKey: 'test_key',
            httpClient: $httpClient
        );

        $sentiment = $provider->getSentiment('AAPL');

        // Only high relevance article should be included
        $this->assertEquals(1, $sentiment->articleCount);
        $this->assertEquals('High Relevance Article', $sentiment->articles[0]['title']);
    }

    public function testGetSentimentWithRateLimitResponse(): void
    {
        $mockResponse = [
            'Note' => 'Thank you for using Alpha Vantage! Our standard API call frequency is 5 calls per minute.',
        ];

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $provider = new AlphaVantageNewsProvider(
            apiKey: 'test_key',
            httpClient: $httpClient
        );

        $sentiment = $provider->getSentiment('AAPL');

        $this->assertFalse($sentiment->isValid());
        $this->assertStringContainsString('Rate limit', $sentiment->error);
    }

    public function testGetSentimentWithApiError(): void
    {
        $mockResponse = [
            'Error Message' => 'Invalid API key',
        ];

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $provider = new AlphaVantageNewsProvider(
            apiKey: 'invalid_key',
            httpClient: $httpClient
        );

        $sentiment = $provider->getSentiment('AAPL');

        $this->assertFalse($sentiment->isValid());
        $this->assertEquals('Invalid API key', $sentiment->error);
    }

    public function testGetSentimentWithNoNewsData(): void
    {
        $mockResponse = [
            'feed' => [],
        ];

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $provider = new AlphaVantageNewsProvider(
            apiKey: 'test_key',
            httpClient: $httpClient
        );

        $sentiment = $provider->getSentiment('AAPL');

        $this->assertTrue($sentiment->isValid());
        $this->assertEquals(0, $sentiment->articleCount);
        $this->assertNull($sentiment->overallSentiment);
    }

    public function testIsAvailableWithKey(): void
    {
        $provider = new AlphaVantageNewsProvider(apiKey: 'test_key');
        $this->assertTrue($provider->isAvailable());
    }

    public function testIsAvailableWithoutKey(): void
    {
        $provider = new AlphaVantageNewsProvider(apiKey: '');
        $this->assertFalse($provider->isAvailable());
    }

    public function testGetProviderName(): void
    {
        $provider = new AlphaVantageNewsProvider(apiKey: 'test_key');
        $this->assertEquals('Alpha Vantage', $provider->getProviderName());
    }

    public function testGetRateLimits(): void
    {
        $provider = new AlphaVantageNewsProvider(apiKey: 'test_key');
        $limits = $provider->getRateLimits();

        $this->assertArrayHasKey('calls_per_day', $limits);
        $this->assertArrayHasKey('calls_per_minute', $limits);
        $this->assertArrayHasKey('tier', $limits);
        $this->assertEquals(25, $limits['calls_per_day']);
        $this->assertEquals(5, $limits['calls_per_minute']);
        $this->assertEquals('free', $limits['tier']);
    }

    public function testGetBatchSentimentReturnsArray(): void
    {
        $this->markTestSkipped('Skipping batch test due to 12-second sleep requirement');

        $mockResponse = [
            'feed' => [
                [
                    'title' => 'Test Article',
                    'url' => 'https://example.com/test',
                    'time_published' => '20231201T120000',
                    'source' => 'Test Source',
                    'ticker_sentiment' => [
                        [
                            'ticker' => 'AAPL',
                            'relevance_score' => '0.8',
                            'ticker_sentiment_score' => '0.5',
                            'ticker_sentiment_label' => 'Bullish',
                        ],
                    ],
                ],
            ],
        ];

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($mockResponse)),
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $provider = new AlphaVantageNewsProvider(
            apiKey: 'test_key',
            httpClient: $httpClient
        );

        $sentiments = $provider->getBatchSentiment(['AAPL', 'MSFT']);

        $this->assertIsArray($sentiments);
        $this->assertCount(2, $sentiments);
        $this->assertArrayHasKey('AAPL', $sentiments);
        $this->assertArrayHasKey('MSFT', $sentiments);
    }
}
