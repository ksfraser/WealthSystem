<?php

namespace WealthSystem\StockAnalysis\AI;

use WealthSystem\StockAnalysis\Data\FundamentalDataService;
use WealthSystem\StockAnalysis\Data\NewsSentimentService;
use WealthSystem\StockAnalysis\Portfolio\PortfolioOptimizerInterface;
use WealthSystem\StockAnalysis\Portfolio\PortfolioRiskAnalyzer;
use Psr\Log\LoggerInterface;

/**
 * LLM Trading Assistant with Full Integration
 * 
 * Combines fundamentals, sentiment, AND portfolio optimization for comprehensive
 * trading recommendations with optimal position sizing.
 * 
 * Features:
 * - Fundamental data (P/E, ROE, margins, growth)
 * - News sentiment analysis (market mood)
 * - Portfolio optimization (MPT, efficient frontier)
 * - Risk analysis (Sharpe ratio, VaR, beta)
 * - Optimal position sizing recommendations
 * 
 * Configuration:
 * - 'use_fundamentals' => bool
 * - 'use_sentiment' => bool
 * - 'use_optimization' => bool
 * - 'optimization_method' => string ('sharpe', 'variance', 'target')
 * - 'target_return' => float (for target return method)
 * - 'risk_tolerance' => string ('conservative', 'moderate', 'aggressive')
 * - 'watchlist' => array
 * 
 * Example:
 * ```php
 * $assistant = new LLMTradingAssistantWithOptimization(
 *     $aiClient,
 *     $fundamentalService,
 *     $sentimentService,
 *     $optimizer
 * );
 * 
 * $recommendations = $assistant->getRecommendations(
 *     $holdings,
 *     $cashBalance,
 *     $totalEquity,
 *     [
 *         'use_fundamentals' => true,
 *         'use_sentiment' => true,
 *         'use_optimization' => true,
 *         'optimization_method' => 'sharpe',
 *         'risk_tolerance' => 'moderate',
 *     ]
 * );
 * ```
 */
class LLMTradingAssistantWithOptimization extends LLMTradingAssistantWithSentiment
{
    private readonly PortfolioRiskAnalyzer $riskAnalyzer;

    public function __construct(
        AIClient $aiClient,
        ?FundamentalDataService $fundamentalService = null,
        ?NewsSentimentService $sentimentService = null,
        private readonly ?PortfolioOptimizerInterface $optimizer = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($aiClient, $fundamentalService, $sentimentService, $logger);
        $this->riskAnalyzer = new PortfolioRiskAnalyzer($logger);
    }

    /**
     * Generate recommendations with full integration
     * 
     * {@inheritdoc}
     */
    public function getRecommendations(
        array $holdings,
        float $cashBalance,
        float $totalEquity,
        array $config = []
    ): TradingRecommendation {
        // Check if optimization is enabled
        $useOptimization = $config['use_optimization'] ?? true;

        if (!$useOptimization || $this->optimizer === null || !$this->optimizer->isAvailable()) {
            // Fall back to parent (fundamentals + sentiment only)
            return parent::getRecommendations($holdings, $cashBalance, $totalEquity, $config);
        }

        // Extract tickers
        $holdingTickers = array_map(fn($holding) => $holding['ticker'], $holdings);
        $watchlist = $config['watchlist'] ?? [];
        $allTickers = array_unique(array_merge($holdingTickers, $watchlist));

        // Run portfolio optimization
        $this->logger->info("Running portfolio optimization for " . count($allTickers) . " tickers");
        
        $optimizationMethod = $config['optimization_method'] ?? 'sharpe';
        $optimizationResult = match($optimizationMethod) {
            'variance' => $this->optimizer->minimizeVariance($allTickers),
            'target' => $this->optimizer->targetReturn($allTickers, $config['target_return'] ?? 0.10),
            default => $this->optimizer->maximizeSharpeRatio($allTickers),
        };

        // Store optimization result in config for prompt enhancement
        $config['optimization'] = $optimizationResult;

        // Call parent (which handles fundamentals + sentiment)
        return parent::getRecommendations($holdings, $cashBalance, $totalEquity, $config);
    }

    /**
     * Generate enhanced prompt with optimization data
     * 
     * {@inheritdoc}
     */
    protected function generatePromptEnhanced(
        array $holdings,
        float $cashBalance,
        float $totalEquity,
        array $config = []
    ): string {
        // Get base prompt with fundamentals + sentiment from parent
        $prompt = parent::generatePromptEnhanced($holdings, $cashBalance, $totalEquity, $config);

        // Add optimization data if available
        $optimization = $config['optimization'] ?? null;
        if ($optimization && $optimization->isValid()) {
            $prompt = $this->enhancePromptWithOptimization($prompt, $optimization, $totalEquity, $config);
        }

        return $prompt;
    }

    /**
     * Enhance prompt with portfolio optimization recommendations
     */
    private function enhancePromptWithOptimization(
        string $prompt,
        $optimization,
        float $totalEquity,
        array $config
    ): string {
        $optimizationText = "\n\n=== PORTFOLIO OPTIMIZATION ===\n\n";
        $optimizationText .= $optimization->toPromptString() . "\n\n";

        // Calculate dollar allocations
        $optimizationText .= "Recommended Dollar Allocations (Total Portfolio: $" . number_format($totalEquity, 2) . "):\n";
        foreach ($optimization->getSortedTickers() as $ticker) {
            $weight = $optimization->getWeight($ticker);
            if ($weight >= 0.01) { // Show weights >= 1%
                $dollars = $totalEquity * $weight;
                $optimizationText .= "  {$ticker}: $" . number_format($dollars, 2) . " (" . number_format($weight * 100, 1) . "%)\n";
            }
        }

        $optimizationText .= "\n=== TRADING INSTRUCTIONS WITH OPTIMIZATION ===\n\n";
        $optimizationText .= "When making trading recommendations:\n";
        $optimizationText .= "1. Use the OPTIMAL WEIGHTS from portfolio optimization as target allocation\n";
        $optimizationText .= "2. Compare current holdings to optimal weights\n";
        $optimizationText .= "3. Recommend trades to move toward optimal allocation\n";
        $optimizationText .= "4. Consider transaction costs and tax implications\n";
        $optimizationText .= "5. Adjust for risk tolerance:\n";
        
        $riskTolerance = $config['risk_tolerance'] ?? 'moderate';
        $optimizationText .= "   - Current risk tolerance: {$riskTolerance}\n";
        $optimizationText .= "   - Conservative: Favor minimum variance, bonds, defensive stocks\n";
        $optimizationText .= "   - Moderate: Balance risk/return, follow optimal weights closely\n";
        $optimizationText .= "   - Aggressive: Accept higher volatility for higher returns\n";
        
        $optimizationText .= "6. Cite specific metrics: Sharpe ratio, expected return, volatility\n";
        $optimizationText .= "7. Explain how recommendations improve portfolio efficiency\n\n";

        return $prompt . $optimizationText;
    }

    /**
     * Check if optimizer is available
     */
    public function hasOptimizer(): bool
    {
        return $this->optimizer !== null && $this->optimizer->isAvailable();
    }

    /**
     * Get optimizer (for direct access if needed)
     */
    public function getOptimizer(): ?PortfolioOptimizerInterface
    {
        return $this->optimizer;
    }

    /**
     * Get risk analyzer (for direct access)
     */
    public function getRiskAnalyzer(): PortfolioRiskAnalyzer
    {
        return $this->riskAnalyzer;
    }
}
