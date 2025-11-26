<?php
/**
 * Alpha Vantage Data Source Implementation
 * 
 * Fetches stock price data from Alpha Vantage API.
 * Implements the DataSourceInterface following the Interface Segregation Principle.
 * Compatible with PHP 7.3+
 */

namespace Ksfraser\Finance\DataSources;

use Ksfraser\Finance\Interfaces\DataSourceInterface;
use DateTime;

class AlphaVantageDataSource implements DataSourceInterface
{
    private $httpClient;
    private $apiKey;
    private $baseUrl = 'https://www.alphavantage.co/query';

    public function __construct(string $apiKey, $httpClient = null)
    {
        $this->apiKey = $apiKey;
        $this->httpClient = $httpClient;
    }

    public function fetchStockPrice(string $symbol): ?array
    {
        try {
            $url = $this->baseUrl . '?' . http_build_query([
                'function' => 'GLOBAL_QUOTE',
                'symbol' => $symbol,
                'apikey' => $this->apiKey
            ]);

            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'Mozilla/5.0 (Finance Package)'
                ]
            ]);

            $response = file_get_contents($url, false, $context);
            if ($response === false) {
                error_log("Alpha Vantage: Failed to fetch data for {$symbol}");
                return null;
            }

            $data = json_decode($response, true);
            
            if (isset($data['Global Quote'])) {
                return $this->normalizeAlphaVantageData($data['Global Quote'], $symbol);
            }
            
            // Handle API error responses
            if (isset($data['Error Message'])) {
                error_log("Alpha Vantage error for {$symbol}: " . $data['Error Message']);
                return null;
            }

            // Handle rate limiting
            if (isset($data['Note'])) {
                error_log("Alpha Vantage rate limit for {$symbol}: " . $data['Note']);
                return null;
            }
            
            return null;
        } catch (\Exception $e) {
            error_log("Alpha Vantage API error for {$symbol}: " . $e->getMessage());
            return null;
        }
    }

    public function fetchMultipleStockPrices(array $symbols): array
    {
        $results = [];
        foreach ($symbols as $symbol) {
            $results[$symbol] = $this->fetchStockPrice($symbol);
            // Rate limiting - Alpha Vantage has 5 requests per minute for free tier
            usleep(300000); // 300ms delay to be safe
        }
        return $results;
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey) && function_exists('file_get_contents');
    }

    public function getName(): string
    {
        return 'Alpha Vantage';
    }

    /**
     * Normalize Alpha Vantage response to standard format
     */
    private function normalizeAlphaVantageData(array $data, string $symbol): array
    {
        return [
            'symbol' => $symbol,
            'price' => (float)($data['05. price'] ?? 0),
            'change' => (float)($data['09. change'] ?? 0),
            'change_percent' => (float)str_replace('%', '', $data['10. change percent'] ?? '0'),
            'volume' => (int)($data['06. volume'] ?? 0),
            'open' => (float)($data['02. open'] ?? 0),
            'high' => (float)($data['03. high'] ?? 0),
            'low' => (float)($data['04. low'] ?? 0),
            'previous_close' => (float)($data['08. previous close'] ?? 0),
            'timestamp' => new DateTime(),
            'source' => $this->getName(),
            'latest_trading_day' => $data['07. latest trading day'] ?? date('Y-m-d')
        ];
    }
}
