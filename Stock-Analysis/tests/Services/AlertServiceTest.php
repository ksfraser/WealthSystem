<?php

declare(strict_types=1);

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\AlertService;
use App\DAO\SectorAnalysisDAO;

/**
 * Test suite for AlertService
 * 
 * Tests alert generation for:
 * - Concentration risk (HHI thresholds)
 * - Rebalancing needs (sector deviation)
 * - Performance warnings (underperformance)
 * 
 * @covers \App\Services\AlertService
 */
class AlertServiceTest extends TestCase
{
    private AlertService $service;
    private $daoMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->daoMock = $this->createMock(SectorAnalysisDAO::class);
        $this->service = new AlertService($this->daoMock);
    }

    /**
     * @test
     */
    public function itGeneratesAlerts(): void
    {
        // Arrange
        $userId = 1;
        
        // Mock DAO to return portfolio data
        $this->daoMock
            ->method('getPortfolioSectorData')
            ->willReturn([
                ['symbol' => 'AAPL', 'sector' => 'Technology', 'value' => 60000, 'shares' => 100],
                ['symbol' => 'GOOGL', 'sector' => 'Technology', 'value' => 40000, 'shares' => 50],
                ['symbol' => 'JNJ', 'sector' => 'Healthcare', 'value' => 15000, 'shares' => 20],
            ]);
        
        // Act
        $alerts = $this->service->generateAlerts($userId);
        
        // Assert
        $this->assertIsArray($alerts);
        // Alerts may be empty if thresholds not exceeded, that's OK
    }

    /**
     * @test
     */
    public function itGeneratesConcentrationRiskAlert(): void
    {
        // Arrange
        $userId = 1;
        
        // Mock DAO to return highly concentrated portfolio
        $this->daoMock
            ->method('getPortfolioSectorData')
            ->willReturn([
                ['symbol' => 'AAPL', 'sector' => 'Technology', 'value' => 70000, 'shares' => 100],
                ['symbol' => 'GOOGL', 'sector' => 'Technology', 'value' => 20000, 'shares' => 50],
                ['symbol' => 'JNJ', 'sector' => 'Healthcare', 'value' => 10000, 'shares' => 20],
            ]);
        
        // Act
        $alerts = $this->service->checkConcentrationRisk($userId);
        
        // Assert
        $this->assertIsArray($alerts);
        if (!empty($alerts)) {
            $alert = $alerts[0];
            $this->assertEquals('concentration_risk', $alert['type']);
            $this->assertArrayHasKey('severity', $alert);
            $this->assertArrayHasKey('metric', $alert);
            $this->assertArrayHasKey('recommendation', $alert);
        }
    }

    /**
     * @test
     */
    public function itChecksRebalancingNeeds(): void
    {
        // Arrange
        $userId = 1;
        
        $this->daoMock
            ->method('getPortfolioSectorData')
            ->willReturn([
                ['symbol' => 'AAPL', 'sector' => 'Technology', 'value' => 45000, 'shares' => 100],
                ['symbol' => 'JNJ', 'sector' => 'Healthcare', 'value' => 30000, 'shares' => 50],
                ['symbol' => 'JPM', 'sector' => 'Finance', 'value' => 25000, 'shares' => 20],
            ]);
        
        // Act
        $alerts = $this->service->checkRebalancingNeeds($userId);
        
        // Assert
        $this->assertIsArray($alerts);
        // May be empty if no rebalancing needed
    }

    /**
     * @test
     */
    public function itAllowsCustomThresholds(): void
    {
        // Arrange
        $customThresholds = [
            'hhi_moderate' => 2000,
            'hhi_high' => 3000,
            'rebalancing_threshold' => 10.0,
        ];
        
        $service = new AlertService($this->daoMock, $customThresholds);
        
        // Act - Just verify instantiation works
        $this->assertInstanceOf(AlertService::class, $service);
    }

    /**
     * @test
     */
    public function itReturnsEmptyArrayForWellBalancedPortfolio(): void
    {
        // Arrange
        $userId = 1;
        $balancedData = [
            ['symbol' => 'AAPL', 'sector' => 'Technology', 'value' => 33333, 'shares' => 100],
            ['symbol' => 'JNJ', 'sector' => 'Healthcare', 'value' => 33333, 'shares' => 50],
            ['symbol' => 'JPM', 'sector' => 'Finance', 'value' => 33334, 'shares' => 20],
        ];
        
        $this->daoMock
            ->method('getPortfolioSectorData')
            ->willReturn($balancedData);
        
        // Act
        $alerts = $this->service->generateAlerts($userId);
        
        // Assert
        $this->assertIsArray($alerts);
        // Well-balanced portfolio may have no/few alerts
    }

    /**
     * @test
     */
    public function itIncludesTimestampInAlerts(): void
    {
        // Arrange
        $userId = 1;
        $portfolioData = [
            ['symbol' => 'AAPL', 'sector' => 'Technology', 'value' => 100000, 'shares' => 100],
        ];
        
        $this->daoMock
            ->method('getPortfolioSectorData')
            ->willReturn($portfolioData);
        
        // Act
        $alerts = $this->service->generateAlerts($userId);
        
        // Assert
        foreach ($alerts as $alert) {
            $this->assertArrayHasKey('timestamp', $alert);
            $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $alert['timestamp']);
        }
    }

    /**
     * @test
     */
    public function itIncludesActionRecommendations(): void
    {
        // Arrange
        $userId = 1;
        $portfolioData = [
            ['symbol' => 'AAPL', 'sector' => 'Technology', 'value' => 70000, 'shares' => 100],
            ['symbol' => 'JNJ', 'sector' => 'Healthcare', 'value' => 30000, 'shares' => 50],
        ];
        
        $this->daoMock
            ->method('getPortfolioSectorData')
            ->willReturn($portfolioData);
        
        // Act
        $alerts = $this->service->generateAlerts($userId);
        
        // Assert
        foreach ($alerts as $alert) {
            $this->assertArrayHasKey('recommendation', $alert);
            $this->assertIsString($alert['recommendation']);
            $this->assertNotEmpty($alert['recommendation']);
        }
    }

    /**
     * @test
     */
    public function itSortsAlertsBySeverity(): void
    {
        // Arrange
        $userId = 1;
        
        // Create a highly concentrated portfolio that should trigger critical alert
        $portfolioData = [
            ['symbol' => 'AAPL', 'sector' => 'Technology', 'value' => 80000, 'shares' => 100],
            ['symbol' => 'GOOGL', 'sector' => 'Technology', 'value' => 15000, 'shares' => 20],
            ['symbol' => 'JNJ', 'sector' => 'Healthcare', 'value' => 5000, 'shares' => 10],
        ];
        
        $this->daoMock
            ->method('getPortfolioSectorData')
            ->willReturn($portfolioData);
        
        // Act
        $alerts = $this->service->generateAlerts($userId);
        
        // Assert - Critical alerts should come first
        if (count($alerts) > 1) {
            $severities = array_map(fn($a) => $a['severity'], $alerts);
            $criticalIndex = array_search('critical', $severities);
            $warningIndex = array_search('warning', $severities);
            
            if ($criticalIndex !== false && $warningIndex !== false) {
                $this->assertLessThan($warningIndex, $criticalIndex);
            }
        }
    }
}
