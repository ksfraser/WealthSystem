<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Base test class providing common testing utilities
 */
class TestBaseSimple extends TestCase
{
    /**
     * Create a basic mock for StockDataService
     */
    protected function createMockStockDataService()
    {
        // Use createStub instead of createMock for simpler mocking
        return $this->createStub(\Ksfraser\Finance\Services\StockDataService::class);
    }

    /**
     * Generate sample market data for testing
     */
    protected function generateMarketData(int $days = 60, float $basePrice = 100.0): array
    {
        $data = [];
        $price = $basePrice;

        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            
            // Add some price variation
            $change = (rand(-5, 5) / 100) * $price;
            $price = max(10, $price + $change);
            
            $high = $price * (1 + rand(0, 3) / 100);
            $low = $price * (1 - rand(0, 3) / 100);
            
            $data[] = [
                'date' => $date,
                'open' => round($price, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($price, 2),
                'volume' => rand(100000, 1000000)
            ];
        }

        return array_reverse($data); // Return chronological order
    }

    /**
     * Generate market data with a clear breakout pattern
     */
    protected function generateBreakoutData(int $periodDays, int $breakoutLength, float $basePrice = 100.0, float $breakoutPercent = 0.05): array
    {
        $data = [];
        $price = $basePrice;

        // Generate stable period
        for ($i = 0; $i < $periodDays; $i++) {
            $date = date('Y-m-d', strtotime("-" . ($periodDays + $breakoutLength - $i) . " days"));
            
            // Keep price relatively stable
            $change = (rand(-1, 1) / 100) * $price;
            $price = max(10, $price + $change);
            
            $high = $price * (1 + rand(0, 1) / 100);
            $low = $price * (1 - rand(0, 1) / 100);
            
            $data[] = [
                'date' => $date,
                'open' => round($price, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($price, 2),
                'volume' => rand(100000, 500000)
            ];
        }

        // Generate breakout period
        for ($i = 0; $i < $breakoutLength; $i++) {
            $date = date('Y-m-d', strtotime("-" . ($breakoutLength - $i) . " days"));
            
            // Strong upward movement
            $change = ($breakoutPercent / $breakoutLength) * $price;
            $price = $price + $change;
            
            $high = $price * (1 + rand(1, 3) / 100);
            $low = $price * (1 - rand(0, 1) / 100);
            
            $data[] = [
                'date' => $date,
                'open' => round($price - $change, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($price, 2),
                'volume' => rand(500000, 2000000) // Higher volume on breakout
            ];
        }

        return $data;
    }

    /**
     * Assert that a signal has valid structure
     */
    protected function assertValidSignal($signal, array $requiredFields = []): void
    {
        $this->assertNotNull($signal, 'Signal should not be null');
        $this->assertIsArray($signal, 'Signal should be an array');

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $signal, "Signal should contain field: {$field}");
        }

        if (isset($signal['action'])) {
            $this->assertContains($signal['action'], ['BUY', 'SELL', 'HOLD'], 'Action should be BUY, SELL, or HOLD');
        }

        if (isset($signal['confidence'])) {
            $this->assertIsFloat($signal['confidence'], 'Confidence should be a float');
            $this->assertGreaterThanOrEqual(0, $signal['confidence'], 'Confidence should be >= 0');
            $this->assertLessThanOrEqual(1, $signal['confidence'], 'Confidence should be <= 1');
        }
    }

    /**
     * Assert that no signal was generated
     */
    protected function assertNoSignal($signal): void
    {
        $this->assertTrue(
            $signal === null || 
            (is_array($signal) && isset($signal['action']) && $signal['action'] === 'HOLD'),
            'Expected no signal or HOLD action'
        );
    }

    /**
     * Generate market data with calculated true range for N-value testing
     */
    protected function generateMarketDataWithTrueRange(int $days = 60, float $basePrice = 100.0): array
    {
        $data = [];
        $price = $basePrice;
        $previousClose = $basePrice;

        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            
            // Add some price variation
            $change = (rand(-3, 3) / 100) * $price;
            $price = max(10, $price + $change);
            
            $high = $price * (1 + rand(1, 4) / 100);
            $low = $price * (1 - rand(1, 4) / 100);
            
            // Ensure true range can be calculated properly
            $trueRange = max(
                $high - $low,
                abs($high - $previousClose),
                abs($low - $previousClose)
            );
            
            $data[] = [
                'date' => $date,
                'open' => round($price, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($price, 2),
                'volume' => rand(100000, 1000000),
                'true_range' => round($trueRange, 2)
            ];
            
            $previousClose = $price;
        }

        return array_reverse($data); // Return chronological order
    }

    /**
     * Generate market data with downward breakout pattern
     */
    protected function generateDownwardBreakoutData(int $periodDays, int $breakdownLength, float $basePrice = 100.0, float $breakdownPercent = -0.05): array
    {
        $data = [];
        $price = $basePrice;

        // Generate stable period
        for ($i = 0; $i < $periodDays; $i++) {
            $date = date('Y-m-d', strtotime("-" . ($periodDays + $breakdownLength - $i) . " days"));
            
            // Keep price relatively stable
            $change = (rand(-1, 1) / 100) * $price;
            $price = max(10, $price + $change);
            
            $high = $price * (1 + rand(0, 1) / 100);
            $low = $price * (1 - rand(0, 1) / 100);
            
            $data[] = [
                'date' => $date,
                'open' => round($price, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($price, 2),
                'volume' => rand(100000, 500000)
            ];
        }

        // Generate breakdown period
        for ($i = 0; $i < $breakdownLength; $i++) {
            $date = date('Y-m-d', strtotime("-" . ($breakdownLength - $i) . " days"));
            
            // Strong downward movement
            $change = ($breakdownPercent / $breakdownLength) * $price;
            $price = max(10, $price + $change);
            
            $high = $price * (1 + rand(0, 1) / 100);
            $low = $price * (1 - rand(1, 3) / 100);
            
            $data[] = [
                'date' => $date,
                'open' => round($price - $change, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($price, 2),
                'volume' => rand(500000, 2000000) // Higher volume on breakdown
            ];
        }

        return $data;
    }

    /**
     * Generate volatile market data for stress testing
     */
    protected function generateVolatileMarketData(int $days = 60, float $basePrice = 100.0, float $volatility = 0.05): array
    {
        $data = [];
        $price = $basePrice;

        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            
            // High volatility price changes
            $change = (rand(-10, 10) / 100) * $volatility * $price;
            $price = max(10, $price + $change);
            
            // Wide high-low range
            $highRange = rand(2, 8) / 100;
            $lowRange = rand(2, 8) / 100;
            
            $high = $price * (1 + $highRange);
            $low = $price * (1 - $lowRange);
            
            $data[] = [
                'date' => $date,
                'open' => round($price, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($price, 2),
                'volume' => rand(500000, 3000000) // High volume in volatile markets
            ];
        }

        return array_reverse($data); // Return chronological order
    }
}
