<?php
/**
 * LLM Analysis Service
 * 
 * Integrates with OpenAI GPT-4 and Anthropic Claude for news analysis,
 * sentiment scoring, and investment recommendations
 */

require_once __DIR__ . '/StockDAO.php';
require_once __DIR__ . '/models/StockModels.php';

class LLMAnalysisService {
    private $stockDAO;
    private $config;
    private $openaiApiKey;
    private $claudeApiKey;
    
    public function __construct(StockDAO $stockDAO, array $config = []) {
        $this->stockDAO = $stockDAO;
        $this->config = array_merge([
            'openai_model' => 'gpt-4-turbo-preview',
            'claude_model' => 'claude-3-sonnet-20240229',
            'default_provider' => 'openai',
            'max_tokens' => 2000,
            'temperature' => 0.3,
            'timeout' => 30,
            'cache_timeout' => 3600, // 1 hour
            'analysis_interval' => 86400, // 24 hours
            'sentiment_threshold' => 0.1,
            'debug_mode' => false
        ], $config);
        
        $this->openaiApiKey = $config['openai_api_key'] ?? getenv('OPENAI_API_KEY');
        $this->claudeApiKey = $config['claude_api_key'] ?? getenv('CLAUDE_API_KEY');
    }
    
    /**
     * Analyze news sentiment for a stock
     */
    public function analyzeNewsSentiment(string $symbol, array $newsItems): array {
        if (empty($newsItems)) {
            return ['sentiment_score' => 0, 'sentiment_label' => 'NEUTRAL', 'confidence' => 0];
        }
        
        try {
            $prompt = $this->buildSentimentPrompt($symbol, $newsItems);
            $response = $this->callLLM($prompt, 'sentiment_analysis');
            
            if ($response && isset($response['sentiment_score'])) {
                // Update news items with sentiment data
                foreach ($newsItems as $index => $news) {
                    if (isset($response['individual_sentiments'][$index])) {
                        $sentimentData = $response['individual_sentiments'][$index];
                        
                        // Update news item in database
                        $newsUpdate = [
                            'sentiment_score' => $sentimentData['score'],
                            'sentiment_label' => $sentimentData['label'],
                            'confidence_score' => $sentimentData['confidence']
                        ];
                        
                        $this->stockDAO->updateNewsItem($symbol, $news['id'], $newsUpdate);
                    }
                }
                
                return [
                    'sentiment_score' => $response['sentiment_score'],
                    'sentiment_label' => $response['sentiment_label'],
                    'confidence' => $response['confidence'],
                    'analysis_summary' => $response['summary'] ?? '',
                    'key_themes' => $response['key_themes'] ?? [],
                    'model_used' => $response['model_used'] ?? $this->config['default_provider']
                ];
            }
            
            return ['sentiment_score' => 0, 'sentiment_label' => 'NEUTRAL', 'confidence' => 0];
            
        } catch (Exception $e) {
            error_log("Sentiment analysis failed for {$symbol}: " . $e->getMessage());
            return ['sentiment_score' => 0, 'sentiment_label' => 'NEUTRAL', 'confidence' => 0];
        }
    }
    
