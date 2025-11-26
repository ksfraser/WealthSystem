<?php
/**
 * Stock Data Service
 * 
 * Core business logic service for managing stock data operations.
 * Follows the Single Responsibility Principle - handles only stock data coordination.
 * Implements dependency injection for loose coupling.
 */

namespace Ksfraser\Finance\Services;

use Ksfraser\Finance\Interfaces\DataSourceInterface;
use Ksfraser\Finance\Interfaces\DataRepositoryInterface;
use Ksfraser\Finance\Interfaces\LLMProviderInterface;
use DateTime;

class StockDataService
{
    private $dataSources;
    private $repository;
    private $llmProvider;
    private $config;

    public function __construct(
        array $dataSources,
        DataRepositoryInterface $repository,
        ?LLMProviderInterface $llmProvider = null,
        array $config = []
    ) {
        $this->dataSources = $dataSources;
        $this->repository = $repository;
        $this->llmProvider = $llmProvider;
        $this->config = array_merge([
            'rate_limit_delay' => 500000, // 500ms default
            'max_retries' => 3,
            'timeout' => 30
        ], $config);
    }

    /**
     * Update stock price from available data sources
     */
    public function updateStockPrice(string $symbol): array
    {
        $results = [];
        $success = false;
        $bestData = null;

        foreach ($this->dataSources as $source) {
            if (!$source->isAvailable()) {
                $results[$source->getName()] = [
                    'success' => false,
                    'error' => 'Data source not available',
                    'data' => null
                ];
                continue;
            }

            $data = $this->fetchWithRetry($source, $symbol);
            if ($data) {
                $saved = $this->repository->saveStockPrice($data);
                $results[$source->getName()] = [
                    'success' => $saved,
                    'data' => $data,
                    'error' => $saved ? null : 'Failed to save to database'
                ];
                
                if ($saved) {
                    $success = true;
                    $bestData = $data; // Keep the most recent successful data
                }
            } else {
                $results[$source->getName()] = [
                    'success' => false,
                    'error' => 'Failed to fetch data',
                    'data' => null
                ];
            }

            // Rate limiting between sources
            usleep($this->config['rate_limit_delay']);
        }

        return [
            'symbol' => $symbol,
            'overall_success' => $success,
            'sources' => $results,
            'best_data' => $bestData,
            'timestamp' => new DateTime()
        ];
    }

    /**
     * Get comprehensive stock analysis using LLM
     */
    public function getStockAnalysis(string $symbol): ?array
    {
        if (!$this->llmProvider || !$this->llmProvider->isAvailable()) {
            return [
                'error' => 'LLM provider not available',
                'symbol' => $symbol,
                'timestamp' => new DateTime()
            ];
        }

        // Gather comprehensive data
        $stockData = $this->repository->getStockPrice($symbol);
        $companyData = $this->repository->getCompany($symbol);
        $historicalData = $this->getRecentHistoricalData($symbol);

        if (!$stockData) {
            return [
                'error' => 'No stock data found for symbol',
                'symbol' => $symbol,
                'timestamp' => new DateTime()
            ];
        }

        // Combine all available data
        $combinedData = [
            'current_price' => $stockData,
            'company_info' => $companyData,
            'historical_data' => $historicalData,
            'analysis_date' => date('Y-m-d H:i:s')
        ];

        try {
            $recommendation = $this->llmProvider->getRecommendation($symbol, $combinedData);
            return $recommendation;
        } catch (\Exception $e) {
            error_log("Error getting stock analysis for {$symbol}: " . $e->getMessage());
            return [
                'error' => 'Analysis generation failed',
                'symbol' => $symbol,
                'timestamp' => new DateTime()
            ];
        }
    }

    /**
     * Bulk update multiple stocks
     */
    public function bulkUpdateStocks(array $symbols): array
    {
        $results = [];
        $successCount = 0;
        $totalSymbols = count($symbols);

        foreach ($symbols as $index => $symbol) {
            try {
                $result = $this->updateStockPrice($symbol);
                $results[$symbol] = $result;
                
                if ($result['overall_success']) {
                    $successCount++;
                }
                
                // Progress logging
                if (($index + 1) % 10 === 0) {
                    error_log("Bulk update progress: " . ($index + 1) . "/{$totalSymbols} symbols processed");
                }
                
            } catch (\Exception $e) {
                $results[$symbol] = [
                    'symbol' => $symbol,
                    'overall_success' => false,
                    'error' => $e->getMessage(),
                    'timestamp' => new DateTime()
                ];
            }

            // Rate limiting between symbols
            usleep($this->config['rate_limit_delay']);
        }

        return [
            'total_symbols' => $totalSymbols,
            'successful_updates' => $successCount,
            'failed_updates' => $totalSymbols - $successCount,
            'success_rate' => $totalSymbols > 0 ? ($successCount / $totalSymbols) * 100 : 0,
            'results' => $results,
            'timestamp' => new DateTime()
        ];
    }

    /**
     * Get market overview for dashboard
     */
    public function getMarketOverview(): array
    {
        try {
            $latestPrices = $this->repository->getLatestPrices();
            $symbols = $this->repository->getAllSymbols();
            
            $marketStats = [
                'total_symbols' => count($symbols),
                'symbols_with_data' => count($latestPrices),
                'last_updated' => null,
                'top_gainers' => [],
                'top_losers' => [],
                'most_active' => []
            ];

            if (!empty($latestPrices)) {
                // Find last update time
                $lastUpdate = max(array_column($latestPrices, 'timestamp'));
                $marketStats['last_updated'] = $lastUpdate;

                // Sort for top gainers (by percentage)
                usort($latestPrices, function($a, $b) {
                    return $b['change_percent'] <=> $a['change_percent'];
                });
                $marketStats['top_gainers'] = array_slice($latestPrices, 0, 5);

                // Sort for top losers
                usort($latestPrices, function($a, $b) {
                    return $a['change_percent'] <=> $b['change_percent'];
                });
                $marketStats['top_losers'] = array_slice($latestPrices, 0, 5);

                // Sort for most active (by volume)
                usort($latestPrices, function($a, $b) {
                    return $b['volume'] <=> $a['volume'];
                });
                $marketStats['most_active'] = array_slice($latestPrices, 0, 5);
            }

            return $marketStats;
        } catch (\Exception $e) {
            error_log("Error getting market overview: " . $e->getMessage());
            return [
                'error' => 'Failed to generate market overview',
                'timestamp' => new DateTime()
            ];
        }
    }

