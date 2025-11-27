<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\PortfolioService;
use App\Repositories\Interfaces\PortfolioRepositoryInterface;
use App\Services\Interfaces\MarketDataServiceInterface;
use App\DataAccess\Interfaces\PortfolioDataSourceInterface;

/**
 * Comprehensive tests for PortfolioService
 * Tests all public methods, DAO integration, and portfolio calculations
 */
class PortfolioServiceTest extends TestCase
{
    private $portfolioRepository;
    private $marketDataService;
    private $userPortfolioDataSource;
    private $microCapDataSource;
    private PortfolioService $service;
    
    protected function setUp(): void
    {
        // Create mocks for dependencies (DI pattern)
        $this->portfolioRepository = $this->createMock(PortfolioRepositoryInterface::class);
        $this->marketDataService = $this->createMock(MarketDataServiceInterface::class);
        $this->userPortfolioDataSource = $this->createMock(PortfolioDataSourceInterface::class);
        $this->microCapDataSource = $this->createMock(PortfolioDataSourceInterface::class);
        
        // Create service with all mocked dependencies
        $this->service = new PortfolioService(
            $this->portfolioRepository,
            $this->marketDataService,
            $this->userPortfolioDataSource,
            $this->microCapDataSource
        );
    }
    
    // ===== getDashboardData() TESTS =====
    
    public function testGetDashboardDataSuccess(): void
    {
        $userId = 1;
        
        // Arrange: Mock micro-cap data source - isAvailable called twice (once in getActualPortfolioData, once in getActualHoldings)
        $this->microCapDataSource
            ->expects($this->exactly(2))
            ->method('isAvailable')
            ->willReturn(true);
        
        $this->microCapDataSource
            ->expects($this->exactly(2))
            ->method('readPortfolio')
            ->willReturn([
                [
                    'Ticker' => 'AAPL',
                    'Company' => 'Apple Inc',
                    'Shares' => 100,
                    'Buy Price' => 120.00,
                    'Current Price' => 150.00,
                    'Market Value' => '15000.00',
                    'P&L' => '3000.00'
                ]
            ]);
        
        // Arrange: Mock market data
        $mockPrices = [
            'AAPL' => ['price' => 150.00, 'change' => 2.50, 'change_percent' => 1.69]
        ];
        
        $this->marketDataService
            ->expects($this->once())
            ->method('getCurrentPrices')
            ->with(['AAPL'])
            ->willReturn($mockPrices);
        
        // Arrange: Mock market summary
        $this->marketDataService
            ->expects($this->once())
            ->method('getMarketSummary')
            ->willReturn([]);
        
        // Act
        $result = $this->service->getDashboardData($userId);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertEquals($userId, $result['user_id']);
        $this->assertArrayHasKey('holdings', $result);
        $this->assertArrayHasKey('marketData', $result);
    }
    
