<?php
namespace Ksfraser\LLM\Interfaces;

/**
 * LLM Provider Interface
 * 
 * Contract for all LLM providers (OpenAI, Anthropic, etc.)
 */
interface LLMProviderInterface
{
    /**
     * Generate a response from the LLM
     * 
     * @param string $prompt The input prompt
     * @param array $options Additional options (temperature, max_tokens, etc.)
     * @return array Response with content, usage stats, etc.
     */
    public function generateResponse(string $prompt, array $options = []): array;

    /**
     * Analyze financial news or reports
     * 
     * @param string $content News article or report content
     * @param array $context Additional context (symbol, date, etc.)
     * @return array Analysis with sentiment, key points, trading implications
     */
    public function analyzeFinancialContent(string $content, array $context = []): array;

    /**
     * Generate trading strategy analysis
     * 
     * @param array $marketData Historical market data
     * @param array $indicators Technical indicators
     * @param string $strategy Strategy name
     * @return array Strategy analysis and recommendations
     */
    public function analyzeStrategy(array $marketData, array $indicators, string $strategy): array;

    /**
     * Get provider name
     */
    public function getProviderName(): string;

    /**
     * Check if provider is available (API key set, etc.)
     */
    public function isAvailable(): bool;

    /**
     * Score a trading strategy based on backtest results
     * 
     * @param string $strategyName Name of the strategy
     * @param array $backtestResults Backtest performance metrics
     * @param array $marketConditions Market condition summary
     * @return array Strategy scoring and analysis
     */
    public function scoreStrategy(string $strategyName, array $backtestResults, array $marketConditions): array;
}
