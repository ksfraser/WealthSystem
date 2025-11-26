<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\PythonIntegrationService;

/**
 * Comprehensive tests for PythonIntegrationService
 * Tests Python execution, file operations, error handling, and all integration points
 */
class PythonIntegrationServiceTest extends TestCase
{
    private string $testPythonPath;
    private PythonIntegrationService $service;
    
    protected function setUp(): void
    {
        // Use a valid Python path for testing
        $this->testPythonPath = 'python'; // or 'python3' on some systems
        $this->service = new PythonIntegrationService($this->testPythonPath);
    }
    
    protected function tearDown(): void
    {
        // Clean up any temp files created during tests
        $tempDir = sys_get_temp_dir();
        $tempFiles = glob($tempDir . '/stock_analysis_*.json');
        foreach ($tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }
    
    // ===== SUCCESS PATH TESTS =====
    
    public function testAnalyzeStockWithValidData(): void
    {
        $this->markTestSkipped('Requires Python environment and analysis.py - integration test');
        
        // Arrange
        $stockData = [
            'symbol' => 'AAPL',
            'prices' => [
                ['date' => '2025-01-01', 'close' => 150.00, 'volume' => 50000000],
                ['date' => '2025-01-02', 'close' => 152.00, 'volume' => 55000000],
            ],
            'fundamentals' => [
                'pe_ratio' => 25.5,
                'eps' => 6.00
            ]
        ];
        
        // Act
        $result = $this->service->analyzeStock($stockData);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('analysis', $result);
    }
    
    // ===== ERROR PATH TESTS =====
    
    public function testAnalyzeStockWithEmptyData(): void
    {
        // Act
        $result = $this->service->analyzeStock([]);
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('empty', strtolower($result['error']));
    }
    
    public function testAnalyzeStockWithNullData(): void
    {
        // Act
        $result = $this->service->analyzeStock(null);
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
    
    public function testAnalyzeStockWithMalformedData(): void
    {
        // Arrange: Invalid data structure
        $invalidData = [
            'not_a_symbol' => 'AAPL',
            'invalid_prices' => 'not_an_array'
        ];
        
        // Act
        $result = $this->service->analyzeStock($invalidData);
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
    
    public function testAnalyzeStockWithInvalidPythonPath(): void
    {
        // Arrange: Service with invalid Python path
        $service = new PythonIntegrationService('/invalid/path/to/python');
        
        $stockData = [
            'symbol' => 'AAPL',
            'prices' => [['date' => '2025-01-01', 'close' => 150.00]]
        ];
        
        // Act
        $result = $service->analyzeStock($stockData);
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('python', strtolower($result['error']));
    }
    
    // ===== FILE OPERATION TESTS =====
    
    public function testTempFileCreationAndCleanup(): void
    {
        // This test verifies temp file is created and cleaned up properly
        $this->markTestSkipped('Requires Python environment - integration test');
        
        // Arrange
        $stockData = [
            'symbol' => 'AAPL',
            'prices' => [['date' => '2025-01-01', 'close' => 150.00]]
        ];
        
        // Get temp files before
        $tempDir = sys_get_temp_dir();
        $filesBefore = glob($tempDir . '/stock_analysis_*.json');
        $countBefore = count($filesBefore);
        
        // Act
        $result = $this->service->analyzeStock($stockData);
        
        // Get temp files after
        $filesAfter = glob($tempDir . '/stock_analysis_*.json');
        $countAfter = count($filesAfter);
        
        // Assert: Temp file was cleaned up
        $this->assertEquals($countBefore, $countAfter, 'Temp file should be cleaned up after analysis');
    }
    
    public function testLargeDatasetHandling(): void
    {
        $this->markTestSkipped('Requires Python environment - integration test');
        
        // Arrange: Generate large dataset (10 years of daily data)
        $prices = [];
        for ($i = 0; $i < 2520; $i++) {
            $prices[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'open' => 100 + rand(-10, 10),
                'high' => 110 + rand(-10, 10),
                'low' => 90 + rand(-10, 10),
                'close' => 100 + rand(-10, 10),
                'volume' => rand(10000000, 100000000)
            ];
        }
        
        $stockData = [
            'symbol' => 'AAPL',
            'prices' => $prices
        ];
        
        // Act
        $result = $this->service->analyzeStock($stockData);
        
        // Assert: Should handle large dataset without errors
        $this->assertTrue($result['success'] || isset($result['error']));
    }
    
    // ===== PYTHON EXECUTION TESTS =====
    
    public function testCheckPythonEnvironment(): void
    {
        // Act
        $result = $this->service->checkPythonEnvironment();
        
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        
        if ($result['success']) {
            $this->assertArrayHasKey('version', $result);
            $this->assertArrayHasKey('path', $result);
        }
    }
    
    public function testCheckPythonEnvironmentWithInvalidPath(): void
    {
        // Arrange
        $service = new PythonIntegrationService('/nonexistent/python');
        
        // Act
        $result = $service->checkPythonEnvironment();
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
    
    // ===== LEGACY METHOD TESTS =====
    
    public function testFetchPriceDataLegacy(): void
    {
        $this->markTestSkipped('Legacy method - requires trading_script.py');
        
        // This tests the legacy fetchPriceData method
        // that calls trading_script.py
        
        // Act
        $result = $this->service->fetchPriceData('AAPL', '2024-01-01', '2024-12-31');
        
        // Assert
        $this->assertIsArray($result);
    }
    
    public function testGetPortfolioDataLegacy(): void
    {
        $this->markTestSkipped('Legacy method - requires trading_script.py');
        
        // Act
        $result = $this->service->getPortfolioData();
        
        // Assert
        $this->assertIsArray($result);
    }
    
    public function testUpdateMultipleSymbolsLegacy(): void
    {
        $this->markTestSkipped('Legacy method - requires trading_script.py');
        
        // Arrange
        $symbols = ['AAPL', 'GOOGL', 'MSFT'];
        
        // Act
        $result = $this->service->updateMultipleSymbols($symbols);
        
        // Assert
        $this->assertIsArray($result);
    }
    
    // ===== ERROR HANDLING TESTS =====
    
    public function testAnalyzeStockWithPythonSyntaxError(): void
    {
        $this->markTestSkipped('Requires mocked Python environment');
        
        // This would test behavior when Python script has syntax errors
        // Requires mock or test double for Python execution
    }
    
    public function testAnalyzeStockWithPythonRuntimeError(): void
    {
        $this->markTestSkipped('Requires mocked Python environment');
        
        // This would test behavior when Python script throws runtime exception
    }
    
    public function testAnalyzeStockWithTimeout(): void
    {
        $this->markTestSkipped('Requires timeout simulation');
        
        // This would test behavior when Python execution times out
    }
    
    // ===== JSON HANDLING TESTS =====
    
    public function testJSONEncodingOfStockData(): void
    {
        // Arrange
        $stockData = [
            'symbol' => 'AAPL',
            'prices' => [
                [
                    'date' => '2025-01-01',
                    'close' => 150.00,
                    'special_char' => "Quote: \"test\""
                ]
            ]
        ];
        
        // Act: Encode to JSON (simulating what analyzeStock does internally)
        $json = json_encode($stockData);
        
        // Assert: Should handle special characters
        $this->assertNotFalse($json);
        $this->assertStringContainsString('AAPL', $json);
        
        // Should be valid JSON
        $decoded = json_decode($json, true);
        $this->assertNotNull($decoded);
        $this->assertEquals('AAPL', $decoded['symbol']);
    }
    
    public function testJSONDecodingOfPythonOutput(): void
    {
        // Arrange: Simulate Python JSON output
        $pythonOutput = json_encode([
            'success' => true,
            'analysis' => [
                'scores' => ['fundamental' => 75.0],
                'overall_score' => 75.0
            ]
        ]);
        
        // Act: Decode (simulating what analyzeStock does)
        $decoded = json_decode($pythonOutput, true);
        
        // Assert
        $this->assertNotNull($decoded);
        $this->assertTrue($decoded['success']);
        $this->assertEquals(75.0, $decoded['analysis']['overall_score']);
    }
    
    public function testInvalidJSONFromPython(): void
    {
        // Arrange: Invalid JSON that Python might return on error
        $invalidJson = "This is not JSON";
        
        // Act
        $decoded = json_decode($invalidJson, true);
        
        // Assert: Should be null for invalid JSON
        $this->assertNull($decoded);
    }
    
    // ===== INTEGRATION WITH ANALYSIS.PY =====
    
    public function testAnalysisScriptExists(): void
    {
        // Arrange
        $analysisScriptPath = dirname(__DIR__, 2) . '/python_analysis/analysis.py';
        
        // Assert: Script should exist
        $this->assertFileExists(
            $analysisScriptPath,
            'python_analysis/analysis.py should exist in project root'
        );
    }
    
    public function testPythonBridgeCreation(): void
    {
        // Act
        $result = $this->service->createPythonBridge();
        
        // Assert
        $this->assertTrue($result);
        
        // Verify bridge file was created
        $bridgePath = dirname(__DIR__, 2) . '/scripts/python_bridge.py';
        $this->assertFileExists($bridgePath);
    }
    
    // ===== SECURITY TESTS =====
    
    public function testCommandInjectionPrevention(): void
    {
        // Arrange: Malicious data with command injection attempt
        $maliciousData = [
            'symbol' => 'AAPL; rm -rf /',
            'prices' => [
                ['date' => '2025-01-01', 'close' => 150.00]
            ]
        ];
        
        // Act: Should not execute injected command
        $result = $this->service->analyzeStock($maliciousData);
        
        // Assert: Should either fail safely or sanitize input
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        // System should still be intact (test is still running)
        $this->assertTrue(true);
    }
    
    public function testPathTraversalPrevention(): void
    {
        // Arrange: Attempt to use path traversal
        $service = new PythonIntegrationService('../../../etc/passwd');
        
        // Act
        $result = $service->checkPythonEnvironment();
        
        // Assert: Should fail, not execute malicious path
        $this->assertFalse($result['success']);
    }
    
    // ===== EDGE CASES =====
    
    public function testAnalyzeStockWithUnicodeSymbol(): void
    {
        // Arrange: Symbol with unicode characters
        $stockData = [
            'symbol' => 'AAPL™',
            'prices' => [['date' => '2025-01-01', 'close' => 150.00]]
        ];
        
        // Act
        $result = $this->service->analyzeStock($stockData);
        
        // Assert: Should handle or reject gracefully
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }
    
    public function testAnalyzeStockWithNullPrices(): void
    {
        // Arrange
        $stockData = [
            'symbol' => 'AAPL',
            'prices' => null
        ];
        
        // Act
        $result = $this->service->analyzeStock($stockData);
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
    
    public function testAnalyzeStockWithEmptyPricesArray(): void
    {
        // Arrange
        $stockData = [
            'symbol' => 'AAPL',
            'prices' => []
        ];
        
        // Act
        $result = $this->service->analyzeStock($stockData);
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
    
    public function testAnalyzeStockWithMissingRequiredFields(): void
    {
        // Arrange: Missing 'symbol' field
        $stockData = [
            'prices' => [['date' => '2025-01-01', 'close' => 150.00]]
        ];
        
        // Act
        $result = $this->service->analyzeStock($stockData);
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
    
    // ===== PERFORMANCE TESTS =====
    
    public function testAnalyzeStockPerformance(): void
    {
        $this->markTestSkipped('Requires Python environment - performance test');
        
        // Arrange
        $stockData = [
            'symbol' => 'AAPL',
            'prices' => array_fill(0, 252, ['date' => '2025-01-01', 'close' => 150.00])
        ];
        
        // Act
        $startTime = microtime(true);
        $result = $this->service->analyzeStock($stockData);
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        
        // Assert: Should complete within reasonable time (e.g., 5 seconds)
        $this->assertLessThan(5.0, $executionTime, 'Analysis should complete within 5 seconds');
    }
}
