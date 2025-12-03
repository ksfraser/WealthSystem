<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\Trading\MomentumQualityStrategyService;
use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

class MomentumQualityStrategyServiceTest extends TestCase
{
    private MomentumQualityStrategyService $strategy;
    private $marketDataService;
    private $marketDataRepository;

    protected function setUp(): void
    {
        $this->marketDataService = $this->createMock(MarketDataService::class);
        $this->marketDataRepository = $this->createMock(MarketDataRepositoryInterface::class);
        
        $this->strategy = new MomentumQualityStrategyService(
            $this->marketDataService,
            $this->marketDataRepository
        );
    }

    /**
     * @test
     */
    public function it_initializes_with_default_parameters()
    {
        $params = $this->strategy->getParameters();
        
        $this->assertIsArray($params);
        $this->assertArrayHasKey('sma_short_period', $params);
        $this->assertArrayHasKey('sma_long_period', $params);
        $this->assertArrayHasKey('min_roe', $params);
    }

    /**
     * @test
     */
    public function it_implements_trading_strategy_interface()
    {
        $this->assertEquals('MomentumQuality', $this->strategy->getName());
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
        $this->assertStringContainsString('insufficient', strtolower($result['reasoning']));
    }

    /**
     * @test
     */
    public function it_calculates_moving_averages()
    {
        $fundamentals = $this->getQualityFundamentals();
        $historicalData = $this->getStrongMomentumData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('sma_50', $result['metrics']);
        $this->assertArrayHasKey('sma_200', $result['metrics']);
        $this->assertGreaterThan(0, $result['metrics']['sma_50']);
        $this->assertGreaterThan(0, $result['metrics']['sma_200']);
    }

    /**
     * @test
     */
    public function it_detects_golden_cross()
    {
        $fundamentals = $this->getQualityFundamentals();
        $historicalData = $this->getGoldenCrossData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('golden_cross', $result['metrics']);
        $this->assertTrue($result['metrics']['golden_cross']);
    }

    /**
     * @test
     */
    public function it_calculates_price_momentum()
    {
        $fundamentals = $this->getQualityFundamentals();
        $historicalData = $this->getStrongMomentumData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('price_momentum_3m', $result['metrics']);
        $this->assertArrayHasKey('price_momentum_6m', $result['metrics']);
        $this->assertGreaterThan(0, $result['metrics']['price_momentum_3m']);
    }

    /**
     * @test
     */
    public function it_validates_earnings_acceleration()
    {
        $fundamentals = $this->getAcceleratingEarningsFundamentals();
        $historicalData = $this->getStrongMomentumData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('earnings_acceleration', $result['metrics']);
        $this->assertTrue($result['metrics']['earnings_acceleration']);
    }

    /**
     * @test
     */
    public function it_calculates_roe_trend()
    {
        $fundamentals = $this->getImprovingROEFundamentals();
        $historicalData = $this->getStrongMomentumData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('roe', $result['metrics']);
        $this->assertArrayHasKey('roe_improving', $result['metrics']);
        $this->assertGreaterThan(0.10, $result['metrics']['roe']);
    }

    /**
     * @test
     */
    public function it_validates_revenue_growth_consistency()
    {
        $fundamentals = $this->getConsistentGrowthFundamentals();
        $historicalData = $this->getStrongMomentumData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('revenue_growth', $result['metrics']);
        $this->assertArrayHasKey('revenue_growth_consistent', $result['metrics']);
        $this->assertGreaterThan(0, $result['metrics']['revenue_growth']);
    }

    /**
     * @test
     */
    public function it_calculates_quality_score()
    {
        $fundamentals = $this->getQualityFundamentals();
        $historicalData = $this->getStrongMomentumData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('quality_score', $result['metrics']);
        $this->assertGreaterThanOrEqual(0, $result['metrics']['quality_score']);
        $this->assertLessThanOrEqual(1, $result['metrics']['quality_score']);
    }

