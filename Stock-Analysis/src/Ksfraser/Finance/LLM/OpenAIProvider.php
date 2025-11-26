<?php
/**
 * OpenAI LLM Provider Implementation
 * 
 * Integrates with OpenAI's ChatGPT API for financial analysis.
 * Implements the LLMProviderInterface following the Interface Segregation Principle.
 */

namespace Ksfraser\Finance\LLM;

use Ksfraser\Finance\Interfaces\LLMProviderInterface;
use DateTime;

class OpenAIProvider implements LLMProviderInterface
{
    private $httpClient;
    private $apiKey;
    private $baseUrl = 'https://api.openai.com/v1/chat/completions';
    private $model = 'gpt-4';

    public function __construct(string $apiKey, $httpClient = null)
    {
        $this->apiKey = $apiKey;
        $this->httpClient = $httpClient;
    }

    public function analyzeFinancialData(array $data, string $query): string
    {
        if (!$this->isAvailable()) {
            return 'OpenAI analysis is not available. Please check API key configuration.';
        }

        $prompt = $this->buildAnalysisPrompt($data, $query);
        
        try {
            $response = $this->makeApiRequest([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system', 
                        'content' => 'You are a professional financial analyst with expertise in stock market analysis, fundamental analysis, and investment recommendations. Provide clear, actionable insights based on the data provided.'
                    ],
                    [
                        'role' => 'user', 
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1500,
                'temperature' => 0.3 // Lower temperature for more consistent financial analysis
            ]);

            if ($response && isset($response['choices'][0]['message']['content'])) {
                return $response['choices'][0]['message']['content'];
            }
            
            return 'Unable to generate analysis at this time.';
        } catch (\Exception $e) {
            error_log("OpenAI API error: " . $e->getMessage());
            return 'Analysis temporarily unavailable due to technical issues.';
        }
    }

    public function getRecommendation(string $symbol, array $financialData): array
    {
        $analysisQuery = "Based on the financial data provided, give a clear BUY/HOLD/SELL recommendation for {$symbol}. " .
                        "Include your confidence level (1-10), key factors influencing your decision, " .
                        "potential risks, and a target price range if applicable.";
        
        $analysis = $this->analyzeFinancialData($financialData, $analysisQuery);
        
        // Parse the recommendation from the analysis
        $recommendation = $this->extractRecommendation($analysis);
        $confidence = $this->extractConfidence($analysis);
        $targetPrice = $this->extractTargetPrice($analysis);
        
        return [
            'symbol' => $symbol,
            'recommendation' => $recommendation,
            'confidence' => $confidence,
            'analysis' => $analysis,
            'target_price' => $targetPrice,
            'timestamp' => new DateTime(),
            'provider' => $this->getName()
        ];
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getName(): string
    {
        return 'OpenAI ChatGPT';
    }

    /**
     * Build a comprehensive prompt for financial analysis
     */
    private function buildAnalysisPrompt(array $data, string $query): string
    {
        $dataString = json_encode($data, JSON_PRETTY_PRINT);
        
        $prompt = "FINANCIAL DATA ANALYSIS REQUEST\n\n";
        $prompt .= "Financial Data:\n{$dataString}\n\n";
        $prompt .= "Analysis Request: {$query}\n\n";
        $prompt .= "Please provide a comprehensive analysis that includes:\n";
        $prompt .= "1. Current market position and recent performance\n";
        $prompt .= "2. Key financial metrics interpretation\n";
        $prompt .= "3. Market trends and sector comparison\n";
        $prompt .= "4. Risk assessment\n";
        $prompt .= "5. Investment recommendation with reasoning\n";
        
        return $prompt;
    }

    /**
     * Make API request to OpenAI
     */
    private function makeApiRequest(array $payload): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey
                ],
                'content' => json_encode($payload),
                'timeout' => 60
            ]
        ]);

        $response = file_get_contents($this->baseUrl, false, $context);
        
        if ($response === false) {
            throw new \Exception('Failed to connect to OpenAI API');
        }

        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            throw new \Exception('OpenAI API Error: ' . $data['error']['message']);
        }
        
        return $data;
    }

    /**
     * Extract recommendation from analysis text
     */
    private function extractRecommendation(string $analysis): string
    {
        $analysis = strtoupper($analysis);
        
        // Look for explicit recommendations
        if (preg_match('/\b(STRONG\s+BUY|STRONGBUY)\b/', $analysis)) {
            return 'STRONG_BUY';
        }
        if (preg_match('/\b(STRONG\s+SELL|STRONGSELL)\b/', $analysis)) {
            return 'STRONG_SELL';
        }
        if (preg_match('/\bBUY\b/', $analysis)) {
            return 'BUY';
        }
        if (preg_match('/\bSELL\b/', $analysis)) {
            return 'SELL';
        }
        if (preg_match('/\bHOLD\b/', $analysis)) {
            return 'HOLD';
        }
        
        // Default to HOLD if no clear recommendation found
        return 'HOLD';
    }

    /**
     * Extract confidence level from analysis
     */
    private function extractConfidence(string $analysis): float
    {
        // Look for explicit confidence numbers
        if (preg_match('/confidence[:\s]*(\d+(?:\.\d+)?)/i', $analysis, $matches)) {
            $confidence = (float)$matches[1];
            // Normalize to 0-1 scale if it's on 1-10 scale
            return $confidence > 1 ? $confidence / 10 : $confidence;
        }
        
        // Analyze language strength for implicit confidence
        $confidenceWords = [
            'certain' => 0.9, 'confident' => 0.8, 'strong' => 0.8, 'clear' => 0.7,
            'likely' => 0.6, 'probable' => 0.6, 'expect' => 0.6
        ];
        
        $uncertainWords = [
            'uncertain' => -0.3, 'unclear' => -0.2, 'mixed' => -0.2, 'volatile' => -0.1,
            'risky' => -0.2, 'cautious' => -0.1
        ];
        
        $confidence = 0.5; // Base confidence
        $analysis_lower = strtolower($analysis);
        
        foreach ($confidenceWords as $word => $boost) {
            if (strpos($analysis_lower, $word) !== false) {
                $confidence += $boost * 0.5; // Reduce impact
            }
        }
        
        foreach ($uncertainWords as $word => $penalty) {
            if (strpos($analysis_lower, $word) !== false) {
                $confidence += $penalty;
            }
        }
        
        return max(0.1, min(1.0, $confidence));
    }

    /**
     * Extract target price from analysis
     */
    private function extractTargetPrice(string $analysis): ?float
    {
        // Look for target price patterns
        if (preg_match('/target\s+price[:\s]*\$?(\d+(?:\.\d+)?)/i', $analysis, $matches)) {
            return (float)$matches[1];
        }
        
        if (preg_match('/price\s+target[:\s]*\$?(\d+(?:\.\d+)?)/i', $analysis, $matches)) {
            return (float)$matches[1];
        }
        
        return null;
    }
}
