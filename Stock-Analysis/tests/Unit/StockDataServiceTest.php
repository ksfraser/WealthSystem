    /**
     * @covers StockDataService::getStockDataWithIndicators
     * LLM/TDD: This test verifies that getStockDataWithIndicators returns both OHLCV and technical indicators.
     */
    public function testGetStockDataWithIndicators()
    {
        // Minimal mock OHLCV data for indicator calculation
        $mockOhlcv = [
            [
                'Date' => '2025-10-01',
                'Open' => 100,
                'High' => 110,
                'Low' => 95,
                'Close' => 105,
                'Volume' => 10000
            ],
            [
                'Date' => '2025-10-02',
                'Open' => 106,
                'High' => 112,
                'Low' => 104,
                'Close' => 110,
                'Volume' => 12000
            ],
            [
                'Date' => '2025-10-03',
                'Open' => 111,
                'High' => 115,
                'Low' => 109,
                'Close' => 113,
                'Volume' => 13000
            ]
        ];

        // Mock repository to return OHLCV data
        $mockRepo = $this->createMock(\Ksfraser\Finance\Interfaces\DataRepositoryInterface::class);
        $mockRepo->method('getHistoricalPrices')->willReturn($mockOhlcv);

        // Service with no data sources, just the mock repo
        $service = new \Ksfraser\Finance\Services\StockDataService([], $mockRepo);
        $result = $service->getStockDataWithIndicators('AAPL', '1y', ['rsi','sma','ema','macd','bbands'], ['rsi'=>2,'sma'=>2,'ema'=>2,'macd_fast'=>2,'macd_slow'=>3,'macd_signal'=>2,'bbands'=>2]);

        $this->assertArrayHasKey('ohlcv', $result);
        $this->assertArrayHasKey('indicators', $result);
        $this->assertCount(3, $result['ohlcv']);
        $this->assertArrayHasKey('rsi', $result['indicators']);
        $this->assertArrayHasKey('sma', $result['indicators']);
        $this->assertArrayHasKey('ema', $result['indicators']);
        $this->assertArrayHasKey('macd', $result['indicators']);
        $this->assertArrayHasKey('bbands', $result['indicators']);
        // Check that indicator arrays are keyed by date
        $this->assertArrayHasKey('2025-10-01', $result['indicators']['rsi']);
        $this->assertArrayHasKey('2025-10-02', $result['indicators']['rsi']);
        $this->assertArrayHasKey('2025-10-03', $result['indicators']['rsi']);
    }
<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../StockDataService.php';

class StockDataServiceTest extends TestCase
{
    private $stockDataService;
    private $mockYahooClient;