    /**
     * @test
     */
    public function it_calculates_momentum_score()
    {
        $fundamentals = $this->getQualityFundamentals();
        $historicalData = $this->getStrongMomentumData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('momentum_score', $result['metrics']);
        $this->assertGreaterThanOrEqual(0, $result['metrics']['momentum_score']);
        $this->assertLessThanOrEqual(1, $result['metrics']['momentum_score']);
    }

    /**
     * @test
     */
    public function it_returns_buy_signal_with_strong_momentum_and_quality()
    {
        $fundamentals = $this->getExcellentMomentumQualityFundamentals();
        $historicalData = $this->getStrongMomentumData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertEquals('BUY', $result['action']);
        $this->assertGreaterThan(60, $result['confidence']);
    }

    /**
     * @test
     */
    public function it_returns_hold_with_death_cross()
    {
        $fundamentals = $this->getQualityFundamentals();
        $historicalData = $this->getDeathCrossData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertEquals('HOLD', $result['action']);
        $this->assertStringContainsString('death cross', strtolower($result['reasoning']));
    }

    /**
     * @test
     */
    public function it_returns_hold_with_poor_quality_metrics()
    {
        $fundamentals = $this->getPoorQualityFundamentals();
        $historicalData = $this->getStrongMomentumData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertEquals('HOLD', $result['action']);
        $this->assertStringContainsString('quality', strtolower($result['reasoning']));
    }

    /**
     * @test
     */
    public function it_validates_relative_strength()
    {
        $fundamentals = $this->getQualityFundamentals();
        $historicalData = $this->getStrongMomentumData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('relative_strength', $result['metrics']);
        $this->assertTrue(is_float($result['metrics']['relative_strength']) || is_int($result['metrics']['relative_strength']));
    }

    /**
     * @test
     */
    public function it_checks_profit_margin_quality()
    {
        $fundamentals = $this->getHighMarginFundamentals();
        $historicalData = $this->getStrongMomentumData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('profit_margin', $result['metrics']);
        $this->assertGreaterThan(0.10, $result['metrics']['profit_margin']);
    }

    /**
     * @test
     */
    public function it_validates_debt_levels()
    {
        $fundamentals = $this->getQualityFundamentals();
        $historicalData = $this->getStrongMomentumData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('debt_to_equity', $result['metrics']);
        $this->assertLessThan(2.0, $result['metrics']['debt_to_equity']);
    }

    /**
     * @test
     */
    public function it_detects_volume_confirmation()
    {
        $fundamentals = $this->getQualityFundamentals();
        $historicalData = $this->getHighVolumeBreakoutData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('volume_confirmation', $result['metrics']);
        $this->assertTrue($result['metrics']['volume_confirmation']);
    }

    /**
     * @test
     */
    public function it_validates_earnings_quality()
    {
        $fundamentals = $this->getQualityFundamentals();
        $historicalData = $this->getStrongMomentumData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('earnings_quality', $result['metrics']);
        $this->assertTrue($result['metrics']['earnings_quality']);
    }

