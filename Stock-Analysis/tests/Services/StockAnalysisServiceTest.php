<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\StockAnalysisService;
use App\Services\MarketDataService;
use App\Services\PythonIntegrationService;

/**
 * Comprehensive tests for StockAnalysisService
 * Tests all entry points, conditional branches, exit points, and error scenarios
 */
class StockAnalysisServiceTest extends TestCase
{
    private $marketDataService;
    private $pythonService;
    private StockAnalysisService $service;
    
    protected function setUp(): void
    {
        // Create mock dependencies
        $this->marketDataService = $this->createMock(MarketDataService::class);
        $this->pythonService = $this->createMock(PythonIntegrationService::class);
        
        // Create service with mocked dependencies
        $this->service = new StockAnalysisService(
            $this->marketDataService,
            $this->pythonService,
            ['python_path' => 'python']
        );
    }
    
    // ===== SUCCESS PATH TESTS =====
    
    public function testAnalyzeStockSuccess(): void
    {
        // Arrange: Mock market data methods
        $mockPriceData = [
            ['date' => '2025-01-01', 'close' => 150.00, 'open' => 149.00, 'high' => 151.00, 'low' => 148.00, 'volume' => 1000000],
            ['date' => '2025-01-02', 'close' => 152.00, 'open' => 150.00, 'high' => 153.00, 'low' => 149.00, 'volume' => 1100000],
        ];
        
        $this->marketDataService
            ->expects($this->once())
            ->method('getHistoricalPrices')
            ->with('AAPL')
            ->willReturn($mockPriceData);
        
        $this->marketDataService
            ->expects($this->once())
            ->method('getFundamentals')
            ->with('AAPL')
            ->willReturn(['pe_ratio' => 25.5]);
        
        // Arrange: Mock Python analysis
        $mockAnalysisResult = [
            'success' => true,
            'data' => [
                'scores' => [
                    'fundamental' => 75.0,
                    'technical' => 68.0,
                    'momentum' => 82.0,
                    'sentiment' => 70.0
                ],
                'overall_score' => 73.5,
                'recommendation' => 'BUY',
                'confidence' => 0.85,
                'risk_level' => 'MEDIUM'
            ]
        ];
        
        $this->pythonService
            ->expects($this->once())
            ->method('analyzeStock')
            ->willReturn($mockAnalysisResult);
        
        // Act
        $result = $this->service->analyzeStock('AAPL');
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('symbol', $result);
        $this->assertEquals('AAPL', $result['symbol']);
        $this->assertArrayHasKey('analysis', $result);
        $this->assertEquals(73.5, $result['analysis']['overall_score']);
        $this->assertEquals('BUY', $result['analysis']['recommendation']);
    }
    
    public function testAnalyzeStockWithCustomWeights(): void
    {
        // Arrange
        $customWeights = [
            'fundamental' => 0.50,
            'technical' => 0.30,
            'momentum' => 0.10,
            'sentiment' => 0.10
        ];
        
        $mockPriceData = [['date' => '2025-01-01', 'close' => 150.00, 'open' => 149.00, 'high' => 151.00, 'low' => 148.00, 'volume' => 1000000]];
        
        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($mockPriceData);
        
        $this->marketDataService
            ->method('getFundamentals')
            ->willReturn(['pe_ratio' => 25.5]);
        
        $mockAnalysisResult = [
            'success' => true,
            'data' => [
                'scores' => ['fundamental' => 80.0],
                'overall_score' => 75.0,
                'recommendation' => 'BUY'
            ]
        ];
        
        $this->pythonService
            ->method('analyzeStock')
            ->willReturn($mockAnalysisResult);
        
        // Act
        $result = $this->service->analyzeStock('AAPL', [
            'weights' => $customWeights
        ]);
        
        // Assert
        if (!$result['success']) {
            echo "\nDEBUG: " . print_r($result, true) . "\n";
        }
        $this->assertTrue($result['success']);
    }
    
