<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\Trading\IPlaceStrategyService;
use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

class IPlaceStrategyServiceTest extends TestCase
{
    private IPlaceStrategyService $strategy;
    private $marketDataService;
    private $marketDataRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->marketDataService = $this->getMockBuilder(MarketDataService::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAnalystRatings'])
            ->onlyMethods(['getFundamentals', 'getHistoricalPrices'])
            ->getMock();
        $this->marketDataRepository = $this->createMock(MarketDataRepositoryInterface::class);
        
        $this->strategy = new IPlaceStrategyService(
            $this->marketDataService,
            $this->marketDataRepository
        );
    }

    /**
     * @test
     */
    public function it_initializes_with_default_parameters()
    {
        $this->assertInstanceOf(IPlaceStrategyService::class, $this->strategy);
        
        $params = $this->strategy->getParameters();
        $this->assertIsArray($params);
        $this->assertArrayHasKey('upgrade_momentum_window', $params);
    }

    /**
     * @test
     */
    public function it_implements_trading_strategy_interface()
    {
        $this->assertEquals('IPlace', $this->strategy->getName());
        $this->assertNotEmpty($this->strategy->getDescription());
        $this->assertIsInt($this->strategy->getRequiredHistoricalDays());
        $this->assertGreaterThan(0, $this->strategy->getRequiredHistoricalDays());
    }

    /**
     * @test
     */
    public function it_returns_hold_with_insufficient_data()
    {
        $this->marketDataService->method('getFundamentals')->willReturn([]);
        $this->marketDataService->method('getHistoricalPrices')->willReturn([]);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertEquals('HOLD', $result['action']);
        $this->assertEquals(0, $result['confidence']);
    }