    /**
     * @test
     */
    public function it_builds_comprehensive_reasoning()
    {
        $fundamentals = $this->getQualityFundamentals();
        $historicalData = $this->getStrongMomentumData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertNotEmpty($result['reasoning']);
        $keywords = ['momentum', 'quality', 'roe', 'earnings'];
        $reasoning = strtolower($result['reasoning']);
        $found = false;
        foreach ($keywords as $keyword) {
            if (strpos($reasoning, $keyword) !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Reasoning should contain momentum/quality keywords');
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
        
        $this->assertGreaterThan(0, $params['sma_short_period']);
        $this->assertGreaterThan($params['sma_short_period'], $params['sma_long_period']);
        $this->assertGreaterThan(0, $params['min_roe']);
        $this->assertLessThan(1, $params['min_roe']);
    }

    /**
     * @test
     */
    public function it_can_update_parameters()
    {
        $newParams = [
            'sma_short_period' => 60,
            'min_roe' => 0.15
        ];
        
        $this->strategy->setParameters($newParams);
        $params = $this->strategy->getParameters();
        
        $this->assertEquals(60, $params['sma_short_period']);
        $this->assertEquals(0.15, $params['min_roe']);
    }

    // Helper methods for test data

    private function getQualityFundamentals(): array
    {
        return [
            'symbol' => 'TEST',
            'market_cap' => 5000000000,
            'price' => 100.0,
            'pe_ratio' => 18.0,
            'roe' => 0.16,
            'debt_to_equity' => 0.70,
            'profit_margin' => 0.12,
            'revenue' => 10000000000,
            'prior_year_revenue' => 9000000000,
            'earnings_per_share' => 5.55,
            'free_cash_flow' => 2000000000,
            'operating_cash_flow' => 2500000000,
            'earnings_history' => [
                ['year' => 2024, 'quarter' => 4, 'eps' => 1.50],
                ['year' => 2024, 'quarter' => 3, 'eps' => 1.45],
                ['year' => 2024, 'quarter' => 2, 'eps' => 1.40],
                ['year' => 2024, 'quarter' => 1, 'eps' => 1.35],
                ['year' => 2023, 'quarter' => 4, 'eps' => 1.30]
            ],
            'revenue_history' => [
                ['year' => 2024, 'revenue' => 10000000000],
                ['year' => 2023, 'revenue' => 9000000000],
                ['year' => 2022, 'revenue' => 8200000000],
                ['year' => 2021, 'revenue' => 7500000000]
            ],
            'roe_history' => [
                ['year' => 2024, 'roe' => 0.16],
                ['year' => 2023, 'roe' => 0.15],
                ['year' => 2022, 'roe' => 0.14]
            ]
        ];
    }

    private function getStrongMomentumData(): array
    {
        $data = [];
        $basePrice = 80.0;
        
        // 250 days for 200-day MA, with uptrend
        for ($i = 0; $i < 250; $i++) {
            $daysAgo = 249 - $i;
            $trend = $i * 0.15; // Steady uptrend
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$daysAgo days")),
                'open' => $basePrice + $trend,
                'high' => $basePrice + $trend + 2,
                'low' => $basePrice + $trend - 1,
                'close' => $basePrice + $trend + 0.5, // Deterministic instead of rand(0,1)
                'volume' => 2000000 // Deterministic volume
            ];
        }
        
        return $data;
    }

    private function getGoldenCrossData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        // Generate data where 50-day MA crosses above 200-day MA
        for ($i = 0; $i < 250; $i++) {
            $daysAgo = 249 - $i;
            
            if ($i < 200) {
                $trend = $i * 0.05; // Slow rise
            } else {
                $trend = 10 + ($i - 200) * 0.30; // Accelerate (golden cross)
            }
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$daysAgo days")),
                'open' => $basePrice + $trend,
                'high' => $basePrice + $trend + 2,
                'low' => $basePrice + $trend - 1,
                'close' => $basePrice + $trend + rand(0, 1),
                'volume' => 2000000 + rand(-300000, 300000)
            ];
        }
        
