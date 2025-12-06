<?php

declare(strict_types=1);

namespace Tests\AI;

use App\AI\AIClient;
use App\AI\Providers\AIProviderInterface;
use App\AI\Providers\AIResponse;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests for AIClient
 */
class AIClientTest extends TestCase
{
    private function createMockProvider(string $name, AIResponse $response, bool $available = true): AIProviderInterface
    {
        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('getProviderName')->willReturn($name);
        $provider->method('getModel')->willReturn('test-model');
        $provider->method('isAvailable')->willReturn($available);
        $provider->method('chat')->willReturn($response);
        
        return $provider;
    }

    public function test_it_uses_primary_provider_when_successful(): void
    {
        $successResponse = new AIResponse(
            content: 'Test response',
            model: 'gpt-4',
            provider: 'openai'
        );

        $provider = $this->createMockProvider('OpenAI', $successResponse);
        $client = new AIClient($provider);

        $result = $client->chat([
            ['role' => 'user', 'content' => 'Test prompt']
        ]);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Test response', $result->content);
        $this->assertEquals('openai', $result->provider);
    }

    public function test_it_falls_back_to_secondary_provider(): void
    {
        $failureResponse = new AIResponse(
            content: '',
            model: 'gpt-4',
            provider: 'openai',
            error: 'API error'
        );

        $successResponse = new AIResponse(
            content: 'Fallback response',
            model: 'claude-3',
            provider: 'anthropic'
        );

        $provider1 = $this->createMockProvider('OpenAI', $failureResponse);
        $provider2 = $this->createMockProvider('Anthropic', $successResponse);

        $client = new AIClient([$provider1, $provider2]);

        $result = $client->chat([
            ['role' => 'user', 'content' => 'Test prompt']
        ]);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Fallback response', $result->content);
        $this->assertEquals('anthropic', $result->provider);
    }

    public function test_it_skips_unavailable_providers(): void
    {
        $successResponse = new AIResponse(
            content: 'Success from available provider',
            model: 'gpt-4',
            provider: 'openai'
        );

        $provider1 = $this->createMockProvider('Unavailable', new AIResponse('', 'test', 'test'), false);
        $provider2 = $this->createMockProvider('OpenAI', $successResponse, true);

        $client = new AIClient([$provider1, $provider2]);

        $result = $client->chat([
            ['role' => 'user', 'content' => 'Test']
        ]);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('openai', $result->provider);
    }

    public function test_it_returns_error_when_all_providers_fail(): void
    {
        $errorResponse1 = new AIResponse('', 'gpt-4', 'openai', error: 'Error 1');
        $errorResponse2 = new AIResponse('', 'claude-3', 'anthropic', error: 'Error 2');

        $provider1 = $this->createMockProvider('OpenAI', $errorResponse1);
        $provider2 = $this->createMockProvider('Anthropic', $errorResponse2);

        $client = new AIClient([$provider1, $provider2]);

        $result = $client->chat([
            ['role' => 'user', 'content' => 'Test']
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('All providers failed', $result->error);
        $this->assertStringContainsString('OpenAI', $result->error);
        $this->assertStringContainsString('Anthropic', $result->error);
    }

    public function test_prompt_convenience_method(): void
    {
        $successResponse = new AIResponse(
            content: 'Paris is the capital',
            model: 'gpt-4',
            provider: 'openai'
        );

        $provider = $this->createMockProvider('OpenAI', $successResponse);
        $client = new AIClient($provider);

        $result = $client->prompt('What is the capital of France?');

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Paris is the capital', $result->content);
    }

    public function test_prompt_with_system_message(): void
    {
        $successResponse = new AIResponse(
            content: 'PARIS',
            model: 'gpt-4',
            provider: 'openai'
        );

        $provider = $this->createMock(AIProviderInterface::class);
        $provider->method('getProviderName')->willReturn('OpenAI');
        $provider->method('isAvailable')->willReturn(true);
        
        // Verify system message is included
        $provider->expects($this->once())
            ->method('chat')
            ->with(
                $this->callback(function ($messages) {
                    return count($messages) === 2 &&
                           $messages[0]['role'] === 'system' &&
                           $messages[0]['content'] === 'Reply in uppercase' &&
                           $messages[1]['role'] === 'user';
                })
            )
            ->willReturn($successResponse);

        $client = new AIClient($provider);
        $result = $client->prompt('What is the capital?', 'Reply in uppercase');

        $this->assertTrue($result->isSuccess());
    }

    public function test_has_available_provider(): void
    {
        $provider1 = $this->createMockProvider('Unavailable', new AIResponse('', 'test', 'test'), false);
        $provider2 = $this->createMockProvider('Available', new AIResponse('', 'test', 'test'), true);

        $client1 = new AIClient([$provider1]);
        $this->assertFalse($client1->hasAvailableProvider());

        $client2 = new AIClient([$provider1, $provider2]);
        $this->assertTrue($client2->hasAvailableProvider());
    }

    public function test_add_provider(): void
    {
        $provider1 = $this->createMockProvider('Provider1', new AIResponse('', 'test', 'test'));
        $provider2 = $this->createMockProvider('Provider2', new AIResponse('', 'test', 'test'));

        $client = new AIClient($provider1);
        $this->assertCount(1, $client->getProviders());

        $client->addProvider($provider2);
        $this->assertCount(2, $client->getProviders());
    }

    public function test_get_primary_provider(): void
    {
        $provider1 = $this->createMockProvider('Primary', new AIResponse('', 'test', 'test'));
        $provider2 = $this->createMockProvider('Secondary', new AIResponse('', 'test', 'test'));

        $client = new AIClient([$provider1, $provider2]);

        $primary = $client->getPrimaryProvider();
        $this->assertNotNull($primary);
        $this->assertEquals('Primary', $primary->getProviderName());
    }
}
