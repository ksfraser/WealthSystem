<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\Trading\QualityDividendStrategyService;
use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

class QualityDividendStrategyServiceTest extends TestCase
{
    private QualityDividendStrategyService $strategy;
    private $marketDataService;
    private $marketDataRepository;

    protected function setUp(): void
    {
        $this->marketDataService = $this->createMock(MarketDataService::class);
        $this->marketDataRepository = $this->createMock(MarketDataRepositoryInterface::class);
        
        $this->strategy = new QualityDividendStrategyService(
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
        $this->assertArrayHasKey('min_dividend_yield', $params);
        $this->assertArrayHasKey('min_dividend_growth_years', $params);
        $this->assertArrayHasKey('max_payout_ratio', $params);
    }

    /**
     * @test
     */
    public function it_implements_trading_strategy_interface()
    {
        $this->assertEquals('QualityDividend', $this->strategy->getName());
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
    public function it_calculates_dividend_yield()
    {
        $fundamentals = $this->getQualityDividendFundamentals();
        $historicalData = $this->getStableHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('dividend_yield', $result['metrics']);
        $this->assertGreaterThan(0, $result['metrics']['dividend_yield']);
        $this->assertTrue(is_float($result['metrics']['dividend_yield']) || is_int($result['metrics']['dividend_yield']));
    }

    /**
     * @test
     */
    public function it_validates_dividend_growth_streak()
    {
        $fundamentals = $this->getConsecutiveGrowthFundamentals();
        $historicalData = $this->getStableHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('dividend_growth_streak', $result['metrics']);
        $this->assertGreaterThanOrEqual(5, $result['metrics']['dividend_growth_streak']);
    }

    /**
     * @test
     */
    public function it_calculates_payout_ratio()
    {
        $fundamentals = $this->getQualityDividendFundamentals();
        $historicalData = $this->getStableHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('payout_ratio', $result['metrics']);
        $this->assertLessThan(0.80, $result['metrics']['payout_ratio']);
        $this->assertGreaterThan(0, $result['metrics']['payout_ratio']);
    }

    /**
     * @test
     */
    public function it_validates_free_cash_flow_coverage()
    {
        $fundamentals = $this->getStrongFCFFundamentals();
        $historicalData = $this->getStableHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('fcf_coverage', $result['metrics']);
        $this->assertGreaterThan(1.0, $result['metrics']['fcf_coverage']);
    }

    /**
     * @test
     */
    public function it_calculates_dividend_growth_rate()
    {
        $fundamentals = $this->getConsecutiveGrowthFundamentals();
        $historicalData = $this->getStableHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('avg_dividend_growth_rate', $result['metrics']);
        $this->assertGreaterThan(0, $result['metrics']['avg_dividend_growth_rate']);
    }

    /**
     * @test
     */
    public function it_validates_earnings_stability()
    {
        $fundamentals = $this->getStableEarningsFundamentals();
        $historicalData = $this->getStableHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('earnings_stability', $result['metrics']);
        $this->assertTrue($result['metrics']['earnings_stability']);
    }

    /**
     * @test
     */
    public function it_checks_dividend_safety_score()
    {
        $fundamentals = $this->getQualityDividendFundamentals();
        $historicalData = $this->getStableHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('dividend_safety_score', $result['metrics']);
        $this->assertGreaterThanOrEqual(0, $result['metrics']['dividend_safety_score']);
        $this->assertLessThanOrEqual(1, $result['metrics']['dividend_safety_score']);
    }

    /**
     * @test
     */
    public function it_returns_buy_signal_with_quality_dividend()
    {
        $fundamentals = $this->getExcellentDividendFundamentals();
        $historicalData = $this->getStableHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertEquals('BUY', $result['action']);
        $this->assertGreaterThan(60, $result['confidence']);
    }

    /**
     * @test
     */
    public function it_returns_hold_with_low_yield()
    {
        $fundamentals = $this->getLowYieldFundamentals();
        $historicalData = $this->getStableHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertEquals('HOLD', $result['action']);
        $this->assertStringContainsString('yield', strtolower($result['reasoning']));
    }

    /**
     * @test
     */
    public function it_returns_hold_with_high_payout_ratio()
    {
        $fundamentals = $this->getHighPayoutFundamentals();
        $historicalData = $this->getStableHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertEquals('HOLD', $result['action']);
        $this->assertStringContainsString('payout', strtolower($result['reasoning']));
    }

    /**
     * @test
     */
    public function it_validates_debt_to_equity_ratio()
    {
        $fundamentals = $this->getQualityDividendFundamentals();
        $historicalData = $this->getStableHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('debt_to_equity', $result['metrics']);
        $this->assertLessThan(2.0, $result['metrics']['debt_to_equity']);
    }

    /**
     * @test
     */
    public function it_checks_roe_quality()
    {
        $fundamentals = $this->getHighROEFundamentals();
        $historicalData = $this->getStableHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('roe', $result['metrics']);
        $this->assertGreaterThan(0.10, $result['metrics']['roe']);
    }

    /**
     * @test
     */
    public function it_validates_revenue_growth()
    {
        $fundamentals = $this->getGrowingRevenueFundamentals();
        $historicalData = $this->getStableHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('revenue_growth', $result['metrics']);
        $this->assertGreaterThan(0, $result['metrics']['revenue_growth']);
    }

    /**
     * @test
     */
    public function it_detects_dividend_cuts()
    {
        $fundamentals = $this->getDividendCutFundamentals();
        $historicalData = $this->getStableHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertEquals('HOLD', $result['action']);
        $this->assertStringContainsString('cut', strtolower($result['reasoning']));
    }

    /**
     * @test
     */
    public function it_validates_pe_ratio_valuation()
    {
        $fundamentals = $this->getQualityDividendFundamentals();
        $historicalData = $this->getStableHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('pe_ratio', $result['metrics']);
        $this->assertGreaterThan(0, $result['metrics']['pe_ratio']);
    }

    /**
     * @test
     */
    public function it_checks_dividend_aristocrat_status()
    {
        $fundamentals = $this->getAristocratFundamentals();
        $historicalData = $this->getStableHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('is_dividend_aristocrat', $result['metrics']);
        $this->assertTrue($result['metrics']['is_dividend_aristocrat']);
    }

    /**
     * @test
     */
    public function it_builds_comprehensive_reasoning()
    {
        $fundamentals = $this->getQualityDividendFundamentals();
        $historicalData = $this->getStableHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertNotEmpty($result['reasoning']);
        $keywords = ['dividend', 'yield', 'payout', 'growth'];
        $reasoning = strtolower($result['reasoning']);
        $found = false;
        foreach ($keywords as $keyword) {
            if (strpos($reasoning, $keyword) !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Reasoning should contain dividend-related keywords');
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
        
        $this->assertGreaterThan(0, $params['min_dividend_yield']);
        $this->assertLessThan(1.0, $params['max_payout_ratio']);
        $this->assertGreaterThan(0, $params['min_dividend_growth_years']);
        $this->assertLessThan(100, $params['min_dividend_growth_years']);
    }

    /**
     * @test
     */
    public function it_can_update_parameters()
    {
        $newParams = [
            'min_dividend_yield' => 0.04,
            'max_payout_ratio' => 0.70
        ];
        
        $this->strategy->setParameters($newParams);
        $params = $this->strategy->getParameters();
        
        $this->assertEquals(0.04, $params['min_dividend_yield']);
        $this->assertEquals(0.70, $params['max_payout_ratio']);
    }

    // Helper methods for test data

    private function getBasicFundamentals(): array
    {
        return [
            'symbol' => 'TEST',
            'market_cap' => 5000000000,
            'price' => 50.0,
            'pe_ratio' => 15.0,
            'dividend_yield' => 0.03,
            'earnings_per_share' => 3.33,
            'dividend_per_share' => 1.50
        ];
    }

    private function getQualityDividendFundamentals(): array
    {
        return [
            'symbol' => 'TEST',
            'market_cap' => 10000000000,
            'price' => 100.0,
            'pe_ratio' => 18.0,
            'dividend_yield' => 0.035,
            'earnings_per_share' => 5.55,
            'dividend_per_share' => 3.50,
            'free_cash_flow' => 5000000000,
            'total_dividends_paid' => 3000000000,
            'debt_to_equity' => 0.80,
            'roe' => 0.15,
            'revenue' => 20000000000,
            'dividend_history' => [
                ['year' => 2024, 'dividend' => 3.50],
                ['year' => 2023, 'dividend' => 3.30],
                ['year' => 2022, 'dividend' => 3.10],
                ['year' => 2021, 'dividend' => 2.95],
                ['year' => 2020, 'dividend' => 2.80],
                ['year' => 2019, 'dividend' => 2.65]
            ],
            'earnings_history' => [
                ['year' => 2024, 'eps' => 5.55],
                ['year' => 2023, 'eps' => 5.40],
                ['year' => 2022, 'eps' => 5.20],
                ['year' => 2021, 'eps' => 5.00],
                ['year' => 2020, 'eps' => 4.85]
            ]
        ];
    }

    private function getConsecutiveGrowthFundamentals(): array
    {
        $data = $this->getQualityDividendFundamentals();
        $data['dividend_history'] = [
            ['year' => 2024, 'dividend' => 4.00],
            ['year' => 2023, 'dividend' => 3.80],
            ['year' => 2022, 'dividend' => 3.60],
            ['year' => 2021, 'dividend' => 3.40],
            ['year' => 2020, 'dividend' => 3.20],
            ['year' => 2019, 'dividend' => 3.00],
            ['year' => 2018, 'dividend' => 2.80],
            ['year' => 2017, 'dividend' => 2.60]
        ];
        return $data;
    }

    private function getStrongFCFFundamentals(): array
    {
        $data = $this->getQualityDividendFundamentals();
        $data['free_cash_flow'] = 8000000000;
        $data['total_dividends_paid'] = 3000000000;
        return $data;
    }

    private function getStableEarningsFundamentals(): array
    {
        $data = $this->getQualityDividendFundamentals();
        $data['earnings_history'] = [
            ['year' => 2024, 'eps' => 5.55],
            ['year' => 2023, 'eps' => 5.50],
            ['year' => 2022, 'eps' => 5.45],
            ['year' => 2021, 'eps' => 5.40],
            ['year' => 2020, 'eps' => 5.35],
            ['year' => 2019, 'eps' => 5.30]
        ];
        return $data;
    }

    private function getExcellentDividendFundamentals(): array
    {
        return [
            'symbol' => 'TEST',
            'market_cap' => 15000000000,
            'price' => 80.0,
            'pe_ratio' => 16.0,
            'dividend_yield' => 0.045,
            'earnings_per_share' => 6.00,
            'dividend_per_share' => 3.60,
            'free_cash_flow' => 6000000000,
            'total_dividends_paid' => 2500000000,
            'debt_to_equity' => 0.60,
            'roe' => 0.18,
            'revenue' => 18000000000,
            'prior_year_revenue' => 16500000000,
            'dividend_history' => [
                ['year' => 2024, 'dividend' => 3.60],
                ['year' => 2023, 'dividend' => 3.40],
                ['year' => 2022, 'dividend' => 3.20],
                ['year' => 2021, 'dividend' => 3.00],
                ['year' => 2020, 'dividend' => 2.85],
                ['year' => 2019, 'dividend' => 2.70],
                ['year' => 2018, 'dividend' => 2.55]
            ],
            'earnings_history' => [
                ['year' => 2024, 'eps' => 5.00],
                ['year' => 2023, 'eps' => 4.85],
                ['year' => 2022, 'eps' => 4.70],
                ['year' => 2021, 'eps' => 4.55],
                ['year' => 2020, 'eps' => 4.40],
                ['year' => 2019, 'eps' => 4.25]
            ]
        ];
    }

    private function getLowYieldFundamentals(): array
    {
        $data = $this->getQualityDividendFundamentals();
        $data['dividend_yield'] = 0.015;
        $data['dividend_per_share'] = 1.50;
        return $data;
    }

    private function getHighPayoutFundamentals(): array
    {
        $data = $this->getQualityDividendFundamentals();
        $data['dividend_per_share'] = 5.00;
        $data['earnings_per_share'] = 5.50;
        return $data;
    }

    private function getHighROEFundamentals(): array
    {
        $data = $this->getQualityDividendFundamentals();
        $data['roe'] = 0.22;
        return $data;
    }

    private function getGrowingRevenueFundamentals(): array
    {
        $data = $this->getQualityDividendFundamentals();
        $data['revenue'] = 22000000000;
        $data['prior_year_revenue'] = 20000000000;
        return $data;
    }

    private function getDividendCutFundamentals(): array
    {
        $data = $this->getQualityDividendFundamentals();
        $data['dividend_history'] = [
            ['year' => 2024, 'dividend' => 2.00],
            ['year' => 2023, 'dividend' => 3.50],
            ['year' => 2022, 'dividend' => 3.40],
            ['year' => 2021, 'dividend' => 3.30]
        ];
        return $data;
    }

    private function getAristocratFundamentals(): array
    {
        $data = $this->getQualityDividendFundamentals();
        $dividendHistory = [];
        // Generate 26 years (1999-2024) of consecutive growth
        for ($year = 2024; $year >= 1999; $year--) {
            $yearsFromStart = $year - 1999; // 0 for 1999, 25 for 2024
            $dividendHistory[] = [
                'year' => $year,
                'dividend' => 2.00 + ($yearsFromStart * 0.10) // 2.00 in 1999, 4.50 in 2024
            ];
        }
        $data['dividend_history'] = $dividendHistory;
        return $data;
    }

    private function getStableHistoricalData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        for ($i = 0; $i < 60; $i++) {
            $daysAgo = 59 - $i;
            $variation = sin($i / 10) * 3;
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$daysAgo days")),
                'open' => $basePrice + $variation,
                'high' => $basePrice + $variation + 2,
                'low' => $basePrice + $variation - 2,
                'close' => $basePrice + $variation + rand(-1, 1),
                'volume' => 2000000 + rand(-500000, 500000)
            ];
        }
        
        return $data;
    }
}
