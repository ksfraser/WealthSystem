<?php

declare(strict_types=1);

namespace App\AI\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Anthropic Claude API provider implementation
 * 
 * Supports Claude 3 (Opus, Sonnet, Haiku) and Claude 2 models
 * 
 * Key differences from OpenAI:
 * - Uses different message format (system is separate parameter)
 * - Requires 'anthropic-version' header
 * - Different token naming (input_tokens vs prompt_tokens)
 * - Max tokens is required (no default)
 * 
 * @see https://docs.anthropic.com/claude/reference/messages_post
 */
class AnthropicProvider implements AIProviderInterface
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';
    
    private Client $httpClient;
    private LoggerInterface $logger;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'claude-3-sonnet-20240229',
        ?LoggerInterface $logger = null,
        ?Client $httpClient = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => 120.0, // Claude can be slower than GPT
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
            // Convert OpenAI-style messages to Anthropic format
            [$systemMessage, $anthropicMessages] = $this->convertMessages($messages);

            $payload = [
                'model' => $this->model,
                'messages' => $anthropicMessages,
                'max_tokens' => $options['max_tokens'] ?? 4096, // Required for Anthropic
                'temperature' => $options['temperature'] ?? 0.3,
            ];

            // Add system message if present
            if ($systemMessage !== null) {
                $payload['system'] = $systemMessage;
            }

            // Add optional parameters
            if (isset($options['top_p'])) {
                $payload['top_p'] = $options['top_p'];
            }
            if (isset($options['top_k'])) {
                $payload['top_k'] = $options['top_k'];
            }
            if (isset($options['stop_sequences'])) {
                $payload['stop_sequences'] = $options['stop_sequences'];
            }

            $this->logger->info("Sending Anthropic chat request", [
                'model' => $this->model,
                'message_count' => count($anthropicMessages),
            ]);

            $response = $this->httpClient->post(self::API_URL, [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => self::API_VERSION,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode((string)$response->getBody(), true);
            $responseTime = microtime(true) - $startTime;

            if (!isset($body['content'][0]['text'])) {
                throw new \RuntimeException("Invalid Anthropic response structure");
            }

            $usage = $body['usage'] ?? [];

            return new AIResponse(
                content: $body['content'][0]['text'],
                model: $body['model'] ?? $this->model,
                provider: 'anthropic',
                promptTokens: $usage['input_tokens'] ?? 0,
                completionTokens: $usage['output_tokens'] ?? 0,
                finishReason: $body['stop_reason'] ?? null,
                error: null,
                responseTime: $responseTime
            );
        } catch (GuzzleException $e) {
            $responseTime = microtime(true) - $startTime;
            
            // Parse Anthropic error response
            $errorMessage = $this->parseErrorResponse($e);
            
            $this->logger->error("Anthropic API request failed", [
                'error' => $errorMessage,
                'status_code' => $e->getCode(),
            ]);

            return new AIResponse(
                content: '',
                model: $this->model,
                provider: 'anthropic',
                error: "Anthropic API request failed: " . $errorMessage,
                responseTime: $responseTime
            );
        } catch (\Exception $e) {
            $responseTime = microtime(true) - $startTime;
            $this->logger->error("Anthropic processing error", [
                'error' => $e->getMessage(),
            ]);

            return new AIResponse(
                content: '',
                model: $this->model,
                provider: 'anthropic',
                error: $e->getMessage(),
                responseTime: $responseTime
            );
        }
    }

    /**
     * Convert OpenAI-style messages to Anthropic format
     * 
     * Anthropic separates system messages from the messages array:
     * - System message goes in 'system' parameter
     * - User/assistant messages go in 'messages' array
     * - Must alternate user/assistant roles
     * 
     * @param array $messages OpenAI-style messages
     * @return array [systemMessage, anthropicMessages]
     */
    private function convertMessages(array $messages): array
    {
        $systemMessage = null;
        $anthropicMessages = [];

        foreach ($messages as $message) {
            $role = $message['role'] ?? 'user';
            $content = $message['content'] ?? '';

            if ($role === 'system') {
                // System message is separate in Anthropic
                $systemMessage = $content;
            } else {
                // Convert to Anthropic format
                $anthropicMessages[] = [
                    'role' => $role,
                    'content' => $content,
                ];
            }
        }

        return [$systemMessage, $anthropicMessages];
    }

    /**
     * Parse Anthropic error response
     * 
     * @param GuzzleException $e
     * @return string
     */
    private function parseErrorResponse(GuzzleException $e): string
    {
        if (!$e->hasResponse()) {
            return $e->getMessage();
        }

        try {
            $response = $e->getResponse();
            $body = json_decode((string)$response->getBody(), true);
            
            if (isset($body['error']['message'])) {
                return $body['error']['message'];
            }
            
            if (isset($body['error']['type'])) {
                return $body['error']['type'];
            }
        } catch (\Exception $ex) {
            // Fall through to default message
        }

        return $e->getMessage();
    }

    /**
     * {@inheritDoc}
     */
    public function getProviderName(): string
    {
        return 'Anthropic';
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

    /**
     * Get list of supported Claude models
     * 
     * @return array
     */
    public static function getSupportedModels(): array
    {
        return [
            // Claude 3 family (most recent)
            'claude-3-opus-20240229' => [
                'name' => 'Claude 3 Opus',
                'context' => 200000,
                'output' => 4096,
                'cost_per_1m_input' => 15.00,
                'cost_per_1m_output' => 75.00,
                'description' => 'Most capable model for complex tasks',
            ],
            'claude-3-sonnet-20240229' => [
                'name' => 'Claude 3 Sonnet',
                'context' => 200000,
                'output' => 4096,
                'cost_per_1m_input' => 3.00,
                'cost_per_1m_output' => 15.00,
                'description' => 'Balanced performance and speed',
            ],
            'claude-3-haiku-20240307' => [
                'name' => 'Claude 3 Haiku',
                'context' => 200000,
                'output' => 4096,
                'cost_per_1m_input' => 0.25,
                'cost_per_1m_output' => 1.25,
                'description' => 'Fastest and most compact',
            ],
            
            // Claude 2 family (legacy)
            'claude-2.1' => [
                'name' => 'Claude 2.1',
                'context' => 200000,
                'output' => 4096,
                'cost_per_1m_input' => 8.00,
                'cost_per_1m_output' => 24.00,
                'description' => 'Previous generation flagship',
            ],
            'claude-2.0' => [
                'name' => 'Claude 2.0',
                'context' => 100000,
                'output' => 4096,
                'cost_per_1m_input' => 8.00,
                'cost_per_1m_output' => 24.00,
                'description' => 'Previous generation',
            ],
        ];
    }
}
