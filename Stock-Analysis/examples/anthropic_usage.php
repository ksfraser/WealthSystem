<?php

/**
 * Anthropic Provider Usage Examples
 * 
 * Demonstrates how to use the AnthropicProvider with AIClient,
 * including multi-provider fallback patterns for resilience.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\AI\AIClient;
use App\AI\Providers\OpenAIProvider;
use App\AI\Providers\AnthropicProvider;
use App\AI\LLMTradingAssistant;

echo "=== Anthropic Provider Examples ===\n\n";

// ============================================================================
// Example 1: Basic Anthropic Usage
// ============================================================================

echo "Example 1: Basic Anthropic Chat\n";
echo str_repeat("-", 80) . "\n";

$anthropicKey = getenv('ANTHROPIC_API_KEY') ?: 'your-anthropic-key-here';
$anthropicProvider = new AnthropicProvider($anthropicKey, 'claude-3-sonnet-20240229');
$client = new AIClient($anthropicProvider);

$response = $client->prompt('Explain the concept of compound interest in 2 sentences.');

if ($response->error === null) {
    echo "✅ Response: {$response->content}\n";
    echo "📊 Tokens: {$response->getTotalTokens()} (input: {$response->promptTokens}, output: {$response->completionTokens})\n";
    echo "⏱️  Time: {$response->responseTime}s\n";
    echo "🤖 Model: {$response->model}\n";
} else {
    echo "❌ Error: {$response->error}\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// Example 2: Multi-Provider Fallback (OpenAI → Anthropic)
// ============================================================================

echo "Example 2: Multi-Provider Fallback (OpenAI primary, Anthropic backup)\n";
echo str_repeat("-", 80) . "\n";

$openaiKey = getenv('OPENAI_API_KEY') ?: 'your-openai-key-here';
$anthropicKey = getenv('ANTHROPIC_API_KEY') ?: 'your-anthropic-key-here';

// Create client with multiple providers
// Will try OpenAI first, fall back to Anthropic if it fails
$multiProviderClient = new AIClient([
    new OpenAIProvider($openaiKey, 'gpt-4'),
    new AnthropicProvider($anthropicKey, 'claude-3-sonnet-20240229'),
]);

$response = $multiProviderClient->prompt('What is the capital of France?');

if ($response->error === null) {
    echo "✅ Success!\n";
    echo "   Provider used: {$response->provider}\n";
    echo "   Model: {$response->model}\n";
    echo "   Response: {$response->content}\n";
} else {
    echo "❌ All providers failed: {$response->error}\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// Example 3: Multi-Turn Conversation with Claude
// ============================================================================

echo "Example 3: Multi-Turn Conversation\n";
echo str_repeat("-", 80) . "\n";

$provider = new AnthropicProvider($anthropicKey, 'claude-3-sonnet-20240229');
$client = new AIClient($provider);

$messages = [
    [
        'role' => 'system',
        'content' => 'You are a financial advisor specializing in micro-cap stocks. Be concise and fact-based.'
    ],
    [
        'role' => 'user',
        'content' => 'What are the key risks of investing in micro-cap stocks?'
    ],
];

$response1 = $client->chat($messages);

if ($response1->error === null) {
    echo "🤖 Claude: {$response1->content}\n\n";
    
    // Continue conversation
    $messages[] = ['role' => 'assistant', 'content' => $response1->content];
    $messages[] = ['role' => 'user', 'content' => 'How can I mitigate those risks?'];
    
    $response2 = $client->chat($messages);
    
    if ($response2->error === null) {
        echo "🤖 Claude: {$response2->content}\n";
        echo "\n📊 Total tokens used: " . ($response1->getTotalTokens() + $response2->getTotalTokens()) . "\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// Example 4: Trading Assistant with Anthropic
// ============================================================================

echo "Example 4: Trading Assistant with Claude\n";
echo str_repeat("-", 80) . "\n";

$provider = new AnthropicProvider($anthropicKey, 'claude-3-opus-20240229'); // Use Opus for complex analysis
$client = new AIClient($provider);
$assistant = new LLMTradingAssistant($client);

$holdings = [
    [
        'ticker' => 'ABCD',
        'shares' => 200,
        'average_price' => 15.00,
        'current_price' => 18.50,
    ]
];

$recommendation = $assistant->getRecommendations($holdings, 10000, 13700, [
    'prompt_style' => 'enhanced', // Use enhanced prompts with Claude
    'max_tokens' => 4096, // Claude can handle longer responses
]);

if ($recommendation->isValid()) {
    echo "✅ Claude-powered recommendation received\n";
    echo "   Confidence: " . ($recommendation->confidence * 100) . "%\n";
    echo "   Trades suggested: " . count($recommendation->trades) . "\n";
    
    if ($recommendation->marketAnalysis !== null) {
        echo "\n📊 Market Analysis:\n";
        echo "   {$recommendation->marketAnalysis['summary']}\n";
    }
    
    foreach ($recommendation->trades as $trade) {
        echo "\n💼 {$trade->action} {$trade->shares} shares of {$trade->ticker} @ \${$trade->price}\n";
        
        if ($trade->bullCase !== null) {
            echo "   🐂 Bull: {$trade->bullCase['thesis']}\n";
        }
        if ($trade->bearCase !== null) {
            echo "   🐻 Bear: {$trade->bearCase['thesis']}\n";
        }
    }
} else {
    echo "❌ No recommendations: {$recommendation->error}\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// Example 5: Cost Comparison (OpenAI vs Anthropic)
// ============================================================================

echo "Example 5: Cost Comparison\n";
echo str_repeat("-", 80) . "\n";

$testPrompt = 'Analyze the following stock: AAPL. Provide fundamental analysis including revenue growth, margins, and valuation metrics.';

// Test with OpenAI GPT-4
$openaiProvider = new OpenAIProvider($openaiKey, 'gpt-4');
$openaiClient = new AIClient($openaiProvider);
$openaiResponse = $openaiClient->prompt($testPrompt);

// Test with Anthropic Claude 3 Sonnet
$anthropicProvider = new AnthropicProvider($anthropicKey, 'claude-3-sonnet-20240229');
$anthropicClient = new AIClient($anthropicProvider);
$anthropicResponse = $anthropicClient->prompt($testPrompt);

echo "OpenAI GPT-4:\n";
if ($openaiResponse->error === null) {
    $openaiCost = ($openaiResponse->promptTokens * 0.00003) + ($openaiResponse->completionTokens * 0.00006);
    echo "   Tokens: {$openaiResponse->getTotalTokens()}\n";
    echo "   Time: {$openaiResponse->responseTime}s\n";
    echo "   Estimated cost: $" . number_format($openaiCost, 6) . "\n";
} else {
    echo "   Error: {$openaiResponse->error}\n";
}

echo "\nAnthropic Claude 3 Sonnet:\n";
if ($anthropicResponse->error === null) {
    $anthropicCost = ($anthropicResponse->promptTokens * 0.000003) + ($anthropicResponse->completionTokens * 0.000015);
    echo "   Tokens: {$anthropicResponse->getTotalTokens()}\n";
    echo "   Time: {$anthropicResponse->responseTime}s\n";
    echo "   Estimated cost: $" . number_format($anthropicCost, 6) . "\n";
} else {
    echo "   Error: {$anthropicResponse->error}\n";
}

if ($openaiResponse->error === null && $anthropicResponse->error === null) {
    $openaiCost = ($openaiResponse->promptTokens * 0.00003) + ($openaiResponse->completionTokens * 0.00006);
    $anthropicCost = ($anthropicResponse->promptTokens * 0.000003) + ($anthropicResponse->completionTokens * 0.000015);
    
    $savings = (($openaiCost - $anthropicCost) / $openaiCost) * 100;
    echo "\n💰 Cost comparison:\n";
    echo "   Claude 3 Sonnet is " . number_format($savings, 1) . "% cheaper than GPT-4\n";
    echo "   Savings per 1000 requests: $" . number_format(($openaiCost - $anthropicCost) * 1000, 2) . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// Example 6: Model Selection Based on Task Complexity
// ============================================================================

echo "Example 6: Smart Model Selection\n";
echo str_repeat("-", 80) . "\n";

function getRecommendationWithCostOptimization(string $task, string $complexity): AIClient
{
    $anthropicKey = getenv('ANTHROPIC_API_KEY') ?: 'your-key-here';
    
    // Choose model based on complexity
    $model = match($complexity) {
        'simple' => 'claude-3-haiku-20240307',    // Fast & cheap
        'moderate' => 'claude-3-sonnet-20240229',  // Balanced
        'complex' => 'claude-3-opus-20240229',     // Most capable
        default => 'claude-3-sonnet-20240229'
    };
    
    echo "   Selected model: {$model} for {$complexity} task\n";
    
    $provider = new AnthropicProvider($anthropicKey, $model);
    return new AIClient($provider);
}

// Simple task - use Haiku (cheapest)
$simpleClient = getRecommendationWithCostOptimization('Quick summary', 'simple');
$simpleResponse = $simpleClient->prompt('What is a P/E ratio?');

if ($simpleResponse->error === null) {
    echo "   ✅ Haiku response: " . substr($simpleResponse->content, 0, 100) . "...\n";
    echo "   💵 Tokens: {$simpleResponse->getTotalTokens()}\n";
}

// Complex task - use Opus (most capable)
echo "\n";
$complexClient = getRecommendationWithCostOptimization('Deep fundamental analysis', 'complex');
$complexResponse = $complexClient->prompt('Provide comprehensive fundamental analysis of NVDA including competitive positioning, growth drivers, and risks.');

if ($complexResponse->error === null) {
    echo "   ✅ Opus response: " . substr($complexResponse->content, 0, 100) . "...\n";
    echo "   💵 Tokens: {$complexResponse->getTotalTokens()}\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// Example 7: Supported Models Info
// ============================================================================

echo "Example 7: Anthropic Claude Models\n";
echo str_repeat("-", 80) . "\n";

$models = AnthropicProvider::getSupportedModels();

foreach ($models as $modelId => $info) {
    echo "\n{$info['name']} ({$modelId}):\n";
    echo "  📝 {$info['description']}\n";
    echo "  📏 Context: " . number_format($info['context']) . " tokens\n";
    echo "  📤 Max output: " . number_format($info['output']) . " tokens\n";
    echo "  💵 Input: \${$info['cost_per_1m_input']}/1M tokens\n";
    echo "  💵 Output: \${$info['cost_per_1m_output']}/1M tokens\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// Best Practices Summary
// ============================================================================

echo "💡 Best Practices:\n";
echo str_repeat("-", 80) . "\n";
echo "1. Use multi-provider fallback for resilience:\n";
echo "   \$client = new AIClient([new OpenAIProvider(...), new AnthropicProvider(...)]);\n\n";
echo "2. Choose model based on task complexity:\n";
echo "   - Haiku: Simple tasks, fast responses, lowest cost\n";
echo "   - Sonnet: Balanced performance for most tasks\n";
echo "   - Opus: Complex analysis, deep reasoning\n\n";
echo "3. Set max_tokens appropriately:\n";
echo "   - Anthropic requires explicit max_tokens\n";
echo "   - Use higher values (4096+) for detailed analysis\n\n";
echo "4. Separate system messages:\n";
echo "   - Anthropic handles system messages differently\n";
echo "   - AIClient abstracts this for you\n\n";
echo "5. Monitor costs:\n";
echo "   - Claude 3 Sonnet is ~80% cheaper than GPT-4\n";
echo "   - Claude 3 Haiku is ~90% cheaper than GPT-4\n\n";
echo "6. Test fallback behavior:\n";
echo "   - Ensure your app works even if one provider is down\n";
echo "   - Log which provider is used for monitoring\n\n";

echo str_repeat("=", 80) . "\n";
