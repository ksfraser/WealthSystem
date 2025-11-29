<?php

namespace Tests\Services\Trading;

use PHPUnit\Framework\TestCase;
use App\Services\Trading\WarrenBuffettStrategyService;
use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

/**
 * Tests for Warren Buffett Strategy Service
 * 
 * @covers \App\Services\Trading\WarrenBuffettStrategyService
 */
class WarrenBuffettStrategyServiceTest extends TestCase
{
    private WarrenBuffettStrategyService $strategy;
    private $mockMarketDataService;
    private $mockRepository;

    protected function setUp(): void
    {
        $this->mockMarketDataService = $this->createMock(MarketDataService::class);
        $this->mockRepository = $this->createMock(MarketDataRepositoryInterface::class);
        
        $this->strategy = new WarrenBuffettStrategyService(
            $this->mockMarketDataService,
            $this->mockRepository
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals("Warren Buffett Value Strategy", $this->strategy->getName());
    }

    public function testGetDescription(): void
    {
        $description = $this->strategy->getDescription();
        $this->assertStringContainsString("Warren Buffett", $description);
        $this->assertStringContainsString("12 investment tenets", $description);
        $this->assertStringContainsString("intrinsic value", $description);
    }

    public function testGetParameters(): void
    {
        $params = $this->strategy->getParameters();
        
        $this->assertArrayHasKey('min_roe_percent', $params);
        $this->assertArrayHasKey('margin_of_safety_percent', $params);
        $this->assertArrayHasKey('discount_rate_percent', $params);
        $this->assertEquals(15.0, $params['min_roe_percent']);
    }

    public function testSetParameters(): void
    {
        $newParams = ['min_roe_percent' => 20.0];
        $this->strategy->setParameters($newParams);
        
        $params = $this->strategy->getParameters();
        $this->assertEquals(20.0, $params['min_roe_percent']);
    }

    public function testAnalyzeReturnsHoldWithInsufficientData(): void
    {
        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn([]);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn([]);

        $result = $this->strategy->analyze('AAPL');

        $this->assertEquals('HOLD', $result['signal']);
        $this->assertEquals(0.0, $result['confidence']);
        $this->assertStringContainsString('Insufficient', $result['reason']);
    }

    public function testAnalyzeHighQualityBusinessBelowIntrinsicValue(): void
    {
        // Setup excellent Buffett-style company (like Coca-Cola or See's Candies)
        $fundamentals = [
            'sector' => 'Consumer Staples',
            'return_on_equity' => 0.30, // 30% ROE - excellent
            'return_on_assets' => 0.20,
            'profit_margin' => 0.30, // 30% margin - excellent
            'debt_to_equity' => 0.15, // Very low debt
            'current_ratio' => 2.5, // Strong liquidity
            'revenue_growth' => 0.12, // 12% growth
            'earnings_growth' => 0.14, // 14% earnings growth
            'market_cap' => 5000000000, // $5B
            'net_income' => 500000000, // $500M - strong
            'operating_cash_flow' => 600000000, // $600M
            'free_cash_flow' => 500000000,
            'capital_expenditures' => 100000000,
            'shares_outstanding' => 100000000,
            'total_assets' => 2500000000,
            'total_equity' => 1500000000,
            'revenue' => 1600000000,
            // Historical consistency
            'revenue_3y_avg' => 1500000000,
            'earnings_3y_avg' => 450000000,
            'roe_3y_avg' => 0.28,
            'brand_value' => 5000000000 // Strong brand
        ];

        // Price at $30, but intrinsic value should be $50+ (significant discount)
        $priceHistory = $this->generatePriceHistory(3650, 30.0); // 10 years, $30 price

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('AAPL');

        $this->assertEquals('BUY', $result['signal']);
        $this->assertGreaterThan(0.6, $result['confidence']);
        $this->assertArrayHasKey('intrinsic_value', $result['metadata']);
        $this->assertGreaterThan(0, $result['metadata']['intrinsic_value']);
    }

    public function testAnalyzeLowQualityBusinessReturnsSell(): void
    {
        $fundamentals = [
            'sector' => 'Technology',
            'return_on_equity' => 0.05, // Poor ROE
            'return_on_assets' => 0.02,
            'profit_margin' => 0.03, // Poor margin
            'debt_to_equity' => 2.5, // High debt
            'revenue_growth' => -0.05, // Declining revenue
            'market_cap' => 100000000,
            'net_income' => -10000000, // Losses
            'operating_cash_flow' => -5000000,
            'shares_outstanding' => 10000000
        ];

        $priceHistory = $this->generatePriceHistory(3650, 10.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('AAPL');

        $this->assertEquals('SELL', $result['signal']);
        $this->assertLessThan(0.5, $result['confidence']);
    }

    public function testAnalyzeOvervaluedStockReturnsSell(): void
    {
        $fundamentals = [
            'sector' => 'Consumer Staples',
            'return_on_equity' => 0.18,
            'return_on_assets' => 0.12,
            'profit_margin' => 0.20,
            'debt_to_equity' => 0.3,
            'revenue_growth' => 0.10,
            'market_cap' => 1000000000,
            'net_income' => 50000000,
            'operating_cash_flow' => 60000000,
            'capital_expenditures' => 10000000,
            'shares_outstanding' => 25000000,
            'earnings_growth' => 0.08
        ];

        // High price relative to fundamentals
        $priceHistory = $this->generatePriceHistory(3650, 150.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('AAPL');

        // Should be SELL or HOLD due to valuation
        $this->assertContains($result['signal'], ['SELL', 'HOLD']);
    }

    public function testBusinessTenantsScoring(): void
    {
        $fundamentals = [
            'sector' => 'Consumer Staples', // Simple business
            'return_on_equity' => 0.25, // Strong moat indicator
            'revenue_growth' => 0.15 // Favorable prospects
        ];

        $priceHistory = $this->generatePriceHistory(3650, 100.0); // 10 years of history

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('AAPL');

        $this->assertArrayHasKey('business_score', $result['metadata']);
        $this->assertGreaterThan(70, $result['metadata']['business_score']);
    }

    public function testManagementTenantsScoring(): void
    {
        $fundamentals = [
            'return_on_equity' => 0.18, // Good capital allocation
            'return_on_assets' => 0.12,
            'debt_to_equity' => 0.25, // Conservative debt
            'profit_margin' => 0.22, // Strong margins
            'sector' => 'Industrials',
            'revenue_growth' => 0.10,
            'market_cap' => 500000000,
            'net_income' => 30000000,
            'operating_cash_flow' => 35000000,
            'shares_outstanding' => 10000000
        ];

        $priceHistory = $this->generatePriceHistory(3650, 50.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('AAPL');

        $this->assertArrayHasKey('management_score', $result['metadata']);
        $this->assertGreaterThan(60, $result['metadata']['management_score']);
    }

    public function testFinancialTenantsScoring(): void
    {
        $fundamentals = [
            'return_on_equity' => 0.20, // Strong ROE
            'profit_margin' => 0.25, // High margins
            'debt_to_equity' => 0.3,
            'net_income' => 50000000,
            'operating_cash_flow' => 60000000, // Positive owner earnings
            'capital_expenditures' => 10000000,
            'sector' => 'Consumer Staples',
            'revenue_growth' => 0.10,
            'market_cap' => 1000000000,
            'shares_outstanding' => 20000000,
            'return_on_assets' => 0.12
        ];

        $priceHistory = $this->generatePriceHistory(3650, 75.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('AAPL');

        $this->assertArrayHasKey('financial_score', $result['metadata']);
        $this->assertGreaterThan(70, $result['metadata']['financial_score']);
    }

    public function testOwnerEarningsCalculation(): void
    {
        $fundamentals = [
            'net_income' => 100000000,
            'operating_cash_flow' => 120000000,
            'capital_expenditures' => 20000000,
            'return_on_equity' => 0.15,
            'profit_margin' => 0.15,
            'debt_to_equity' => 0.5,
            'sector' => 'Industrials',
            'revenue_growth' => 0.08,
            'market_cap' => 800000000,
            'shares_outstanding' => 25000000,
            'return_on_assets' => 0.10
        ];

        $priceHistory = $this->generatePriceHistory(3650, 40.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('AAPL');

        $this->assertArrayHasKey('owner_earnings', $result['metadata']);
        $ownerEarnings = $result['metadata']['owner_earnings'];
        
        // Should be operating cash flow - capex = 120M - 20M = 100M
        $this->assertEquals(100000000, $ownerEarnings);
    }

    public function testIntrinsicValueCalculation(): void
    {
        $fundamentals = [
            'net_income' => 50000000,
            'operating_cash_flow' => 60000000,
            'capital_expenditures' => 10000000,
            'shares_outstanding' => 25000000,
            'earnings_growth' => 0.10,
            'return_on_equity' => 0.16,
            'profit_margin' => 0.18,
            'debt_to_equity' => 0.4,
            'sector' => 'Consumer Staples',
            'revenue_growth' => 0.12,
            'market_cap' => 750000000,
            'return_on_assets' => 0.11
        ];

        $priceHistory = $this->generatePriceHistory(3650, 30.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('AAPL');

        $this->assertArrayHasKey('intrinsic_value', $result['metadata']);
        $this->assertGreaterThan(0, $result['metadata']['intrinsic_value']);
        
        // Intrinsic value should be calculated via DCF
        // Owner earnings per share = (60M - 10M) / 25M = $2
        // With 10% growth and 10% discount, should be > $20/share
        $this->assertGreaterThan(20, $result['metadata']['intrinsic_value']);
    }

    public function testMarginOfSafetyCalculation(): void
    {
        $fundamentals = [
            'net_income' => 80000000,
            'operating_cash_flow' => 100000000,
            'capital_expenditures' => 15000000,
            'shares_outstanding' => 40000000,
            'earnings_growth' => 0.12,
            'return_on_equity' => 0.22,
            'profit_margin' => 0.22,
            'debt_to_equity' => 0.25,
            'sector' => 'Healthcare',
            'revenue_growth' => 0.15,
            'market_cap' => 1200000000,
            'return_on_assets' => 0.14
        ];

        $priceHistory = $this->generatePriceHistory(3650, 25.0); // Low price for value

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('AAPL');

        $this->assertArrayHasKey('margin_of_safety', $result['metadata']);
        $marginOfSafety = $result['metadata']['margin_of_safety'];
        
        // Should be positive (price below intrinsic value)
        $this->assertGreaterThan(0, $marginOfSafety);
    }

    public function testMoatStrengthCalculation(): void
    {
        $fundamentals = [
            'profit_margin' => 0.30, // Strong brand
            'return_on_assets' => 0.18, // Cost advantages
            'market_cap' => 5000000000, // Scale
            'debt_to_equity' => 0.20, // Customer loyalty indicator
            'sector' => 'Healthcare', // Regulated sector
            'return_on_equity' => 0.20,
            'revenue_growth' => 0.12,
            'net_income' => 200000000,
            'operating_cash_flow' => 250000000,
            'shares_outstanding' => 100000000
        ];

        $priceHistory = $this->generatePriceHistory(3650, 40.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('AAPL');

        $this->assertArrayHasKey('moat_strength', $result['metadata']);
        $this->assertGreaterThan(70, $result['metadata']['moat_strength']);
    }

    public function testQualityScoreAggregation(): void
    {
        $fundamentals = [
            'sector' => 'Consumer Staples',
            'return_on_equity' => 0.20,
            'return_on_assets' => 0.13,
            'profit_margin' => 0.22,
            'debt_to_equity' => 0.30,
            'revenue_growth' => 0.12,
            'market_cap' => 1500000000,
            'net_income' => 75000000,
            'operating_cash_flow' => 90000000,
            'capital_expenditures' => 15000000,
            'shares_outstanding' => 30000000,
            'earnings_growth' => 0.11
        ];

        $priceHistory = $this->generatePriceHistory(3650, 50.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('AAPL');

        $this->assertArrayHasKey('quality_score', $result['metadata']);
        $qualityScore = $result['metadata']['quality_score'];
        
        // Quality score should be high for this excellent business
        $this->assertGreaterThan(70, $qualityScore);
        $this->assertLessThanOrEqual(100, $qualityScore);
    }

    public function testPositionSizingBasedOnQuality(): void
    {
        $highQualityFundamentals = [
            'sector' => 'Consumer Staples',
            'return_on_equity' => 0.25,
            'return_on_assets' => 0.16,
            'profit_margin' => 0.28,
            'debt_to_equity' => 0.15,
            'revenue_growth' => 0.15,
            'market_cap' => 3000000000,
            'net_income' => 150000000,
            'operating_cash_flow' => 180000000,
            'capital_expenditures' => 30000000,
            'shares_outstanding' => 50000000,
            'earnings_growth' => 0.13
        ];

        $priceHistory = $this->generatePriceHistory(3650, 40.0); // Good value

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($highQualityFundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('AAPL');

        $this->assertArrayHasKey('position_size', $result);
        $this->assertGreaterThan(0, $result['position_size']);
        $this->assertLessThanOrEqual(0.15, $result['position_size']); // Max 15%
    }

    public function testStopLossAndTakeProfitLevels(): void
    {
        $fundamentals = [
            'sector' => 'Industrials',
            'return_on_equity' => 0.18,
            'return_on_assets' => 0.12,
            'profit_margin' => 0.20,
            'debt_to_equity' => 0.35,
            'revenue_growth' => 0.10,
            'market_cap' => 800000000,
            'net_income' => 60000000,
            'operating_cash_flow' => 75000000,
            'capital_expenditures' => 12000000,
            'shares_outstanding' => 20000000,
            'earnings_growth' => 0.09
        ];

        $priceHistory = $this->generatePriceHistory(3650, 60.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('AAPL');

        $this->assertArrayHasKey('stop_loss', $result);
        $this->assertArrayHasKey('take_profit', $result);
        
        if ($result['stop_loss']) {
            $this->assertLessThan($result['entry_price'], $result['stop_loss']);
        }
        
        if ($result['take_profit']) {
            $this->assertGreaterThan($result['entry_price'], $result['take_profit']);
        }
    }

    public function testConfidenceCalculation(): void
    {
        $fundamentals = [
            'sector' => 'Consumer Staples',
            'return_on_equity' => 0.22,
            'return_on_assets' => 0.14,
            'profit_margin' => 0.24,
            'debt_to_equity' => 0.25,
            'revenue_growth' => 0.13,
            'market_cap' => 2000000000,
            'net_income' => 100000000,
            'operating_cash_flow' => 120000000,
            'capital_expenditures' => 20000000,
            'shares_outstanding' => 40000000,
            'earnings_growth' => 0.11
        ];

        $priceHistory = $this->generatePriceHistory(3650, 45.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('AAPL');

        $this->assertArrayHasKey('confidence', $result);
        $this->assertGreaterThanOrEqual(0.0, $result['confidence']);
        $this->assertLessThanOrEqual(1.0, $result['confidence']);
    }

    public function testMetadataCompleteness(): void
    {
        $fundamentals = [
            'sector' => 'Healthcare',
            'return_on_equity' => 0.19,
            'return_on_assets' => 0.13,
            'profit_margin' => 0.21,
            'debt_to_equity' => 0.30,
            'revenue_growth' => 0.11,
            'market_cap' => 1200000000,
            'net_income' => 70000000,
            'operating_cash_flow' => 85000000,
            'capital_expenditures' => 15000000,
            'shares_outstanding' => 25000000,
            'earnings_growth' => 0.10
        ];

        $priceHistory = $this->generatePriceHistory(3650, 55.0);

        $this->mockMarketDataService->method('getFundamentals')
            ->willReturn($fundamentals);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturn($priceHistory);

        $result = $this->strategy->analyze('AAPL');

        $metadata = $result['metadata'];
        
        $this->assertArrayHasKey('strategy', $metadata);
        $this->assertArrayHasKey('business_score', $metadata);
        $this->assertArrayHasKey('management_score', $metadata);
        $this->assertArrayHasKey('financial_score', $metadata);
        $this->assertArrayHasKey('value_score', $metadata);
        $this->assertArrayHasKey('quality_score', $metadata);
        $this->assertArrayHasKey('moat_strength', $metadata);
        $this->assertArrayHasKey('intrinsic_value', $metadata);
        $this->assertArrayHasKey('margin_of_safety', $metadata);
        $this->assertArrayHasKey('owner_earnings', $metadata);
    }

    /**
     * Helper: Generate price history data
     */
    private function generatePriceHistory(int $days, float $basePrice): array
    {
        $history = [];
        
        for ($i = 0; $i < $days; $i++) {
            $history[] = [
                'date' => date('Y-m-d', strtotime("-{$i} days")),
                'open' => $basePrice,
                'high' => $basePrice * 1.02,
                'low' => $basePrice * 0.98,
                'close' => $basePrice,
                'volume' => 1000000
            ];
        }
        
        return array_reverse($history);
    }
}
