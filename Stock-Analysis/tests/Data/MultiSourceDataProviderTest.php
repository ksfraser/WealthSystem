<?php

declare(strict_types=1);

namespace Tests\Data;

use App\Data\DataSource;
use App\Data\DataFetchResult;
use App\Data\MultiSourceDataProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

class MultiSourceDataProviderTest extends TestCase
{
    private MockHandler $mockHandler;
    private Client $mockClient;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->mockClient = new Client(['handler' => $handlerStack]);
    }

    /**
     * @test
     */
    public function it_fetches_from_yahoo_successfully(): void
    {
        $csvData = "Date,Open,High,Low,Close,Adj Close,Volume\n"
            . "2024-01-01,100.0,105.0,99.0,103.0,103.0,1000000\n"
            . "2024-01-02,103.0,107.0,102.0,106.0,106.0,1200000";

        $this->mockHandler->append(new Response(200, [], $csvData));

        $provider = new MultiSourceDataProvider(
            alphaVantageKey: null,
            finnhubKey: null,
            logger: null,
            httpClient: $this->mockClient
        );

        $result = $provider->fetchData('AAPL', '2024-01-01', '2024-01-02');

        $this->assertTrue($result->isSuccess());
        $this->assertSame(DataSource::YAHOO, $result->source);
        $this->assertCount(2, $result->data);
        $this->assertNull($result->error);
    }

    /**
     * @test
     */
    public function it_falls_back_to_alpha_vantage_when_yahoo_fails(): void
    {
        $csvData = "timestamp,open,high,low,close,adjusted_close,volume\n"
            . "2024-01-01,100.0,105.0,99.0,103.0,103.0,1000000\n"
            . "2024-01-02,103.0,107.0,102.0,106.0,106.0,1200000";

        // Yahoo fails
        $this->mockHandler->append(new RequestException(
            "Error Communicating with Server",
            new Request('GET', 'test')
        ));

        // Alpha Vantage succeeds
        $this->mockHandler->append(new Response(200, [], $csvData));

        $provider = new MultiSourceDataProvider(
            alphaVantageKey: 'test-key',
            finnhubKey: null,
            logger: null,
            httpClient: $this->mockClient
        );

        $result = $provider->fetchData('AAPL', '2024-01-01', '2024-01-02');

        $this->assertTrue($result->isSuccess());
        $this->assertSame(DataSource::ALPHA_VANTAGE, $result->source);
    }

    /**
     * @test
     */
    public function it_falls_back_to_finnhub_when_yahoo_and_alpha_vantage_fail(): void
    {
        $finnhubData = json_encode([
            's' => 'ok',
            't' => [1704067200, 1704153600], // timestamps
            'o' => [100.0, 103.0],
            'h' => [105.0, 107.0],
            'l' => [99.0, 102.0],
            'c' => [103.0, 106.0],
            'v' => [1000000, 1200000],
        ]);

        // Yahoo fails
        $this->mockHandler->append(new RequestException(
            "Error Communicating with Server",
            new Request('GET', 'test')
        ));

        // Alpha Vantage fails
        $this->mockHandler->append(new Response(200, [], 'Error Message: Invalid API call'));

        // Finnhub succeeds
        $this->mockHandler->append(new Response(200, [], $finnhubData));

        $provider = new MultiSourceDataProvider(
            alphaVantageKey: 'test-key',
            finnhubKey: 'test-key',
            logger: null,
            httpClient: $this->mockClient
        );

        $result = $provider->fetchData('AAPL', '2024-01-01', '2024-01-02');

        $this->assertTrue($result->isSuccess());
        $this->assertSame(DataSource::FINNHUB, $result->source);
    }

    /**
     * @test
     */
    public function it_falls_back_to_stooq_when_other_sources_fail(): void
    {
        $csvData = "Date,Open,High,Low,Close,Volume\n"
            . "2024-01-01,100.0,105.0,99.0,103.0,1000000\n"
            . "2024-01-02,103.0,107.0,102.0,106.0,1200000";

        // Yahoo fails
        $this->mockHandler->append(new RequestException(
            "Error Communicating with Server",
            new Request('GET', 'test')
        ));

        // Stooq succeeds
        $this->mockHandler->append(new Response(200, [], $csvData));

        $provider = new MultiSourceDataProvider(
            alphaVantageKey: null,
            finnhubKey: null,
            logger: null,
            httpClient: $this->mockClient
        );

        $result = $provider->fetchData('AAPL', '2024-01-01', '2024-01-02');

        $this->assertTrue($result->isSuccess());
        $this->assertSame(DataSource::STOOQ, $result->source);
    }

    /**
     * @test
     */
    public function it_tries_index_proxy_as_last_resort(): void
    {
        $csvData = "Date,Open,High,Low,Close,Adj Close,Volume\n"
            . "2024-01-01,450.0,455.0,448.0,453.0,453.0,10000000";

        // Yahoo for ^GSPC fails with exception
        $this->mockHandler->append(new RequestException(
            "Yahoo failed",
            new Request('GET', 'test')
        ));

        // Stooq fails with exception
        $this->mockHandler->append(new RequestException(
            "Stooq failed",
            new Request('GET', 'test')
        ));

        // Yahoo for SPY (proxy) succeeds
        $this->mockHandler->append(new Response(200, [], $csvData));

        $provider = new MultiSourceDataProvider(
            alphaVantageKey: null,
            finnhubKey: null,
            logger: null,
            httpClient: $this->mockClient
        );

        $result = $provider->fetchData('^GSPC', '2024-01-01', '2024-01-02');

        $this->assertTrue($result->isSuccess());
        $this->assertSame(DataSource::YAHOO, $result->source);
        $this->assertStringContainsString('Proxied via SPY', $result->error ?? '');
    }

    /**
     * @test
     */
    public function it_returns_empty_result_when_all_sources_fail(): void
    {
        // All sources fail
        $this->mockHandler->append(new RequestException(
            "Yahoo failed",
            new Request('GET', 'test')
        ));
        $this->mockHandler->append(new RequestException(
            "Stooq failed",
            new Request('GET', 'test')
        ));

        $provider = new MultiSourceDataProvider(
            alphaVantageKey: null,
            finnhubKey: null,
            logger: null,
            httpClient: $this->mockClient
        );

        $result = $provider->fetchData('INVALID', '2024-01-01', '2024-01-02');

        $this->assertFalse($result->isSuccess());
        $this->assertSame(DataSource::EMPTY, $result->source);
        $this->assertStringContainsString('All data sources failed', $result->error ?? '');
        $this->assertTrue($result->isEmpty());
    }

    /**
     * @test
     */
    public function it_skips_stooq_for_blocklisted_symbols(): void
    {
        // Yahoo fails for ^RUT with exception
        $this->mockHandler->append(new RequestException(
            "Yahoo failed",
            new Request('GET', 'test')
        ));

        // Should skip Stooq and try IWM proxy
        $csvData = "Date,Open,High,Low,Close,Adj Close,Volume\n"
            . "2024-01-01,200.0,205.0,198.0,203.0,203.0,5000000";
        $this->mockHandler->append(new Response(200, [], $csvData));

        $provider = new MultiSourceDataProvider(
            alphaVantageKey: null,
            finnhubKey: null,
            logger: null,
            httpClient: $this->mockClient
        );

        $result = $provider->fetchData('^RUT', '2024-01-01', '2024-01-02');

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('Proxied via IWM', $result->error ?? '');
    }

    /**
     * @test
     */
    public function it_handles_malformed_csv_gracefully(): void
    {
        $badCsv = "This is not valid CSV data\nwithout proper structure";

        // Yahoo returns bad CSV
        $this->mockHandler->append(new Response(200, [], $badCsv));
        
        // Stooq also returns bad CSV
        $this->mockHandler->append(new Response(200, [], $badCsv));

        $provider = new MultiSourceDataProvider(
            alphaVantageKey: null,
            finnhubKey: null,
            logger: null,
            httpClient: $this->mockClient
        );

        $result = $provider->fetchData('AAPL', '2024-01-01', '2024-01-02');

        // Parser correctly returns empty array for malformed CSV
        // This leads to "No data parsed" error and empty result
        $this->assertFalse($result->isSuccess());
        $this->assertSame(DataSource::EMPTY, $result->source);
    }

    /**
     * @test
     */
    public function it_adds_adj_close_to_stooq_data(): void
    {
        $csvData = "Date,Open,High,Low,Close,Volume\n"
            . "2024-01-01,100.0,105.0,99.0,103.0,1000000";

        $this->mockHandler->append(new Response(200, [], ''));
        $this->mockHandler->append(new Response(200, [], $csvData));

        $provider = new MultiSourceDataProvider(
            alphaVantageKey: null,
            finnhubKey: null,
            logger: null,
            httpClient: $this->mockClient
        );

        $result = $provider->fetchData('AAPL', '2024-01-01', '2024-01-02');

        $this->assertTrue($result->isSuccess());
        $this->assertArrayHasKey('Adj Close', $result->data[0]);
        $this->assertEquals(103.0, $result->data[0]['Adj Close']);
    }

    /**
     * @test
     */
    public function it_tracks_fetch_time(): void
    {
        $csvData = "Date,Open,High,Low,Close,Adj Close,Volume\n"
            . "2024-01-01,100.0,105.0,99.0,103.0,103.0,1000000";

        $this->mockHandler->append(new Response(200, [], $csvData));

        $provider = new MultiSourceDataProvider(
            alphaVantageKey: null,
            finnhubKey: null,
            logger: null,
            httpClient: $this->mockClient
        );

        $result = $provider->fetchData('AAPL', '2024-01-01', '2024-01-02');

        $this->assertGreaterThanOrEqual(0, $result->fetchTime);
    }

    /**
     * @test
     */
    public function data_fetch_result_to_array_includes_all_fields(): void
    {
        $result = new DataFetchResult(
            data: [['Date' => '2024-01-01', 'Close' => 100.0]],
            source: DataSource::YAHOO,
            error: null,
            fetchTime: 0.123
        );

        $array = $result->toArray();

        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('source', $array);
        $this->assertArrayHasKey('error', $array);
        $this->assertArrayHasKey('fetch_time', $array);
        $this->assertArrayHasKey('success', $array);
        $this->assertTrue($array['success']);
        $this->assertEquals('yahoo', $array['source']);
    }

    /**
     * @test
     */
    public function it_filters_alpha_vantage_data_by_date_range(): void
    {
        $csvData = "timestamp,open,high,low,close,adjusted_close,volume\n"
            . "2024-01-01,100.0,105.0,99.0,103.0,103.0,1000000\n"
            . "2024-01-02,103.0,107.0,102.0,106.0,106.0,1200000\n"
            . "2024-01-03,106.0,110.0,105.0,109.0,109.0,1300000";

        $this->mockHandler->append(new RequestException(
            "Yahoo failed",
            new Request('GET', 'test')
        ));
        $this->mockHandler->append(new Response(200, [], $csvData));

        $provider = new MultiSourceDataProvider(
            alphaVantageKey: 'test-key',
            finnhubKey: null,
            logger: null,
            httpClient: $this->mockClient
        );

        $result = $provider->fetchData('AAPL', '2024-01-01', '2024-01-02');

        $this->assertTrue($result->isSuccess());
        // Should only include dates within range
        $this->assertLessThanOrEqual(2, count($result->data));
    }
}
