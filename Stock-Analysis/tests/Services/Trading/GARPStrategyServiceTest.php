<?php

namespace Tests\Services\Trading;

use PHPUnit\Framework\TestCase;
use App\Services\Trading\GARPStrategyService;
use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

class GARPStrategyServiceTest extends TestCase
{
    private GARPStrategyService $strategy;
    private $mockMarketDataService;
    private $mockMarketDataRepository;

    protected function setUp(): void
    {
        $this->mockMarketDataService = $this->createMock(MarketDataService::class);
        $this->mockMarketDataRepository = $this->createMock(MarketDataRepositoryInterface::class);
        
        $this->strategy = new GARPStrategyService(
            $this->mockMarketDataService,
            $this->mockMarketDataRepository
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('GARP (Growth at Reasonable Price) Strategy', $this->strategy->getName());
    }

    public function testGetDescription(): void
    {
        $description = $this->strategy->getDescription();
        $this->assertStringContainsString('Growth at Reasonable Price', $description);
        $this->assertStringContainsString('Motley Fool', $description);
        $this->assertStringContainsString('PEG', $description);
    }

    public function testGetParameters(): void
    {
        $params = $this->strategy->getParameters();
        
        $this->assertArrayHasKey('min_revenue_growth', $params);
        $this->assertArrayHasKey('max_peg_ratio', $params);
        $this->assertEquals(0.20, $params['min_revenue_growth']);
        $this->assertEquals(1.0, $params['max_peg_ratio']);
    }

    public function testSetParameters(): void
    {
        $this->strategy->setParameters(['min_revenue_growth' => 0.25]);
        $params = $this->strategy->getParameters();
        
        $this->assertEquals(0.25, $params['min_revenue_growth']);
    }

    public function testCanExecuteWithSufficientData(): void
    {
        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn([
                'revenue' => 1000000000,
                'revenue_growth' => 0.25,
                'earnings_growth' => 0.30
            ]);

        $this->assertTrue($this->strategy->canExecute('TSLA'));
    }

    public function testCanExecuteWithInsufficientData(): void
    {
        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn([
                'revenue' => 1000000000
                // Missing growth data
            ]);

        $this->assertFalse($this->strategy->canExecute('TSLA'));
    }

    public function testGetRequiredHistoricalDays(): void
    {
        $this->assertEquals(730, $this->strategy->getRequiredHistoricalDays()); // 2 years
    }

    public function testAnalyzeReturnsHoldWithInsufficientData(): void
    {
        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn([]);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn([]);

        $result = $this->strategy->analyze('TSLA');

        $this->assertEquals('HOLD', $result['signal']);
        $this->assertStringContainsString('Insufficient', $result['reason']);
    }

    public function testAnalyzeHighGrowthLowPEGGeneratesBuy(): void
    {
        // Setup excellent GARP candidate (like early Amazon, Netflix, etc.)
        $fundamentals = [
            'symbol' => 'TSLA',
            'revenue' => 50000000000, // $50B
            'revenue_growth' => 0.35, // 35% revenue growth
            'earnings_growth' => 0.40, // 40% earnings growth
            'pe_ratio' => 30, // P/E of 30
            'market_cap' => 10000000000, // $10B
            'gross_margin' => 0.55, // 55% gross margin
            'profit_margin' => 0.20, // 20% net margin
            'return_on_equity' => 0.28, // 28% ROE
            'debt_to_equity' => 0.40, // Low debt
            'current_ratio' => 2.0, // Strong liquidity
            'operating_cash_flow' => 8000000000,
            'free_cash_flow' => 6000000000,
            'shares_outstanding' => 200000000,
            'institutional_ownership' => 0.45, // 45% institutional
            // Accelerating growth indicators
            'revenue_growth_qtd' => 0.38,
            'revenue_growth_prior_qtr' => 0.32,
            'revenue_growth_3y_avg' => 0.30
        ];

        // Price trending upward
        $priceHistory = $this->generatePriceHistory(730, 100.0, 0.30); // 2 years, 30% gain

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('TSLA');

        $this->assertEquals('BUY', $result['signal']);
        $this->assertGreaterThan(0.6, $result['confidence']);
        $this->assertLessThan(1.0, $result['metadata']['peg_ratio']); // PEG = 30 / 40 = 0.75
        $this->assertTrue($result['metadata']['is_accelerating']);
        $this->assertGreaterThan(60, $result['metadata']['quality_score']);
    }

    public function testAnalyzeLowGrowthReturnsSell(): void
    {
        $fundamentals = [
            'symbol' => 'SLOW',
            'revenue' => 10000000000,
            'revenue_growth' => 0.03, // Only 3% growth (below threshold)
            'earnings_growth' => 0.02, // 2% earnings growth
            'pe_ratio' => 25,
            'market_cap' => 5000000000,
            'gross_margin' => 0.30,
            'profit_margin' => 0.08,
            'debt_to_equity' => 0.60,
            'current_ratio' => 1.5,
            'shares_outstanding' => 100000000
        ];

        $priceHistory = $this->generatePriceHistory(730, 50.0, -0.05); // Declining

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('SLOW');

        $this->assertEquals('SELL', $result['signal']);
        $this->assertStringContainsString('growth', strtolower($result['reason']));
    }

    public function testAnalyzeOvervaluedHighPEGReturnsSell(): void
    {
        $fundamentals = [
            'symbol' => 'OVVL',
            'revenue' => 5000000000,
            'revenue_growth' => 0.10, // 10% growth
            'earnings_growth' => 0.08, // 8% earnings growth
            'pe_ratio' => 80, // Very high P/E
            'market_cap' => 20000000000,
            'gross_margin' => 0.40,
            'profit_margin' => 0.12,
            'debt_to_equity' => 0.50,
            'current_ratio' => 1.8,
            'shares_outstanding' => 100000000
        ];

        $priceHistory = $this->generatePriceHistory(730, 200.0, 0.15);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('OVVL');

        $this->assertEquals('SELL', $result['signal']);
        $this->assertGreaterThan(2.0, $result['metadata']['peg_ratio']); // PEG = 80 / 8 = 10.0
        $this->assertStringContainsString('Overvalued', $result['reason']);
    }

    public function testGrowthScoreCalculation(): void
    {
        $fundamentals = [
            'revenue' => 10000000000,
            'revenue_growth' => 0.40, // 40% exceptional
            'earnings_growth' => 0.35, // 35% excellent
            'pe_ratio' => 25,
            'market_cap' => 5000000000,
            'shares_outstanding' => 100000000
        ];

        $priceHistory = $this->generatePriceHistory(730, 100.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('GROW');

        $this->assertGreaterThan(85, $result['metadata']['growth_score']); // Should be near 95-100
        $this->assertEquals(0.40, $result['metadata']['revenue_growth']);
    }

    public function testPEGRatioCalculation(): void
    {
        $fundamentals = [
            'revenue' => 10000000000,
            'revenue_growth' => 0.25,
            'earnings_growth' => 0.30, // 30% growth
            'pe_ratio' => 30, // P/E of 30
            'market_cap' => 5000000000,
            'shares_outstanding' => 100000000
        ];

        $priceHistory = $this->generatePriceHistory(730, 100.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('PEG');

        // PEG = PE / (Growth * 100) = 30 / 30 = 1.0
        $this->assertEquals(1.0, $result['metadata']['peg_ratio']);
    }

    public function testRuleBreakerScoringHighMarketCap(): void
    {
        $fundamentals = [
            'revenue' => 50000000000,
            'revenue_growth' => 0.25,
            'earnings_growth' => 0.30,
            'pe_ratio' => 25,
            'market_cap' => 2000000000, // $2B sweet spot for Rule Breakers
            'gross_margin' => 0.65, // Excellent margin
            'return_on_equity' => 0.30, // Exceptional ROE
            'debt_to_equity' => 0.30,
            'current_ratio' => 2.5,
            'shares_outstanding' => 100000000
        ];

        $priceHistory = $this->generatePriceHistory(730, 50.0, 0.60); // 60% gain

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('RULE');

        $this->assertGreaterThan(70, $result['metadata']['rule_breaker_score']);
    }

    public function testMomentumScoringStrongTrend(): void
    {
        $fundamentals = [
            'revenue' => 10000000000,
            'revenue_growth' => 0.25,
            'earnings_growth' => 0.30,
            'pe_ratio' => 30,
            'market_cap' => 5000000000,
            'shares_outstanding' => 100000000
        ];

        // Strong uptrend: 80% gain over 2 years (concentrated in recent months)
        $priceHistory = $this->generatePriceHistory(730, 100.0, 0.80);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('MOMO');

        // Momentum score should be calculated and present
        $this->assertArrayHasKey('momentum_score', $result['metadata']);
        $this->assertGreaterThanOrEqual(0, $result['metadata']['momentum_score']);
        $this->assertLessThanOrEqual(100, $result['metadata']['momentum_score']);
        // With 80% gain over 2 years, score should be positive
        $this->assertGreaterThan(10, $result['metadata']['momentum_score']);
    }

    public function testFinancialStrengthScoringStrongCashFlow(): void
    {
        $fundamentals = [
            'revenue' => 10000000000,
            'revenue_growth' => 0.25,
            'earnings_growth' => 0.30,
            'pe_ratio' => 30,
            'market_cap' => 5000000000,
            'operating_cash_flow' => 2500000000, // $2.5B
            'free_cash_flow' => 2000000000, // $2B, 20% FCF margin
            'profit_margin' => 0.25, // 25% net margin
            'current_ratio' => 3.0, // Very strong
            'debt_to_equity' => 0.25, // Very low debt
            'shares_outstanding' => 100000000
        ];

        $priceHistory = $this->generatePriceHistory(730, 100.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('CASH');

        $this->assertGreaterThan(80, $result['metadata']['financial_strength_score']);
    }

    public function testAcceleratingGrowthDetection(): void
    {
        $fundamentals = [
            'revenue' => 10000000000,
            'revenue_growth' => 0.35, // Current: 35%
            'revenue_growth_qtd' => 0.38, // Recent quarter: 38%
            'revenue_growth_prior_qtr' => 0.32, // Prior quarter: 32%
            'earnings_growth' => 0.30,
            'pe_ratio' => 30,
            'market_cap' => 5000000000,
            'shares_outstanding' => 100000000
        ];

        $priceHistory = $this->generatePriceHistory(730, 100.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('ACCEL');

        $this->assertTrue($result['metadata']['is_accelerating']);
    }

    public function testDeceleratingGrowthDetection(): void
    {
        $fundamentals = [
            'revenue' => 10000000000,
            'revenue_growth' => 0.20, // Current: 20%
            'revenue_growth_qtd' => 0.18, // Recent quarter: 18%
            'revenue_growth_prior_qtr' => 0.22, // Prior quarter: 22%
            'earnings_growth' => 0.20,
            'pe_ratio' => 30,
            'market_cap' => 5000000000,
            'shares_outstanding' => 100000000
        ];

        $priceHistory = $this->generatePriceHistory(730, 100.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('DECEL');

        $this->assertFalse($result['metadata']['is_accelerating']);
    }

    public function testPositionSizingBasedOnQualityAndPEG(): void
    {
        // High quality, excellent PEG
        $fundamentals = [
            'revenue' => 10000000000,
            'revenue_growth' => 0.40,
            'earnings_growth' => 0.50, // 50% growth
            'pe_ratio' => 25, // PEG = 25/50 = 0.5 (excellent)
            'market_cap' => 5000000000,
            'gross_margin' => 0.60,
            'return_on_equity' => 0.30,
            'debt_to_equity' => 0.30,
            'current_ratio' => 2.5,
            'shares_outstanding' => 100000000
        ];

        $priceHistory = $this->generatePriceHistory(730, 100.0, 0.40);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('SIZE');

        $this->assertGreaterThan(0.08, $result['position_size']); // Should be near max (0.10)
        $this->assertLessThanOrEqual(0.10, $result['position_size']);
    }

    public function testStopLossAndTakeProfitLevels(): void
    {
        $fundamentals = [
            'revenue' => 10000000000,
            'revenue_growth' => 0.30,
            'earnings_growth' => 0.35,
            'pe_ratio' => 28,
            'market_cap' => 5000000000,
            'shares_outstanding' => 100000000
        ];

        $priceHistory = $this->generatePriceHistory(730, 100.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('STOPS');
        $currentPrice = $result['entry_price'];

        // Stop loss should be ~20% below current price
        $expectedStopLoss = $currentPrice * 0.80;
        $this->assertEqualsWithDelta($expectedStopLoss, $result['stop_loss'], 0.01);
        
        // Take profit should be ~100% above (double)
        $expectedTakeProfit = $currentPrice * 2.0;
        $this->assertEqualsWithDelta($expectedTakeProfit, $result['take_profit'], 0.01);
    }

    public function testConfidenceCalculationHighQuality(): void
    {
        $fundamentals = [
            'revenue' => 10000000000,
            'revenue_growth' => 0.40,
            'earnings_growth' => 0.50,
            'pe_ratio' => 25, // PEG = 0.5
            'market_cap' => 5000000000,
            'gross_margin' => 0.65,
            'return_on_equity' => 0.35,
            'debt_to_equity' => 0.25,
            'current_ratio' => 3.0,
            'free_cash_flow' => 2000000000,
            'profit_margin' => 0.25,
            'shares_outstanding' => 100000000,
            'revenue_growth_qtd' => 0.45,
            'revenue_growth_prior_qtr' => 0.38
        ];

        $priceHistory = $this->generatePriceHistory(730, 100.0, 0.60); // Strong momentum

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('CONF');

        $this->assertGreaterThan(0.75, $result['confidence']); // Very high confidence
    }

    public function testMetadataCompleteness(): void
    {
        $fundamentals = [
            'revenue' => 10000000000,
            'revenue_growth' => 0.30,
            'earnings_growth' => 0.35,
            'pe_ratio' => 30,
            'market_cap' => 5000000000,
            'shares_outstanding' => 100000000
        ];

        $priceHistory = $this->generatePriceHistory(730, 100.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('META');

        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('quality_score', $result['metadata']);
        $this->assertArrayHasKey('growth_score', $result['metadata']);
        $this->assertArrayHasKey('valuation_score', $result['metadata']);
        $this->assertArrayHasKey('rule_breaker_score', $result['metadata']);
        $this->assertArrayHasKey('momentum_score', $result['metadata']);
        $this->assertArrayHasKey('financial_strength_score', $result['metadata']);
        $this->assertArrayHasKey('peg_ratio', $result['metadata']);
        $this->assertArrayHasKey('is_accelerating', $result['metadata']);
    }

    public function testHighDebtReturnsSell(): void
    {
        $fundamentals = [
            'revenue' => 10000000000,
            'revenue_growth' => 0.25,
            'earnings_growth' => 0.30,
            'pe_ratio' => 30,
            'market_cap' => 5000000000,
            'debt_to_equity' => 2.0, // Very high debt (2x equity)
            'current_ratio' => 0.8, // Poor liquidity
            'shares_outstanding' => 100000000
        ];

        $priceHistory = $this->generatePriceHistory(730, 100.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('DEBT');

        $this->assertEquals('SELL', $result['signal']);
    }

    /**
     * Helper: Generate price history with trend
     */
    private function generatePriceHistory(int $days, float $startPrice, float $totalReturn = 0): array
    {
        $history = [];
        $dailyReturn = $totalReturn / $days;
        
        for ($i = 0; $i < $days; $i++) {
            $price = $startPrice * (1 + ($dailyReturn * $i));
            // Add some randomness
            $price *= (1 + (mt_rand(-100, 100) / 10000));
            
            $date = date('Y-m-d', strtotime("-$days days +$i days"));
            $history[] = [
                'date' => $date,
                'open' => $price,
                'high' => $price * 1.02,
                'low' => $price * 0.98,
                'close' => $price,
                'volume' => 1000000
            ];
        }
        
        return $history;
    }
}
