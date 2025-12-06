<?php

declare(strict_types=1);

namespace App\AI\Providers;

/**
 * Standardized AI response
 * 
 * Normalizes responses from different AI providers into a common format
 */
class AIResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $model,
        public readonly string $provider,
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly ?string $finishReason = null,
        public readonly ?string $error = null,
        public readonly float $responseTime = 0.0
    ) {
    }

    /**
     * Check if response was successful
     */
    public function isSuccess(): bool
    {
        return $this->error === null && !empty($this->content);
    }

    /**
     * Get total token usage
     */
    public function getTotalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens;
    }

    /**
     * Convert to array
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'model' => $this->model,
            'provider' => $this->provider,
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens' => $this->getTotalTokens(),
            'finish_reason' => $this->finishReason,
            'error' => $this->error,
            'response_time' => $this->responseTime,
            'success' => $this->isSuccess(),
        ];
    }
}
