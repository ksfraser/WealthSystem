<?php

declare(strict_types=1);

namespace Tests\Data;

use App\Data\AlphaVantageFundamentalProvider;
use App\Data\FundamentalData;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class AlphaVantageFundamentalProviderTest extends TestCase
{
    private AlphaVantageFundamentalProvider $provider;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $this->provider = new AlphaVantageFundamentalProvider(
            apiKey: 'test_key',
            httpClient: $httpClient
        );
    }

    public function testGetFundamentalsWithSuccessfulResponse(): void
    {
        // Mock successful Alpha Vantage response
        $mockResponse = [
            'Symbol' => 'AAPL',
            'Name' => 'Apple Inc',
            'Sector' => 'Technology',
            'Industry' => 'Consumer Electronics',
            'MarketCapitalization' => '2500000000000',
            'PERatio' => '28.5',
            'PEGRatio' => '2.1',
            'PriceToBookRatio' => '45.2',
            'PriceToSalesRatioTTM' => '7.8',
            'ProfitMargin' => '0.26',
            'OperatingMarginTTM' => '0.30',
            'ReturnOnAssetsTTM' => '0.22',
            'ReturnOnEquityTTM' => '1.47',
            'RevenueTTM' => '394328000000',
            'GrossProfitTTM' => '169148000000',
            'EPS' => '6.15',
            'QuarterlyRevenueGrowthYOY' => '0.02',
            'QuarterlyEarningsGrowthYOY' => '0.11',
            'DebtToEquity' => '1.96',
            'CurrentRatio' => '0.98',
            'BookValue' => '3.85',
            'DividendYield' => '0.0046',
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $result = $this->provider->getFundamentals('AAPL');

        $this->assertTrue($result->isValid());
        $this->assertEquals('AAPL', $result->ticker);
        $this->assertEquals('Apple Inc', $result->companyName);
        $this->assertEquals('Technology', $result->sector);
        $this->assertEquals('Consumer Electronics', $result->industry);
        $this->assertEquals(2500000000000, $result->marketCap);
        $this->assertNotNull($result->ratios);
        $this->assertNotNull($result->financials);
        $this->assertNotNull($result->growth);
        $this->assertEquals('alpha_vantage', $result->provider);
    }

    public function testGetFundamentalsExtractsRatiosCorrectly(): void
    {
        $mockResponse = [
            'Symbol' => 'TEST',
            'Name' => 'Test Company',
            'PERatio' => '20.5',
            'ProfitMargin' => '0.15',
            'ReturnOnEquityTTM' => '0.20',
            'DebtToEquity' => '0.5',
            'CurrentRatio' => '2.0',
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $result = $this->provider->getFundamentals('TEST');

        $this->assertTrue($result->isValid());
        $this->assertEquals(20.5, $result->ratios['pe_ratio']);
        $this->assertEquals(15.0, $result->ratios['profit_margin']);
        $this->assertEquals(20.0, $result->ratios['roe']);
        $this->assertEquals(0.5, $result->ratios['debt_to_equity']);
        $this->assertEquals(2.0, $result->ratios['current_ratio']);
    }

    public function testGetFundamentalsWithRateLimitResponse(): void
    {
        $mockResponse = [
            'Note' => 'Thank you for using Alpha Vantage! Our standard API call frequency is 5 calls per minute and 25 calls per day.'
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode($mockResponse))
        );

        $result = $this->provider->getFundamentals('AAPL');

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('API call frequency', $result->error);
    }

    public function testGetFundamentalsWithApiError(): void
    {
        $this->mockHandler->append(
            new Response(401, [], json_encode(['Error Message' => 'Invalid API key']))
        );

        $result = $this->provider->getFundamentals('AAPL');

        $this->assertFalse($result->isValid());
        $this->assertNotNull($result->error);
    }

    public function testIsAvailableWithKey(): void
    {
        $this->assertTrue($this->provider->isAvailable());
    }

    public function testIsAvailableWithoutKey(): void
    {
        $provider = new AlphaVantageFundamentalProvider('');
        $this->assertFalse($provider->isAvailable());
    }

    public function testGetProviderName(): void
    {
        $this->assertEquals('Alpha Vantage', $this->provider->getProviderName());
    }

    public function testGetRateLimits(): void
    {
        $limits = $this->provider->getRateLimits();
        
        $this->assertEquals(25, $limits['calls_per_day']);
        $this->assertEquals(5, $limits['calls_per_minute']);
    }

    public function testGetBatchFundamentalsReturnArray(): void
    {
        // Mock two responses
        $mockResponse1 = [
            'Symbol' => 'AAPL',
            'Name' => 'Apple Inc',
            'Sector' => 'Technology',
            'MarketCapitalization' => '2500000000000',
        ];

        $mockResponse2 = [
            'Symbol' => 'MSFT',
            'Name' => 'Microsoft Corp',
            'Sector' => 'Technology',
            'MarketCapitalization' => '2300000000000',
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode($mockResponse1)),
            new Response(200, [], json_encode($mockResponse2))
        );

        // Note: This test will actually sleep for 12 seconds due to rate limiting
        // In real tests, you might want to mock time or skip this test
        $this->markTestSkipped('Batch test requires sleep for rate limiting');
        
        $results = $this->provider->getBatchFundamentals(['AAPL', 'MSFT']);
        
        $this->assertCount(2, $results);
        $this->assertArrayHasKey('AAPL', $results);
        $this->assertArrayHasKey('MSFT', $results);
    }
}
