<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ExcelExportService;
use App\DAO\SectorAnalysisDAO;

/**
 * Excel Export Service Test
 * 
 * Tests for Excel export functionality using PhpSpreadsheet
 */
class ExcelExportServiceTest extends TestCase
{
    private ExcelExportService $service;
    private $mockDao;
    
    protected function setUp(): void
    {
        $this->mockDao = $this->createMock(SectorAnalysisDAO::class);
        $this->service = new ExcelExportService($this->mockDao);
    }
    
    public function testServiceInstantiation(): void
    {
        $this->assertInstanceOf(ExcelExportService::class, $this->service);
    }
    
    public function testGenerateSectorAnalysisWorkbook(): void
    {
        $userId = 1;
        $sectorData = [
            'Technology' => [
                'weight' => 45.5,
                'return' => 12.5,
                'volatility' => 15.2,
                'sharpe' => 0.82
            ],
            'Healthcare' => [
                'weight' => 25.0,
                'return' => 8.3,
                'volatility' => 10.5,
                'sharpe' => 0.79
            ],
        ];
        
        $result = $this->service->generateSectorAnalysisWorkbook($userId, $sectorData);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('mime_type', $result);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $result['mime_type']);
        $this->assertStringContainsString('sector_analysis', $result['filename']);
    }
    
    public function testGenerateIndexBenchmarkWorkbook(): void
    {
        $userId = 1;
        $benchmarkData = [
            'portfolio_return' => 15.5,
            'sp500_return' => 12.3,
            'outperformance' => 3.2,
            'alpha' => 2.8,
            'beta' => 1.05,
            'tracking_error' => 4.5,
            'information_ratio' => 0.71
        ];
        
        $result = $this->service->generateIndexBenchmarkWorkbook($userId, $benchmarkData);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertStringContainsString('index_benchmark', $result['filename']);
    }
    
    public function testGenerateAdvancedChartsWorkbook(): void
    {
        $userId = 1;
        $chartData = [
            'correlation' => [
                ['Technology', 'Healthcare', 0.65],
                ['Technology', 'Finance', 0.45],
                ['Healthcare', 'Finance', 0.32],
            ],
            'concentration' => [
                '2024-01-01' => 2345,
                '2024-02-01' => 2280,
                '2024-03-01' => 2150,
            ],
        ];
        
        $result = $this->service->generateAdvancedChartsWorkbook($userId, $chartData);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertStringContainsString('advanced_charts', $result['filename']);
    }
    
    public function testWorkbookHasMultipleSheets(): void
    {
        $userId = 1;
        $sectorData = [
            'Technology' => ['weight' => 50, 'return' => 10, 'volatility' => 15, 'sharpe' => 0.67],
            'Healthcare' => ['weight' => 50, 'return' => 8, 'volatility' => 12, 'sharpe' => 0.67],
        ];
        
        $result = $this->service->generateSectorAnalysisWorkbook($userId, $sectorData);
        $sheets = $result['sheets'] ?? [];
        
        $this->assertIsArray($sheets);
        $this->assertContains('Summary', $sheets);
        $this->assertContains('Sector Details', $sheets);
    }
    
    public function testExcelHasProperFormatting(): void
    {
        $userId = 1;
        $sectorData = [
            'Technology' => ['weight' => 45.5, 'return' => 12.5, 'volatility' => 15.2, 'sharpe' => 0.82],
        ];
        
        $result = $this->service->generateSectorAnalysisWorkbook($userId, $sectorData);
        
        $this->assertArrayHasKey('formatting', $result);
        $formatting = $result['formatting'];
        $this->assertTrue($formatting['bold_headers']);
        $this->assertTrue($formatting['auto_width']);
        $this->assertTrue($formatting['borders']);
    }
    
    public function testEmptyDataHandling(): void
    {
        $userId = 1;
        $emptyData = [];
        
        $result = $this->service->generateSectorAnalysisWorkbook($userId, $emptyData);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('filename', $result);
        // Should still generate a workbook with headers
        $this->assertNotEmpty($result['content']);
    }
}
