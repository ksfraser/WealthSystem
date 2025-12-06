# AI Client Infrastructure

## Overview

This document describes the generic AI client infrastructure that abstracts AI provider implementations (OpenAI, Anthropic, Google, etc.) into a reusable, testable, and extensible system.

## Architecture

### Core Components

```
app/AI/
├── AIClient.php                    # Main client with provider abstraction
├── Providers/
│   ├── AIProviderInterface.php     # Provider contract
│   ├── AIResponse.php              # Standardized response format
│   └── OpenAIProvider.php          # OpenAI implementation
└── LLMTradingAssistant.php         # Domain-specific service using AIClient
```

### Design Principles

1. **Separation of Concerns**: AI connectivity is separate from business logic
2. **Provider Abstraction**: Switch providers without changing application code
3. **Dependency Injection**: Services depend on abstractions, not implementations
4. **Single Responsibility**: Each class has one clear purpose
5. **Open/Closed**: Easy to extend with new providers without modifying existing code

## Components

### AIProviderInterface

The contract that all AI providers must implement:

```php
interface AIProviderInterface
{
    public function chat(array $messages, array $options = []): AIResponse;
    public function getProviderName(): string;
    public function getModel(): string;
    public function isAvailable(): bool;
}
```

### AIResponse

Standardized response format across all providers:

```php
class AIResponse
{
    public readonly string $content;          // AI response text
    public readonly string $model;            // Model used
    public readonly string $provider;         // Provider name
    public readonly int $promptTokens;        // Tokens in prompt
    public readonly int $completionTokens;    // Tokens in completion
    public readonly ?string $finishReason;    // Why generation stopped
    public readonly ?string $error;           // Error message if failed
    public readonly float $responseTime;      // Time taken (seconds)
}
```

### OpenAIProvider

OpenAI API implementation:

```php
$provider = new OpenAIProvider(
    apiKey: 'sk-...',
    model: 'gpt-4',  // or 'gpt-3.5-turbo'
    logger: $logger
);
```

Supports all OpenAI chat models and options:
- `temperature` (0.0-2.0)
- `max_tokens`
- `top_p`
- `frequency_penalty`
- `presence_penalty`
- `stop` sequences

### AIClient

Main client with multi-provider support and automatic fallback:

```php
// Single provider
$client = new AIClient($openAIProvider);

// Multiple providers with fallback
$client = new AIClient([
    $openAIProvider,    // Try first
    $anthropicProvider, // Fallback
    $googleProvider     // Last resort
]);
```

## Usage Patterns

### 1. Simple Queries

```php
$response = $aiClient->prompt('What is diversification?');

if ($response->isSuccess()) {
    echo $response->content;
}
```

### 2. With System Message

```php
$response = $aiClient->prompt(
    prompt: 'Explain PE ratio',
    systemMessage: 'You are a financial educator. Be concise.'
);
```

### 3. Multi-turn Conversations

```php
$messages = [
    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
    ['role' => 'user', 'content' => 'What is a stock?'],
];

$response = $aiClient->chat($messages);

// Continue conversation
$messages[] = ['role' => 'assistant', 'content' => $response->content];
$messages[] = ['role' => 'user', 'content' => 'What about bonds?'];

$response2 = $aiClient->chat($messages);
```

### 4. Custom Options

```php
$response = $aiClient->chat($messages, [
    'temperature' => 0.7,
    'max_tokens' => 500,
    'top_p' => 0.9,
]);
```

### 5. Domain-Specific Services

```php
// Trading assistant uses AIClient internally
$tradingAssistant = new LLMTradingAssistant($aiClient);

$recommendations = $tradingAssistant->getRecommendations(
    holdings: $portfolioHoldings,
    cashBalance: 10000.00,
    totalEquity: 50000.00,
    config: ['max_position_size_pct' => 10]
);
```

## Benefits

### 1. Provider Independence

Switch AI providers without changing business logic:

```php
// Development: Use cheaper model
$devClient = new AIClient(
    new OpenAIProvider('key', 'gpt-3.5-turbo')
);

// Production: Use better model
$prodClient = new AIClient(
    new OpenAIProvider('key', 'gpt-4')
);

// Same usage
$assistant = new LLMTradingAssistant($devClient); // or $prodClient
```

### 2. Automatic Fallback

If primary provider fails, automatically try backups:

```php
$client = new AIClient([
    new OpenAIProvider($openaiKey, 'gpt-4'),
    new AnthropicProvider($anthropicKey, 'claude-3-opus'),
    new GoogleProvider($googleKey, 'gemini-pro'),
]);

// Will try all providers until one succeeds
$response = $client->chat($messages);
```

### 3. Easy Testing

Mock providers for unit tests:

```php
$mockProvider = $this->createMock(AIProviderInterface::class);
$mockProvider->method('chat')->willReturn(
    new AIResponse('Test response', 'test-model', 'test-provider')
);

$client = new AIClient($mockProvider);
$assistant = new LLMTradingAssistant($client);

// Test business logic without API calls
$result = $assistant->getRecommendations(...);
```

### 4. Centralized Monitoring

Track all AI usage in one place:

```php
class MonitoredAIClient extends AIClient
{
    public function chat(array $messages, array $options = []): AIResponse
    {
        $response = parent::chat($messages, $options);
        
        // Log metrics
        $this->metrics->record([
            'provider' => $response->provider,
            'model' => $response->model,
            'tokens' => $response->getTotalTokens(),
            'cost' => $this->calculateCost($response),
            'latency' => $response->responseTime,
        ]);
        
        return $response;
    }
}
```

### 5. Future Extensibility

Add new providers without modifying existing code:

