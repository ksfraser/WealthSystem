<?php

declare(strict_types=1);

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\PdfExportService;

/**
 * Test suite for PdfExportService
 * 
 * Tests PDF generation for:
 * - Sector analysis reports
 * - Index benchmark reports
 * - Advanced charts reports
 * 
 * @covers \App\Services\PdfExportService
 */
class PdfExportServiceTest extends TestCase
{
    private PdfExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PdfExportService();
    }

    /**
     * @test
     */
    public function itGeneratesSectorAnalysisPdf(): void
    {
        // Arrange
        $userId = 1;
        $sectorData = [
            'sectors' => [
                ['name' => 'Technology', 'percentage' => 45.5, 'value' => 45500],
                ['name' => 'Healthcare', 'percentage' => 30.2, 'value' => 30200],
                ['name' => 'Finance', 'percentage' => 24.3, 'value' => 24300]
            ],
            'total_value' => 100000,
            'hhi' => 3500
        ];
        
        // Act
        $result = $this->service->generateSectorAnalysisPdf($userId, $sectorData);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('mime_type', $result);
        $this->assertEquals('application/pdf', $result['mime_type']);
        $this->assertStringStartsWith('%PDF', $result['content'], 'Should return valid PDF content');
        $this->assertGreaterThan(500, strlen($result['content']), 'PDF should have substantial content');
    }

    /**
     * @test
     */
    public function itGeneratesIndexBenchmarkPdf(): void
    {
        // Arrange
        $userId = 1;
        $benchmarkData = [
            'portfolio' => ['return' => 15.5, 'volatility' => 18.2, 'sharpe' => 0.85],
            'sp500' => ['return' => 12.3, 'volatility' => 16.5, 'sharpe' => 0.75],
            'comparison' => ['alpha' => 3.2, 'beta' => 1.05]
        ];
        
        // Act
        $result = $this->service->generateIndexBenchmarkPdf($userId, $benchmarkData);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertStringStartsWith('%PDF', $result['content']);
    }

    /**
     * @test
     */
    public function itGeneratesAdvancedChartsPdf(): void
    {
        // Arrange
        $userId = 1;
        $chartData = [
            'symbols' => ['AAPL', 'MSFT', 'GOOGL'],
            'correlation_data' => [
                ['symbol1' => 'AAPL', 'symbol2' => 'MSFT', 'correlation' => 0.85],
                ['symbol1' => 'AAPL', 'symbol2' => 'GOOGL', 'correlation' => 0.72]
            ],
            'hhi' => 2800
        ];
        
        // Act
        $result = $this->service->generateAdvancedChartsPdf($userId, $chartData);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertStringStartsWith('%PDF', $result['content']);
    }

    /**
     * @test
     */
    public function itHandlesMissingDataGracefully(): void
    {
        // Arrange
        $userId = 999;
        $sectorData = ['sectors' => [], 'total_value' => 0];
        
        // Act
        $result = $this->service->generateSectorAnalysisPdf($userId, $sectorData);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertStringStartsWith('%PDF', $result['content']);
    }

    /**
     * @test
     */
    public function itIncludesMetadataInPdf(): void
    {
        // Arrange
        $userId = 1;
        $sectorData = [
            'sectors' => [['name' => 'Tech', 'percentage' => 100, 'value' => 1000]],
            'total_value' => 1000
        ];
        
        // Act
        $result = $this->service->generateSectorAnalysisPdf($userId, $sectorData);
        
        // Assert
        $this->assertArrayHasKey('generation_date', $result);
        $this->assertArrayHasKey('mime_type', $result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertEquals('application/pdf', $result['mime_type']);
    }

    /**
     * @test
     */
    public function itFormatsNumbersCorrectly(): void
    {
        // Arrange
        $userId = 1;
        $sectorData = [
            'sectors' => [
                ['name' => 'Tech', 'percentage' => 45.567, 'value' => 12345.67]
            ],
            'total_value' => 12345.67
        ];
        
        // Act
        $result = $this->service->generateSectorAnalysisPdf($userId, $sectorData);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertStringStartsWith('%PDF', $result['content']);
    }

    /**
     * @test
     */
    public function itHandlesLargeDatasets(): void
    {
        // Arrange
        $userId = 1;
        $sectors = [];
        for ($i = 0; $i < 20; $i++) {
            $sectors[] = [
                'name' => "Sector $i",
                'percentage' => 5.0,
                'value' => 5000
            ];
        }
        
        $sectorData = [
            'sectors' => $sectors,
            'total_value' => 100000
        ];
        
        // Act
        $result = $this->service->generateSectorAnalysisPdf($userId, $sectorData);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertGreaterThan(500, strlen($result['content']));
    }

    /**
     * @test
     */
    public function itGeneratesPdfWithCorrectContentType(): void
    {
        // Arrange
        $userId = 1;
        $sectorData = ['sectors' => [], 'total_value' => 0];
        
        // Act
        $result = $this->service->generateSectorAnalysisPdf($userId, $sectorData);
        
        // Assert
        $this->assertArrayHasKey('content', $result);
        $this->assertStringStartsWith('%PDF-', $result['content']);
        $this->assertStringContainsString('%%EOF', $result['content']);
    }
}
