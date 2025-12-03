<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\Trading\StrategyWeightingEngine;
use App\Services\Trading\TradingStrategyInterface;

class StrategyWeightingEngineTest extends TestCase
{
    private StrategyWeightingEngine $engine;
    private array $mockStrategies;

    protected function setUp(): void
    {
        $this->mockStrategies = $this->createMockStrategies();
        $this->engine = new StrategyWeightingEngine($this->mockStrategies);
    }

    /**
     * @test
     */
    public function it_initializes_with_equal_weights()
    {
        $weights = $this->engine->getWeights();
        
        $this->assertCount(6, $weights);
        
        // Each strategy should have ~16.67% weight (1/6)
        foreach ($weights as $weight) {
            $this->assertEqualsWithDelta(0.1667, $weight, 0.01);
        }
        
        // Sum should be 1.0
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.01);
    }

    /**
     * @test
     */
    public function it_sets_custom_weights()
    {
        $customWeights = [
            'SmallCapCatalyst' => 0.30,
            'IPlace' => 0.20,
            'MeanReversion' => 0.20,
            'QualityDividend' => 0.15,
            'MomentumQuality' => 0.10,
            'Contrarian' => 0.05
        ];
        
        $this->engine->setWeights($customWeights);
        $weights = $this->engine->getWeights();
        
        $this->assertEquals(0.30, $weights['SmallCapCatalyst']);
        $this->assertEquals(0.05, $weights['Contrarian']);
    }

    /**
     * @test
     */
    public function it_normalizes_weights_to_sum_one()
    {
        $unnormalizedWeights = [
            'SmallCapCatalyst' => 30,
            'IPlace' => 20,
            'MeanReversion' => 20,
            'QualityDividend' => 15,
            'MomentumQuality' => 10,
            'Contrarian' => 5
        ];
        
        $this->engine->setWeights($unnormalizedWeights);
        $weights = $this->engine->getWeights();
        
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.01);
        $this->assertEqualsWithDelta(0.30, $weights['SmallCapCatalyst'], 0.01);
    }

    /**
     * @test
     */
    public function it_rejects_negative_weights()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/cannot be negative/');
        
        $badWeights = [
            'SmallCapCatalyst' => 0.50,
            'IPlace' => -0.20, // Negative weight
            'MeanReversion' => 0.30,
            'QualityDividend' => 0.20,
            'MomentumQuality' => 0.10,
            'Contrarian' => 0.10
        ];
        
        $this->engine->setWeights($badWeights);
    }

    /**
     * @test
     */
    public function it_loads_conservative_profile()
    {
        $this->engine->loadProfile('conservative');
        $weights = $this->engine->getWeights();
        
        // Conservative should favor QualityDividend
        $this->assertGreaterThan(0.30, $weights['QualityDividend']);
        $this->assertLessThan(0.10, $weights['SmallCapCatalyst']);
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.01);
    }

    /**
     * @test
     */
    public function it_loads_aggressive_profile()
    {
        $this->engine->loadProfile('aggressive');
        $weights = $this->engine->getWeights();
        
        // Aggressive should favor SmallCapCatalyst and momentum
        $this->assertGreaterThan(0.25, $weights['SmallCapCatalyst']);
        $this->assertGreaterThan(0.20, $weights['MomentumQuality']);
        $this->assertLessThan(0.10, $weights['QualityDividend']);
    }

    /**
     * @test
     */
    public function it_loads_balanced_profile()
    {
        $this->engine->loadProfile('balanced');
        $weights = $this->engine->getWeights();
        
        // Balanced should have similar weights
        $min = min($weights);
        $max = max($weights);
        $this->assertLessThan(0.15, $max - $min); // Max difference <15%
    }

    /**
     * @test
     */
    public function it_lists_available_profiles()
    {
        $profiles = $this->engine->getAvailableProfiles();
        
        $this->assertArrayHasKey('conservative', $profiles);
        $this->assertArrayHasKey('balanced', $profiles);
        $this->assertArrayHasKey('aggressive', $profiles);
        $this->assertArrayHasKey('growth', $profiles);
        $this->assertArrayHasKey('value', $profiles);
        $this->assertArrayHasKey('catalyst_focused', $profiles);
        
        // Each profile should have name, description, weights
        foreach ($profiles as $profile) {
            $this->assertArrayHasKey('name', $profile);
            $this->assertArrayHasKey('description', $profile);
            $this->assertArrayHasKey('weights', $profile);
        }
    }

    /**
     * @test
     */
    public function it_rejects_unknown_profile()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown profile/');
        
        $this->engine->loadProfile('nonexistent');
    }

    /**
     * @test
     */
    public function it_analyzes_symbol_with_weighted_confidence()
    {
        $result = $this->engine->analyzeSymbol('TEST');
        
        $this->assertArrayHasKey('symbol', $result);
        $this->assertEquals('TEST', $result['symbol']);
        $this->assertArrayHasKey('overall_action', $result);
        $this->assertArrayHasKey('weighted_confidence', $result);
        $this->assertArrayHasKey('buy_vote_weight', $result);
        $this->assertArrayHasKey('hold_vote_weight', $result);
        $this->assertArrayHasKey('consensus', $result);
        $this->assertArrayHasKey('strategy_results', $result);
        $this->assertArrayHasKey('recommendation', $result);
    }

    /**
     * @test
     */
    public function it_returns_buy_when_majority_vote_buy()
    {
        // Set up mocks where 4 out of 6 strategies vote BUY
        $result = $this->engine->analyzeSymbol('STRONG_BUY');
        
        $this->assertEquals('BUY', $result['overall_action']);
        $this->assertGreaterThan(0.50, $result['buy_vote_weight']);
        $this->assertStringContainsString('BUY recommendation', $result['recommendation']);
    }

    /**
     * @test
     */
    public function it_returns_hold_when_majority_vote_hold()
    {
        // Set up mocks where most strategies vote HOLD
        $result = $this->engine->analyzeSymbol('WEAK_BUY');
        
        $this->assertEquals('HOLD', $result['overall_action']);
        $this->assertLessThanOrEqual(0.50, $result['buy_vote_weight']);
        $this->assertStringContainsString('HOLD recommendation', $result['recommendation']);
    }

    /**
     * @test
     */
    public function it_calculates_weighted_confidence_correctly()
    {
        $customWeights = [
            'SmallCapCatalyst' => 0.50, // 50% weight
            'IPlace' => 0.50,           // 50% weight
            'MeanReversion' => 0.00,
            'QualityDividend' => 0.00,
            'MomentumQuality' => 0.00,
            'Contrarian' => 0.00
        ];
        
        $this->engine->setWeights($customWeights);
        $result = $this->engine->analyzeSymbol('TEST');
        
        // Weighted confidence = (80 * 0.5) + (60 * 0.5) = 70
        $this->assertEqualsWithDelta(70, $result['weighted_confidence'], 5);
    }

    /**
     * @test
     */
    public function it_includes_individual_strategy_results()
    {
        $result = $this->engine->analyzeSymbol('TEST');
        
        $this->assertCount(6, $result['strategy_results']);
        
        foreach ($result['strategy_results'] as $name => $strategyResult) {
            $this->assertArrayHasKey('action', $strategyResult);
            $this->assertArrayHasKey('confidence', $strategyResult);
            $this->assertArrayHasKey('reasoning', $strategyResult);
            $this->assertArrayHasKey('weight', $strategyResult);
            $this->assertArrayHasKey('weighted_confidence', $strategyResult);
        }
    }

    /**
     * @test
     */
    public function it_handles_strategy_errors_gracefully()
    {
        $result = $this->engine->analyzeSymbol('ERROR_SYMBOL');
        
        // Should still return results, errors marked as ERROR action
        $this->assertArrayHasKey('overall_action', $result);
        $this->assertIsArray($result['strategy_results']);
    }

    /**
     * @test
     */
    public function it_ranks_multiple_symbols()
    {
        $symbols = ['STRONG_BUY', 'TEST', 'WEAK_BUY'];
        $results = $this->engine->analyzeAndRank($symbols);
        
        $this->assertCount(3, $results);
        
        // Results should be sorted by weighted_confidence descending
        $confidences = array_column($results, 'weighted_confidence');
        $sortedConfidences = $confidences;
        rsort($sortedConfidences);
        
        $this->assertEquals($sortedConfidences, $confidences);
    }

    /**
     * @test
     */
    public function it_rebalances_for_bull_market()
    {
        $this->engine->rebalanceForMarketConditions('bull');
        $weights = $this->engine->getWeights();
        
        // Bull market should favor momentum and catalysts
        $growthWeight = $weights['MomentumQuality'] + $weights['IPlace'] + $weights['SmallCapCatalyst'];
        $defensiveWeight = $weights['QualityDividend'] + $weights['Contrarian'];
        
        $this->assertGreaterThan($defensiveWeight, $growthWeight);
    }

    /**
     * @test
     */
    public function it_rebalances_for_bear_market()
    {
        $this->engine->rebalanceForMarketConditions('bear');
        $weights = $this->engine->getWeights();
        
        // Bear market should favor defensive and contrarian
        $defensiveWeight = $weights['QualityDividend'] + $weights['Contrarian'];
        $growthWeight = $weights['SmallCapCatalyst'] + $weights['MomentumQuality'];
        
        $this->assertGreaterThan($growthWeight, $defensiveWeight);
    }

    /**
     * @test
     */
    public function it_rebalances_for_volatile_market()
    {
        $this->engine->rebalanceForMarketConditions('volatile');
        $weights = $this->engine->getWeights();
        
        // Volatile market should favor mean reversion
        $this->assertGreaterThan(0.20, $weights['MeanReversion']);
    }

    /**
     * @test
     */
    public function it_maintains_sum_one_after_rebalancing()
    {
        $conditions = ['bull', 'bear', 'sideways', 'volatile'];
        
        foreach ($conditions as $condition) {
            $this->engine->rebalanceForMarketConditions($condition);
            $weights = $this->engine->getWeights();
            
            $this->assertEqualsWithDelta(1.0, array_sum($weights), 0.01, 
                "Failed for $condition market");
        }
    }

    /**
     * @test
     */
    public function it_rejects_unknown_market_condition()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown market condition/');
        
        $this->engine->rebalanceForMarketConditions('moonshot');
    }

    /**
     * @test
     */
    public function it_adds_new_strategy_dynamically()
    {
        $newStrategy = $this->createMock(TradingStrategyInterface::class);
        $newStrategy->method('analyze')->willReturn([
            'action' => 'BUY',
            'confidence' => 75,
            'reasoning' => 'Test strategy'
        ]);
        
        $this->engine->addStrategy('NewStrategy', $newStrategy);
        
        // Should now have 7 strategies
        $weights = $this->engine->getWeights();
        $this->assertArrayHasKey('NewStrategy', $weights);
    }

    // Helper method to create mock strategies
    private function createMockStrategies(): array
    {
        $strategies = [];
        
        $strategyConfigs = [
            'SmallCapCatalyst' => ['action' => 'BUY', 'confidence' => 80],
            'IPlace' => ['action' => 'BUY', 'confidence' => 60],
            'MeanReversion' => ['action' => 'HOLD', 'confidence' => 40],
            'QualityDividend' => ['action' => 'HOLD', 'confidence' => 30],
            'MomentumQuality' => ['action' => 'BUY', 'confidence' => 70],
            'Contrarian' => ['action' => 'HOLD', 'confidence' => 35]
        ];
        
        foreach ($strategyConfigs as $name => $config) {
            $mock = $this->createMock(TradingStrategyInterface::class);
            
            $mock->method('analyze')
                ->willReturnCallback(function($symbol) use ($config) {
                    if ($symbol === 'ERROR_SYMBOL') {
                        throw new \Exception('Test error');
                    }
                    
                    if ($symbol === 'STRONG_BUY') {
                        return [
                            'action' => 'BUY',
                            'confidence' => 85,
                            'reasoning' => 'Strong BUY signal'
                        ];
                    }
                    
                    if ($symbol === 'WEAK_BUY') {
                        return [
                            'action' => 'HOLD',
                            'confidence' => 25,
                            'reasoning' => 'Weak signal'
                        ];
                    }
                    
                    return [
                        'action' => $config['action'],
                        'confidence' => $config['confidence'],
                        'reasoning' => "Test reasoning for $symbol"
                    ];
                });
            
            $strategies[$name] = $mock;
        }
        
        return $strategies;
    }
}
