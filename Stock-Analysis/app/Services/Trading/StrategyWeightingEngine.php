<?php

namespace App\Services\Trading;

/**
 * Strategy Weighting Engine
 * 
 * Manages portfolio allocation across multiple trading strategies using preset
 * risk profiles and consensus-based decision making.
 * 
 * Preset Allocation Profiles:
 * - **Conservative**: 35% QualityDividend, 25% MeanReversion, 20% Contrarian
 *   Focus on income and stability, minimal growth exposure
 * 
 * - **Balanced**: 20% each QualityDividend/MomentumQuality, equal distribution
 *   Diversified approach balancing growth, income, and value
 * 
 * - **Aggressive**: 30% SmallCapCatalyst, 25% MomentumQuality, 20% IPlace
 *   Growth-focused with higher risk tolerance
 * 
 * - **Growth**: 30% MomentumQuality, 25% IPlace, 20% SmallCapCatalyst
 *   Momentum and upgrade-driven opportunities
 * 
 * - **Value**: 35% Contrarian, 25% QualityDividend, 20% MeanReversion
 *   Deep value and mean reversion focus
 * 
 * Consensus Decision Making:
 * - Aggregates signals from all strategies based on their weights
 * - Calculates weighted confidence scores
 * - Supports BUY/SELL/HOLD with reasoning from each strategy
 * - Normalizes weights to sum to 1.0
 * 
 * @package App\Services\Trading
 */
class StrategyWeightingEngine
{
    private array $strategies = [];
    private array $weights = [];
    
    // Preset allocation profiles
    private const PROFILES = [
        'conservative' => [
            'QualityDividend' => 0.35,
            'MeanReversion' => 0.25,
            'Contrarian' => 0.20,
            'IPlace' => 0.10,
            'MomentumQuality' => 0.05,
            'SmallCapCatalyst' => 0.05
        ],
        'balanced' => [
            'QualityDividend' => 0.20,
            'MomentumQuality' => 0.20,
            'MeanReversion' => 0.15,
            'IPlace' => 0.15,
            'Contrarian' => 0.15,
            'SmallCapCatalyst' => 0.15
        ],
        'aggressive' => [
            'SmallCapCatalyst' => 0.30,
            'MomentumQuality' => 0.25,
            'IPlace' => 0.20,
            'MeanReversion' => 0.15,
            'Contrarian' => 0.05,
            'QualityDividend' => 0.05
        ],
        'growth' => [
            'MomentumQuality' => 0.30,
            'IPlace' => 0.25,
            'SmallCapCatalyst' => 0.20,
            'MeanReversion' => 0.15,
            'Contrarian' => 0.05,
            'QualityDividend' => 0.05
        ],
        'value' => [
            'Contrarian' => 0.35,
            'QualityDividend' => 0.25,
            'MeanReversion' => 0.20,
            'MomentumQuality' => 0.10,
            'IPlace' => 0.05,
            'SmallCapCatalyst' => 0.05
        ],
        'catalyst_focused' => [
            'SmallCapCatalyst' => 0.40,
            'IPlace' => 0.25,
            'MomentumQuality' => 0.15,
            'Contrarian' => 0.10,
            'MeanReversion' => 0.05,
            'QualityDividend' => 0.05
        ]
    ];

    public function __construct(array $strategies = [])
    {
        $this->strategies = $strategies;
        $this->setWeights($this->getDefaultWeights());
    }

    /**
     * Register a strategy with the weighting engine
     */
    public function addStrategy(string $name, TradingStrategyInterface $strategy): void
    {
        $this->strategies[$name] = $strategy;
        
        // Recalculate weights to include new strategy with equal weight
        $this->setWeights($this->getDefaultWeights());
    }

    /**
     * Set custom weights for strategies
     * @param array $weights Associative array of strategy name => weight (0-1)
     * @throws \InvalidArgumentException if weights are invalid
     */
    public function setWeights(array $weights): void
    {
        $sum = array_sum($weights);
        
        if ($sum <= 0) {
            throw new \InvalidArgumentException('Weights must sum to a positive value');
        }

        // Auto-normalize to exactly 1.0
        $normalized = [];
        foreach ($weights as $name => $weight) {
            if ($weight < 0) {
                throw new \InvalidArgumentException("Weight for $name cannot be negative");
            }
            $normalized[$name] = $weight / $sum;
        }
        
        $this->weights = $normalized;
    }