```php
// Future: Add Anthropic Claude
class AnthropicProvider implements AIProviderInterface
{
    public function chat(array $messages, array $options = []): AIResponse
    {
        // Anthropic API implementation
    }
    
    // ... implement interface
}

// Drop-in replacement
$client = new AIClient(new AnthropicProvider($key));
```

## Use Cases Throughout Application

The AI client can power many features:

1. **Trading Recommendations** (LLMTradingAssistant)
   - Analyze portfolio and suggest trades
   - Risk assessment
   - Position sizing

2. **Stock Analysis**
   - Fundamental analysis from financial data
   - Technical indicator interpretation
   - Comparative analysis

3. **News Sentiment**
   - Analyze news articles for sentiment
   - Extract key points from earnings calls
   - Summarize research reports

4. **Report Generation**
   - Portfolio performance summaries
   - Market commentary
   - Trade justifications

5. **Natural Language Queries**
   - "What stocks do I own in the tech sector?"
   - "Show me my best performing trades this month"
   - "Explain why AAPL is in my portfolio"

6. **Risk Analysis**
   - Identify portfolio concentration risks
   - Suggest hedging strategies
   - Correlation analysis

7. **Education**
   - Explain financial concepts
   - Provide context on market events
   - Answer user questions

## Testing

### Unit Tests

All components have comprehensive test coverage:

```bash
# Test AI client
vendor/bin/phpunit tests/AI/AIClientTest.php

# Test OpenAI provider
vendor/bin/phpunit tests/AI/Providers/OpenAIProviderTest.php

# Test all AI components
vendor/bin/phpunit tests/AI/
```

### Integration Tests

Test with real API (requires API key):

```php
// Set environment variable
putenv('OPENAI_API_KEY=sk-...');

// Run example
php examples/ai_client_usage.php
```

## Configuration

### Environment Variables

```bash
# OpenAI
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4  # or gpt-3.5-turbo

# Future providers
ANTHROPIC_API_KEY=sk-ant-...
GOOGLE_API_KEY=...
```

### Service Container Setup

```php
// In your DI container
$container->singleton(AIClient::class, function () {
    $providers = [];
    
    // Add OpenAI if configured
    if ($openaiKey = getenv('OPENAI_API_KEY')) {
        $providers[] = new OpenAIProvider(
            $openaiKey,
            getenv('OPENAI_MODEL') ?: 'gpt-4'
        );
    }
    
    // Add other providers...
    
    return new AIClient($providers);
});

// Trading assistant uses AI client
$container->singleton(LLMTradingAssistant::class, function ($container) {
    return new LLMTradingAssistant(
        $container->make(AIClient::class)
    );
});
```

## Migration Guide

### From Old LLMTradingAssistant

**Before** (direct OpenAI coupling):

```php
$assistant = new LLMTradingAssistant(
    apiKey: 'sk-...',
    model: 'gpt-4'
);

$recommendations = $assistant->getRecommendations(...);
```

**After** (provider abstraction):

```php
$provider = new OpenAIProvider('sk-...', 'gpt-4');
$aiClient = new AIClient($provider);
$assistant = new LLMTradingAssistant($aiClient);

$recommendations = $assistant->getRecommendations(...);
```

### Adding New Providers

1. Create provider class implementing `AIProviderInterface`
2. Map provider's response format to `AIResponse`
3. Add to `AIClient` providers array
4. No changes needed in domain services!

Example:

```php
class CustomProvider implements AIProviderInterface
{
    public function chat(array $messages, array $options = []): AIResponse
    {
        // Call your API
        $result = $this->api->complete($messages);
        
        // Map to standard response
        return new AIResponse(
            content: $result['text'],
            model: $result['model'],
            provider: 'custom',
            promptTokens: $result['usage']['input'],
            completionTokens: $result['usage']['output']
        );
    }
    
    // ... implement other methods
}

// Use it
$client = new AIClient(new CustomProvider());
```

## Best Practices

1. **Always check `hasAvailableProvider()`** before making requests
2. **Handle errors gracefully** - check `$response->isSuccess()`
3. **Log token usage** for cost tracking
4. **Use appropriate temperatures**:
   - 0.0-0.3: Factual, consistent (trading, analysis)
   - 0.7-1.0: Creative (summaries, explanations)
5. **Set reasonable `max_tokens`** to control costs
6. **Use system messages** to set consistent behavior
7. **Cache responses** when appropriate
8. **Mock providers in tests** to avoid API costs

## Performance Considerations

- **Typical latency**: 1-5 seconds per request
- **Rate limits**: Respect provider rate limits
- **Token costs**: Monitor via `AIResponse.getTotalTokens()`
- **Caching**: Cache responses for repeated queries
- **Batch requests**: Group similar queries when possible
- **Async**: Consider async execution for multiple independent requests

## Future Enhancements

1. **Additional Providers**
   - Anthropic Claude
   - Google Gemini
   - Cohere
   - Open-source models (Llama, Mistral)

2. **Advanced Features**
   - Response caching with TTL
   - Rate limiting per provider
   - Cost tracking and budgets
   - Streaming responses
   - Function calling support
   - Image input (GPT-4 Vision)

3. **Monitoring Dashboard**
   - Usage statistics
   - Cost analysis
   - Performance metrics
   - Error rates

4. **Load Balancing**
   - Round-robin across providers
   - Cost-based routing
   - Performance-based selection

## Examples

See `examples/ai_client_usage.php` for complete working examples.

## Questions?

This infrastructure makes the codebase more:
- **Modular**: Clear separation of concerns
- **Testable**: Easy to mock and test
- **Flexible**: Switch providers without code changes
- **Maintainable**: Changes to AI logic isolated to provider classes
- **Scalable**: Add features without touching core infrastructure
