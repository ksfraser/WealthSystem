<?php

/**
 * Example: Using the AI Client infrastructure
 * 
 * This demonstrates how the new AI provider abstraction works
 * and how it can be used throughout the application.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\AI\AIClient;
use App\AI\Providers\OpenAIProvider;
use App\AI\LLMTradingAssistant;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Setup logger
$logger = new Logger('ai-example');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

// ============================================================================
// Example 1: Simple AI queries with the generic AIClient
// ============================================================================

echo "=== Example 1: Generic AI Client ===\n\n";

// Create OpenAI provider
$openAIProvider = new OpenAIProvider(
    apiKey: getenv('OPENAI_API_KEY') ?: 'your-api-key-here',
    model: 'gpt-4',
    logger: $logger
);

// Create AI client (can support multiple providers with fallback)
$aiClient = new AIClient($openAIProvider, $logger);

// Simple prompt example
$response = $aiClient->prompt(
    prompt: 'Explain the difference between a stock and a bond in one sentence.',
    systemMessage: 'You are a financial educator.'
);

if ($response->isSuccess()) {
    echo "AI Response: {$response->content}\n";
    echo "Model: {$response->model}\n";
    echo "Provider: {$response->provider}\n";
    echo "Tokens: {$response->getTotalTokens()}\n";
    echo "Response Time: " . round($response->responseTime, 3) . "s\n\n";
} else {
    echo "Error: {$response->error}\n\n";
}

// ============================================================================
// Example 2: Multi-turn conversation
// ============================================================================

echo "=== Example 2: Multi-turn Conversation ===\n\n";

$conversation = [
    ['role' => 'system', 'content' => 'You are a financial analyst.'],
    ['role' => 'user', 'content' => 'What are the main risks of investing in micro-cap stocks?'],
];

$response = $aiClient->chat($conversation);

if ($response->isSuccess()) {
    echo "AI: {$response->content}\n\n";
    
    // Continue conversation
    $conversation[] = ['role' => 'assistant', 'content' => $response->content];
    $conversation[] = ['role' => 'user', 'content' => 'How can I mitigate these risks?'];
    
    $response2 = $aiClient->chat($conversation);
    echo "AI: {$response2->content}\n\n";
}

// ============================================================================
// Example 3: Multi-provider fallback
// ============================================================================

echo "=== Example 3: Multi-Provider Fallback ===\n\n";

// In a real scenario, you might have multiple providers:
// - Primary: OpenAI GPT-4
// - Fallback: Anthropic Claude
// - Fallback: Google Gemini

$provider1 = new OpenAIProvider(
    apiKey: getenv('OPENAI_API_KEY') ?: '',
    model: 'gpt-4',
    logger: $logger
);

// You could add more providers here
// $provider2 = new AnthropicProvider(...);
// $provider3 = new GoogleProvider(...);

$multiClient = new AIClient([$provider1], $logger);

if (!$multiClient->hasAvailableProvider()) {
    echo "No AI providers configured!\n";
    echo "Set OPENAI_API_KEY environment variable to use this example.\n\n";
} else {
    $response = $multiClient->prompt('What is diversification in investing?');
    if ($response->isSuccess()) {
        echo "Used provider: {$response->provider}\n";
        echo "Response: {$response->content}\n\n";
    }
}

// ============================================================================
// Example 4: Using with LLM Trading Assistant
// ============================================================================

echo "=== Example 4: LLM Trading Assistant ===\n\n";

// Create trading assistant using the AI client
$tradingAssistant = new LLMTradingAssistant($aiClient, $logger);

// Sample portfolio state
$holdings = [
    [
        'ticker' => 'AAPL',
        'shares' => 50,
        'average_price' => 150.00,
        'current_price' => 175.00,
        'unrealized_pnl' => 1250.00,
        'unrealized_pnl_pct' => 16.67
    ],
    [
        'ticker' => 'MSFT',
        'shares' => 30,
        'average_price' => 300.00,
        'current_price' => 350.00,
        'unrealized_pnl' => 1500.00,
        'unrealized_pnl_pct' => 16.67
    ]
];

$cashBalance = 10000.00;
$totalEquity = 25000.00;

$config = [
    'max_position_size_pct' => 10,
    'min_confidence' => 0.70,
    'focus' => 'micro-cap',
    'temperature' => 0.3,  // Lower temperature for more consistent recommendations
];

// Note: This will make an actual API call if you have a valid API key
// Comment out if you don't want to use API credits

/*
$recommendations = $tradingAssistant->getRecommendations(
    holdings: $holdings,
    cashBalance: $cashBalance,
    totalEquity: $totalEquity,
    config: $config
);

if ($recommendations->isValid()) {
    echo "Market Analysis:\n{$recommendations->analysis}\n\n";
    echo "Confidence: " . ($recommendations->confidence * 100) . "%\n\n";
    
    if (!empty($recommendations->trades)) {
        echo "Recommended Trades:\n";
        foreach ($recommendations->trades as $trade) {
            echo "  {$trade->action} {$trade->shares} shares of {$trade->ticker} @ \${$trade->price}\n";
            echo "    Stop Loss: \${$trade->stopLoss} (" . round($trade->getStopLossPercent(), 2) . "%)\n";
            echo "    Reason: {$trade->reason}\n\n";
        }
    } else {
        echo "No trades recommended at this time.\n";
    }
} else {
    echo "Error: {$recommendations->error}\n";
}
*/

echo "Trading assistant example commented out to avoid API usage.\n";
echo "Uncomment the code block above to test with a real API key.\n\n";

// ============================================================================
// Example 5: Custom options and fine-tuning
// ============================================================================

echo "=== Example 5: Custom AI Options ===\n\n";

$response = $aiClient->chat(
    messages: [
        ['role' => 'user', 'content' => 'List 3 blue-chip stocks']
    ],
    options: [
        'temperature' => 0.5,      // More creative
        'max_tokens' => 100,        // Shorter response
        'top_p' => 0.9,            // Nucleus sampling
        'frequency_penalty' => 0.3, // Reduce repetition
    ]
);

if ($response->isSuccess()) {
    echo "Response with custom options:\n{$response->content}\n\n";
    echo "Total tokens: {$response->getTotalTokens()}\n";
    echo "Response time: " . round($response->responseTime, 3) . "s\n";
}

echo "\n=== Examples Complete ===\n";

// ============================================================================
// Benefits of this architecture:
// ============================================================================

/*
1. **Provider Abstraction**: Switch between OpenAI, Anthropic, Google, etc. without changing application code

2. **Automatic Fallback**: If primary provider fails, automatically try backup providers

3. **Standardized Interface**: All providers return the same AIResponse format

4. **Easy Testing**: Mock providers easily in unit tests

5. **Reusability**: Use AIClient throughout the application for any AI task:
   - Trading recommendations (LLMTradingAssistant)
   - Stock analysis
   - News sentiment analysis
   - Report generation
   - Natural language queries
   - Any other AI-powered feature

6. **Centralized Configuration**: Manage API keys, models, and settings in one place

7. **Future Extensibility**: Easy to add new providers (Anthropic, Google, Cohere, etc.)

8. **Monitoring**: Track usage, costs, and performance across all AI operations
*/
