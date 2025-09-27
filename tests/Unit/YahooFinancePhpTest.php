<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

require_once __DIR__ . '/../../YahooFinancePhp.php';

class YahooFinancePhpTest extends TestCase
{
    private $yahooFinance;
    private $mockHandler;
    private $mockClient;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->mockClient = new Client(['handler' => $handlerStack]);
        
        $this->yahooFinance = new YahooFinancePhp();
        
        // Use reflection to inject mock client
        $reflection = new ReflectionClass($this->yahooFinance);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->yahooFinance, $this->mockClient);
    }

    public function testConstructorInitialization()
    {
        $yahoo = new YahooFinancePhp();
        $this->assertInstanceOf(YahooFinancePhp::class, $yahoo);
        
        // Verify internal client is created
        $reflection = new ReflectionClass($yahoo);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $client = $clientProperty->getValue($yahoo);
        
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testFetchHistoricalDataSuccess()
    {
        // Mock successful Yahoo Finance response
        $mockResponseBody = json_encode([
            'chart' => [
                'result' => [[
                    'meta' => [
                        'symbol' => 'AAPL',
                        'exchangeName' => 'NMS',
                        'currency' => 'USD'
                    ],
                    'timestamp' => [1672531200, 1672617600, 1672704000], // 3 days
                    'indicators' => [
                        'quote' => [[
                            'open' => [130.25, 131.50, 129.75],
                            'high' => [135.50, 134.25, 132.80],
                            'low' => [129.75, 130.80, 128.90],
                            'close' => [133.41, 132.15, 131.25],
                            'volume' => [85467200, 78945600, 92341500]
                        ]]
                    ]
                ]]
            ]
        ]);

        $this->mockHandler->append(
            new Response(200, ['Content-Type' => 'application/json'], $mockResponseBody)
        );

        $result = $this->yahooFinance->fetchHistoricalData('AAPL', '2023-01-01', '2023-01-03');

        $this->assertIsArray($result);
        $this->assertEquals('AAPL', $result['symbol']);
        $this->assertEquals(3, $result['count']);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(3, $result['data']);

        // Verify data structure
        $firstRecord = $result['data'][0];
        $this->assertArrayHasKey('Date', $firstRecord);
        $this->assertArrayHasKey('Open', $firstRecord);
        $this->assertArrayHasKey('High', $firstRecord);
        $this->assertArrayHasKey('Low', $firstRecord);
        $this->assertArrayHasKey('Close', $firstRecord);
        $this->assertArrayHasKey('Adj Close', $firstRecord);
        $this->assertArrayHasKey('Volume', $firstRecord);

        // Verify data values
        $this->assertEquals(130.25, $firstRecord['Open']);
        $this->assertEquals(135.50, $firstRecord['High']);
        $this->assertEquals(129.75, $firstRecord['Low']);
        $this->assertEquals(133.41, $firstRecord['Close']);
        $this->assertEquals(85467200, $firstRecord['Volume']);
    }

    public function testFetchHistoricalDataInvalidSymbol()
    {
        // Mock Yahoo Finance response for invalid symbol
        $mockResponseBody = json_encode([
            'chart' => [
                'result' => null,
                'error' => ['code' => 'Not Found', 'description' => 'Symbol not found']
            ]
        ]);

        $this->mockHandler->append(
            new Response(200, ['Content-Type' => 'application/json'], $mockResponseBody)
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No data returned from Yahoo Finance');

        $this->yahooFinance->fetchHistoricalData('INVALID', '2023-01-01', '2023-01-03');
    }

    public function testFetchHistoricalDataInvalidDateFormat()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid date format');

        $this->yahooFinance->fetchHistoricalData('AAPL', 'invalid-date', '2023-01-03');
    }

    public function testFetchHistoricalDataNetworkError()
    {
        $this->mockHandler->append(
            new RequestException('Network error', new Request('GET', 'test'))
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('HTTP request failed');

        $this->yahooFinance->fetchHistoricalData('AAPL', '2023-01-01', '2023-01-03');
    }

    public function testSaveToCSVWithValidData()
    {
        $testData = [
            'symbol' => 'TEST',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-03',
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
            ],
            'count' => 2
        ];

        $csvPath = $this->yahooFinance->saveToCSV($testData);

        $this->assertFileExists($csvPath);
        $this->assertStringContainsString('TEST_2023-01-01_to_2023-01-03.csv', $csvPath);

        // Verify CSV content
        $csvContent = file_get_contents($csvPath);
        $this->assertStringContainsString('Date,Open,High,Low,Close,Adj Close,Volume', $csvContent);
        $this->assertStringContainsString('2023-01-01,100,105,99,103.5,103.5,1000000', $csvContent);
        $this->assertStringContainsString('2023-01-02,103.5,106,102,104.75,104.75,1200000', $csvContent);

        // Clean up
        unlink($csvPath);
    }

    public function testSaveToCSVWithCustomFilename()
    {
        $testData = [
            'symbol' => 'TEST',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-03',
            'data' => [
                [
                    'Date' => '2023-01-01',
                    'Open' => 100.00,
                    'High' => 105.00,
                    'Low' => 99.00,
                    'Close' => 103.50,
                    'Adj Close' => 103.50,
                    'Volume' => 1000000
                ]
            ],
            'count' => 1
        ];

        $customFilename = 'custom_test_data.csv';
        $csvPath = $this->yahooFinance->saveToCSV($testData, $customFilename);

        $this->assertFileExists($csvPath);
        $this->assertStringContainsString($customFilename, $csvPath);

        // Clean up
        unlink($csvPath);
    }

    public function testTestConnectionSuccess()
    {
        $this->mockHandler->append(
            new Response(200, [], 'Yahoo Finance homepage')
        );

        $result = $this->yahooFinance->testConnection();
        $this->assertTrue($result);
    }

    public function testTestConnectionFailure()
    {
        $this->mockHandler->append(
            new RequestException('Connection failed', new Request('GET', 'test'))
        );

        $result = $this->yahooFinance->testConnection();
        $this->assertFalse($result);
    }

    public function testParseYahooDataWithMissingValues()
    {
        // Mock response with some missing/null values
        $mockResponseBody = json_encode([
            'chart' => [
                'result' => [[
                    'meta' => ['symbol' => 'AAPL'],
                    'timestamp' => [1672531200, 1672617600, 1672704000],
                    'indicators' => [
                        'quote' => [[
                            'open' => [130.25, null, 129.75], // null value in middle
                            'high' => [135.50, 134.25, 132.80],
                            'low' => [129.75, 130.80, 128.90],
                            'close' => [133.41, 132.15, 131.25],
                            'volume' => [85467200, 78945600, 92341500]
                        ]]
                    ]
                ]]
            ]
        ]);

        $this->mockHandler->append(
            new Response(200, ['Content-Type' => 'application/json'], $mockResponseBody)
        );

        $result = $this->yahooFinance->fetchHistoricalData('AAPL', '2023-01-01', '2023-01-03');

        // Should skip the record with null open value
        $this->assertEquals(2, $result['count']);
        $this->assertCount(2, $result['data']);
    }

    public function testDateRangeValidation()
    {
        // Test end date before start date
        $this->expectException(Exception::class);
        
        // This should fail at the strtotime level or in validation
        $startDate = '2023-12-31';
        $endDate = '2023-01-01';
        
        // Mock a response to ensure we get to the validation
        $this->mockHandler->append(
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'chart' => ['result' => [[]]]
            ]))
        );
        
        // The specific validation depends on implementation
        // This test ensures invalid date ranges are handled
        $result = $this->yahooFinance->fetchHistoricalData('AAPL', $startDate, $endDate);
    }

    public function testDataDirectoryCreation()
    {
        $testData = [
            'symbol' => 'DIRTEST',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-01',
            'data' => [
                [
                    'Date' => '2023-01-01',
                    'Open' => 100.00,
                    'High' => 100.00,
                    'Low' => 100.00,
                    'Close' => 100.00,
                    'Adj Close' => 100.00,
                    'Volume' => 1000
                ]
            ],
            'count' => 1
        ];

        // Remove data directory if it exists
        $dataDir = __DIR__ . '/../../data/csv';
        if (is_dir($dataDir)) {
            array_map('unlink', glob("$dataDir/*"));
            rmdir($dataDir);
        }

        $csvPath = $this->yahooFinance->saveToCSV($testData);

        $this->assertDirectoryExists($dataDir);
        $this->assertFileExists($csvPath);

        // Clean up
        unlink($csvPath);
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