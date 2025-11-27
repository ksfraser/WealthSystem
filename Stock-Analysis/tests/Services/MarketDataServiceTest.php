<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\MarketDataService;
use App\DataAccess\Interfaces\StockDataAccessInterface;

/**
 * Comprehensive tests for MarketDataService
 * Tests all public methods, error handling, and data source integration
 */
class MarketDataServiceTest extends TestCase
{
    private MarketDataService $service;
    private $mockStockDataAccess;
    
    protected function setUp(): void
    {
        // Create mock for StockDataAccessInterface (DI pattern)
        $this->mockStockDataAccess = $this->createMock(StockDataAccessInterface::class);
        
        // Create service with injected mock
        $this->service = new MarketDataService($this->mockStockDataAccess);
    }
    
    // ===== getCurrentPrices() TESTS =====
    
    public function testGetCurrentPricesSingleSymbol(): void
    {
        // Arrange: Mock data access to return price data
        $mockPriceData = [
            'symbol' => 'AAPL',
            'close' => 150.00,
            'open' => 148.00,
            'high' => 152.00,
            'low' => 147.00,
            'volume' => 50000000,
            'date' => '2025-01-15'
        ];
        
        $this->mockStockDataAccess
            ->expects($this->once())
            ->method('getLatestPrice')
            ->with('AAPL')
            ->willReturn($mockPriceData);
        
        // Act
        $result = $this->service->getCurrentPrices(['AAPL']);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('AAPL', $result);
        $this->assertNotNull($result['AAPL']);
        $this->assertEquals(150.00, $result['AAPL']['price']);
    }
    
    public function testGetCurrentPricesMultipleSymbols(): void
    {
        // Arrange: Mock data for multiple symbols
        $mockDataMap = [
            ['AAPL', ['symbol' => 'AAPL', 'close' => 150.00, 'date' => '2025-01-15']],
            ['GOOGL', ['symbol' => 'GOOGL', 'close' => 140.00, 'date' => '2025-01-15']],
            ['MSFT', ['symbol' => 'MSFT', 'close' => 380.00, 'date' => '2025-01-15']]
        ];
        
        $this->mockStockDataAccess
            ->expects($this->exactly(3))
            ->method('getLatestPrice')
            ->willReturnMap($mockDataMap);
        
        // Act
        $result = $this->service->getCurrentPrices(['AAPL', 'GOOGL', 'MSFT']);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('AAPL', $result);
        $this->assertArrayHasKey('GOOGL', $result);
        $this->assertArrayHasKey('MSFT', $result);
    }
    
