<?php

namespace App\Services;

/**
 * Market Factors Service
 * 
 * Tracks technical indicator predictions, calculates accuracy, and generates
 * trading recommendations based on weighted scoring of multiple market factors.
 * 
 * Features:
 * - Indicator prediction tracking and accuracy calculation
 * - Performance scoring with weighted recommendations
 * - Market, sector, index, forex, and economic summaries
 * - Correlation analysis between factors
 * - Data export/import for analysis
 */
class MarketFactorsService
{
    private string $storagePath;
    private array $predictions = [];
    private array $performance = [];
    private array $correlations = [];
    
    /**
     * Default weights for recommendation calculation
     */
    private array $defaultWeights = [
        'rsi' => 0.15,
        'macd' => 0.20,
        'moving_average' => 0.15,
        'volume' => 0.10,
        'bollinger_bands' => 0.10,
        'stochastic' => 0.10,
        'momentum' => 0.10,
        'support_resistance' => 0.10
    ];

    public function __construct(string $storagePath = null)
    {
        $this->storagePath = $storagePath ?? __DIR__ . '/../../storage/market_factors';
        $this->ensureStorageExists();
        $this->loadData();
    }

    /**
     * Track an indicator prediction for accuracy measurement
     * 
     * @param string $symbol Stock symbol
     * @param string $indicator Indicator name (rsi, macd, etc.)
     * @param string $prediction BUY, SELL, or HOLD
     * @param float $confidence Confidence level (0.0 to 1.0)
     * @param array $context Additional context (price, indicator values, etc.)
     * @return string Prediction ID
     */
    public function trackIndicatorPrediction(
        string $symbol,
        string $indicator,
        string $prediction,
        float $confidence,
        array $context = []
    ): string {
        $predictionId = $this->generatePredictionId($symbol, $indicator);
        
        $this->predictions[$predictionId] = [
            'id' => $predictionId,
            'symbol' => $symbol,
            'indicator' => $indicator,
            'prediction' => $prediction,
            'confidence' => $confidence,
            'context' => $context,
            'timestamp' => date('Y-m-d H:i:s'),
            'result' => null,
            'outcome_date' => null
        ];
        
        $this->saveData();
        return $predictionId;
    }

    /**
     * Update indicator accuracy after outcome is known
     * 
     * @param string $predictionId Prediction ID
     * @param string $result CORRECT, INCORRECT, or PARTIAL
     * @param array $outcomeData Actual price movement, gain/loss, etc.
     * @return bool Success
     */
    public function updateIndicatorAccuracy(
        string $predictionId,
        string $result,
        array $outcomeData = []
    ): bool {
        if (!isset($this->predictions[$predictionId])) {
            return false;
        }
        
        $this->predictions[$predictionId]['result'] = $result;
        $this->predictions[$predictionId]['outcome_date'] = date('Y-m-d H:i:s');
        $this->predictions[$predictionId]['outcome_data'] = $outcomeData;
        
        // Update performance metrics
        $indicator = $this->predictions[$predictionId]['indicator'];
        $this->updateIndicatorPerformance($indicator);
        
        $this->saveData();
        return true;
    }

