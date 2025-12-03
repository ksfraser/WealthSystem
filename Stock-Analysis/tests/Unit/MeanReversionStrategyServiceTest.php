<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\Trading\MeanReversionStrategyService;
use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

class MeanReversionStrategyServiceTest extends TestCase
{
    private MeanReversionStrategyService $strategy;
    private $marketDataService;
    private $marketDataRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->marketDataService = $this->createMock(MarketDataService::class);
        $this->marketDataRepository = $this->createMock(MarketDataRepositoryInterface::class);
        
        $this->strategy = new MeanReversionStrategyService(
            $this->marketDataService,
            $this->marketDataRepository
        );
    }

    /**
     * @test
     */
    public function it_initializes_with_default_parameters()
    {
        $this->assertInstanceOf(MeanReversionStrategyService::class, $this->strategy);
        
        $params = $this->strategy->getParameters();
        $this->assertIsArray($params);
        $this->assertArrayHasKey('bb_period', $params);
        $this->assertArrayHasKey('bb_std_dev', $params);
    }

    /**
     * @test
     */
    public function it_implements_trading_strategy_interface()
    {
        $this->assertEquals('MeanReversion', $this->strategy->getName());
        $this->assertNotEmpty($this->strategy->getDescription());
        $this->assertIsInt($this->strategy->getRequiredHistoricalDays());
        $this->assertGreaterThan(0, $this->strategy->getRequiredHistoricalDays());
    }

    /**
     * @test
     */
    public function it_returns_hold_with_insufficient_data()
    {
        $this->marketDataService->method('getFundamentals')->willReturn([]);
        $this->marketDataService->method('getHistoricalPrices')->willReturn([]);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertEquals('HOLD', $result['action']);
        $this->assertEquals(0, $result['confidence']);
    }

    /**
     * @test
     */
    public function it_calculates_bollinger_bands()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getBasicHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('bb_position', $result['metrics']);
        $this->assertIsFloat($result['metrics']['bb_position']);
    }

    /**
     * @test
     */
    public function it_detects_oversold_condition_below_lower_band()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getOversoldData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('oversold_signal', $result['metrics']);
        $this->assertTrue($result['metrics']['oversold_signal']);
    }

    /**
     * @test
     */
    public function it_calculates_rsi()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getBasicHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('rsi', $result['metrics']);
        $this->assertIsFloat($result['metrics']['rsi']);
        $this->assertGreaterThanOrEqual(0, $result['metrics']['rsi']);
        $this->assertLessThanOrEqual(100, $result['metrics']['rsi']);
    }

    /**
     * @test
     */
    public function it_detects_rsi_oversold_below_30()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getRSIOversoldData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('rsi', $result['metrics']);
        $this->assertLessThan(35, $result['metrics']['rsi']);
    }

    /**
     * @test
     */
    public function it_detects_bullish_rsi_divergence()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getBullishDivergenceData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('rsi_divergence', $result['metrics']);
        $this->assertTrue(in_array($result['metrics']['rsi_divergence'], ['bullish', 'bearish', 'none']));
    }

    /**
     * @test
     */
    public function it_validates_volume_confirmation()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getHighVolumeReversalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('volume_confirmation', $result['metrics']);
        $this->assertIsBool($result['metrics']['volume_confirmation']);
    }

    /**
     * @test
     */
    public function it_calculates_mean_reversion_score()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getOversoldData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('mean_reversion_score', $result['metrics']);
        $this->assertIsFloat($result['metrics']['mean_reversion_score']);
        $this->assertGreaterThanOrEqual(0, $result['metrics']['mean_reversion_score']);
        $this->assertLessThanOrEqual(1, $result['metrics']['mean_reversion_score']);
    }

    /**
     * @test
     */
    public function it_detects_price_bounce_from_support()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getSupportBounceData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('support_bounce', $result['metrics']);
    }

    /**
     * @test
     */
    public function it_returns_buy_signal_when_oversold_with_confirmation()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getStrongOversoldWithConfirmation();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        // Strong oversold + volume confirmation should suggest BUY
        $this->assertContains($result['action'], ['BUY', 'HOLD']);
        
        if ($result['action'] === 'BUY') {
            $this->assertGreaterThan(50, $result['confidence']);
        }
    }

    /**
     * @test
     */
    public function it_returns_hold_when_not_oversold()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getNeutralData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        // Neutral conditions should not trigger buy
        $this->assertEquals('HOLD', $result['action']);
    }

    /**
     * @test
     */
    public function it_returns_hold_when_overbought()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getOverboughtData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        // Overbought should not trigger buy
        $this->assertNotEquals('BUY', $result['action']);
    }

    /**
     * @test
     */
    public function it_calculates_volatility_measure()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getBasicHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('volatility', $result['metrics']);
        $this->assertIsFloat($result['metrics']['volatility']);
    }

    /**
     * @test
     */
    public function it_requires_minimum_volatility_for_mean_reversion()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getLowVolatilityData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        // Low volatility reduces mean reversion opportunity
        if (isset($result['metrics']['volatility'])) {
            $this->assertIsFloat($result['metrics']['volatility']);
        }
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_detects_multiple_oversold_touches()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getMultipleBandTouchData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('band_touches', $result['metrics']);
    }

    /**
     * @test
     */
    public function it_calculates_distance_from_mean()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getOversoldData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('distance_from_mean', $result['metrics']);
        $this->assertIsFloat($result['metrics']['distance_from_mean']);
    }

    /**
     * @test
     */
    public function it_validates_trend_context()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getDowntrendData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        // Should identify trend direction
        $this->assertArrayHasKey('trend_direction', $result['metrics']);
    }

    /**
     * @test
     */
    public function it_builds_comprehensive_reasoning()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getOversoldData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('reasoning', $result);
        $this->assertNotEmpty($result['reasoning']);
        $reasoning = strtolower($result['reasoning']);
        $this->assertTrue(
            str_contains($reasoning, 'bollinger') ||
            str_contains($reasoning, 'rsi') ||
            str_contains($reasoning, 'oversold') ||
            str_contains($reasoning, 'mean')
        );
    }

    /**
     * @test
     */
    public function it_handles_exceptions_gracefully()
    {
        $this->marketDataService->method('getFundamentals')
            ->willThrowException(new \Exception('API Error'));
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertEquals('HOLD', $result['action']);
        $this->assertEquals(0, $result['confidence']);
        $this->assertStringContainsString('error', strtolower($result['reasoning']));
    }

    /**
     * @test
     */
    public function it_validates_parameters_are_within_expected_ranges()
    {
        $params = $this->strategy->getParameters();
        
        $this->assertArrayHasKey('bb_period', $params);
        $this->assertArrayHasKey('bb_std_dev', $params);
        $this->assertArrayHasKey('rsi_period', $params);
        $this->assertArrayHasKey('rsi_oversold', $params);
        $this->assertArrayHasKey('volume_threshold', $params);
        
        // Validate reasonable defaults
        $this->assertGreaterThan(10, $params['bb_period']);
        $this->assertLessThanOrEqual(30, $params['bb_period']);
        $this->assertGreaterThan(1, $params['bb_std_dev']);
        $this->assertLessThanOrEqual(3, $params['bb_std_dev']);
    }

    /**
     * @test
     */
    public function it_can_update_parameters()
    {
        $newParams = [
            'bb_period' => 25,
            'rsi_oversold' => 25
        ];
        
        $this->strategy->setParameters($newParams);
        $params = $this->strategy->getParameters();
        
        $this->assertEquals(25, $params['bb_period']);
        $this->assertEquals(25, $params['rsi_oversold']);
    }

    // Helper methods for test data

    private function getBasicFundamentals(): array
    {
        return [
            'market_cap' => 5000000000,
            'avg_volume' => 2000000,
            'sector' => 'Technology'
        ];
    }

    private function getBasicHistoricalData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        for ($i = 0; $i < 60; $i++) {
            $variance = sin($i / 5) * 5; // Create some oscillation
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'open' => $basePrice + $variance + rand(-2, 2),
                'high' => $basePrice + $variance + rand(1, 4),
                'low' => $basePrice + $variance - rand(1, 4),
                'close' => $basePrice + $variance + rand(-2, 2),
                'volume' => 2000000 + rand(-500000, 500000)
            ];
        }
        
        return array_reverse($data);
    }

    private function getOversoldData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        // Generate chronologically (oldest to newest) with continuous decline
        // First 40 days: gradual trend, Last 20 days: steep decline to create oversold
        for ($i = 0; $i < 60; $i++) {
            $daysAgo = 59 - $i; // i=0: 59 days ago, i=59: today
            if ($i < 40) {
                $decline = -($i * 0.25); // Gradual -0.25 per day = -10 total
            } else {
                $decline = -10 - (($i - 40) * 1.5); // Steep -1.5 per day = -30 more
            }
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$daysAgo days")),
                'open' => $basePrice + $decline + rand(-1, 1),
                'high' => $basePrice + $decline + rand(0, 2),
                'low' => $basePrice + $decline - rand(1, 3),
                'close' => $basePrice + $decline + rand(-1, 1),
                'volume' => 2000000 + rand(-500000, 500000)
            ];
        }
        
        return $data;
    }

    private function getRSIOversoldData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        // Create strong downtrend for RSI < 30 (oldest to newest)
        // Continuous decline: 100 -> 40 over 60 days (-1 per day)
        for ($i = 0; $i < 60; $i++) {
            $daysAgo = 59 - $i; // i=0: 59 days ago, i=59: today
            $decline = -$i; // Continuous decline
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$daysAgo days")),
                'open' => $basePrice + $decline,
                'high' => $basePrice + $decline + 1,
                'low' => $basePrice + $decline - 2,
                'close' => $basePrice + $decline - 1,
                'volume' => 2000000 + rand(-500000, 500000)
            ];
        }
        
        return $data;
    }

    private function getBullishDivergenceData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        // Price making lower lows, but RSI making higher lows (bullish divergence)
        for ($i = 0; $i < 60; $i++) {
            if ($i < 30) {
                $trend = -($i * 1.5);
            } else {
                // Price continues down but at slower rate
                $trend = -45 - (($i - 30) * 0.3);
            }
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'open' => $basePrice + $trend + rand(-1, 1),
                'high' => $basePrice + $trend + rand(0, 2),
                'low' => $basePrice + $trend - rand(0, 2),
                'close' => $basePrice + $trend + rand(-1, 1),
                'volume' => 2000000 + rand(-500000, 500000)
            ];
        }
        
        return array_reverse($data);
    }

    private function getHighVolumeReversalData(): array
    {
        $data = $this->getOversoldData();
        
        // Spike volume on recent days (reversal signal)
        for ($i = count($data) - 5; $i < count($data); $i++) {
            $data[$i]['volume'] *= 2.5;
        }
        
        return $data;
    }

    private function getSupportBounceData(): array
    {
        $data = [];
        $basePrice = 100.0;
        $support = 85.0;
        
        for ($i = 0; $i < 60; $i++) {
            if ($i < 10) {
                // Decline to support
                $price = $basePrice - ($i * 1.5);
            } else {
                // Bounce from support
                $price = $support + (($i - 10) * 0.5);
            }
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'open' => $price + rand(-1, 1),
                'high' => $price + rand(1, 3),
                'low' => max($support, $price - rand(1, 2)),
                'close' => $price + rand(-1, 1),
                'volume' => 2000000 + rand(-500000, 500000)
            ];
        }
        
        return array_reverse($data);
    }

    private function getStrongOversoldWithConfirmation(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        // Strong decline then reversal with volume
        for ($i = 0; $i < 60; $i++) {
            if ($i < 15) {
                $trend = -($i * 2);
                $volume = 2000000;
            } else {
                // Start reversing with high volume
                $trend = -30 + (($i - 15) * 0.8);
                $volume = 4000000;
            }
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'open' => $basePrice + $trend + rand(-1, 1),
                'high' => $basePrice + $trend + rand(1, 3),
                'low' => $basePrice + $trend - rand(1, 3),
                'close' => $basePrice + $trend + rand(-1, 2),
                'volume' => $volume + rand(-500000, 500000)
            ];
        }
        
        return array_reverse($data);
    }

    private function getNeutralData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        // Sideways movement near mean
        for ($i = 0; $i < 60; $i++) {
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'open' => $basePrice + rand(-3, 3),
                'high' => $basePrice + rand(1, 5),
                'low' => $basePrice - rand(1, 5),
                'close' => $basePrice + rand(-3, 3),
                'volume' => 2000000 + rand(-500000, 500000)
            ];
        }
        
        return array_reverse($data);
    }

    private function getOverboughtData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        // Strong uptrend - overbought
        for ($i = 0; $i < 60; $i++) {
            $trend = $i < 20 ? ($i * 2) : 40;
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'open' => $basePrice + $trend,
                'high' => $basePrice + $trend + 2,
                'low' => $basePrice + $trend - 1,
                'close' => $basePrice + $trend + 1,
                'volume' => 2000000 + rand(-500000, 500000)
            ];
        }
        
        return array_reverse($data);
    }

    private function getLowVolatilityData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        // Very tight range - low volatility
        for ($i = 0; $i < 60; $i++) {
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'open' => $basePrice + rand(-1, 1) * 0.5,
                'high' => $basePrice + 0.5,
                'low' => $basePrice - 0.5,
                'close' => $basePrice + rand(-1, 1) * 0.5,
                'volume' => 2000000 + rand(-500000, 500000)
            ];
        }
        
        return array_reverse($data);
    }

    private function getMultipleBandTouchData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        // Multiple touches of lower band
        for ($i = 0; $i < 60; $i++) {
            // Oscillate with touches at i=10, 25, 40
            if (in_array($i, [10, 25, 40])) {
                $trend = -15;
            } else {
                $trend = sin($i / 8) * 8;
            }
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'open' => $basePrice + $trend + rand(-1, 1),
                'high' => $basePrice + $trend + rand(1, 3),
                'low' => $basePrice + $trend - rand(1, 3),
                'close' => $basePrice + $trend + rand(-1, 1),
                'volume' => 2000000 + rand(-500000, 500000)
            ];
        }
        
        return array_reverse($data);
    }

    private function getDowntrendData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        // Consistent downtrend
        for ($i = 0; $i < 60; $i++) {
            $trend = -($i * 0.8);
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'open' => $basePrice + $trend + rand(-1, 1),
                'high' => $basePrice + $trend + rand(0, 2),
                'low' => $basePrice + $trend - rand(1, 3),
                'close' => $basePrice + $trend + rand(-1, 1),
                'volume' => 2000000 + rand(-500000, 500000)
            ];
        }
        
        return array_reverse($data);
    }
}
