<?php

declare(strict_types=1);

namespace Tests\AI;

use App\AI\Providers\AnthropicProvider;
use App\AI\Providers\AIResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class AnthropicProviderTest extends TestCase
{
    public function testChatWithSuccessfulResponse(): void
    {
        $mockResponse = new Response(200, [], json_encode([
            'id' => 'msg_123',
            'type' => 'message',
            'role' => 'assistant',
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'Hello! How can I help you today?'
                ]
            ],
            'model' => 'claude-3-sonnet-20240229',
            'stop_reason' => 'end_turn',
            'usage' => [
                'input_tokens' => 10,
                'output_tokens' => 25
            ]
        ]));

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new AnthropicProvider('test-api-key', 'claude-3-sonnet-20240229', new NullLogger(), $client);

        $messages = [
            ['role' => 'user', 'content' => 'Hello, Claude!']
        ];

        $response = $provider->chat($messages);

        $this->assertInstanceOf(AIResponse::class, $response);
        $this->assertEquals('Hello! How can I help you today?', $response->content);
        $this->assertEquals('claude-3-sonnet-20240229', $response->model);
        $this->assertEquals('anthropic', $response->provider);
        $this->assertEquals(10, $response->promptTokens);
        $this->assertEquals(25, $response->completionTokens);
        $this->assertEquals(35, $response->getTotalTokens());
        $this->assertEquals('end_turn', $response->finishReason);
        $this->assertNull($response->error);
        $this->assertGreaterThan(0, $response->responseTime);
    }

    public function testChatWithSystemMessage(): void
    {
        $requestVerified = false;
        
        $mockResponse = new Response(200, [], json_encode([
            'content' => [['type' => 'text', 'text' => 'Response']],
            'model' => 'claude-3-sonnet-20240229',
            'usage' => ['input_tokens' => 50, 'output_tokens' => 20]
        ]));

        $mock = new MockHandler([
            function (Request $request) use (&$requestVerified, $mockResponse) {
                $body = json_decode($request->getBody()->getContents(), true);
                
                // Verify system message is separate
                $this->assertArrayHasKey('system', $body);
                $this->assertEquals('You are a helpful assistant', $body['system']);
                
                // Verify messages don't include system
                $this->assertCount(1, $body['messages']);
                $this->assertEquals('user', $body['messages'][0]['role']);
                
                $requestVerified = true;
                return $mockResponse;
            }
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new AnthropicProvider('test-api-key', 'claude-3-sonnet-20240229', new NullLogger(), $client);

        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant'],
            ['role' => 'user', 'content' => 'Hello']
        ];

        $response = $provider->chat($messages);

        $this->assertTrue($requestVerified);
        $this->assertNull($response->error);
    }

    public function testChatWithOptions(): void
    {
        $requestVerified = false;
        
        $mockResponse = new Response(200, [], json_encode([
            'content' => [['type' => 'text', 'text' => 'Response']],
            'model' => 'claude-3-opus-20240229',
            'usage' => ['input_tokens' => 30, 'output_tokens' => 40]
        ]));

        $mock = new MockHandler([
            function (Request $request) use (&$requestVerified, $mockResponse) {
                $body = json_decode($request->getBody()->getContents(), true);
                
                $this->assertEquals(2048, $body['max_tokens']);
                $this->assertEquals(0.7, $body['temperature']);
                $this->assertEquals(0.9, $body['top_p']);
                $this->assertEquals(40, $body['top_k']);
                $this->assertEquals(['STOP'], $body['stop_sequences']);
                
                $requestVerified = true;
                return $mockResponse;
            }
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new AnthropicProvider('test-api-key', 'claude-3-opus-20240229', new NullLogger(), $client);

        $messages = [['role' => 'user', 'content' => 'Test']];
        $options = [
            'max_tokens' => 2048,
            'temperature' => 0.7,
            'top_p' => 0.9,
            'top_k' => 40,
            'stop_sequences' => ['STOP']
        ];

        $response = $provider->chat($messages, $options);

        $this->assertTrue($requestVerified);
        $this->assertNull($response->error);
    }

    public function testChatWithApiError(): void
    {
        $errorResponse = new Response(400, [], json_encode([
            'type' => 'error',
            'error' => [
                'type' => 'invalid_request_error',
                'message' => 'max_tokens is required'
            ]
        ]));

        $mock = new MockHandler([
            new RequestException('Bad Request', new Request('POST', 'test'), $errorResponse)
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new AnthropicProvider('test-api-key', 'claude-3-sonnet-20240229', new NullLogger(), $client);

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $response = $provider->chat($messages);

        $this->assertNotNull($response->error);
        $this->assertStringContainsString('max_tokens is required', $response->error);
        $this->assertEquals('', $response->content);
        $this->assertEquals('anthropic', $response->provider);
    }

    public function testChatWithNetworkError(): void
    {
        $mock = new MockHandler([
            new RequestException('Connection timeout', new Request('POST', 'test'))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new AnthropicProvider('test-api-key', 'claude-3-sonnet-20240229', new NullLogger(), $client);

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $response = $provider->chat($messages);

        $this->assertNotNull($response->error);
        $this->assertStringContainsString('Connection timeout', $response->error);
        $this->assertEquals('', $response->content);
    }

    public function testChatWithInvalidResponseStructure(): void
    {
        $mockResponse = new Response(200, [], json_encode([
            'model' => 'claude-3-sonnet-20240229',
            // Missing 'content' field
        ]));

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new AnthropicProvider('test-api-key', 'claude-3-sonnet-20240229', new NullLogger(), $client);

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $response = $provider->chat($messages);

        $this->assertNotNull($response->error);
        $this->assertStringContainsString('Invalid Anthropic response structure', $response->error);
    }

    public function testGetProviderName(): void
    {
        $provider = new AnthropicProvider('test-api-key');
        $this->assertEquals('Anthropic', $provider->getProviderName());
    }

    public function testGetModel(): void
    {
        $provider = new AnthropicProvider('test-api-key', 'claude-3-opus-20240229');
        $this->assertEquals('claude-3-opus-20240229', $provider->getModel());
    }

    public function testIsAvailableWithKey(): void
    {
        $provider = new AnthropicProvider('test-api-key');
        $this->assertTrue($provider->isAvailable());
    }

    public function testIsAvailableWithoutKey(): void
    {
        $provider = new AnthropicProvider('');
        $this->assertFalse($provider->isAvailable());
    }

    public function testDefaultModel(): void
    {
        $provider = new AnthropicProvider('test-api-key');
        $this->assertEquals('claude-3-sonnet-20240229', $provider->getModel());
    }

    public function testGetSupportedModels(): void
    {
        $models = AnthropicProvider::getSupportedModels();

        $this->assertIsArray($models);
        $this->assertArrayHasKey('claude-3-opus-20240229', $models);
        $this->assertArrayHasKey('claude-3-sonnet-20240229', $models);
        $this->assertArrayHasKey('claude-3-haiku-20240307', $models);
        $this->assertArrayHasKey('claude-2.1', $models);

        // Check structure
        $opus = $models['claude-3-opus-20240229'];
        $this->assertArrayHasKey('name', $opus);
        $this->assertArrayHasKey('context', $opus);
        $this->assertArrayHasKey('output', $opus);
        $this->assertArrayHasKey('cost_per_1m_input', $opus);
        $this->assertArrayHasKey('cost_per_1m_output', $opus);
        $this->assertArrayHasKey('description', $opus);

        // Check values
        $this->assertEquals('Claude 3 Opus', $opus['name']);
        $this->assertEquals(200000, $opus['context']);
        $this->assertEquals(4096, $opus['output']);
    }

    public function testMultiTurnConversation(): void
    {
        $mockResponse = new Response(200, [], json_encode([
            'content' => [['type' => 'text', 'text' => 'Sure, I can help with that.']],
            'model' => 'claude-3-sonnet-20240229',
            'usage' => ['input_tokens' => 100, 'output_tokens' => 30]
        ]));

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new AnthropicProvider('test-api-key', 'claude-3-sonnet-20240229', new NullLogger(), $client);

        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant'],
            ['role' => 'user', 'content' => 'What is 2+2?'],
            ['role' => 'assistant', 'content' => 'The answer is 4.'],
            ['role' => 'user', 'content' => 'Can you explain why?']
        ];

        $response = $provider->chat($messages);

        $this->assertNull($response->error);
        $this->assertEquals('Sure, I can help with that.', $response->content);
        $this->assertEquals(100, $response->promptTokens);
        $this->assertEquals(30, $response->completionTokens);
    }

    public function testRequiredHeadersAreSent(): void
    {
        $requestVerified = false;
        
        $mockResponse = new Response(200, [], json_encode([
            'content' => [['type' => 'text', 'text' => 'Response']],
            'model' => 'claude-3-sonnet-20240229',
            'usage' => ['input_tokens' => 10, 'output_tokens' => 10]
        ]));

        $mock = new MockHandler([
            function (Request $request) use (&$requestVerified, $mockResponse) {
                // Verify required headers
                $this->assertTrue($request->hasHeader('x-api-key'));
                $this->assertTrue($request->hasHeader('anthropic-version'));
                $this->assertTrue($request->hasHeader('Content-Type'));
                
                $this->assertEquals('test-api-key', $request->getHeaderLine('x-api-key'));
                $this->assertEquals('2023-06-01', $request->getHeaderLine('anthropic-version'));
                $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
                
                $requestVerified = true;
                return $mockResponse;
            }
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new AnthropicProvider('test-api-key', 'claude-3-sonnet-20240229', new NullLogger(), $client);

        $messages = [['role' => 'user', 'content' => 'Test']];
        $response = $provider->chat($messages);

        $this->assertTrue($requestVerified);
    }

    public function testDefaultMaxTokensIsSet(): void
    {
        $requestVerified = false;
        
        $mockResponse = new Response(200, [], json_encode([
            'content' => [['type' => 'text', 'text' => 'Response']],
            'model' => 'claude-3-sonnet-20240229',
            'usage' => ['input_tokens' => 10, 'output_tokens' => 10]
        ]));

        $mock = new MockHandler([
            function (Request $request) use (&$requestVerified, $mockResponse) {
                $body = json_decode($request->getBody()->getContents(), true);
                
                // Anthropic requires max_tokens, verify default is set
                $this->assertArrayHasKey('max_tokens', $body);
                $this->assertEquals(4096, $body['max_tokens']);
                
                $requestVerified = true;
                return $mockResponse;
            }
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new AnthropicProvider('test-api-key', 'claude-3-sonnet-20240229', new NullLogger(), $client);

        $messages = [['role' => 'user', 'content' => 'Test']];
        $response = $provider->chat($messages, []); // No options provided

        $this->assertTrue($requestVerified);
    }
}
