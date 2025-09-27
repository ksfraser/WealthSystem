<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../ProgressiveHistoricalLoader.php';

class ProgressiveHistoricalLoaderTest extends TestCase
{
    private $progressiveLoader;
    private $mockDatabase;
    private $mockStockDAO;
    private $mockStockDataService;
    private $mockLogger;

    protected function setUp(): void
    {
        // Create mock database connection
        $this->mockDatabase = $this->createMock(PDO::class);
        
        // Create mock logger
        $this->mockLogger = $this->createMock(SimpleLogger::class);
        
        // Create the loader
        $this->progressiveLoader = new ProgressiveHistoricalLoader($this->mockDatabase, $this->mockLogger);
        
        // Create mocks for internal components
        $this->mockStockDAO = $this->createMock(StockDAO::class);
        $this->mockStockDataService = $this->createMock(StockDataService::class);
        
        // Use reflection to inject mock components
        $reflection = new ReflectionClass($this->progressiveLoader);
        
        $stockDAOProperty = $reflection->getProperty('stockDAO');
        $stockDAOProperty->setAccessible(true);
        $stockDAOProperty->setValue($this->progressiveLoader, $this->mockStockDAO);
        
        $stockDataServiceProperty = $reflection->getProperty('stockDataService');
        $stockDataServiceProperty->setAccessible(true);
        $stockDataServiceProperty->setValue($this->progressiveLoader, $this->mockStockDataService);
    }

    public function testConstructorInitialization()
    {
        $loader = new ProgressiveHistoricalLoader($this->mockDatabase);
        $this->assertInstanceOf(ProgressiveHistoricalLoader::class, $loader);
        
        // Test with custom logger
        $customLogger = $this->createMock(SimpleLogger::class);
        $loaderWithLogger = new ProgressiveHistoricalLoader($this->mockDatabase, $customLogger);
        $this->assertInstanceOf(ProgressiveHistoricalLoader::class, $loaderWithLogger);
    }

    public function testLoadAllHistoricalDataSuccess()
    {
        $symbol = 'AAPL';
        
        // Mock no existing data (starting fresh)
        $this->mockStockDAO
            ->expects($this->once())
            ->method('getPriceDataOrdered')
            ->willReturn([]);
            
        // Mock successful data service responses
        $mockJsonResponse = json_encode([
            'success' => true,
            'symbol' => $symbol,
            'total_records' => 250,
            'data' => []
        ]);
        
        $this->mockStockDataService
            ->expects($this->atLeastOnce())
            ->method('fetchHistoricalData')
            ->willReturn($mockJsonResponse);

        // Mock logger expectations
        $this->mockLogger
            ->expects($this->atLeastOnce())
            ->method('info')
            ->with($this->stringContains('progressive historical data load'));

        $result = $this->progressiveLoader->loadAllHistoricalData($symbol);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($symbol, $result['symbol']);
        $this->assertArrayHasKey('chunks_processed', $result);
        $this->assertArrayHasKey('total_records', $result);
        $this->assertArrayHasKey('date_range', $result);
    }