    public function testGetDashboardDataEmptyPortfolio(): void
    {
        $userId = 1;
        
        // Arrange: Both data sources return empty data
        $this->microCapDataSource
            ->method('isAvailable')
            ->willReturn(true);
        
        $this->microCapDataSource
            ->method('readPortfolio')
            ->willReturn([]); // Empty portfolio
        
        $this->marketDataService
            ->method('getMarketSummary')
            ->willReturn([]);
        
        // Act
        $result = $this->service->getDashboardData($userId);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result['holdings']);
        $this->assertEquals(0, $result['total_value']);
    }
    
    public function testGetDashboardDataMarketDataUnavailable(): void
    {
        $userId = 1;
        
        // Arrange: Data source returns holdings
        $this->microCapDataSource
            ->method('isAvailable')
            ->willReturn(true);
        
        $this->microCapDataSource
            ->method('readPortfolio')
            ->willReturn([
                ['Ticker' => 'AAPL', 'Shares' => 100, 'Buy Price' => 120.00, 'Current Price' => 150.00]
            ]);
        
        // Arrange: Market data service returns empty (unavailable)
        $this->marketDataService
            ->expects($this->once())
            ->method('getCurrentPrices')
            ->willReturn([]);
        
        $this->marketDataService
            ->method('getMarketSummary')
            ->willReturn([]);
        
        // Act
        $result = $this->service->getDashboardData($userId);
        
        // Assert: Should still return data
        $this->assertIsArray($result);
        $this->assertArrayHasKey('holdings', $result);
    }
    
    public function testGetDashboardDataRepositoryThrowsException(): void
    {
        $userId = 1;
        
        // Arrange: Data source throws exception
        $this->microCapDataSource
            ->method('isAvailable')
            ->willReturn(true);
        
        $this->microCapDataSource
            ->method('readPortfolio')
            ->willThrowException(new \Exception('Data source error'));
        
        $this->marketDataService
            ->method('getMarketSummary')
            ->willReturn([]);
        
        // Act
        $result = $this->service->getDashboardData($userId);
        
        // Assert: Should return default values (error is caught)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }
    
    // ===== calculatePerformance() TESTS =====
    
    public function testCalculatePerformanceWithGains(): void
    {
        $userId = 1;
        
        // Arrange: Mock performance history showing gains
        $this->portfolioRepository
            ->expects($this->once())
            ->method('getPerformanceHistory')
            ->with($userId)
            ->willReturn([
                ['value' => 10000, 'date' => '2024-01-01'],
                ['value' => 12000, 'date' => '2024-06-01'],
                ['value' => 15000, 'date' => '2024-12-01']
            ]);
        
        // Act
        $result = $this->service->calculatePerformance($userId);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_return', $result);
        $this->assertArrayHasKey('total_return_percent', $result);
        $this->assertArrayHasKey('daily_return', $result);
        $this->assertArrayHasKey('current_value', $result);
        
        $this->assertEquals(5000, $result['total_return']); // 15000 - 10000
        $this->assertEquals(50.0, $result['total_return_percent']); // (5000/10000) * 100
    }
    
    public function testCalculatePerformanceWithLosses(): void
    {
        $userId = 1;
        
        // Arrange: Mock performance history showing losses
        $this->portfolioRepository
            ->expects($this->once())
            ->method('getPerformanceHistory')
            ->with($userId)
            ->willReturn([
                ['value' => 15000, 'date' => '2024-01-01'],
                ['value' => 12000, 'date' => '2024-12-01']
            ]);
        
        // Act
        $result = $this->service->calculatePerformance($userId);
        
        // Assert
        $this->assertEquals(-3000, $result['total_return']);
        $this->assertEquals(12000, $result['current_value']);
        $this->assertEquals(-20.0, $result['total_return_percent']);
    }
    
    public function testCalculatePerformanceEmptyHoldings(): void
    {
        $userId = 1;
        
        // Arrange: Empty performance history
        $this->portfolioRepository
            ->expects($this->once())
            ->method('getPerformanceHistory')
            ->with($userId)
            ->willReturn([]);
        
        // Act
        $result = $this->service->calculatePerformance($userId);
        
        // Assert
        $this->assertEquals(0, $result['total_return']);
        $this->assertEquals(0, $result['total_return_percent']);
        $this->assertEquals(0, $result['daily_return']);
    }
    
    public function testCalculatePerformanceNullHoldings(): void
    {
        // Act
        $result = $this->service->calculatePerformance(null);
        
        // Assert
        $this->assertEquals(0, $result['total_cost']);
        $this->assertEquals(0, $result['current_value']);
        $this->assertEquals(0, $result['total_gain']);
        $this->assertEquals(0, $result['total_gain_percent']);
    }
    
    // ===== getCurrentValue() TESTS =====
    
    public function testGetCurrentValue(): void
    {
        // Arrange
        $mockPortfolio = [
            'holdings' => [
                ['symbol' => 'AAPL', 'shares' => 100],
                ['symbol' => 'GOOGL', 'shares' => 50]
            ],
            'cash' => 5000.00
        ];
        
        $mockPrices = [
            'AAPL' => ['price' => 150.00],
            'GOOGL' => ['price' => 140.00]
        ];
        
        $this->portfolioRepository
            ->method('getPortfolio')
            ->willReturn($mockPortfolio);
        
        $this->marketDataService
            ->method('getCurrentPrices')
            ->willReturn($mockPrices);
        
        // Act
        $result = $this->service->getCurrentValue();
        
        // Assert
        // (100 * 150) + (50 * 140) + 5000 = 15000 + 7000 + 5000 = 27000
        $this->assertEquals(27000.00, $result);
    }
    
    public function testGetCurrentValueWithZeroPrices(): void
    {
        // Arrange
        $mockPortfolio = [
            'holdings' => [
                ['symbol' => 'AAPL', 'shares' => 100]
            ],
            'cash' => 5000.00
        ];
        
        $mockPrices = [
            'AAPL' => ['price' => 0.00] // Zero price (market closed or error)
        ];
        
        $this->portfolioRepository
            ->method('getPortfolio')
            ->willReturn($mockPortfolio);
        
        $this->marketDataService
            ->method('getCurrentPrices')
            ->willReturn($mockPrices);
        
        // Act
        $result = $this->service->getCurrentValue();
        
        // Assert: Should still include cash
        $this->assertEquals(5000.00, $result);
    }
    
    // ===== getPositions() TESTS =====
    
    public function testGetPositions(): void
    {
        // Arrange
        $mockPositions = [
            [
                'symbol' => 'AAPL',
                'shares' => 100,
                'cost_basis' => 12000.00,
                'avg_cost' => 120.00
            ],
            [
                'symbol' => 'GOOGL',
                'shares' => 50,
                'cost_basis' => 7500.00,
                'avg_cost' => 150.00
            ]
        ];
        
        $this->portfolioRepository
            ->expects($this->once())
            ->method('getPositions')
            ->willReturn($mockPositions);
        
        // Act
        $result = $this->service->getPositions();
        
        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('AAPL', $result[0]['symbol']);
        $this->assertEquals(100, $result[0]['shares']);
    }
    
    public function testGetPositionsEmpty(): void
    {
        // Arrange
        $this->portfolioRepository
            ->expects($this->once())
            ->method('getPositions')
            ->willReturn([]);
        
        // Act
        $result = $this->service->getPositions();
        
        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    // ===== addTransaction() TESTS =====
    
    public function testAddTransactionBuy(): void
    {
        // Arrange
        $transaction = [
            'type' => 'BUY',
            'symbol' => 'AAPL',
            'shares' => 10,
            'price' => 150.00,
            'date' => '2025-01-15'
        ];
        
        $this->portfolioRepository
            ->expects($this->once())
            ->method('addTransaction')
            ->with($transaction)
            ->willReturn(true);
        
        // Act
        $result = $this->service->addTransaction($transaction);
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function testAddTransactionSell(): void
    {
        // Arrange
        $transaction = [
            'type' => 'SELL',
            'symbol' => 'AAPL',
            'shares' => 5,
            'price' => 160.00,
            'date' => '2025-01-16'
        ];
        
        $this->portfolioRepository
            ->expects($this->once())
            ->method('addTransaction')
            ->with($transaction)
            ->willReturn(true);
        
        // Act
        $result = $this->service->addTransaction($transaction);
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function testAddTransactionInvalidType(): void
    {
        // Arrange: Invalid transaction type
        $transaction = [
            'type' => 'INVALID',
            'symbol' => 'AAPL',
            'shares' => 10,
            'price' => 150.00
        ];
        
        // Act
        $result = $this->service->addTransaction($transaction);
        
        // Assert: Should fail validation
        $this->assertFalse($result);
    }
    
    public function testAddTransactionMissingRequiredFields(): void
    {
        // Arrange: Missing 'shares' field
        $transaction = [
            'type' => 'BUY',
            'symbol' => 'AAPL',
            'price' => 150.00
        ];
        
        // Act
        $result = $this->service->addTransaction($transaction);
        
        // Assert
        $this->assertFalse($result);
    }
    
    public function testAddTransactionNegativeShares(): void
    {
        // Arrange
        $transaction = [
            'type' => 'BUY',
            'symbol' => 'AAPL',
            'shares' => -10, // Negative!
            'price' => 150.00
        ];
        
        // Act
        $result = $this->service->addTransaction($transaction);
        
        // Assert
        $this->assertFalse($result);
    }
    
    public function testAddTransactionZeroPrice(): void
    {
        // Arrange
        $transaction = [
            'type' => 'BUY',
            'symbol' => 'AAPL',
            'shares' => 10,
            'price' => 0.00 // Zero price
        ];
        
        // Act
        $result = $this->service->addTransaction($transaction);
        
        // Assert
        $this->assertFalse($result);
    }
    
    // ===== DAO INTEGRATION TESTS =====
    
    public function testUserPortfolioDAOIntegration(): void
    {
        $this->markTestIncomplete(
            'PortfolioService has DI violations - directly instantiates UserPortfolioDAO. ' .
            'Refactor to inject DAO adapters through constructor.'
        );
    }
    
    public function testMicroCapDAOIntegration(): void
    {
        $this->markTestIncomplete(
            'PortfolioService has DI violations - directly instantiates MicroCapPortfolioDAO. ' .
            'Refactor to inject DAO adapters through constructor.'
        );
    }
    
    // ===== PRIVATE METHOD TESTS (via public methods) =====
    
    public function testGetActualPortfolioDataFallback(): void
    {
        // This tests the private getActualPortfolioData() method
        // which falls back between UserPortfolioDAO and MicroCapDAO
        
        $this->markTestIncomplete(
            'Cannot test private method getActualPortfolioData() without refactoring. ' .
            'Should be extracted to separate service or made testable.'
        );
    }
    
    public function testFormatHoldings(): void
    {
        // This tests the private formatHoldings() method
        
        $this->markTestIncomplete(
            'Cannot test private method formatHoldings() directly. ' .
            'Consider making it protected or extracting to separate formatter class.'
        );
    }
    
    // ===== EDGE CASES =====
    
    public function testCalculatePerformanceWithVeryLargeNumbers(): void
    {
        // Arrange: Institutional-sized portfolio
        $holdings = [
            [
                'symbol' => 'AAPL',
                'shares' => 10000000, // 10 million shares
                'cost_basis' => 1200000000.00, // $1.2 billion
                'current_price' => 150.00
            ]
        ];
        
        // Act
        $result = $this->service->calculatePerformance($holdings);
        
        // Assert: Should handle large numbers correctly
        $this->assertEquals(1200000000.00, $result['total_cost']);
        $this->assertEquals(1500000000.00, $result['current_value']);
        $this->assertEquals(300000000.00, $result['total_gain']);
    }
    
    public function testCalculatePerformanceWithFractionalShares(): void
    {
        // Arrange: Fractional shares (common with modern brokers)
        $holdings = [
            [
                'symbol' => 'AAPL',
                'shares' => 10.5, // Fractional
                'cost_basis' => 1260.00,
                'current_price' => 150.00
            ]
        ];
        
        // Act
        $result = $this->service->calculatePerformance($holdings);
        
        // Assert
        $this->assertEquals(1260.00, $result['total_cost']);
        $this->assertEquals(1575.00, $result['current_value']); // 10.5 * 150
    }
    
    public function testGetDashboardDataWithMixedPerformance(): void
    {
        // Arrange: Some positions up, some down
        $mockPortfolio = [
            'holdings' => [
                ['symbol' => 'AAPL', 'shares' => 100, 'cost_basis' => 10000.00], // Will gain
                ['symbol' => 'GOOGL', 'shares' => 50, 'cost_basis' => 10000.00]  // Will lose
            ],
            'cash' => 5000.00
        ];
        
        $mockPrices = [
            'AAPL' => ['price' => 150.00],  // Up from avg $100
            'GOOGL' => ['price' => 150.00]  // Down from avg $200
        ];
        
        $this->portfolioRepository
            ->method('getPortfolio')
            ->willReturn($mockPortfolio);
        
        $this->marketDataService
            ->method('getCurrentPrices')
            ->willReturn($mockPrices);
        
        // Act
        $result = $this->service->getDashboardData();
        
        // Assert: Should show overall performance
        $this->assertIsArray($result);
        $this->assertArrayHasKey('performance', $result);
    }
    
    // ===== INTEGRATION TESTS =====
    
    public function testCompletePortfolioWorkflow(): void
    {
        // This test verifies a complete workflow:
        // 1. Add buy transaction
        // 2. Get dashboard data
        // 3. Calculate performance
        // 4. Add sell transaction
        // 5. Get updated data
        
        $this->markTestSkipped('Integration test - requires full service stack');
    }
    
    // ===== DOCUMENTATION TESTS =====
    
    public function testPortfolioServiceInterface(): void
    {
        // Assert: Service should implement interface
        $this->assertInstanceOf(
            \App\Services\Interfaces\PortfolioServiceInterface::class,
            $this->service
        );
    }
    
    public function testDependencyInjectionViolations(): void
    {
        // DOCUMENTATION TEST
        $this->markTestIncomplete(
            'PortfolioService has multiple DI violations:\n' .
            '1. Hard-coded require_once for UserPortfolioDAO\n' .
            '2. Hard-coded require_once for PortfolioDAO\n' .
            '3. Hard-coded require_once for MicroCapPortfolioDAO\n' .
            '4. Direct instantiation in constructor\n' .
            '5. Hardcoded CSV file paths\n\n' .
            'Recommended refactoring:\n' .
            '- Create PortfolioDataSource interface\n' .
            '- Create adapters for each DAO\n' .
            '- Inject via constructor\n' .
            '- Move file paths to configuration'
        );
    }
}
