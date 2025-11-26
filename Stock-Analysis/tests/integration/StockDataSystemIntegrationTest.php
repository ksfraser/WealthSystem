<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../YahooFinancePhp.php';
require_once __DIR__ . '/../../StockDataService.php';

/**
 * Integration tests for Stock Data System
 * Tests component interactions and end-to-end workflows
 */
class StockDataSystemIntegrationTest extends TestCase
{
    private $tempDataDir;
    private $yahooFinance;
    private $stockDataService;

    protected function setUp(): void
    {
        // Create temporary data directory for tests
        $this->tempDataDir = sys_get_temp_dir() . '/stock_data_test_' . uniqid();
        if (!is_dir($this->tempDataDir)) {
            mkdir($this->tempDataDir, 0755, true);
        }

        // Initialize components
        $this->yahooFinance = new YahooFinancePhp();
        $this->stockDataService = new StockDataService(false);
    }

    public function testYahooFinanceToStockDataServiceIntegration()
    {
        // Skip if no internet connection (for CI/CD environments)
        if (!$this->hasInternetConnection()) {
            $this->markTestSkipped('No internet connection available for Yahoo Finance API testing');
        }

        // Test with a reliable, large-cap stock
        $symbol = 'AAPL';
        $startDate = '2023-01-01';
        $endDate = '2023-01-03'; // Short range for quick test

        try {
            // Test direct Yahoo Finance PHP class
            $yahooResult = $this->yahooFinance->fetchHistoricalData($symbol, $startDate, $endDate);
            
            $this->assertIsArray($yahooResult);
            $this->assertEquals($symbol, $yahooResult['symbol']);
            $this->assertGreaterThan(0, $yahooResult['count']);
            $this->assertArrayHasKey('data', $yahooResult);

            // Test StockDataService wrapper
            $serviceResult = $this->stockDataService->fetchHistoricalData($symbol, $startDate, $endDate);
            $serviceData = json_decode($serviceResult, true);

            $this->assertJson($serviceResult);
            $this->assertTrue($serviceData['success']);
            $this->assertEquals($symbol, $serviceData['symbol']);
            $this->assertGreaterThan(0, $serviceData['total_records']);
            $this->assertEquals('PHP_YahooFinance', $serviceData['source']);

            // Verify data consistency between components
            $this->assertEquals($yahooResult['count'], $serviceData['total_records']);
            $this->assertEquals($yahooResult['start_date'], $serviceData['start_date']);
            $this->assertEquals($yahooResult['end_date'], $serviceData['end_date']);

        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'HTTP request failed') !== false) {
                $this->markTestSkipped('Yahoo Finance API unavailable: ' . $e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function testCSVGenerationIntegration()
    {
        // Create mock data for CSV testing
        $mockData = [
            'symbol' => 'TEST',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-03',
            'count' => 3,
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
                ],
                [
                    'Date' => '2023-01-03',
                    'Open' => 104.75,
                    'High' => 107.00,
                    'Low' => 103.50,
                    'Close' => 106.25,
                    'Adj Close' => 106.25,
                    'Volume' => 980000
                ]
            ]
        ];

        // Test CSV generation through YahooFinancePhp
        $csvPath = $this->yahooFinance->saveToCSV($mockData);
        
        $this->assertFileExists($csvPath);
        $this->assertStringContainsString('TEST_2023-01-01_to_2023-01-03.csv', basename($csvPath));

        // Verify CSV content
        $csvContent = file_get_contents($csvPath);
        $lines = explode("\n", trim($csvContent));
        
        $this->assertCount(4, $lines); // Header + 3 data rows
        $this->assertEquals('Date,Open,High,Low,Close,Adj Close,Volume', $lines[0]);
        $this->assertStringContainsString('2023-01-01,100,105,99,103.5,103.5,1000000', $lines[1]);
        $this->assertStringContainsString('2023-01-02,103.5,106,102,104.75,104.75,1200000', $lines[2]);
        $this->assertStringContainsString('2023-01-03,104.75,107,103.5,106.25,106.25,980000', $lines[3]);

        // Test CSV generation through StockDataService
        $jsonData = json_encode([
            'success' => true,
            'symbol' => 'TEST2',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-03',
            'total_records' => 3,
            'data' => $mockData['data']
        ]);

        $serviceCsvPath = $this->stockDataService->saveToCSV($jsonData);
        
        $this->assertFileExists($serviceCsvPath);
        
        // Verify both CSV files have the same data content
        $originalCsvLines = explode("\n", trim(file_get_contents($csvPath)));
        $serviceCsvLines = explode("\n", trim(file_get_contents($serviceCsvPath)));
        
        $this->assertEquals($originalCsvLines[0], $serviceCsvLines[0]); // Same header
        $this->assertCount(count($originalCsvLines), $serviceCsvLines); // Same number of lines

        // Clean up
        unlink($csvPath);
        unlink($serviceCsvPath);
    }

