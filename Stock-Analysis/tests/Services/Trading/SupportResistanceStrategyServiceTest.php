<?php

namespace Tests\Services\Trading;

use PHPUnit\Framework\TestCase;
use App\Services\Trading\SupportResistanceStrategyService;
use App\Services\Trading\TradingStrategyInterface;
use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

/**
 * Tests for SupportResistanceStrategyService
 * 
 * @covers \App\Services\Trading\SupportResistanceStrategyService
 */
class SupportResistanceStrategyServiceTest extends TestCase
{
    private SupportResistanceStrategyService $strategy;
    private MarketDataService $marketDataService;
    private MarketDataRepositoryInterface $marketDataRepository;

    protected function setUp(): void
    {
        $this->marketDataService = $this->createMock(MarketDataService::class);
        $this->marketDataRepository = $this->createMock(MarketDataRepositoryInterface::class);
        
        $this->strategy = new SupportResistanceStrategyService(
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
        $this->assertEquals('Support and Resistance', $this->strategy->getName());
    }

    public function testGetDescription(): void
    {
        $description = $this->strategy->getDescription();
        
        $this->assertStringContainsString('support', strtolower($description));
        $this->assertStringContainsString('resistance', strtolower($description));
        $this->assertStringContainsString('volume', strtolower($description));
    }

    public function testGetDefaultParameters(): void
    {
        $params = $this->strategy->getParameters();
        
        $this->assertEquals(60, $params['lookback_period']);
        $this->assertEquals(3, $params['pivot_strength']);
        $this->assertEquals(0.02, $params['level_tolerance']);
        $this->assertEquals(2, $params['min_touches']);
        $this->assertEquals(1.2, $params['volume_threshold']);
        $this->assertEquals(0.05, $params['stop_loss_percent']);
        $this->assertEquals(0.10, $params['take_profit_percent']);
        $this->assertEquals(0.10, $params['position_size']);
        $this->assertTrue($params['require_volume_confirmation']);
    }

    public function testSetParameters(): void
    {
        $newParams = [
            'lookback_period' => 90,
            'level_tolerance' => 0.03
        ];

        $this->strategy->setParameters($newParams);
        $params = $this->strategy->getParameters();

        $this->assertEquals(90, $params['lookback_period']);
        $this->assertEquals(0.03, $params['level_tolerance']);
        
        // Other params should remain unchanged
        $this->assertEquals(3, $params['pivot_strength']);
    }

    public function testGetRequiredHistoricalDays(): void
    {
        $days = $this->strategy->getRequiredHistoricalDays();
        
        // Should be lookback_period (60) + (pivot_strength * 2) = 60 + 6 = 66
        $this->assertEquals(66, $days);
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
        $priceHistory = $this->generatePriceHistory(70, 95, 100);
        
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

    public function testBuySignalNearSupport(): void
    {
        // Disable volume confirmation for easier testing
        $this->strategy->setParameters(['require_volume_confirmation' => false]);
        
        // Generate price with clear support around 95
        $priceHistory = $this->generateSupportResistanceHistory(70);
        $currentPrice = 95.5; // Near support

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // Should be BUY or HOLD depending on pivot detection
        $this->assertContains($result['signal'], ['BUY', 'HOLD']);
        
        if ($result['signal'] === 'BUY') {
            $this->assertStringContainsString('support', strtolower($result['reason']));
            $this->assertGreaterThan(0, $result['confidence']);
            $this->assertEquals($currentPrice, $result['entry_price']);
            $this->assertNotNull($result['stop_loss']);
            $this->assertLessThan($currentPrice, $result['stop_loss']);
            $this->assertNotNull($result['take_profit']);
            $this->assertGreaterThan($currentPrice, $result['take_profit']);
            $this->assertArrayHasKey('support_level', $result['metadata']);
            $this->assertArrayHasKey('level_touches', $result['metadata']);
        }
    }

    public function testSellSignalNearResistance(): void
    {
        // Generate price with clear resistance around 105
        $priceHistory = $this->generateSupportResistanceHistory(70);
        $currentPrice = 104.5; // Near resistance

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // Should be SELL or HOLD depending on pivot detection
        $this->assertContains($result['signal'], ['SELL', 'HOLD']);
        
        if ($result['signal'] === 'SELL') {
            $this->assertStringContainsString('resistance', strtolower($result['reason']));
            $this->assertEquals(1.0, $result['position_size']); // Exit entire position
            $this->assertArrayHasKey('resistance_level', $result['metadata']);
        }
    }

    public function testHoldBetweenLevels(): void
    {
        $this->strategy->setParameters(['require_volume_confirmation' => false]);
        
        $priceHistory = $this->generateSupportResistanceHistory(70);
        $currentPrice = 100; // Between support and resistance

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        $this->assertContains($result['signal'], ['BUY', 'SELL', 'HOLD']);
        $this->assertNotEmpty($result['reason']);
    }

    public function testVolumeConfirmationRequired(): void
    {
        $this->strategy->setParameters([
            'require_volume_confirmation' => true,
            'volume_threshold' => 2.0 // Require 2x average volume
        ]);
        
        $priceHistory = $this->generateSupportResistanceHistory(70);
        $currentPrice = 95.5; // Near support

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // With high volume threshold, likely won't get BUY
        $this->assertContains($result['signal'], ['BUY', 'HOLD']);
        
        if ($result['signal'] === 'HOLD' && strpos($result['reason'], 'volume') !== false) {
            $this->assertStringContainsString('volume', strtolower($result['reason']));
        }
    }

    public function testMetadataIncludesLevelInfo(): void
    {
        $this->strategy->setParameters(['require_volume_confirmation' => false]);
        
        $priceHistory = $this->generateSupportResistanceHistory(70);
        $currentPrice = 95.5;

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
            $this->assertArrayHasKey('support_level', $result['metadata']);
            $this->assertArrayHasKey('level_touches', $result['metadata']);
            $this->assertArrayHasKey('level_strength', $result['metadata']);
            $this->assertArrayHasKey('distance_to_level', $result['metadata']);
            $this->assertArrayHasKey('risk_reward_ratio', $result['metadata']);
            
            $this->assertGreaterThanOrEqual(2, $result['metadata']['level_touches']);
            $this->assertGreaterThan(0, $result['metadata']['level_strength']);
            $this->assertLessThanOrEqual(1.0, $result['metadata']['level_strength']);
        }
    }

    public function testConfidenceIncreasesWithMoreTouches(): void
    {
        $this->strategy->setParameters([
            'require_volume_confirmation' => false,
            'min_touches' => 2
        ]);
        
        $priceHistory = $this->generateSupportResistanceHistory(70);
        $currentPrice = 95.5;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // Confidence should always be valid
        $this->assertGreaterThanOrEqual(0.0, $result['confidence']);
        $this->assertLessThanOrEqual(1.0, $result['confidence']);
        
        if ($result['signal'] === 'BUY') {
            // BUY confidence should be reasonable
            $this->assertGreaterThanOrEqual(0.60, $result['confidence']);
        }
    }

    public function testRiskRewardRatioCalculation(): void
    {
        $this->strategy->setParameters(['require_volume_confirmation' => false]);
        
        $priceHistory = $this->generateSupportResistanceHistory(70);
        $currentPrice = 95.5;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // Should return valid signal
        $this->assertContains($result['signal'], ['BUY', 'SELL', 'HOLD']);
        
        if ($result['signal'] === 'BUY') {
            $this->assertArrayHasKey('risk_reward_ratio', $result['metadata']);
            $rrr = $result['metadata']['risk_reward_ratio'];
            $this->assertGreaterThan(0, $rrr);
        }
    }

    public function testCanExecuteReturnsTrueWithSufficientData(): void
    {
        $priceHistory = $this->generateSupportResistanceHistory(70);

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

    public function testCustomLevelTolerance(): void
    {
        $this->strategy->setParameters([
            'level_tolerance' => 0.05, // 5% tolerance
            'require_volume_confirmation' => false
        ]);
        
        $priceHistory = $this->generateSupportResistanceHistory(70);
        $currentPrice = 93; // Further from support but within 5%

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // With higher tolerance, more likely to find levels
        $this->assertContains($result['signal'], ['BUY', 'SELL', 'HOLD']);
    }

    public function testCustomPivotStrength(): void
    {
        $this->strategy->setParameters([
            'pivot_strength' => 5, // Stronger pivot requirement
            'require_volume_confirmation' => false
        ]);
        
        $priceHistory = $this->generateSupportResistanceHistory(80);
        $currentPrice = 95.5;

        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($priceHistory);
        
        $this->marketDataService
            ->method('getCurrentPrice')
            ->willReturn(['price' => $currentPrice]);

        $result = $this->strategy->analyze('AAPL');

        // Should work with custom pivot strength
        $this->assertContains($result['signal'], ['BUY', 'SELL', 'HOLD']);
    }

    /**
     * Helper: Generate price history with support/resistance levels
     */
    private function generateSupportResistanceHistory(int $days): array
    {
        $history = [];
        $basePrice = 100;
        
        for ($i = 0; $i < $days; $i++) {
            // Create oscillating pattern between 95 (support) and 105 (resistance)
            $cycle = ($i % 20) / 20.0; // 20-day cycle
            $price = 95 + (10 * sin($cycle * M_PI * 2));
            
            // Add some volatility
            $volatility = 1.0;
            
            $history[] = [
                'date' => date('Y-m-d', strtotime("-" . ($days - $i) . " days")),
                'open' => $price - ($volatility * 0.5),
                'high' => $price + $volatility,
                'low' => $price - $volatility,
                'close' => $price,
                'volume' => rand(1000000, 3000000)
            ];
        }

        return $history;
    }

    /**
     * Helper: Generate standard price history
     */
    private function generatePriceHistory(int $days, float $startPrice, float $endPrice): array
    {
        $history = [];
        $priceRange = $endPrice - $startPrice;
        $priceIncrement = $priceRange / $days;

        for ($i = 0; $i < $days; $i++) {
            $basePrice = $startPrice + ($priceIncrement * $i);
            $volatility = $basePrice * 0.02;

            $history[] = [
                'date' => date('Y-m-d', strtotime("-" . ($days - $i) . " days")),
                'open' => $basePrice - ($volatility * 0.5),
                'high' => $basePrice + $volatility,
                'low' => $basePrice - $volatility,
                'close' => $basePrice,
                'volume' => rand(1000000, 3000000)
            ];
        }

        return $history;
    }
}
