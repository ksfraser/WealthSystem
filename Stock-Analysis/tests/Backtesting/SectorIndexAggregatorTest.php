<?php

declare(strict_types=1);

namespace Tests\Backtesting;

use App\Backtesting\SectorIndexAggregator;
use PHPUnit\Framework\TestCase;

class SectorIndexAggregatorTest extends TestCase
{
    private SectorIndexAggregator $aggregator;
    
    protected function setUp(): void
    {
        $this->aggregator = new SectorIndexAggregator();
    }
    
    public function testItAggregatesPerformanceBySector(): void
    {
        $this->aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 15.5);
        $this->aggregator->addResult('MSFT', 'Technology', 'NASDAQ', 'RSI', 12.3);
        $this->aggregator->addResult('JPM', 'Financial', 'NYSE', 'RSI', 8.7);
        
        $bySector = $this->aggregator->getPerformanceBySector();
        
        $this->assertArrayHasKey('Technology', $bySector);
        $this->assertArrayHasKey('Financial', $bySector);
        $this->assertEquals(2, $bySector['Technology']['count']);
        $this->assertEquals(1, $bySector['Financial']['count']);
        $this->assertEquals(13.9, $bySector['Technology']['average_return']);
    }
    
    public function testItAggregatesPerformanceByIndex(): void
    {
        $this->aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 15.5);
        $this->aggregator->addResult('GOOGL', 'Technology', 'NASDAQ', 'RSI', 10.2);
        $this->aggregator->addResult('JPM', 'Financial', 'NYSE', 'RSI', 8.7);
        
        $byIndex = $this->aggregator->getPerformanceByIndex();
        
        $this->assertArrayHasKey('NASDAQ', $byIndex);
        $this->assertArrayHasKey('NYSE', $byIndex);
        $this->assertEquals(2, $byIndex['NASDAQ']['count']);
        $this->assertEquals(1, $byIndex['NYSE']['count']);
        $this->assertEquals(12.85, $byIndex['NASDAQ']['average_return']);
    }
    
    public function testItAggregatesPerformanceBySectorAndStrategy(): void
    {
        $this->aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 15.5);
        $this->aggregator->addResult('MSFT', 'Technology', 'NASDAQ', 'MACD', 10.0);
        $this->aggregator->addResult('GOOGL', 'Technology', 'NASDAQ', 'RSI', 12.0);
        
        $bySectorStrategy = $this->aggregator->getPerformanceBySectorAndStrategy();
        
        $this->assertArrayHasKey('Technology', $bySectorStrategy);
        $this->assertArrayHasKey('RSI', $bySectorStrategy['Technology']);
        $this->assertArrayHasKey('MACD', $bySectorStrategy['Technology']);
        $this->assertEquals(13.75, $bySectorStrategy['Technology']['RSI']['average_return']);
        $this->assertEquals(10.0, $bySectorStrategy['Technology']['MACD']['average_return']);
    }
    
    public function testItAggregatesPerformanceByIndexAndStrategy(): void
    {
        $this->aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 15.5);
        $this->aggregator->addResult('GOOGL', 'Technology', 'NASDAQ', 'MACD', 10.0);
        $this->aggregator->addResult('JPM', 'Financial', 'NYSE', 'RSI', 8.7);
        
        $byIndexStrategy = $this->aggregator->getPerformanceByIndexAndStrategy();
        
        $this->assertArrayHasKey('NASDAQ', $byIndexStrategy);
        $this->assertArrayHasKey('NYSE', $byIndexStrategy);
        $this->assertArrayHasKey('RSI', $byIndexStrategy['NASDAQ']);
        $this->assertEquals(15.5, $byIndexStrategy['NASDAQ']['RSI']['average_return']);
    }
    
    public function testItCalculatesBestPerformingSector(): void
    {
        $this->aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 25.0);
        $this->aggregator->addResult('MSFT', 'Technology', 'NASDAQ', 'RSI', 20.0);
        $this->aggregator->addResult('JPM', 'Financial', 'NYSE', 'RSI', 8.0);
        $this->aggregator->addResult('XOM', 'Energy', 'NYSE', 'RSI', 5.0);
        
        $best = $this->aggregator->getBestPerformingSector();
        
        $this->assertEquals('Technology', $best['sector']);
        $this->assertEquals(22.5, $best['average_return']);
    }
    
    public function testItCalculatesWorstPerformingSector(): void
    {
        $this->aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 25.0);
        $this->aggregator->addResult('JPM', 'Financial', 'NYSE', 'RSI', 8.0);
        $this->aggregator->addResult('XOM', 'Energy', 'NYSE', 'RSI', -5.0);
        
        $worst = $this->aggregator->getWorstPerformingSector();
        
        $this->assertEquals('Energy', $worst['sector']);
        $this->assertEquals(-5.0, $worst['average_return']);
    }
    
    public function testItCalculatesBestPerformingIndex(): void
    {
        $this->aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 25.0);
        $this->aggregator->addResult('GOOGL', 'Technology', 'NASDAQ', 'RSI', 20.0);
        $this->aggregator->addResult('JPM', 'Financial', 'NYSE', 'RSI', 8.0);
        
        $best = $this->aggregator->getBestPerformingIndex();
        
        $this->assertEquals('NASDAQ', $best['index']);
        $this->assertEquals(22.5, $best['average_return']);
    }
    
    public function testItCalculatesSectorCorrelation(): void
    {
        // Technology and Healthcare perform similarly
        $this->aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 15.0);
        $this->aggregator->addResult('MSFT', 'Technology', 'NASDAQ', 'RSI', 20.0);
        $this->aggregator->addResult('JNJ', 'Healthcare', 'NYSE', 'RSI', 14.0);
        $this->aggregator->addResult('PFE', 'Healthcare', 'NYSE', 'RSI', 18.0);
        
        $correlation = $this->aggregator->getSectorCorrelation('Technology', 'Healthcare');
        
        $this->assertIsFloat($correlation);
        $this->assertGreaterThanOrEqual(-1.0, $correlation);
        $this->assertLessThanOrEqual(1.0, $correlation);
    }
    
    public function testItGeneratesSectorRotationReport(): void
    {
        $this->aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 25.0);
        $this->aggregator->addResult('JPM', 'Financial', 'NYSE', 'RSI', 15.0);
        $this->aggregator->addResult('XOM', 'Energy', 'NYSE', 'RSI', -5.0);
        
        $report = $this->aggregator->generateSectorRotationReport();
        
        $this->assertIsString($report);
        $this->assertStringContainsString('SECTOR ROTATION ANALYSIS', $report);
        $this->assertStringContainsString('Technology', $report);
        $this->assertStringContainsString('Best Performing', $report);
    }
    
    public function testItExportsSectorPerformanceToCSV(): void
    {
        $this->aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 15.5);
        $this->aggregator->addResult('JPM', 'Financial', 'NYSE', 'RSI', 8.7);
        
        $csv = $this->aggregator->exportSectorPerformanceToCSV();
        
        $this->assertIsString($csv);
        $this->assertStringContainsString('Sector,Count,Average Return,Min Return,Max Return', $csv);
        $this->assertStringContainsString('Technology', $csv);
        $this->assertStringContainsString('Financial', $csv);
    }
    
    public function testItExportsIndexPerformanceToCSV(): void
    {
        $this->aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 15.5);
        $this->aggregator->addResult('JPM', 'Financial', 'NYSE', 'RSI', 8.7);
        
        $csv = $this->aggregator->exportIndexPerformanceToCSV();
        
        $this->assertIsString($csv);
        $this->assertStringContainsString('Index,Count,Average Return,Min Return,Max Return', $csv);
        $this->assertStringContainsString('NASDAQ', $csv);
        $this->assertStringContainsString('NYSE', $csv);
    }
    
    public function testItHandlesEmptyResults(): void
    {
        $bySector = $this->aggregator->getPerformanceBySector();
        $byIndex = $this->aggregator->getPerformanceByIndex();
        
        $this->assertIsArray($bySector);
        $this->assertIsArray($byIndex);
        $this->assertEmpty($bySector);
        $this->assertEmpty($byIndex);
    }
    
    public function testItCalculatesSectorVolatility(): void
    {
        $this->aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 25.0);
        $this->aggregator->addResult('MSFT', 'Technology', 'NASDAQ', 'RSI', 10.0);
        $this->aggregator->addResult('GOOGL', 'Technology', 'NASDAQ', 'RSI', 20.0);
        
        $bySector = $this->aggregator->getPerformanceBySector();
        
        $this->assertArrayHasKey('Technology', $bySector);
        $this->assertArrayHasKey('volatility', $bySector['Technology']);
        $this->assertGreaterThan(0, $bySector['Technology']['volatility']);
    }
    
    public function testItFiltersResultsByStrategy(): void
    {
        $this->aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 15.0);
        $this->aggregator->addResult('MSFT', 'Technology', 'NASDAQ', 'MACD', 10.0);
        $this->aggregator->addResult('GOOGL', 'Technology', 'NASDAQ', 'RSI', 20.0);
        
        $filtered = $this->aggregator->getPerformanceBySector('RSI');
        
        $this->assertArrayHasKey('Technology', $filtered);
        $this->assertEquals(2, $filtered['Technology']['count']);
        $this->assertEquals(17.5, $filtered['Technology']['average_return']);
    }
    
    public function testItReturnsTopPerformingSectors(): void
    {
        $this->aggregator->addResult('AAPL', 'Technology', 'NASDAQ', 'RSI', 25.0);
        $this->aggregator->addResult('JPM', 'Financial', 'NYSE', 'RSI', 15.0);
        $this->aggregator->addResult('XOM', 'Energy', 'NYSE', 'RSI', 10.0);
        $this->aggregator->addResult('JNJ', 'Healthcare', 'NYSE', 'RSI', 20.0);
        
        $topSectors = $this->aggregator->getTopPerformingSectors(2);
        
        $this->assertCount(2, $topSectors);
        $this->assertEquals('Technology', $topSectors[0]['sector']);
        $this->assertEquals('Healthcare', $topSectors[1]['sector']);
    }
}
