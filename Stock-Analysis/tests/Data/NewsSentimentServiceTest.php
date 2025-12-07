<?php

namespace WealthSystem\StockAnalysis\Tests\Data;

use PHPUnit\Framework\TestCase;
use WealthSystem\StockAnalysis\Data\NewsSentiment;
use WealthSystem\StockAnalysis\Data\NewsSentimentProviderInterface;
use WealthSystem\StockAnalysis\Data\NewsSentimentService;

class NewsSentimentServiceTest extends TestCase
{
    public function testAddProvider(): void
    {
        $service = new NewsSentimentService();
        $provider = $this->createMockProvider('TestProvider', true);

        $service->addProvider($provider);

        $this->assertEquals(['TestProvider'], $service->getAvailableProviders());
    }

    public function testGetSentimentUsesFirstAvailableProvider(): void
    {
        $sentiment = new NewsSentiment(
            ticker: 'AAPL',
            overallSentiment: 0.5,
            articleCount: 10,
            provider: 'provider1'
        );

        $provider1 = $this->createMockProvider('Provider1', true, $sentiment);
        $provider2 = $this->createMockProvider('Provider2', true);

        $service = new NewsSentimentService([$provider1, $provider2]);

        $result = $service->getSentiment('AAPL', [], false);

        $this->assertEquals('provider1', $result->provider);
        $this->assertEquals(0.5, $result->overallSentiment);
    }

    public function testGetSentimentFallsBackToSecondProvider(): void
    {
        $failedSentiment = new NewsSentiment(
            ticker: 'AAPL',
            provider: 'provider1',
            error: 'Rate limit exceeded'
        );

        $successSentiment = new NewsSentiment(
            ticker: 'AAPL',
            overallSentiment: 0.6,
            articleCount: 5,
            provider: 'provider2'
        );

        $provider1 = $this->createMockProvider('Provider1', true, $failedSentiment);
        $provider2 = $this->createMockProvider('Provider2', true, $successSentiment);

        $service = new NewsSentimentService([$provider1, $provider2]);

        $result = $service->getSentiment('AAPL', [], false);

        $this->assertEquals('provider2', $result->provider);
        $this->assertEquals(0.6, $result->overallSentiment);
    }

    public function testGetSentimentSkipsUnavailableProvider(): void
    {
        $sentiment = new NewsSentiment(
            ticker: 'AAPL',
            overallSentiment: 0.4,
            articleCount: 8,
            provider: 'provider2'
        );

        $provider1 = $this->createMockProvider('Provider1', false); // Unavailable
        $provider2 = $this->createMockProvider('Provider2', true, $sentiment);

        $service = new NewsSentimentService([$provider1, $provider2]);

        $result = $service->getSentiment('AAPL', [], false);

        $this->assertEquals('provider2', $result->provider);
    }

    public function testGetSentimentReturnsErrorWhenAllProvidersFail(): void
    {
        $provider1 = $this->createMockProvider('Provider1', false);
        $provider2 = $this->createMockProvider('Provider2', false);

        $service = new NewsSentimentService([$provider1, $provider2]);

        $result = $service->getSentiment('AAPL', [], false);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('No providers available', $result->error);
    }

    public function testCachingWorks(): void
    {
        $sentiment = new NewsSentiment(
            ticker: 'AAPL',
            overallSentiment: 0.5,
            articleCount: 10,
            provider: 'provider1'
        );

        $provider = $this->createMock(NewsSentimentProviderInterface::class);
        $provider->method('getProviderName')->willReturn('TestProvider');
        $provider->method('isAvailable')->willReturn(true);
        $provider->expects($this->once()) // Should only be called once due to caching
            ->method('getSentiment')
            ->willReturn($sentiment);

        $service = new NewsSentimentService([$provider]);

        // First call - fetches from provider
        $result1 = $service->getSentiment('AAPL', [], true);
        // Second call - uses cache
        $result2 = $service->getSentiment('AAPL', [], true);

        $this->assertEquals($result1, $result2);
        $this->assertEquals(0.5, $result2->overallSentiment);
    }

    public function testCacheCanBeDisabled(): void
    {
        $sentiment = new NewsSentiment(
            ticker: 'AAPL',
            overallSentiment: 0.5,
            articleCount: 10,
            provider: 'provider1'
        );

        $provider = $this->createMock(NewsSentimentProviderInterface::class);
        $provider->method('getProviderName')->willReturn('TestProvider');
        $provider->method('isAvailable')->willReturn(true);
        $provider->expects($this->exactly(2)) // Should be called twice when cache disabled
            ->method('getSentiment')
            ->willReturn($sentiment);

        $service = new NewsSentimentService([$provider]);

        $result1 = $service->getSentiment('AAPL', [], false);
        $result2 = $service->getSentiment('AAPL', [], false);

        $this->assertEquals(0.5, $result1->overallSentiment);
        $this->assertEquals(0.5, $result2->overallSentiment);
    }

