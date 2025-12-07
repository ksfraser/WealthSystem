<?php

declare(strict_types=1);

namespace App\AI;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * LLM Trading Assistant
 * 
 * Provides automated trading recommendations based on portfolio state.
 * Now uses the generic AIClient for provider abstraction.
 * Ported from Python simple_automation.py
 */
class LLMTradingAssistant
{
    private LoggerInterface $logger;

    private const DEFAULT_TEMPERATURE = 0.3;
    private const DEFAULT_MAX_TOKENS = 2500;  // Increased for enhanced responses
    
    // Prompt styles: 'simple' (original) or 'enhanced' (CIBC best practices)
    private const PROMPT_STYLE_SIMPLE = 'simple';
    private const PROMPT_STYLE_ENHANCED = 'enhanced';

    public function __construct(
        private readonly AIClient $aiClient,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Generate trading recommendations based on portfolio state
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
        if (!$this->aiClient->hasAvailableProvider()) {
            $this->logger->error("No AI provider available");
            return new TradingRecommendation(
                analysis: "No AI provider configured",
                trades: [],
                confidence: 0.0,
                error: "No AI provider available"
            );
        }

        // Select prompt style (default to enhanced for better analysis)
        $promptStyle = $config['prompt_style'] ?? self::PROMPT_STYLE_ENHANCED;
        
        $prompt = match($promptStyle) {
            self::PROMPT_STYLE_SIMPLE => $this->generatePromptSimple($holdings, $cashBalance, $totalEquity, $config),
            self::PROMPT_STYLE_ENHANCED => $this->generatePromptEnhanced($holdings, $cashBalance, $totalEquity, $config),
            default => $this->generatePromptEnhanced($holdings, $cashBalance, $totalEquity, $config),
        };

        $this->logger->info("Requesting trading recommendations from AI");

        try {
            // Enhanced system message based on CIBC best practices
            $systemMessage = $promptStyle === self::PROMPT_STYLE_ENHANCED
                ? 'You are a professional portfolio analyst and financial advisor specializing in micro-cap stocks. ' .
                  'Provide detailed, fact-based analysis with verifiable sources. Focus on fundamentals over sentiment. ' .
                  'Always provide bull and bear cases with clear assumptions. State confidence levels transparently. ' .
                  'If data is unavailable, explicitly state so rather than making assumptions. ' .
                  'Respond with structured JSON analysis including fundamentals, scenarios, and risk triggers.'
                : 'You are a professional portfolio analyst and financial advisor specializing in micro-cap stocks. ' .
                  'Analyze portfolios and provide actionable trading recommendations in JSON format. ' .
                  'Be conservative with position sizing and always consider risk management.';
            
            $messages = [
                [
                    'role' => 'system',
                    'content' => $systemMessage
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ];

            $options = [
                'temperature' => $config['temperature'] ?? self::DEFAULT_TEMPERATURE,
                'max_tokens' => $config['max_tokens'] ?? self::DEFAULT_MAX_TOKENS,
            ];

            $response = $this->aiClient->chat($messages, $options);

            if (!$response->isSuccess()) {
                return new TradingRecommendation(
                    analysis: "AI request failed",
                    trades: [],
                    confidence: 0.0,
                    error: $response->error
                );
            }

            $recommendation = $this->parseResponse($response->content);

            $this->logger->info("Received recommendations", [
                'provider' => $response->provider,
                'model' => $response->model,
                'trade_count' => count($recommendation->trades),
                'confidence' => $recommendation->confidence,
                'tokens' => $response->getTotalTokens(),
                'response_time' => $response->responseTime,
            ]);

            return $recommendation;
        } catch (\Exception $e) {
            $this->logger->error("LLM recommendation failed: " . $e->getMessage());
            return new TradingRecommendation(
                analysis: "Error: " . $e->getMessage(),
                trades: [],
                confidence: 0.0,
                error: $e->getMessage()
            );
        }
    }

    /**
     * Generate enhanced trading prompt with CIBC best practices
     */
    private function generatePromptEnhanced(
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
        $marketCapLimit = $config['market_cap_limit'] ?? 300000000; // $300M for micro-cap
        $minCashReserve = $config['min_cash_reserve'] ?? 500;
        $focus = $config['focus'] ?? 'micro-cap';

        $maxPositionDollars = round($totalEquity * ($maxPositionSize / 100), 2);
        
        $prompt = <<<PROMPT
You are a professional portfolio analyst specializing in micro-cap stocks. Today is {$today}.

=== PORTFOLIO STATE ===

Current Holdings:
{$holdingsText}

Cash Available: \${$cashBalance}
Total Portfolio Value: \${$totalEquity}
Max Position Size: {$maxPositionSize}% (\${$maxPositionDollars})
Min Cash Reserve: \${$minCashReserve}

=== INVESTMENT MANDATE ===

Focus: U.S. {$focus} stocks (market cap < \${$marketCapLimit})
Position Limits: {$maxPositionSize}% max per position, full shares only
Risk Tools: Stop-losses required on all positions
Confidence Threshold: Minimum {$minConfidence} to recommend

=== ANALYSIS FRAMEWORK ===

For each potential trade, analyze:

1. **Fundamental Drivers**
   - Revenue growth trajectory (last 4 quarters if available)
   - Profit margins and trend (gross, operating, net)
   - Cash flow quality (operating CF vs net income)
   - Balance sheet health (debt/equity, current ratio)
   - Valuation metrics (P/E, P/S, EV/EBITDA vs peers)

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
- Cite specific data sources when available (e.g., "Q3 2024 10-Q filing showed...")
- State confidence level (0.0 to 1.0) with reasoning
- List 2-3 key assumptions
- Identify 2-3 risk triggers that would invalidate thesis
- Provide specific entry price and stop-loss with rationale
- If data unavailable, explicitly state "Data unavailable" rather than guessing

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
        "revenue_growth_yoy": "25% or 'Data unavailable'",
        "operating_margin": "15% or 'Data unavailable'",
        "pe_ratio": 18.5,
        "debt_to_equity": 0.3,
        "note": "Additional context if needed"
      },
      
      "bull_case": {
        "thesis": "Concise bull thesis (1-2 sentences)",
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
      
      "position_logic": "Why this position size makes sense for portfolio",
      "sources": ["Source 1 if available", "Source 2 if available"],
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
- Do NOT make up data — if unknown, state "Data unavailable"
- Do verify math — position sizes must sum correctly
- Do focus on fundamentals, NOT social media sentiment
- Do provide specific, verifiable claims when possible
- Do be transparent about data limitations

If no high-confidence trades exist, return empty trades array with explanation in market_analysis.
PROMPT;

        return $prompt;
    }
    
    /**
     * Generate simple trading prompt (original version, for backward compatibility)
     */
    private function generatePromptSimple(
        array $holdings,
        float $cashBalance,
        float $totalEquity,
        array $config
    ): string {
        $today = date('Y-m-d');
        $holdingsText = empty($holdings) ? "No current holdings" : $this->formatHoldings($holdings);
        
        $maxPositionSize = $config['max_position_size_pct'] ?? 10;
        $minConfidence = $config['min_confidence'] ?? 0.70;
        $marketCapLimit = $config['market_cap_limit'] ?? 300000000;
        $minCashReserve = $config['min_cash_reserve'] ?? 500;
        $focus = $config['focus'] ?? 'micro-cap';
        $maxPositionDollars = round($totalEquity * ($maxPositionSize / 100), 2);

        $prompt = <<<PROMPT
You are a professional portfolio analyst. Here is your current portfolio state as of {$today}:

[ Holdings ]
{$holdingsText}

[ Snapshot ]
Cash Balance: \${$cashBalance}
Total Equity: \${$totalEquity}

Rules:
- You have \${$cashBalance} in cash available for new positions
- Prefer U.S. {$focus} stocks (<\${$marketCapLimit} market cap)
- Maximum position size: {$maxPositionSize}% of portfolio (\${$maxPositionDollars})
- Maintain minimum cash reserve: \${$minCashReserve}
- Full shares only, no options or derivatives
- Use stop-losses for risk management
- Be conservative with position sizing
- Only recommend trades with confidence >= {$minConfidence}

Analyze the current market conditions and provide specific trading recommendations.

Respond with ONLY a valid JSON object in this exact format:
{
    "analysis": "Brief market analysis and reasoning",
    "trades": [
        {
            "action": "buy",
            "ticker": "SYMBOL",
            "shares": 100,
            "price": 25.50,
            "stop_loss": 20.00,
            "reason": "Brief rationale for this trade"
        }
    ],
    "confidence": 0.8
}

Only recommend trades you are confident about. If no trades are recommended, use an empty trades array.
PROMPT;

        return $prompt;
    }

    /**
     * Format holdings for display
     */
    private function formatHoldings(array $holdings): string
    {
        if (empty($holdings)) {
            return "No holdings";
        }

        $lines = ["Ticker | Shares | Avg Price | Current Price | P&L | P&L%"];
        $lines[] = str_repeat("-", 70);

        foreach ($holdings as $holding) {
            $ticker = $holding['ticker'] ?? 'N/A';
            $shares = $holding['shares'] ?? 0;
            $avgPrice = $holding['average_price'] ?? 0;
            $currentPrice = $holding['current_price'] ?? 0;
            $pnl = $holding['unrealized_pnl'] ?? 0;
            $pnlPct = $holding['unrealized_pnl_pct'] ?? 0;

            $lines[] = sprintf(
                "%s | %d | $%.2f | $%.2f | $%.2f | %.2f%%",
                str_pad($ticker, 6),
                $shares,
                $avgPrice,
                $currentPrice,
                $pnl,
                $pnlPct
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Parse LLM response into TradingRecommendation
     * Supports both simple and enhanced response formats
     */
    private function parseResponse(string $content): TradingRecommendation
    {
        // Strip markdown code blocks if present
        $content = preg_replace('/```json\s*/i', '', $content);
        $content = preg_replace('/```\s*$/i', '', $content);
        $content = trim($content);

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            // Check for required fields (support both formats)
            $hasSimpleFormat = isset($data['analysis']) && isset($data['trades']) && isset($data['confidence']);
            $hasEnhancedFormat = isset($data['market_analysis']) && isset($data['trades']) && isset($data['overall_confidence']);
            
            if (!$hasSimpleFormat && !$hasEnhancedFormat) {
                throw new \RuntimeException("Missing required fields in response");
            }

            // Extract analysis text (support both formats)
            $analysis = $data['market_analysis']['summary'] ?? $data['analysis'] ?? 'No analysis provided';
            $confidence = (float)($data['overall_confidence'] ?? $data['confidence'] ?? 0.0);

            $trades = [];
            foreach ($data['trades'] as $trade) {
                $trades[] = new TradeRecommendation(
                    action: $trade['action'] ?? 'hold',
                    ticker: $trade['ticker'] ?? '',
                    shares: (int)($trade['shares'] ?? 0),
                    price: (float)($trade['entry_price'] ?? $trade['price'] ?? 0),
                    stopLoss: (float)($trade['stop_loss'] ?? 0),
                    reason: $trade['bull_case']['thesis'] ?? $trade['reason'] ?? '',
                    // Enhanced fields (optional)
                    priceTarget: (float)($trade['price_target'] ?? 0),
                    fundamentals: $trade['fundamentals'] ?? null,
                    bullCase: $trade['bull_case'] ?? null,
                    bearCase: $trade['bear_case'] ?? null,
                    scenarios: $trade['scenarios'] ?? null,
                    positionLogic: $trade['position_logic'] ?? null,
                    sources: $trade['sources'] ?? [],
                    tradeConfidence: (float)($trade['confidence'] ?? 0),
                    holdingPeriod: $trade['holding_period'] ?? null
                );
            }

            $marketAnalysisData = $data['market_analysis'] ?? null;
            $portfolioNotesData = $data['portfolio_notes'] ?? null;
            
            return new TradingRecommendation(
                analysis: $analysis,
                trades: $trades,
                confidence: $confidence,
                error: null,
                // Enhanced metadata (optional)
                marketAnalysis: $marketAnalysisData,
                portfolioNotes: $portfolioNotesData
            );
        } catch (\Exception $e) {
            $this->logger->error("Failed to parse LLM response", [
                'error' => $e->getMessage(),
                'content' => substr($content, 0, 500),
            ]);

            return new TradingRecommendation(
                analysis: "Failed to parse response",
                trades: [],
                confidence: 0.0,
                error: "Parse error: " . $e->getMessage()
            );
        }
    }
}

/**
 * Trading recommendation result
 */
class TradingRecommendation
{
    /**
     * @param string $analysis Market analysis text
     * @param array<TradeRecommendation> $trades List of trade recommendations
     * @param float $confidence Confidence score (0.0 to 1.0)
     * @param string|null $error Error message if any
     * @param array|null $marketAnalysis Enhanced market analysis (optional)
     * @param array|null $portfolioNotes Portfolio-specific notes (optional)
     */
    public function __construct(
        public readonly string $analysis,
        public readonly array $trades,
        public readonly float $confidence,
        public readonly ?string $error = null,
        public readonly ?array $marketAnalysis = null,
        public readonly ?array $portfolioNotes = null
    ) {
    }

    /**
     * Check if recommendations are valid
     */
    public function isValid(): bool
    {
        return $this->error === null && $this->confidence > 0.0;
    }

    /**
     * Get trades by action type
     * 
     * @return array<TradeRecommendation>
     */
    public function getTradesByAction(string $action): array
    {
        return array_filter($this->trades, fn($t) => $t->action === $action);
    }

    /**
     * Get buy trades
     * 
     * @return array<TradeRecommendation>
     */
    public function getBuyTrades(): array
    {
        return $this->getTradesByAction('buy');
    }

    /**
     * Get sell trades
     * 
     * @return array<TradeRecommendation>
     */
    public function getSellTrades(): array
    {
        return $this->getTradesByAction('sell');
    }

    /**
     * Convert to array
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'analysis' => $this->analysis,
            'trades' => array_map(fn($t) => $t->toArray(), $this->trades),
            'confidence' => $this->confidence,
            'error' => $this->error,
            'valid' => $this->isValid(),
        ];
        
        // Include enhanced fields if present
        if ($this->marketAnalysis !== null) {
            $result['market_analysis'] = $this->marketAnalysis;
        }
        if ($this->portfolioNotes !== null) {
            $result['portfolio_notes'] = $this->portfolioNotes;
        }
        
        return $result;
    }
}

/**
 * Individual trade recommendation
 */
class TradeRecommendation
{
    /**
     * @param string $action 'buy', 'sell', 'hold'
     * @param string $ticker Stock ticker symbol
     * @param int $shares Number of shares
     * @param float $price Entry/exit price
     * @param float $stopLoss Stop loss price
     * @param string $reason Brief rationale or bull case thesis
     * @param float $priceTarget Target price (optional, enhanced)
     * @param array|null $fundamentals Fundamental metrics (optional, enhanced)
     * @param array|null $bullCase Bull case analysis (optional, enhanced)
     * @param array|null $bearCase Bear case analysis (optional, enhanced)
     * @param array|null $scenarios Scenario analysis (optional, enhanced)
     * @param string|null $positionLogic Position sizing logic (optional, enhanced)
     * @param array $sources Data sources cited (optional, enhanced)
     * @param float $tradeConfidence Individual trade confidence (optional, enhanced)
     * @param string|null $holdingPeriod Expected holding period (optional, enhanced)
     */
    public function __construct(
        public readonly string $action,
        public readonly string $ticker,
        public readonly int $shares,
        public readonly float $price,
        public readonly float $stopLoss,
        public readonly string $reason,
        // Enhanced fields (optional)
        public readonly float $priceTarget = 0,
        public readonly ?array $fundamentals = null,
        public readonly ?array $bullCase = null,
        public readonly ?array $bearCase = null,
        public readonly ?array $scenarios = null,
        public readonly ?string $positionLogic = null,
        public readonly array $sources = [],
        public readonly float $tradeConfidence = 0,
        public readonly ?string $holdingPeriod = null
    ) {
    }

    /**
     * Get total cost/proceeds
     */
    public function getTotalValue(): float
    {
        return $this->shares * $this->price;
    }

    /**
     * Get stop loss percentage
     */
    public function getStopLossPercent(): float
    {
        if ($this->price <= 0) {
            return 0.0;
        }
        return (($this->stopLoss - $this->price) / $this->price) * 100;
    }

    /**
     * Get upside potential percentage
     */
    public function getUpsidePotential(): float
    {
        if ($this->price <= 0 || $this->priceTarget <= 0) {
            return 0.0;
        }
        return (($this->priceTarget - $this->price) / $this->price) * 100;
    }
    
    /**
     * Get risk/reward ratio
     */
    public function getRiskRewardRatio(): float
    {
        $risk = $this->price - $this->stopLoss;
        $reward = $this->priceTarget - $this->price;
        
        if ($risk <= 0) {
            return 0.0;
        }
        
        return $reward / $risk;
    }

    /**
     * Convert to array
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'action' => $this->action,
            'ticker' => $this->ticker,
            'shares' => $this->shares,
            'price' => $this->price,
            'stop_loss' => $this->stopLoss,
            'stop_loss_percent' => $this->getStopLossPercent(),
            'total_value' => $this->getTotalValue(),
            'reason' => $this->reason,
        ];
        
        // Add enhanced fields if present
        if ($this->priceTarget > 0) {
            $result['price_target'] = $this->priceTarget;
            $result['upside_potential'] = $this->getUpsidePotential();
            $result['risk_reward_ratio'] = $this->getRiskRewardRatio();
        }
        
        if ($this->fundamentals !== null) {
            $result['fundamentals'] = $this->fundamentals;
        }
        
        if ($this->bullCase !== null) {
            $result['bull_case'] = $this->bullCase;
        }
        
        if ($this->bearCase !== null) {
            $result['bear_case'] = $this->bearCase;
        }
        
        if ($this->scenarios !== null) {
            $result['scenarios'] = $this->scenarios;
        }
        
        if ($this->positionLogic !== null) {
            $result['position_logic'] = $this->positionLogic;
        }
        
        if (!empty($this->sources)) {
            $result['sources'] = $this->sources;
        }
        
        if ($this->tradeConfidence > 0) {
            $result['trade_confidence'] = $this->tradeConfidence;
        }
        
        if ($this->holdingPeriod !== null) {
            $result['holding_period'] = $this->holdingPeriod;
        }
        
        return $result;
    }
}
