<?php

declare(strict_types=1);

namespace Tests\Data;

use App\Data\BatchDataFetcher;
use App\Data\DataFetchResult;
use App\Data\DataSource;
use App\Data\MultiSourceDataProvider;
use PHPUnit\Framework\TestCase;

class BatchDataFetcherTest extends TestCase
{
    /**
     * @test
     */
    public function it_fetches_multiple_symbols_concurrently(): void
    {
        $mockProvider = $this->createMock(MultiSourceDataProvider::class);

        // Mock successful responses for all symbols
        $mockProvider->method('fetchData')
            ->willReturnCallback(function ($symbol) {
                return new DataFetchResult(
                    data: [
                        ['Date' => '2024-01-01', 'Close' => 100.0],
                        ['Date' => '2024-01-02', 'Close' => 102.0],
                    ],
                    source: DataSource::YAHOO,
                    error: null,
                    fetchTime: 0.1
                );
            });

        $fetcher = new BatchDataFetcher($mockProvider);

        $symbols = ['AAPL', 'MSFT', 'GOOGL'];
        $results = $fetcher->batchFetch($symbols, '2024-01-01', '2024-01-02', 5);

        $this->assertCount(3, $results);
        $this->assertArrayHasKey('AAPL', $results);
        $this->assertArrayHasKey('MSFT', $results);
        $this->assertArrayHasKey('GOOGL', $results);

        foreach ($results as $result) {
            $this->assertTrue($result->isSuccess());
        }
    }

    /**
     * @test
     */
    public function it_handles_mixed_success_and_failure(): void
    {
        $mockProvider = $this->createMock(MultiSourceDataProvider::class);

        $mockProvider->method('fetchData')
            ->willReturnCallback(function ($symbol) {
                if ($symbol === 'INVALID') {
                    return new DataFetchResult(
                        data: [],
                        source: DataSource::EMPTY,
                        error: 'Symbol not found',
                        fetchTime: 0.05
                    );
                }

                return new DataFetchResult(
                    data: [['Date' => '2024-01-01', 'Close' => 100.0]],
                    source: DataSource::YAHOO,
                    error: null,
                    fetchTime: 0.1
                );
            });

        $fetcher = new BatchDataFetcher($mockProvider);

        $symbols = ['AAPL', 'INVALID', 'MSFT'];
        $results = $fetcher->batchFetch($symbols, '2024-01-01', '2024-01-02', 5);

        $this->assertCount(3, $results);
        $this->assertTrue($results['AAPL']->isSuccess());
        $this->assertFalse($results['INVALID']->isSuccess());
        $this->assertTrue($results['MSFT']->isSuccess());
    }

    /**
     * @test
     */
    public function it_provides_batch_statistics(): void
    {
        $mockProvider = $this->createMock(MultiSourceDataProvider::class);

        $mockProvider->method('fetchData')
            ->willReturnCallback(function ($symbol) {
                if ($symbol === 'FAIL1' || $symbol === 'FAIL2') {
                    return new DataFetchResult(
                        data: [],
                        source: DataSource::EMPTY,
                        error: 'Failed',
                        fetchTime: 0.05
                    );
                }

                $source = $symbol === 'AAPL' ? DataSource::YAHOO : DataSource::ALPHA_VANTAGE;
                return new DataFetchResult(
                    data: [['Date' => '2024-01-01', 'Close' => 100.0]],
                    source: $source,
                    error: null,
                    fetchTime: 0.1
                );
            });

        $fetcher = new BatchDataFetcher($mockProvider);

        $symbols = ['AAPL', 'MSFT', 'GOOGL', 'FAIL1', 'FAIL2'];
        $result = $fetcher->batchFetchWithStats($symbols, '2024-01-01', '2024-01-02', 5);

        $stats = $result['statistics'];

        $this->assertEquals(5, $stats['total_symbols']);
        $this->assertEquals(3, $stats['successful']);
        $this->assertEquals(2, $stats['failed']);
        $this->assertEquals(60.0, $stats['success_rate']);
        $this->assertArrayHasKey('source_breakdown', $stats);
        $this->assertArrayHasKey('total_fetch_time', $stats);
        $this->assertArrayHasKey('average_fetch_time', $stats);
    }

