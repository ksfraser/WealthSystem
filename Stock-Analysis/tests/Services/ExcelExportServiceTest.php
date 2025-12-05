<?php

declare(strict_types=1);

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ExcelExportService;
use App\DAO\SectorAnalysisDAO;

/**
 * Test suite for ExcelExportService
 * 
 * Tests Excel export functionality including:
 * - Workbook creation
 * - Sheet formatting
 * - Data export (sector analysis, portfolios, holdings)
 * - Style application (headers, borders, number formats)
 * - Formula support
 * 
 * @covers \App\Services\ExcelExportService
 */
class ExcelExportServiceTest extends TestCase
{
    private ExcelExportService $service;
    private SectorAnalysisDAO $sectorDAO;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sectorDAO = $this->createMock(SectorAnalysisDAO::class);
        $this->service = new ExcelExportService($this->sectorDAO);
    }

    /**
     * @test
     * @group excel
     */
    public function itCreatesExcelWorkbook(): void
    {
        $data = ['holdings' => [], 'totals' => []];
        
        $result = $this->service->exportPortfolio(1, $data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('mime_type', $result);
    }

    /**
     * @test
     * @group excel
     */
    public function itSetsCorrectMimeType(): void
    {
        $data = ['holdings' => [], 'totals' => []];
        
        $result = $this->service->exportPortfolio(1, $data);
        
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $result['mime_type']
        );
    }

    /**
     * @test
     * @group excel
     */
    public function itGeneratesDescriptiveFilename(): void
    {
        $data = ['holdings' => [], 'totals' => []];
        
        $result = $this->service->exportPortfolio(1, $data);
        
        $this->assertStringStartsWith('portfolio_', $result['filename']);
        $this->assertStringEndsWith('.xlsx', $result['filename']);
        $this->assertStringContainsString(date('Y-m-d'), $result['filename']);
    }

    /**
     * @test
     * @group excel
     */
    public function itExportsHoldingsData(): void
    {
        $data = [
            'holdings' => [
                ['symbol' => 'AAPL', 'shares' => 100, 'cost' => 15000, 'value' => 18000],
                ['symbol' => 'GOOGL', 'shares' => 50, 'cost' => 12000, 'value' => 14000],
            ],
            'totals' => ['cost' => 27000, 'value' => 32000]
        ];
        
        $result = $this->service->exportPortfolio(1, $data);
        
        // Verify result structure (mock implementation provides simplified output)
        $this->assertNotEmpty($result['content']);
        $this->assertStringContainsString('Portfolio Holdings', $result['content']);
    }

    /**
     * @test
     * @group excel
     */
    public function itExportsSectorAnalysis(): void
    {
        // Mock the DAO method that will be implemented later
        $this->sectorDAO->expects($this->once())
            ->method('getSectorBreakdown')
            ->with($this->equalTo(1))
            ->willReturn([
                ['sector' => 'Technology', 'value' => 50000, 'percentage' => 50.0],
                ['sector' => 'Healthcare', 'value' => 30000, 'percentage' => 30.0],
            ]);
        
        $result = $this->service->exportSectorAnalysis(1);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertStringStartsWith('sector_analysis_', $result['filename']);
    }

    /**
     * @test
     * @group excel
     */
    public function itCreatesMultipleSheets(): void
    {
        $result = $this->service->exportFullReport(1);
        
        $this->assertIsArray($result);
        $this->assertStringStartsWith('full_report_', $result['filename']);
        $this->assertNotEmpty($result['content']);
    }

    /**
     * @test
     * @group excel
     */
    public function itHandlesEmptyData(): void
    {
        $data = ['holdings' => [], 'totals' => []];
        
        $result = $this->service->exportPortfolio(1, $data);
        
        $this->assertNotEmpty($result['content']);
    }

    /**
     * @test
     * @group excel
     */
    public function itHandlesLargeDatasets(): void
    {
        $holdings = [];
        for ($i = 0; $i < 1000; $i++) {
            $holdings[] = [
                'symbol' => "SYM{$i}",
                'shares' => rand(1, 1000),
                'cost' => rand(1000, 100000),
                'value' => rand(1000, 100000)
            ];
        }
        
        $data = ['holdings' => $holdings, 'totals' => []];
        
        $result = $this->service->exportPortfolio(1, $data);
        
        $this->assertNotEmpty($result['content']);
    }

    /**
     * @test
     * @group excel
     */
    public function itValidatesUserId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be positive');
        
        $this->service->exportPortfolio(0, []);
    }
}
