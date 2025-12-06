<?php

declare(strict_types=1);

namespace Tests\AI\Providers;

use App\AI\Providers\OpenAIProvider;
use App\AI\Providers\AIResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

/**
 * Tests for OpenAIProvider
 */
class OpenAIProviderTest extends TestCase
{
    private function createMockClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        return new Client(['handler' => $handlerStack]);
    }

    public function test_successful_chat_request(): void
    {
        $mockResponse = new Response(200, [], json_encode([
            'id' => 'chatcmpl-123',
            'object' => 'chat.completion',
            'model' => 'gpt-4',
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Paris is the capital of France.'
                    ],
                    'finish_reason' => 'stop'
                ]
            ],
            'usage' => [
                'prompt_tokens' => 20,
                'completion_tokens' => 10,
                'total_tokens' => 30
            ]
        ]));

        $client = $this->createMockClient([$mockResponse]);
        $provider = new OpenAIProvider('test-api-key', 'gpt-4', null, $client);

        $result = $provider->chat([
            ['role' => 'user', 'content' => 'What is the capital of France?']
        ]);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Paris is the capital of France.', $result->content);
        $this->assertEquals('gpt-4', $result->model);
        $this->assertEquals('openai', $result->provider);
        $this->assertEquals(20, $result->promptTokens);
        $this->assertEquals(10, $result->completionTokens);
        $this->assertEquals(30, $result->getTotalTokens());
        $this->assertEquals('stop', $result->finishReason);
    }

    public function test_chat_with_options(): void
    {
        $mockResponse = new Response(200, [], json_encode([
            'model' => 'gpt-4',
            'choices' => [
                ['message' => ['content' => 'Response'], 'finish_reason' => 'stop']
            ],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5]
        ]));

        $client = $this->createMockClient([$mockResponse]);
        $provider = new OpenAIProvider('test-api-key', 'gpt-4', null, $client);

        $result = $provider->chat(
            [['role' => 'user', 'content' => 'Test']],
            [
                'temperature' => 0.7,
                'max_tokens' => 500,
                'top_p' => 0.9,
                'frequency_penalty' => 0.5,
                'presence_penalty' => 0.3,
                'stop' => ['\n']
            ]
        );

        $this->assertTrue($result->isSuccess());
    }

    public function test_handles_api_error(): void
    {
        $mockRequest = new Request('POST', 'test');
        $exception = new RequestException(
            'API Error',
            $mockRequest,
            new Response(500, [], 'Internal Server Error')
        );

        $client = $this->createMockClient([$exception]);
        $provider = new OpenAIProvider('test-api-key', 'gpt-4', null, $client);

        $result = $provider->chat([
            ['role' => 'user', 'content' => 'Test']
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('OpenAI API request failed', $result->error);
    }

    public function test_handles_invalid_response_structure(): void
    {
        $mockResponse = new Response(200, [], json_encode([
            'invalid' => 'structure'
        ]));

        $client = $this->createMockClient([$mockResponse]);
        $provider = new OpenAIProvider('test-api-key', 'gpt-4', null, $client);

        $result = $provider->chat([
            ['role' => 'user', 'content' => 'Test']
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Invalid OpenAI response structure', $result->error);
    }

    public function test_get_provider_name(): void
    {
        $provider = new OpenAIProvider('test-key');
        $this->assertEquals('OpenAI', $provider->getProviderName());
    }

    public function test_get_model(): void
    {
        $provider = new OpenAIProvider('test-key', 'gpt-3.5-turbo');
        $this->assertEquals('gpt-3.5-turbo', $provider->getModel());
    }

    public function test_is_available(): void
    {
        $provider1 = new OpenAIProvider('test-key');
        $this->assertTrue($provider1->isAvailable());

        $provider2 = new OpenAIProvider('');
        $this->assertFalse($provider2->isAvailable());
    }

    public function test_tracks_response_time(): void
    {
        $mockResponse = new Response(200, [], json_encode([
            'model' => 'gpt-4',
            'choices' => [
                ['message' => ['content' => 'Response'], 'finish_reason' => 'stop']
            ],
            'usage' => []
        ]));

        $client = $this->createMockClient([$mockResponse]);
        $provider = new OpenAIProvider('test-key', 'gpt-4', null, $client);

        $result = $provider->chat([['role' => 'user', 'content' => 'Test']]);

        $this->assertGreaterThan(0, $result->responseTime);
    }
}
