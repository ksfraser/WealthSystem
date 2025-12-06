<?php

declare(strict_types=1);

namespace App\Data;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Multi-source data provider with automatic fallback
 * 
 * Fetches stock data from multiple sources with fallback chain:
 * 1. Yahoo Finance (primary, free, no API key)
 * 2. Alpha Vantage (fallback, requires API key)
 * 3. Finnhub (fallback, requires API key)
 * 4. Stooq (fallback, free, limited coverage)
 * 
 * Ported from Python trading_script.py and StockDataFetcher
 */
class MultiSourceDataProvider
{
    private Client $httpClient;
    private LoggerInterface $logger;

    /** @var array<string, string> Stooq symbol remapping */
    private const STOOQ_MAP = [
        '^GSPC' => '^SPX',  // S&P 500
        '^DJI' => '^DJI',   // Dow Jones
        '^IXIC' => '^IXIC', // Nasdaq
    ];

    /** @var array<string> Symbols not available on Stooq */
    private const STOOQ_BLOCKLIST = ['^RUT'];

    /** @var array<string, string> Index proxy mapping for fallback */
    private const INDEX_PROXIES = [
        '^GSPC' => 'SPY',  // S&P 500 -> SPY ETF
        '^RUT' => 'IWM',   // Russell 2000 -> IWM ETF
    ];

