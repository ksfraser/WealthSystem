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
    private const DEFAULT_MAX_TOKENS = 1500;

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

        $prompt = $this->generatePrompt($holdings, $cashBalance, $totalEquity, $config);

        $this->logger->info("Requesting trading recommendations from AI");

        try {
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are a professional portfolio analyst and financial advisor specializing in micro-cap stocks. ' .
                                'Analyze portfolios and provide actionable trading recommendations in JSON format. ' .
                                'Be conservative with position sizing and always consider risk management.'
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
     * Generate trading prompt
     */
    private function generatePrompt(
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
- Maximum position size: {$maxPositionSize}% of portfolio (\$" . round($totalEquity * ($maxPositionSize / 100), 2) . ")
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
     */
    private function parseResponse(string $content): TradingRecommendation
    {
        // Strip markdown code blocks if present
        $content = preg_replace('/```json\s*/i', '', $content);
        $content = preg_replace('/```\s*$/i', '', $content);
        $content = trim($content);

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['analysis']) || !isset($data['trades']) || !isset($data['confidence'])) {
                throw new \RuntimeException("Missing required fields in response");
            }

            $trades = [];
            foreach ($data['trades'] as $trade) {
                $trades[] = new TradeRecommendation(
                    action: $trade['action'] ?? 'hold',
                    ticker: $trade['ticker'] ?? '',
                    shares: (int)($trade['shares'] ?? 0),
                    price: (float)($trade['price'] ?? 0),
                    stopLoss: (float)($trade['stop_loss'] ?? 0),
                    reason: $trade['reason'] ?? ''
                );
            }

            return new TradingRecommendation(
                analysis: $data['analysis'],
                trades: $trades,
                confidence: (float)$data['confidence'],
                error: null
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
     */
    public function __construct(
        public readonly string $analysis,
        public readonly array $trades,
        public readonly float $confidence,
        public readonly ?string $error = null
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
        return [
            'analysis' => $this->analysis,
            'trades' => array_map(fn($t) => $t->toArray(), $this->trades),
            'confidence' => $this->confidence,
            'error' => $this->error,
            'valid' => $this->isValid(),
        ];
    }
}

/**
 * Individual trade recommendation
 */
class TradeRecommendation
{
    public function __construct(
        public readonly string $action,  // 'buy', 'sell', 'hold'
        public readonly string $ticker,
        public readonly int $shares,
        public readonly float $price,
        public readonly float $stopLoss,
        public readonly string $reason
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
     * Convert to array
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'ticker' => $this->ticker,
            'shares' => $this->shares,
            'price' => $this->price,
            'stop_loss' => $this->stopLoss,
            'stop_loss_percent' => $this->getStopLossPercent(),
            'total_value' => $this->getTotalValue(),
            'reason' => $this->reason,
        ];
    }
}
