<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\IndexBenchmarkService;
use App\DAO\IndexDataDAO;

/**
 * Test-Driven Development Tests for Index Benchmarking
 * 
 * Tests define requirements for comparing portfolios/stocks against major indexes.
 * Written BEFORE implementation following TDD red-green-refactor cycle.
 * 
 * Design Principles:
 * - TDD: Tests drive implementation
 * - SRP: Service handles only benchmark comparisons
 * - SOLID: Interface-based dependencies
 * - DRY: Reusable test fixtures
 * 
 * Requirements Tested:
 * 1. Index data fetching (S&P 500, NASDAQ, Dow Jones)
 * 2. Performance comparison calculations
 * 3. Relative performance metrics (alpha, beta, correlation)
 * 4. Time period analysis (1M, 3M, 6M, 1Y, 3Y, 5Y)
 * 5. Chart data formatting
 * 6. Risk-adjusted returns (Sharpe, Sortino)
 * 
 * @package Tests\Services
 * @version 1.0.0
 */
class IndexBenchmarkServiceTest extends TestCase
{
    private IndexBenchmarkService $service;
    private IndexDataDAO $mockDAO;
    private array $samplePortfolioReturns;
    private array $sampleIndexReturns;
    
    /**
     * Set up test fixtures
     * 
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock DAO
        $this->mockDAO = $this->createMock(IndexDataDAO::class);
        
        // Initialize service with mock DAO
        $this->service = new IndexBenchmarkService($this->mockDAO);
        
        // Sample portfolio monthly returns (%)
        $this->samplePortfolioReturns = [
            '2025-01' => 5.2,
            '2025-02' => 3.1,
            '2025-03' => -2.4,
            '2025-04' => 4.8,
            '2025-05' => 2.9,
            '2025-06' => 1.5,
            '2025-07' => 6.3,
            '2025-08' => -1.2,
            '2025-09' => 3.7,
            '2025-10' => 5.1,
            '2025-11' => 2.8,
            '2025-12' => 4.2
        ];
        
        // Sample S&P 500 monthly returns (%)
        $this->sampleIndexReturns = [
            '2025-01' => 4.5,
            '2025-02' => 2.8,
            '2025-03' => -1.9,
            '2025-04' => 3.2,
            '2025-05' => 2.1,
            '2025-06' => 1.8,
            '2025-07' => 5.5,
            '2025-08' => -0.9,
            '2025-09' => 3.1,
            '2025-10' => 4.2,
            '2025-11' => 2.5,
            '2025-12' => 3.8
        ];
    }
    
    // ===== TEST: Service Creation =====
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testServiceCanBeInstantiated(): void
    {
        $service = new IndexBenchmarkService($this->mockDAO);
        $this->assertInstanceOf(IndexBenchmarkService::class, $service);
    }
    
    // ===== TEST: Index Data Fetching =====
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testFetchSP500Data(): void
    {
        // Arrange
        $this->mockDAO->method('getIndexData')
            ->willReturn([
                ['date' => '2025-01-01', 'close' => 4500.00],
                ['date' => '2025-01-02', 'close' => 4525.50]
            ]);
        
        // Act
        $data = $this->service->fetchIndexData('SPX', '1y');
        
        // Assert
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('date', $data[0]);
        $this->assertArrayHasKey('close', $data[0]);
    }
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testFetchMultipleIndexes(): void
    {
        // Arrange
        $indexes = ['SPX', 'IXIC', 'DJI']; // S&P 500, NASDAQ, Dow Jones
        
        // Act
        $results = $this->service->fetchMultipleIndexes($indexes, '1y');
        
        // Assert
        $this->assertIsArray($results);
        $this->assertCount(3, $results);
        $this->assertArrayHasKey('SPX', $results);
        $this->assertArrayHasKey('IXIC', $results);
        $this->assertArrayHasKey('DJI', $results);
    }
    
    // ===== TEST: Performance Calculations =====
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testCalculateTotalReturn(): void
    {
        // Arrange: 12 months of returns
        $returns = $this->samplePortfolioReturns;
        
        // Act
        $totalReturn = $this->service->calculateTotalReturn(array_values($returns));
        
        // Assert: Compound return should be ~40%
        $this->assertGreaterThan(35, $totalReturn);
        $this->assertLessThan(45, $totalReturn);
    }
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testCalculateAnnualizedReturn(): void
    {
        // Arrange: 12 months of returns
        $returns = array_values($this->samplePortfolioReturns);
        
        // Act
        $annualizedReturn = $this->service->calculateAnnualizedReturn($returns, 12);
        
        // Assert: Should match total return for 1 year
        $totalReturn = $this->service->calculateTotalReturn($returns);
        $this->assertEquals($totalReturn, $annualizedReturn, '', 0.5);
    }
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testCalculateRelativePerformance(): void
    {
        // Arrange
        $portfolioReturns = array_values($this->samplePortfolioReturns);
        $indexReturns = array_values($this->sampleIndexReturns);
        
        // Act
        $relative = $this->service->calculateRelativePerformance(
            $portfolioReturns,
            $indexReturns
        );
        
        // Assert: Portfolio should outperform index
        $this->assertIsArray($relative);
        $this->assertArrayHasKey('portfolio_return', $relative);
        $this->assertArrayHasKey('index_return', $relative);
        $this->assertArrayHasKey('excess_return', $relative);
        $this->assertArrayHasKey('outperformance_periods', $relative);
        
        $this->assertGreaterThan($relative['index_return'], $relative['portfolio_return']);
    }
    
    // ===== TEST: Alpha and Beta Calculations =====
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testCalculateBeta(): void
    {
        // Arrange
        $portfolioReturns = array_values($this->samplePortfolioReturns);
        $indexReturns = array_values($this->sampleIndexReturns);
        
        // Act
        $beta = $this->service->calculateBeta($portfolioReturns, $indexReturns);
        
        // Assert: Beta should be positive and close to 1
        $this->assertIsFloat($beta);
        $this->assertGreaterThan(0, $beta);
        $this->assertLessThan(2, $beta);
    }
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testCalculateAlpha(): void
    {
        // Arrange
        $portfolioReturn = 40.0; // Annual return
        $indexReturn = 33.0; // S&P 500 return
        $beta = 1.15;
        $riskFreeRate = 4.5; // Treasury rate
        
        // Act
        $alpha = $this->service->calculateAlpha(
            $portfolioReturn,
            $indexReturn,
            $beta,
            $riskFreeRate
        );
        
        // Assert: Alpha should be positive (outperformance)
        $this->assertIsFloat($alpha);
        $this->assertGreaterThan(0, $alpha);
    }
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testCalculateCorrelation(): void
    {
        // Arrange
        $portfolioReturns = array_values($this->samplePortfolioReturns);
        $indexReturns = array_values($this->sampleIndexReturns);
        
        // Act
        $correlation = $this->service->calculateCorrelation(
            $portfolioReturns,
            $indexReturns
        );
        
        // Assert: Correlation should be between -1 and 1
        $this->assertIsFloat($correlation);
        $this->assertGreaterThanOrEqual(-1, $correlation);
        $this->assertLessThanOrEqual(1, $correlation);
        
        // Assert: Should be positive (similar movements)
        $this->assertGreaterThan(0, $correlation);
    }
    
    // ===== TEST: Risk-Adjusted Returns =====
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testCalculateSharpeRatio(): void
    {
        // Arrange
        $returns = array_values($this->samplePortfolioReturns);
        $riskFreeRate = 4.5 / 12; // Monthly risk-free rate
        
        // Act
        $sharpe = $this->service->calculateSharpeRatio($returns, $riskFreeRate);
        
        // Assert: Sharpe ratio should be positive
        $this->assertIsFloat($sharpe);
        $this->assertGreaterThan(0, $sharpe);
        
        // Good Sharpe ratio is > 1
        $this->assertGreaterThan(1, $sharpe);
    }
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testCalculateSortinoRatio(): void
    {
        // Arrange
        $returns = array_values($this->samplePortfolioReturns);
        $targetReturn = 0; // 0% target
        
        // Act
        $sortino = $this->service->calculateSortinoRatio($returns, $targetReturn);
        
        // Assert: Sortino should be higher than Sharpe (only downside deviation)
        $sharpe = $this->service->calculateSharpeRatio($returns, 0);
        $this->assertGreaterThan($sharpe, $sortino);
    }
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testCalculateMaxDrawdown(): void
    {
        // Arrange: Cumulative returns with a drawdown
        $cumulativeValues = [100, 105, 110, 108, 103, 107, 115, 112, 120];
        
        // Act
        $maxDrawdown = $this->service->calculateMaxDrawdown($cumulativeValues);
        
        // Assert: Drawdown should be negative percentage
        $this->assertIsFloat($maxDrawdown);
        $this->assertLessThan(0, $maxDrawdown);
        
        // In this case: (103 - 110) / 110 = -6.36%
        $this->assertLessThan(-6, $maxDrawdown);
        $this->assertGreaterThan(-7, $maxDrawdown);
    }
    
    // ===== TEST: Time Period Analysis =====
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testCompareMultiplePeriods(): void
    {
        // Arrange
        $periods = ['1M', '3M', '6M', '1Y', '3Y', '5Y'];
        
        // Act
        $comparison = $this->service->compareAcrossPeriods(
            'AAPL',
            'SPX',
            $periods
        );
        
        // Assert: Returns comparison for each period
        $this->assertIsArray($comparison);
        $this->assertCount(6, $comparison);
        
        foreach ($periods as $period) {
            $this->assertArrayHasKey($period, $comparison);
            $this->assertArrayHasKey('portfolio_return', $comparison[$period]);
            $this->assertArrayHasKey('index_return', $comparison[$period]);
            $this->assertArrayHasKey('excess_return', $comparison[$period]);
        }
    }
    
    // ===== TEST: Chart Data Formatting =====
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testFormatForPerformanceChart(): void
    {
        // Arrange
        $portfolioData = [
            ['date' => '2025-01', 'value' => 100],
            ['date' => '2025-02', 'value' => 105],
            ['date' => '2025-03', 'value' => 103]
        ];
        
        $indexData = [
            ['date' => '2025-01', 'value' => 100],
            ['date' => '2025-02', 'value' => 103],
            ['date' => '2025-03', 'value' => 102]
        ];
        
        // Act
        $chartData = $this->service->formatForPerformanceChart(
            $portfolioData,
            $indexData,
            'Portfolio vs S&P 500'
        );
        
        // Assert: Chart.js line chart format
        $this->assertIsArray($chartData);
        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('datasets', $chartData);
        
        $this->assertCount(2, $chartData['datasets']);
        $this->assertEquals('Portfolio', $chartData['datasets'][0]['label']);
        $this->assertEquals('S&P 500', $chartData['datasets'][1]['label']);
    }
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testFormatForComparisonTable(): void
    {
        // Arrange
        $metrics = [
            'total_return' => 40.0,
            'annualized_return' => 40.0,
            'volatility' => 12.5,
            'sharpe_ratio' => 2.1,
            'max_drawdown' => -8.3,
            'alpha' => 3.5,
            'beta' => 1.15
        ];
        
        $indexMetrics = [
            'total_return' => 33.0,
            'annualized_return' => 33.0,
            'volatility' => 10.2,
            'sharpe_ratio' => 2.0,
            'max_drawdown' => -6.5,
            'alpha' => 0.0,
            'beta' => 1.0
        ];
        
        // Act
        $table = $this->service->formatForComparisonTable($metrics, $indexMetrics);
        
        // Assert: HTML table or array format
        $this->assertIsArray($table);
        $this->assertArrayHasKey('headers', $table);
        $this->assertArrayHasKey('rows', $table);
        
        $this->assertCount(7, $table['rows']); // 7 metrics
    }
    
    // ===== TEST: Data Validation =====
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testValidateDataAlignment(): void
    {
        // Arrange: Matching dates
        $portfolioData = [
            ['date' => '2025-01', 'return' => 5.2],
            ['date' => '2025-02', 'return' => 3.1]
        ];
        
        $indexData = [
            ['date' => '2025-01', 'return' => 4.5],
            ['date' => '2025-02', 'return' => 2.8]
        ];
        
        // Act
        $aligned = $this->service->alignDataByDate($portfolioData, $indexData);
        
        // Assert: Returns aligned arrays
        $this->assertIsArray($aligned);
        $this->assertArrayHasKey('portfolio', $aligned);
        $this->assertArrayHasKey('index', $aligned);
        $this->assertCount(2, $aligned['portfolio']);
        $this->assertCount(2, $aligned['index']);
    }
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testHandleMissingDataPoints(): void
    {
        // Arrange: Portfolio has gaps
        $portfolioData = [
            ['date' => '2025-01', 'return' => 5.2],
            // Missing 2025-02
            ['date' => '2025-03', 'return' => -2.4]
        ];
        
        $indexData = [
            ['date' => '2025-01', 'return' => 4.5],
            ['date' => '2025-02', 'return' => 2.8],
            ['date' => '2025-03', 'return' => -1.9]
        ];
        
        // Act
        $aligned = $this->service->alignDataByDate($portfolioData, $indexData);
        
        // Assert: Should handle gap (interpolate or skip)
        $this->assertIsArray($aligned);
        // Either both have 2 items (skip) or 3 items (interpolate)
        $this->assertEquals(
            count($aligned['portfolio']),
            count($aligned['index'])
        );
    }
    
    // ===== TEST: Error Handling =====
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testHandleInvalidIndexSymbol(): void
    {
        // Arrange: Invalid symbol
        $invalidSymbol = 'INVALID_INDEX';
        
        // Configure mock DAO to throw exception
        $this->mockDAO->expects($this->once())
            ->method('getIndexData')
            ->with($invalidSymbol, '1y')
            ->willThrowException(new \InvalidArgumentException('Invalid index symbol'));
        
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->service->fetchIndexData($invalidSymbol, '1y');
    }
    
    /**
     * @test
     * @group unit
     * @group index-benchmark
     */
    public function testHandleInsufficientData(): void
    {
        // Arrange: Only 1 data point (can't calculate correlation)
        $portfolioReturns = [5.2];
        $indexReturns = [4.5];
        
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->service->calculateCorrelation($portfolioReturns, $indexReturns);
    }
}
