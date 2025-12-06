# AI Provider Architecture - Visual Guide

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                     Application Layer                           │
│  ┌──────────────────┐  ┌──────────────────┐  ┌───────────────┐│
│  │ Trading          │  │ Stock            │  │ News          ││
│  │ Assistant        │  │ Analyzer         │  │ Sentiment     ││
│  └────────┬─────────┘  └────────┬─────────┘  └───────┬───────┘│
│           │                     │                     │         │
│           └─────────────────────┼─────────────────────┘         │
└───────────────────────────────────┬───────────────────────────────┘
                                    │
                    ┌───────────────▼──────────────┐
                    │        AIClient              │
                    │  - Multi-provider support    │
                    │  - Automatic fallback        │
                    │  - chat(messages, options)   │
                    │  - prompt(text)              │
                    └───────────────┬──────────────┘
                                    │
            ┌───────────────────────┼───────────────────────┐
            │                       │                       │
┌───────────▼──────────┐ ┌─────────▼────────┐ ┌──────────▼──────────┐
│ OpenAIProvider       │ │AnthropicProvider │ │ GoogleProvider      │
│ - GPT-4              │ │- Claude 3        │ │ - Gemini Pro        │
│ - GPT-3.5            │ │- Claude 2        │ │ - Gemini Ultra      │
└───────────┬──────────┘ └─────────┬────────┘ └──────────┬──────────┘
            │                      │                      │
            │          All implement AIProviderInterface  │
            │                      │                      │
            └──────────────────────┼──────────────────────┘
                                   │
                    ┌──────────────▼──────────────┐
                    │   AIProviderInterface       │
                    │  + chat()                   │
                    │  + getProviderName()        │
                    │  + getModel()               │
                    │  + isAvailable()            │
                    └──────────────┬──────────────┘
                                   │
                    ┌──────────────▼──────────────┐
                    │      AIResponse             │
                    │  - content                  │
                    │  - model                    │
                    │  - provider                 │
                    │  - tokens                   │
                    │  - timing                   │
                    │  - error                    │
                    └─────────────────────────────┘
```

## Request Flow

### Successful Request

```
User Request
    │
    ▼
LLMTradingAssistant.getRecommendations()
    │
    ▼
AIClient.chat(messages, options)
    │
    ├─► Check Provider 1 (OpenAI)
    │   ├─► isAvailable()? → Yes
    │   ├─► chat() → Success! ✓
    │   └─► Return AIResponse
    │
    └─► Return to Trading Assistant
        │
        ▼
    Parse & Return TradingRecommendation
```

### Fallback Scenario

```
User Request
    │
    ▼
AIClient.chat(messages, options)
    │
    ├─► Check Provider 1 (OpenAI)
    │   ├─► isAvailable()? → Yes
    │   ├─► chat() → Error! ✗
    │   └─► Try next provider
    │
    ├─► Check Provider 2 (Anthropic)
    │   ├─► isAvailable()? → Yes
    │   ├─► chat() → Error! ✗
    │   └─► Try next provider
    │
    ├─► Check Provider 3 (Google)
    │   ├─► isAvailable()? → Yes
    │   ├─► chat() → Success! ✓
    │   └─► Return AIResponse (provider: 'google')
    │
    └─► Return successful response
```

## Component Relationships

```
┌─────────────────────────────────────────────────────────────┐
│                   Before Refactoring                        │
│                                                             │
│  LLMTradingAssistant                                        │
│  ├── Direct HTTP calls to OpenAI                           │
│  ├── Response parsing                                       │
│  ├── Error handling                                         │
│  └── Trading logic                                          │
│                                                             │
│  Problems:                                                  │
│  - Tightly coupled to OpenAI                                │
│  - Hard to test                                             │
│  - Can't reuse AI logic                                     │
│  - Mixed concerns                                           │
└─────────────────────────────────────────────────────────────┘

                            ▼ Refactored to ▼

┌─────────────────────────────────────────────────────────────┐
│                   After Refactoring                         │
│                                                             │
│  AIClient (Infrastructure)                                  │
│  ├── Provider abstraction                                   │
│  ├── Multi-provider support                                 │
│  ├── Automatic fallback                                     │
│  └── Response standardization                               │
│                                                             │
│  OpenAIProvider (Implementation)                            │
│  ├── HTTP calls to OpenAI                                   │
│  ├── Response mapping                                       │
│  └── Error handling                                         │
│                                                             │
│  LLMTradingAssistant (Business Logic)                       │
│  ├── Portfolio analysis                                     │
│  ├── Recommendation generation                              │
│  └── Uses AIClient (not OpenAI directly)                    │
│                                                             │
│  Benefits:                                                  │
│  ✓ Provider-agnostic                                        │
│  ✓ Easy to test (mock interface)                           │
│  ✓ Reusable across app                                      │
│  ✓ Separation of concerns                                   │
└─────────────────────────────────────────────────────────────┘
```

## Usage Patterns

### Pattern 1: Direct Usage

```php
// Setup
$provider = new OpenAIProvider('sk-...', 'gpt-4');
$client = new AIClient($provider);

// Use
$response = $client->prompt('What is a stock?');
echo $response->content;
```

### Pattern 2: Dependency Injection

```php
class StockAnalyzer {
    public function __construct(
        private AIClient $aiClient
    ) {}
    
    public function analyze(string $ticker): string {
        return $this->aiClient->prompt(
            "Analyze {$ticker} fundamentals"
        )->content;
    }
}

