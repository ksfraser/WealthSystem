<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Base test class for unit tests
 * 
 * Provides common utilities and mock data for trading system tests
 */
abstract class TestBase extends TestCase
{
    /**
     * Generate sample market data for testing
     * 
     * @param int $days Number of days of data
     * @param float $startPrice Starting price
     * @param float $trend Daily trend multiplier (1.0 = no trend)
     * @param float $volatility Volatility factor
     * @return array Array of OHLCV data
     */
    protected function generateMarketData(int $days = 30, float $startPrice = 100.0, float $trend = 1.0, float $volatility = 0.02): array
    {
        $data = [];
        $currentPrice = $startPrice;
        
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-{$days} days +{$i} days"));
            
            // Generate daily price movement
            $randomFactor = 1 + (rand(-100, 100) / 100 * $volatility);
            $dailyPrice = $currentPrice * $trend * $randomFactor;
            
            // Generate OHLC based on daily price
            $open = $currentPrice;
            $close = $dailyPrice;
            $high = max($open, $close) * (1 + rand(0, 50) / 1000); // Random high
            $low = min($open, $close) * (1 - rand(0, 50) / 1000);  // Random low
            $volume = rand(100000, 1000000);
            
            $data[] = [
                'date' => $date,
                'open' => round($open, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($close, 2),
                'volume' => $volume,
                'adj_close' => round($close, 2)
            ];
            
            $currentPrice = $dailyPrice;
        }
        
        return $data;
    }

    /**
     * Generate trending market data (uptrend or downtrend)
     * 
     * @param int $days Number of days
     * @param float $startPrice Starting price
     * @param bool $uptrend True for uptrend, false for downtrend
     * @param float $strength Trend strength (0.01 = 1% per day)
     * @return array Array of OHLCV data
     */
    protected function generateTrendingData(int $days = 30, float $startPrice = 100.0, bool $uptrend = true, float $strength = 0.01): array
    {
        $trend = $uptrend ? (1 + $strength) : (1 - $strength);
        return $this->generateMarketData($days, $startPrice, $trend, 0.015);
    }

    /**
     * Generate breakout pattern data
     * 
     * @param int $consolidationDays Days of sideways movement
     * @param int $breakoutDay Day when breakout occurs
     * @param float $basePrice Base price for consolidation
     * @param float $breakoutPercent Breakout percentage
     * @return array Array of OHLCV data
     */
    protected function generateBreakoutData(int $consolidationDays = 20, int $breakoutDay = 15, float $basePrice = 100.0, float $breakoutPercent = 0.05): array
    {
        $data = [];
        
        // Consolidation phase
        for ($i = 0; $i < $consolidationDays; $i++) {
            $date = date('Y-m-d', strtotime("-{$consolidationDays} days +{$i} days"));
            $noise = 1 + (rand(-20, 20) / 1000); // 2% noise
            $price = $basePrice * $noise;
            
            $data[] = [
                'date' => $date,
                'open' => round($price, 2),
                'high' => round($price * 1.01, 2),
                'low' => round($price * 0.99, 2),
                'close' => round($price, 2),
                'volume' => rand(100000, 200000),
                'adj_close' => round($price, 2)
            ];
        }
        
        // Breakout phase
        for ($i = $consolidationDays; $i < $consolidationDays + 10; $i++) {
            $date = date('Y-m-d', strtotime("-{$consolidationDays} days +{$i} days"));
            $multiplier = 1 + ($breakoutPercent * ($i - $consolidationDays + 1));
            $price = $basePrice * $multiplier;
            
            $data[] = [
                'date' => $date,
                'open' => round($price * 0.98, 2),
                'high' => round($price * 1.02, 2),
                'low' => round($price * 0.97, 2),
                'close' => round($price, 2),
                'volume' => rand(500000, 1500000), // Higher volume on breakout
                'adj_close' => round($price, 2)
            ];
        }
        
        return $data;
    }

    /**
     * Generate volatile market data
     * 
     * @param int $days Number of days
     * @param float $basePrice Base price
     * @param float $volatility Volatility factor
     * @return array Array of OHLCV data
     */
    protected function generateVolatileData(int $days = 30, float $basePrice = 100.0, float $volatility = 0.05): array
    {
        return $this->generateMarketData($days, $basePrice, 1.0, $volatility);
    }