    public function testDataChunkingIntegration()
    {
        $symbol = 'AAPL';
        $startDate = '2020-01-01';
        $endDate = '2022-12-31';
        $chunkMonths = 12;

        // Test chunking functionality
        $chunks = $this->stockDataService->getChunkedData($symbol, $startDate, $endDate, $chunkMonths);

        $this->assertIsArray($chunks);
        $this->assertGreaterThan(0, count($chunks));

        // Verify chunk structure
        foreach ($chunks as $index => $chunk) {
            $this->assertArrayHasKey('start', $chunk);
            $this->assertArrayHasKey('end', $chunk);
            
            $startDateTime = new DateTime($chunk['start']);
            $endDateTime = new DateTime($chunk['end']);
            
            $this->assertLessThanOrEqual($endDateTime, $startDateTime->add(new DateInterval('P13M')));
            
            // If not the last chunk, verify no gaps
            if ($index < count($chunks) - 1) {
                $nextChunk = $chunks[$index + 1];
                $currentEndDate = new DateTime($chunk['end']);
                $nextStartDate = new DateTime($nextChunk['start']);
                
                // Next chunk should start the day after current chunk ends
                $expectedNextStart = clone $currentEndDate;
                $expectedNextStart->add(new DateInterval('P1D'));
                
                $this->assertEquals($expectedNextStart->format('Y-m-d'), $nextStartDate->format('Y-m-d'));
            }
        }

        // Test date range coverage
        $firstChunk = reset($chunks);
        $lastChunk = end($chunks);
        
        $this->assertEquals($startDate, $firstChunk['start']);
        $this->assertEquals($endDate, $lastChunk['end']);
    }

    public function testErrorHandlingIntegration()
    {
        // Test invalid symbol error handling through service layer
        $invalidSymbol = 'INVALID_SYMBOL_12345';
        
        try {
            $result = $this->stockDataService->fetchHistoricalData($invalidSymbol, '2023-01-01', '2023-01-03');
            $data = json_decode($result, true);
            
            $this->assertJson($result);
            $this->assertFalse($data['success']);
            $this->assertEquals($invalidSymbol, $data['symbol']);
            $this->assertArrayHasKey('error', $data);
            $this->assertEquals('PHP_YahooFinance', $data['source']);
            
        } catch (Exception $e) {
            // If Yahoo Finance throws an exception instead of returning error data,
            // verify the service layer catches it properly
            $this->assertStringContainsString('Yahoo Finance', $e->getMessage());
        }

        // Test invalid date format
        try {
            $result = $this->stockDataService->fetchHistoricalData('AAPL', 'invalid-date', '2023-01-03');
            $data = json_decode($result, true);
            
            $this->assertFalse($data['success']);
            $this->assertArrayHasKey('error', $data);
            
        } catch (Exception $e) {
            $this->assertStringContainsString('date', strtolower($e->getMessage()));
        }
    }

    public function testConnectionTestingIntegration()
    {
        // Test connection testing through both components
        $yahooConnection = $this->yahooFinance->testConnection();
        $serviceConnection = $this->stockDataService->testConnection();

        // Both should return boolean values
        $this->assertIsBool($yahooConnection);
        $this->assertIsBool($serviceConnection);

        // If one succeeds, the other should too (they use the same underlying connection)
        if ($yahooConnection) {
            $this->assertTrue($serviceConnection);
        } else {
            $this->assertFalse($serviceConnection);
        }
    }

    public function testDataFormatConsistency()
    {
        // Test that data format is consistent across components
        $mockYahooData = [
            'symbol' => 'CONSISTENCY_TEST',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-01',
            'count' => 1,
            'data' => [
                [
                    'Date' => '2023-01-01',
                    'Open' => 150.25,
                    'High' => 152.80,
                    'Low' => 149.50,
                    'Close' => 151.75,
                    'Adj Close' => 151.75,
                    'Volume' => 25000000
                ]
            ]
        ];

        // Convert through service layer to JSON format
        $serviceJsonData = json_encode([
            'success' => true,
            'symbol' => 'CONSISTENCY_TEST',
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-01',
            'total_records' => 1,
            'data' => $mockYahooData['data'],
            'source' => 'PHP_YahooFinance'
        ]);

        // Generate CSV from both sources
        $yahooCsv = $this->yahooFinance->saveToCSV($mockYahooData);
        $serviceCsv = $this->stockDataService->saveToCSV($serviceJsonData);

        // Compare CSV content
        $yahooContent = file_get_contents($yahooCsv);
        $serviceContent = file_get_contents($serviceCsv);

        // Headers should be identical
        $yahooLines = explode("\n", trim($yahooContent));
        $serviceLines = explode("\n", trim($serviceContent));

        $this->assertEquals($yahooLines[0], $serviceLines[0]); // Headers match
        $this->assertCount(count($yahooLines), $serviceLines); // Same number of lines

        // Data precision should be maintained
        $this->assertStringContainsString('150.25', $yahooContent);
        $this->assertStringContainsString('152.8', $yahooContent);
        $this->assertStringContainsString('25000000', $yahooContent);

        // Clean up
        unlink($yahooCsv);
        unlink($serviceCsv);
    }

    private function hasInternetConnection(): bool
    {
        // Simple internet connectivity check
        $connected = @fsockopen("www.google.com", 80, $errno, $errstr, 5);
        if ($connected) {
            fclose($connected);
            return true;
        }
        return false;
    }

    protected function tearDown(): void
    {
        // Clean up temporary directory
        if (is_dir($this->tempDataDir)) {
            $files = glob($this->tempDataDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDataDir);
        }

        // Clean up any CSV files created during testing
        $dataDir = __DIR__ . '/../../data/csv';
        if (is_dir($dataDir)) {
            $testFiles = glob("$dataDir/TEST*.csv");
            $testFiles = array_merge($testFiles, glob("$dataDir/CONSISTENCY_TEST*.csv"));
            foreach ($testFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
}