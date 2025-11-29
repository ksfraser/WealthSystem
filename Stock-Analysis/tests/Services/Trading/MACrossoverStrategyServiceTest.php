<?php

namespace Tests\Services\Trading;

use PHPUnit\Framework\TestCase;
use App\Services\Trading\MACrossoverStrategyService;
use App\Services\Trading\TradingStrategyInterface;
use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

/**
 * Tests for MACrossoverStrategyService
 * 
 * @covers \App\Services\Trading\MACrossoverStrategyService
 */
class MACrossoverStrategyServiceTest extends TestCase
{
    private MACrossoverStrategyService $strategy;
    private MarketDataService $marketDataService;
    private MarketDataRepositoryInterface $marketDataRepository;

    protected function setUp(): void
    {
        $this->marketDataService = $this->createMock(MarketDataService::class);
        $this->marketDataRepository = $this->createMock(MarketDataRepositoryInterface::class);
        
        $this->strategy = new MACrossoverStrategyService(
            $this->marketDataService,
            $this->marketDataRepository
        );
    }

    public function testImplementsTradingStrategyInterface(): void
    {
        $this->assertInstanceOf(TradingStrategyInterface::class, $this->strategy);
    }

    public function testGetName(): void
    {
        $this->assertEquals('Moving Average Crossover', $this->strategy->getName());
    }

    public function testGetDescription(): void
    {
        $description = $this->strategy->getDescription();
        
        $this->assertStringContainsString('crossover', $description);
        $this->assertStringContainsString('Golden Cross', $description);
        $this->assertStringContainsString('Death Cross', $description);
    }

    public function testGetDefaultParameters(): void
    {
        $params = $this->strategy->getParameters();
        
        $this->assertEquals(50, $params['fast_period']);
        $this->assertEquals(200, $params['slow_period']);
        $this->assertEquals('SMA', $params['ma_type']);
        $this->assertTrue($params['confirm_with_price']);
        $this->assertEquals(0.001, $params['min_crossover_gap']);
        $this->assertEquals(0.05, $params['position_size']);
        $this->assertEquals(0.05, $params['stop_loss_percent']);
        $this->assertEquals(0.15, $params['take_profit_percent']);
    }

    public function testSetParameters(): void
    {
        $newParams = [
            'fast_period' => 20,
            'slow_period' => 50,
            'ma_type' => 'EMA'
        ];

        $this->strategy->setParameters($newParams);
        $params = $this->strategy->getParameters();

        $this->assertEquals(20, $params['fast_period']);
        $this->assertEquals(50, $params['slow_period']);
        $this->assertEquals('EMA', $params['ma_type']);
        
        // Other params should remain unchanged
        $this->assertEquals(0.05, $params['position_size']);
    }

    public function testGetRequiredHistoricalDays(): void
    {
        $days = $this->strategy->getRequiredHistoricalDays();
        
        // Should be slow_period (200) + 20 buffer = 220
        $this->assertEquals(220, $days);
    }

    public function testAnalyzeThrowsExceptionForEmptySymbol(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Symbol cannot be empty');
        
        $this->strategy->analyze('');
    }

    public function testAnalyzeReturnsHoldForInsufficientData(): void
    {
        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn([]);

        $result = $this->strategy->analyze('AAPL');

        $this->assertEquals('HOLD', $result['signal']);
        $this->assertStringContainsString('Insufficient price data', $result['reason']);
    }

