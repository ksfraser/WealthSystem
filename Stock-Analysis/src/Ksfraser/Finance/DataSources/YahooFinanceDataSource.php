<?php
/**
 * Yahoo Finance Data Source Implementation
 * 
 * Fetches stock price data from Yahoo Finance API (free alternative).
 * Implements the DataSourceInterface following the Interface Segregation Principle.
 */

namespace Ksfraser\Finance\DataSources;

use Ksfraser\Finance\Interfaces\DataSourceInterface;
use DateTime;

class YahooFinanceDataSource implements DataSourceInterface
{
    private $httpClient;
    private $baseUrl = 'https://query1.finance.yahoo.com/v8/finance/chart/';

    public function __construct($httpClient = null)
    {
        $this->httpClient = $httpClient ?? $this->createDefaultClient();
    }

    public function fetchStockPrice(string $symbol): ?array
    {
        try {
            $url = $this->baseUrl . urlencode($symbol);
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            if ($response === false) {
                error_log("Yahoo Finance: Failed to fetch data for {$symbol}");
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (isset($data['chart']['result'][0])) {
                return $this->normalizeYahooData($data['chart']['result'][0], $symbol);
            }
            
            if (isset($data['chart']['error'])) {
                error_log("Yahoo Finance error for {$symbol}: " . $data['chart']['error']['description']);
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("Yahoo Finance API error for {$symbol}: " . $e->getMessage());
            return null;
        }
    }

    public function fetchMultipleStockPrices(array $symbols): array
    {
        $results = [];
        foreach ($symbols as $symbol) {
            $results[$symbol] = $this->fetchStockPrice($symbol);
            // Small delay to be respectful to Yahoo's servers
            usleep(100000); // 100ms delay
        }
        return $results;
    }

    public function isAvailable(): bool
    {
        return function_exists('file_get_contents'); // Yahoo Finance is free
    }

    public function getName(): string
    {
        return 'Yahoo Finance';
    }

    /**
     * Create default HTTP client using built-in functions
     */
    private function createDefaultClient()
    {
        return null; // Using built-in file_get_contents for simplicity
    }

    /**
     * Normalize Yahoo Finance response to standard format
     */
    private function normalizeYahooData(array $data, string $symbol): array
    {
        $meta = $data['meta'] ?? [];
        $currentPrice = $meta['regularMarketPrice'] ?? 0;
        $previousClose = $meta['previousClose'] ?? 0;
        
        $change = $currentPrice - $previousClose;
        $changePercent = $previousClose > 0 ? (($currentPrice - $previousClose) / $previousClose) * 100 : 0;
        
        return [
            'symbol' => $symbol,
            'price' => (float)$currentPrice,
            'change' => (float)$change,
            'change_percent' => (float)$changePercent,
            'volume' => (int)($meta['regularMarketVolume'] ?? 0),
            'open' => (float)($meta['regularMarketOpen'] ?? 0),
            'high' => (float)($meta['regularMarketDayHigh'] ?? 0),
            'low' => (float)($meta['regularMarketDayLow'] ?? 0),
            'previous_close' => (float)$previousClose,
            'timestamp' => new DateTime(),
            'source' => $this->getName(),
            'latest_trading_day' => date('Y-m-d')
        ];
    }
}
