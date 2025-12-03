<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\Trading\ContrarianStrategyService;
use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

class ContrarianStrategyServiceTest extends TestCase
{
    private $strategy;
    private $marketDataService;
    private $marketDataRepository;

    protected function setUp(): void
    {
        $this->marketDataService = $this->createMock(MarketDataService::class);
        $this->marketDataRepository = $this->createMock(MarketDataRepositoryInterface::class);
        
        $this->strategy = new ContrarianStrategyService(
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
        $this->assertArrayHasKey('min_drawdown_percent', $params);
        $this->assertArrayHasKey('max_drawdown_days', $params);
        $this->assertArrayHasKey('min_fundamental_score', $params);
    }

    /**
     * @test
     */
    public function it_implements_trading_strategy_interface()
    {
        $this->assertEquals('Contrarian', $this->strategy->getName());
        $this->assertNotEmpty($this->strategy->getDescription());
        $this->assertIsInt($this->strategy->getRequiredHistoricalDays());
        $this->assertGreaterThan(0, $this->strategy->getRequiredHistoricalDays());
    }

    /**
     * @test
     */
    public function it_returns_hold_with_insufficient_data()
    {
        $fundamentals = $this->getStrongFundamentals();
        $historicalData = array_slice($this->getOversoldData(), 0, 50);

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertEquals('HOLD', $result['action']);
        $this->assertEquals(0, $result['confidence']);
    }

    /**
     * @test
     */
    public function it_detects_oversold_conditions()
    {
        $fundamentals = $this->getStrongFundamentals();
        $historicalData = $this->getOversoldData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('is_oversold', $result['metrics']);
        $this->assertTrue($result['metrics']['is_oversold']);
        $this->assertArrayHasKey('drawdown_percent', $result['metrics']);
        $this->assertGreaterThan(0.20, $result['metrics']['drawdown_percent']); // >20% drawdown
    }

    /**
     * @test
     */
    public function it_calculates_sentiment_score()
    {
        $fundamentals = $this->getStrongFundamentals();
        $historicalData = $this->getOversoldData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('sentiment_score', $result['metrics']);
        $this->assertIsFloat($result['metrics']['sentiment_score']);
        $this->assertGreaterThanOrEqual(0, $result['metrics']['sentiment_score']);
        $this->assertLessThanOrEqual(1, $result['metrics']['sentiment_score']);
    }

    /**
     * @test
     */
    public function it_detects_panic_selling()
    {
        $fundamentals = $this->getStrongFundamentals();
        $historicalData = $this->getPanicSellingData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('panic_selling', $result['metrics']);
        $this->assertTrue($result['metrics']['panic_selling']);
        $this->assertArrayHasKey('volume_spike', $result['metrics']);
        $this->assertGreaterThan(1.8, $result['metrics']['volume_spike']); // >1.8x normal volume (diluted by stabilization period)
    }

    /**
     * @test
     */
    public function it_validates_fundamental_strength()
    {
        $fundamentals = $this->getStrongFundamentals();
        $historicalData = $this->getOversoldData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('fundamental_score', $result['metrics']);
        $this->assertGreaterThan(0.70, $result['metrics']['fundamental_score']);
    }

    /**
     * @test
     */
    public function it_calculates_recovery_potential()
    {
        $fundamentals = $this->getStrongFundamentals();
        $historicalData = $this->getOversoldData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('recovery_potential', $result['metrics']);
        $this->assertIsFloat($result['metrics']['recovery_potential']);
    }

    /**
     * @test
     */
    public function it_checks_valuation_at_discount()
    {
        $fundamentals = $this->getStrongFundamentals();
        $historicalData = $this->getOversoldData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('pe_ratio', $result['metrics']);
        $this->assertArrayHasKey('price_to_book', $result['metrics']);
    }

    /**
     * @test
     */
    public function it_calculates_contrarian_score()
    {
        $fundamentals = $this->getStrongFundamentals();
        $historicalData = $this->getOversoldData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('contrarian_score', $result['metrics']);
        $this->assertGreaterThanOrEqual(0, $result['metrics']['contrarian_score']);
        $this->assertLessThanOrEqual(1, $result['metrics']['contrarian_score']);
    }

    /**
     * @test
     */
    public function it_returns_buy_signal_with_oversold_strong_fundamentals()
    {
        $fundamentals = $this->getStrongFundamentals();
        $historicalData = $this->getPanicSellingData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertEquals('BUY', $result['action']);
        $this->assertGreaterThan(60, $result['confidence']);
    }

    /**
     * @test
     */
    public function it_returns_hold_with_weak_fundamentals()
    {
        $fundamentals = $this->getWeakFundamentals();
        $historicalData = $this->getOversoldData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertEquals('HOLD', $result['action']);
        $this->assertStringContainsString('fundamental', strtolower($result['reasoning']));
    }

    /**
     * @test
     */
    public function it_returns_hold_without_sufficient_drawdown()
    {
        $fundamentals = $this->getStrongFundamentals();
        $historicalData = $this->getNormalTrendData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertEquals('HOLD', $result['action']);
        $this->assertStringContainsString('drawdown', strtolower($result['reasoning']));
    }

    /**
     * @test
     */
    public function it_detects_capitulation_signals()
    {
        $fundamentals = $this->getStrongFundamentals();
        $historicalData = $this->getPanicSellingData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('capitulation', $result['metrics']);
        $this->assertTrue($result['metrics']['capitulation']);
    }

    /**
     * @test
     */
    public function it_checks_insider_buying()
    {
        $fundamentals = $this->getStrongFundamentalsWithInsiderBuying();
        $historicalData = $this->getOversoldData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('insider_buying', $result['metrics']);
        $this->assertTrue($result['metrics']['insider_buying']);
    }

    /**
     * @test
     */
    public function it_validates_debt_levels()
    {
        $fundamentals = $this->getStrongFundamentals();
        $historicalData = $this->getOversoldData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('debt_to_equity', $result['metrics']);
        $this->assertLessThan(2.0, $result['metrics']['debt_to_equity']);
    }

    /**
     * @test
     */
    public function it_calculates_rsi_extremes()
    {
        $fundamentals = $this->getStrongFundamentals();
        $historicalData = $this->getOversoldData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('rsi', $result['metrics']);
        $this->assertLessThan(35, $result['metrics']['rsi']); // Oversold RSI
    }

    /**
     * @test
     */
    public function it_detects_sentiment_reversal()
    {
        $fundamentals = $this->getStrongFundamentals();
        $historicalData = $this->getReversalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('sentiment_reversal', $result['metrics']);
        $this->assertTrue($result['metrics']['sentiment_reversal']);
    }

    /**
     * @test
     */
    public function it_validates_cash_flow_strength()
    {
        $fundamentals = $this->getStrongFundamentals();
        $historicalData = $this->getOversoldData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('free_cash_flow', $result['metrics']);
        $this->assertGreaterThan(0, $result['metrics']['free_cash_flow']);
    }

    /**
     * @test
     */
    public function it_builds_comprehensive_reasoning()
    {
        $fundamentals = $this->getStrongFundamentals();
        $historicalData = $this->getPanicSellingData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertNotEmpty($result['reasoning']);
        $keywords = ['oversold', 'fundamental', 'drawdown', 'contrarian'];
        $reasoning = strtolower($result['reasoning']);
        
        $foundKeywords = 0;
        foreach ($keywords as $keyword) {
            if (strpos($reasoning, $keyword) !== false) {
                $foundKeywords++;
            }
        }
        
        $this->assertGreaterThanOrEqual(2, $foundKeywords);
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
        $this->assertStringContainsString('Error', $result['reasoning']);
    }

    /**
     * @test
     */
    public function it_validates_parameters_are_within_expected_ranges()
    {
        $params = $this->strategy->getParameters();
        
        $this->assertGreaterThan(0, $params['min_drawdown_percent']);
        $this->assertLessThan(1, $params['min_drawdown_percent']);
        $this->assertGreaterThan(0, $params['min_fundamental_score']);
        $this->assertLessThan(1, $params['min_fundamental_score']);
    }

    /**
     * @test
     */
    public function it_can_update_parameters()
    {
        $newParams = [
            'min_drawdown_percent' => 0.25,
            'min_fundamental_score' => 0.75
        ];
        
        $this->strategy->setParameters($newParams);
        $params = $this->strategy->getParameters();
        
        $this->assertEquals(0.25, $params['min_drawdown_percent']);
        $this->assertEquals(0.75, $params['min_fundamental_score']);
    }

    // Helper methods to generate test data

    private function getStrongFundamentals(): array
    {
        return [
            'symbol' => 'TEST',
            'market_cap' => 5000000000,
            'price' => 80.0,
            'pe_ratio' => 12.0,
            'price_to_book' => 1.5,
            'roe' => 0.18,
            'debt_to_equity' => 0.60,
            'profit_margin' => 0.14,
            'revenue' => 10000000000,
            'prior_year_revenue' => 9000000000,
            'earnings_per_share' => 6.50,
            'free_cash_flow' => 1500000000,
            'operating_cash_flow' => 2000000000,
            'current_ratio' => 2.5,
            'quick_ratio' => 1.8,
            'earnings_history' => [
                ['year' => 2024, 'quarter' => 4, 'eps' => 1.70],
                ['year' => 2024, 'quarter' => 3, 'eps' => 1.65],
                ['year' => 2024, 'quarter' => 2, 'eps' => 1.60],
                ['year' => 2024, 'quarter' => 1, 'eps' => 1.55]
            ],
            'insider_transactions' => []
        ];
    }

    private function getStrongFundamentalsWithInsiderBuying(): array
    {
        $data = $this->getStrongFundamentals();
        $data['insider_transactions'] = [
            ['date' => date('Y-m-d', strtotime('-5 days')), 'type' => 'BUY', 'shares' => 50000, 'value' => 4000000],
            ['date' => date('Y-m-d', strtotime('-10 days')), 'type' => 'BUY', 'shares' => 30000, 'value' => 2400000],
            ['date' => date('Y-m-d', strtotime('-15 days')), 'type' => 'BUY', 'shares' => 20000, 'value' => 1600000]
        ];
        return $data;
    }

    private function getWeakFundamentals(): array
    {
        return [
            'symbol' => 'TEST',
            'market_cap' => 3000000000,
            'price' => 40.0,
            'pe_ratio' => -5.0, // Negative earnings
            'price_to_book' => 0.8,
            'roe' => -0.05, // Negative ROE
            'debt_to_equity' => 3.5, // High debt
            'profit_margin' => -0.03, // Negative margin
            'revenue' => 5000000000,
            'prior_year_revenue' => 6000000000, // Declining revenue
            'earnings_per_share' => -2.00,
            'free_cash_flow' => -500000000, // Negative FCF
            'operating_cash_flow' => -200000000,
            'current_ratio' => 0.8, // Poor liquidity
            'quick_ratio' => 0.5,
            'insider_transactions' => []
        ];
    }

    private function getOversoldData(): array
    {
        $data = [];
        $peakPrice = 120.0;
        
        // Generate 150 days: peak, then sharp decline ending recently for low RSI
        for ($i = 0; $i < 150; $i++) {
            $daysAgo = 149 - $i;
            
            if ($i < 100) {
                // Peak period
                $price = $peakPrice;
            } elseif ($i < 130) {
                // Sharp decline over 30 days (25% drawdown) - recent!
                $declineProgress = ($i - 100) / 30;
                $price = $peakPrice * (1 - 0.25 * $declineProgress);
            } else {
                // Last 20 days continuing to decline slowly (oversold RSI)
                $additionalDecline = ($i - 130) / 20 * 0.03; // 3% more decline
                $price = $peakPrice * (0.75 - $additionalDecline);
            }
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$daysAgo days")),
                'open' => $price,
                'high' => $price + 1,
                'low' => $price - 1,
                'close' => $price,
                'volume' => 2000000
            ];
        }
        
        return $data;
    }

