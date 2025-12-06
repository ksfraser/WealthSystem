<?php

declare(strict_types=1);

namespace App\AI\Providers;

/**
 * Interface for AI/LLM providers
 * 
 * Abstracts the underlying AI service (OpenAI, Anthropic, Google, etc.)
 */
interface AIProviderInterface
{
    /**
     * Send a chat completion request
     * 
     * @param array<int, array<string, string>> $messages Array of message objects with 'role' and 'content'
     * @param array<string, mixed> $options Additional options (temperature, max_tokens, etc.)
     * @return AIResponse
     */
    public function chat(array $messages, array $options = []): AIResponse;

    /**
     * Get the provider name
     */
    public function getProviderName(): string;

    /**
     * Get the model being used
     */
    public function getModel(): string;

    /**
     * Check if the provider is available (API key configured)
     */
    public function isAvailable(): bool;
}