    /**
     * Generate comprehensive stock analysis
     */
    public function generateStockAnalysis(string $symbol): ?array {
        try {
            // Get stock data
            $stockSummary = $this->stockDAO->getStockSummary($symbol);
            if (!$stockSummary) {
                throw new Exception("No data available for {$symbol}");
            }
            
            // Check if recent analysis exists
            $lastAnalysis = $this->stockDAO->getLatestAnalysis($symbol);
            if ($lastAnalysis && 
                strtotime($lastAnalysis['created_at']) > (time() - $this->config['analysis_interval'])) {
                return $lastAnalysis; // Return cached analysis
            }
            
            // Build comprehensive analysis prompt
            $prompt = $this->buildAnalysisPrompt($symbol, $stockSummary);
            $response = $this->callLLM($prompt, 'stock_analysis');
            
            if ($response) {
                $analysisData = [
                    'analysis_date' => date('Y-m-d'),
                    'fundamental_score' => $response['fundamental_score'] ?? null,
                    'technical_score' => $response['technical_score'] ?? null,
                    'momentum_score' => $response['momentum_score'] ?? null,
                    'sentiment_score' => $response['sentiment_score'] ?? null,
                    'news_score' => $response['news_score'] ?? null,
                    'overall_score' => $response['overall_score'] ?? null,
                    'confidence_level' => $response['confidence_level'] ?? null,
                    'recommendation' => $response['recommendation'] ?? null,
                    'target_price' => $response['target_price'] ?? null,
                    'stop_loss' => $response['stop_loss'] ?? null,
                    'take_profit' => $response['take_profit'] ?? null,
                    'risk_level' => $response['risk_level'] ?? null,
                    'volatility_assessment' => $response['volatility_assessment'] ?? null,
                    'risk_factors' => json_encode($response['risk_factors'] ?? []),
                    'llm_analysis' => $response['analysis_text'] ?? null,
                    'llm_reasoning' => $response['reasoning'] ?? null,
                    'llm_model' => $response['model_used'] ?? $this->config['default_provider'],
                    'llm_tokens_used' => $response['tokens_used'] ?? null,
                    'data_freshness_score' => $this->calculateDataFreshness($stockSummary)
                ];
                
                // Save analysis to database
                if ($this->stockDAO->saveAnalysis($symbol, $analysisData)) {
                    return $analysisData;
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Stock analysis failed for {$symbol}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Analyze news and update recommendations
     */
    public function analyzeNewsImpact(string $symbol): array {
        try {
            // Get recent news
            $recentNews = $this->stockDAO->getNews($symbol, 20);
            
            if (empty($recentNews)) {
                return ['impact_score' => 0, 'key_events' => [], 'recommendations' => []];
            }
            
            // Analyze news impact
            $prompt = $this->buildNewsImpactPrompt($symbol, $recentNews);
            $response = $this->callLLM($prompt, 'news_impact');
            
            if ($response) {
                return [
                    'impact_score' => $response['impact_score'] ?? 0,
                    'key_events' => $response['key_events'] ?? [],
                    'recommendations' => $response['recommendations'] ?? [],
                    'time_horizon' => $response['time_horizon'] ?? 'medium',
                    'confidence' => $response['confidence'] ?? 0,
                    'analysis_summary' => $response['summary'] ?? ''
                ];
            }
            
            return ['impact_score' => 0, 'key_events' => [], 'recommendations' => []];
            
        } catch (Exception $e) {
            error_log("News impact analysis failed for {$symbol}: " . $e->getMessage());
            return ['impact_score' => 0, 'key_events' => [], 'recommendations' => []];
        }
    }
    
    /**
     * Generate investment thesis
     */
    public function generateInvestmentThesis(string $symbol): ?array {
        try {
            $stockSummary = $this->stockDAO->getStockSummary($symbol);
            if (!$stockSummary) {
                return null;
            }
            
            $prompt = $this->buildInvestmentThesisPrompt($symbol, $stockSummary);
            $response = $this->callLLM($prompt, 'investment_thesis');
            
            if ($response) {
                return [
                    'bull_case' => $response['bull_case'] ?? '',
                    'bear_case' => $response['bear_case'] ?? '',
                    'key_catalysts' => $response['key_catalysts'] ?? [],
                    'key_risks' => $response['key_risks'] ?? [],
                    'investment_timeline' => $response['investment_timeline'] ?? '',
                    'price_targets' => $response['price_targets'] ?? [],
                    'comparable_companies' => $response['comparable_companies'] ?? [],
                    'thesis_summary' => $response['thesis_summary'] ?? ''
                ];
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Investment thesis generation failed for {$symbol}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Build sentiment analysis prompt
     */
    private function buildSentimentPrompt(string $symbol, array $newsItems): string {
        $newsText = '';
        foreach ($newsItems as $index => $news) {
            $newsText .= "News {$index}: {$news['headline']}\n";
            if ($news['summary']) {
                $newsText .= "Summary: {$news['summary']}\n";
            }
            $newsText .= "Source: {$news['source']} | Published: {$news['published_at']}\n\n";
        }
        
        return "Analyze the sentiment of the following news articles about {$symbol} stock. 

NEWS ARTICLES:
{$newsText}

Please provide a JSON response with:
1. Overall sentiment_score (-1.0 to 1.0, where -1 is very negative, 0 is neutral, 1 is very positive)
2. sentiment_label (POSITIVE, NEGATIVE, or NEUTRAL)
3. confidence (0.0 to 1.0)
4. summary (brief explanation of the overall sentiment)
5. key_themes (array of main themes/topics mentioned)
6. individual_sentiments (array with score, label, confidence for each news item)

Focus on:
- Market impact and investor sentiment
- Business fundamentals mentioned
- Financial performance indicators
- Strategic developments
- Regulatory or competitive factors

Respond only with valid JSON.";
    }
    
    /**
     * Build comprehensive analysis prompt
     */
    private function buildAnalysisPrompt(string $symbol, array $stockSummary): string {
        $latestPrice = $stockSummary['latest_price'];
        $fundamentals = $stockSummary['fundamentals'];
        $analysis = $stockSummary['latest_analysis'];
        $priceHistory = $stockSummary['price_history'];
        
        $priceData = '';
        if (!empty($priceHistory)) {
            $priceData = "Recent price data (last 30 days):\n";
            foreach (array_slice($priceHistory, 0, 10) as $price) {
                $priceData .= "Date: {$price['date']}, Close: \${$price['close']}, Volume: {$price['volume']}\n";
            }
        }
        
        $fundamentalData = '';
        if ($fundamentals) {
            $fundamentalData = "Fundamental metrics:\n";
            $fundamentalData .= "P/E Ratio: " . ($fundamentals['pe_ratio'] ?? 'N/A') . "\n";
            $fundamentalData .= "Market Cap: " . ($fundamentals['market_cap'] ?? 'N/A') . "\n";
            $fundamentalData .= "Revenue Growth: " . ($fundamentals['revenue_growth_yoy'] ?? 'N/A') . "%\n";
            $fundamentalData .= "ROE: " . ($fundamentals['return_on_equity'] ?? 'N/A') . "%\n";
            $fundamentalData .= "Debt/Equity: " . ($fundamentals['debt_to_equity'] ?? 'N/A') . "\n";
        }
        
        return "Conduct a comprehensive investment analysis for {$symbol} stock.

CURRENT PRICE DATA:
Current Price: \${$latestPrice['close']}
Date: {$latestPrice['date']}

PRICE HISTORY:
{$priceData}

FUNDAMENTAL DATA:
{$fundamentalData}

Please provide a JSON response with detailed analysis including:
1. fundamental_score (0-100): Based on financial health, valuation, growth prospects
2. technical_score (0-100): Based on price trends, momentum, technical indicators
3. momentum_score (0-100): Based on recent price action and volume
4. sentiment_score (0-100): Based on news sentiment and market sentiment
5. news_score (0-100): Based on recent news impact
6. overall_score (0-100): Weighted combination of all factors
7. confidence_level (0-100): Confidence in the analysis
8. recommendation (STRONG_BUY, BUY, HOLD, SELL, STRONG_SELL)
9. target_price: 12-month price target
10. stop_loss: Suggested stop loss level
11. take_profit: Suggested take profit level
12. risk_level (LOW, MEDIUM, HIGH, VERY_HIGH)
13. volatility_assessment: Assessment of expected volatility
14. risk_factors: Array of key risks
15. analysis_text: Detailed analysis explanation
16. reasoning: Step-by-step reasoning for recommendation

Consider:
- Financial health and fundamentals
- Technical analysis and chart patterns
- Market conditions and sector trends
- News sentiment and recent developments
- Risk-reward profile
- Time horizon for investment

Respond only with valid JSON.";
    }
    
    /**
     * Build news impact analysis prompt
     */
    private function buildNewsImpactPrompt(string $symbol, array $newsItems): string {
        $newsText = '';
        foreach ($newsItems as $news) {
            $newsText .= "Headline: {$news['headline']}\n";
            $newsText .= "Date: {$news['published_at']}\n";
            $newsText .= "Category: {$news['category']}\n";
            if ($news['summary']) {
                $newsText .= "Summary: {$news['summary']}\n";
            }
            $newsText .= "---\n";
        }
        
        return "Analyze the potential market impact of recent news for {$symbol} stock.

RECENT NEWS:
{$newsText}

Provide JSON response with:
1. impact_score (-10 to 10): Expected impact on stock price
2. key_events: Array of most significant news events
3. recommendations: Array of actionable insights
4. time_horizon: Expected timeframe for impact (short/medium/long)
5. confidence: Confidence level (0.0-1.0)
6. summary: Brief analysis of overall news impact

Focus on:
- Earnings and financial results
- Product launches or developments
- Regulatory changes
- Management changes
- Market conditions
- Competitive developments

Respond only with valid JSON.";
    }
    
    /**
     * Build investment thesis prompt
     */
    private function buildInvestmentThesisPrompt(string $symbol, array $stockSummary): string {
        return "Generate a comprehensive investment thesis for {$symbol}.

Provide JSON response with:
1. bull_case: Strong positive arguments for investment
2. bear_case: Key risks and negative factors
3. key_catalysts: Potential positive drivers
4. key_risks: Major risks to watch
5. investment_timeline: Suggested holding period
6. price_targets: Conservative, base, and optimistic targets
7. comparable_companies: Similar companies for comparison
8. thesis_summary: Executive summary of investment case

Be objective and balanced in the analysis.
Respond only with valid JSON.";
    }
    
    /**
     * Call LLM API (OpenAI or Claude)
     */
    private function callLLM(string $prompt, string $analysisType = 'general'): ?array {
        $provider = $this->config['default_provider'];
        
        try {
            if ($provider === 'claude' && $this->claudeApiKey) {
                return $this->callClaude($prompt, $analysisType);
            } else if ($this->openaiApiKey) {
                return $this->callOpenAI($prompt, $analysisType);
            } else {
                throw new Exception('No API key available for LLM providers');
            }
        } catch (Exception $e) {
            // Try fallback provider
            if ($provider === 'claude' && $this->openaiApiKey) {
                return $this->callOpenAI($prompt, $analysisType);
            } else if ($provider === 'openai' && $this->claudeApiKey) {
                return $this->callClaude($prompt, $analysisType);
            }
            
            error_log("LLM API call failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Call OpenAI API
     */
    private function callOpenAI(string $prompt, string $analysisType): ?array {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => $this->config['openai_model'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional financial analyst. Provide accurate, objective analysis in JSON format only.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $this->config['max_tokens'],
            'temperature' => $this->config['temperature']
        ];
        
        $headers = [
            'Authorization: Bearer ' . $this->openaiApiKey,
            'Content-Type: application/json'
        ];
        
        $response = $this->makeHttpRequest($url, $data, $headers);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            $content = trim($response['choices'][0]['message']['content']);
            $decoded = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                $decoded['model_used'] = 'openai';
                $decoded['tokens_used'] = $response['usage']['total_tokens'] ?? null;
                return $decoded;
            }
        }
        
        return null;
    }
    
    /**
     * Call Claude API
     */
    private function callClaude(string $prompt, string $analysisType): ?array {
        $url = 'https://api.anthropic.com/v1/messages';
        
        $data = [
            'model' => $this->config['claude_model'],
            'max_tokens' => $this->config['max_tokens'],
            'temperature' => $this->config['temperature'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];
        
        $headers = [
            'Authorization: Bearer ' . $this->claudeApiKey,
            'Content-Type: application/json',
            'anthropic-version: 2023-06-01'
        ];
        
        $response = $this->makeHttpRequest($url, $data, $headers);
        
        if ($response && isset($response['content'][0]['text'])) {
            $content = trim($response['content'][0]['text']);
            $decoded = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                $decoded['model_used'] = 'claude';
                $decoded['tokens_used'] = $response['usage']['output_tokens'] ?? null;
                return $decoded;
            }
        }
        
        return null;
    }
    
    /**
     * Make HTTP request
     */
    private function makeHttpRequest(string $url, array $data, array $headers): ?array {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("HTTP request error: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP error code: " . $httpCode);
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response");
        }
        
        return $decoded;
    }
    
    /**
     * Calculate data freshness score
     */
    private function calculateDataFreshness(array $stockSummary): float {
        $score = 100;
        
        // Check price data age
        if ($stockSummary['latest_price']) {
            $priceAge = time() - strtotime($stockSummary['latest_price']['date']);
            if ($priceAge > 86400) { // More than 1 day old
                $score -= min(30, $priceAge / 86400 * 5);
            }
        } else {
            $score -= 50;
        }
        
        // Check news data
        if (empty($stockSummary['recent_news'])) {
            $score -= 20;
        } else {
            $latestNews = $stockSummary['recent_news'][0];
            $newsAge = time() - strtotime($latestNews['published_at']);
            if ($newsAge > 604800) { // More than 1 week old
                $score -= 15;
            }
        }
        
        // Check fundamental data
        if (!$stockSummary['fundamentals']) {
            $score -= 30;
        }
        
        return max(0, $score);
    }
    
    /**
     * Get service status
     */
    public function getServiceStatus(): array {
        return [
            'openai_available' => !empty($this->openaiApiKey),
            'claude_available' => !empty($this->claudeApiKey),
            'default_provider' => $this->config['default_provider'],
            'models' => [
                'openai' => $this->config['openai_model'],
                'claude' => $this->config['claude_model']
            ],
            'cache_timeout' => $this->config['cache_timeout'],
            'analysis_interval' => $this->config['analysis_interval']
        ];
    }
}