        return $data;
    }

    private function getDeathCrossData(): array
    {
        $data = [];
        $basePrice = 120.0;
        
        // Generate data where 50-day MA crosses below 200-day MA
        for ($i = 0; $i < 250; $i++) {
            $daysAgo = 249 - $i;
            
            if ($i < 200) {
                $trend = -($i * 0.05); // Slow decline
            } else {
                $trend = -10 - (($i - 200) * 0.30); // Accelerate down (death cross)
            }
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$daysAgo days")),
                'open' => $basePrice + $trend,
                'high' => $basePrice + $trend + 1,
                'low' => $basePrice + $trend - 2,
                'close' => $basePrice + $trend + rand(-1, 0),
                'volume' => 2000000 + rand(-300000, 300000)
            ];
        }
        
        return $data;
    }

    private function getAcceleratingEarningsFundamentals(): array
    {
        $data = $this->getQualityFundamentals();
        $data['earnings_history'] = [
            ['year' => 2024, 'quarter' => 4, 'eps' => 1.80],
            ['year' => 2024, 'quarter' => 3, 'eps' => 1.70],
            ['year' => 2024, 'quarter' => 2, 'eps' => 1.55],
            ['year' => 2024, 'quarter' => 1, 'eps' => 1.40],
            ['year' => 2023, 'quarter' => 4, 'eps' => 1.30],
            ['year' => 2023, 'quarter' => 3, 'eps' => 1.25]
        ];
        return $data;
    }

    private function getImprovingROEFundamentals(): array
    {
        $data = $this->getQualityFundamentals();
        $data['roe'] = 0.18;
        $data['roe_history'] = [
            ['year' => 2024, 'roe' => 0.18],
            ['year' => 2023, 'roe' => 0.16],
            ['year' => 2022, 'roe' => 0.14],
            ['year' => 2021, 'roe' => 0.13]
        ];
        return $data;
    }

    private function getConsistentGrowthFundamentals(): array
    {
        $data = $this->getQualityFundamentals();
        $data['revenue_history'] = [
            ['year' => 2024, 'revenue' => 12000000000],
            ['year' => 2023, 'revenue' => 11000000000],
            ['year' => 2022, 'revenue' => 10000000000],
            ['year' => 2021, 'revenue' => 9200000000],
            ['year' => 2020, 'revenue' => 8500000000]
        ];
        return $data;
    }

    private function getExcellentMomentumQualityFundamentals(): array
    {
        return [
            'symbol' => 'TEST',
            'market_cap' => 8000000000,
            'price' => 120.0,
            'pe_ratio' => 20.0,
            'roe' => 0.20,
            'debt_to_equity' => 0.50,
            'profit_margin' => 0.15,
            'revenue' => 12000000000,
            'prior_year_revenue' => 10000000000,
            'earnings_per_share' => 6.00,
            'free_cash_flow' => 2500000000,
            'operating_cash_flow' => 3000000000,
            'earnings_history' => [
                ['year' => 2024, 'quarter' => 4, 'eps' => 1.70],
                ['year' => 2024, 'quarter' => 3, 'eps' => 1.60],
                ['year' => 2024, 'quarter' => 2, 'eps' => 1.45],
                ['year' => 2024, 'quarter' => 1, 'eps' => 1.35],
                ['year' => 2023, 'quarter' => 4, 'eps' => 1.25],
                ['year' => 2023, 'quarter' => 3, 'eps' => 1.20]
            ],
            'revenue_history' => [
                ['year' => 2024, 'revenue' => 12000000000],
                ['year' => 2023, 'revenue' => 10000000000],
                ['year' => 2022, 'revenue' => 8500000000],
                ['year' => 2021, 'revenue' => 7500000000]
            ],
            'roe_history' => [
                ['year' => 2024, 'roe' => 0.20],
                ['year' => 2023, 'roe' => 0.18],
                ['year' => 2022, 'roe' => 0.16]
            ]
        ];
    }

    private function getPoorQualityFundamentals(): array
    {
        $data = $this->getQualityFundamentals();
        $data['roe'] = 0.05;
        $data['debt_to_equity'] = 2.5;
        $data['profit_margin'] = 0.02;
        $data['free_cash_flow'] = -500000000;
        return $data;
    }

    private function getHighMarginFundamentals(): array
    {
        $data = $this->getQualityFundamentals();
        $data['profit_margin'] = 0.22;
        return $data;
    }

    private function getHighVolumeBreakoutData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        for ($i = 0; $i < 250; $i++) {
            $daysAgo = 249 - $i;
            $trend = $i * 0.15;
            
            // Last 5 days: high volume
            $volume = ($i >= 245) ? 5000000 : 2000000;
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$daysAgo days")),
                'open' => $basePrice + $trend,
                'high' => $basePrice + $trend + 2,
                'low' => $basePrice + $trend - 1,
                'close' => $basePrice + $trend + rand(0, 1),
                'volume' => $volume + rand(-300000, 300000)
            ];
        }
        
        return $data;
    }
}