    private function getPanicSellingData(): array
    {
        $data = [];
        $peakPrice = 120.0;
        
        // Generate 150 days: peak, then panic selling with volume spike (more recent)
        for ($i = 0; $i < 150; $i++) {
            $daysAgo = 149 - $i;
            
            if ($i < 100) {
                // Peak period
                $price = $peakPrice;
                $volume = 2000000;
            } elseif ($i < 115) {
                // Panic selling - 15 days of heavy volume and price collapse
                $declineProgress = ($i - 100) / 15;
                $price = $peakPrice * (1 - 0.35 * $declineProgress);
                $volume = 8000000; // 4x volume spike
            } else {
                // Stabilize at bottom (last 35 days)
                $price = $peakPrice * 0.65;
                $volume = 2500000; // Still slightly elevated
            }
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$daysAgo days")),
                'open' => $price,
                'high' => $price + 2,
                'low' => $price - 2,
                'close' => $price,
                'volume' => (int)$volume
            ];
        }
        
        return $data;
    }

    private function getNormalTrendData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        // Generate 150 days of normal price action (no significant drawdown)
        for ($i = 0; $i < 150; $i++) {
            $daysAgo = 149 - $i;
            $price = $basePrice + ($i * 0.05); // Slight uptrend
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$daysAgo days")),
                'open' => $price,
                'high' => $price + 1,
                'low' => $price - 1,
                'close' => $price,
                'volume' => 2000000
            ];
        }
        
        return $data;
    }

    private function getReversalData(): array
    {
        $data = [];
        $peakPrice = 120.0;
        
        // Generate 150 days: decline, then reversal signal (higher lows, volume increase)
        for ($i = 0; $i < 150; $i++) {
            $daysAgo = 149 - $i;
            
            if ($i < 50) {
                // Peak period
                $price = $peakPrice;
                $volume = 2000000;
            } elseif ($i < 100) {
                // Decline
                $declineProgress = ($i - 50) / 50;
                $price = $peakPrice * (1 - 0.25 * $declineProgress);
                $volume = 2000000;
            } else {
                // Reversal - higher lows, increasing volume
                $recoveryProgress = ($i - 100) / 50;
                $price = $peakPrice * 0.75 + ($recoveryProgress * 5); // Bottoming with higher lows
                $volume = 2000000 + ($recoveryProgress * 2000000); // Volume increasing
            }
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$daysAgo days")),
                'open' => $price,
                'high' => $price + 1,
                'low' => $price - 1,
                'close' => $price,
                'volume' => (int)$volume
            ];
        }
        
        return $data;
    }
}
