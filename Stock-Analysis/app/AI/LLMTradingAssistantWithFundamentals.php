<?php

declare(strict_types=1);

namespace App\AI;

use App\Data\FundamentalDataService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * LLM Trading Assistant with Fundamental Data Integration
 * 
 * Extends the base trading assistant with real-time fundamental data fetching.
 * Replaces "Data unavailable" with actual company fundamentals from Alpha Vantage, etc.
 */
class LLMTradingAssistantWithFundamentals extends LLMTradingAssistant
{
    private LoggerInterface $logger;
    private ?FundamentalDataService $fundamentalService = null;

    public function __construct(
        AIClient $aiClient,
        ?FundamentalDataService $fundamentalService = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($aiClient, $logger);
        $this->fundamentalService = $fundamentalService;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Generate trading recommendations with real fundamental data
     * 
     * @param array<int, array<string, mixed>> $holdings Current portfolio holdings
     * @param float $cashBalance Available cash
     * @param float $totalEquity Total portfolio value
     * @param array<string, mixed> $config Additional configuration
     * @return TradingRecommendation
     */
    public function getRecommendations(
        array $holdings,
        float $cashBalance,
        float $totalEquity,
        array $config = []
    ): TradingRecommendation {
        // If fundamental data is disabled or not available, use parent implementation
        if ($this->fundamentalService === null || 
            !$this->fundamentalService->hasAvailableProvider() ||
            ($config['use_fundamentals'] ?? true) === false) {
            
            $this->logger->info("Fundamental data not available, using base assistant");
            return parent::getRecommendations($holdings, $cashBalance, $totalEquity, $config);
        }

        // Fetch fundamental data for existing holdings and watchlist
        $tickers = $this->extractTickers($holdings, $config);
        $fundamentals = $this->fundamentalService->getBatchFundamentals($tickers, useCache: true);

        // Add fundamentals to config for prompt generation
        $config['fundamentals'] = $fundamentals;

        $this->logger->info("Fetched fundamentals for " . count($fundamentals) . " tickers", [
            'valid' => array_sum(array_map(fn($f) => $f->isValid() ? 1 : 0, $fundamentals)),
            'invalid' => array_sum(array_map(fn($f) => !$f->isValid() ? 1 : 0, $fundamentals)),
        ]);

        // Get recommendations (will use enhanced prompt with fundamentals)
        return parent::getRecommendations($holdings, $cashBalance, $totalEquity, $config);
    }

    /**
     * Extract tickers from holdings and watchlist
     * 
     * @return array<string>
     */
    private function extractTickers(array $holdings, array $config): array
    {
        $tickers = [];

        // Extract from holdings
        foreach ($holdings as $holding) {
            if (isset($holding['ticker'])) {
                $tickers[] = (string)$holding['ticker'];
            }
        }

        // Add from watchlist if provided
        if (isset($config['watchlist']) && is_array($config['watchlist'])) {
            $tickers = array_merge($tickers, $config['watchlist']);
        }

        return array_unique(array_filter($tickers));
    }

    /**
     * Generate enhanced trading prompt with real fundamental data
     * 
     * Overrides parent method to include fundamental data in prompt
     */
    protected function generatePromptEnhanced(
        array $holdings,
        float $cashBalance,
        float $totalEquity,
        array $config
    ): string {
        $today = date('Y-m-d');

        // Format holdings
        $holdingsText = empty($holdings) ? "No current holdings" : $this->formatHoldings($holdings);

        // Extract config
        $maxPositionSize = $config['max_position_size_pct'] ?? 10;
        $minConfidence = $config['min_confidence'] ?? 0.70;
        $marketCapLimit = $config['market_cap_limit'] ?? 300000000;
        $minCashReserve = $config['min_cash_reserve'] ?? 500;
        $focus = $config['focus'] ?? 'micro-cap';

        $maxPositionDollars = round($totalEquity * ($maxPositionSize / 100), 2);

        // Format fundamental data if available
        $fundamentalsText = $this->formatFundamentals($config['fundamentals'] ?? []);

        $prompt = <<<PROMPT
You are a professional portfolio analyst specializing in micro-cap stocks. Today is {$today}.

=== PORTFOLIO STATE ===

Current Holdings:
{$holdingsText}

Cash Available: \${$cashBalance}
Total Portfolio Value: \${$totalEquity}
Max Position Size: {$maxPositionSize}% (\${$maxPositionDollars})
Min Cash Reserve: \${$minCashReserve}

=== FUNDAMENTAL DATA ===

{$fundamentalsText}

=== INVESTMENT MANDATE ===

Focus: U.S. {$focus} stocks (market cap < \${$marketCapLimit})
Position Limits: {$maxPositionSize}% max per position, full shares only
Risk Tools: Stop-losses required on all positions
Confidence Threshold: Minimum {$minConfidence} to recommend

=== ANALYSIS FRAMEWORK ===

For each potential trade, analyze:

1. **Fundamental Drivers**
   - Use the provided fundamental data above
   - Analyze revenue growth trajectory
   - Evaluate profit margins and trends
   - Assess cash flow quality
   - Review balance sheet health (debt/equity, current ratio)
   - Compare valuation metrics (P/E, P/S, EV/EBITDA) to peers

2. **Bull Case**
   - Key catalysts over next 6-12 months
   - Competitive advantages or moats
   - Management track record
   - Assumptions that must hold true

3. **Bear Case**
   - Primary risks to thesis
   - What could invalidate the investment?
   - Regulatory, competitive, or macro headwinds
   - Leading indicators to monitor

4. **Scenario Analysis**
   - Base case: Expected outcome with conservative assumptions
   - Upside: What would drive 2x returns?
   - Downside: What triggers stop-loss or exit?

5. **Position Sizing Logic**
   - Risk/reward ratio calculation
   - Why this position size vs others?
   - Portfolio diversification impact

=== VERIFICATION REQUIREMENTS ===

For each recommendation:
- Use the provided fundamental data (revenue, margins, ratios, growth)
- State confidence level (0.0 to 1.0) with reasoning based on fundamentals
- List 2-3 key assumptions
- Identify 2-3 risk triggers that would invalidate thesis
- Provide specific entry price and stop-loss with rationale
- Only state "Data unavailable" if specific metrics are truly missing from the provided data

=== OUTPUT FORMAT ===

Respond with ONLY valid JSON:

{
  "market_analysis": {
    "summary": "Current macro environment assessment (2-3 sentences)",
    "key_themes": ["Theme 1", "Theme 2"],
    "risks": ["Risk 1", "Risk 2"]
  },
  "trades": [
    {
      "action": "buy",
      "ticker": "SYMBOL",
      "shares": 100,
      "entry_price": 25.50,
      "stop_loss": 20.00,
      "price_target": 35.00,
      
      "fundamentals": {
        "revenue_growth_yoy": "Use provided data",
        "operating_margin": "Use provided data",
        "pe_ratio": "Use provided data",
        "debt_to_equity": "Use provided data",
        "note": "Analysis of provided fundamentals"
      },
      
      "bull_case": {
        "thesis": "Concise bull thesis based on fundamentals (1-2 sentences)",
        "catalysts": ["Catalyst 1", "Catalyst 2"],
        "assumptions": ["Assumption 1", "Assumption 2"]
      },
      
      "bear_case": {
        "thesis": "Concise bear thesis (1-2 sentences)",
        "risks": ["Risk 1", "Risk 2"],
        "invalidation_triggers": ["What would prove this wrong?"]
      },
      
      "scenarios": {
        "base": {"return_pct": 20, "probability_pct": 50, "conditions": "Expected conditions"},
        "upside": {"return_pct": 100, "probability_pct": 20, "conditions": "Best case conditions"},
        "downside": {"return_pct": -20, "probability_pct": 30, "conditions": "Worst case conditions"}
      },
      
      "position_logic": "Why this position size makes sense given the fundamentals",
      "sources": ["Fundamental data from Alpha Vantage", "Q3 2024 10-Q filing"],
      "confidence": 0.75,
      "holding_period": "6-12 months"
    }
  ],
  "portfolio_notes": {
    "diversification": "Portfolio concentration analysis",
    "risk_level": "Current portfolio risk assessment"
  },
  "overall_confidence": 0.8
}

=== CRITICAL RULES ===

- Do NOT recommend trades if confidence < {$minConfidence}
- Do NOT exceed available cash (\${$cashBalance})
- Do NOT exceed max position size per holding (\${$maxPositionDollars})
- Do USE the provided fundamental data - it's real, current data
- Do verify math — position sizes must sum correctly
- Do focus on fundamentals from the provided data
- Do provide specific, verifiable claims based on the data
- Do be transparent if specific metrics are missing

If no high-confidence trades exist, return empty trades array with explanation in market_analysis.
PROMPT;

        return $prompt;
    }

    /**
     * Format fundamental data for prompt inclusion
     */
    private function formatFundamentals(array $fundamentals): string
    {
        if (empty($fundamentals)) {
            return "No fundamental data available. Use qualitative analysis only.";
        }

        $lines = [];

        foreach ($fundamentals as $ticker => $data) {
            $lines[] = "\n{$ticker}:";
            if ($data->isValid()) {
                $lines[] = $data->toPromptString();
                $lines[] = "  Data Age: " . round($data->getAge() / 60) . " minutes";
            } else {
                $lines[] = "  Data unavailable" . ($data->error ? ": {$data->error}" : "");
            }
        }

        if (empty($lines)) {
            return "No fundamental data available. Use qualitative analysis only.";
        }

        return implode("\n", $lines);
    }

    /**
     * Get fundamental data service (for testing/inspection)
     */
    public function getFundamentalService(): ?FundamentalDataService
    {
        return $this->fundamentalService;
    }
}