    /**
     * Load a preset allocation profile
     * @param string $profile Profile name (conservative, balanced, aggressive, etc.)
     * @throws \InvalidArgumentException if profile doesn't exist
     */
    public function loadProfile(string $profile): void
    {
        $profile = strtolower($profile);
        
        if (!isset(self::PROFILES[$profile])) {
            throw new \InvalidArgumentException(
                sprintf('Unknown profile: %s. Available: %s', 
                    $profile, 
                    implode(', ', array_keys(self::PROFILES))
                )
            );
        }
        
        $this->setWeights(self::PROFILES[$profile]);
    }

    /**
     * Get available preset profiles
     * @return array List of profile names with descriptions
     */
    public function getAvailableProfiles(): array
    {
        return [
            'conservative' => [
                'name' => 'Conservative',
                'description' => 'Focus on dividend quality and mean reversion (60% defensive)',
                'weights' => self::PROFILES['conservative']
            ],
            'balanced' => [
                'name' => 'Balanced',
                'description' => 'Equal mix of all strategies for diversification',
                'weights' => self::PROFILES['balanced']
            ],
            'aggressive' => [
                'name' => 'Aggressive',
                'description' => 'High allocation to small-cap catalysts and momentum (55% growth)',
                'weights' => self::PROFILES['aggressive']
            ],
            'growth' => [
                'name' => 'Growth',
                'description' => 'Momentum and analyst upgrades focus (55% growth)',
                'weights' => self::PROFILES['growth']
            ],
            'value' => [
                'name' => 'Value',
                'description' => 'Contrarian and dividend focus (60% value)',
                'weights' => self::PROFILES['value']
            ],
            'catalyst_focused' => [
                'name' => 'Catalyst Focused',
                'description' => 'Event-driven and upgrade focus (65% catalyst)',
                'weights' => self::PROFILES['catalyst_focused']
            ]
        ];
    }

    /**
     * Get current weights
     */
    public function getWeights(): array
    {
        return $this->weights;
    }

    /**
     * Analyze a symbol across all strategies and return weighted recommendation
     * @param string $symbol Stock symbol
     * @param string $date Analysis date (default: today)
     * @return array Weighted analysis results
     */
    public function analyzeSymbol(string $symbol, string $date = 'today'): array
    {
        $results = [];
        $weightedConfidence = 0;
        $buyVotes = 0;
        $holdVotes = 0;
        $totalWeight = 0;
        
        foreach ($this->strategies as $name => $strategy) {
            $weight = $this->weights[$name] ?? 0;
            
            if ($weight == 0) {
                continue;
            }
            
            try {
                $analysis = $strategy->analyze($symbol, $date);
                
                $results[$name] = [
                    'action' => $analysis['action'],
                    'confidence' => $analysis['confidence'],
                    'reasoning' => $analysis['reasoning'],
                    'weight' => $weight,
                    'weighted_confidence' => $analysis['confidence'] * $weight
                ];
                
                $weightedConfidence += $analysis['confidence'] * $weight;
                $totalWeight += $weight;
                
                if ($analysis['action'] === 'BUY') {
                    $buyVotes += $weight;
                } else {
                    $holdVotes += $weight;
                }
                
            } catch (\Exception $e) {
                $results[$name] = [
                    'action' => 'ERROR',
                    'confidence' => 0,
                    'reasoning' => $e->getMessage(),
                    'weight' => $weight,
                    'weighted_confidence' => 0
                ];
            }
        }
        
        // Determine overall action
        $overallAction = 'HOLD';
        if ($buyVotes > 0.50) {
            $overallAction = 'BUY';
        }
        
        // Calculate consensus level
        $consensus = max($buyVotes, $holdVotes);
        
        return [
            'symbol' => $symbol,
            'date' => $date,
            'overall_action' => $overallAction,
            'weighted_confidence' => round($weightedConfidence, 2),
            'buy_vote_weight' => round($buyVotes, 2),
            'hold_vote_weight' => round($holdVotes, 2),
            'consensus' => round($consensus * 100, 1),
            'strategy_results' => $results,
            'recommendation' => $this->buildRecommendation(
                $overallAction, 
                $weightedConfidence, 
                $buyVotes, 
                $results
            )
        ];
    }

    /**
     * Analyze multiple symbols and rank by weighted confidence
     * @param array $symbols List of stock symbols
     * @param string $date Analysis date
     * @return array Ranked analysis results
     */
    public function analyzeAndRank(array $symbols, string $date = 'today'): array
    {
        $analyses = [];
        
        foreach ($symbols as $symbol) {
            $analyses[] = $this->analyzeSymbol($symbol, $date);
        }
        
        // Sort by weighted confidence descending
        usort($analyses, function($a, $b) {
            return $b['weighted_confidence'] <=> $a['weighted_confidence'];
        });
        
        return $analyses;
    }

