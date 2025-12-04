<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\AdvancedChartService;
use App\DAO\SectorAnalysisDAO;
use InvalidArgumentException;

/**
 * Test suite for AdvancedChartService
 * 
 * Tests heatmap, treemap, and historical trend chart generation.
 */
class AdvancedChartServiceTest extends TestCase
{
    private AdvancedChartService $service;
    private SectorAnalysisDAO $mockDAO;

    protected function setUp(): void
    {
        $this->mockDAO = $this->createMock(SectorAnalysisDAO::class);
        $this->service = new AdvancedChartService($this->mockDAO);
    }

    /**
     * Test service instantiation
     */
    public function testServiceInstantiation(): void
    {
        $this->assertInstanceOf(AdvancedChartService::class, $this->service);
    }

    /**
     * Test correlation heatmap generation
     */
    public function testGenerateCorrelationHeatmap(): void
    {
        $sectorReturns = [
            'Technology' => [0.05, 0.03, -0.02, 0.04, 0.06],
            'Healthcare' => [0.02, 0.04, 0.01, 0.03, 0.02],
            'Financials' => [0.03, -0.01, 0.02, 0.05, 0.01],
            'Energy' => [-0.01, -0.02, 0.03, -0.01, 0.02]
        ];

        $heatmap = $this->service->generateCorrelationHeatmap($sectorReturns);

        $this->assertIsArray($heatmap);
        $this->assertArrayHasKey('labels', $heatmap);
        $this->assertArrayHasKey('data', $heatmap);
        $this->assertArrayHasKey('colors', $heatmap);
        
        $this->assertCount(4, $heatmap['labels']);
        $this->assertCount(4, $heatmap['data']); // 4x4 matrix
        
        // Check correlation matrix symmetry
        $this->assertEquals($heatmap['data'][0][1], $heatmap['data'][1][0]);
        
        // Check diagonal is 1.0 (perfect self-correlation)
        $this->assertEquals(1.0, $heatmap['data'][0][0]);
        $this->assertEquals(1.0, $heatmap['data'][1][1]);
    }

    /**
     * Test correlation calculation
     */
    public function testCalculateCorrelation(): void
    {
        $returns1 = [0.01, 0.02, -0.01, 0.03, 0.02];
        $returns2 = [0.02, 0.03, -0.02, 0.04, 0.01];

        $correlation = $this->service->calculateCorrelation($returns1, $returns2);

        $this->assertIsFloat($correlation);
        $this->assertGreaterThanOrEqual(-1.0, $correlation);
        $this->assertLessThanOrEqual(1.0, $correlation);
    }

    /**
     * Test perfect positive correlation
     */
    public function testPerfectPositiveCorrelation(): void
    {
        $returns1 = [0.01, 0.02, 0.03, 0.04, 0.05];
        $returns2 = [0.02, 0.04, 0.06, 0.08, 0.10]; // Exact linear relationship

        $correlation = $this->service->calculateCorrelation($returns1, $returns2);

        $this->assertEquals(1.0, $correlation, '', 0.01);
    }

    /**
     * Test perfect negative correlation
     */
    public function testPerfectNegativeCorrelation(): void
    {
        $returns1 = [0.01, 0.02, 0.03, 0.04, 0.05];
        $returns2 = [-0.02, -0.04, -0.06, -0.08, -0.10]; // Inverse relationship

        $correlation = $this->service->calculateCorrelation($returns1, $returns2);

        $this->assertEquals(-1.0, $correlation, '', 0.01);
    }

    /**
     * Test treemap generation for portfolio composition
     */
    public function testGeneratePortfolioTreemap(): void
    {
        $holdings = [
            ['symbol' => 'AAPL', 'value' => 50000, 'sector' => 'Technology', 'return' => 15.5],
            ['symbol' => 'MSFT', 'value' => 40000, 'sector' => 'Technology', 'return' => 12.3],
            ['symbol' => 'JNJ', 'value' => 30000, 'sector' => 'Healthcare', 'return' => 8.2],
            ['symbol' => 'JPM', 'value' => 25000, 'sector' => 'Financials', 'return' => 10.1],
            ['symbol' => 'XOM', 'value' => 15000, 'sector' => 'Energy', 'return' => -2.5]
        ];

        $treemap = $this->service->generatePortfolioTreemap($holdings);

        $this->assertIsArray($treemap);
        $this->assertArrayHasKey('children', $treemap);
        $this->assertArrayHasKey('name', $treemap);
        $this->assertEquals('Portfolio', $treemap['name']);
        
        // Check sectors are grouped
        $sectors = $treemap['children'];
        $this->assertGreaterThan(0, count($sectors));
        
        // Technology sector should have 2 holdings
        $techSector = array_filter($sectors, fn($s) => $s['name'] === 'Technology');
        $techSector = array_values($techSector)[0];
        $this->assertCount(2, $techSector['children']);
    }