// Usage
$analyzer = new StockAnalyzer($aiClient);
$analysis = $analyzer->analyze('AAPL');
```

### Pattern 3: Multi-Provider Setup

```php
// Setup all available providers
$providers = [];

if ($openaiKey = getenv('OPENAI_API_KEY')) {
    $providers[] = new OpenAIProvider($openaiKey, 'gpt-4');
}

if ($anthropicKey = getenv('ANTHROPIC_API_KEY')) {
    $providers[] = new AnthropicProvider($anthropicKey, 'claude-3');
}

// Client tries providers in order
$client = new AIClient($providers);

// Automatic fallback if primary fails
$response = $client->prompt('Analyze market');
echo "Used: {$response->provider}\n"; // Shows which provider worked
```

## Data Flow

### Request → Response Flow

```
┌──────────────┐
│ Application  │
└──────┬───────┘
       │ "Analyze AAPL"
       ▼
┌──────────────┐
│  AIClient    │
└──────┬───────┘
       │ [messages, options]
       ▼
┌──────────────┐
│ OpenAI       │
│ Provider     │
└──────┬───────┘
       │ HTTP POST
       ▼
┌──────────────┐
│ OpenAI API   │
└──────┬───────┘
       │ JSON Response
       ▼
┌──────────────┐
│ OpenAI       │
│ Provider     │ Map to AIResponse
└──────┬───────┘
       │ AIResponse object
       ▼
┌──────────────┐
│  AIClient    │ Return to caller
└──────┬───────┘
       │ AIResponse
       ▼
┌──────────────┐
│ Application  │ Use response.content
└──────────────┘
```

## Testing Architecture

```
┌─────────────────────────────────────────────┐
│            Production Code                  │
│                                             │
│  AIClient → AIProviderInterface ← OpenAI    │
│                                             │
└─────────────────┬───────────────────────────┘
                  │
                  │ In tests, replace with:
                  │
┌─────────────────▼───────────────────────────┐
│             Test Code                       │
│                                             │
│  AIClient → MockProvider                    │
│             (returns predefined responses)  │
│                                             │
│  Benefits:                                  │
│  - No API calls                             │
│  - Instant execution                        │
│  - Deterministic results                    │
│  - Test edge cases                          │
└─────────────────────────────────────────────┘
```

### Mock Example

```php
// Create mock provider
$mockProvider = $this->createMock(AIProviderInterface::class);
$mockProvider->method('chat')->willReturn(
    new AIResponse('Test response', 'test-model', 'test')
);

// Inject into client
$client = new AIClient($mockProvider);

// Test business logic without real API
$result = $myService->doSomething($client);
```

## Extensibility

### Adding a New Provider

```
Step 1: Create Provider Class
┌────────────────────────────┐
│ class CustomProvider       │
│ implements                 │
│ AIProviderInterface        │
└────────────────────────────┘

Step 2: Implement Required Methods
┌────────────────────────────┐
│ - chat()                   │
│ - getProviderName()        │
│ - getModel()               │
│ - isAvailable()            │
└────────────────────────────┘

Step 3: Map Provider Response to AIResponse
┌────────────────────────────┐
│ return new AIResponse(     │
│   content: ...,            │
│   model: ...,              │
│   provider: 'custom'       │
│ );                         │
└────────────────────────────┘

Step 4: Use It!
┌────────────────────────────┐
│ $client = new AIClient(    │
│   new CustomProvider()     │
│ );                         │
└────────────────────────────┘

No changes needed in:
- AIClient
- Application code
- Tests (except provider-specific ones)
```

## Monitoring & Observability

```
Every AI Request Tracks:

┌─────────────────────┐
│   Request Start     │
│   Start Timer       │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  API Call           │
│  - Provider name    │
│  - Model name       │
│  - Message count    │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Response           │
│  - Content length   │
│  - Token counts     │
│  - Finish reason    │
│  - Stop timer       │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  AIResponse Object  │
│  Contains:          │
│  - All metadata     │
│  - Response time    │
│  - Success/failure  │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Log & Return       │
│  logger->info(...)  │
└─────────────────────┘
```

## Real-World Example: Trading Flow

```
1. User Portfolio State
   ├── Holdings: [AAPL: 50 shares, MSFT: 30 shares]
   ├── Cash: $10,000
   └── Total: $25,000

2. LLMTradingAssistant.getRecommendations()
   ├── Check aiClient.hasAvailableProvider() → Yes
   ├── Format portfolio data into prompt
   └── Call aiClient.chat(messages, options)

3. AIClient.chat()
   ├── Try OpenAIProvider
   │   ├── isAvailable()? → Yes (API key set)
   │   └── chat() → Success!
   └── Return AIResponse

4. OpenAIProvider.chat()
   ├── Build request payload
   ├── POST to api.openai.com
   ├── Parse JSON response
   ├── Map to AIResponse
   └── Return (content, tokens, timing)

5. LLMTradingAssistant.parseResponse()
   ├── Strip markdown
   ├── Parse JSON
   ├── Create TradingRecommendation
   └── Return to user

6. Result
   ├── Analysis: "Market showing strength..."
   ├── Trades: [BUY 100 XYZ @ $50]
   ├── Confidence: 0.85
   └── Metadata: Used GPT-4, 1500 tokens, 2.3s
```

## Summary

This architecture provides:

✅ **Flexibility** - Switch providers easily
✅ **Reliability** - Automatic fallback  
✅ **Testability** - Mock-friendly design
✅ **Reusability** - Use anywhere in app
✅ **Maintainability** - Clear separation
✅ **Observability** - Complete tracking
✅ **Extensibility** - Add providers easily

The AI client is now infrastructure, not implementation detail.
