<?php
/**
 * LLM Provider Interface for Financial Analysis
 * 
 * Defines the contract for Large Language Model providers that can analyze financial data.
 * Enables integration with ChatGPT, Claude, or other LLM services.
 */

namespace Ksfraser\Finance\Interfaces;

interface LLMProviderInterface
{
    /**
     * Analyze financial data using LLM
     * 
     * @param array $data Financial data to analyze
     * @param string $query Specific query or analysis request
     * @return string Analysis result
     */
    public function analyzeFinancialData(array $data, string $query): string;
    
    /**
     * Get investment recommendation
     * 
     * @param string $symbol Stock symbol
     * @param array $financialData Complete financial data
     * @return array Recommendation with confidence score
     */
    public function getRecommendation(string $symbol, array $financialData): array;
    
    /**
     * Check if the LLM provider is available
     * 
     * @return bool True if the provider can be used
     */
    public function isAvailable(): bool;
    
    /**
     * Get the name of the LLM provider
     * 
     * @return string Provider name
     */
    public function getName(): string;
}