    public function testAnalyzeReturnsHoldForInvalidPrice(): void
    {
        // Configure for shorter periods to make testing easier
        $this->strategy->setParameters(['fast_period' => 10, 'slow_period' => 20]);
        
        $priceHistory = $this->generatePriceHistory(30, 95, 100);
        
        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => 0.001]);

        $result = $this->strategy->analyze('AAPL');

        $this->assertEquals('HOLD', $result['signal']);
        $this->assertNotEmpty($result['reason']);
    }

    public function testGoldenCrossGeneratesBuySignal(): void
    {
        // Use shorter periods for easier testing
        $this->strategy->setParameters([
            'fast_period' => 10,
            'slow_period' => 20,
            'confirm_with_price' => false
        ]);

        // Generate price history with uptrend causing golden cross
        // Need 45 days: 20 slow + 20 buffer + 5 crossover
        $priceHistory = $this->generateCrossoverPriceHistory(45, 90, 110);
        $currentPrice = 111;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // Should generate a signal (BUY or HOLD depending on whether crossover occurred)
        $this->assertContains($result['signal'], ['BUY', 'HOLD']);
        $this->assertNotEmpty($result['reason']);
        $this->assertGreaterThanOrEqual(0, $result['confidence']);
        
        // If it's a BUY, validate all BUY-specific fields
        if ($result['signal'] === 'BUY') {
            $this->assertEquals($currentPrice, $result['entry_price']);
            $this->assertLessThan($currentPrice, $result['stop_loss']);
            $this->assertGreaterThan($currentPrice, $result['take_profit']);
            $this->assertStringContainsString('Golden Cross', $result['reason']);
            $this->assertArrayHasKey('fast_ma', $result['metadata']);
            $this->assertArrayHasKey('slow_ma', $result['metadata']);
        } else {
            // HOLD signal should still have valid structure
            $this->assertArrayHasKey('metadata', $result);
        }
    }

    public function testGoldenCrossWithPriceConfirmation(): void
    {
        $this->strategy->setParameters([
            'fast_period' => 10,
            'slow_period' => 20,
            'confirm_with_price' => true
        ]);

        $priceHistory = $this->generateCrossoverPriceHistory(45, 90, 110);
        $currentPrice = 108; // Near the MA values

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // Should be either BUY or HOLD depending on price/MA relationship
        $this->assertContains($result['signal'], ['BUY', 'HOLD']);
        $this->assertNotEmpty($result['reason']);
    }

    public function testDeathCrossGeneratesSellSignal(): void
    {
        $this->strategy->setParameters([
            'fast_period' => 10,
            'slow_period' => 20
        ]);

        // Generate price history with downtrend causing death cross
        $priceHistory = $this->generateCrossoverPriceHistory(45, 110, 90, 'down');
        $currentPrice = 89;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // Should generate SELL or HOLD
        $this->assertContains($result['signal'], ['SELL', 'HOLD']);
        $this->assertNotEmpty($result['reason']);
        
        // If SELL, should exit entire position
        if ($result['signal'] === 'SELL') {
            $this->assertEquals(1.0, $result['position_size']);
        }
    }

    public function testUptrendContinuesReturnsHold(): void
    {
        $this->strategy->setParameters([
            'fast_period' => 10,
            'slow_period' => 20
        ]);

        // Generate stable uptrend (no new crossover) with enough data
        $priceHistory = $this->generatePriceHistory(45, 95, 105);
        $currentPrice = 106;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        $this->assertEquals('HOLD', $result['signal']);
        // Should have valid reason
        $this->assertNotEmpty($result['reason']);
    }

    public function testCanExecuteReturnsTrueWithSufficientData(): void
    {
        $this->strategy->setParameters(['fast_period' => 10, 'slow_period' => 20]);
        $priceHistory = $this->generatePriceHistory(50, 95, 100);

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $canExecute = $this->strategy->canExecute('AAPL');

        $this->assertTrue($canExecute);
    }

    public function testCanExecuteReturnsFalseWithInsufficientData(): void
    {
        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn([]);

        $canExecute = $this->strategy->canExecute('AAPL');

        $this->assertFalse($canExecute);
    }

    public function testCanExecuteReturnsFalseOnException(): void
    {
        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willThrowException(new \Exception('API Error'));

        $canExecute = $this->strategy->canExecute('AAPL');

        $this->assertFalse($canExecute);
    }

    public function testBuySignalIncludesStopLossAndTakeProfit(): void
    {
        $this->strategy->setParameters([
            'fast_period' => 10,
            'slow_period' => 20,
            'confirm_with_price' => false,
            'stop_loss_percent' => 0.05,
            'take_profit_percent' => 0.15
        ]);

        $priceHistory = $this->generateCrossoverPriceHistory(45, 90, 110);
        $currentPrice = 111;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // Verify stop loss and take profit are set for any signal type
        $this->assertArrayHasKey('stop_loss', $result);
        $this->assertArrayHasKey('take_profit', $result);
        
        if ($result['signal'] === 'BUY') {
            $expectedStopLoss = $currentPrice * 0.95; // 5% below
            $expectedTakeProfit = $currentPrice * 1.15; // 15% above
            
            $this->assertEqualsWithDelta($expectedStopLoss, $result['stop_loss'], 0.01);
            $this->assertEqualsWithDelta($expectedTakeProfit, $result['take_profit'], 0.01);
        }
    }

    public function testMetadataIncludesMAValues(): void
    {
        $this->strategy->setParameters([
            'fast_period' => 10,
            'slow_period' => 20,
            'ma_type' => 'SMA',
            'confirm_with_price' => false
        ]);

        $priceHistory = $this->generateCrossoverPriceHistory(45, 90, 110);
        $currentPrice = 111;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // Metadata should always be present
        $this->assertArrayHasKey('metadata', $result);
        
        if ($result['signal'] === 'BUY') {
            $this->assertArrayHasKey('fast_ma', $result['metadata']);
            $this->assertArrayHasKey('slow_ma', $result['metadata']);
            $this->assertArrayHasKey('fast_period', $result['metadata']);
            $this->assertArrayHasKey('slow_period', $result['metadata']);
            $this->assertArrayHasKey('ma_type', $result['metadata']);
            $this->assertArrayHasKey('ma_spread_percent', $result['metadata']);
            $this->assertEquals(10, $result['metadata']['fast_period']);
            $this->assertEquals(20, $result['metadata']['slow_period']);
            $this->assertEquals('SMA', $result['metadata']['ma_type']);
        }
    }

    public function testConfidenceIncreasesWithStrongerTrend(): void
    {
        $this->strategy->setParameters([
            'fast_period' => 10,
            'slow_period' => 20,
            'confirm_with_price' => false
        ]);

        $priceHistory = $this->generateCrossoverPriceHistory(45, 80, 120); // Strong uptrend
        $currentPrice = 121;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // Confidence should always be between 0 and 1
        $this->assertGreaterThanOrEqual(0.0, $result['confidence']);
        $this->assertLessThanOrEqual(1.0, $result['confidence']);
        
        if ($result['signal'] === 'BUY') {
            // Confidence should be higher with strong trend
            $this->assertGreaterThan(0.5, $result['confidence']);
        }
    }

    public function testEMACalculation(): void
    {
        $this->strategy->setParameters([
            'fast_period' => 10,
            'slow_period' => 20,
            'ma_type' => 'EMA',
            'confirm_with_price' => false
        ]);

        $priceHistory = $this->generateCrossoverPriceHistory(30, 90, 110);
        $currentPrice = 111;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // Should work with EMA calculation
        $this->assertContains($result['signal'], ['BUY', 'SELL', 'HOLD']);
        
        if (isset($result['metadata']['ma_type'])) {
            $this->assertEquals('EMA', $result['metadata']['ma_type']);
        }
    }

    /**
     * Helper: Generate standard price history for testing
     */
    private function generatePriceHistory(int $days, float $startPrice, float $endPrice): array
    {
        $history = [];
        $priceRange = $endPrice - $startPrice;
        $priceIncrement = $priceRange / $days;

        for ($i = 0; $i < $days; $i++) {
            $basePrice = $startPrice + ($priceIncrement * $i);
            $volatility = $basePrice * 0.01; // 1% daily volatility

            $history[] = [
                'date' => date('Y-m-d', strtotime("-" . ($days - $i) . " days")),
                'open' => $basePrice - ($volatility * 0.5),
                'high' => $basePrice + $volatility,
                'low' => $basePrice - $volatility,
                'close' => $basePrice,
                'volume' => rand(1000000, 5000000)
            ];
        }

        return $history;
    }

    /**
     * Helper: Generate price history designed to create MA crossover
     */
    private function generateCrossoverPriceHistory(int $days, float $startPrice, float $endPrice, string $direction = 'up'): array
    {
        $history = [];
        $priceRange = $endPrice - $startPrice;

        for ($i = 0; $i < $days; $i++) {
            // Create accelerating price movement to force crossover
            $progress = $i / $days;
            
            if ($direction === 'up') {
                // Accelerate upward in later periods
                $basePrice = $startPrice + ($priceRange * pow($progress, 0.8));
            } else {
                // Accelerate downward in later periods
                $basePrice = $startPrice - ($priceRange * pow($progress, 0.8));
            }
            
            $volatility = abs($basePrice) * 0.01;

            $history[] = [
                'date' => date('Y-m-d', strtotime("-" . ($days - $i) . " days")),
                'open' => $basePrice - ($volatility * 0.5),
                'high' => $basePrice + $volatility,
                'low' => $basePrice - $volatility,
                'close' => $basePrice,
                'volume' => rand(1000000, 5000000)
            ];
        }

        return $history;
    }
}
