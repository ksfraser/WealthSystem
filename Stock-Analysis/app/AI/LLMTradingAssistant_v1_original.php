<?php

declare(strict_types=1);

namespace App\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * LLM Trading Assistant using OpenAI API
 * 
 * Provides automated trading recommendations based on portfolio state.
 * Ported from Python simple_automation.py
 */
class LLMTradingAssistant
{
    private Client $httpClient;
    private LoggerInterface $logger;

    private const OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';
    private const DEFAULT_MODEL = 'gpt-4';
    private const DEFAULT_TEMPERATURE = 0.3;
    private const DEFAULT_MAX_TOKENS = 1500;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = self::DEFAULT_MODEL,
        ?LoggerInterface $logger = null,
        ?Client $httpClient = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => 60.0,
            'verify' => false,
        ]);
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
        $prompt = $this->generatePrompt($holdings, $cashBalance, $totalEquity, $config);

        $this->logger->info("Requesting trading recommendations from LLM");

        try {
            $response = $this->callOpenAI($prompt, $config);
            $recommendation = $this->parseResponse($response);

            $this->logger->info("Received recommendations", [
                'trade_count' => count($recommendation->trades),
                'confidence' => $recommendation->confidence,
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
            return "No current holdings";
        }

        $lines = ["Symbol | Shares | Avg Cost | Current Price | Gain/Loss | Position Value"];
        $lines[] = str_repeat('-', 80);

        foreach ($holdings as $holding) {
            $symbol = $holding['symbol'] ?? '?';
            $shares = $holding['shares'] ?? 0;
            $avgCost = $holding['avg_cost'] ?? 0;
            $currentPrice = $holding['current_price'] ?? $avgCost;
            $gainLoss = ($currentPrice - $avgCost) * $shares;
            $positionValue = $currentPrice * $shares;

            $lines[] = sprintf(
                "%-6s | %6d | %8.2f | %13.2f | %9.2f | %14.2f",
                $symbol,
                $shares,
                $avgCost,
                $currentPrice,
                $gainLoss,
                $positionValue
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Call OpenAI API
     */
    private function callOpenAI(string $prompt, array $config): string
    {
        $temperature = $config['temperature'] ?? self::DEFAULT_TEMPERATURE;
        $maxTokens = $config['max_tokens'] ?? self::DEFAULT_MAX_TOKENS;

        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional portfolio analyst. Always respond with valid JSON in the exact format requested. Never include markdown code blocks or any text outside the JSON object.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];

        try {
            $response = $this->httpClient->post(self::OPENAI_API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode((string)$response->getBody(), true);

            if (!isset($body['choices'][0]['message']['content'])) {
                throw new \RuntimeException("Invalid OpenAI response structure");
            }

            return $body['choices'][0]['message']['content'];
        } catch (GuzzleException $e) {
            throw new \RuntimeException("OpenAI API request failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Parse LLM response into TradingRecommendation
     */
    private function parseResponse(string $response): TradingRecommendation
    {
        // Remove markdown code blocks if present
        $response = preg_replace('/```json\s*|\s*```/', '', $response);
        $response = trim($response);

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Failed to parse LLM response as JSON: " . json_last_error_msg());
        }

        if (!isset($data['analysis'], $data['trades'], $data['confidence'])) {
            throw new \RuntimeException("LLM response missing required fields (analysis, trades, confidence)");
        }

        // Parse trades
        $trades = [];
        foreach ($data['trades'] as $tradeData) {
            $trades[] = new TradeRecommendation(
                action: $tradeData['action'] ?? 'hold',
                ticker: $tradeData['ticker'] ?? '',
                shares: (int)($tradeData['shares'] ?? 0),
                price: (float)($tradeData['price'] ?? 0.0),
                stopLoss: isset($tradeData['stop_loss']) ? (float)$tradeData['stop_loss'] : null,
                reason: $tradeData['reason'] ?? ''
            );
        }

        return new TradingRecommendation(
            analysis: $data['analysis'],
            trades: $trades,
            confidence: (float)$data['confidence'],
            rawResponse: $response
        );
    }

    /**
     * Get model information
     */
    public function getModel(): string
    {
        return $this->model;
    }
}

/**
 * Trading recommendation from LLM
 */
class TradingRecommendation
{
    /**
     * @param string $analysis Market analysis
     * @param array<TradeRecommendation> $trades Recommended trades
     * @param float $confidence Overall confidence (0.0-1.0)
     * @param string|null $rawResponse Raw LLM response
     * @param string|null $error Error message if failed
     */
    public function __construct(
        public readonly string $analysis,
        public readonly array $trades,
        public readonly float $confidence,
        public readonly ?string $rawResponse = null,
        public readonly ?string $error = null
    ) {
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    public function hasTrades(): bool
    {
        return !empty($this->trades);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'analysis' => $this->analysis,
            'trades' => array_map(fn($t) => $t->toArray(), $this->trades),
            'confidence' => $this->confidence,
            'has_error' => $this->hasError(),
            'error' => $this->error,
        ];
    }
}

/**
 * Individual trade recommendation
 */
class TradeRecommendation
{
    public function __construct(
        public readonly string $action,
        public readonly string $ticker,
        public readonly int $shares,
        public readonly float $price,
        public readonly ?float $stopLoss = null,
        public readonly string $reason = ''
    ) {
    }

    public function isBuy(): bool
    {
        return strtolower($this->action) === 'buy';
    }

    public function isSell(): bool
    {
        return strtolower($this->action) === 'sell';
    }

    public function getPositionValue(): float
    {
        return $this->shares * $this->price;
    }

    /**
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
            'reason' => $this->reason,
            'position_value' => $this->getPositionValue(),
        ];
    }
}
