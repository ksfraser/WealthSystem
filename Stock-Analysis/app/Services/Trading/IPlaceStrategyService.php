<?php

namespace App\Services\Trading;

use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

/**
 * IPlace Strategy Service
 * 
 * Momentum strategy focused on analyst rating changes (upgrades/downgrades).
 * Capitalizes on price momentum following positive analyst sentiment shifts.
 * 
 * Key Signals:
 * 1. Recent Analyst Upgrades (within momentum window)
 * 2. Upgrade Clusters (multiple upgrades in short timeframe)
 * 3. High-Reputation Analyst Upgrades
 * 4. Target Price Increases
 * 5. Post-Upgrade Price Momentum
 * 6. Volume Confirmation on Upgrade Days
 * 
 * Risk Management:
 * - Requires minimum analyst coverage for reliability
 * - Weighs analyst reputation heavily
 * - Monitors upgrade/downgrade ratio
 * - Validates price reaction confirms upgrade thesis
 * 
 * @package App\Services\Trading
 */
class IPlaceStrategyService implements TradingStrategyInterface
{
    private MarketDataService $marketDataService;
    private MarketDataRepositoryInterface $marketDataRepository;
    
    private array $parameters = [
        'upgrade_momentum_window' => 30,        // Days to look back for upgrades
        'min_analyst_coverage' => 3,            // Minimum analysts covering stock
        'min_upgrades_for_buy' => 2,            // Minimum upgrades to trigger buy
        'upgrade_weight' => 1.0,                // Weight for upgrades
        'downgrade_penalty' => 1.5,             // Penalty multiplier for downgrades
        'high_reputation_threshold' => 0.80,    // Reputation score threshold
        'volume_spike_threshold' => 1.5,        // Volume multiplier for confirmation
        'upgrade_cluster_days' => 14,           // Days to identify upgrade cluster
        'min_cluster_size' => 3,                // Min upgrades for cluster
        'post_upgrade_momentum_days' => 10,     // Days to measure post-upgrade momentum
        'target_price_weight' => 0.30,          // Weight for target price increases
        'consensus_change_threshold' => 0.50    // Threshold for significant consensus shift
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
                return;
            }