    /**
     * Get strategy agreement metrics
     * Shows how often strategies agree on BUY signals
     */
    public function getStrategyAgreement(array $symbols, string $date = 'today'): array
    {
        $agreementMatrix = [];
        $strategyNames = array_keys($this->strategies);
        
        foreach ($strategyNames as $name1) {
            foreach ($strategyNames as $name2) {
                if ($name1 === $name2) continue;
                
                $agreements = 0;
                $total = 0;
                
                foreach ($symbols as $symbol) {
                    try {
                        $result1 = $this->strategies[$name1]->analyze($symbol, $date);
                        $result2 = $this->strategies[$name2]->analyze($symbol, $date);
                        
                        if ($result1['action'] === $result2['action']) {
                            $agreements++;
                        }
                        $total++;
                    } catch (\Exception $e) {
                        // Skip on error
                    }
                }
                
                if ($total > 0) {
                    $agreementMatrix[$name1][$name2] = round($agreements / $total, 3);
                }
            }
        }
        
        return $agreementMatrix;
    }

    /**
     * Rebalance weights based on market conditions
     * @param string $marketCondition bull, bear, sideways, volatile
     */
    public function rebalanceForMarketConditions(string $marketCondition): void
    {
        $adjustments = [
            'bull' => [
                'MomentumQuality' => 1.3,
                'IPlace' => 1.2,
                'SmallCapCatalyst' => 1.1,
                'MeanReversion' => 0.8,
                'Contrarian' => 0.7,
                'QualityDividend' => 0.9
            ],
            'bear' => [
                'QualityDividend' => 1.4,
                'Contrarian' => 1.3,
                'MeanReversion' => 1.1,
                'MomentumQuality' => 0.6,
                'IPlace' => 0.7,
                'SmallCapCatalyst' => 0.5
            ],
            'sideways' => [
                'MeanReversion' => 1.3,
                'Contrarian' => 1.2,
                'QualityDividend' => 1.1,
                'MomentumQuality' => 0.8,
                'IPlace' => 0.9,
                'SmallCapCatalyst' => 0.7
            ],
            'volatile' => [
                'MeanReversion' => 1.4,
                'SmallCapCatalyst' => 1.2,
                'QualityDividend' => 1.1,
                'Contrarian' => 1.0,
                'MomentumQuality' => 0.7,
                'IPlace' => 0.6
            ]
        ];
        
        if (!isset($adjustments[$marketCondition])) {
            throw new \InvalidArgumentException(
                "Unknown market condition: $marketCondition"
            );
        }
        
        $adjustedWeights = [];
        foreach ($this->weights as $name => $weight) {
            $multiplier = $adjustments[$marketCondition][$name] ?? 1.0;
            $adjustedWeights[$name] = $weight * $multiplier;
        }
        
        // Normalize to sum to 1.0
        $sum = array_sum($adjustedWeights);
        foreach ($adjustedWeights as $name => $weight) {
            $adjustedWeights[$name] = $weight / $sum;
        }
        
        $this->weights = $adjustedWeights;
    }

    /**
     * Get default equal weights
     */
    private function getDefaultWeights(): array
    {
        if (empty($this->strategies)) {
            return [];
        }
        
        $weight = 1.0 / count($this->strategies);
        $weights = [];
        
        foreach (array_keys($this->strategies) as $name) {
            $weights[$name] = $weight;
        }
        
        return $weights;
    }

    /**
     * Build human-readable recommendation
     */
    private function buildRecommendation(
        string $action, 
        float $confidence, 
        float $buyVotes, 
        array $results
    ): string {
        $buyStrategies = [];
        
        foreach ($results as $name => $result) {
            if ($result['action'] === 'BUY') {
                $buyStrategies[] = sprintf(
                    '%s (%.0f%% confidence, %.0f%% weight)',
                    $name,
                    $result['confidence'],
                    $result['weight'] * 100
                );
            }
        }
        
        if ($action === 'BUY') {
            $recommendation = sprintf(
                'BUY recommendation with %.1f%% weighted confidence. ',
                $confidence
            );
            
            $recommendation .= sprintf(
                '%.0f%% of portfolio weight voting BUY. ',
                $buyVotes * 100
            );
            
            if (!empty($buyStrategies)) {
                $recommendation .= 'Supporting strategies: ' . implode(', ', $buyStrategies);
            }
        } else {
            $recommendation = sprintf(
                'HOLD recommendation. Only %.0f%% of portfolio weight voting BUY (need >50%%). ',
                $buyVotes * 100
            );
            
            if (!empty($buyStrategies)) {
                $recommendation .= 'Strategies with BUY signals: ' . implode(', ', $buyStrategies);
            } else {
                $recommendation .= 'No strategies recommend BUY at this time.';
            }
        }
        
        return $recommendation;
    }
}
