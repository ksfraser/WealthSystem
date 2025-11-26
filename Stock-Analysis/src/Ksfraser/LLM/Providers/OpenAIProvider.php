<?php
namespace Ksfraser\LLM\Providers;

use Ksfraser\LLM\Interfaces\LLMProviderInterface;
use OpenAI\Client;

/**
 * OpenAI Provider for LLM operations
 * 
 * Uses the OpenAI PHP library for ChatGPT integration
 */
class OpenAIProvider implements LLMProviderInterface
{
    private $client = null; // Will be OpenAI\Client when library is installed
    private array $config;
    private string $model;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'api_key' => '',
            'model' => 'gpt-4',
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'timeout' => 30
        ], $config);

        $this->model = $this->config['model'];

        if (!empty($this->config['api_key']) && class_exists('\OpenAI')) {
            $this->client = \OpenAI::client($this->config['api_key']);
        }
    }

    public function generateResponse(string $prompt, array $options = []): array
    {
        if (!$this->isAvailable()) {
            throw new \Exception('OpenAI client not available. Check API key configuration.');
        }

        $params = array_merge([
            'model' => $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $this->config['temperature'],
            'max_tokens' => $this->config['max_tokens']
        ], $options);

        try {
            $response = $this->client->chat()->create($params);

            return [
                'success' => true,
                'content' => $response->choices[0]->message->content,
                'usage' => [
                    'prompt_tokens' => $response->usage->promptTokens,
                    'completion_tokens' => $response->usage->completionTokens,
                    'total_tokens' => $response->usage->totalTokens
                ],
                'model' => $response->model,
                'finish_reason' => $response->choices[0]->finishReason
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'content' => null
            ];
        }
    }

    public function analyzeFinancialContent(string $content, array $context = []): array
    {
        $symbol = $context['symbol'] ?? 'UNKNOWN';
        $date = $context['date'] ?? date('Y-m-d');
        $contentType = $context['type'] ?? 'news';

        $prompt = "As an expert financial analyst, analyze the following {$contentType} for {$symbol} (dated {$date}):

{$content}

Please provide a structured analysis including:
1. Overall sentiment (bullish/bearish/neutral) with confidence score (0-1)
2. Key financial metrics or events mentioned
3. Trading implications and potential price impact
4. Risk factors identified
5. Recommendation (buy/sell/hold) with reasoning
6. Time horizon for the analysis (short/medium/long term)

Respond in JSON format with clear categorization.";

        $options = [
            'temperature' => 0.3, // Lower temperature for more analytical responses
            'max_tokens' => 1500
        ];

        $response = $this->generateResponse($prompt, $options);

        if (!$response['success']) {
            return $response;
        }

        // Try to parse JSON response
        $analysis = json_decode($response['content'], true);
        
        if (!$analysis) {
            // Fallback if not valid JSON
            $analysis = [
                'raw_response' => $response['content'],
                'sentiment' => 'neutral',
                'confidence' => 0.5,
                'recommendation' => 'hold'
            ];
        }

        return [
            'success' => true,
            'analysis' => $analysis,
            'symbol' => $symbol,
            'date' => $date,
            'content_type' => $contentType,
            'usage' => $response['usage']
        ];
    }

    public function analyzeStrategy(array $marketData, array $indicators, string $strategy): array
    {
        $latestData = end($marketData);
        $symbol = $latestData['symbol'] ?? 'UNKNOWN';
        
        // Prepare market data summary
        $dataPoints = count($marketData);
        $priceChange = $dataPoints > 1 ? 
            (($latestData['close'] - $marketData[0]['close']) / $marketData[0]['close'] * 100) : 0;

        $prompt = "As an expert quantitative analyst, evaluate the {$strategy} trading strategy for {$symbol}:

Market Data Summary:
- Data points: {$dataPoints}
- Price change: " . number_format($priceChange, 2) . "%
- Current price: {$latestData['close']}
- Volume: {$latestData['volume']}

Technical Indicators:
" . $this->formatIndicators($indicators) . "

Recent Price Action:
" . $this->formatRecentPriceAction($marketData) . "

Please analyze:
1. Strategy effectiveness in current market conditions
2. Risk assessment and probability of success
3. Optimal entry/exit points
4. Position sizing recommendations
5. Stop loss and take profit levels
6. Market regime suitability (trending/ranging/volatile)
7. Expected returns and drawdown scenarios

Provide a detailed analysis with specific actionable recommendations.";

        $options = [
            'temperature' => 0.4,
            'max_tokens' => 2000
        ];

        $response = $this->generateResponse($prompt, $options);

        if (!$response['success']) {
            return $response;
        }

        return [
            'success' => true,
            'strategy_analysis' => $response['content'],
            'strategy' => $strategy,
            'symbol' => $symbol,
            'analysis_date' => date('Y-m-d H:i:s'),
            'usage' => $response['usage']
        ];
    }

    private function formatIndicators(array $indicators): string
    {
        $formatted = [];
        foreach ($indicators as $name => $value) {
            if (is_numeric($value)) {
                $formatted[] = "- {$name}: " . number_format($value, 4);
            } else {
                $formatted[] = "- {$name}: {$value}";
            }
        }
        return implode("\n", $formatted);
    }

    private function formatRecentPriceAction(array $marketData): string
    {
        $recent = array_slice($marketData, -5); // Last 5 periods
        $formatted = [];
        
        foreach ($recent as $data) {
            $date = $data['date'] ?? 'N/A';
            $formatted[] = "- {$date}: O={$data['open']} H={$data['high']} L={$data['low']} C={$data['close']} V={$data['volume']}";
        }
        
        return implode("\n", $formatted);
    }

    public function getProviderName(): string
    {
        return 'OpenAI';
    }

    public function isAvailable(): bool
    {
        return $this->client !== null && !empty($this->config['api_key']);
    }

    /**
     * Generate trading signals based on multiple data sources
     */
    public function generateTradingSignals(array $marketData, array $newsData, array $indicators): array
    {
        $symbol = end($marketData)['symbol'] ?? 'UNKNOWN';
        
        // Combine all data sources for comprehensive analysis
        $prompt = "As a professional trading algorithm, generate trading signals for {$symbol} based on:

TECHNICAL DATA:
" . $this->formatRecentPriceAction($marketData) . "

INDICATORS:
" . $this->formatIndicators($indicators) . "

NEWS SENTIMENT:
" . $this->formatNewsData($newsData) . "

Generate specific trading signals with:
1. Action (BUY/SELL/HOLD)
2. Confidence level (0-1)
3. Entry price range
4. Stop loss level
5. Take profit targets
6. Position size (% of portfolio)
7. Time horizon
8. Risk rating (1-5)
9. Key reasoning points

Format as JSON for automated processing.";

        $options = [
            'temperature' => 0.2, // Very low for consistent signals
            'max_tokens' => 1000
        ];

        $response = $this->generateResponse($prompt, $options);

        if (!$response['success']) {
            return $response;
        }

        // Parse the trading signal
        $signals = json_decode($response['content'], true);
        
        if (!$signals) {
            $signals = [
                'action' => 'HOLD',
                'confidence' => 0.5,
                'reasoning' => 'Unable to parse AI response: ' . $response['content']
            ];
        }

        return [
            'success' => true,
            'signals' => $signals,
            'symbol' => $symbol,
            'generated_at' => date('Y-m-d H:i:s'),
            'usage' => $response['usage']
        ];
    }

    private function formatNewsData(array $newsData): string
    {
        if (empty($newsData)) {
            return "No recent news data available.";
        }

        $formatted = [];
        foreach ($newsData as $news) {
            $sentiment = $news['sentiment'] ?? 'neutral';
            $headline = $news['headline'] ?? 'N/A';
            $date = $news['date'] ?? 'N/A';
            $formatted[] = "- [{$date}] {$headline} (Sentiment: {$sentiment})";
        }
        
        return implode("\n", array_slice($formatted, 0, 5)); // Limit to 5 most recent
    }

    /**
     * Validate and score a trading strategy against historical data
     */
    public function scoreStrategy(string $strategyName, array $backtestResults, array $marketConditions): array
    {
        $totalTrades = $backtestResults['total_trades'] ?? 0;
        $winRate = $backtestResults['win_rate'] ?? 0;
        $totalReturn = $backtestResults['total_return'] ?? 0;
        $maxDrawdown = $backtestResults['max_drawdown'] ?? 0;
        $sharpeRatio = $backtestResults['sharpe_ratio'] ?? 0;

        $prompt = "As a quantitative strategy analyst, score the {$strategyName} strategy based on:

BACKTEST RESULTS:
- Total Trades: {$totalTrades}
- Win Rate: " . number_format($winRate * 100, 1) . "%
- Total Return: " . number_format($totalReturn * 100, 1) . "%
- Maximum Drawdown: " . number_format($maxDrawdown * 100, 1) . "%
- Sharpe Ratio: " . number_format($sharpeRatio, 2) . "

MARKET CONDITIONS:
" . $this->formatMarketConditions($marketConditions) . "

Provide a comprehensive strategy score (0-100) with detailed breakdown:
1. Performance Score (0-30): Returns, win rate, consistency
2. Risk Score (0-25): Drawdown, volatility, tail risk
3. Robustness Score (0-25): Performance across different market conditions
4. Implementation Score (0-20): Execution complexity, transaction costs

Include specific recommendations for improvement and optimal market conditions for deployment.";

        $response = $this->generateResponse($prompt, [
            'temperature' => 0.3,
            'max_tokens' => 1500
        ]);

        if (!$response['success']) {
            return $response;
        }

        return [
            'success' => true,
            'strategy_score' => $response['content'],
            'strategy_name' => $strategyName,
            'evaluated_at' => date('Y-m-d H:i:s'),
            'usage' => $response['usage']
        ];
    }

    private function formatMarketConditions(array $conditions): string
    {
        $formatted = [];
        foreach ($conditions as $condition => $value) {
            if (is_numeric($value)) {
                $formatted[] = "- {$condition}: " . number_format($value, 2);
            } else {
                $formatted[] = "- {$condition}: {$value}";
            }
        }
        return implode("\n", $formatted);
    }
}
