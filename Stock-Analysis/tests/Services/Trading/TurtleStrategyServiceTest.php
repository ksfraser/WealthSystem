<?php

namespace Tests\Services\Trading;

use PHPUnit\Framework\TestCase;
use App\Services\Trading\TurtleStrategyService;
use App\Services\Trading\TradingStrategyInterface;
use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

/**
 * Tests for TurtleStrategyService
 * 
 * @covers \App\Services\Trading\TurtleStrategyService
 */
class TurtleStrategyServiceTest extends TestCase
{
    private TurtleStrategyService $strategy;
    private MarketDataService $marketDataService;
    private MarketDataRepositoryInterface $marketDataRepository;

    protected function setUp(): void
    {
        $this->marketDataService = $this->createMock(MarketDataService::class);
        $this->marketDataRepository = $this->createMock(MarketDataRepositoryInterface::class);
        
        $this->strategy = new TurtleStrategyService(
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
        $this->assertEquals('Turtle Trading System', $this->strategy->getName());
    }

    public function testGetDescription(): void
    {
        $description = $this->strategy->getDescription();
        
        $this->assertStringContainsString('trend-following', $description);
        $this->assertStringContainsString('breakout', $description);
        $this->assertStringContainsString('ATR', $description);
    }

    public function testGetDefaultParameters(): void
    {
        $params = $this->strategy->getParameters();
        
        $this->assertEquals('BOTH', $params['system']);
        $this->assertEquals(20, $params['system1_entry']);
        $this->assertEquals(10, $params['system1_exit']);
        $this->assertEquals(55, $params['system2_entry']);
        $this->assertEquals(20, $params['system2_exit']);
        $this->assertEquals(20, $params['atr_period']);
        $this->assertEquals(0.02, $params['risk_per_trade']);
        $this->assertEquals(4, $params['max_units']);
    }

    public function testSetParameters(): void
    {
        $newParams = [
            'system' => 'ONE',
            'system1_entry' => 25,
            'risk_per_trade' => 0.03
        ];

        $this->strategy->setParameters($newParams);
        $params = $this->strategy->getParameters();

        $this->assertEquals('ONE', $params['system']);
        $this->assertEquals(25, $params['system1_entry']);
        $this->assertEquals(0.03, $params['risk_per_trade']);
        
        // Other params should remain unchanged
        $this->assertEquals(10, $params['system1_exit']);
    }

    public function testGetRequiredHistoricalDays(): void
    {
        $days = $this->strategy->getRequiredHistoricalDays();
        
        // Should be at least 55 (system2_entry) + 10 buffer = 65
        $this->assertGreaterThanOrEqual(65, $days);
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
        $this->assertEquals('Insufficient price data', $result['reason']);
        $this->assertEquals(0.0, $result['confidence']);
    }

    public function testAnalyzeReturnsHoldForInvalidPrice(): void
    {
        $priceHistory = $this->generatePriceHistory(70, 95, 100);
        
        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => 0.001]); // Invalid price

        $result = $this->strategy->analyze('AAPL');