    public function testClearCache(): void
    {
        $sentiment = new NewsSentiment(
            ticker: 'AAPL',
            overallSentiment: 0.5,
            articleCount: 10,
            provider: 'provider1'
        );

        $provider = $this->createMockProvider('Provider1', true, $sentiment);
        $service = new NewsSentimentService([$provider]);

        // Fetch and cache
        $service->getSentiment('AAPL', [], true);
        $this->assertTrue($service->isCached('AAPL'));

        // Clear cache
        $service->clearCache();
        $this->assertFalse($service->isCached('AAPL'));
    }

    public function testGetBatchSentiment(): void
    {
        $sentiment1 = new NewsSentiment(ticker: 'AAPL', overallSentiment: 0.5, articleCount: 10, provider: 'test');
        $sentiment2 = new NewsSentiment(ticker: 'MSFT', overallSentiment: 0.3, articleCount: 8, provider: 'test');

        $provider = $this->createMock(NewsSentimentProviderInterface::class);
        $provider->method('getProviderName')->willReturn('TestProvider');
        $provider->method('isAvailable')->willReturn(true);
        $provider->method('getSentiment')
            ->willReturnCallback(function ($ticker) use ($sentiment1, $sentiment2) {
                return $ticker === 'AAPL' ? $sentiment1 : $sentiment2;
            });

        $service = new NewsSentimentService([$provider]);

        $results = $service->getBatchSentiment(['AAPL', 'MSFT'], [], false);

        $this->assertCount(2, $results);
        $this->assertEquals(0.5, $results['AAPL']->overallSentiment);
        $this->assertEquals(0.3, $results['MSFT']->overallSentiment);
    }

    public function testGetCacheStats(): void
    {
        $validSentiment = new NewsSentiment(ticker: 'AAPL', overallSentiment: 0.5, articleCount: 10, provider: 'test');
        $invalidSentiment = new NewsSentiment(ticker: 'MSFT', provider: 'test', error: 'Failed');

        $provider = $this->createMock(NewsSentimentProviderInterface::class);
        $provider->method('getProviderName')->willReturn('TestProvider');
        $provider->method('isAvailable')->willReturn(true);
        $provider->method('getSentiment')
            ->willReturnCallback(function ($ticker) use ($validSentiment, $invalidSentiment) {
                return $ticker === 'AAPL' ? $validSentiment : $invalidSentiment;
            });

        $service = new NewsSentimentService([$provider]);
        $service->setCacheTTL(10); // Short TTL for testing

        $service->getSentiment('AAPL', [], true);
        $service->getSentiment('MSFT', [], true);

        $stats = $service->getCacheStats();

        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['valid']);
        $this->assertEquals(1, $stats['invalid']);
    }

    public function testHasAvailableProvider(): void
    {
        $provider1 = $this->createMockProvider('Provider1', false);
        $provider2 = $this->createMockProvider('Provider2', true);

        $service = new NewsSentimentService([$provider1, $provider2]);

        $this->assertTrue($service->hasAvailableProvider());
    }

    public function testHasAvailableProviderReturnsFalseWhenNoneAvailable(): void
    {
        $provider1 = $this->createMockProvider('Provider1', false);
        $provider2 = $this->createMockProvider('Provider2', false);

        $service = new NewsSentimentService([$provider1, $provider2]);

        $this->assertFalse($service->hasAvailableProvider());
    }

    public function testGetProviderRateLimits(): void
    {
        $provider1 = $this->createMockProvider('Provider1', true);
        $provider2 = $this->createMockProvider('Provider2', true);

        $service = new NewsSentimentService([$provider1, $provider2]);

        $limits = $service->getProviderRateLimits();

        $this->assertArrayHasKey('Provider1', $limits);
        $this->assertArrayHasKey('Provider2', $limits);
        $this->assertEquals(25, $limits['Provider1']['calls_per_day']);
        $this->assertEquals(5, $limits['Provider1']['calls_per_minute']);
    }

    private function createMockProvider(
        string $name,
        bool $available,
        ?NewsSentiment $returnSentiment = null
    ): NewsSentimentProviderInterface {
        $provider = $this->createMock(NewsSentimentProviderInterface::class);
        $provider->method('getProviderName')->willReturn($name);
        $provider->method('isAvailable')->willReturn($available);
        $provider->method('getRateLimits')->willReturn([
            'calls_per_day' => 25,
            'calls_per_minute' => 5,
            'tier' => 'free',
        ]);

        if ($returnSentiment !== null) {
            $provider->method('getSentiment')->willReturn($returnSentiment);
        }

        return $provider;
    }
}