    public function testAnalyzeStockWithPersistence(): void
    {
        // Arrange
        $mockPriceData = [['date' => '2025-01-01', 'close' => 150.00, 'open' => 149.00, 'high' => 151.00, 'low' => 148.00, 'volume' => 1000000]];
        
        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($mockPriceData);
        
        $this->marketDataService
            ->method('getFundamentals')
            ->willReturn(['pe_ratio' => 25.5]);
        
        $mockAnalysisResult = [
            'success' => true,
            'data' => [
                'scores' => ['fundamental' => 80.0],
                'overall_score' => 75.0,
                'recommendation' => 'BUY'
            ]
        ];
        
        $this->pythonService
            ->method('analyzeStock')
            ->willReturn($mockAnalysisResult);
        
        // Act
        $result = $this->service->analyzeStock('AAPL', [
            'persist' => true
        ]);
        
        // Assert
        $this->assertTrue($result['success']);
        // TODO: Verify data was persisted to database
        // This requires mocking the persistence layer when it's implemented
    }
    
    // ===== ERROR PATH TESTS =====
    
    public function testAnalyzeStockEmptySymbol(): void
    {
        // Act
        $result = $this->service->analyzeStock('');
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Symbol', $result['error']);
    }
    
    public function testAnalyzeStockInvalidSymbolFormat(): void
    {
        // Act: Symbol with numbers (invalid format)
        $result = $this->service->analyzeStock('AAPL123');
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
    
    public function testAnalyzeStockSymbolTooLong(): void
    {
        // Act: Symbol longer than 5 characters
        $result = $this->service->analyzeStock('TOOLONG');
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
    
    public function testAnalyzeStockFetchStockDataFails(): void
    {
        // Arrange: Market data fetch fails (returns empty array)
        $this->marketDataService
            ->expects($this->once())
            ->method('getHistoricalPrices')
            ->with('AAPL')
            ->willReturn([]);
        
        // Act
        $result = $this->service->analyzeStock('AAPL');
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
    
    public function testAnalyzeStockPythonAnalysisFails(): void
    {
        // Arrange: Market data succeeds
        $mockPriceData = [['date' => '2025-01-01', 'close' => 150.00, 'open' => 149.00, 'high' => 151.00, 'low' => 148.00, 'volume' => 1000000]];
        
        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($mockPriceData);
        
        $this->marketDataService
            ->method('getFundamentals')
            ->willReturn(['pe_ratio' => 25.5]);
        
        // Arrange: Python analysis fails
        $this->pythonService
            ->expects($this->once())
            ->method('analyzeStock')
            ->willReturn([
                'success' => false,
                'error' => 'Python execution failed'
            ]);
        
        // Act
        $result = $this->service->analyzeStock('AAPL');
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
    
    // ===== EXCEPTION HANDLING TESTS =====
    
    public function testAnalyzeStockMarketDataServiceThrowsException(): void
    {
        // Arrange: Market data service throws exception
        $this->marketDataService
            ->expects($this->once())
            ->method('getHistoricalPrices')
            ->willThrowException(new \Exception('Database connection lost'));
        
        // Act
        $result = $this->service->analyzeStock('AAPL');
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
    
    public function testAnalyzeStockPythonServiceThrowsException(): void
    {
        // Arrange: Market data succeeds
        $mockPriceData = [['date' => '2025-01-01', 'close' => 150.00, 'open' => 149.00, 'high' => 151.00, 'low' => 148.00, 'volume' => 1000000]];
        
        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($mockPriceData);
        
        $this->marketDataService
            ->method('getFundamentals')
            ->willReturn(['pe_ratio' => 25.5]);
        
        // Arrange: Python service throws exception
        $this->pythonService
            ->expects($this->once())
            ->method('analyzeStock')
            ->willThrowException(new \RuntimeException('Python script not found'));
        
        // Act
        $result = $this->service->analyzeStock('AAPL');
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
    
    // ===== EDGE CASE TESTS =====
    
    public function testAnalyzeStockNoPriceData(): void
    {
        // Arrange: Stock data has no prices
        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn([]); // Empty prices
        
        $this->marketDataService
            ->method('getFundamentals')
            ->willReturn([]);
        
        // Act
        $result = $this->service->analyzeStock('AAPL');
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('price', strtolower($result['error']));
    }
    
    public function testAnalyzeStockPartialData(): void
    {
        // Arrange: Stock data has limited information
        $mockPriceData = [
            ['date' => '2025-01-01', 'close' => 150.00, 'open' => 149.00, 'high' => 151.00, 'low' => 148.00, 'volume' => 1000000]
        ];
        
        $mockAnalysisResult = [
            'success' => true,
            'data' => [
                'scores' => [
                    'fundamental' => 50.0, // Lower score due to missing data
                    'technical' => 50.0,
                    'momentum' => 50.0,
                    'sentiment' => 50.0
                ],
                'overall_score' => 50.0,
                'recommendation' => 'HOLD',
                'confidence' => 0.4 // Low confidence
            ]
        ];
        
        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($mockPriceData);
        
        $this->marketDataService
            ->method('getFundamentals')
            ->willReturn([]);
        
        $this->pythonService
            ->method('analyzeStock')
            ->willReturn($mockAnalysisResult);
        
        // Act
        $result = $this->service->analyzeStock('AAPL');
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertLessThan(0.7, $result['analysis']['confidence']);
    }
    
    public function testAnalyzeStockVeryLargeDataset(): void
    {
        // Arrange: 10 years of daily price data
        $prices = [];
        for ($i = 0; $i < 2520; $i++) { // ~10 years of trading days
            $prices[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'close' => 100 + ($i * 0.1),
                'open' => 99 + ($i * 0.1),
                'high' => 101 + ($i * 0.1),
                'low' => 98 + ($i * 0.1),
                'volume' => 1000000
            ];
        }
        
        $mockAnalysisResult = [
            'success' => true,
            'data' => [
                'scores' => ['fundamental' => 75.0],
                'overall_score' => 75.0,
                'recommendation' => 'BUY'
            ]
        ];
        
        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($prices);
        
        $this->marketDataService
            ->method('getFundamentals')
            ->willReturn(['pe_ratio' => 25.5]);
        
        $this->pythonService
            ->method('analyzeStock')
            ->willReturn($mockAnalysisResult);
        
        // Act
        $result = $this->service->analyzeStock('AAPL');
        
        // Assert
        $this->assertTrue($result['success']);
    }
    
    // ===== CONDITIONAL BRANCH TESTS =====
    
    public function testPrepareAnalysisInputWithFundamentals(): void
    {
        // Arrange
        $stockData = [
            'symbol' => 'AAPL',
            'prices' => [['date' => '2025-01-01', 'close' => 150.00]],
            'fundamentals' => [
                'pe_ratio' => 25.5,
                'eps' => 6.00,
                'market_cap' => 2500000000000
            ]
        ];
        
        $mockAnalysisResult = [
            'success' => true,
            'data' => ['overall_score' => 75.0, 'recommendation' => 'BUY', 'risk_level' => 'MEDIUM']
        ];
        
        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($stockData['prices']);
        
        $this->marketDataService
            ->method('getFundamentals')
            ->willReturn($stockData['fundamentals']);
        
        $this->pythonService
            ->method('analyzeStock')
            ->willReturn($mockAnalysisResult);
        
        // Act
        $result = $this->service->analyzeStock('AAPL');
        
        // Assert
        $this->assertTrue($result['success']);
    }
    
    public function testPrepareAnalysisInputWithoutFundamentals(): void
    {
        // Arrange: No fundamentals data
        $mockPriceData = [['date' => '2025-01-01', 'close' => 150.00, 'open' => 149.00, 'high' => 151.00, 'low' => 148.00, 'volume' => 1000000]];
        
        $mockAnalysisResult = [
            'success' => true,
            'data' => ['overall_score' => 65.0, 'recommendation' => 'BUY', 'risk_level' => 'MEDIUM']
        ];
        
        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($mockPriceData);
        
        $this->marketDataService
            ->method('getFundamentals')
            ->willReturn([]);
        
        $this->pythonService
            ->method('analyzeStock')
            ->willReturn($mockAnalysisResult);
        
        // Act
        $result = $this->service->analyzeStock('AAPL');
        
        // Assert
        $this->assertTrue($result['success']);
    }
    
    public function testAnalyzeStockWithDateRange(): void
    {
        // Arrange
        $options = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31'
        ];
        
        $mockPriceData = [['date' => '2024-06-01', 'close' => 150.00, 'open' => 149.00, 'high' => 151.00, 'low' => 148.00, 'volume' => 1000000]];
        
        $mockAnalysisResult = [
            'success' => true,
            'data' => ['overall_score' => 70.0, 'recommendation' => 'BUY', 'risk_level' => 'MEDIUM']
        ];
        
        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($mockPriceData);
        
        $this->marketDataService
            ->expects($this->once())
            ->method('getFundamentals')
            ->with(
                'AAPL',
                $this->callback(function($opts) {
                    return isset($opts['start_date']) && isset($opts['end_date']);
                })
            )
            ->willReturn(['pe_ratio' => 25.5]);
        
        $this->pythonService
            ->method('analyzeStock')
            ->willReturn($mockAnalysisResult);
        
        // Act
        $result = $this->service->analyzeStock('AAPL', $options);
        
        // Assert
        $this->assertTrue($result['success']);
    }
    
    // ===== INTEGRATION TESTS =====
    
    public function testCompleteAnalysisWorkflow(): void
    {
        // This test verifies the entire workflow from symbol input to final result
        
        // Arrange
        $symbol = 'AAPL';
        
        $mockStockData = [
            'success' => true,
            'symbol' => $symbol,
            'prices' => [
                ['date' => '2025-01-01', 'open' => 148.00, 'high' => 152.00, 'low' => 147.00, 'close' => 150.00, 'volume' => 50000000],
                ['date' => '2025-01-02', 'open' => 150.50, 'high' => 154.00, 'low' => 149.00, 'close' => 152.00, 'volume' => 55000000],
            ],
            'fundamentals' => [
                'pe_ratio' => 25.5,
                'eps' => 6.00,
                'market_cap' => 2500000000000,
                'revenue_growth' => 0.12
            ]
        ];
        
        $mockAnalysisResult = [
            'success' => true,
            'data' => [
                'scores' => [
                    'fundamental' => 75.0,
                    'technical' => 68.0,
                    'momentum' => 82.0,
                    'sentiment' => 70.0
                ],
                'overall_score' => 73.5,
                'recommendation' => 'BUY',
                'confidence' => 0.85,
                'reasoning' => 'Strong fundamentals and positive momentum'
            ]
        ];
        
        $this->marketDataService
            ->method('getHistoricalPrices')
            ->willReturn($mockStockData['prices']);
        
        $this->marketDataService
            ->method('getFundamentals')
            ->willReturn($mockStockData['fundamentals']);
        
        $this->pythonService
            ->method('analyzeStock')
            ->willReturn($mockAnalysisResult);
        
        // Act
        $result = $this->service->analyzeStock($symbol);
        
        // Assert: Comprehensive validation of entire result structure
        $this->assertTrue($result['success']);
        $this->assertEquals($symbol, $result['symbol']);
        
        $this->assertArrayHasKey('analysis', $result);
        $this->assertArrayHasKey('scores', $result['analysis']);
        $this->assertArrayHasKey('overall_score', $result['analysis']);
        $this->assertArrayHasKey('recommendation', $result['analysis']);
        $this->assertArrayHasKey('confidence', $result['analysis']);
        
        $this->assertGreaterThanOrEqual(0, $result['analysis']['overall_score']);
        $this->assertLessThanOrEqual(100, $result['analysis']['overall_score']);
        
        $this->assertContains(
            $result['analysis']['recommendation'],
            ['BUY', 'HOLD', 'SELL']
        );
        
        $this->assertGreaterThanOrEqual(0, $result['analysis']['confidence']);
        $this->assertLessThanOrEqual(1, $result['analysis']['confidence']);
    }
}