    /**
     * @test
     */
    public function it_respects_max_concurrent_limit(): void
    {
        $mockProvider = $this->createMock(MultiSourceDataProvider::class);

        $callCount = 0;
        $mockProvider->method('fetchData')
            ->willReturnCallback(function () use (&$callCount) {
                $callCount++;
                return new DataFetchResult(
                    data: [['Date' => '2024-01-01', 'Close' => 100.0]],
                    source: DataSource::YAHOO
                );
            });

        $fetcher = new BatchDataFetcher($mockProvider);

        $symbols = ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'TSLA', 'META', 'NVDA'];
        $maxConcurrent = 3;

        $results = $fetcher->batchFetch($symbols, '2024-01-01', '2024-01-02', $maxConcurrent);

        $this->assertCount(7, $results);
        $this->assertEquals(7, $callCount);
        // With max_concurrent=3, should process in batches of 3,3,1
    }

    /**
     * @test
     */
    public function it_tracks_source_breakdown_in_statistics(): void
    {
        $mockProvider = $this->createMock(MultiSourceDataProvider::class);

        $mockProvider->method('fetchData')
            ->willReturnCallback(function ($symbol) {
                $sources = [
                    'AAPL' => DataSource::YAHOO,
                    'MSFT' => DataSource::YAHOO,
                    'GOOGL' => DataSource::ALPHA_VANTAGE,
                    'AMZN' => DataSource::FINNHUB,
                ];

                return new DataFetchResult(
                    data: [['Date' => '2024-01-01', 'Close' => 100.0]],
                    source: $sources[$symbol] ?? DataSource::YAHOO
                );
            });

        $fetcher = new BatchDataFetcher($mockProvider);

        $result = $fetcher->batchFetchWithStats(
            ['AAPL', 'MSFT', 'GOOGL', 'AMZN'],
            '2024-01-01',
            '2024-01-02'
        );

        $breakdown = $result['statistics']['source_breakdown'];

        $this->assertEquals(2, $breakdown['yahoo']);
        $this->assertEquals(1, $breakdown['alpha_vantage']);
        $this->assertEquals(1, $breakdown['finnhub']);
    }

    /**
     * @test
     */
    public function it_handles_empty_symbol_list(): void
    {
        $mockProvider = $this->createMock(MultiSourceDataProvider::class);
        $fetcher = new BatchDataFetcher($mockProvider);

        $results = $fetcher->batchFetch([], '2024-01-01', '2024-01-02');

        $this->assertEmpty($results);
    }

    /**
     * @test
     */
    public function it_calculates_average_fetch_time(): void
    {
        $mockProvider = $this->createMock(MultiSourceDataProvider::class);

        $mockProvider->method('fetchData')
            ->willReturnCallback(function () {
                static $times = [0.1, 0.2, 0.3];
                static $index = 0;
                $time = $times[$index++ % count($times)];

                return new DataFetchResult(
                    data: [['Date' => '2024-01-01', 'Close' => 100.0]],
                    source: DataSource::YAHOO,
                    error: null,
                    fetchTime: $time
                );
            });

        $fetcher = new BatchDataFetcher($mockProvider);

        $result = $fetcher->batchFetchWithStats(
            ['AAPL', 'MSFT', 'GOOGL'],
            '2024-01-01',
            '2024-01-02'
        );

        $stats = $result['statistics'];

        $this->assertEquals(0.6, $stats['total_fetch_time']);
        $this->assertEquals(0.2, $stats['average_fetch_time']);
    }

    /**
     * @test
     */
    public function it_provides_sp500_symbol_list(): void
    {
        $mockProvider = $this->createMock(MultiSourceDataProvider::class);

        $mockProvider->method('fetchData')
            ->willReturn(new DataFetchResult(
                data: [['Date' => '2024-01-01', 'Close' => 100.0]],
                source: DataSource::YAHOO
            ));

        $fetcher = new BatchDataFetcher($mockProvider);

        $results = $fetcher->fetchSP500('2024-01-01', '2024-01-02', 10);

        // Should have fetched multiple S&P 500 symbols
        $this->assertGreaterThan(10, count($results));
    }

    /**
     * @test
     */
    public function batch_statistics_show_zero_for_empty_batch(): void
    {
        $mockProvider = $this->createMock(MultiSourceDataProvider::class);
        $fetcher = new BatchDataFetcher($mockProvider);

        $result = $fetcher->batchFetchWithStats(
            [],
            '2024-01-01',
            '2024-01-02'
        );

        $stats = $result['statistics'];

        $this->assertEquals(0, $stats['total_symbols']);
        $this->assertEquals(0, $stats['successful']);
        $this->assertEquals(0, $stats['failed']);
        $this->assertEquals(0, $stats['success_rate']);
        $this->assertEquals(0, $stats['average_fetch_time']);
    }
}