    /**
     * Test treemap color coding by performance
     */
    public function testTreemapColorCoding(): void
    {
        $holdings = [
            ['symbol' => 'AAPL', 'value' => 10000, 'sector' => 'Technology', 'return' => 25.0],
            ['symbol' => 'MSFT', 'value' => 10000, 'sector' => 'Technology', 'return' => -10.0]
        ];

        $treemap = $this->service->generatePortfolioTreemap($holdings);

        $techSector = $treemap['children'][0];
        $holdingNodes = $techSector['children'];

        // Positive return should be green-ish
        $positiveHolding = array_values(array_filter($holdingNodes, fn($h) => $h['name'] === 'AAPL'))[0];
        $this->assertStringContainsString('rgba(0,', $positiveHolding['color']);

        // Negative return should be red-ish (-10% = rgba(200, 100, 100))
        $negativeHolding = array_values(array_filter($holdingNodes, fn($h) => $h['name'] === 'MSFT'))[0];
        $this->assertStringContainsString('rgba(200,', $negativeHolding['color']);
    }

    /**
     * Test historical sector weights trend chart
     */
    public function testGenerateHistoricalSectorTrends(): void
    {
        $historicalData = [
            '2025-01-01' => [
                'Technology' => 30.5,
                'Healthcare' => 20.0,
                'Financials' => 15.5,
                'Energy' => 10.0
            ],
            '2025-02-01' => [
                'Technology' => 32.0,
                'Healthcare' => 19.5,
                'Financials' => 15.0,
                'Energy' => 9.5
            ],
            '2025-03-01' => [
                'Technology' => 35.0,
                'Healthcare' => 18.0,
                'Financials' => 14.5,
                'Energy' => 8.5
            ]
        ];

        $this->mockDAO
            ->expects($this->once())
            ->method('getHistoricalSectorWeights')
            ->with(1, '2025-01-01', '2025-03-31')
            ->willReturn($historicalData);

        $trends = $this->service->generateHistoricalSectorTrends(1, '2025-01-01', '2025-03-31');

        $this->assertIsArray($trends);
        $this->assertArrayHasKey('labels', $trends);
        $this->assertArrayHasKey('datasets', $trends);
        
        $this->assertCount(3, $trends['labels']); // 3 dates
        $this->assertCount(4, $trends['datasets']); // 4 sectors
        
        // Each dataset should have 3 data points
        foreach ($trends['datasets'] as $dataset) {
            $this->assertCount(3, $dataset['data']);
            $this->assertArrayHasKey('label', $dataset);
            $this->assertArrayHasKey('borderColor', $dataset);
        }
    }

    /**
     * Test sector concentration over time
     */
    public function testCalculateSectorConcentrationTrend(): void
    {
        $historicalData = [
            '2025-01-01' => ['Tech' => 40, 'Health' => 30, 'Finance' => 30],
            '2025-02-01' => ['Tech' => 50, 'Health' => 25, 'Finance' => 25],
            '2025-03-01' => ['Tech' => 60, 'Health' => 20, 'Finance' => 20]
        ];

        $this->mockDAO
            ->expects($this->once())
            ->method('getHistoricalSectorWeights')
            ->willReturn($historicalData);

        $concentration = $this->service->calculateSectorConcentrationTrend(1, '2025-01-01', '2025-03-31');

        $this->assertIsArray($concentration);
        $this->assertCount(3, $concentration);
        
        // HHI should increase over time (more concentrated)
        $this->assertLessThan($concentration['2025-02-01'], $concentration['2025-01-01']);
        $this->assertLessThan($concentration['2025-03-01'], $concentration['2025-02-01']);
    }