    /**
     * Calculate prediction accuracy for an indicator
     * 
     * @param string $indicator Indicator name
     * @param int $lookbackDays Days to analyze (default: 90)
     * @return array Accuracy metrics
     */
    public function calculatePredictionAccuracy(string $indicator, int $lookbackDays = 90): array
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$lookbackDays} days"));
        
        $total = 0;
        $correct = 0;
        $incorrect = 0;
        $partial = 0;
        $pending = 0;
        
        foreach ($this->predictions as $prediction) {
            if ($prediction['indicator'] !== $indicator) {
                continue;
            }
            
            if ($prediction['timestamp'] < $cutoffDate) {
                continue;
            }
            
            $total++;
            
            if ($prediction['result'] === null) {
                $pending++;
            } elseif ($prediction['result'] === 'CORRECT') {
                $correct++;
            } elseif ($prediction['result'] === 'INCORRECT') {
                $incorrect++;
            } elseif ($prediction['result'] === 'PARTIAL') {
                $partial++;
            }
        }
        
        $completed = $total - $pending;
        $accuracy = $completed > 0 ? ($correct + ($partial * 0.5)) / $completed : 0.0;
        
        return [
            'indicator' => $indicator,
            'total_predictions' => $total,
            'correct' => $correct,
            'incorrect' => $incorrect,
            'partial' => $partial,
            'pending' => $pending,
            'accuracy' => round($accuracy, 4),
            'period_days' => $lookbackDays
        ];
    }

    /**
     * Update performance score for an indicator
     * 
     * @param string $indicator Indicator name
     * @return void
     */
    public function updateIndicatorPerformance(string $indicator): void
    {
        $accuracy = $this->calculatePredictionAccuracy($indicator, 90);
        
        // Calculate performance score (0.0 to 1.0)
        $score = $accuracy['accuracy'];
        
        // Adjust for confidence levels
        $avgConfidence = $this->calculateAverageConfidence($indicator);
        $score = ($score * 0.7) + ($avgConfidence * 0.3);
        
        $this->performance[$indicator] = [
            'indicator' => $indicator,
            'score' => round($score, 4),
            'accuracy' => $accuracy['accuracy'],
            'avg_confidence' => $avgConfidence,
            'total_predictions' => $accuracy['total_predictions'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->saveData();
    }

    /**
     * Get indicator accuracy metrics
     * 
     * @param string $indicator Indicator name
     * @param int $lookbackDays Days to analyze
     * @return array Accuracy data
     */
    public function getIndicatorAccuracy(string $indicator, int $lookbackDays = 90): array
    {
        return $this->calculatePredictionAccuracy($indicator, $lookbackDays);
    }

    /**
     * Get indicator performance score
     * 
     * @param string $indicator Indicator name
     * @return float Performance score (0.0 to 1.0)
     */
    public function getIndicatorPerformanceScore(string $indicator): float
    {
        return $this->performance[$indicator]['score'] ?? 0.0;
    }

    /**
     * Get all indicator performance data
     * 
     * @return array All performance metrics
     */
    public function getAllIndicatorPerformance(): array
    {
        return $this->performance;
    }

    /**
     * Calculate weighted score across multiple indicators
     * 
     * @param array $signals Indicator signals with predictions
     * @param array $weights Custom weights (optional)
     * @return float Weighted score (-1.0 to 1.0)
     */
    public function calculateWeightedScore(array $signals, array $weights = null): float
    {
        $weights = $weights ?? $this->defaultWeights;
        $totalWeight = 0;
        $weightedSum = 0;
        
        foreach ($signals as $indicator => $signal) {
            if (!isset($weights[$indicator])) {
                continue;
            }
            
            $weight = $weights[$indicator];
            $performance = $this->getIndicatorPerformanceScore($indicator);
            
            // Convert signal to numeric value
            $signalValue = match($signal['prediction']) {
                'BUY' => 1.0,
                'SELL' => -1.0,
                'HOLD' => 0.0,
                default => 0.0
            };
            
            // Weight by performance and confidence
            $confidence = $signal['confidence'] ?? 1.0;
            $adjustedWeight = $weight * $performance * $confidence;
            
            $weightedSum += $signalValue * $adjustedWeight;
            $totalWeight += $adjustedWeight;
        }
        
        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0.0;
    }

    /**
     * Generate trading recommendation
     * 
     * @param string $symbol Stock symbol
     * @param array $signals Indicator signals
     * @param array $weights Custom weights (optional)
     * @return array Recommendation with signal and confidence
     */
    public function generateRecommendation(
        string $symbol,
        array $signals,
        array $weights = null
    ): array {
        $weightedScore = $this->calculateWeightedScore($signals, $weights);
        
        // Determine signal based on weighted score
        if ($weightedScore > 0.3) {
            $signal = 'BUY';
        } elseif ($weightedScore < -0.3) {
            $signal = 'SELL';
        } else {
            $signal = 'HOLD';
        }
        
        $confidence = $this->calculateConfidence($signals, $weightedScore);
        
        return [
            'symbol' => $symbol,
            'signal' => $signal,
            'confidence' => $confidence,
            'weighted_score' => round($weightedScore, 4),
            'contributing_signals' => count($signals),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Calculate recommendation confidence
     * 
     * @param array $signals Indicator signals
     * @param float $weightedScore Weighted score
     * @return float Confidence (0.0 to 1.0)
     */
    public function calculateConfidence(array $signals, float $weightedScore): float
    {
        // Base confidence from signal agreement
        $agreement = abs($weightedScore);
        
        // Factor in number of signals (more signals = higher confidence)
        $signalCount = count($signals);
        $signalFactor = min(1.0, $signalCount / 5); // Normalize to 5 signals
        
        // Factor in average indicator performance
        $avgPerformance = 0;
        foreach ($signals as $indicator => $signal) {
            $avgPerformance += $this->getIndicatorPerformanceScore($indicator);
        }
        $avgPerformance = $signalCount > 0 ? $avgPerformance / $signalCount : 0;
        
        // Combine factors
        $confidence = ($agreement * 0.5) + ($signalFactor * 0.25) + ($avgPerformance * 0.25);
        
        return round(min(1.0, $confidence), 4);
    }

    /**
     * Calculate risk level for a recommendation
     * 
     * @param array $recommendation Recommendation data
     * @param array $marketContext Market conditions
     * @return string Risk level: LOW, MEDIUM, HIGH
     */
    public function calculateRiskLevel(array $recommendation, array $marketContext = []): string
    {
        $confidence = $recommendation['confidence'];
        $volatility = $marketContext['volatility'] ?? 0.5;
        
        // Lower confidence or higher volatility = higher risk
        // Scale risk score from 0 to 1
        $riskScore = ((1 - $confidence) + $volatility) / 2;
        
        if ($riskScore < 0.4) {
            return 'LOW';
        } elseif ($riskScore < 0.7) {
            return 'MEDIUM';
        } else {
            return 'HIGH';
        }
    }

    /**
     * Get market summary with key metrics
     * 
     * @param array $symbols Symbols to analyze
     * @return array Market summary
     */
    public function getMarketSummary(array $symbols = []): array
    {
        $buySignals = 0;
        $sellSignals = 0;
        $holdSignals = 0;
        $avgConfidence = 0;
        
        foreach ($symbols as $symbol => $recommendation) {
            match($recommendation['signal']) {
                'BUY' => $buySignals++,
                'SELL' => $sellSignals++,
                'HOLD' => $holdSignals++,
                default => null
            };
            
            $avgConfidence += $recommendation['confidence'] ?? 0;
        }
        
        $total = count($symbols);
        $avgConfidence = $total > 0 ? $avgConfidence / $total : 0;
        
        // Calculate market sentiment
        $sentiment = $this->calculateMarketSentiment([
            'buy' => $buySignals,
            'sell' => $sellSignals,
            'hold' => $holdSignals
        ]);
        
        return [
            'total_symbols' => $total,
            'buy_signals' => $buySignals,
            'sell_signals' => $sellSignals,
            'hold_signals' => $holdSignals,
            'avg_confidence' => round($avgConfidence, 4),
            'sentiment' => $sentiment,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get sector summary
     * 
     * @param string $sector Sector name
     * @param array $symbolData Symbol recommendations in sector
     * @return array Sector summary
     */
    public function getSectorSummary(string $sector, array $symbolData): array
    {
        $summary = $this->getMarketSummary($symbolData);
        $summary['sector'] = $sector;
        
        return $summary;
    }

    /**
     * Get index summary (e.g., S&P 500, NASDAQ)
     * 
     * @param string $index Index name
     * @param array $indexData Index component data
     * @return array Index summary
     */
    public function getIndexSummary(string $index, array $indexData): array
    {
        $summary = $this->getMarketSummary($indexData);
        $summary['index'] = $index;
        
        return $summary;
    }

    /**
     * Get forex summary
     * 
     * @param array $forexPairs Forex pair data
     * @return array Forex summary
     */
    public function getForexSummary(array $forexPairs): array
    {
        $summary = $this->getMarketSummary($forexPairs);
        $summary['type'] = 'forex';
        
        return $summary;
    }

    /**
     * Get economics summary (GDP, inflation, unemployment, etc.)
     * 
     * @param array $economicData Economic indicator data
     * @return array Economics summary
     */
    public function getEconomicsSummary(array $economicData): array
    {
        return [
            'indicators' => $economicData,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Track correlation between factors
     * 
     * @param string $factor1 First factor
     * @param string $factor2 Second factor
     * @param float $correlation Correlation coefficient (-1.0 to 1.0)
     * @return void
     */
    public function trackCorrelation(string $factor1, string $factor2, float $correlation): void
    {
        $key = $this->getCorrelationKey($factor1, $factor2);
        
        $this->correlations[$key] = [
            'factor1' => $factor1,
            'factor2' => $factor2,
            'correlation' => round($correlation, 4),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->saveData();
    }

    /**
     * Get correlation matrix for all tracked factors
     * 
     * @return array Correlation matrix
     */
    public function getCorrelationMatrix(): array
    {
        return $this->correlations;
    }

    /**
     * Calculate market sentiment
     * 
     * @param array $signals Signal counts (buy, sell, hold)
     * @return string Sentiment: BULLISH, BEARISH, or NEUTRAL
     */
    public function calculateMarketSentiment(array $signals): string
    {
        $buy = $signals['buy'] ?? 0;
        $sell = $signals['sell'] ?? 0;
        $hold = $signals['hold'] ?? 0;
        $total = $buy + $sell + $hold;
        
        if ($total === 0) {
            return 'NEUTRAL';
        }
        
        $buyRatio = $buy / $total;
        $sellRatio = $sell / $total;
        
        if ($buyRatio > 0.5) {
            return 'BULLISH';
        } elseif ($sellRatio > 0.5) {
            return 'BEARISH';
        } else {
            return 'NEUTRAL';
        }
    }

    /**
     * Export data for analysis
     * 
     * @param string $type Data type: predictions, performance, correlations, all
     * @return array Exported data
     */
    public function exportData(string $type = 'all'): array
    {
        return match($type) {
            'predictions' => $this->predictions,
            'performance' => $this->performance,
            'correlations' => $this->correlations,
            'all' => [
                'predictions' => $this->predictions,
                'performance' => $this->performance,
                'correlations' => $this->correlations
            ],
            default => []
        };
    }

    /**
     * Import data from external source
     * 
     * @param array $data Data to import
     * @param string $type Data type: predictions, performance, correlations
     * @return bool Success
     */
    public function importData(array $data, string $type): bool
    {
        try {
            match($type) {
                'predictions' => $this->predictions = array_merge($this->predictions, $data),
                'performance' => $this->performance = array_merge($this->performance, $data),
                'correlations' => $this->correlations = array_merge($this->correlations, $data),
                default => null
            };
            
            $this->saveData();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Calculate average confidence for an indicator
     * 
     * @param string $indicator Indicator name
     * @return float Average confidence
     */
    private function calculateAverageConfidence(string $indicator): float
    {
        $total = 0;
        $sum = 0;
        
        foreach ($this->predictions as $prediction) {
            if ($prediction['indicator'] === $indicator) {
                $sum += $prediction['confidence'];
                $total++;
            }
        }
        
        return $total > 0 ? $sum / $total : 0.0;
    }

    /**
     * Generate prediction ID
     * 
     * @param string $symbol Stock symbol
     * @param string $indicator Indicator name
     * @return string Prediction ID
     */
    private function generatePredictionId(string $symbol, string $indicator): string
    {
        return sprintf(
            '%s_%s_%s_%s',
            $indicator,
            $symbol,
            date('YmdHis'),
            substr(md5(uniqid()), 0, 8)
        );
    }

    /**
     * Get correlation key (sorted to ensure consistency)
     * 
     * @param string $factor1 First factor
     * @param string $factor2 Second factor
     * @return string Correlation key
     */
    private function getCorrelationKey(string $factor1, string $factor2): string
    {
        $factors = [$factor1, $factor2];
        sort($factors);
        return implode('_', $factors);
    }

    /**
     * Ensure storage directories exist
     * 
     * @return void
     */
    private function ensureStorageExists(): void
    {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0777, true);
        }
    }

    /**
     * Load data from storage
     * 
     * @return void
     */
    private function loadData(): void
    {
        $predictionsFile = $this->storagePath . '/predictions.json';
        $performanceFile = $this->storagePath . '/performance.json';
        $correlationsFile = $this->storagePath . '/correlations.json';
        
        if (file_exists($predictionsFile)) {
            $this->predictions = json_decode(file_get_contents($predictionsFile), true) ?? [];
        }
        
        if (file_exists($performanceFile)) {
            $this->performance = json_decode(file_get_contents($performanceFile), true) ?? [];
        }
        
        if (file_exists($correlationsFile)) {
            $this->correlations = json_decode(file_get_contents($correlationsFile), true) ?? [];
        }
    }

    /**
     * Save data to storage
     * 
     * @return void
     */
    private function saveData(): void
    {
        file_put_contents(
            $this->storagePath . '/predictions.json',
            json_encode($this->predictions, JSON_PRETTY_PRINT)
        );
        
        file_put_contents(
            $this->storagePath . '/performance.json',
            json_encode($this->performance, JSON_PRETTY_PRINT)
        );
        
        file_put_contents(
            $this->storagePath . '/correlations.json',
            json_encode($this->correlations, JSON_PRETTY_PRINT)
        );
    }
}