    public function testGetCurrentPricesEmptyArray(): void
    {
        // No mock expectations needed - empty array means no calls to data access
        
        // Act
        $result = $this->service->getCurrentPrices([]);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    public function testGetCurrentPricesInvalidSymbol(): void
    {
        // Arrange: Mock returns null for invalid symbol
        $this->mockStockDataAccess
            ->expects($this->once())
            ->method('getLatestPrice')
            ->with('INVALID123')
            ->willReturn(null);
        
        // Act
        $result = $this->service->getCurrentPrices(['INVALID123']);
        
        // Assert: Should handle gracefully - invalid symbols are not included in result
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    // ===== getCurrentPrice() TESTS =====
    
    public function testGetCurrentPriceValidSymbol(): void
    {
        // Arrange: Mock price data
        $mockPriceData = [
            'symbol' => 'AAPL',
            'close' => 150.00,
            'date' => '2025-01-15',
            'open' => 148.00,
            'high' => 152.00,
            'low' => 147.00,
            'volume' => 50000000
        ];
        
        $this->mockStockDataAccess
            ->expects($this->once())
            ->method('getLatestPrice')
            ->with('AAPL')
            ->willReturn($mockPriceData);
        
        // Act
        $result = $this->service->getCurrentPrice('AAPL');
        
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('symbol', $result);
        $this->assertArrayHasKey('price', $result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('change', $result);
        $this->assertArrayHasKey('change_percent', $result);
    }
    
    public function testGetCurrentPriceNullForInvalidSymbol(): void
    {
        // Arrange: Mock returns null for invalid symbol
        $this->mockStockDataAccess
            ->expects($this->once())
            ->method('getLatestPrice')
            ->with('INVALID')
            ->willReturn(null);
        
        // Act
        $result = $this->service->getCurrentPrice('INVALID');
        
        // Assert
        $this->assertNull($result);
    }
    
    public function testGetCurrentPriceEmptySymbol(): void
    {
        // Act
        $result = $this->service->getCurrentPrice('');
        
        // Assert
        $this->assertNull($result);
    }
    
    // ===== getHistoricalPrices() TESTS =====
    
    public function testGetHistoricalPricesWithDateRange(): void
    {
        // Arrange: Mock historical price data
        $mockHistoricalData = [
            ['date' => '2024-01-02', 'open' => 148.00, 'high' => 152.00, 'low' => 147.00, 'close' => 150.00, 'volume' => 50000000],
            ['date' => '2024-01-03', 'open' => 150.00, 'high' => 153.00, 'low' => 149.00, 'close' => 152.00, 'volume' => 48000000],
            ['date' => '2024-01-04', 'open' => 152.00, 'high' => 155.00, 'low' => 151.00, 'close' => 154.00, 'volume' => 52000000]
        ];
        
        $this->mockStockDataAccess
            ->expects($this->once())
            ->method('getPriceData')
            ->with('AAPL', '2024-01-01', '2024-12-31', null)
            ->willReturn($mockHistoricalData);
        
        // Act
        $result = $this->service->getHistoricalPrices('AAPL', '2024-01-01', '2024-12-31');
        
        // Assert
        $this->assertIsArray($result);
        $this->assertGreaterThan(0, count($result));
        
        // Check structure of first element
        $firstPrice = $result[0];
        $this->assertArrayHasKey('date', $firstPrice);
        $this->assertArrayHasKey('open', $firstPrice);
        $this->assertArrayHasKey('high', $firstPrice);
        $this->assertArrayHasKey('low', $firstPrice);
        $this->assertArrayHasKey('close', $firstPrice);
        $this->assertArrayHasKey('volume', $firstPrice);
    }
    
    public function testGetHistoricalPricesWithoutDateRange(): void
    {
        // Arrange: Mock returns recent data when no dates specified
        $mockRecentData = [
            ['date' => '2025-01-13', 'open' => 148.00, 'high' => 152.00, 'low' => 147.00, 'close' => 150.00, 'volume' => 50000000],
            ['date' => '2025-01-14', 'open' => 150.00, 'high' => 153.00, 'low' => 149.00, 'close' => 152.00, 'volume' => 48000000]
        ];
        
        $this->mockStockDataAccess
            ->expects($this->once())
            ->method('getPriceData')
            ->with('AAPL', null, null, null)
            ->willReturn($mockRecentData);
        
        // Act: No dates = default to recent history
        $result = $this->service->getHistoricalPrices('AAPL');
        
        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }
    
    public function testGetHistoricalPricesInvalidDateRange(): void
    {
        // Arrange: End date before start date - mock returns empty array
        $this->mockStockDataAccess
            ->expects($this->once())
            ->method('getPriceData')
            ->with('AAPL', '2024-12-31', '2024-01-01', null)
            ->willReturn([]);
        
        // Act
        $result = $this->service->getHistoricalPrices('AAPL', '2024-12-31', '2024-01-01');
        
        // Assert: Should return empty
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    public function testGetHistoricalPricesInvalidDateFormat(): void
    {
        // Arrange: Invalid date format - mock returns empty array
        $this->mockStockDataAccess
            ->expects($this->once())
            ->method('getPriceData')
            ->with('AAPL', 'invalid-date', '2024-12-31', null)
            ->willReturn([]);
        
        // Act
        $result = $this->service->getHistoricalPrices('AAPL', 'invalid-date', '2024-12-31');
        
        // Assert: Should handle gracefully
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    // ===== getMarketSummary() TESTS =====
    
    public function testGetMarketSummary(): void
    {
        // Arrange: Mock data for major indices
        $mockIndicesData = [
            ['^GSPC', ['symbol' => '^GSPC', 'close' => 4500.00, 'open' => 4480.00, 'date' => '2025-01-15']],
            ['^DJI', ['symbol' => '^DJI', 'close' => 35000.00, 'open' => 34900.00, 'date' => '2025-01-15']],
            ['^IXIC', ['symbol' => '^IXIC', 'close' => 14000.00, 'open' => 13950.00, 'date' => '2025-01-15']]
        ];
        
        $this->mockStockDataAccess
            ->expects($this->exactly(3))
            ->method('getLatestPrice')
            ->willReturnMap($mockIndicesData);
        
        // Act
        $result = $this->service->getMarketSummary();
        
        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        
        // Verify each index is present
        $symbols = array_column($result, 'symbol');
        $this->assertContains('^GSPC', $symbols); // S&P 500
        $this->assertContains('^DJI', $symbols);  // Dow Jones
        $this->assertContains('^IXIC', $symbols); // NASDAQ
    }
    
    public function testGetMarketSummaryStructure(): void
    {
        // Arrange: Mock returns complete data
        $mockData = [
            'symbol' => '^GSPC',
            'close' => 4500.00,
            'open' => 4480.00,
            'date' => '2025-01-15'
        ];
        
        $this->mockStockDataAccess
            ->method('getLatestPrice')
            ->willReturn($mockData);
        
        // Act
        $result = $this->service->getMarketSummary();
        
        // Assert: Check structure of each index
        foreach ($result as $data) {
            $this->assertArrayHasKey('symbol', $data);
            $this->assertArrayHasKey('name', $data);
            $this->assertArrayHasKey('value', $data);
            $this->assertArrayHasKey('change', $data);
            $this->assertArrayHasKey('change_percent', $data);
        }
    }
    
    // ===== PRIVATE METHOD TESTS (via public methods) =====
    
    public function testCalculateDayChange(): void
    {
        // Arrange: Mock with open and close prices to test change calculation
        $mockData = [
            'symbol' => '^GSPC',
            'close' => 4500.00,
            'open' => 4480.00,
            'date' => '2025-01-15'
        ];
        
        $this->mockStockDataAccess
            ->method('getLatestPrice')
            ->willReturn($mockData);
        
        // Act
        $result = $this->service->getMarketSummary();
        
        // Assert: Changes should be calculated
        foreach ($result as $data) {
            $this->assertIsNumeric($data['change']);
        }
    }
    
    public function testCalculateDayChangePercent(): void
    {
        // Arrange: Mock with open and close to test percentage calculation
        $mockData = [
            'symbol' => '^GSPC',
            'close' => 4500.00,
            'open' => 4480.00,
            'date' => '2025-01-15'
        ];
        
        $this->mockStockDataAccess
            ->method('getLatestPrice')
            ->willReturn($mockData);
        
        // Act
        $result = $this->service->getMarketSummary();
        
        // Assert: Change percentages should be calculated
        foreach ($result as $data) {
            $this->assertIsNumeric($data['change_percent']);
            // Percent should be reasonable (e.g., -20% to +20% in a day)
            $this->assertGreaterThanOrEqual(-20, $data['change_percent']);
            $this->assertLessThanOrEqual(20, $data['change_percent']);
        }
    }
    
    // ===== ERROR HANDLING TESTS =====
    
    public function testStockDataAccessInitializationFailure(): void
    {
        // This test highlights the DI violation
        // If DynamicStockDataAccess fails to initialize, the service
        // catches the exception and continues with null stockDataAccess
        
        // Currently, we can't easily test this without refactoring
        // This is a perfect example of why DI is important!
        
        $this->markTestIncomplete('Cannot test initialization failure due to DI violation');
    }
    
    public function testNetworkTimeout(): void
    {
        // Arrange: Mock throws exception to simulate timeout
        $this->mockStockDataAccess
            ->expects($this->once())
            ->method('getLatestPrice')
            ->with('AAPL')
            ->willThrowException(new \Exception('Network timeout'));
        
        // Act
        $result = $this->service->getCurrentPrice('AAPL');
        
        // Assert: Should handle gracefully and return null
        $this->assertNull($result);
    }
    
    public function testAPIRateLimitExceeded(): void
    {
        // Arrange: Mock throws exception to simulate API rate limit
        $this->mockStockDataAccess
            ->expects($this->once())
            ->method('getLatestPrice')
            ->with('AAPL')
            ->willThrowException(new \Exception('API rate limit exceeded'));
        
        // Act
        $result = $this->service->getCurrentPrice('AAPL');
        
        // Assert: Should handle gracefully and return null
        $this->assertNull($result);
    }
    
    public function testInvalidResponseFormat(): void
    {
        // Arrange: Mock returns malformed data (missing required fields)
        $this->mockStockDataAccess
            ->expects($this->once())
            ->method('getLatestPrice')
            ->with('AAPL')
            ->willReturn(['invalid' => 'data']); // Missing close, open, etc.
        
        // Act
        $result = $this->service->getCurrentPrice('AAPL');
        
        // Assert: Should handle gracefully - returns data with defaults
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['price']); // Default value when close is missing
    }
    
    // ===== EDGE CASES =====
    
    public function testGetCurrentPricesNullSymbols(): void
    {
        // Act: Pass null instead of array
        // Note: This might cause a TypeError in PHP 8+ if type-hinted strictly
        try {
            $result = $this->service->getCurrentPrices(null);
            
            // Assert: Should handle gracefully or throw TypeError
            $this->assertIsArray($result);
            $this->assertEmpty($result);
        } catch (\TypeError $e) {
            // TypeError is acceptable for null argument
            $this->assertStringContainsString('array', $e->getMessage());
        }
    }
    
    public function testGetHistoricalPricesFutureDate(): void
    {
        // Arrange: Request data for future date - mock returns empty array
        $startDate = date('Y-m-d', strtotime('+1 year'));
        $endDate = date('Y-m-d', strtotime('+2 years'));
        
        $this->mockStockDataAccess
            ->expects($this->once())
            ->method('getPriceData')
            ->with('AAPL', $startDate, $endDate, null)
            ->willReturn([]);
        
        // Act
        $result = $this->service->getHistoricalPrices('AAPL', $startDate, $endDate);
        
        // Assert: Should return empty (no future data)
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    public function testGetHistoricalPricesVeryLargeDateRange(): void
    {
        // Arrange: Request 50 years of data
        $startDate = '1970-01-01';
        $endDate = date('Y-m-d');
        
        // Mock returns whatever data is available
        $mockLargeDataset = array_fill(0, 100, [
            'date' => '2024-01-01',
            'open' => 150.00,
            'high' => 152.00,
            'low' => 148.00,
            'close' => 151.00,
            'volume' => 50000000
        ]);
        
        $this->mockStockDataAccess
            ->expects($this->once())
            ->method('getPriceData')
            ->with('AAPL', $startDate, $endDate, null)
            ->willReturn($mockLargeDataset);
        
        // Act
        $result = $this->service->getHistoricalPrices('AAPL', $startDate, $endDate);
        
        // Assert: Should handle large dataset
        $this->assertIsArray($result);
        $this->assertCount(100, $result);
    }
    
    // ===== INTEGRATION TESTS =====
    
    public function testCompleteMarketDataWorkflow(): void
    {
        // This test verifies the complete workflow with all method calls
        
        // Arrange: Set up mocks for all three operations
        $mockCurrentPrice = ['symbol' => 'AAPL', 'close' => 150.00, 'open' => 148.00, 'date' => '2025-01-15'];
        $mockHistorical = [['date' => '2024-01-02', 'open' => 148.00, 'close' => 150.00, 'volume' => 50000000]];
        $mockIndexPrice = ['symbol' => '^GSPC', 'close' => 4500.00, 'open' => 4480.00, 'date' => '2025-01-15'];
        
        $this->mockStockDataAccess
            ->expects($this->exactly(4)) // 1 for current + 1 for historical + 3 for market summary indices
            ->method('getLatestPrice')
            ->willReturnOnConsecutiveCalls($mockCurrentPrice, $mockIndexPrice, $mockIndexPrice, $mockIndexPrice);
        
        $this->mockStockDataAccess
            ->expects($this->once())
            ->method('getPriceData')
            ->with('AAPL', '2024-01-01', '2024-12-31', null)
            ->willReturn($mockHistorical);
        
        // Act
        $currentPrice = $this->service->getCurrentPrice('AAPL');
        $historicalPrices = $this->service->getHistoricalPrices('AAPL', '2024-01-01', '2024-12-31');
        $marketSummary = $this->service->getMarketSummary();
        
        // Assert
        $this->assertNotNull($currentPrice);
        $this->assertNotEmpty($historicalPrices);
        $this->assertNotEmpty($marketSummary);
    }
    
    // ===== PERFORMANCE TESTS =====
    
    public function testGetCurrentPricesPerformance(): void
    {
        // Arrange: Large batch of symbols with mocked responses
        $symbols = ['AAPL', 'GOOGL', 'MSFT', 'AMZN', 'TSLA', 'META', 'NVDA', 'AMD', 'INTC', 'NFLX'];
        
        // Mock returns data for all symbols
        $mockPrice = ['symbol' => 'TEST', 'close' => 150.00, 'open' => 148.00, 'date' => '2025-01-15'];
        $this->mockStockDataAccess
            ->expects($this->exactly(10))
            ->method('getLatestPrice')
            ->willReturn($mockPrice);
        
        // Act
        $startTime = microtime(true);
        $result = $this->service->getCurrentPrices($symbols);
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        
        // Assert: With mocked data, should be very fast
        $this->assertLessThan(1.0, $executionTime, 'Batch price fetch with mocks should complete within 1 second');
        $this->assertCount(10, $result);
    }
    
    // ===== DOCUMENTATION TESTS =====
    
    public function testMarketDataServiceInterface(): void
    {
        // Assert: Service should implement interface
        $this->assertInstanceOf(
            \App\Services\Interfaces\MarketDataServiceInterface::class,
            $this->service
        );
    }
    
    /**
     * This test documents the DI violation that needs to be fixed
     */
    public function testDependencyInjectionViolation(): void
    {
        // DOCUMENTATION TEST
        // This test documents that MarketDataService has a DI violation:
        // 1. Hard-coded require_once for DynamicStockDataAccess
        // 2. Direct instantiation in constructor
        // 3. Cannot inject mock for testing
        
        // Expected behavior after refactoring:
        // - Constructor should accept StockDataAccessInterface
        // - No direct instantiation
        // - Tests can inject mocks
        
        $this->markTestIncomplete(
            'MarketDataService requires refactoring for Dependency Injection. ' .
            'Constructor should accept StockDataAccessInterface parameter instead of ' .
            'directly instantiating DynamicStockDataAccess.'
        );
    }
}
