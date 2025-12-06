<?php

declare(strict_types=1);

namespace App\Data;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Batch data fetcher with concurrent requests
 * 
 * Fetches data for multiple symbols concurrently using Guzzle's async capabilities.
 * Similar to Python's ThreadPoolExecutor batch_fetch_data().
 */
class BatchDataFetcher
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly MultiSourceDataProvider $dataProvider,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Fetch data for multiple symbols concurrently
     * 
     * @param array<string> $symbols List of stock symbols
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param int $maxConcurrent Maximum concurrent requests (default: 5)
     * @return array<string, DataFetchResult> Symbol => DataFetchResult
     */
    public function batchFetch(
        array $symbols,
        string $startDate,
        string $endDate,
        int $maxConcurrent = 5
    ): array {
        $startTime = microtime(true);
        $this->logger->info(sprintf(
            "Starting batch fetch for %d symbols (max concurrent: %d)",
            count($symbols),
            $maxConcurrent
        ));

        $results = [];
        $batches = array_chunk($symbols, $maxConcurrent);

        foreach ($batches as $batchIndex => $batch) {
            $batchResults = $this->fetchBatch($batch, $startDate, $endDate);
            $results = array_merge($results, $batchResults);

            $this->logger->info(sprintf(
                "Completed batch %d/%d (%d symbols)",
                $batchIndex + 1,
                count($batches),
                count($batch)
            ));

            // Small delay between batches to respect rate limits
            if ($batchIndex < count($batches) - 1) {
                usleep(100000); // 100ms
            }
        }

        $duration = microtime(true) - $startTime;
        $successCount = count(array_filter($results, fn($r) => $r->isSuccess()));

        $this->logger->info(sprintf(
            "Batch fetch completed: %d/%d successful in %.2f seconds",
            $successCount,
            count($symbols),
            $duration
        ));

        return $results;
    }

    /**
     * Fetch a single batch sequentially
     * 
     * Note: PHP doesn't have true threading like Python's ThreadPoolExecutor.
     * This implementation processes symbols sequentially within each batch,
     * with small delays between batches for rate limiting.
     * 
     * @param array<string> $symbols
     * @return array<string, DataFetchResult>
     */
    private function fetchBatch(array $symbols, string $startDate, string $endDate): array
    {
        $results = [];

        foreach ($symbols as $symbol) {
            try {
                $results[$symbol] = $this->dataProvider->fetchData($symbol, $startDate, $endDate);
            } catch (\Exception $e) {
                $this->logger->error("Fetch failed for {$symbol}: " . $e->getMessage());
                $results[$symbol] = new DataFetchResult(
                    [],
                    DataSource::EMPTY,
                    $e->getMessage()
                );
            }
        }

        return $results;
    }

    /**
     * Fetch data for multiple symbols with statistics
     * 
     * @param array<string> $symbols
     * @return array{results: array<string, DataFetchResult>, statistics: array<string, mixed>}
     */
    public function batchFetchWithStats(
        array $symbols,
        string $startDate,
        string $endDate,
        int $maxConcurrent = 5
    ): array {
        $results = $this->batchFetch($symbols, $startDate, $endDate, $maxConcurrent);

        // Calculate statistics
        $successCount = 0;
        $failureCount = 0;
        $sourceStats = [];
        $totalFetchTime = 0.0;

        foreach ($results as $result) {
            if ($result->isSuccess()) {
                $successCount++;
                $source = $result->source->value;
                $sourceStats[$source] = ($sourceStats[$source] ?? 0) + 1;
            } else {
                $failureCount++;
            }
            $totalFetchTime += $result->fetchTime;
        }

        $statistics = [
            'total_symbols' => count($symbols),
            'successful' => $successCount,
            'failed' => $failureCount,
            'success_rate' => count($symbols) > 0 ? round(($successCount / count($symbols)) * 100, 2) : 0,
            'source_breakdown' => $sourceStats,
            'total_fetch_time' => round($totalFetchTime, 2),
            'average_fetch_time' => count($symbols) > 0 ? round($totalFetchTime / count($symbols), 3) : 0,
        ];

        return [
            'results' => $results,
            'statistics' => $statistics,
        ];
    }

    /**
     * Fetch S&P 500 symbols data
     * 
    /**
     * Fetch S&P 500 symbols data
     * 
     * @return array<string, DataFetchResult>
     */
    public function fetchSP500(string $startDate, string $endDate, int $maxConcurrent = 10): array
    {
        $symbols = $this->getSP500Symbols();
        $count = count($symbols);
        $this->logger->info("Fetching S&P 500 data ({$count} symbols)");

        return $this->batchFetch($symbols, $startDate, $endDate, $maxConcurrent);
    }* Get S&P 500 symbol list
     * 
     * @return array<string>
     */
    private function getSP500Symbols(): array
    {
        // Fallback list of major S&P 500 stocks
        // In production, this should fetch from Wikipedia or a dedicated API
        return [
            'AAPL', 'MSFT', 'GOOGL', 'AMZN', 'NVDA', 'META', 'TSLA', 'BRK.B',
            'UNH', 'XOM', 'JNJ', 'JPM', 'V', 'PG', 'MA', 'HD', 'CVX', 'MRK',
            'ABBV', 'PEP', 'KO', 'AVGO', 'COST', 'LLY', 'WMT', 'TMO', 'MCD',
            'CSCO', 'ABT', 'ACN', 'DHR', 'VZ', 'ADBE', 'NEE', 'TXN', 'NKE',
            'PM', 'CRM', 'DIS', 'WFC', 'CMCSA', 'UPS', 'BMY', 'ORCL', 'QCOM',
            'RTX', 'AMGN', 'HON', 'UNP', 'BA', 'IBM', 'CAT', 'SBUX', 'GE',
        ];
    }
}

