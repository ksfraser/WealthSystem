<?php

declare(strict_types=1);

namespace Tests\AI;

use App\AI\AIClient;
use App\AI\LLMTradingAssistant;  // This is LLMTradingAssistant_v2.php (uses AIClient)
use App\AI\Providers\AIProviderInterface;
use App\AI\Providers\AIResponse;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests for LLM Trading Assistant Enhanced Prompts
 */
class LLMTradingAssistantEnhancedTest extends TestCase
{
    private AIProviderInterface $mockProvider;
    private AIClient $aiClient;
    private LLMTradingAssistant $assistant;

    protected function setUp(): void
    {
        $this->mockProvider = $this->createMock(AIProviderInterface::class);
        $this->mockProvider->method('isAvailable')->willReturn(true);
        $this->mockProvider->method('getProviderName')->willReturn('mock');
        $this->mockProvider->method('getModel')->willReturn('mock-model');

        $this->aiClient = new AIClient($this->mockProvider);
        $this->assistant = new LLMTradingAssistant($this->aiClient, new NullLogger());
    }

    public function testEnhancedPromptGeneratesStructuredResponse(): void
    {
        // Mock enhanced response format
        $enhancedResponse = json_encode([
            'market_analysis' => [
                'summary' => 'Market showing strong momentum in tech sector',
                'key_themes' => ['AI growth', 'Regulatory clarity'],
                'risks' => ['Interest rate uncertainty', 'Valuation concerns']
            ],
            'trades' => [
                [
                    'action' => 'buy',
                    'ticker' => 'TEST',
                    'shares' => 100,
                    'entry_price' => 25.50,
                    'stop_loss' => 20.00,
                    'price_target' => 35.00,
                    'fundamentals' => [
                        'revenue_growth_yoy' => '25%',
                        'operating_margin' => '15%',
                        'pe_ratio' => 18.5,
                        'debt_to_equity' => 0.3
                    ],
                    'bull_case' => [
                        'thesis' => 'Strong revenue growth with expanding margins',
                        'catalysts' => ['New product launch', 'Market expansion'],
                        'assumptions' => ['Maintains 20%+ growth', 'Margins expand to 18%']
                    ],
                    'bear_case' => [
                        'thesis' => 'Competition could pressure margins',
                        'risks' => ['New entrants', 'Price competition'],
                        'invalidation_triggers' => ['Revenue growth below 10%']
                    ],
                    'scenarios' => [
                        'base' => ['return_pct' => 20, 'probability_pct' => 50, 'conditions' => 'Steady growth'],
                        'upside' => ['return_pct' => 100, 'probability_pct' => 20, 'conditions' => 'Accelerated growth'],
                        'downside' => ['return_pct' => -20, 'probability_pct' => 30, 'conditions' => 'Margin pressure']
                    ],
                    'position_logic' => 'Strong fundamentals justify 10% position',
                    'sources' => ['Q3 2024 10-Q', 'Earnings call Nov 5'],
                    'confidence' => 0.75,
                    'holding_period' => '6-12 months'
                ]
            ],
            'portfolio_notes' => [
                'diversification' => 'Well-balanced across sectors',
                'risk_level' => 'Moderate'
            ],
            'overall_confidence' => 0.80
        ]);

        $this->mockProvider
            ->method('chat')
            ->willReturn(new AIResponse(
                content: $enhancedResponse,
                model: 'mock-model',
                provider: 'mock',
                promptTokens: 500,
                completionTokens: 800
            ));

        $holdings = [];
        $recommendation = $this->assistant->getRecommendations($holdings, 10000, 10000, [
            'prompt_style' => 'enhanced'
        ]);

        $this->assertTrue($recommendation->isValid());
        $this->assertEquals(0.80, $recommendation->confidence);
        $this->assertCount(1, $recommendation->trades);

        // Check enhanced fields
        $trade = $recommendation->trades[0];
        $this->assertEquals('TEST', $trade->ticker);
        $this->assertEquals(35.00, $trade->priceTarget);
        $this->assertNotNull($trade->fundamentals);
        $this->assertNotNull($trade->bullCase);
        $this->assertNotNull($trade->bearCase);
        $this->assertNotNull($trade->scenarios);
        $this->assertNotEmpty($trade->sources);
        $this->assertEquals(0.75, $trade->tradeConfidence);

        // Check market analysis
        $this->assertNotNull($recommendation->marketAnalysis);
        $this->assertArrayHasKey('summary', $recommendation->marketAnalysis);
        $this->assertArrayHasKey('key_themes', $recommendation->marketAnalysis);
        $this->assertArrayHasKey('risks', $recommendation->marketAnalysis);

        // Check portfolio notes
        $this->assertNotNull($recommendation->portfolioNotes);
        $this->assertArrayHasKey('diversification', $recommendation->portfolioNotes);
    }

