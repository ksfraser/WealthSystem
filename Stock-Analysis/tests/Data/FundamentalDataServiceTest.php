<?php

declare(strict_types=1);

namespace Tests\Data;

use App\Data\FundamentalData;
use App\Data\FundamentalDataProviderInterface;
use App\Data\FundamentalDataService;
use PHPUnit\Framework\TestCase;

class FundamentalDataServiceTest extends TestCase
{
    public function testAddProvider(): void
    {
        $service = new FundamentalDataService();
        $provider = $this->createMockProvider('TestProvider', true);
        
        $service->addProvider($provider);
        
        $this->assertContains('TestProvider', $service->getAvailableProviders());
    }

    public function testGetFundamentalsUsesFirstAvailableProvider(): void
    {
        $validData = new FundamentalData(
            ticker: 'AAPL',
            companyName: 'Apple Inc',
            financials: ['revenue' => 1000000],
            provider: 'Provider1'
        );

        $provider1 = $this->createMockProvider('Provider1', true, $validData);
        $provider2 = $this->createMockProvider('Provider2', true);
        
        $service = new FundamentalDataService([$provider1, $provider2]);
        
        $result = $service->getFundamentals('AAPL');
        
        $this->assertEquals('Provider1', $result->provider);
        $this->assertTrue($result->isValid());
    }

    public function testGetFundamentalsFallsBackToSecondProvider(): void
    {
        $invalidData = new FundamentalData(
            ticker: 'AAPL',
            provider: 'Provider1',
            error: 'Rate limit'
        );

        $validData = new FundamentalData(
            ticker: 'AAPL',
            companyName: 'Apple Inc',
            financials: ['revenue' => 1000000],
            provider: 'Provider2'
        );

        $provider1 = $this->createMockProvider('Provider1', true, $invalidData);
        $provider2 = $this->createMockProvider('Provider2', true, $validData);
        
        $service = new FundamentalDataService([$provider1, $provider2]);
        
        $result = $service->getFundamentals('AAPL');
        
        $this->assertEquals('Provider2', $result->provider);
        $this->assertTrue($result->isValid());
    }

    public function testGetFundamentalsSkipsUnavailableProvider(): void
    {
        $validData = new FundamentalData(
            ticker: 'AAPL',
            companyName: 'Apple Inc',
            financials: ['revenue' => 1000000],
            provider: 'Provider2'
        );

        $provider1 = $this->createMockProvider('Provider1', false); // Not available
        $provider2 = $this->createMockProvider('Provider2', true, $validData);
        
        $service = new FundamentalDataService([$provider1, $provider2]);
        
        $result = $service->getFundamentals('AAPL');
        
        $this->assertEquals('Provider2', $result->provider);
    }

    public function testGetFundamentalsReturnsErrorWhenAllProvidersFail(): void
    {
        $invalidData = new FundamentalData(
            ticker: 'AAPL',
            provider: 'Provider1',
            error: 'Failed'
        );

        $provider1 = $this->createMockProvider('Provider1', true, $invalidData);
        
        $service = new FundamentalDataService([$provider1]);
        
        $result = $service->getFundamentals('AAPL');
        
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('failed', $result->error);
    }

    public function testCachingWorks(): void
    {
        $validData = new FundamentalData(
            ticker: 'AAPL',
            companyName: 'Apple Inc',
            financials: ['revenue' => 1000000],
            provider: 'Provider1'
        );

        $provider = $this->createMockProvider('Provider1', true, $validData);
        
        // Provider should only be called once due to caching
        $provider->expects($this->once())
            ->method('getFundamentals')
            ->willReturn($validData);
        
        $service = new FundamentalDataService([$provider]);
        
        // First call
        $result1 = $service->getFundamentals('AAPL', useCache: true);
        $this->assertTrue($service->isCached('AAPL'));
        
        // Second call (cached)
        $result2 = $service->getFundamentals('AAPL', useCache: true);
        
        $this->assertEquals($result1->companyName, $result2->companyName);
    }