            $pdo = new \PDO('sqlite:' . $dbPath);
            $stmt = $pdo->prepare(
                'SELECT parameter_key, parameter_value, parameter_type 
                 FROM strategy_parameters 
                 WHERE strategy_name = ? AND is_active = 1'
            );
            $stmt->execute(['IPlace']);
            
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
            error_log("Could not load IPlace parameters: " . $e->getMessage());
        }
    }

    /**
     * Cast parameter value to appropriate type
     */
    private function castParameterValue($value, string $type)
    {
        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
            case 'double':
            case 'decimal':
                return (float) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            default:
                return $value;
        }
    }

    public function getName(): string
    {
        return "IPlace";
    }

    public function getDescription(): string
    {
        return "Analyst-driven momentum strategy that identifies stocks with recent upgrades, " .
               "upgrade clusters, and strong post-upgrade price momentum. " .
               "Weighs analyst reputation and validates price reaction to rating changes.";
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }

    public function canExecute(string $symbol): bool
    {
        try {
            $fundamentals = $this->marketDataService->getFundamentals($symbol);
            return !empty($fundamentals);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getRequiredHistoricalDays(): int
    {
        return 90; // Need 90 days for momentum analysis
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
            $startDate = date('Y-m-d', strtotime('-90 days', strtotime($endDate)));
            $historicalData = $this->marketDataService->getHistoricalPrices($symbol, $startDate, $endDate);
            
            if (empty($historicalData)) {
                return $this->createAnalysisResult($symbol, 'HOLD', 0, 'Insufficient price history');
            }
            
            // Get analyst ratings
            $analystRatings = $this->getAnalystRatings($symbol);
            
            if (empty($analystRatings)) {
                return $this->createAnalysisResult($symbol, 'HOLD', 0, 'No analyst coverage data available');
            }
            
            // Calculate scores
            $upgradeMetrics = $this->calculateUpgradeMetrics($analystRatings, $historicalData);
            $momentumScore = $this->calculateUpgradeMomentum($historicalData, $analystRatings);
            $analystQualityScore = $this->calculateAnalystQualityScore($analystRatings);
            $volumeConfirmation = $this->calculateVolumeConfirmation($historicalData, $analystRatings);
            
            // Overall confidence
            $overallScore = (
                $upgradeMetrics['score'] * 0.35 +          // 35% weight on upgrade activity
                $momentumScore * 0.30 +                     // 30% weight on price momentum
                $analystQualityScore * 0.20 +               // 20% weight on analyst quality
                $volumeConfirmation * 0.15                  // 15% weight on volume
            );

            // Decision logic
            $action = $this->determineAction($upgradeMetrics, $momentumScore, $analystQualityScore);
            $confidence = $overallScore * 100;

            $reasoning = $this->buildReasoning(
                $upgradeMetrics,
                $momentumScore,
                $analystQualityScore,
                $volumeConfirmation
            );

            return $this->createAnalysisResult(
                $symbol,
                $action,
                $confidence,
                $reasoning,
                array_merge($upgradeMetrics, [
                    'upgrade_momentum' => $momentumScore,
                    'analyst_quality_score' => $analystQualityScore,
                    'volume_confirmation' => $volumeConfirmation,
                    'price_reaction_score' => $upgradeMetrics['price_reaction_score'] ?? 0.0
                ])
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
     * Get analyst ratings with fallback to mock method
     */
    private function getAnalystRatings(string $symbol): array
    {
        // Try to get real data from service
        if (method_exists($this->marketDataService, 'getAnalystRatings')) {
            try {
                return $this->marketDataService->getAnalystRatings($symbol);
            } catch (\Exception $e) {
                // Fall through to empty array
            }
        }
        
        return [];
    }

    /**
     * Calculate upgrade-related metrics
     */
    private function calculateUpgradeMetrics(array $analystRatings, array $historicalData): array
    {
        $windowDays = $this->parameters['upgrade_momentum_window'];
        $cutoffDate = date('Y-m-d', strtotime("-{$windowDays} days"));
        
        $recentUpgrades = 0;
        $recentDowngrades = 0;
        $targetPriceIncreases = 0;
        $lastUpgradeDate = null;
        $upgradeCluster = false;
        $clusterUpgrades = [];
        
        foreach ($analystRatings as $rating) {
            $ratingDate = $rating['date'] ?? null;
            $action = strtolower($rating['action'] ?? '');
            
            if (!$ratingDate || $ratingDate < $cutoffDate) {
                continue;
            }
            
            if ($action === 'upgrade') {
                $recentUpgrades++;
                $lastUpgradeDate = $ratingDate;
                $clusterUpgrades[] = $ratingDate;
            } elseif ($action === 'downgrade') {
                $recentDowngrades++;
            }
            
            // Check for target price increases
            if (isset($rating['target_price']) && isset($rating['old_target_price'])) {
                if ($rating['target_price'] > $rating['old_target_price']) {
                    $targetPriceIncreases++;
                }
            }
        }
        
        // Detect upgrade cluster
        if (count($clusterUpgrades) >= $this->parameters['min_cluster_size']) {
            $clusterDays = $this->parameters['upgrade_cluster_days'];
            $clusterCutoff = date('Y-m-d', strtotime("-{$clusterDays} days"));
            $recentClusterUpgrades = array_filter($clusterUpgrades, fn($d) => $d >= $clusterCutoff);
            $upgradeCluster = count($recentClusterUpgrades) >= $this->parameters['min_cluster_size'];
        }
        
        // Calculate upgrade score
        $upgradeScore = ($recentUpgrades * $this->parameters['upgrade_weight']) - 
                        ($recentDowngrades * $this->parameters['downgrade_penalty']);
        $upgradeScore = max(0, $upgradeScore) / 10.0; // Normalize to 0-1
        $upgradeScore = min(1.0, $upgradeScore);
        
        // Calculate upgrade/downgrade ratio
        $ratio = $recentDowngrades > 0 ? $recentUpgrades / $recentDowngrades : $recentUpgrades;
        
        // Calculate days since last upgrade
        $daysSinceLastUpgrade = null;
        if ($lastUpgradeDate) {
            $daysSinceLastUpgrade = (strtotime(date('Y-m-d')) - strtotime($lastUpgradeDate)) / 86400;
        }
        
        // Calculate consensus change
        $consensusChange = ($recentUpgrades - $recentDowngrades) / max(1, count($analystRatings));
        
        // Calculate post-upgrade performance
        $postUpgradePerformance = $this->calculatePostUpgradePerformance($historicalData, $analystRatings);
        
        // Calculate price reaction score
        $priceReactionScore = $this->calculatePriceReactionScore($historicalData, $analystRatings);
        
        // Calculate target price momentum
        $targetPriceMomentum = $targetPriceIncreases / max(1, $recentUpgrades);
        
        // Analyst coverage count
        $coverageCount = count(array_unique(array_column($analystRatings, 'analyst_firm')));
        
        return [
            'score' => $upgradeScore,
            'recent_upgrades' => $recentUpgrades,
            'recent_downgrades' => $recentDowngrades,
            'upgrade_downgrade_ratio' => $ratio,
            'days_since_last_upgrade' => $daysSinceLastUpgrade,
            'consensus_change' => $consensusChange,
            'upgrade_cluster_detected' => $upgradeCluster,
            'target_price_momentum' => $targetPriceMomentum,
            'post_upgrade_performance' => $postUpgradePerformance,
            'price_reaction_score' => $priceReactionScore,
            'analyst_coverage_count' => $coverageCount
        ];
    }

    /**
     * Calculate upgrade momentum (price momentum following upgrades)
     */
    private function calculateUpgradeMomentum(array $historicalData, array $analystRatings): float
    {
        if (empty($historicalData) || empty($analystRatings)) {
            return 0.0;
        }
        
        $windowDays = $this->parameters['upgrade_momentum_window'];
        $cutoffDate = date('Y-m-d', strtotime("-{$windowDays} days"));
        
        $recentUpgrades = array_filter($analystRatings, function($rating) use ($cutoffDate) {
            return ($rating['date'] ?? '') >= $cutoffDate && 
                   strtolower($rating['action'] ?? '') === 'upgrade';
        });
        
        if (empty($recentUpgrades)) {
            return 0.0;
        }
        
        // Find most recent upgrade date
        $latestUpgrade = max(array_column($recentUpgrades, 'date'));
        
        // Calculate price momentum since upgrade
        $pricesAfterUpgrade = array_filter($historicalData, fn($d) => $d['date'] >= $latestUpgrade);
        
        if (count($pricesAfterUpgrade) < 2) {
            return 0.0;
        }
        
        $pricesAfterUpgrade = array_values($pricesAfterUpgrade);
        $startPrice = $pricesAfterUpgrade[0]['close'];
        $endPrice = end($pricesAfterUpgrade)['close'];
        
        $momentum = ($endPrice - $startPrice) / $startPrice;
        
        // Normalize to 0-1 scale (assume 20% gain = 1.0)
        return max(0, min(1.0, $momentum / 0.20));
    }

    /**
     * Calculate analyst quality score based on reputation
     */
    private function calculateAnalystQualityScore(array $analystRatings): float
    {
        if (empty($analystRatings)) {
            return 0.0;
        }
        
        $windowDays = $this->parameters['upgrade_momentum_window'];
        $cutoffDate = date('Y-m-d', strtotime("-{$windowDays} days"));
        
        $recentRatings = array_filter($analystRatings, function($rating) use ($cutoffDate) {
            return ($rating['date'] ?? '') >= $cutoffDate;
        });
        
        if (empty($recentRatings)) {
            return 0.0;
        }
        
        $totalReputation = 0;
        $count = 0;
        
        foreach ($recentRatings as $rating) {
            if (isset($rating['reputation_score'])) {
                $totalReputation += $rating['reputation_score'];
                $count++;
            }
        }
        
        return $count > 0 ? $totalReputation / $count : 0.5;
    }

    /**
     * Calculate volume confirmation on upgrade days
     */
    private function calculateVolumeConfirmation(array $historicalData, array $analystRatings): float
    {
        if (empty($historicalData) || empty($analystRatings)) {
            return 0.0;
        }
        
        $windowDays = $this->parameters['upgrade_momentum_window'];
        $cutoffDate = date('Y-m-d', strtotime("-{$windowDays} days"));
        
        $recentUpgrades = array_filter($analystRatings, function($rating) use ($cutoffDate) {
            return ($rating['date'] ?? '') >= $cutoffDate && 
                   strtolower($rating['action'] ?? '') === 'upgrade';
        });
        
        if (empty($recentUpgrades)) {
            return 0.0;
        }
        
        // Calculate average volume
        $volumes = array_column($historicalData, 'volume');
        $avgVolume = array_sum($volumes) / count($volumes);
        
        // Check volume on upgrade dates
        $volumeSpikes = 0;
        $upgradeDates = array_column($recentUpgrades, 'date');
        
        foreach ($historicalData as $day) {
            if (in_array($day['date'], $upgradeDates)) {
                if ($day['volume'] >= $avgVolume * $this->parameters['volume_spike_threshold']) {
                    $volumeSpikes++;
                }
            }
        }
        
        return count($upgradeDates) > 0 ? $volumeSpikes / count($upgradeDates) : 0.0;
    }

    /**
     * Calculate post-upgrade performance
     */
    private function calculatePostUpgradePerformance(array $historicalData, array $analystRatings): float
    {
        if (empty($historicalData) || empty($analystRatings)) {
            return 0.0;
        }
        
        $windowDays = $this->parameters['post_upgrade_momentum_days'];
        
        $recentUpgrades = array_filter($analystRatings, function($rating) {
            return strtolower($rating['action'] ?? '') === 'upgrade';
        });
        
        if (empty($recentUpgrades)) {
            return 0.0;
        }
        
        $totalPerformance = 0;
        $count = 0;
        
        foreach ($recentUpgrades as $upgrade) {
            $upgradeDate = $upgrade['date'];
            
            // Find price on upgrade date and N days later
            $upgradeDayPrice = null;
            $laterPrice = null;
            
            foreach ($historicalData as $i => $day) {
                if ($day['date'] === $upgradeDate) {
                    $upgradeDayPrice = $day['close'];
                    
                    // Look ahead for later price
                    $targetIndex = $i + $windowDays;
                    if (isset($historicalData[$targetIndex])) {
                        $laterPrice = $historicalData[$targetIndex]['close'];
                    }
                    break;
                }
            }
            
            if ($upgradeDayPrice && $laterPrice) {
                $performance = ($laterPrice - $upgradeDayPrice) / $upgradeDayPrice;
                $totalPerformance += $performance;
                $count++;
            }
        }
        
        return $count > 0 ? $totalPerformance / $count : 0.0;
    }

    /**
     * Calculate price reaction score to upgrades
     */
    private function calculatePriceReactionScore(array $historicalData, array $analystRatings): float
    {
        if (empty($historicalData) || empty($analystRatings)) {
            return 0.0;
        }
        
        $windowDays = $this->parameters['upgrade_momentum_window'];
        $cutoffDate = date('Y-m-d', strtotime("-{$windowDays} days"));
        
        $recentUpgrades = array_filter($analystRatings, function($rating) use ($cutoffDate) {
            return ($rating['date'] ?? '') >= $cutoffDate && 
                   strtolower($rating['action'] ?? '') === 'upgrade';
        });
        
        if (empty($recentUpgrades)) {
            return 0.0;
        }
        
        $positiveReactions = 0;
        $totalChecked = 0;
        
        foreach ($recentUpgrades as $upgrade) {
            $upgradeDate = $upgrade['date'];
            
            foreach ($historicalData as $i => $day) {
                if ($day['date'] === $upgradeDate && isset($historicalData[$i + 1])) {
                    $priceChange = ($historicalData[$i + 1]['close'] - $day['close']) / $day['close'];
                    if ($priceChange > 0) {
                        $positiveReactions++;
                    }
                    $totalChecked++;
                    break;
                }
            }
        }
        
        return $totalChecked > 0 ? $positiveReactions / $totalChecked : 0.0;
    }

    /**
     * Determine trading action
     */
    private function determineAction(array $upgradeMetrics, float $momentumScore, float $analystQualityScore): string
    {
        $minUpgrades = $this->parameters['min_upgrades_for_buy'];
        $minCoverage = $this->parameters['min_analyst_coverage'];
        
        // Insufficient coverage
        if ($upgradeMetrics['analyst_coverage_count'] < $minCoverage) {
            return 'HOLD';
        }
        
        // Recent downgrades
        if ($upgradeMetrics['recent_downgrades'] > $upgradeMetrics['recent_upgrades']) {
            return 'HOLD';
        }
        
        // Not enough upgrades
        if ($upgradeMetrics['recent_upgrades'] < $minUpgrades) {
            return 'HOLD';
        }
        
        // Strong signal: multiple upgrades + momentum + quality
        if ($upgradeMetrics['recent_upgrades'] >= $minUpgrades &&
            $momentumScore > 0.60 &&
            $analystQualityScore > $this->parameters['high_reputation_threshold']) {
            return 'BUY';
        }
        
        // Good signal: upgrade cluster or strong consensus
        if ($upgradeMetrics['upgrade_cluster_detected'] || 
            $upgradeMetrics['consensus_change'] > $this->parameters['consensus_change_threshold']) {
            return 'BUY';
        }
        
        // Moderate signal
        if ($upgradeMetrics['score'] > 0.60 && $momentumScore > 0.50) {
            return 'BUY';
        }
        
        return 'HOLD';
    }

    /**
     * Build reasoning string
     */
    private function buildReasoning(
        array $upgradeMetrics,
        float $momentumScore,
        float $analystQualityScore,
        float $volumeConfirmation
    ): string {
        $parts = [];
        
        // Upgrade activity
        if ($upgradeMetrics['recent_upgrades'] > 0) {
            $parts[] = "{$upgradeMetrics['recent_upgrades']} analyst upgrade(s) in past {$this->parameters['upgrade_momentum_window']} days";
        }
        
        // Upgrade cluster
        if ($upgradeMetrics['upgrade_cluster_detected']) {
            $parts[] = "Upgrade cluster detected";
        }
        
        // Momentum
        $momentumPct = round($momentumScore * 100);
        $parts[] = "Upgrade momentum: {$momentumPct}%";
        
        // Analyst quality
        $qualityPct = round($analystQualityScore * 100);
        $parts[] = "Analyst quality: {$qualityPct}%";
        
        // Volume confirmation
        if ($volumeConfirmation > 0.50) {
            $parts[] = "Volume confirmation on upgrade days";
        }
        
        // Downgrades
        if ($upgradeMetrics['recent_downgrades'] > 0) {
            $parts[] = "{$upgradeMetrics['recent_downgrades']} downgrade(s) noted";
        }
        
        // Coverage
        $parts[] = "{$upgradeMetrics['analyst_coverage_count']} analyst(s) covering";
        
        return implode('. ', $parts) . '.';
    }

    /**
     * Create analysis result
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
            'action' => $action,
            'confidence' => round($confidence, 2),
            'reasoning' => $reasoning,
            'metrics' => $metrics
        ];
    }
}
