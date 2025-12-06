<?php

declare(strict_types=1);

namespace App\AI;

use App\AI\Providers\AIProviderInterface;
use App\AI\Providers\AIResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Generic AI client with provider abstraction
 * 
 * This service abstracts the AI provider (OpenAI, Anthropic, etc.) and provides
 * a unified interface for AI-powered features throughout the application.
 * 
 * Features:
 * - Multi-provider support (OpenAI, Anthropic, Google, etc.)
 * - Automatic fallback between providers
 * - Token usage tracking
 * - Response caching (optional)
 * - Rate limiting (optional)
 * 
 * @example
 * ```php
 * $client = new AIClient($openAIProvider);
 * 
 * $response = $client->chat([
 *     ['role' => 'system', 'content' => 'You are a helpful assistant.'],
 *     ['role' => 'user', 'content' => 'What is the capital of France?'],
 * ]);
 * 
 * echo $response->content; // "Paris"
 * ```
 */
class AIClient
{
    private LoggerInterface $logger;

    /** @var array<AIProviderInterface> */
    private array $providers = [];

    private ?AIProviderInterface $primaryProvider = null;

    /**
     * @param AIProviderInterface|array<AIProviderInterface> $providers Single provider or array of providers
     */
    public function __construct(
        AIProviderInterface|array $providers,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();

        if (is_array($providers)) {
            $this->providers = $providers;
            $this->primaryProvider = $providers[0] ?? null;
        } else {
            $this->providers = [$providers];
            $this->primaryProvider = $providers;
        }
    }

    /**
     * Send a chat completion request
     * 
     * Tries the primary provider first, then falls back to other providers if available.
     * 
     * @param array<int, array<string, string>> $messages Messages with 'role' and 'content'
     * @param array<string, mixed> $options Provider-specific options
     * @return AIResponse
     */
    public function chat(array $messages, array $options = []): AIResponse
    {
        $errors = [];

        foreach ($this->providers as $provider) {
            if (!$provider->isAvailable()) {
                $this->logger->debug("Skipping unavailable provider: {$provider->getProviderName()}");
                continue;
            }

            $this->logger->info("Attempting chat with provider: {$provider->getProviderName()}");

            $response = $provider->chat($messages, $options);

            if ($response->isSuccess()) {
                $this->logger->info("Chat successful", [
                    'provider' => $provider->getProviderName(),
                    'model' => $provider->getModel(),
                    'tokens' => $response->getTotalTokens(),
                    'response_time' => $response->responseTime,
                ]);
                return $response;
            }

            $errors[] = "{$provider->getProviderName()}: {$response->error}";
            $this->logger->warning("Provider failed: {$provider->getProviderName()}", [
                'error' => $response->error,
            ]);
        }

        // All providers failed
        $this->logger->error("All AI providers failed", ['errors' => $errors]);

        return new AIResponse(
            content: '',
            model: $this->primaryProvider?->getModel() ?? 'unknown',
            provider: 'none',
            error: "All providers failed: " . implode('; ', $errors)
        );
    }

    /**
     * Send a simple prompt and get text response
     * 
     * Convenience method for single-turn conversations
     */
    public function prompt(string $prompt, string $systemMessage = '', array $options = []): AIResponse
    {
        $messages = [];

        if (!empty($systemMessage)) {
            $messages[] = ['role' => 'system', 'content' => $systemMessage];
        }

        $messages[] = ['role' => 'user', 'content' => $prompt];

        return $this->chat($messages, $options);
    }

    /**
     * Get the primary provider
     */
    public function getPrimaryProvider(): ?AIProviderInterface
    {
        return $this->primaryProvider;
    }

    /**
     * Get all providers
     * 
     * @return array<AIProviderInterface>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Add a fallback provider
     */
    public function addProvider(AIProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * Check if any provider is available
     */
    public function hasAvailableProvider(): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->isAvailable()) {
                return true;
            }
        }
        return false;
    }
}