    protected function setUp(): void
    {
        $this->stockDataService = new StockDataService(false); // Disable debug mode for tests
        
        // Create a mock YahooFinancePhp client
        $this->mockYahooClient = $this->createMock(YahooFinancePhp::class);
        
        // Use reflection to inject mock client
        $reflection = new ReflectionClass($this->stockDataService);
        $clientProperty = $reflection->getProperty('yahooClient');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->stockDataService, $this->mockYahooClient);
    }

    public function testConstructorInitialization()
    {
        $service = new StockDataService();
        $this->assertInstanceOf(StockDataService::class, $service);
        
        // Test with debug mode
        $debugService = new StockDataService(true);
        $this->assertInstanceOf(StockDataService::class, $debugService);
    }

    public function testFetchHistoricalDataSuccess()
    {
        $mockYahooResponse = [
            'symbol' => 'AAPL',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-03',
            'count' => 2,
            'data' => [
                [
                    'Date' => '2023-01-01',
                    'Open' => 130.25,
                    'High' => 135.50,
                    'Low' => 129.75,
                    'Close' => 133.41,
                    'Adj Close' => 133.41,
                    'Volume' => 85467200
                ],
                [
                    'Date' => '2023-01-02',
                    'Open' => 133.41,
                    'High' => 134.25,
                    'Low' => 130.80,
                    'Close' => 132.15,
                    'Adj Close' => 132.15,
                    'Volume' => 78945600
                ]
            ]
        ];

        $this->mockYahooClient
            ->expects($this->once())
            ->method('fetchHistoricalData')
            ->with('AAPL', '2023-01-01', '2023-01-03')
            ->willReturn($mockYahooResponse);

        $result = $this->stockDataService->fetchHistoricalData('AAPL', '2023-01-01', '2023-01-03');
        $decodedResult = json_decode($result, true);

        $this->assertIsString($result);
        $this->assertJson($result);
        $this->assertTrue($decodedResult['success']);
        $this->assertEquals('AAPL', $decodedResult['symbol']);
        $this->assertEquals('2023-01-01', $decodedResult['start_date']);
        $this->assertEquals('2023-01-03', $decodedResult['end_date']);
        $this->assertEquals(2, $decodedResult['total_records']);
        $this->assertEquals('PHP_YahooFinance', $decodedResult['source']);
        $this->assertArrayHasKey('data', $decodedResult);
        $this->assertCount(2, $decodedResult['data']);

        // Verify data type conversion
        $firstRecord = $decodedResult['data'][0];
        $this->assertIsFloat($firstRecord['Open']);
        $this->assertIsFloat($firstRecord['High']);
        $this->assertIsFloat($firstRecord['Low']);
        $this->assertIsFloat($firstRecord['Close']);
        $this->assertIsFloat($firstRecord['Adj Close']);
        $this->assertIsInt($firstRecord['Volume']);
    }

    public function testFetchHistoricalDataFailure()
    {
        $this->mockYahooClient
            ->expects($this->once())
            ->method('fetchHistoricalData')
            ->with('INVALID', '2023-01-01', '2023-01-03')
            ->will($this->throwException(new Exception('Symbol not found')));

        $result = $this->stockDataService->fetchHistoricalData('INVALID', '2023-01-01', '2023-01-03');
        $decodedResult = json_decode($result, true);

        $this->assertIsString($result);
        $this->assertJson($result);
        $this->assertFalse($decodedResult['success']);
        $this->assertEquals('Symbol not found', $decodedResult['error']);
        $this->assertEquals('INVALID', $decodedResult['symbol']);
        $this->assertEquals('PHP_YahooFinance', $decodedResult['source']);
    }

    public function testSaveToCSVSuccess()
    {
        $jsonData = json_encode([
            'success' => true,
            'symbol' => 'TEST',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-02',
            'total_records' => 2,
            'data' => [
                [
                    'Date' => '2023-01-01',
                    'Open' => 100.00,
                    'High' => 105.00,
                    'Low' => 99.00,
                    'Close' => 103.50,
                    'Adj Close' => 103.50,
                    'Volume' => 1000000
                ],
                [
                    'Date' => '2023-01-02',
                    'Open' => 103.50,
                    'High' => 106.00,
                    'Low' => 102.00,
                    'Close' => 104.75,
                    'Adj Close' => 104.75,
                    'Volume' => 1200000
                ]
            ]
        ]);

        $expectedYahooData = [
            'symbol' => 'TEST',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-02',
            'count' => 2,
            'data' => [
                [
                    'Date' => '2023-01-01',
                    'Open' => 100.00,
                    'High' => 105.00,
                    'Low' => 99.00,
                    'Close' => 103.50,
                    'Adj Close' => 103.50,
                    'Volume' => 1000000
                ],
                [
                    'Date' => '2023-01-02',
                    'Open' => 103.50,
                    'High' => 106.00,
                    'Low' => 102.00,
                    'Close' => 104.75,
                    'Adj Close' => 104.75,
                    'Volume' => 1200000
                ]
            ]
        ];

        $expectedCsvPath = '/path/to/test.csv';

        $this->mockYahooClient
            ->expects($this->once())
            ->method('saveToCSV')
            ->with($expectedYahooData, null)
            ->willReturn($expectedCsvPath);

        $result = $this->stockDataService->saveToCSV($jsonData);
        $this->assertEquals($expectedCsvPath, $result);
    }

    public function testSaveToCSVWithFailedData()
    {
        $jsonData = json_encode([
            'success' => false,
            'error' => 'Data fetch failed',
            'symbol' => 'INVALID'
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot save failed data to CSV');

        $this->stockDataService->saveToCSV($jsonData);
    }

    public function testTestConnection()
    {
        $this->mockYahooClient
            ->expects($this->once())
            ->method('testConnection')
            ->willReturn(true);

        $result = $this->stockDataService->testConnection();
        $this->assertTrue($result);
    }

    public function testGetChunkedDataBasic()
    {
        $chunks = $this->stockDataService->getChunkedData('AAPL', '2020-01-01', '2020-12-31', 6);

        $this->assertIsArray($chunks);
        $this->assertCount(2, $chunks); // Should create 2 chunks of 6 months each

        $firstChunk = $chunks[0];
        $this->assertEquals('2020-01-01', $firstChunk['start']);
        $this->assertEquals('2020-07-01', $firstChunk['end']);

        $secondChunk = $chunks[1];
        $this->assertEquals('2020-07-02', $secondChunk['start']);
        $this->assertEquals('2020-12-31', $secondChunk['end']);
    }

    public function testGetChunkedDataLargeRange()
    {
        $chunks = $this->stockDataService->getChunkedData('AAPL', '2018-01-01', '2023-12-31', 12);

        $this->assertIsArray($chunks);
        $this->assertCount(6, $chunks); // 6 years = 6 chunks of 12 months

        // Test first chunk
        $firstChunk = $chunks[0];
        $this->assertEquals('2018-01-01', $firstChunk['start']);
        $this->assertEquals('2019-01-01', $firstChunk['end']);

        // Test last chunk
        $lastChunk = end($chunks);
    $this->assertEquals('2023-01-06', $lastChunk['start']);
        $this->assertEquals('2023-12-31', $lastChunk['end']);
    }

    public function testGetChunkedDataSmallRange()
    {
        $chunks = $this->stockDataService->getChunkedData('AAPL', '2023-01-01', '2023-03-31', 6);

        $this->assertIsArray($chunks);
        $this->assertCount(1, $chunks); // 3 months should create only 1 chunk

        $chunk = $chunks[0];
        $this->assertEquals('2023-01-01', $chunk['start']);
        $this->assertEquals('2023-03-31', $chunk['end']);
    }

    public function testFetchChunkData()
    {
        $mockYahooResponse = [
            'symbol' => 'AAPL',
            'start_date' => '2023-01-01',
            'end_date' => '2023-06-30',
            'count' => 126,
            'data' => [] // Empty for test
        ];

        $this->mockYahooClient
            ->expects($this->once())
            ->method('fetchHistoricalData')
            ->with('AAPL', '2023-01-01', '2023-06-30')
            ->willReturn($mockYahooResponse);

        $result = $this->stockDataService->fetchChunkData('AAPL', '2023-01-01', '2023-06-30');
        $decodedResult = json_decode($result, true);

        $this->assertIsString($result);
        $this->assertJson($result);
        $this->assertTrue($decodedResult['success']);
        $this->assertEquals('AAPL', $decodedResult['symbol']);
        $this->assertEquals(126, $decodedResult['total_records']);
    }

    public function testDebugMode()
    {
        // Test that debug mode doesn't throw errors
        $debugService = new StockDataService(true);
        
        // Create mock for debug service
        $mockYahooClient = $this->createMock(YahooFinancePhp::class);
        $reflection = new ReflectionClass($debugService);
        $clientProperty = $reflection->getProperty('yahooClient');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($debugService, $mockYahooClient);

        $mockYahooResponse = [
            'symbol' => 'AAPL',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-03',
            'count' => 2,
            'data' => []
        ];

        $mockYahooClient
            ->expects($this->once())
            ->method('fetchHistoricalData')
            ->willReturn($mockYahooResponse);

        $result = $debugService->fetchHistoricalData('AAPL', '2023-01-01', '2023-01-03');
        $decodedResult = json_decode($result, true);

        $this->assertTrue($decodedResult['success']);
    }

    public function testDataTypeConversion()
    {
        $mockYahooResponse = [
            'symbol' => 'AAPL',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-01',
            'count' => 1,
            'data' => [
                [
                    'Date' => '2023-01-01',
                    'Open' => '130.25', // String number
                    'High' => '135.50',
                    'Low' => '129.75',
                    'Close' => '133.41',
                    'Adj Close' => '133.41',
                    'Volume' => '85467200' // String number
                ]
            ]
        ];

        $this->mockYahooClient
            ->expects($this->once())
            ->method('fetchHistoricalData')
            ->willReturn($mockYahooResponse);

        $result = $this->stockDataService->fetchHistoricalData('AAPL', '2023-01-01', '2023-01-01');
        $decodedResult = json_decode($result, true);

        $record = $decodedResult['data'][0];
        
        // Verify proper type conversion
        $this->assertIsFloat($record['Open']);
        $this->assertIsFloat($record['High']);
        $this->assertIsFloat($record['Low']);
        $this->assertIsFloat($record['Close']);
        $this->assertIsFloat($record['Adj Close']);
        $this->assertIsInt($record['Volume']);

        // Verify values
        $this->assertEquals(130.25, $record['Open']);
        $this->assertEquals(85467200, $record['Volume']);
    }

    protected function tearDown(): void
    {
        // Clean up any test files
        $dataDir = __DIR__ . '/../../data/csv';
        if (is_dir($dataDir)) {
            $files = glob("$dataDir/TEST_*.csv");
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
}