    /**
     * Fetch data with retry mechanism
     */
    private function fetchWithRetry(DataSourceInterface $source, string $symbol): ?array
    {
        $retries = 0;
        $maxRetries = $this->config['max_retries'];

        while ($retries < $maxRetries) {
            try {
                $data = $source->fetchStockPrice($symbol);
                if ($data) {
                    return $data;
                }
            } catch (\Exception $e) {
                error_log("Attempt " . ($retries + 1) . " failed for {$symbol} from {$source->getName()}: " . $e->getMessage());
            }

            $retries++;
            if ($retries < $maxRetries) {
                // Exponential backoff
                usleep(pow(2, $retries) * 100000); // 200ms, 400ms, 800ms...
            }
        }

        return null;
    }

    /**
     * Get recent historical data for analysis
     */
    private function getRecentHistoricalData(string $symbol): array
    {
        $endDate = new DateTime();
        $startDate = new DateTime('-30 days'); // Last 30 days

        return $this->repository->getHistoricalPrices($symbol, $startDate, $endDate);
    }

    /**
     * Get stock data for analysis and strategy execution
     * 
     * @param string $symbol Stock symbol
     * @param string $period Time period (1d, 5d, 1m, 3m, 6m, 1y, 2y, 5y, 10y, ytd, max)
     * @return array Historical stock data
     */
    public function getStockData(string $symbol, string $period = '1y'): array
    {
        // Convert period to date range
        $endDate = new DateTime();
        $startDate = clone $endDate;
        
        switch ($period) {
            case '1d':
                $startDate->modify('-1 day');
                break;
            case '5d':
                $startDate->modify('-5 days');
                break;
            case '1m':
                $startDate->modify('-1 month');
                break;
            case '3m':
                $startDate->modify('-3 months');
                break;
            case '6m':
                $startDate->modify('-6 months');
                break;
            case '1y':
                $startDate->modify('-1 year');
                break;
            case '2y':
                $startDate->modify('-2 years');
                break;
            case '5y':
                $startDate->modify('-5 years');
                break;
            case '10y':
                $startDate->modify('-10 years');
                break;
            case 'ytd':
                $startDate = new DateTime(date('Y-01-01'));
                break;
            case 'max':
                $startDate->modify('-20 years'); // Reasonable max
                break;
            default:
                $startDate->modify('-1 year');
        }

        try {
            // First try to get from database
            $historicalData = $this->repository->getHistoricalPrices($symbol, $startDate, $endDate);
            
            // If no data or insufficient data, try to fetch from sources
            if (empty($historicalData) || count($historicalData) < 10) {
                $this->updateStockPrice($symbol); // Update current data
                
                // Try each data source for historical data
                foreach ($this->dataSources as $source) {
                    if ($source->isAvailable()) {
                        try {
                            $freshData = $source->fetchHistoricalData($symbol, $startDate, $endDate);
                            if (!empty($freshData)) {
                                // Save to database for future use
                                foreach ($freshData as $dataPoint) {
                                    $this->repository->saveStockPrice($dataPoint);
                                }
                                return $freshData;
                            }
                        } catch (\Exception $e) {
                            error_log("Failed to fetch historical data from {$source->getName()}: " . $e->getMessage());
                        }
                    }
                }
                
                // If still no data, return what we have
                return $historicalData;
            }
            
            return $historicalData;
            
        } catch (\Exception $e) {
            error_log("Error getting stock data for {$symbol}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get stock OHLCV data and technical indicators for a symbol and period
     *
     * @param string $symbol
     * @param string $period
     * @param array $indicators
     * @param array $params
     * @return array [ 'ohlcv' => [...], 'indicators' => [...] ]
     */
    public function getStockDataWithIndicators(string $symbol, string $period = '1y', array $indicators = ['rsi','sma','ema','macd','bbands'], array $params = []): array
    {
        // Get OHLCV data using existing logic
        $ohlcv = $this->getStockData($symbol, $period);
        // Only calculate indicators if we have enough data
        $indicatorsOut = [];
        if (!empty($ohlcv) && is_array($ohlcv) && isset($ohlcv[0]['Open'])) {
            // Normalize to expected format for TALibCalculators
            $ohlcvRows = [];
            foreach ($ohlcv as $row) {
                // Ensure all required keys exist
                if (isset($row['Date'], $row['Open'], $row['High'], $row['Low'], $row['Close'], $row['Volume'])) {
                    $ohlcvRows[] = [
                        'Date' => $row['Date'],
                        'Open' => $row['Open'],
                        'High' => $row['High'],
                        'Low' => $row['Low'],
                        'Close' => $row['Close'],
                        'Volume' => $row['Volume'],
                    ];
                }
            }
            if (count($ohlcvRows) > 0) {
                $indicatorsOut = \Services\Calculators\TALibCalculators::calculateIndicators($ohlcvRows, $indicators, $params);
            }
        }
        return [
            'ohlcv' => $ohlcv,
            'indicators' => $indicatorsOut
        ];
    }
}