    /**
     * Assert that signal contains required fields
     * 
     * @param array|null $signal Signal to validate
     * @param array $requiredFields Required field names
     */
    protected function assertValidSignal(?array $signal, array $requiredFields = ['action', 'price', 'confidence']): void
    {
        $this->assertNotNull($signal, 'Signal should not be null');
        $this->assertIsArray($signal, 'Signal should be an array');
        
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $signal, "Signal should contain '{$field}' field");
        }
        
        // Validate action field
        if (isset($signal['action'])) {
            $this->assertContains($signal['action'], ['BUY', 'SELL', 'SHORT', 'COVER', 'HOLD'], 'Invalid action type');
        }
        
        // Validate price field
        if (isset($signal['price'])) {
            $this->assertIsNumeric($signal['price'], 'Price should be numeric');
            $this->assertGreaterThan(0, $signal['price'], 'Price should be positive');
        }
        
        // Validate confidence field
        if (isset($signal['confidence'])) {
            $this->assertIsNumeric($signal['confidence'], 'Confidence should be numeric');
            $this->assertGreaterThanOrEqual(0, $signal['confidence'], 'Confidence should be >= 0');
            $this->assertLessThanOrEqual(1, $signal['confidence'], 'Confidence should be <= 1');
        }
    }

    /**
     * Assert that no signal is generated (null or empty)
     * 
     * @param array|null $signal Signal to check
     */
    protected function assertNoSignal(?array $signal): void
    {
        $this->assertNull($signal, 'No signal should be generated');
    }

    /**
     * Mock StockDataService for testing
     * 
     * @param array $marketData Data to return from getStockData
     * @return MockObject
     */
    protected function createMockStockDataService(array $marketData = []): MockObject
    {
        $mock = $this->createMock(\Ksfraser\Finance\Services\StockDataService::class);
        
        $mock->method('getStockData')
             ->willReturn($marketData);
             
        return $mock;
    }

    /**
     * Create a mock HTTP response for API testing
     * 
     * @param int $statusCode HTTP status code
     * @param mixed $body Response body
     * @return MockObject
     */
    protected function createMockHttpResponse(int $statusCode = 200, $body = ''): MockObject
    {
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getBody')->willReturn($body);
        
        return $response;
    }

    /**
     * Assert that performance metrics are valid
     * 
     * @param array $metrics Performance metrics to validate
     */
    protected function assertValidPerformanceMetrics(array $metrics): void
    {
        $requiredMetrics = [
            'total_return', 'sharpe_ratio', 'max_drawdown', 
            'win_rate', 'total_trades', 'winning_trades'
        ];
        
        foreach ($requiredMetrics as $metric) {
            $this->assertArrayHasKey($metric, $metrics, "Missing performance metric: {$metric}");
            $this->assertIsNumeric($metrics[$metric], "Performance metric '{$metric}' should be numeric");
        }
        
        // Specific validations
        $this->assertGreaterThanOrEqual(0, $metrics['win_rate'], 'Win rate should be >= 0');
        $this->assertLessThanOrEqual(1, $metrics['win_rate'], 'Win rate should be <= 1');
        $this->assertGreaterThanOrEqual(0, $metrics['total_trades'], 'Total trades should be >= 0');
        $this->assertLessThanOrEqual($metrics['total_trades'], $metrics['winning_trades'], 'Winning trades cannot exceed total trades');
    }

    /**
     * Create test portfolio data
     * 
     * @param float $initialValue Initial portfolio value
     * @return array Portfolio data structure
     */
    protected function createTestPortfolio(float $initialValue = 100000.0): array
    {
        return [
            'initial_value' => $initialValue,
            'current_value' => $initialValue,
            'cash' => $initialValue * 0.1, // 10% cash
            'positions' => [],
            'trades' => [],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Create test trade data
     * 
     * @param string $symbol Symbol traded
     * @param string $action Buy/Sell action
     * @param float $price Trade price
     * @param int $quantity Number of shares
     * @return array Trade data structure
     */
    protected function createTestTrade(string $symbol = 'AAPL', string $action = 'BUY', float $price = 150.0, int $quantity = 100): array
    {
        return [
            'symbol' => $symbol,
            'action' => $action,
            'price' => $price,
            'quantity' => $quantity,
            'total_value' => $price * $quantity,
            'commission' => 1.0,
            'timestamp' => date('Y-m-d H:i:s'),
            'strategy' => 'test_strategy'
        ];
    }
}