    public function testSimplePromptBackwardCompatibility(): void
    {
        // Mock simple response format (original)
        $simpleResponse = json_encode([
            'analysis' => 'Market conditions favorable',
            'trades' => [
                [
                    'action' => 'buy',
                    'ticker' => 'SIMPLE',
                    'shares' => 50,
                    'price' => 10.00,
                    'stop_loss' => 8.00,
                    'reason' => 'Undervalued with strong fundamentals'
                ]
            ],
            'confidence' => 0.70
        ]);

        $this->mockProvider
            ->method('chat')
            ->willReturn(new AIResponse(
                content: $simpleResponse,
                model: 'mock-model',
                provider: 'mock',
                promptTokens: 300,
                completionTokens: 400
            ));

        $holdings = [];
        $recommendation = $this->assistant->getRecommendations($holdings, 5000, 5000, [
            'prompt_style' => 'simple'
        ]);

        $this->assertTrue($recommendation->isValid());
        $this->assertEquals(0.70, $recommendation->confidence);
        $this->assertEquals('Market conditions favorable', $recommendation->analysis);

        $trade = $recommendation->trades[0];
        $this->assertEquals('SIMPLE', $trade->ticker);
        $this->assertEquals('Undervalued with strong fundamentals', $trade->reason);

        // Enhanced fields should be empty/null for simple format
        $this->assertEquals(0, $trade->priceTarget);
        $this->assertNull($trade->fundamentals);
        $this->assertNull($trade->bullCase);
    }

    public function testEnhancedPromptDefaultsToEnhanced(): void
    {
        // When no prompt_style specified, should use enhanced
        $enhancedResponse = json_encode([
            'market_analysis' => [
                'summary' => 'Test',
                'key_themes' => [],
                'risks' => []
            ],
            'trades' => [],
            'overall_confidence' => 0.50
        ]);

        $this->mockProvider
            ->method('chat')
            ->willReturn(new AIResponse(
                content: $enhancedResponse,
                model: 'mock-model',
                provider: 'mock',
                promptTokens: 100,
                completionTokens: 100
            ));

        $recommendation = $this->assistant->getRecommendations([], 1000, 1000, []);

        // Should parse as enhanced format
        $this->assertNotNull($recommendation->marketAnalysis);
    }

    public function testRiskRewardRatioCalculation(): void
    {
        $enhancedResponse = json_encode([
            'market_analysis' => ['summary' => 'Test', 'key_themes' => [], 'risks' => []],
            'trades' => [
                [
                    'action' => 'buy',
                    'ticker' => 'RRR',
                    'shares' => 100,
                    'entry_price' => 30.00,
                    'stop_loss' => 25.00,  // Risk: $5
                    'price_target' => 45.00,  // Reward: $15
                    'bull_case' => ['thesis' => 'Test', 'catalysts' => [], 'assumptions' => []],
                    'bear_case' => ['thesis' => 'Test', 'risks' => [], 'invalidation_triggers' => []],
                    'scenarios' => [],
                    'position_logic' => 'Test',
                    'sources' => [],
                    'confidence' => 0.75
                ]
            ],
            'overall_confidence' => 0.75
        ]);

        $this->mockProvider
            ->method('chat')
            ->willReturn(new AIResponse(
                content: $enhancedResponse,
                model: 'mock-model',
                provider: 'mock',
                promptTokens: 100,
                completionTokens: 200
            ));

        $recommendation = $this->assistant->getRecommendations([], 5000, 5000);
        $trade = $recommendation->trades[0];

        // Risk/Reward should be 15/5 = 3:1
        $this->assertEquals(3.0, $trade->getRiskRewardRatio());

        // Upside potential: (45-30)/30 = 50%
        $this->assertEquals(50.0, $trade->getUpsidePotential());

        // Stop loss percent: (25-30)/30 = -16.67%
        $this->assertEquals(-16.67, round($trade->getStopLossPercent(), 2));
    }

