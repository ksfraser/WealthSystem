<?php
/**
 * Stock Controller
 * 
 * Handles HTTP requests for stock data operations.
 * Follows the MVC pattern - Controller layer for request handling.
 * Implements dependency injection for loose coupling.
 */

namespace Ksfraser\Finance\Controllers;

use Ksfraser\Finance\Services\StockDataService;
use DateTime;

class StockController
{
    private $stockService;

    public function __construct(StockDataService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Update single stock price
     * 
     * @param string $symbol Stock symbol
     * @return array Response array with success status and data
     */
    public function updateStock(string $symbol): array
    {
        try {
            // Validate symbol
            if (empty($symbol) || !$this->isValidSymbol($symbol)) {
                return [
                    'success' => false,
                    'error' => 'Invalid stock symbol provided',
                    'symbol' => $symbol
                ];
            }

            $result = $this->stockService->updateStockPrice($symbol);
            
            return [
                'success' => $result['overall_success'],
                'data' => $result,
                'message' => $result['overall_success'] 
                    ? "Successfully updated stock data for {$symbol}"
                    : "Failed to update stock data for {$symbol}"
            ];
        } catch (\Exception $e) {
            error_log("StockController::updateStock error for {$symbol}: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Internal server error: ' . $e->getMessage(),
                'symbol' => $symbol
            ];
        }
    }

    /**
     * Get AI analysis for a stock
     * 
     * @param string $symbol Stock symbol
     * @return array Analysis result
     */
    public function getAnalysis(string $symbol): array
    {
        try {
            if (empty($symbol) || !$this->isValidSymbol($symbol)) {
                return [
                    'success' => false,
                    'error' => 'Invalid stock symbol provided',
                    'symbol' => $symbol
                ];
            }

            $analysis = $this->stockService->getStockAnalysis($symbol);
            
            if (!$analysis) {
                return [
                    'success' => false,
                    'error' => 'Unable to generate analysis',
                    'symbol' => $symbol
                ];
            }

            if (isset($analysis['error'])) {
                return [
                    'success' => false,
                    'error' => $analysis['error'],
                    'symbol' => $symbol
                ];
            }

            return [
                'success' => true,
                'data' => $analysis,
                'message' => "Analysis generated successfully for {$symbol}"
            ];
        } catch (\Exception $e) {
            error_log("StockController::getAnalysis error for {$symbol}: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Internal server error: ' . $e->getMessage(),
                'symbol' => $symbol
            ];
        }
    }

    /**
     * Bulk update multiple stocks
     * 
     * @param array $symbols Array of stock symbols
     * @return array Bulk update results
     */
    public function bulkUpdate(array $symbols): array
    {
        try {
            // Validate input
            if (empty($symbols)) {
                return [
                    'success' => false,
                    'error' => 'No symbols provided for bulk update'
                ];
            }

            // Validate and clean symbols
            $validSymbols = [];
            $invalidSymbols = [];
            
            foreach ($symbols as $symbol) {
                if ($this->isValidSymbol($symbol)) {
                    $validSymbols[] = strtoupper(trim($symbol));
                } else {
                    $invalidSymbols[] = $symbol;
                }
            }

            if (empty($validSymbols)) {
                return [
                    'success' => false,
                    'error' => 'No valid symbols provided',
                    'invalid_symbols' => $invalidSymbols
                ];
            }

            // Limit bulk operations to prevent abuse
            if (count($validSymbols) > 100) {
                return [
                    'success' => false,
                    'error' => 'Bulk update limited to 100 symbols maximum',
                    'provided_count' => count($validSymbols)
                ];
            }

            $result = $this->stockService->bulkUpdateStocks($validSymbols);
            
            return [
                'success' => $result['successful_updates'] > 0,
                'data' => $result,
                'invalid_symbols' => $invalidSymbols,
                'message' => sprintf(
                    "Bulk update completed: %d/%d symbols updated successfully", 
                    $result['successful_updates'], 
                    $result['total_symbols']
                )
            ];
        } catch (\Exception $e) {
            error_log("StockController::bulkUpdate error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Internal server error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get market overview
     * 
     * @return array Market statistics
     */
    public function getMarketOverview(): array
    {
        try {
            $overview = $this->stockService->getMarketOverview();
            
            if (isset($overview['error'])) {
                return [
                    'success' => false,
                    'error' => $overview['error']
                ];
            }

            return [
                'success' => true,
                'data' => $overview,
                'message' => 'Market overview generated successfully'
            ];
        } catch (\Exception $e) {
            error_log("StockController::getMarketOverview error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Internal server error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get stock price history
     * 
     * @param string $symbol Stock symbol
     * @param int $days Number of days to look back (default 30)
     * @return array Historical data
     */
    public function getHistory(string $symbol, int $days = 30): array
    {
        try {
            if (empty($symbol) || !$this->isValidSymbol($symbol)) {
                return [
                    'success' => false,
                    'error' => 'Invalid stock symbol provided',
                    'symbol' => $symbol
                ];
            }

            if ($days < 1 || $days > 365) {
                return [
                    'success' => false,
                    'error' => 'Days parameter must be between 1 and 365',
                    'symbol' => $symbol
                ];
            }

            $endDate = new DateTime();
            $startDate = new DateTime("-{$days} days");
            
            $history = $this->stockService->getRepository()->getHistoricalPrices($symbol, $startDate, $endDate);

            return [
                'success' => true,
                'data' => [
                    'symbol' => $symbol,
                    'period' => $days,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'prices' => $history
                ],
                'message' => "Historical data retrieved for {$symbol}"
            ];
        } catch (\Exception $e) {
            error_log("StockController::getHistory error for {$symbol}: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Internal server error: ' . $e->getMessage(),
                'symbol' => $symbol
            ];
        }
    }

    /**
     * Validate stock symbol format
     * 
     * @param string $symbol Symbol to validate
     * @return bool True if valid
     */
    private function isValidSymbol(string $symbol): bool
    {
        if (empty($symbol)) {
            return false;
        }

        // Clean the symbol
        $symbol = strtoupper(trim($symbol));
        
        // Basic validation: 1-10 characters, letters, numbers, dots, hyphens
        if (!preg_match('/^[A-Z0-9\.\-]{1,10}$/', $symbol)) {
            return false;
        }

        // Additional validation for common patterns
        $validPatterns = [
            '/^[A-Z]{1,5}$/',           // Standard US stocks (AAPL, MSFT)
            '/^[A-Z]{1,5}\.[A-Z]{2}$/', // International stocks (TSM.TW)
            '/^[A-Z]{1,5}-[A-Z]$/',     // Preferred shares (BRK-A)
            '/^[A-Z]{1,5}\.TO$/',       // Toronto exchange (.TO)
            '/^[A-Z]{1,5}\.L$/',        // London exchange (.L)
        ];

        foreach ($validPatterns as $pattern) {
            if (preg_match($pattern, $symbol)) {
                return true;
            }
        }

        return false;
    }
}
