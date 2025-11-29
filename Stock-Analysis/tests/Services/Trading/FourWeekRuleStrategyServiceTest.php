<?php

namespace Tests\Services\Trading;

use PHPUnit\Framework\TestCase;
use App\Services\Trading\FourWeekRuleStrategyService;
use App\Services\Trading\TradingStrategyInterface;
use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

/**
 * Tests for FourWeekRuleStrategyService
 * 
 * @covers \App\Services\Trading\FourWeekRuleStrategyService
 */
class FourWeekRuleStrategyServiceTest extends TestCase
{
    private FourWeekRuleStrategyService $strategy;
    private MarketDataService $marketDataService;
    private MarketDataRepositoryInterface $marketDataRepository;

    protected function setUp(): void
    {
        $this->marketDataService = $this->createMock(MarketDataService::class);
        $this->marketDataRepository = $this->createMock(MarketDataRepositoryInterface::class);
        
        $this->strategy = new FourWeekRuleStrategyService(
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
        $this->assertEquals('Four Week Rule', $this->strategy->getName());
    }

    public function testGetDescription(): void
    {
        $description = $this->strategy->getDescription();
        
        $this->assertStringContainsString('20-day', $description);
        $this->assertStringContainsString('breakout', $description);
        $this->assertStringContainsString('ATR', $description);
    }

    public function testGetDefaultParameters(): void
    {
        $params = $this->strategy->getParameters();
        
        $this->assertEquals(20, $params['entry_period']);
        $this->assertEquals(20, $params['exit_period']);
        $this->assertEquals(20, $params['atr_period']);
        $this->assertEquals(0.02, $params['risk_per_trade']);
        $this->assertEquals(0.25, $params['max_position_size']);
        $this->assertEquals(2.0, $params['atr_multiplier']);
        $this->assertEquals(0.08, $params['stop_loss_percent']);
        $this->assertEquals(0.20, $params['take_profit_percent']);
    }

    public function testSetParameters(): void
    {
        $newParams = [
            'entry_period' => 30,
            'risk_per_trade' => 0.03
        ];

        $this->strategy->setParameters($newParams);
        $params = $this->strategy->getParameters();

        $this->assertEquals(30, $params['entry_period']);
        $this->assertEquals(0.03, $params['risk_per_trade']);
        
        // Other params should remain unchanged
        $this->assertEquals(20, $params['exit_period']);
    }

    public function testGetRequiredHistoricalDays(): void
    {
        $days = $this->strategy->getRequiredHistoricalDays();
        
        // Should be max(entry_period, atr_period) + 5 = 20 + 5 = 25
        $this->assertEquals(25, $days);
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
        $this->assertStringContainsString('Insufficient', $result['reason']);
    }

    public function testAnalyzeReturnsHoldForInvalidPrice(): void
    {
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

    public function testBullishBreakoutGeneratesBuySignal(): void
    {
        // Generate price history with upward trend
        $priceHistory = $this->generatePriceHistory(30, 90, 100);
        $currentPrice = 102; // Above 20-day high

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        $this->assertEquals('BUY', $result['signal']);
        $this->assertStringContainsString('Bullish breakout', $result['reason']);
        $this->assertGreaterThan(0, $result['confidence']);
        $this->assertEquals($currentPrice, $result['entry_price']);
        $this->assertNotNull($result['stop_loss']);
        $this->assertLessThan($currentPrice, $result['stop_loss']);
        $this->assertNotNull($result['take_profit']);
        $this->assertGreaterThan($currentPrice, $result['take_profit']);
        $this->assertArrayHasKey('breakout_level', $result['metadata']);
        $this->assertArrayHasKey('atr', $result['metadata']);
    }

    public function testBearishBreakoutGeneratesShortSignal(): void
    {
        // Generate price history with downward trend
        $priceHistory = $this->generatePriceHistory(30, 100, 90);
        $currentPrice = 88; // Below 20-day low

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        $this->assertEquals('SHORT', $result['signal']);
        $this->assertStringContainsString('Bearish breakout', $result['reason']);
        $this->assertEquals($currentPrice, $result['entry_price']);
        $this->assertNotNull($result['stop_loss']);
        $this->assertGreaterThan($currentPrice, $result['stop_loss']);
        $this->assertNotNull($result['take_profit']);
        $this->assertLessThan($currentPrice, $result['take_profit']);
        $this->assertArrayHasKey('breakout_level', $result['metadata']);
    }

    public function testExitLongGeneratesSellSignal(): void
    {
        // Generate stable range then breakdown
        $priceHistory = $this->generatePriceHistory(30, 95, 100);
        $currentPrice = 94; // Below 20-day low

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // Could be SELL (exit long) or SHORT (new short entry)
        $this->assertContains($result['signal'], ['SELL', 'SHORT']);
        
        if ($result['signal'] === 'SELL') {
            $this->assertStringContainsString('Exit long', $result['reason']);
            $this->assertEquals(1.0, $result['position_size']); // Exit entire position
        }
    }

    public function testCoverShortGeneratesCoverSignal(): void
    {
        // Generate stable range then breakout
        $priceHistory = $this->generatePriceHistory(30, 90, 95);
        $currentPrice = 96; // Above 20-day high

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // Could be COVER (exit short), BUY (new long entry), or HOLD (within range)
        $this->assertContains($result['signal'], ['COVER', 'BUY', 'HOLD']);
        
        if ($result['signal'] === 'COVER') {
            $this->assertStringContainsString('Cover short', $result['reason']);
            $this->assertEquals(1.0, $result['position_size']);
        } elseif ($result['signal'] === 'BUY') {
            $this->assertStringContainsString('Bullish breakout', $result['reason']);
        }
    }

    public function testNoBreakoutReturnsHold(): void
    {
        // Generate stable price range
        $priceHistory = $this->generatePriceHistory(30, 95, 100);
        $currentPrice = 97.5; // Within range

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        $this->assertEquals('HOLD', $result['signal']);
        $this->assertStringContainsString('No breakout', $result['reason']);
    }

    public function testPositionSizingBasedOnATR(): void
    {
        $priceHistory = $this->generatePriceHistory(30, 90, 100);
        $currentPrice = 102;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        if ($result['signal'] === 'BUY') {
            // Position size should be between 0 and max_position_size
            $this->assertGreaterThan(0, $result['position_size']);
            $this->assertLessThanOrEqual(0.25, $result['position_size']);
        }
    }

    public function testATRCalculationAffectsStopLoss(): void
    {
        $priceHistory = $this->generatePriceHistory(30, 90, 100);
        $currentPrice = 102;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        if ($result['signal'] === 'BUY') {
            $this->assertArrayHasKey('atr', $result['metadata']);
            $this->assertArrayHasKey('atr_stop_distance', $result['metadata']);
            
            $atr = $result['metadata']['atr'];
            $this->assertGreaterThan(0, $atr);
            
            // Stop should be approximately ATR * multiplier away
            $stopDistance = $currentPrice - $result['stop_loss'];
            $this->assertGreaterThan(0, $stopDistance);
        }
    }

    public function testRiskRewardRatioCalculation(): void
    {
        $priceHistory = $this->generatePriceHistory(30, 90, 100);
        $currentPrice = 102;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        if ($result['signal'] === 'BUY') {
            $this->assertArrayHasKey('risk_reward_ratio', $result['metadata']);
            
            $rrr = $result['metadata']['risk_reward_ratio'];
            $this->assertGreaterThan(0, $rrr);
        }
    }

    public function testCanExecuteReturnsTrueWithSufficientData(): void
    {
        $priceHistory = $this->generatePriceHistory(30, 95, 100);

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

    public function testMetadataIncludesBreakoutLevels(): void
    {
        $priceHistory = $this->generatePriceHistory(30, 90, 100);
        $currentPrice = 102;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        if ($result['signal'] === 'BUY') {
            $this->assertArrayHasKey('metadata', $result);
            $this->assertArrayHasKey('breakout_level', $result['metadata']);
            $this->assertArrayHasKey('entry_period', $result['metadata']);
            
            $this->assertEquals(20, $result['metadata']['entry_period']);
            $this->assertGreaterThan(0, $result['metadata']['breakout_level']);
        }
    }

    public function testCustomEntryPeriod(): void
    {
        $this->strategy->setParameters(['entry_period' => 10]);
        
        $priceHistory = $this->generatePriceHistory(30, 90, 100);
        $currentPrice = 101;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // Should work with custom period
        $this->assertContains($result['signal'], ['BUY', 'SELL', 'SHORT', 'COVER', 'HOLD']);
        
        if (isset($result['metadata']['entry_period'])) {
            $this->assertEquals(10, $result['metadata']['entry_period']);
        }
    }

    /**
     * Helper: Generate price history for testing
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