    public function testEnhancedResponseToArray(): void
    {
        $enhancedResponse = json_encode([
            'market_analysis' => [
                'summary' => 'Market analysis',
                'key_themes' => ['Theme 1'],
                'risks' => ['Risk 1']
            ],
            'trades' => [
                [
                    'action' => 'buy',
                    'ticker' => 'ARR',
                    'shares' => 100,
                    'entry_price' => 20.00,
                    'stop_loss' => 18.00,
                    'price_target' => 25.00,
                    'fundamentals' => ['pe_ratio' => 15],
                    'bull_case' => ['thesis' => 'Bull', 'catalysts' => ['C1'], 'assumptions' => ['A1']],
                    'bear_case' => ['thesis' => 'Bear', 'risks' => ['R1'], 'invalidation_triggers' => ['T1']],
                    'scenarios' => [
                        'base' => ['return_pct' => 25, 'probability_pct' => 50, 'conditions' => 'Base']
                    ],
                    'position_logic' => 'Good fit',
                    'sources' => ['Source 1'],
                    'confidence' => 0.80,
                    'holding_period' => '12 months'
                ]
            ],
            'portfolio_notes' => [
                'diversification' => 'Good',
                'risk_level' => 'Medium'
            ],
            'overall_confidence' => 0.80
        ]);

        $this->mockProvider
            ->method('chat')
            ->willReturn(new AIResponse(
                content: $enhancedResponse,
                model: 'mock-model',
                provider: 'mock',
                promptTokens: 100,
                completionTokens: 300
            ));

        $recommendation = $this->assistant->getRecommendations([], 5000, 5000);
        $array = $recommendation->toArray();

        // Check all enhanced fields are present
        $this->assertArrayHasKey('market_analysis', $array);
        $this->assertArrayHasKey('portfolio_notes', $array);

        $tradeArray = $array['trades'][0];
        $this->assertArrayHasKey('price_target', $tradeArray);
        $this->assertArrayHasKey('upside_potential', $tradeArray);
        $this->assertArrayHasKey('risk_reward_ratio', $tradeArray);
        $this->assertArrayHasKey('fundamentals', $tradeArray);
        $this->assertArrayHasKey('bull_case', $tradeArray);
        $this->assertArrayHasKey('bear_case', $tradeArray);
        $this->assertArrayHasKey('scenarios', $tradeArray);
        $this->assertArrayHasKey('position_logic', $tradeArray);
        $this->assertArrayHasKey('sources', $tradeArray);
        $this->assertArrayHasKey('trade_confidence', $tradeArray);
        $this->assertArrayHasKey('holding_period', $tradeArray);
    }

    public function testParsesMixedEnhancedAndSimpleFields(): void
    {
        // AI might return some enhanced fields but not all
        $mixedResponse = json_encode([
            'market_analysis' => [
                'summary' => 'Mixed format',
                'key_themes' => [],
                'risks' => []
            ],
            'trades' => [
                [
                    'action' => 'buy',
                    'ticker' => 'MIX',
                    'shares' => 50,
                    'entry_price' => 15.00,
                    'stop_loss' => 12.00,
                    'price_target' => 20.00,
                    // Some enhanced fields present
                    'fundamentals' => ['pe_ratio' => 12],
                    'bull_case' => ['thesis' => 'Good value', 'catalysts' => [], 'assumptions' => []],
                    // But missing bear_case, scenarios, etc.
                    'position_logic' => 'Diversification',
                    'confidence' => 0.70
                ]
            ],
            'overall_confidence' => 0.70
        ]);

        $this->mockProvider
            ->method('chat')
            ->willReturn(new AIResponse(
                content: $mixedResponse,
                model: 'mock-model',
                provider: 'mock',
                promptTokens: 100,
                completionTokens: 200
            ));

        $recommendation = $this->assistant->getRecommendations([], 3000, 3000);

        $this->assertTrue($recommendation->isValid());
        $trade = $recommendation->trades[0];

        // Present fields should work
        $this->assertNotNull($trade->fundamentals);
        $this->assertNotNull($trade->bullCase);
        $this->assertEquals('Diversification', $trade->positionLogic);

        // Missing fields should be null
        $this->assertNull($trade->bearCase);
        $this->assertNull($trade->scenarios);
    }

