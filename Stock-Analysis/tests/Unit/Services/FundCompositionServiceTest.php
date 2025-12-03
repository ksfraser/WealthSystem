<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\FundCompositionService;
use App\Services\MarketDataService;
use App\DAOs\FundDAO;
use App\DAOs\FundHoldingDAO;
use App\DAOs\FundEligibilityDAO;
use App\Models\Fund;

/**
 * Fund Composition Service Tests
 * 
 * Tests ETF/mutual fund/seg fund analysis, holdings, eligibility, MER comparison.
 */
class FundCompositionServiceTest extends TestCase
{
    private FundCompositionService $service;
    private $mockFundDAO;
    private $mockHoldingDAO;
    private $mockEligibilityDAO;
    private $mockMarketDataService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockFundDAO = $this->createMock(FundDAO::class);
        $this->mockHoldingDAO = $this->createMock(FundHoldingDAO::class);
        $this->mockEligibilityDAO = $this->createMock(FundEligibilityDAO::class);
        $this->mockMarketDataService = $this->createMock(MarketDataService::class);
        
        $this->service = new FundCompositionService(
            $this->mockFundDAO,
            $this->mockHoldingDAO,
            $this->mockEligibilityDAO,
            $this->mockMarketDataService
        );
    }
    
    /**
     * Test getting fund composition with holdings
     */
    public function testGetFundCompositionReturnsCompleteData(): void
    {
        $fund = new Fund([
            'symbol' => 'SPY',
            'name' => 'SPDR S&P 500 ETF',
            'type' => 'ETF',
            'fund_family' => 'State Street',
            'mer' => 0.09,
            'mer_tier' => 'RETAIL',
            'aum' => 400000000000.0
        ]);
        
        $holdings = [
            ['holding_symbol' => 'AAPL', 'weight' => 7.0, 'sector' => 'Technology'],
            ['holding_symbol' => 'MSFT', 'weight' => 6.5, 'sector' => 'Technology'],
            ['holding_symbol' => 'AMZN', 'weight' => 3.2, 'sector' => 'Consumer Discretionary']
        ];
        
        $this->mockFundDAO->method('getBySymbol')->willReturn($fund);
        $this->mockHoldingDAO->method('getHoldingsByFund')->willReturn($holdings);
        
        $result = $this->service->getFundComposition('SPY');
        
        $this->assertEquals('SPY', $result['fund']['symbol']);
        $this->assertEquals('SPDR S&P 500 ETF', $result['fund']['name']);
        $this->assertEquals(0.09, $result['fund']['mer']);
        $this->assertEquals(3, $result['holdings']['total_count']);
        $this->assertArrayHasKey('allocations', $result);
        $this->assertArrayHasKey('concentration', $result);
    }
    
    /**
     * Test fund overlap comparison
     */
    public function testCompareFundOverlapCalculatesSharedHoldings(): void
    {
        $holdings1 = [
            ['holding_symbol' => 'AAPL', 'weight' => 7.0],
            ['holding_symbol' => 'MSFT', 'weight' => 6.5],
            ['holding_symbol' => 'GOOGL', 'weight' => 3.0]
        ];
        
        $holdings2 = [
            ['holding_symbol' => 'AAPL', 'weight' => 8.0],
            ['holding_symbol' => 'MSFT', 'weight' => 7.0],
            ['holding_symbol' => 'NVDA', 'weight' => 5.0]
        ];
        
        $this->mockHoldingDAO->method('getHoldingsByFund')
            ->willReturnOnConsecutiveCalls($holdings1, $holdings2);
        
        $result = $this->service->compareFundOverlap('SPY', 'QQQ');
        
        $this->assertEquals('SPY', $result['fund1']);
        $this->assertEquals('QQQ', $result['fund2']);
        $this->assertEquals(2, $result['common_holdings']); // AAPL, MSFT
        $this->assertGreaterThan(0, $result['overlap_percent']);
        $this->assertArrayHasKey('weighted_overlap', $result);
        $this->assertContains('AAPL', $result['common_symbols']);
        $this->assertContains('MSFT', $result['common_symbols']);
    }
    
    /**
     * Test getting fund family variants (different MER tiers)
     */
    public function testGetFundFamilyVariantsReturnsSortedByMER(): void
    {
        $variants = [
            new Fund(['symbol' => 'FUND-I', 'mer' => 0.50, 'mer_tier' => 'INSTITUTIONAL']),
            new Fund(['symbol' => 'FUND-P', 'mer' => 1.50, 'mer_tier' => 'PREMIUM']),
            new Fund(['symbol' => 'FUND-R', 'mer' => 2.50, 'mer_tier' => 'RETAIL'])
        ];
        
        $this->mockFundDAO->method('getByBaseFund')->willReturn($variants);
        
        $result = $this->service->getFundFamilyVariants('BASE-FUND-123');
        
        $this->assertCount(3, $result);
        $this->assertEquals('FUND-I', $result[0]['symbol']);
        $this->assertEquals(0.50, $result[0]['mer']);
        $this->assertEquals('FUND-R', $result[2]['symbol']);
        $this->assertEquals(2.50, $result[2]['mer']);
    }
    
    /**
     * Test eligibility filtering for client
     */
    public function testFilterByEligibilityIdentifiesQualifiedFunds(): void
    {
        $fund1 = new Fund([
            'symbol' => 'FUND-R',
            'name' => 'Retail Fund',
            'mer' => 2.5,
            'mer_tier' => 'RETAIL',
            'minimum_net_worth' => 0,
            'allows_family_aggregation' => true
        ]);
        
        $fund2 = new Fund([
            'symbol' => 'FUND-P',
            'name' => 'Premium Fund',
            'mer' => 1.5,
            'mer_tier' => 'PREMIUM',
            'minimum_net_worth' => 500000,
            'allows_family_aggregation' => true
        ]);
        
        $fund3 = new Fund([
            'symbol' => 'FUND-I',
            'name' => 'Institutional Fund',
            'mer' => 0.5,
            'mer_tier' => 'INSTITUTIONAL',
            'minimum_net_worth' => 5000000,
            'allows_family_aggregation' => false
        ]);
        
        $this->mockFundDAO->method('getBySymbol')
            ->willReturnOnConsecutiveCalls($fund1, $fund2, $fund3);
        
        $result = $this->service->filterByEligibility(
            300000, // client net worth
            700000, // family net worth
            ['FUND-R', 'FUND-P', 'FUND-I']
        );
        
        $this->assertEquals(300000, $result['client_net_worth']);
        $this->assertEquals(700000, $result['family_net_worth']);
        $this->assertEquals(2, $result['total_eligible']); // Retail + Premium
        $this->assertEquals(1, $result['total_ineligible']); // Institutional
        
        // Check eligible funds
        $eligibleSymbols = array_column($result['eligible'], 'symbol');
        $this->assertContains('FUND-R', $eligibleSymbols);
        $this->assertContains('FUND-P', $eligibleSymbols);
        
        // Check ineligible funds
        $ineligibleSymbols = array_column($result['ineligible'], 'symbol');
        $this->assertContains('FUND-I', $ineligibleSymbols);
    }
    
    /**
     * Test eligibility with family aggregation
     */
    public function testFilterByEligibilityUsesFamilyAggregationWhenAllowed(): void
    {
        $fund = new Fund([
            'symbol' => 'FUND-P',
            'name' => 'Premium Fund',
            'mer' => 1.5,
            'mer_tier' => 'PREMIUM',
            'minimum_net_worth' => 600000,
            'allows_family_aggregation' => true
        ]);
        
        $this->mockFundDAO->method('getBySymbol')->willReturn($fund);
        
        $result = $this->service->filterByEligibility(
            400000, // client alone doesn't qualify
            800000, // family qualifies
            ['FUND-P']
        );
        
        $this->assertEquals(1, $result['total_eligible']);
        $this->assertEquals('family_net_worth', $result['eligible'][0]['qualified_by']);
    }
    
    /**
     * Test MER comparison across variants
     */
    public function testCompareMERsShowsFeeProjections(): void
    {
        $variants = [
            ['fund_code' => 'I', 'mer' => 0.5, 'mer_tier' => 'INSTITUTIONAL', 'minimum_investment' => 1000000],
            ['fund_code' => 'P', 'mer' => 1.5, 'mer_tier' => 'PREMIUM', 'minimum_investment' => 100000],
            ['fund_code' => 'R', 'mer' => 2.5, 'mer_tier' => 'RETAIL', 'minimum_investment' => 0]
        ];
        
        $this->mockFundDAO->method('getByBaseFund')->willReturn([]);
        
        // Mock getFundFamilyVariants
        $service = $this->getMockBuilder(FundCompositionService::class)
            ->setConstructorArgs([
                $this->mockFundDAO,
                $this->mockHoldingDAO,
                $this->mockEligibilityDAO,
                $this->mockMarketDataService
            ])
            ->onlyMethods(['getFundFamilyVariants'])
            ->getMock();
        
        $service->method('getFundFamilyVariants')->willReturn($variants);
        
        $result = $service->compareMERs('BASE-FUND', 100000);
        
        $this->assertEquals('BASE-FUND', $result['base_fund']);
        $this->assertEquals(100000, $result['investment_amount']);
        $this->assertCount(3, $result['variants']);
        
        // Check MER range
        $this->assertEquals(0.5, $result['mer_range']['lowest']);
        $this->assertEquals(2.5, $result['mer_range']['highest']);
        
        // Check potential savings exists
        $this->assertArrayHasKey('potential_savings', $result);
    }
    
    /**
     * Test fund performance analysis vs benchmark
     */
    public function testAnalyzeFundPerformanceComparesToBenchmark(): void
    {
        $fund = new Fund([
            'symbol' => 'VFINX',
            'name' => 'Vanguard 500 Index',
            'mer' => 0.14
        ]);
        
        // Fund prices (up 20%)
        $fundPrices = [
            ['date' => '2024-01-01', 'close' => 100.00],
            ['date' => '2024-12-01', 'close' => 120.00]
        ];
        
        // Benchmark prices (up 18%)
        $benchmarkPrices = [
            ['date' => '2024-01-01', 'close' => 4500.00],
            ['date' => '2024-12-01', 'close' => 5310.00]
        ];
        
        $this->mockFundDAO->method('getBySymbol')->willReturn($fund);
        $this->mockMarketDataService->method('getHistoricalPrices')
            ->willReturnOnConsecutiveCalls($fundPrices, $benchmarkPrices);
        
        $result = $this->service->analyzeFundPerformance('VFINX', 'SPY', '2024-01-01', '2024-12-01');
        
        $this->assertEquals('VFINX', $result['fund']['symbol']);
        $this->assertEquals(20.0, $result['fund']['gross_return']);
        $this->assertEquals(0.14, $result['fund']['mer']);
        $this->assertEquals(18.0, $result['benchmark']['return']);
        $this->assertArrayHasKey('alpha', $result['comparison']);
        $this->assertArrayHasKey('sharpe_ratio', $result['comparison']);
    }
    
    /**
     * Test high overlap interpretation
     */
    public function testCompareFundOverlapInterpretsHighOverlap(): void
    {
        $holdings1 = [
            ['holding_symbol' => 'AAPL', 'weight' => 7.0],
            ['holding_symbol' => 'MSFT', 'weight' => 6.5],
            ['holding_symbol' => 'GOOGL', 'weight' => 3.0],
            ['holding_symbol' => 'AMZN', 'weight' => 2.5]
        ];
        
        $holdings2 = [
            ['holding_symbol' => 'AAPL', 'weight' => 8.0],
            ['holding_symbol' => 'MSFT', 'weight' => 7.0],
            ['holding_symbol' => 'GOOGL', 'weight' => 4.0],
            ['holding_symbol' => 'AMZN', 'weight' => 3.0]
        ];
        
        $this->mockHoldingDAO->method('getHoldingsByFund')
            ->willReturnOnConsecutiveCalls($holdings1, $holdings2);
        
        $result = $this->service->compareFundOverlap('FUND1', 'FUND2');
        
        $this->assertGreaterThan(80, $result['overlap_percent']);
        $this->assertStringContainsString('High', $result['interpretation']);
    }
    
    /**
     * Test upgrade opportunity detection
     */
    public function testFilterByEligibilityIdentifiesUpgradeOpportunities(): void
    {
        $currentFund = new Fund([
            'symbol' => 'FUND-R',
            'name' => 'Retail Fund',
            'mer' => 2.5,
            'mer_tier' => 'RETAIL',
            'base_fund_id' => 'BASE-123',
            'minimum_net_worth' => 0
        ]);
        
        $upgradeFund = new Fund([
            'symbol' => 'FUND-P',
            'name' => 'Premium Fund',
            'mer' => 1.5,
            'mer_tier' => 'PREMIUM',
            'base_fund_id' => 'BASE-123',
            'minimum_net_worth' => 500000
        ]);
        
        $this->mockFundDAO->method('getBySymbol')->willReturn($currentFund);
        $this->mockFundDAO->method('getByBaseFund')->willReturn([$currentFund, $upgradeFund]);
        
        $result = $this->service->filterByEligibility(
            600000, // qualifies for premium
            600000,
            ['FUND-R']
        );
        
        $this->assertNotEmpty($result['upgrade_opportunities']);
        $this->assertEquals('FUND-P', $result['upgrade_opportunities'][0]['upgrade_to']);
        $this->assertEquals(1.0, $result['upgrade_opportunities'][0]['mer_savings']); // 2.5 - 1.5
    }
    
    /**
     * Test concentration metrics calculation
     */
    public function testGetFundCompositionCalculatesConcentration(): void
    {
        $fund = new Fund(['symbol' => 'TEST', 'name' => 'Test Fund', 'mer' => 1.0]);
        
        $holdings = [
            ['holding_symbol' => 'STOCK1', 'weight' => 15.0, 'sector' => 'Tech'],
            ['holding_symbol' => 'STOCK2', 'weight' => 12.0, 'sector' => 'Tech'],
            ['holding_symbol' => 'STOCK3', 'weight' => 10.0, 'sector' => 'Tech'],
            ['holding_symbol' => 'STOCK4', 'weight' => 8.0, 'sector' => 'Finance'],
            ['holding_symbol' => 'STOCK5', 'weight' => 6.0, 'sector' => 'Finance']
        ];
        
        $this->mockFundDAO->method('getBySymbol')->willReturn($fund);
        $this->mockHoldingDAO->method('getHoldingsByFund')->willReturn($holdings);
        
        $result = $this->service->getFundComposition('TEST');
        
        $this->assertArrayHasKey('concentration', $result);
        $this->assertArrayHasKey('top_10_concentration', $result['concentration']);
        $this->assertArrayHasKey('concentration_level', $result['concentration']);
        $this->assertGreaterThan(50, $result['concentration']['top_10_concentration']);
    }
    
    /**
     * Test sector allocation calculation
     */
    public function testGetFundCompositionCalculatesSectorAllocation(): void
    {
        $fund = new Fund(['symbol' => 'TEST', 'name' => 'Test Fund', 'mer' => 1.0]);
        
        $holdings = [
            ['holding_symbol' => 'AAPL', 'weight' => 20.0, 'sector' => 'Technology'],
            ['holding_symbol' => 'MSFT', 'weight' => 15.0, 'sector' => 'Technology'],
            ['holding_symbol' => 'JPM', 'weight' => 10.0, 'sector' => 'Financials'],
            ['holding_symbol' => 'JNJ', 'weight' => 8.0, 'sector' => 'Healthcare']
        ];
        
        $this->mockFundDAO->method('getBySymbol')->willReturn($fund);
        $this->mockHoldingDAO->method('getHoldingsByFund')->willReturn($holdings);
        
        $result = $this->service->getFundComposition('TEST');
        
        $this->assertArrayHasKey('sector', $result['allocations']);
        $this->assertEquals(35.0, $result['allocations']['sector']['Technology']); // 20 + 15
        $this->assertEquals(10.0, $result['allocations']['sector']['Financials']);
        $this->assertEquals(8.0, $result['allocations']['sector']['Healthcare']);
    }
}