    public function testCacheCanBeDisabled(): void
    {
        $validData = new FundamentalData(
            ticker: 'AAPL',
            companyName: 'Apple Inc',
            financials: ['revenue' => 1000000],
            provider: 'Provider1'
        );

        $provider = $this->createMockProvider('Provider1', true, $validData);
        
        // Provider should be called twice when cache is disabled
        $provider->expects($this->exactly(2))
            ->method('getFundamentals')
            ->willReturn($validData);
        
        $service = new FundamentalDataService([$provider]);
        
        $result1 = $service->getFundamentals('AAPL', useCache: false);
        $result2 = $service->getFundamentals('AAPL', useCache: false);
        
        $this->assertEquals($result1->companyName, $result2->companyName);
    }

    public function testClearCache(): void
    {
        $validData = new FundamentalData(
            ticker: 'AAPL',
            companyName: 'Apple Inc',
            financials: ['revenue' => 1000000],
            provider: 'Provider1'
        );

        $provider = $this->createMockProvider('Provider1', true, $validData);
        
        $service = new FundamentalDataService([$provider]);
        
        // Cache data
        $service->getFundamentals('AAPL');
        $this->assertTrue($service->isCached('AAPL'));
        
        // Clear cache
        $service->clearCache('AAPL');
        $this->assertFalse($service->isCached('AAPL'));
    }

    public function testGetBatchFundamentals(): void
    {
        $dataAAPL = new FundamentalData(
            ticker: 'AAPL',
            companyName: 'Apple Inc',
            financials: ['revenue' => 1000000],
            provider: 'Provider1'
        );

        $dataMSFT = new FundamentalData(
            ticker: 'MSFT',
            companyName: 'Microsoft Corp',
            financials: ['revenue' => 2000000],
            provider: 'Provider1'
        );

        $provider = $this->createMockProvider('Provider1', true);
        $provider->method('getFundamentals')
            ->willReturnMap([
                ['AAPL', $dataAAPL],
                ['MSFT', $dataMSFT],
            ]);
        
        $service = new FundamentalDataService([$provider]);
        
        $results = $service->getBatchFundamentals(['AAPL', 'MSFT']);
        
        $this->assertCount(2, $results);
        $this->assertArrayHasKey('AAPL', $results);
        $this->assertArrayHasKey('MSFT', $results);
        $this->assertEquals('Apple Inc', $results['AAPL']->companyName);
        $this->assertEquals('Microsoft Corp', $results['MSFT']->companyName);
    }

    public function testGetCacheStats(): void
    {
        $validData = new FundamentalData(
            ticker: 'AAPL',
            companyName: 'Apple Inc',
            financials: ['revenue' => 1000000],
            provider: 'Provider1'
        );

        $provider = $this->createMockProvider('Provider1', true, $validData);
        
        $service = new FundamentalDataService([$provider]);
        $service->setCacheTTL(3600);
        
        // Cache one item
        $service->getFundamentals('AAPL');
        
        $stats = $service->getCacheStats();
        
        $this->assertEquals(1, $stats['total_cached']);
        $this->assertEquals(1, $stats['valid']);
        $this->assertEquals(3600, $stats['ttl_seconds']);
    }

    public function testHasAvailableProvider(): void
    {
        $provider = $this->createMockProvider('Provider1', true);
        
        $service = new FundamentalDataService([$provider]);
        
        $this->assertTrue($service->hasAvailableProvider());
    }

    public function testGetProviderRateLimits(): void
    {
        $provider = $this->createMockProvider('Provider1', true);
        $provider->method('getRateLimits')
            ->willReturn(['calls_per_day' => 25, 'calls_per_minute' => 5]);
        
        $service = new FundamentalDataService([$provider]);
        
        $limits = $service->getProviderRateLimits();
        
        $this->assertArrayHasKey('Provider1', $limits);
        $this->assertEquals(25, $limits['Provider1']['calls_per_day']);
        $this->assertEquals(5, $limits['Provider1']['calls_per_minute']);
    }

    /**
     * Create a mock provider for testing
     */
    private function createMockProvider(
        string $name,
        bool $available,
        ?FundamentalData $returnData = null
    ): FundamentalDataProviderInterface {
        $provider = $this->createMock(FundamentalDataProviderInterface::class);
        
        $provider->method('getProviderName')->willReturn($name);
        $provider->method('isAvailable')->willReturn($available);
        
        if ($returnData !== null) {
            $provider->method('getFundamentals')->willReturn($returnData);
        }
        
        $provider->method('getRateLimits')->willReturn([
            'calls_per_day' => 100,
            'calls_per_minute' => 10,
        ]);
        
        return $provider;
    }
}