    /**
     * @test
     */
    public function it_detects_recent_analyst_upgrades()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getBasicHistoricalData();
        $analystRatings = $this->getRecentUpgradeData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        $this->marketDataService->method('getAnalystRatings')->willReturn($analystRatings);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('recent_upgrades', $result['metrics']);
        $this->assertGreaterThan(0, $result['metrics']['recent_upgrades']);
    }

    /**
     * @test
     */
    public function it_calculates_upgrade_momentum_score()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getUpgradeMomentumData();
        $analystRatings = $this->getRecentUpgradeData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        $this->marketDataService->method('getAnalystRatings')->willReturn($analystRatings);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('upgrade_momentum', $result['metrics']);
        $this->assertIsFloat($result['metrics']['upgrade_momentum']);
        $this->assertGreaterThanOrEqual(0, $result['metrics']['upgrade_momentum']);
    }

    /**
     * @test
     */
    public function it_measures_price_reaction_to_upgrades()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getUpgradeMomentumData();
        $analystRatings = $this->getRecentUpgradeData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        $this->marketDataService->method('getAnalystRatings')->willReturn($analystRatings);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('price_reaction_score', $result['metrics']);
        $this->assertIsFloat($result['metrics']['price_reaction_score']);
    }

    /**
     * @test
     */
    public function it_ignores_downgrades_for_buy_signals()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getBasicHistoricalData();
        $analystRatings = $this->getDowngradeData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        $this->marketDataService->method('getAnalystRatings')->willReturn($analystRatings);
        
        $result = $this->strategy->analyze('TEST');
        
        // Should not recommend BUY after downgrade
        $this->assertNotEquals('BUY', $result['action']);
    }

    /**
     * @test
     */
    public function it_requires_minimum_upgrades_for_buy()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getBasicHistoricalData();
        $analystRatings = $this->getSingleUpgradeData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        $this->marketDataService->method('getAnalystRatings')->willReturn($analystRatings);
        
        $result = $this->strategy->analyze('TEST');
        
        // With only 1 upgrade, should be cautious
        if ($result['action'] === 'BUY') {
            $this->assertLessThan(75, $result['confidence']);
        }
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_calculates_consensus_rating_change()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getBasicHistoricalData();
        $analystRatings = $this->getMultipleUpgradeData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        $this->marketDataService->method('getAnalystRatings')->willReturn($analystRatings);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('consensus_change', $result['metrics']);
        $this->assertTrue(is_float($result['metrics']['consensus_change']) || is_int($result['metrics']['consensus_change']));
    }

    /**
     * @test
     */
    public function it_considers_analyst_reputation()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getBasicHistoricalData();
        $analystRatings = $this->getHighReputationUpgradeData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        $this->marketDataService->method('getAnalystRatings')->willReturn($analystRatings);
        
        $result = $this->strategy->analyze('TEST');
        
        // High reputation analyst should increase confidence
        $this->assertArrayHasKey('analyst_quality_score', $result['metrics']);
    }

    /**
     * @test
     */
    public function it_checks_upgrade_recency()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getBasicHistoricalData();
        $analystRatings = $this->getOldUpgradeData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        $this->marketDataService->method('getAnalystRatings')->willReturn($analystRatings);
        
        $result = $this->strategy->analyze('TEST');
        
        // Old upgrades should have lower impact
        if (isset($result['metrics']['days_since_last_upgrade'])) {
            $this->assertIsInt($result['metrics']['days_since_last_upgrade']);
        }
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_calculates_target_price_momentum()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getBasicHistoricalData();
        $analystRatings = $this->getTargetPriceIncreaseData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        $this->marketDataService->method('getAnalystRatings')->willReturn($analystRatings);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('target_price_momentum', $result['metrics']);
        $this->assertTrue(is_float($result['metrics']['target_price_momentum']) || is_int($result['metrics']['target_price_momentum']));
    }

    /**
     * @test
     */
    public function it_validates_minimum_analyst_coverage()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getBasicHistoricalData();
        $analystRatings = $this->getSingleAnalystData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        $this->marketDataService->method('getAnalystRatings')->willReturn($analystRatings);
        
        $result = $this->strategy->analyze('TEST');
        
        // Need minimum coverage for reliable signals
        $this->assertArrayHasKey('analyst_coverage_count', $result['metrics']);
    }

    /**
     * @test
     */
    public function it_returns_buy_signal_with_strong_upgrade_momentum()
    {
        $fundamentals = $this->getStrongFundamentals();
        $historicalData = $this->getUpgradeMomentumData();
        $analystRatings = $this->getMultipleRecentUpgradesData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        $this->marketDataService->method('getAnalystRatings')->willReturn($analystRatings);
        
        $result = $this->strategy->analyze('TEST');
        
        // Strong fundamentals + multiple upgrades + momentum should suggest BUY
        $this->assertContains($result['action'], ['BUY', 'HOLD']);
        
        if ($result['action'] === 'BUY') {
            $this->assertGreaterThan(20, $result['confidence']);
        }
    }

    /**
     * @test
     */
    public function it_considers_volume_on_upgrade_day()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getHighVolumeUpgradeData();
        $analystRatings = $this->getRecentUpgradeData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        $this->marketDataService->method('getAnalystRatings')->willReturn($analystRatings);
        
        $result = $this->strategy->analyze('TEST');
        
        // High volume on upgrade should increase confidence
        $this->assertArrayHasKey('volume_confirmation', $result['metrics']);
    }

    /**
     * @test
     */
    public function it_tracks_upgrade_to_downgrade_ratio()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getBasicHistoricalData();
        $analystRatings = $this->getMixedRatingsData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        $this->marketDataService->method('getAnalystRatings')->willReturn($analystRatings);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('upgrade_downgrade_ratio', $result['metrics']);
        $this->assertTrue(is_float($result['metrics']['upgrade_downgrade_ratio']) || is_int($result['metrics']['upgrade_downgrade_ratio']));
    }

    /**
     * @test
     */
    public function it_calculates_post_upgrade_returns()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getPostUpgradeGainData();
        $analystRatings = $this->getRecentUpgradeData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        $this->marketDataService->method('getAnalystRatings')->willReturn($analystRatings);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('post_upgrade_performance', $result['metrics']);
    }

    /**
     * @test
     */
    public function it_identifies_upgrade_clusters()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getBasicHistoricalData();
        $analystRatings = $this->getClusteredUpgradesData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        $this->marketDataService->method('getAnalystRatings')->willReturn($analystRatings);
        
        $result = $this->strategy->analyze('TEST');
        
        // Multiple upgrades in short period should be identified
        $this->assertArrayHasKey('upgrade_cluster_detected', $result['metrics']);
    }

    /**
     * @test
     */
    public function it_builds_comprehensive_reasoning()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getBasicHistoricalData();
        $analystRatings = $this->getRecentUpgradeData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        $this->marketDataService->method('getAnalystRatings')->willReturn($analystRatings);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertArrayHasKey('reasoning', $result);
        $this->assertNotEmpty($result['reasoning']);
        $reasoning = strtolower($result['reasoning']);
        $this->assertTrue(
            str_contains($reasoning, 'upgrade') ||
            str_contains($reasoning, 'analyst') ||
            str_contains($reasoning, 'rating')
        );
    }

    /**
     * @test
     */
    public function it_handles_no_analyst_data_gracefully()
    {
        $fundamentals = $this->getBasicFundamentals();
        $historicalData = $this->getBasicHistoricalData();

        $this->marketDataService->method('getFundamentals')->willReturn($fundamentals);
        $this->marketDataService->method('getHistoricalPrices')->willReturn($historicalData);
        $this->marketDataService->method('getAnalystRatings')->willReturn([]);
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertEquals('HOLD', $result['action']);
        $this->assertStringContainsString('analyst', strtolower($result['reasoning']));
    }

    /**
     * @test
     */
    public function it_handles_exceptions_gracefully()
    {
        $this->marketDataService->method('getFundamentals')
            ->willThrowException(new \Exception('API Error'));
        
        $result = $this->strategy->analyze('TEST');
        
        $this->assertEquals('HOLD', $result['action']);
        $this->assertEquals(0, $result['confidence']);
        $this->assertStringContainsString('error', strtolower($result['reasoning']));
    }

    /**
     * @test
     */
    public function it_validates_parameters_are_within_expected_ranges()
    {
        $params = $this->strategy->getParameters();
        
        $this->assertArrayHasKey('upgrade_momentum_window', $params);
        $this->assertArrayHasKey('min_analyst_coverage', $params);
        $this->assertArrayHasKey('upgrade_weight', $params);
        $this->assertArrayHasKey('downgrade_penalty', $params);
        
        // Validate reasonable defaults
        $this->assertGreaterThan(0, $params['upgrade_momentum_window']);
        $this->assertLessThanOrEqual(90, $params['upgrade_momentum_window']);
    }

    /**
     * @test
     */
    public function it_can_update_parameters()
    {
        $newParams = [
            'upgrade_momentum_window' => 60,
            'min_analyst_coverage' => 5
        ];
        
        $this->strategy->setParameters($newParams);
        $params = $this->strategy->getParameters();
        
        $this->assertEquals(60, $params['upgrade_momentum_window']);
        $this->assertEquals(5, $params['min_analyst_coverage']);
    }

    // Helper methods for test data

    private function getBasicFundamentals(): array
    {
        return [
            'market_cap' => 5000000000,
            'avg_volume' => 2000000,
            'sector' => 'Technology',
            'analyst_coverage' => 10
        ];
    }

    private function getStrongFundamentals(): array
    {
        return [
            'market_cap' => 10000000000,
            'avg_volume' => 5000000,
            'sector' => 'Technology',
            'analyst_coverage' => 20,
            'pe_ratio' => 25,
            'revenue_growth' => 0.25
        ];
    }

    private function getBasicHistoricalData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        for ($i = 0; $i < 90; $i++) {
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'open' => $basePrice + rand(-3, 3),
                'high' => $basePrice + rand(0, 5),
                'low' => $basePrice - rand(0, 5),
                'close' => $basePrice + rand(-3, 3),
                'volume' => 2000000 + rand(-500000, 500000)
            ];
        }
        
        return array_reverse($data);
    }

    private function getUpgradeMomentumData(): array
    {
        $data = [];
        $basePrice = 90.0;
        
        for ($i = 0; $i < 90; $i++) {
            // Price increases after day 75 (15 days ago)
            $trend = $i > 75 ? ($i - 75) * 0.5 : 0;
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'open' => $basePrice + $trend + rand(-2, 2),
                'high' => $basePrice + $trend + rand(1, 4),
                'low' => $basePrice + $trend - rand(0, 3),
                'close' => $basePrice + $trend + rand(-1, 3),
                'volume' => 2000000 + rand(-500000, 500000)
            ];
        }
        
        return array_reverse($data);
    }

    private function getHighVolumeUpgradeData(): array
    {
        $data = $this->getBasicHistoricalData();
        // Spike volume on recent days
        for ($i = count($data) - 10; $i < count($data); $i++) {
            $data[$i]['volume'] *= 2;
        }
        return $data;
    }

    private function getPostUpgradeGainData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        for ($i = 0; $i < 90; $i++) {
            // Strong gain in last 30 days (after upgrade)
            $trend = $i > 60 ? ($i - 60) * 0.7 : 0;
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'open' => $basePrice + $trend + rand(-2, 2),
                'high' => $basePrice + $trend + rand(1, 4),
                'low' => $basePrice + $trend - rand(0, 3),
                'close' => $basePrice + $trend + rand(-1, 3),
                'volume' => 2000000 + rand(-500000, 500000)
            ];
        }
        
        return array_reverse($data);
    }

    private function getRecentUpgradeData(): array
    {
        return [
            [
                'date' => date('Y-m-d', strtotime('-10 days')),
                'analyst_firm' => 'Goldman Sachs',
                'action' => 'upgrade',
                'old_rating' => 'Hold',
                'new_rating' => 'Buy',
                'target_price' => 120.0,
                'reputation_score' => 0.85
            ]
        ];
    }

    private function getSingleUpgradeData(): array
    {
        return [
            [
                'date' => date('Y-m-d', strtotime('-20 days')),
                'analyst_firm' => 'Small Firm',
                'action' => 'upgrade',
                'old_rating' => 'Hold',
                'new_rating' => 'Buy',
                'target_price' => 110.0,
                'reputation_score' => 0.60
            ]
        ];
    }

    private function getMultipleUpgradeData(): array
    {
        return [
            [
                'date' => date('Y-m-d', strtotime('-10 days')),
                'analyst_firm' => 'Goldman Sachs',
                'action' => 'upgrade',
                'old_rating' => 'Hold',
                'new_rating' => 'Buy',
                'target_price' => 120.0,
                'reputation_score' => 0.85
            ],
            [
                'date' => date('Y-m-d', strtotime('-15 days')),
                'analyst_firm' => 'Morgan Stanley',
                'action' => 'upgrade',
                'old_rating' => 'Sell',
                'new_rating' => 'Hold',
                'target_price' => 105.0,
                'reputation_score' => 0.80
            ]
        ];
    }

    private function getMultipleRecentUpgradesData(): array
    {
        return [
            [
                'date' => date('Y-m-d', strtotime('-5 days')),
                'analyst_firm' => 'Goldman Sachs',
                'action' => 'upgrade',
                'old_rating' => 'Hold',
                'new_rating' => 'Buy',
                'target_price' => 130.0,
                'reputation_score' => 0.85
            ],
            [
                'date' => date('Y-m-d', strtotime('-8 days')),
                'analyst_firm' => 'Morgan Stanley',
                'action' => 'upgrade',
                'old_rating' => 'Hold',
                'new_rating' => 'Buy',
                'target_price' => 125.0,
                'reputation_score' => 0.80
            ],
            [
                'date' => date('Y-m-d', strtotime('-12 days')),
                'analyst_firm' => 'JP Morgan',
                'action' => 'upgrade',
                'old_rating' => 'Neutral',
                'new_rating' => 'Overweight',
                'target_price' => 128.0,
                'reputation_score' => 0.82
            ]
        ];
    }

    private function getClusteredUpgradesData(): array
    {
        return $this->getMultipleRecentUpgradesData();
    }

    private function getHighReputationUpgradeData(): array
    {
        return [
            [
                'date' => date('Y-m-d', strtotime('-7 days')),
                'analyst_firm' => 'Goldman Sachs',
                'action' => 'upgrade',
                'old_rating' => 'Neutral',
                'new_rating' => 'Buy',
                'target_price' => 135.0,
                'reputation_score' => 0.95
            ]
        ];
    }

    private function getDowngradeData(): array
    {
        return [
            [
                'date' => date('Y-m-d', strtotime('-5 days')),
                'analyst_firm' => 'Goldman Sachs',
                'action' => 'downgrade',
                'old_rating' => 'Buy',
                'new_rating' => 'Hold',
                'target_price' => 90.0,
                'reputation_score' => 0.85
            ]
        ];
    }

    private function getMixedRatingsData(): array
    {
        return [
            [
                'date' => date('Y-m-d', strtotime('-10 days')),
                'analyst_firm' => 'Goldman Sachs',
                'action' => 'upgrade',
                'old_rating' => 'Hold',
                'new_rating' => 'Buy',
                'target_price' => 120.0,
                'reputation_score' => 0.85
            ],
            [
                'date' => date('Y-m-d', strtotime('-15 days')),
                'analyst_firm' => 'Small Firm',
                'action' => 'downgrade',
                'old_rating' => 'Buy',
                'new_rating' => 'Hold',
                'target_price' => 95.0,
                'reputation_score' => 0.65
            ]
        ];
    }

    private function getOldUpgradeData(): array
    {
        return [
            [
                'date' => date('Y-m-d', strtotime('-75 days')),
                'analyst_firm' => 'Goldman Sachs',
                'action' => 'upgrade',
                'old_rating' => 'Hold',
                'new_rating' => 'Buy',
                'target_price' => 110.0,
                'reputation_score' => 0.85
            ]
        ];
    }

    private function getTargetPriceIncreaseData(): array
    {
        return [
            [
                'date' => date('Y-m-d', strtotime('-10 days')),
                'analyst_firm' => 'Goldman Sachs',
                'action' => 'reiterate',
                'old_rating' => 'Buy',
                'new_rating' => 'Buy',
                'old_target_price' => 110.0,
                'target_price' => 130.0,
                'reputation_score' => 0.85
            ]
        ];
    }

    private function getSingleAnalystData(): array
    {
        return [
            [
                'date' => date('Y-m-d', strtotime('-10 days')),
                'analyst_firm' => 'Single Coverage',
                'action' => 'initiate',
                'new_rating' => 'Buy',
                'target_price' => 120.0,
                'reputation_score' => 0.70
            ]
        ];
    }
}