    public function testLoadAllHistoricalDataWithException()
    {
        $symbol = 'INVALID';
        
        // Mock exception during data retrieval
        $this->mockStockDAO
            ->expects($this->once())
            ->method('getPriceDataOrdered')
            ->will($this->throwException(new Exception('Database connection failed')));

        // Mock logger expectations for error
        $this->mockLogger
            ->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Progressive load failed'));

        $result = $this->progressiveLoader->loadAllHistoricalData($symbol);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals($symbol, $result['symbol']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Database connection failed', $result['error']);
    }

    public function testLoadMultipleSymbolsSuccess()
    {
        $symbols = ['AAPL', 'MSFT', 'GOOGL'];
        
        // Mock successful individual loads
        $this->mockStockDAO
            ->method('getPriceDataOrdered')
            ->willReturn([]);
            
        $mockJsonResponse = json_encode([
            'success' => true,
            'symbol' => 'TEST',
            'total_records' => 100,
            'data' => []
        ]);
        
        $this->mockStockDataService
            ->method('fetchHistoricalData')
            ->willReturn($mockJsonResponse);

        $results = $this->progressiveLoader->loadMultipleSymbols($symbols);

        $this->assertIsArray($results);
        $this->assertCount(3, $results);
        
        foreach ($symbols as $symbol) {
            $this->assertArrayHasKey($symbol, $results);
            $this->assertArrayHasKey('success', $results[$symbol]);
        }
    }

    public function testLoadMultipleSymbolsWithMixedResults()
    {
        $symbols = ['AAPL', 'INVALID'];
        
        // Mock different responses for different symbols
        $this->mockStockDAO
            ->method('getPriceDataOrdered')
            ->willReturnCallback(function($symbol) {
                if ($symbol === 'INVALID') {
                    throw new Exception('Symbol not found');
                }
                return [];
            });

        $mockSuccessResponse = json_encode([
            'success' => true,
            'symbol' => 'AAPL',
            'total_records' => 100,
            'data' => []
        ]);
        
        $this->mockStockDataService
            ->method('fetchHistoricalData')
            ->willReturn($mockSuccessResponse);

        $results = $this->progressiveLoader->loadMultipleSymbols($symbols);

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        
        // AAPL should succeed, INVALID should fail
        $this->assertTrue($results['AAPL']['success']);
        $this->assertFalse($results['INVALID']['success']);
        $this->assertArrayHasKey('error', $results['INVALID']);
    }

    public function testGetProgressInfoBasic()
    {
        $symbol = 'AAPL';
        
        // Mock existing data
        $mockData = [
            ['date' => '2023-01-01', 'close_price' => 150.00],
            ['date' => '2023-01-02', 'close_price' => 151.00],
            ['date' => '2023-01-03', 'close_price' => 149.00]
        ];
        
        $this->mockStockDAO
            ->expects($this->once())
            ->method('getPriceDataOrdered')
            ->with($symbol, 'ASC')
            ->willReturn($mockData);
        
        $this->mockStockDAO
            ->expects($this->once())
            ->method('getPriceDataOrdered')
            ->with($symbol, 'DESC', 1)
            ->willReturn([['date' => '2023-01-03', 'close_price' => 149.00]]);

        $progressInfo = $this->progressiveLoader->getProgressInfo($symbol);

        $this->assertIsArray($progressInfo);
        $this->assertEquals($symbol, $progressInfo['symbol']);
        $this->assertArrayHasKey('total_records', $progressInfo);
        $this->assertArrayHasKey('date_range', $progressInfo);
    }

    public function testGetProgressInfoNoData()
    {
        $symbol = 'NEWSTOCK';
        
        // Mock no existing data
        $this->mockStockDAO
            ->method('getPriceDataOrdered')
            ->willReturn([]);

        $progressInfo = $this->progressiveLoader->getProgressInfo($symbol);

        $this->assertIsArray($progressInfo);
        $this->assertEquals($symbol, $progressInfo['symbol']);
        $this->assertEquals(0, $progressInfo['total_records']);
        $this->assertNull($progressInfo['date_range']['oldest']);
        $this->assertNull($progressInfo['date_range']['newest']);
    }

    public function testHasDataForPeriodTrue()
    {
        $symbol = 'AAPL';
        $startDate = new DateTime('2023-01-01');
        $endDate = new DateTime('2023-01-03');
        
        // Mock data exists for period
        $this->mockStockDAO
            ->expects($this->once())
            ->method('getDataCountForPeriod')
            ->with($symbol, '2023-01-01', '2023-01-03')
            ->willReturn(3);

        $reflection = new ReflectionClass($this->progressiveLoader);
        $method = $reflection->getMethod('hasDataForPeriod');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($this->progressiveLoader, [$symbol, $startDate, $endDate]);
        $this->assertTrue($result);
    }

    public function testHasDataForPeriodFalse()
    {
        $symbol = 'AAPL';
        $startDate = new DateTime('2020-01-01');
        $endDate = new DateTime('2020-01-03');
        
        // Mock no data for period
        $this->mockStockDAO
            ->expects($this->once())
            ->method('getDataCountForPeriod')
            ->with($symbol, '2020-01-01', '2020-01-03')
            ->willReturn(0);

        $reflection = new ReflectionClass($this->progressiveLoader);
        $method = $reflection->getMethod('hasDataForPeriod');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($this->progressiveLoader, [$symbol, $startDate, $endDate]);
        $this->assertFalse($result);
    }

    public function testEstimateIPODate()
    {
        $symbol = 'AAPL';
        
        // Mock IPO estimation (this is a simplified version)
        $reflection = new ReflectionClass($this->progressiveLoader);
        $method = $reflection->getMethod('estimateIPODate');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($this->progressiveLoader, [$symbol]);
        
        // Should return a DateTime object or null
        $this->assertTrue($result === null || $result instanceof DateTime);
    }

    public function testFetchChunkDataSuccess()
    {
        $symbol = 'AAPL';
        $startDate = new DateTime('2023-01-01');
        $endDate = new DateTime('2023-01-31');
        
        $mockJsonResponse = json_encode([
            'success' => true,
            'symbol' => $symbol,
            'total_records' => 21,
            'data' => []
        ]);
        
        $this->mockStockDataService
            ->expects($this->once())
            ->method('fetchHistoricalData')
            ->with($symbol, '2023-01-01', '2023-01-31')
            ->willReturn($mockJsonResponse);

        $reflection = new ReflectionClass($this->progressiveLoader);
        $method = $reflection->getMethod('fetchChunkData');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($this->progressiveLoader, [$symbol, $startDate, $endDate]);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals(21, $result['records']);
    }

    public function testFetchChunkDataFailure()
    {
        $symbol = 'INVALID';
        $startDate = new DateTime('2023-01-01');
        $endDate = new DateTime('2023-01-31');
        
        $mockJsonResponse = json_encode([
            'success' => false,
            'error' => 'Symbol not found',
            'symbol' => $symbol
        ]);
        
        $this->mockStockDataService
            ->expects($this->once())
            ->method('fetchHistoricalData')
            ->with($symbol, '2023-01-01', '2023-01-31')
            ->willReturn($mockJsonResponse);

        $reflection = new ReflectionClass($this->progressiveLoader);
        $method = $reflection->getMethod('fetchChunkData');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($this->progressiveLoader, [$symbol, $startDate, $endDate]);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Symbol not found', $result['error']);
    }

    public function testGetOldestDataDate()
    {
        $symbol = 'AAPL';
        
        $mockData = [
            ['date' => '2020-01-01', 'close_price' => 150.00]
        ];
        
        $this->mockStockDAO
            ->expects($this->once())
            ->method('getPriceDataOrdered')
            ->with($symbol, 'ASC', 1)
            ->willReturn($mockData);

        $reflection = new ReflectionClass($this->progressiveLoader);
        $method = $reflection->getMethod('getOldestDataDate');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($this->progressiveLoader, [$symbol]);

        $this->assertInstanceOf(DateTime::class, $result);
        $this->assertEquals('2020-01-01', $result->format('Y-m-d'));
    }

    public function testGetOldestDataDateNoData()
    {
        $symbol = 'NEWSTOCK';
        
        $this->mockStockDAO
            ->expects($this->once())
            ->method('getPriceDataOrdered')
            ->with($symbol, 'ASC', 1)
            ->willReturn([]);

        $reflection = new ReflectionClass($this->progressiveLoader);
        $method = $reflection->getMethod('getOldestDataDate');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($this->progressiveLoader, [$symbol]);

        $this->assertNull($result);
    }

    public function testGetLatestDataDate()
    {
        $symbol = 'AAPL';
        
        $mockData = [
            ['date' => '2023-12-31', 'close_price' => 180.00]
        ];
        
        $this->mockStockDAO
            ->expects($this->once())
            ->method('getPriceDataOrdered')
            ->with($symbol, 'DESC', 1)
            ->willReturn($mockData);

        $reflection = new ReflectionClass($this->progressiveLoader);
        $method = $reflection->getMethod('getLatestDataDate');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($this->progressiveLoader, [$symbol]);

        $this->assertInstanceOf(DateTime::class, $result);
        $this->assertEquals('2023-12-31', $result->format('Y-m-d'));
    }

    public function testCustomStartDate()
    {
        $symbol = 'AAPL';
        $customStartDate = '2022-06-01';
        
        // Mock no existing data
        $this->mockStockDAO
            ->method('getPriceDataOrdered')
            ->willReturn([]);
            
        $mockJsonResponse = json_encode([
            'success' => true,
            'symbol' => $symbol,
            'total_records' => 100,
            'data' => []
        ]);
        
        $this->mockStockDataService
            ->method('fetchHistoricalData')
            ->willReturn($mockJsonResponse);

        $result = $this->progressiveLoader->loadAllHistoricalData($symbol, $customStartDate);

        $this->assertTrue($result['success']);
        $this->assertEquals($symbol, $result['symbol']);
        // The date range should reflect the custom start date
        $this->assertEquals('2022-06-01', $result['date_range']['oldest']);
    }

    protected function tearDown(): void
    {
        // Clean up any test artifacts
        parent::tearDown();
    }
}