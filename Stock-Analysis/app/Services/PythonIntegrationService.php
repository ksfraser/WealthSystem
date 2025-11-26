<?php

namespace App\Services;

/**
 * Python Integration Service (Refactored with DI)
 * 
 * High-level orchestration service for Python script integration.
 * Delegates responsibilities to specialized services following SRP and DI principles.
 * 
 * Dependencies (injected):
 * - PythonBridgeService: Manages bridge script
 * - PythonExecutorService: Executes scripts securely
 * - PythonResponseParser: Parses responses
 * 
 * This service now focuses ONLY on orchestration, not implementation details.
 * 
 * @package App\Services
 * @version 2.0.0 (Refactored with DI and SRP)
 */
class PythonIntegrationService
{
    /**
     * Bridge service for script management
     * 
     * @var PythonBridgeService
     */
    private PythonBridgeService $bridgeService;
    
    /**
     * Executor service for running scripts
     * 
     * @var PythonExecutorService
     */
    private PythonExecutorService $executorService;
    
    /**
     * Parser for response handling
     * 
     * @var PythonResponseParser
     */
    private PythonResponseParser $parser;
    
    /**
     * Constructor with dependency injection
     * 
     * @param PythonBridgeService|null $bridgeService Bridge service (optional, creates default)
     * @param PythonExecutorService|null $executorService Executor service (optional, creates default)
     * @param PythonResponseParser|null $parser Response parser (optional, creates default)
     */
    public function __construct(
        ?PythonBridgeService $bridgeService = null,
        ?PythonExecutorService $executorService = null,
        ?PythonResponseParser $parser = null
    ) {
        $this->bridgeService = $bridgeService ?? new PythonBridgeService();
        $this->executorService = $executorService ?? new PythonExecutorService();
        $this->parser = $parser ?? new PythonResponseParser();
    }
    
    /**
     * Fetch price data for a symbol using trading_script.py
     * 
     * @param string $symbol Stock symbol
     * @param string $period Time period (default: 1y)
     * 
     * @return array{success: bool, data?: array, error?: string}
     */
    public function fetchPriceData(string $symbol, string $period = '1y'): array
    {
        // Ensure bridge script exists
        $scriptPath = $this->bridgeService->ensureBridgeScript();
        
        // Execute via executor service
        $result = $this->executorService->executeScript(
            basename($scriptPath),
            'fetch_price_data',
            [
                'symbol' => $symbol,
                'period' => $period
            ]
        );
        
        // Parse response
        return $this->parser->parseProcessResult($result);
    }
    
    /**
     * Get portfolio data using existing Python systems
     * 
     * @param string|null $portfolioFile Portfolio CSV file path
     * 
     * @return array{success: bool, data?: array, error?: string}
     */
    public function getPortfolioData(?string $portfolioFile = null): array
    {
        $portfolioFile = $portfolioFile ?? 'Scripts and CSV Files/chatgpt_portfolio_update.csv';
        
        // Ensure bridge script exists
        $scriptPath = $this->bridgeService->ensureBridgeScript();
        
        // Execute via executor service
        $result = $this->executorService->executeScript(
            basename($scriptPath),
            'get_portfolio_data',
            ['file' => $portfolioFile]
        );
        
        // Parse response
        return $this->parser->parseProcessResult($result);
    }
    
    /**
     * Update prices for multiple symbols
     * 
     * @param array<string> $symbols Stock symbols to update
     * 
     * @return array{success: bool, results?: array, error?: string}
     */
    public function updateMultipleSymbols(array $symbols): array
    {
        // Ensure bridge script exists
        $scriptPath = $this->bridgeService->ensureBridgeScript();
        
        // Execute via executor service
        $result = $this->executorService->executeScript(
            basename($scriptPath),
            'update_symbols',
            ['symbols' => $symbols]
        );
        
        // Parse response
        return $this->parser->parseProcessResult($result);
    }
    
    /**
     * Check if Python environment is available
     * 
     * @return array{available: bool, version: string}
     */
    public function checkPythonEnvironment(): array
    {
        return $this->executorService->checkPythonEnvironment();
    }
    
    /**
     * Analyze stock using Python AI analysis module
     * 
     * Uses temp file approach for large data sets to avoid command-line length limits.
     * 
     * @param array $stockData Stock data including symbol, price_data, fundamentals
     * 
     * @return array{success: bool, data?: array, error?: string} Analysis results
     */
    public function analyzeStock(array $stockData): array
    {
        // Use temp file approach for large data
        $result = $this->executorService->executeWithTempFile(
            'python_analysis/analysis.py',
            'analyze-file',
            $stockData
        );
        
        // Parse response
        return $this->parser->parseProcessResult($result);
    }
}