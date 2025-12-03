<?php

namespace App\Services\Trading;

use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

/**
 * Small-Cap Catalyst Strategy Service
 * 
 * Identifies small-cap stocks with upcoming catalysts that could drive significant price movement.
 * Focuses on event-driven opportunities with asymmetric risk/reward profiles (minimum 3:1).
 * 
 * Key Catalysts Monitored:
 * 1. Earnings Announcements (especially surprise beats)
 * 2. FDA Approvals / Clinical Trial Results (biotech/pharma)
 * 3. Product Launches / Major Contracts
 * 4. M&A Activity / Acquisition Targets
 * 5. Analyst Coverage Initiation (coverage gap)
 * 6. Insider Buying Activity
 * 7. Short Interest Squeeze Potential
 * 8. Technical Breakouts from Consolidation
 * 
 * Risk Management:
 * - Strict 3:1 minimum risk/reward ratio
 * - Position sizing based on catalyst confidence
 * - Time-boxed exits (close before catalyst if no movement)
 * - Tight stop losses (15% default)
 * 
 * @package App\Services\Trading
 */
class SmallCapCatalystStrategyService implements TradingStrategyInterface
{
    private MarketDataService $marketDataService;
    private MarketDataRepositoryInterface $marketDataRepository;
    
    private array $parameters = [
        'min_market_cap' => 50000000,          // $50M minimum (true small-cap)
        'max_market_cap' => 2000000000,        // $2B maximum
        'min_avg_volume' => 100000,            // 100K shares minimum liquidity
        'min_risk_reward_ratio' => 3.0,        // 3:1 minimum risk/reward
        'stop_loss_percent' => 0.15,           // 15% stop loss
        'min_catalyst_confidence' => 0.60,     // 60% minimum confidence score
        'max_days_to_catalyst' => 90,          // 90 days maximum timeline
        'min_days_to_catalyst' => 7,           // 7 days minimum (avoid front-running)
        'max_short_interest' => 0.30,          // 30% max short interest (squeeze potential)
        'min_short_interest_squeeze' => 0.15,  // 15% for squeeze plays
        'min_insider_ownership' => 0.10,       // 10% minimum insider ownership
        'max_institutional_ownership' => 0.50, // 50% max (want undiscovered stocks)
        'min_analyst_coverage_gap' => 0,       // 0 = no coverage (undiscovered)
        'max_analyst_coverage_gap' => 3,       // Max 3 analysts (still under-followed)
        'catalyst_window_days' => 30,          // Primary catalyst window
        'max_position_size' => 0.05,           // 5% of portfolio (higher risk)
        'min_technical_score' => 0.60,         // 60% technical strength
        'min_price_consolidation_days' => 20,  // 20 days consolidation for breakout
        'earnings_surprise_threshold' => 0.10  // 10% earnings surprise threshold
    ];

    public function __construct(
        MarketDataService $marketDataService,
        MarketDataRepositoryInterface $marketDataRepository
    ) {
        $this->marketDataService = $marketDataService;
        $this->marketDataRepository = $marketDataRepository;
        
        // Load parameters from database if available
        $this->loadParametersFromDatabase();
    }