    public function __construct(
        private readonly ?string $alphaVantageKey = null,
        private readonly ?string $finnhubKey = null,
        ?LoggerInterface $logger = null,
        ?Client $httpClient = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => 10.0,
            'verify' => false, // Disable SSL verification for development
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
        ]);
    }

    /**
     * Fetch stock data with automatic fallback through multiple sources
     * 
     * @param string $symbol Stock symbol (e.g., 'AAPL', '^GSPC')
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return DataFetchResult
     */
    public function fetchData(string $symbol, string $startDate, string $endDate): DataFetchResult
    {
        $startTime = microtime(true);

        // Try Yahoo Finance first (free, no API key required)
        $result = $this->fetchFromYahoo($symbol, $startDate, $endDate);
        if ($result->isSuccess()) {
            return new DataFetchResult(
                $result->data,
                $result->source,
                $result->error,
                microtime(true) - $startTime
            );
        }
        $this->logger->warning("Yahoo Finance failed for {$symbol}: {$result->error}");

        // Try Alpha Vantage if API key available
        if ($this->alphaVantageKey) {
            $result = $this->fetchFromAlphaVantage($symbol, $startDate, $endDate);
            if ($result->isSuccess()) {
                return new DataFetchResult(
                    $result->data,
                    $result->source,
                    $result->error,
                    microtime(true) - $startTime
                );
            }
            $this->logger->warning("Alpha Vantage failed for {$symbol}: {$result->error}");
        }

        // Try Finnhub if API key available
        if ($this->finnhubKey) {
            $result = $this->fetchFromFinnhub($symbol, $startDate, $endDate);
            if ($result->isSuccess()) {
                return new DataFetchResult(
                    $result->data,
                    $result->source,
                    $result->error,
                    microtime(true) - $startTime
                );
            }
            $this->logger->warning("Finnhub failed for {$symbol}: {$result->error}");
        }

        // Try Stooq (free, but limited coverage)
        $result = $this->fetchFromStooq($symbol, $startDate, $endDate);
        if ($result->isSuccess()) {
            return new DataFetchResult(
                $result->data,
                $result->source,
                $result->error,
                microtime(true) - $startTime
            );
        }
        $this->logger->warning("Stooq failed for {$symbol}: {$result->error}");

        // Try index proxy as last resort
        if (isset(self::INDEX_PROXIES[$symbol])) {
            $proxy = self::INDEX_PROXIES[$symbol];
            $this->logger->info("Trying proxy {$proxy} for {$symbol}");
            $result = $this->fetchFromYahoo($proxy, $startDate, $endDate);
            if ($result->isSuccess()) {
                return new DataFetchResult(
                    $result->data,
                    $result->source,
                    "Proxied via {$proxy}",
                    microtime(true) - $startTime
                );
            }
        }

        // All sources failed
        $this->logger->error("All data sources failed for {$symbol}");
        return new DataFetchResult(
            [],
            DataSource::EMPTY,
            "All data sources failed",
            microtime(true) - $startTime
        );
    }

    /**
     * Fetch from Yahoo Finance using yfinance-like approach
     */
    private function fetchFromYahoo(string $symbol, string $startDate, string $endDate): DataFetchResult
    {
        try {
            $start = strtotime($startDate);
            $end = strtotime($endDate);

            $url = sprintf(
                'https://query1.finance.yahoo.com/v7/finance/download/%s?period1=%d&period2=%d&interval=1d&events=history',
                urlencode($symbol),
                $start,
                $end
            );

            $response = $this->httpClient->get($url);
            $csv = (string)$response->getBody();

            if (empty($csv)) {
                return new DataFetchResult([], DataSource::EMPTY, "Empty response from Yahoo");
            }

            $data = $this->parseCsv($csv);
            if (empty($data)) {
                return new DataFetchResult([], DataSource::EMPTY, "No data parsed from Yahoo CSV");
            }

            return new DataFetchResult($data, DataSource::YAHOO);
        } catch (GuzzleException $e) {
            return new DataFetchResult([], DataSource::EMPTY, "Yahoo request failed: " . $e->getMessage());
        } catch (\Exception $e) {
            return new DataFetchResult([], DataSource::EMPTY, "Yahoo error: " . $e->getMessage());
        }
    }

    /**
     * Fetch from Alpha Vantage
     */
    private function fetchFromAlphaVantage(string $symbol, string $startDate, string $endDate): DataFetchResult
    {
        if (!$this->alphaVantageKey) {
            return new DataFetchResult([], DataSource::EMPTY, "Alpha Vantage API key not configured");
        }

        try {
            $url = sprintf(
                'https://www.alphavantage.co/query?function=TIME_SERIES_DAILY_ADJUSTED&symbol=%s&apikey=%s&outputsize=full&datatype=csv',
                urlencode($symbol),
                $this->alphaVantageKey
            );

            $response = $this->httpClient->get($url);
            $csv = (string)$response->getBody();

            if (empty($csv) || str_contains($csv, 'Error Message') || str_contains($csv, 'Invalid API call')) {
                return new DataFetchResult([], DataSource::EMPTY, "Alpha Vantage returned error or empty data");
            }

            $data = $this->parseCsv($csv);
            if (empty($data)) {
                return new DataFetchResult([], DataSource::EMPTY, "No data parsed from Alpha Vantage CSV");
            }

            // Filter by date range
            $data = $this->filterByDateRange($data, $startDate, $endDate);

            return new DataFetchResult($data, DataSource::ALPHA_VANTAGE);
        } catch (GuzzleException $e) {
            return new DataFetchResult([], DataSource::EMPTY, "Alpha Vantage request failed: " . $e->getMessage());
        } catch (\Exception $e) {
            return new DataFetchResult([], DataSource::EMPTY, "Alpha Vantage error: " . $e->getMessage());
        }
    }

    /**
     * Fetch from Finnhub
     */
    private function fetchFromFinnhub(string $symbol, string $startDate, string $endDate): DataFetchResult
    {
        if (!$this->finnhubKey) {
            return new DataFetchResult([], DataSource::EMPTY, "Finnhub API key not configured");
        }

        try {
            $start = strtotime($startDate);
            $end = strtotime($endDate);

            $url = sprintf(
                'https://finnhub.io/api/v1/stock/candle?symbol=%s&resolution=D&from=%d&to=%d&token=%s',
                urlencode($symbol),
                $start,
                $end,
                $this->finnhubKey
            );

            $response = $this->httpClient->get($url);
            $json = json_decode((string)$response->getBody(), true);

            if (!isset($json['s']) || $json['s'] !== 'ok' || empty($json['t'])) {
                return new DataFetchResult([], DataSource::EMPTY, "Finnhub returned no data or error status");
            }

            // Convert Finnhub format to standard OHLCV
            $data = [];
            $count = count($json['t']);
            for ($i = 0; $i < $count; $i++) {
                $data[] = [
                    'Date' => date('Y-m-d', $json['t'][$i]),
                    'Open' => $json['o'][$i],
                    'High' => $json['h'][$i],
                    'Low' => $json['l'][$i],
                    'Close' => $json['c'][$i],
                    'Volume' => $json['v'][$i],
                    'Adj Close' => $json['c'][$i], // Finnhub doesn't provide adjusted close
                ];
            }

            return new DataFetchResult($data, DataSource::FINNHUB);
        } catch (GuzzleException $e) {
            return new DataFetchResult([], DataSource::EMPTY, "Finnhub request failed: " . $e->getMessage());
        } catch (\Exception $e) {
            return new DataFetchResult([], DataSource::EMPTY, "Finnhub error: " . $e->getMessage());
        }
    }

    /**
     * Fetch from Stooq
     */
    private function fetchFromStooq(string $symbol, string $startDate, string $endDate): DataFetchResult
    {
        // Check blocklist
        if (in_array($symbol, self::STOOQ_BLOCKLIST, true)) {
            return new DataFetchResult([], DataSource::EMPTY, "Symbol {$symbol} not available on Stooq");
        }

        try {
            // Remap symbol if needed
            $stooqSymbol = self::STOOQ_MAP[$symbol] ?? $symbol;

            // Format symbol for Stooq (lowercase, add .us for equities)
            if (!str_starts_with($stooqSymbol, '^')) {
                $stooqSymbol = strtolower($stooqSymbol);
                if (!str_ends_with($stooqSymbol, '.us')) {
                    $stooqSymbol .= '.us';
                }
            } else {
                $stooqSymbol = strtolower($stooqSymbol);
            }

            $url = sprintf('https://stooq.com/q/d/l/?s=%s&i=d', $stooqSymbol);

            $response = $this->httpClient->get($url);
            $csv = (string)$response->getBody();

            if (empty($csv)) {
                return new DataFetchResult([], DataSource::EMPTY, "Empty response from Stooq");
            }

            $data = $this->parseCsv($csv);
            if (empty($data)) {
                return new DataFetchResult([], DataSource::EMPTY, "No data parsed from Stooq CSV");
            }

            // Filter by date range
            $data = $this->filterByDateRange($data, $startDate, $endDate);

            // Ensure Adj Close column exists (Stooq doesn't provide it)
            foreach ($data as &$row) {
                if (!isset($row['Adj Close'])) {
                    $row['Adj Close'] = $row['Close'];
                }
            }

            return new DataFetchResult($data, DataSource::STOOQ);
        } catch (GuzzleException $e) {
            return new DataFetchResult([], DataSource::EMPTY, "Stooq request failed: " . $e->getMessage());
        } catch (\Exception $e) {
            return new DataFetchResult([], DataSource::EMPTY, "Stooq error: " . $e->getMessage());
        }
    }

    /**
     * Parse CSV data into array of associative arrays
     * 
     * @return array<int, array<string, mixed>>
     */
    private function parseCsv(string $csv): array
    {
        $lines = explode("\n", trim($csv));
        if (count($lines) < 2) {
            return [];
        }

        // Parse header
        $header = str_getcsv($lines[0]);
        $data = [];

        // Parse rows
        for ($i = 1; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i]);
            if (count($row) !== count($header)) {
                continue; // Skip malformed rows
            }

            $dataRow = array_combine($header, $row);
            if ($dataRow !== false) {
                $data[] = $dataRow;
            }
        }

        return $data;
    }

    /**
     * Filter data by date range
     * 
     * @param array<int, array<string, mixed>> $data
     * @return array<int, array<string, mixed>>
     */
    private function filterByDateRange(array $data, string $startDate, string $endDate): array
    {
        $startTs = strtotime($startDate);
        $endTs = strtotime($endDate);

        return array_filter($data, function ($row) use ($startTs, $endTs) {
            $dateStr = $row['Date'] ?? $row['date'] ?? $row['timestamp'] ?? null;
            if (!$dateStr) {
                return false;
            }

            $rowTs = strtotime($dateStr);
            return $rowTs >= $startTs && $rowTs <= $endTs;
        });
    }
}
