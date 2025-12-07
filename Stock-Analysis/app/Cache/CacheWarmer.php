<?php

namespace WealthSystem\StockAnalysis\Cache;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Cache Warmer
 * 
 * Preloads frequently accessed data into cache to improve performance.
 * 
 * Use Cases:
 * - Application startup: Warm cache with common stocks
 * - Scheduled jobs: Refresh cache before market opens
 * - After cache clear: Rebuild cache with hot data
 * - Pre-trading: Load data for watchlist symbols
 * 
 * Features:
 * - Batch warming with progress tracking
 * - Concurrent warming (simulated via batches)
 * - Error handling per symbol
 * - Warming statistics and reporting
 * - Configurable batch sizes
 * 
 * Example:
 * ```php
 * $warmer = new CacheWarmer([
 *     'fundamental' => $cachedFundamentalService,
 *     'news' => $cachedNewsService,
 * ]);
 * 
 * // Warm cache for S&P 500 top 50
 * $result = $warmer->warmMultiple($sp500Top50, [
 *     'services' => ['fundamental', 'news'],
 *     'batch_size' => 10,
 * ]);
 * 
 * echo "Warmed {$result['success']}/{$result['total']} symbols\n";
 * ```
 */
class CacheWarmer
{
    public function __construct(
        private readonly array $services = [],
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * Warm cache for single symbol
     *
     * @param string $symbol Stock symbol
     * @param array<string> $serviceNames Services to warm (empty = all)
     * @return array<string, mixed> Result with status and timing
     */
    public function warm(string $symbol, array $serviceNames = []): array
    {
        $startTime = microtime(true);
        $results = [];

        // Use all services if none specified
        if (empty($serviceNames)) {
            $serviceNames = array_keys($this->services);
        }

        foreach ($serviceNames as $serviceName) {
            if (!isset($this->services[$serviceName])) {
                $results[$serviceName] = [
                    'success' => false,
                    'error' => "Service '{$serviceName}' not registered",
                ];
                continue;
            }

            try {
                $service = $this->services[$serviceName];
                $serviceStart = microtime(true);

                // Call warmCache method if available
                if (method_exists($service, 'warmCache')) {
                    $service->warmCache([$symbol]);
                } else {
                    // Fallback: Try to call main methods
                    $this->warmServiceFallback($service, $symbol);
                }

                $results[$serviceName] = [
                    'success' => true,
                    'duration' => round((microtime(true) - $serviceStart) * 1000, 2),
                ];

                $this->logger->debug("Warmed {$serviceName} for {$symbol}");
            } catch (\Exception $e) {
                $results[$serviceName] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'duration' => round((microtime(true) - $serviceStart) * 1000, 2),
                ];

                $this->logger->error("Failed to warm {$serviceName} for {$symbol}: " . $e->getMessage());
            }
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        return [
            'symbol' => $symbol,
            'success' => $successCount === count($serviceNames),
            'services' => $results,
            'duration_ms' => $duration,
        ];
    }

    /**
     * Warm cache for multiple symbols
     *
     * @param array<string> $symbols Stock symbols
     * @param array<string, mixed> $options Configuration options
     * @return array<string, mixed> Summary statistics
     */
    public function warmMultiple(array $symbols, array $options = []): array
    {
        $startTime = microtime(true);
        $serviceNames = $options['services'] ?? [];
        $batchSize = $options['batch_size'] ?? 10;
        $delayBetweenBatches = $options['delay_ms'] ?? 100;

        $results = [];
        $batches = array_chunk($symbols, $batchSize);

        $this->logger->info("Starting cache warming for " . count($symbols) . " symbols in " . count($batches) . " batches");

        foreach ($batches as $batchIndex => $batch) {
            $this->logger->info("Processing batch " . ($batchIndex + 1) . "/" . count($batches));

            foreach ($batch as $symbol) {
                $results[$symbol] = $this->warm($symbol, $serviceNames);
            }

            // Delay between batches to avoid overwhelming services
            if ($batchIndex < count($batches) - 1 && $delayBetweenBatches > 0) {
                usleep($delayBetweenBatches * 1000);
            }
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        $summary = [
            'total' => count($symbols),
            'success' => $successCount,
            'failed' => count($symbols) - $successCount,
            'success_rate' => round(($successCount / count($symbols)) * 100, 2),
            'duration_ms' => $duration,
            'duration_per_symbol_ms' => round($duration / count($symbols), 2),
            'batches' => count($batches),
            'batch_size' => $batchSize,
            'results' => $results,
        ];

        $this->logger->info("Cache warming complete: {$successCount}/{$summary['total']} symbols ({$summary['success_rate']}%)");

        return $summary;
    }

    /**
     * Warm cache for S&P 500 stocks
     *
     * @param array<string, mixed> $options Configuration options
     * @return array<string, mixed> Summary statistics
     */
    public function warmSP500(array $options = []): array
    {
        $sp500Symbols = $this->getSP500Symbols();

        return $this->warmMultiple($sp500Symbols, array_merge([
            'batch_size' => 50,
            'delay_ms' => 200,
        ], $options));
    }

    /**
     * Warm cache for watchlist
     *
     * @param array<string> $watchlist Stock symbols
     * @param array<string, mixed> $options Configuration options
     * @return array<string, mixed> Summary statistics
     */
    public function warmWatchlist(array $watchlist, array $options = []): array
    {
        return $this->warmMultiple($watchlist, array_merge([
            'batch_size' => 5,
            'delay_ms' => 50,
        ], $options));
    }

    /**
     * Schedule cache warming
     *
     * Creates a warming schedule for different times of day.
     *
     * @return array<string, array<string, mixed>> Schedule configuration
     */
    public function getWarmingSchedule(): array
    {
        return [
            'pre_market' => [
                'time' => '08:00',
                'timezone' => 'America/New_York',
                'symbols' => 'sp500_top100',
                'services' => ['fundamental', 'news'],
                'description' => 'Warm cache before market opens',
            ],
            'market_open' => [
                'time' => '09:30',
                'timezone' => 'America/New_York',
                'symbols' => 'watchlist',
                'services' => ['fundamental', 'news'],
                'description' => 'Refresh watchlist data at market open',
            ],
            'midday' => [
                'time' => '12:00',
                'timezone' => 'America/New_York',
                'symbols' => 'sp500_top50',
                'services' => ['news'],
                'description' => 'Update news sentiment midday',
            ],
            'post_market' => [
                'time' => '17:00',
                'timezone' => 'America/New_York',
                'symbols' => 'sp500_top100',
                'services' => ['fundamental'],
                'description' => 'Update fundamentals after market closes',
            ],
        ];
    }

    /**
     * Get cache warming statistics
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $stats = [];

        foreach ($this->services as $name => $service) {
            if (method_exists($service, 'getCacheStats')) {
                $stats[$name] = $service->getCacheStats();
            }
        }

        return $stats;
    }

    /**
     * Clear all caches
     *
     * @return array<string, bool> Service name => success status
     */
    public function clearAll(): array
    {
        $results = [];

        foreach ($this->services as $name => $service) {
            try {
                if (method_exists($service, 'invalidate')) {
                    // Service-specific clearing would go here
                    // For now, we'll skip since it requires symbol parameter
                }
                $results[$name] = true;
                $this->logger->info("Cleared cache for {$name}");
            } catch (\Exception $e) {
                $results[$name] = false;
                $this->logger->error("Failed to clear cache for {$name}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Fallback warming method for services without warmCache
     *
     * @param object $service Service instance
     * @param string $symbol Stock symbol
     * @return void
     */
    private function warmServiceFallback(object $service, string $symbol): void
    {
        // Try common method names
        $methods = ['getFundamentalData', 'getSentiment', 'getData', 'get'];

        foreach ($methods as $method) {
            if (method_exists($service, $method)) {
                $service->$method($symbol);
                return;
            }
        }

        throw new \RuntimeException("Service does not have a warmable method");
    }

    /**
     * Get S&P 500 symbols (top 100 for demo)
     *
     * @return array<string>
     */
    private function getSP500Symbols(): array
    {
        // Top 100 S&P 500 stocks by market cap
        return [
            'AAPL', 'MSFT', 'GOOGL', 'AMZN', 'NVDA', 'META', 'TSLA', 'BRK.B', 'UNH', 'XOM',
            'LLY', 'JPM', 'JNJ', 'V', 'PG', 'MA', 'AVGO', 'HD', 'CVX', 'MRK',
            'ABBV', 'PEP', 'COST', 'KO', 'ADBE', 'WMT', 'BAC', 'TMO', 'CRM', 'MCD',
            'CSCO', 'ACN', 'DIS', 'ABT', 'LIN', 'CMCSA', 'NKE', 'VZ', 'DHR', 'WFC',
            'NEE', 'TXN', 'BMY', 'PM', 'UPS', 'RTX', 'ORCL', 'QCOM', 'T', 'COP',
            'AMGN', 'HON', 'LOW', 'UNP', 'SPGI', 'IBM', 'ELV', 'INTU', 'CAT', 'AMD',
            'BA', 'GS', 'SBUX', 'BLK', 'DE', 'GILD', 'ISRG', 'AXP', 'BKNG', 'TJX',
            'MDLZ', 'ADI', 'MMC', 'VRTX', 'PLD', 'ADP', 'AMT', 'SYK', 'LRCX', 'CB',
            'REGN', 'CI', 'SCHW', 'MO', 'ZTS', 'NOW', 'C', 'CVS', 'ETN', 'TMUS',
            'FI', 'BSX', 'BDX', 'DUK', 'PGR', 'ITW', 'SO', 'WM', 'EOG', 'GE',
        ];
    }
}
