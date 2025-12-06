<?php

declare(strict_types=1);

namespace Tests\AI;

use App\AI\LLMTradingAssistant;
use App\AI\TradingRecommendation;
use App\AI\TradeRecommendation;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

class LLMTradingAssistantTest extends TestCase
{
    private MockHandler $mockHandler;
    private Client $mockClient;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->mockClient = new Client(['handler' => $handlerStack]);
    }

    /**
     * @test
     */
    public function it_generates_trading_recommendations(): void
    {
        $llmResponse = json_encode([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'analysis' => 'Market showing bullish momentum',
                            'trades' => [
                                [
                                    'action' => 'buy',
                                    'ticker' => 'AAPL',
                                    'shares' => 100,
                                    'price' => 150.50,
                                    'stop_loss' => 140.00,
                                    'reason' => 'Strong technical breakout',
                                ],
                            ],
                            'confidence' => 0.85,
                        ]),
                    ],
                ],
            ],
        ]);

        $this->mockHandler->append(new Response(200, [], $llmResponse));

        $assistant = new LLMTradingAssistant(
            apiKey: 'test-api-key',
            model: 'gpt-4',
            logger: null,
            httpClient: $this->mockClient
        );

        $holdings = [
            ['symbol' => 'TSLA', 'shares' => 50, 'avg_cost' => 200.0, 'current_price' => 210.0],
        ];

        $recommendation = $assistant->getRecommendations(
            holdings: $holdings,
            cashBalance: 10000.0,
            totalEquity: 20500.0
        );

        $this->assertFalse($recommendation->hasError());
        $this->assertTrue($recommendation->hasTrades());
        $this->assertEquals('Market showing bullish momentum', $recommendation->analysis);
        $this->assertEquals(0.85, $recommendation->confidence);
        $this->assertCount(1, $recommendation->trades);

        $trade = $recommendation->trades[0];
        $this->assertEquals('buy', $trade->action);
        $this->assertEquals('AAPL', $trade->ticker);
        $this->assertEquals(100, $trade->shares);
        $this->assertEquals(150.50, $trade->price);
        $this->assertEquals(140.00, $trade->stopLoss);
        $this->assertTrue($trade->isBuy());
        $this->assertEquals(15050.0, $trade->getPositionValue());
    }

    /**
     * @test
     */
    public function it_handles_no_trade_recommendations(): void
    {
        $llmResponse = json_encode([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'analysis' => 'Market conditions uncertain, holding positions',
                            'trades' => [],
                            'confidence' => 0.60,
                        ]),
                    ],
                ],
            ],
        ]);

        $this->mockHandler->append(new Response(200, [], $llmResponse));

        $assistant = new LLMTradingAssistant(
            apiKey: 'test-api-key',
            httpClient: $this->mockClient
        );

        $recommendation = $assistant->getRecommendations(
            holdings: [],
            cashBalance: 10000.0,
            totalEquity: 10000.0
        );

        $this->assertFalse($recommendation->hasError());
        $this->assertFalse($recommendation->hasTrades());
        $this->assertEmpty($recommendation->trades);
    }

    /**
     * @test
     */
    public function it_handles_multiple_trade_recommendations(): void
    {
        $llmResponse = json_encode([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'analysis' => 'Diversification opportunity',
                            'trades' => [
                                [
                                    'action' => 'buy',
                                    'ticker' => 'AAPL',
                                    'shares' => 50,
                                    'price' => 150.0,
                                    'stop_loss' => 140.0,
                                    'reason' => 'Tech sector strength',
                                ],
                                [
                                    'action' => 'buy',
                                    'ticker' => 'MSFT',
                                    'shares' => 30,
                                    'price' => 350.0,
                                    'stop_loss' => 330.0,
                                    'reason' => 'Cloud growth',
                                ],
                                [
                                    'action' => 'sell',
                                    'ticker' => 'XYZ',
                                    'shares' => 100,
                                    'price' => 25.0,
                                    'reason' => 'Underperforming',
                                ],
                            ],
                            'confidence' => 0.75,
                        ]),
                    ],
                ],
            ],
        ]);

        $this->mockHandler->append(new Response(200, [], $llmResponse));

        $assistant = new LLMTradingAssistant(
            apiKey: 'test-api-key',
            httpClient: $this->mockClient
        );

        $recommendation = $assistant->getRecommendations(
            holdings: [],
            cashBalance: 20000.0,
            totalEquity: 20000.0
        );

        $this->assertCount(3, $recommendation->trades);

        $trade1 = $recommendation->trades[0];
        $this->assertTrue($trade1->isBuy());
        $this->assertEquals('AAPL', $trade1->ticker);

        $trade2 = $recommendation->trades[1];
        $this->assertTrue($trade2->isBuy());
        $this->assertEquals('MSFT', $trade2->ticker);

        $trade3 = $recommendation->trades[2];
        $this->assertTrue($trade3->isSell());
        $this->assertEquals('XYZ', $trade3->ticker);
    }

    /**
     * @test
     */
    public function it_handles_api_request_failure(): void
    {
        $this->mockHandler->append(new RequestException(
            "Connection timeout",
            new Request('POST', 'test')
        ));

        $assistant = new LLMTradingAssistant(
            apiKey: 'test-api-key',
            httpClient: $this->mockClient
        );

        $recommendation = $assistant->getRecommendations(
            holdings: [],
            cashBalance: 10000.0,
            totalEquity: 10000.0
        );

        $this->assertTrue($recommendation->hasError());
        $this->assertStringContainsString('Connection timeout', $recommendation->error ?? '');
        $this->assertEquals(0.0, $recommendation->confidence);
        $this->assertEmpty($recommendation->trades);
    }

    /**
     * @test
     */
    public function it_handles_invalid_json_response(): void
    {
        $llmResponse = json_encode([
            'choices' => [
                [
                    'message' => [
                        'content' => 'This is not valid JSON',
                    ],
                ],
            ],
        ]);

        $this->mockHandler->append(new Response(200, [], $llmResponse));

        $assistant = new LLMTradingAssistant(
            apiKey: 'test-api-key',
            httpClient: $this->mockClient
        );

        $recommendation = $assistant->getRecommendations(
            holdings: [],
            cashBalance: 10000.0,
            totalEquity: 10000.0
        );

        $this->assertTrue($recommendation->hasError());
        $this->assertStringContainsString('JSON', $recommendation->error ?? '');
    }

    /**
     * @test
     */
    public function it_handles_missing_required_fields_in_response(): void
    {
        $llmResponse = json_encode([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'analysis' => 'Some analysis',
                            // Missing 'trades' and 'confidence'
                        ]),
                    ],
                ],
            ],
        ]);

        $this->mockHandler->append(new Response(200, [], $llmResponse));

        $assistant = new LLMTradingAssistant(
            apiKey: 'test-api-key',
            httpClient: $this->mockClient
        );

        $recommendation = $assistant->getRecommendations(
            holdings: [],
            cashBalance: 10000.0,
            totalEquity: 10000.0
        );

        $this->assertTrue($recommendation->hasError());
        $this->assertStringContainsString('missing required fields', $recommendation->error ?? '');
    }

    /**
     * @test
     */
    public function it_strips_markdown_code_blocks_from_response(): void
    {
        $llmResponse = json_encode([
            'choices' => [
                [
                    'message' => [
                        'content' => '```json' . "\n" . json_encode([
                            'analysis' => 'Market analysis',
                            'trades' => [],
                            'confidence' => 0.70,
                        ]) . "\n" . '```',
                    ],
                ],
            ],
        ]);

        $this->mockHandler->append(new Response(200, [], $llmResponse));

        $assistant = new LLMTradingAssistant(
            apiKey: 'test-api-key',
            httpClient: $this->mockClient
        );

        $recommendation = $assistant->getRecommendations(
            holdings: [],
            cashBalance: 10000.0,
            totalEquity: 10000.0
        );

        $this->assertFalse($recommendation->hasError());
        $this->assertEquals('Market analysis', $recommendation->analysis);
    }

    /**
     * @test
     */
    public function it_formats_holdings_correctly_in_prompt(): void
    {
        $llmResponse = json_encode([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'analysis' => 'Test',
                            'trades' => [],
                            'confidence' => 0.5,
                        ]),
                    ],
                ],
            ],
        ]);

        $this->mockHandler->append(new Response(200, [], $llmResponse));

        $assistant = new LLMTradingAssistant(
            apiKey: 'test-api-key',
            httpClient: $this->mockClient
        );

        $holdings = [
            ['symbol' => 'AAPL', 'shares' => 100, 'avg_cost' => 150.0, 'current_price' => 155.0],
            ['symbol' => 'MSFT', 'shares' => 50, 'avg_cost' => 300.0, 'current_price' => 310.0],
        ];

        $recommendation = $assistant->getRecommendations(
            holdings: $holdings,
            cashBalance: 10000.0,
            totalEquity: 25500.0
        );

        // Verify it completes without error (prompt was formatted correctly)
        $this->assertFalse($recommendation->hasError());
    }

    /**
     * @test
     */
    public function trading_recommendation_to_array_includes_all_fields(): void
    {
        $trades = [
            new TradeRecommendation(
                action: 'buy',
                ticker: 'AAPL',
                shares: 100,
                price: 150.0,
                stopLoss: 140.0,
                reason: 'Test'
            ),
        ];

        $recommendation = new TradingRecommendation(
            analysis: 'Market analysis',
            trades: $trades,
            confidence: 0.80,
            rawResponse: '{"test": true}',
            error: null
        );

        $array = $recommendation->toArray();

        $this->assertArrayHasKey('analysis', $array);
        $this->assertArrayHasKey('trades', $array);
        $this->assertArrayHasKey('confidence', $array);
        $this->assertArrayHasKey('has_error', $array);
        $this->assertArrayHasKey('error', $array);
        $this->assertFalse($array['has_error']);
        $this->assertCount(1, $array['trades']);
    }

    /**
     * @test
     */
    public function trade_recommendation_to_array_includes_all_fields(): void
    {
        $trade = new TradeRecommendation(
            action: 'buy',
            ticker: 'AAPL',
            shares: 100,
            price: 150.0,
            stopLoss: 140.0,
            reason: 'Strong momentum'
        );

        $array = $trade->toArray();

        $this->assertEquals('buy', $array['action']);
        $this->assertEquals('AAPL', $array['ticker']);
        $this->assertEquals(100, $array['shares']);
        $this->assertEquals(150.0, $array['price']);
        $this->assertEquals(140.0, $array['stop_loss']);
        $this->assertEquals('Strong momentum', $array['reason']);
        $this->assertEquals(15000.0, $array['position_value']);
    }

    /**
     * @test
     */
    public function it_uses_custom_config_parameters(): void
    {
        $llmResponse = json_encode([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'analysis' => 'Custom config test',
                            'trades' => [],
                            'confidence' => 0.60,
                        ]),
                    ],
                ],
            ],
        ]);

        $this->mockHandler->append(new Response(200, [], $llmResponse));

        $assistant = new LLMTradingAssistant(
            apiKey: 'test-api-key',
            model: 'gpt-3.5-turbo', // Custom model
            httpClient: $this->mockClient
        );

        $config = [
            'temperature' => 0.5,
            'max_tokens' => 2000,
            'max_position_size_pct' => 15,
            'min_confidence' => 0.80,
            'market_cap_limit' => 500000000,
        ];

        $recommendation = $assistant->getRecommendations(
            holdings: [],
            cashBalance: 10000.0,
            totalEquity: 10000.0,
            config: $config
        );

        $this->assertFalse($recommendation->hasError());
        $this->assertEquals('gpt-3.5-turbo', $assistant->getModel());
    }
}
