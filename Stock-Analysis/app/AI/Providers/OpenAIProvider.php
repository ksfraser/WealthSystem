<?php

declare(strict_types=1);

namespace App\AI\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * OpenAI API provider implementation
 * 
 * Supports GPT-4, GPT-3.5-turbo, and other OpenAI chat models
 */
class OpenAIProvider implements AIProviderInterface
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';
    
    private Client $httpClient;
    private LoggerInterface $logger;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'gpt-4',
        ?LoggerInterface $logger = null,
        ?Client $httpClient = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => 60.0,
            'verify' => false,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function chat(array $messages, array $options = []): AIResponse
    {
        $startTime = microtime(true);

        try {
            $payload = [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => $options['temperature'] ?? 0.3,
                'max_tokens' => $options['max_tokens'] ?? 1500,
            ];

            // Add optional parameters
            if (isset($options['top_p'])) {
                $payload['top_p'] = $options['top_p'];
            }
            if (isset($options['frequency_penalty'])) {
                $payload['frequency_penalty'] = $options['frequency_penalty'];
            }
            if (isset($options['presence_penalty'])) {
                $payload['presence_penalty'] = $options['presence_penalty'];
            }
            if (isset($options['stop'])) {
                $payload['stop'] = $options['stop'];
            }

            $this->logger->info("Sending OpenAI chat request", [
                'model' => $this->model,
                'message_count' => count($messages),
            ]);

            $response = $this->httpClient->post(self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode((string)$response->getBody(), true);
            $responseTime = microtime(true) - $startTime;

            if (!isset($body['choices'][0]['message']['content'])) {
                throw new \RuntimeException("Invalid OpenAI response structure");
            }

            $usage = $body['usage'] ?? [];

            return new AIResponse(
                content: $body['choices'][0]['message']['content'],
                model: $body['model'] ?? $this->model,
                provider: 'openai',
                promptTokens: $usage['prompt_tokens'] ?? 0,
                completionTokens: $usage['completion_tokens'] ?? 0,
                finishReason: $body['choices'][0]['finish_reason'] ?? null,
                error: null,
                responseTime: $responseTime
            );
        } catch (GuzzleException $e) {
            $responseTime = microtime(true) - $startTime;
            $this->logger->error("OpenAI API request failed", [
                'error' => $e->getMessage(),
            ]);

            return new AIResponse(
                content: '',
                model: $this->model,
                provider: 'openai',
                error: "OpenAI API request failed: " . $e->getMessage(),
                responseTime: $responseTime
            );
        } catch (\Exception $e) {
            $responseTime = microtime(true) - $startTime;
            $this->logger->error("OpenAI processing error", [
                'error' => $e->getMessage(),
            ]);

            return new AIResponse(
                content: '',
                model: $this->model,
                provider: 'openai',
                error: $e->getMessage(),
                responseTime: $responseTime
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getProviderName(): string
    {
        return 'OpenAI';
    }

    /**
     * {@inheritDoc}
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }
}