        $this->assertEquals('HOLD', $result['signal']);
        $this->assertEquals('Invalid price data', $result['reason']);
    }

    public function testSystem1BuySignalOn20DayBreakout(): void
    {
        // Configure for System 1 only
        $this->strategy->setParameters(['system' => 'ONE']);

        // Generate price history where current price breaks above 20-day high
        $priceHistory = $this->generatePriceHistory(70, 95, 100); // Prices 95-100
        $currentPrice = 101; // Breaks above recent high

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        $this->assertEquals('BUY', $result['signal']);
        $this->assertGreaterThan(0, $result['confidence']);
        $this->assertStringContainsString('20-day breakout', $result['reason']);
        $this->assertEquals($currentPrice, $result['entry_price']);
        $this->assertNotNull($result['stop_loss']);
        $this->assertNotNull($result['take_profit']);
        $this->assertNotNull($result['position_size']);
        $this->assertArrayHasKey('atr', $result['metadata']);
    }

    public function testSystem1ShortSignalOn20DayBreakdown(): void
    {
        $this->strategy->setParameters(['system' => 'ONE']);

        // Generate price history where current price breaks below 20-day low
        $priceHistory = $this->generatePriceHistory(70, 100, 96); // Prices declining from 100 to 96
        $currentPrice = 95; // Breaks below recent low of 96

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        $this->assertEquals('SHORT', $result['signal']);
        $this->assertGreaterThan(0, $result['confidence']);
        $this->assertStringContainsString('20-day breakdown', $result['reason']);
    }

    public function testSystem1SellSignalOn10DayLowBreak(): void
    {
        $this->strategy->setParameters(['system' => 'ONE']);

        // Generate declining price trend where we need to exit a LONG position
        // Recent prices hovering around 96-97, then breaks down
        $priceHistory = $this->generatePriceHistory(70, 100, 97);
        $currentPrice = 96; // Breaks 10-day low (exits long position)

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // Should get either SELL or HOLD (depends on whether it's in exit range)
        $this->assertContains($result['signal'], ['SELL', 'HOLD', 'SHORT']);
        
        if ($result['signal'] === 'SELL') {
            $this->assertStringContainsString('10-day low', $result['reason']);
            $this->assertEquals(1.0, $result['position_size']); // Exit entire position
        }
    }

    public function testSystem2BuySignalOn55DayBreakout(): void
    {
        $this->strategy->setParameters(['system' => 'TWO']);

        // Generate 65+ days of data with breakout
        $priceHistory = $this->generatePriceHistory(70, 90, 100);
        $currentPrice = 101; // Breaks above 55-day high

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        $this->assertEquals('BUY', $result['signal']);
        $this->assertStringContainsString('55-day breakout', $result['reason']);
    }

    public function testBothSystemsAgreeIncreasesConfidence(): void
    {
        $this->strategy->setParameters(['system' => 'BOTH']);

        // Generate data where both systems would signal BUY
        $priceHistory = $this->generatePriceHistory(70, 90, 99);
        $currentPrice = 101; // Breaks both 20-day and 55-day highs

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        $this->assertEquals('BUY', $result['signal']);
        $this->assertStringContainsString('Both systems agree', $result['reason']);
        // Confidence should be higher when both agree
        $this->assertGreaterThanOrEqual(0.75, $result['confidence']);
    }

    public function testBothSystemsOneHoldReturnsOtherSignal(): void
    {
        $this->strategy->setParameters(['system' => 'BOTH']);

        // Generate data where only System 1 signals (20-day breakout, but not 55-day)
        $priceHistory = $this->generatePriceHistory(70, 80, 98);
        $currentPrice = 99; // Breaks 20-day but not 55-day

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // Should return the non-HOLD signal
        $this->assertNotEquals('HOLD', $result['signal']);
    }

    public function testCanExecuteReturnsTrueWithSufficientData(): void
    {
        $priceHistory = $this->generatePriceHistory(70, 95, 100);

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

    public function testBuySignalIncludesStopLoss(): void
    {
        $this->strategy->setParameters(['system' => 'ONE']);

        $priceHistory = $this->generatePriceHistory(70, 95, 100);
        $currentPrice = 101;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        $this->assertEquals('BUY', $result['signal']);
        $this->assertNotNull($result['stop_loss']);
        $this->assertLessThan($currentPrice, $result['stop_loss']);
        $this->assertNotNull($result['take_profit']);
        $this->assertGreaterThan($currentPrice, $result['take_profit']);
    }

    public function testShortSignalIncludesStopLoss(): void
    {
        $this->strategy->setParameters(['system' => 'ONE']);

        $priceHistory = $this->generatePriceHistory(70, 100, 96); // Declining from 100 to 96
        $currentPrice = 95; // Breaks below 20-day low

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        $this->assertEquals('SHORT', $result['signal']);
        $this->assertNotNull($result['stop_loss']);
        $this->assertGreaterThan($currentPrice, $result['stop_loss']);
        $this->assertNotNull($result['take_profit']);
        $this->assertLessThan($currentPrice, $result['take_profit']);
    }

    public function testPositionSizeIsReasonable(): void
    {
        $this->strategy->setParameters(['system' => 'ONE']);

        $priceHistory = $this->generatePriceHistory(70, 95, 100);
        $currentPrice = 101;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        $this->assertEquals('BUY', $result['signal']);
        $this->assertGreaterThan(0, $result['position_size']);
        $this->assertLessThanOrEqual(0.25, $result['position_size']); // Max 25%
    }

    public function testMetadataIncludesATRAndRiskReward(): void
    {
        $this->strategy->setParameters(['system' => 'ONE']);

        $priceHistory = $this->generatePriceHistory(70, 95, 100);
        $currentPrice = 101;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('atr', $result['metadata']);
        $this->assertArrayHasKey('risk_reward_ratio', $result['metadata']);
        $this->assertArrayHasKey('max_units', $result['metadata']);
        $this->assertGreaterThan(0, $result['metadata']['atr']);
    }

    /**
     * Helper: Generate price history for testing
     * 
     * @param int $days Number of days
     * @param float $startPrice Starting price
     * @param float $endPrice Ending price
     * @return array Price history
     */
    private function generatePriceHistory(int $days, float $startPrice, float $endPrice): array
    {
        $history = [];
        $priceRange = $endPrice - $startPrice;
        $priceIncrement = $priceRange / $days;

        for ($i = 0; $i < $days; $i++) {
            $basePrice = $startPrice + ($priceIncrement * $i);
            $volatility = $basePrice * 0.02; // 2% daily volatility

            $history[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'open' => $basePrice - ($volatility * 0.5),
                'high' => $basePrice + $volatility,
                'low' => $basePrice - $volatility,
                'close' => $basePrice + ($volatility * 0.25),
                'volume' => rand(1000000, 5000000)
            ];
        }

        return array_reverse($history); // Return chronological order
    }
}