    public function testHandlesDataUnavailableInFundamentals(): void
    {
        $responseWithMissingData = json_encode([
            'market_analysis' => ['summary' => 'Test', 'key_themes' => [], 'risks' => []],
            'trades' => [
                [
                    'action' => 'buy',
                    'ticker' => 'DATA',
                    'shares' => 100,
                    'entry_price' => 10.00,
                    'stop_loss' => 8.00,
                    'price_target' => 15.00,
                    'fundamentals' => [
                        'revenue_growth_yoy' => 'Data unavailable',
                        'operating_margin' => '12%',
                        'pe_ratio' => 'Data unavailable',
                        'debt_to_equity' => 0.5
                    ],
                    'bull_case' => ['thesis' => 'Test', 'catalysts' => [], 'assumptions' => []],
                    'bear_case' => ['thesis' => 'Test', 'risks' => [], 'invalidation_triggers' => []],
                    'position_logic' => 'Test',
                    'confidence' => 0.65
                ]
            ],
            'overall_confidence' => 0.65
        ]);

        $this->mockProvider
            ->method('chat')
            ->willReturn(new AIResponse(
                content: $responseWithMissingData,
                model: 'mock-model',
                provider: 'mock',
                promptTokens: 100,
                completionTokens: 200
            ));

        $recommendation = $this->assistant->getRecommendations([], 2000, 2000);
        $trade = $recommendation->trades[0];

        // Should still parse successfully
        $this->assertEquals('DATA', $trade->ticker);
        $this->assertNotNull($trade->fundamentals);
        $this->assertEquals('Data unavailable', $trade->fundamentals['revenue_growth_yoy']);
        $this->assertEquals('12%', $trade->fundamentals['operating_margin']);
    }

    public function testEnhancedPromptWithCustomConfiguration(): void
    {
        $this->mockProvider
            ->method('chat')
            ->willReturn(new AIResponse(
                content: json_encode([
                    'market_analysis' => ['summary' => 'Test', 'key_themes' => [], 'risks' => []],
                    'trades' => [],
                    'overall_confidence' => 0.50
                ]),
                model: 'mock-model',
                provider: 'mock',
                promptTokens: 100,
                completionTokens: 100
            ));

        $customConfig = [
            'prompt_style' => 'enhanced',
            'max_position_size_pct' => 15,
            'min_confidence' => 0.80,
            'market_cap_limit' => 500000000,
            'min_cash_reserve' => 2000,
            'focus' => 'small-cap',
            'temperature' => 0.2,
            'max_tokens' => 3000
        ];

        $recommendation = $this->assistant->getRecommendations([], 20000, 30000, $customConfig);

        // Should successfully process with custom config
        $this->assertNotNull($recommendation);
    }

    public function testZeroRiskRewardWhenNoPriceTarget(): void
    {
        $responseNoPriceTarget = json_encode([
            'market_analysis' => ['summary' => 'Test', 'key_themes' => [], 'risks' => []],
            'trades' => [
                [
                    'action' => 'buy',
                    'ticker' => 'NOTAR',
                    'shares' => 100,
                    'entry_price' => 20.00,
                    'stop_loss' => 18.00,
                    // No price_target
                    'bull_case' => ['thesis' => 'Test', 'catalysts' => [], 'assumptions' => []],
                    'confidence' => 0.70
                ]
            ],
            'overall_confidence' => 0.70
        ]);

        $this->mockProvider
            ->method('chat')
            ->willReturn(new AIResponse(
                content: $responseNoPriceTarget,
                model: 'mock-model',
                provider: 'mock',
                promptTokens: 100,
                completionTokens: 150
            ));

        $recommendation = $this->assistant->getRecommendations([], 3000, 3000);
        $trade = $recommendation->trades[0];

        // Should return 0 when no price target
        $this->assertEquals(0.0, $trade->getRiskRewardRatio());
        $this->assertEquals(0.0, $trade->getUpsidePotential());
    }
}