    /**
     * Test rebalancing suggestions based on trends
     */
    public function testGenerateRebalancingSuggestions(): void
    {
        $currentAllocation = [
            'Technology' => 45.0,
            'Healthcare' => 15.0,
            'Financials' => 20.0,
            'Energy' => 10.0,
            'Utilities' => 10.0
        ];

        $targetAllocation = [
            'Technology' => 30.0,
            'Healthcare' => 20.0,
            'Financials' => 20.0,
            'Energy' => 15.0,
            'Utilities' => 15.0
        ];

        $suggestions = $this->service->generateRebalancingSuggestions($currentAllocation, $targetAllocation);

        $this->assertIsArray($suggestions);
        $this->assertArrayHasKey('overweight', $suggestions);
        $this->assertArrayHasKey('underweight', $suggestions);
        $this->assertArrayHasKey('rebalancing_required', $suggestions);
        
        // Technology is overweight by 15%
        $this->assertContains('Technology', array_column($suggestions['overweight'], 'sector'));
        
        // Healthcare is underweight by 5%
        $this->assertContains('Healthcare', array_column($suggestions['underweight'], 'sector'));
        
        $this->assertTrue($suggestions['rebalancing_required']);
    }

    /**
     * Test insufficient data handling
     */
    public function testInsufficientDataForCorrelation(): void
    {
        $returns1 = [0.01];
        $returns2 = [0.02];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient data');

        $this->service->calculateCorrelation($returns1, $returns2);
    }

    /**
     * Test mismatched array lengths
     */
    public function testMismatchedArrayLengths(): void
    {
        $returns1 = [0.01, 0.02, 0.03];
        $returns2 = [0.01, 0.02];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array lengths must match');

        $this->service->calculateCorrelation($returns1, $returns2);
    }

    /**
     * Test zero variance handling
     */
    public function testZeroVarianceCorrelation(): void
    {
        $returns1 = [0.01, 0.01, 0.01, 0.01];
        $returns2 = [0.02, 0.03, 0.01, 0.04];

        $correlation = $this->service->calculateCorrelation($returns1, $returns2);

        $this->assertEquals(0.0, $correlation);
    }

    /**
     * Test color gradient generation
     */
    public function testGenerateColorGradient(): void
    {
        $colors = $this->service->generateColorGradient(-1.0, 1.0, 11);

        $this->assertIsArray($colors);
        $this->assertCount(11, $colors);
        
        // First color should be red (negative)
        $this->assertStringContainsString('rgb(255', $colors[0]);
        
        // Middle color should be white/neutral
        $this->assertStringContainsString('rgb(255, 255, 255)', $colors[5]);
        
        // Last color should be green (positive)
        $this->assertStringContainsString('rgb(0', $colors[10]);
    }

    /**
     * Test format for Chart.js heatmap plugin
     */
    public function testFormatForChartJsHeatmap(): void
    {
        $correlationMatrix = [
            [1.0, 0.8, 0.6],
            [0.8, 1.0, 0.5],
            [0.6, 0.5, 1.0]
        ];
        
        $labels = ['Sector A', 'Sector B', 'Sector C'];

        $formatted = $this->service->formatForChartJsHeatmap($correlationMatrix, $labels);

        $this->assertIsArray($formatted);
        $this->assertArrayHasKey('datasets', $formatted);
        $this->assertArrayHasKey('xLabels', $formatted);
        $this->assertArrayHasKey('yLabels', $formatted);
        
        $this->assertEquals($labels, $formatted['xLabels']);
        $this->assertEquals($labels, $formatted['yLabels']);
        
        // Check data points
        $dataPoints = $formatted['datasets'][0]['data'];
        $this->assertCount(9, $dataPoints); // 3x3 matrix = 9 points
        
        // Check first point
        $this->assertEquals(0, $dataPoints[0]['x']);
        $this->assertEquals(0, $dataPoints[0]['y']);
        $this->assertEquals(1.0, $dataPoints[0]['v']);
    }

    /**
     * Test empty holdings handling
     */
    public function testEmptyHoldingsTreemap(): void
    {
        $holdings = [];

        $treemap = $this->service->generatePortfolioTreemap($holdings);

        $this->assertIsArray($treemap);
        $this->assertArrayHasKey('children', $treemap);
        $this->assertEmpty($treemap['children']);
    }

    /**
     * Test date range validation
     */
    public function testInvalidDateRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date range');

        $this->service->generateHistoricalSectorTrends(1, '2025-03-01', '2025-01-01');
    }
}