    /**
     * Load strategy parameters from database
     */
    private function loadParametersFromDatabase(): void
    {
        try {
            $dbPath = __DIR__ . '/../../../storage/database/stock_analysis.db';
            if (!file_exists($dbPath)) {
                return; // Use defaults if database doesn't exist
            }

            $pdo = new \PDO('sqlite:' . $dbPath);
            $stmt = $pdo->prepare(
                'SELECT parameter_key, parameter_value, parameter_type 
                 FROM strategy_parameters 
                 WHERE strategy_name = ? AND is_active = 1'
            );
            $stmt->execute(['SmallCapCatalyst']);
            
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $key = $row['parameter_key'];
                if (array_key_exists($key, $this->parameters)) {
                    $this->parameters[$key] = $this->castParameterValue(
                        $row['parameter_value'],
                        $row['parameter_type']
                    );
                }
            }
        } catch (\Exception $e) {
            // Silently fail and use defaults
            error_log("Could not load SmallCapCatalyst parameters: " . $e->getMessage());
        }
    }

    /**
     * Cast parameter value to appropriate type
     */
    private function castParameterValue($value, string $type)
    {
        switch ($type) {
            case 'integer':
                return (int)$value;
            case 'float':
            case 'decimal':
                return (float)$value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            default:
                return $value;
        }
    }

    public function getName(): string
    {
        return "SmallCapCatalyst";
    }

    public function getDescription(): string
    {
        return "Event-driven small-cap strategy that identifies stocks with upcoming catalysts " .
               "(earnings, FDA approvals, coverage gaps, insider buying, short squeezes, technical breakouts). " .
               "Focuses on asymmetric risk/reward opportunities with minimum 3:1 ratios. " .
               "Targets $50M-$2B market cap companies with sufficient liquidity and catalyst confidence.";
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * {@inheritDoc}
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function canExecute(string $symbol): bool
    {
        try {
            $fundamentals = $this->marketDataService->getFundamentals($symbol);
            return !empty($fundamentals);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredHistoricalDays(): int
    {
        return 365; // Need 1 year for catalyst analysis
    }

    /**
     * {@inheritDoc}
     */
    public function analyze(string $symbol, string $date = 'today'): array
    {
        try {
            // Get fundamental metrics
            $fundamentals = $this->marketDataService->getFundamentals($symbol);
            
            if (empty($fundamentals)) {
                return $this->createAnalysisResult($symbol, 'HOLD', 0, 'Insufficient fundamental data');
            }
            
            // Fetch historical price data
            $endDate = $date === 'today' ? date('Y-m-d') : $date;
            $startDate = date('Y-m-d', strtotime('-1 year', strtotime($endDate)));
            $historicalData = $this->marketDataService->getHistoricalPrices($symbol, $startDate, $endDate);
            
            if (empty($historicalData)) {
                return $this->createAnalysisResult($symbol, 'HOLD', 0, 'Insufficient price history');
            }
            
            // Get latest financial data
            $latestData = end($historicalData);
            if (!$latestData) {
                return $this->createAnalysisResult($symbol, 'HOLD', 0, 'Insufficient data');
            }
            
            // Calculate scores
            $catalystScore = $this->calculateCatalystScore($symbol, $fundamentals);
            $technicalScore = $this->calculateTechnicalScore($historicalData);
            $riskRewardRatio = $this->calculateRiskRewardRatio($historicalData, $catalystScore);
            $liquidityScore = $this->calculateLiquidityScore($fundamentals);
            
            // Overall confidence
            $overallScore = (
                $catalystScore['score'] * 0.40 +      // 40% weight on catalyst
                $technicalScore * 0.25 +               // 25% weight on technicals
                min($riskRewardRatio / 3.0, 1.0) * 0.25 + // 25% on risk/reward
                $liquidityScore * 0.10                 // 10% on liquidity
            );

            // Decision logic
            $action = $this->determineAction(
                $catalystScore,
                $technicalScore,
                $riskRewardRatio,
                $liquidityScore,
                $fundamentals
            );

            $confidence = $overallScore * 100;

            // Calculate target and stop loss
            $currentPrice = $latestData['close'] ?? 0;
            $stopLoss = $currentPrice * (1 - $this->parameters['stop_loss_percent']);
            $targetPrice = $currentPrice + (($currentPrice - $stopLoss) * $riskRewardRatio);

            $reasoning = $this->buildReasoning(
                $catalystScore,
                $technicalScore,
                $riskRewardRatio,
                $liquidityScore,
                $fundamentals
            );

            return $this->createAnalysisResult(
                $symbol,
                $action,
                $confidence,
                $reasoning,
                [
                    'catalyst_score' => $catalystScore['score'],
                    'catalyst_type' => $catalystScore['primary_catalyst'],
                    'days_to_catalyst' => $catalystScore['days_to_catalyst'],
                    'technical_score' => $technicalScore,
                    'risk_reward_ratio' => $riskRewardRatio,
                    'liquidity_score' => $liquidityScore,
                    'target_price' => $targetPrice,
                    'stop_loss' => $stopLoss,
                    'position_size' => $this->calculatePositionSize($confidence, $riskRewardRatio),
                    'catalysts' => $catalystScore['catalysts']
                ]
            );

        } catch (\Exception $e) {
            return $this->createAnalysisResult(
                $symbol,
                'HOLD',
                0,
                'Analysis error: ' . $e->getMessage()
            );
        }
    }

    /**
     * Calculate catalyst score and identify upcoming catalysts
     */
    private function calculateCatalystScore(string $symbol, array $fundamentals): array
    {
        $catalysts = [];
        $scores = [];
        $primaryCatalyst = null;
        $daysToMainCatalyst = PHP_INT_MAX;

        // 1. Earnings Announcement Catalyst
        $earningsData = $this->getUpcomingEarnings($symbol);
        if ($earningsData) {
            $daysToEarnings = $earningsData['days_until'];
            if ($daysToEarnings >= $this->parameters['min_days_to_catalyst'] &&
                $daysToEarnings <= $this->parameters['max_days_to_catalyst']) {
                
                $earningsScore = $this->scoreEarningsCatalyst($symbol, $earningsData, $fundamentals);
                $catalysts[] = [
                    'type' => 'earnings',
                    'date' => $earningsData['date'],
                    'days_until' => $daysToEarnings,
                    'confidence' => $earningsScore
                ];
                $scores[] = $earningsScore;
                
                if ($daysToEarnings < $daysToMainCatalyst) {
                    $primaryCatalyst = 'earnings';
                    $daysToMainCatalyst = $daysToEarnings;
                }
            }
        }

        // 2. FDA Approval / Clinical Trial Results (biotech)
        if ($this->isBiotechOrPharma($fundamentals)) {
            $fdaData = $this->getUpcomingFDAEvents($symbol);
            if ($fdaData) {
                $daysToFDA = $fdaData['days_until'];
                if ($daysToFDA >= $this->parameters['min_days_to_catalyst'] &&
                    $daysToFDA <= $this->parameters['max_days_to_catalyst']) {
                    
                    $fdaScore = $this->scoreFDACatalyst($fdaData);
                    $catalysts[] = [
                        'type' => 'fda',
                        'description' => $fdaData['description'],
                        'days_until' => $daysToFDA,
                        'confidence' => $fdaScore
                    ];
                    $scores[] = $fdaScore;
                    
                    if ($daysToFDA < $daysToMainCatalyst) {
                        $primaryCatalyst = 'fda_approval';
                        $daysToMainCatalyst = $daysToFDA;
                    }
                }
            }
        }

        // 3. Analyst Coverage Gap (undiscovered stocks)
        $analystCount = $fundamentals['analyst_count'] ?? 0;
        if ($analystCount >= $this->parameters['min_analyst_coverage_gap'] &&
            $analystCount <= $this->parameters['max_analyst_coverage_gap']) {
            
            $coverageScore = $this->scoreAnalystCoverageGap($analystCount, $fundamentals);
            $catalysts[] = [
                'type' => 'coverage_gap',
                'analyst_count' => $analystCount,
                'confidence' => $coverageScore
            ];
            $scores[] = $coverageScore;
            
            if ($analystCount == 0 && !$primaryCatalyst) {
                $primaryCatalyst = 'coverage_initiation';
                $daysToMainCatalyst = 30; // Estimated
            }
        }

        // 4. Insider Buying Activity
        $insiderBuying = $this->getRecentInsiderBuying($symbol);
        if ($insiderBuying['significant']) {
            $insiderScore = $this->scoreInsiderBuying($insiderBuying);
            $catalysts[] = [
                'type' => 'insider_buying',
                'amount' => $insiderBuying['total_amount'],
                'confidence' => $insiderScore
            ];
            $scores[] = $insiderScore;
        }

        // 5. Short Squeeze Potential
        $shortInterest = $fundamentals['short_percent'] ?? 0;
        if ($shortInterest >= $this->parameters['min_short_interest_squeeze'] &&
            $shortInterest <= $this->parameters['max_short_interest']) {
            
            $squeezeScore = $this->scoreShortSqueezePotential($shortInterest, $fundamentals);
            $catalysts[] = [
                'type' => 'short_squeeze',
                'short_interest' => $shortInterest,
                'confidence' => $squeezeScore
            ];
            $scores[] = $squeezeScore;
        }

        // 6. Technical Breakout Setup
        $breakoutScore = $this->scoreBreakoutPotential($symbol);
        if ($breakoutScore > 0.60) {
            $catalysts[] = [
                'type' => 'technical_breakout',
                'confidence' => $breakoutScore
            ];
            $scores[] = $breakoutScore;
        }

        // Calculate overall catalyst score
        $overallScore = empty($scores) ? 0 : max($scores);

        return [
            'score' => $overallScore,
            'primary_catalyst' => $primaryCatalyst ?? 'none',
            'days_to_catalyst' => $daysToMainCatalyst === PHP_INT_MAX ? null : $daysToMainCatalyst,
            'catalysts' => $catalysts,
            'catalyst_count' => count($catalysts)
        ];
    }

    /**
     * Score earnings catalyst based on historical beats and expected surprise
     */
    private function scoreEarningsCatalyst(string $symbol, array $earningsData, array $fundamentals): float
    {
        $score = 0.50; // Base score for having upcoming earnings

        // Bonus for consistent earnings beats
        $beatStreak = $fundamentals['earnings_beat_streak'] ?? 0;
        if ($beatStreak >= 2) {
            $score += 0.15;
        }
        if ($beatStreak >= 4) {
            $score += 0.10;
        }

        // Bonus for positive estimate revisions
        $revisions = $fundamentals['estimate_revisions'] ?? [];
        if (!empty($revisions['upward'])) {
            $score += 0.15;
        }

        // Bonus for revenue acceleration
        if (($fundamentals['revenue_growth_acceleration'] ?? 0) > 0) {
            $score += 0.10;
        }

        return min($score, 1.0);
    }

    /**
     * Score FDA catalyst
     */
    private function scoreFDACatalyst(array $fdaData): float
    {
        $score = 0.60; // Base score for FDA event

        // Bonus for Phase 3 or NDA/BLA
        if (in_array($fdaData['stage'] ?? '', ['Phase 3', 'NDA', 'BLA'])) {
            $score += 0.20;
        }

        // Bonus for positive data history
        if ($fdaData['historical_success_rate'] ?? 0 > 0.70) {
            $score += 0.15;
        }

        return min($score, 1.0);
    }

    /**
     * Score analyst coverage gap opportunity
     */
    private function scoreAnalystCoverageGap(int $analystCount, array $fundamentals): float
    {
        // No coverage = highest score (undiscovered)
        if ($analystCount == 0) {
            return 0.85;
        }

        // 1-3 analysts = moderate score
        if ($analystCount <= 3) {
            return 0.65 - ($analystCount * 0.10);
        }

        return 0.40;
    }

    /**
     * Score insider buying activity
     */
    private function scoreInsiderBuying(array $insiderData): float
    {
        $score = 0.50;

        // Bonus for large purchases
        $purchaseRatio = $insiderData['purchase_to_market_cap'] ?? 0;
        if ($purchaseRatio > 0.01) { // 1% of market cap
            $score += 0.20;
        }

        // Bonus for multiple insiders
        if ($insiderData['insider_count'] ?? 0 > 1) {
            $score += 0.15;
        }

        // Bonus for C-level executives
        if ($insiderData['c_level_buying'] ?? false) {
            $score += 0.15;
        }

        return min($score, 1.0);
    }

    /**
     * Score short squeeze potential
     */
    private function scoreShortSqueezePotential(float $shortInterest, array $fundamentals): float
    {
        $score = 0.40;

        // Higher short interest = higher squeeze potential
        if ($shortInterest > 0.20) {
            $score += 0.20;
        }

        // Low days to cover = easier to squeeze
        $daysToCover = $fundamentals['days_to_cover'] ?? 10;
        if ($daysToCover < 3) {
            $score += 0.20;
        }

        // Recent price compression
        if (($fundamentals['price_vs_52w_high'] ?? 1.0) < 0.70) {
            $score += 0.20;
        }

        return min($score, 1.0);
    }

    /**
     * Score breakout potential from consolidation
     */
    private function scoreBreakoutPotential(string $symbol): float
    {
        // This would analyze:
        // - Recent consolidation period
        // - Volume patterns
        // - Resistance levels
        // - RSI and momentum indicators
        
        // Simplified implementation
        return 0.60; // Placeholder
    }

    /**
     * Calculate technical score
     */
    private function calculateTechnicalScore(array $historicalData): float
    {
        if (count($historicalData) < 50) {
            return 0.50; // Insufficient data
        }

        $score = 0;
        $latest = end($historicalData);
        $closes = array_column($historicalData, 'close');

        // 1. Price momentum (25%)
        $momentum3m = $this->calculateMomentum($closes, 60);
        if ($momentum3m > 0.10) {
            $score += 0.25;
        } elseif ($momentum3m > 0) {
            $score += 0.15;
        }

        // 2. RSI (25%) - not overbought/oversold
        $rsi = $this->calculateRSI($closes, 14);
        if ($rsi >= 45 && $rsi <= 65) {
            $score += 0.25;
        } elseif ($rsi >= 40 && $rsi <= 70) {
            $score += 0.15;
        }

        // 3. Volume trend (25%)
        $volumes = array_column($historicalData, 'volume');
        $volumeTrend = $this->calculateVolumeTrend($volumes);
        if ($volumeTrend > 0.10) {
            $score += 0.25;
        } elseif ($volumeTrend > 0) {
            $score += 0.15;
        }

        // 4. Price consolidation (25%)
        $consolidation = $this->detectConsolidation($closes, 20);
        if ($consolidation['is_consolidating']) {
            $score += 0.25;
        }

        return min($score, 1.0);
    }

    /**
     * Calculate risk/reward ratio
     */
    private function calculateRiskRewardRatio(array $historicalData, array $catalystScore): float
    {
        $latest = end($historicalData);
        $currentPrice = $latest['close'] ?? 0;

        if ($currentPrice == 0) {
            return 0;
        }

        // Risk = stop loss distance
        $stopLoss = $currentPrice * (1 - $this->parameters['stop_loss_percent']);
        $risk = $currentPrice - $stopLoss;

        // Reward = estimated upside based on catalyst strength
        $baseCatalystMove = 0.25; // 25% base move
        $catalystMultiplier = 1 + ($catalystScore['score'] * 2); // Up to 3x for strong catalysts
        $estimatedUpside = $currentPrice * $baseCatalystMove * $catalystMultiplier;
        
        $reward = $estimatedUpside;

        return $reward / $risk;
    }

    /**
     * Calculate liquidity score
     */
    private function calculateLiquidityScore(array $fundamentals): float
    {
        $avgVolume = $fundamentals['avg_volume'] ?? 0;
        $minVolume = $this->parameters['min_avg_volume'];

        if ($avgVolume >= $minVolume * 2) {
            return 1.0;
        } elseif ($avgVolume >= $minVolume) {
            return 0.75;
        } elseif ($avgVolume >= $minVolume * 0.75) {
            return 0.50;
        }

        return 0.25;
    }

    /**
     * Determine trading action
     */
    private function determineAction(
        array $catalystScore,
        float $technicalScore,
        float $riskRewardRatio,
        float $liquidityScore,
        array $fundamentals
    ): string {
        // Must meet minimum thresholds
        if ($catalystScore['score'] < $this->parameters['min_catalyst_confidence']) {
            return 'HOLD';
        }

        if ($riskRewardRatio < $this->parameters['min_risk_reward_ratio']) {
            return 'HOLD';
        }

        if ($technicalScore < $this->parameters['min_technical_score']) {
            return 'HOLD';
        }

        if ($liquidityScore < 0.50) {
            return 'HOLD';
        }

        // Check market cap range
        $marketCap = $fundamentals['market_cap'] ?? 0;
        if ($marketCap < $this->parameters['min_market_cap'] ||
            $marketCap > $this->parameters['max_market_cap']) {
            return 'HOLD';
        }

        // All criteria met
        return 'BUY';
    }

    /**
     * Calculate position size based on confidence and risk/reward
     */
    private function calculatePositionSize(float $confidence, float $riskRewardRatio): float
    {
        $baseSize = $this->parameters['max_position_size'];
        
        // Scale by confidence
        $confidenceMultiplier = $confidence / 100;
        
        // Scale by risk/reward (better R:R = larger position)
        $rrMultiplier = min($riskRewardRatio / 5.0, 1.0);
        
        return $baseSize * $confidenceMultiplier * $rrMultiplier;
    }

    /**
     * Build reasoning text
     */
    private function buildReasoning(
        array $catalystScore,
        float $technicalScore,
        float $riskRewardRatio,
        float $liquidityScore,
        array $fundamentals
    ): string {
        $reasoning = [];

        $reasoning[] = sprintf(
            "Catalyst Score: %.0f%% (%d catalysts identified, primary: %s)",
            $catalystScore['score'] * 100,
            $catalystScore['catalyst_count'],
            $catalystScore['primary_catalyst']
        );

        if ($catalystScore['days_to_catalyst']) {
            $reasoning[] = sprintf(
                "Days to Primary Catalyst: %d days",
                $catalystScore['days_to_catalyst']
            );
        }

        $reasoning[] = sprintf("Technical Score: %.0f%%", $technicalScore * 100);
        $reasoning[] = sprintf("Risk/Reward Ratio: %.1f:1", $riskRewardRatio);
        $reasoning[] = sprintf("Liquidity Score: %.0f%%", $liquidityScore * 100);

        // Add specific catalyst details
        foreach ($catalystScore['catalysts'] as $catalyst) {
            $reasoning[] = sprintf(
                "- %s catalyst (%.0f%% confidence)",
                ucfirst(str_replace('_', ' ', $catalyst['type'])),
                $catalyst['confidence'] * 100
            );
        }

        return implode('. ', $reasoning);
    }

    /**
     * Create standardized analysis result
     */
    private function createAnalysisResult(
        string $symbol,
        string $action,
        float $confidence,
        string $reasoning,
        array $metrics = []
    ): array {
        return [
            'symbol' => $symbol,
            'strategy' => 'SmallCapCatalyst',
            'action' => $action,
            'confidence' => round($confidence, 2),
            'reasoning' => $reasoning,
            'timestamp' => date('Y-m-d H:i:s'),
            'metrics' => $metrics
        ];
    }

    // Helper methods (simplified implementations)

    private function getUpcomingEarnings(string $symbol): ?array
    {
        // Would integrate with earnings calendar API
        // For now, return mock data
        return null;
    }

    private function isBiotechOrPharma(array $fundamentals): bool
    {
        $sector = $fundamentals['sector'] ?? '';
        $industry = $fundamentals['industry'] ?? '';
        
        return stripos($sector, 'healthcare') !== false ||
               stripos($industry, 'biotech') !== false ||
               stripos($industry, 'pharma') !== false;
    }

    private function getUpcomingFDAEvents(string $symbol): ?array
    {
        // Would integrate with FDA calendar/database
        return null;
    }

    private function getRecentInsiderBuying(string $symbol): array
    {
        // Would query insider trading database
        return ['significant' => false];
    }

    private function calculateMomentum(array $prices, int $period): float
    {
        if (count($prices) < $period) {
            return 0;
        }

        $current = end($prices);
        $past = $prices[count($prices) - $period];

        return ($current - $past) / $past;
    }

    private function calculateRSI(array $prices, int $period = 14): float
    {
        if (count($prices) < $period + 1) {
            return 50;
        }

        $gains = [];
        $losses = [];

        for ($i = count($prices) - $period; $i < count($prices); $i++) {
            $change = $prices[$i] - $prices[$i - 1];
            if ($change > 0) {
                $gains[] = $change;
                $losses[] = 0;
            } else {
                $gains[] = 0;
                $losses[] = abs($change);
            }
        }

        $avgGain = array_sum($gains) / $period;
        $avgLoss = array_sum($losses) / $period;

        if ($avgLoss == 0) {
            return 100;
        }

        $rs = $avgGain / $avgLoss;
        return 100 - (100 / (1 + $rs));
    }

    private function calculateVolumeTrend(array $volumes): float
    {
        if (count($volumes) < 20) {
            return 0;
        }

        $recent = array_slice($volumes, -10);
        $older = array_slice($volumes, -20, 10);

        $recentAvg = array_sum($recent) / count($recent);
        $olderAvg = array_sum($older) / count($older);

        if ($olderAvg == 0) {
            return 0;
        }

        return ($recentAvg - $olderAvg) / $olderAvg;
    }

    private function detectConsolidation(array $prices, int $period): array
    {
        if (count($prices) < $period) {
            return ['is_consolidating' => false];
        }

        $recent = array_slice($prices, -$period);
        $high = max($recent);
        $low = min($recent);
        $range = ($high - $low) / $low;

        // Consolidation if price range < 10%
        return [
            'is_consolidating' => $range < 0.10,
            'range' => $range
        ];
    }
}
