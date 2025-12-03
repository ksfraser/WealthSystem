<?php

namespace App\Services\Trading;

use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

/**
 * Mean Reversion Strategy Service
 * 
 * Identifies oversold conditions using Bollinger Bands and RSI, targeting mean reversion opportunities.
 * 
 * Key Technical Indicators:
 * - Bollinger Bands (20-day SMA, 2 standard deviations)
 * - RSI (Relative Strength Index) for oversold detection (< 30)
 * - Volume confirmation (1.5x average)
 * - RSI divergence patterns (bullish/bearish)
 * - Support level bounce detection
 * 
 * Strategy Logic:
 * - BUY when price is below lower Bollinger Band with RSI < 30 and volume confirmation
 * - Target reversion to middle band (mean)
 * - Higher confidence when bullish divergence present
 * - Requires minimum volatility threshold to ensure tradeable movement
 * 
 * Risk Management:
 * - Minimum volatility requirement prevents low-movement trades
 * - Volume confirmation reduces false signals
 * - Multiple indicator confluence increases probability
 * 
 * @package App\Services\Trading
 */
class MeanReversionStrategyService implements TradingStrategyInterface
{
    /**
     * @var MarketDataService Market data service for fetching fundamentals and prices
     */
    private MarketDataService $marketDataService;
    
    /**
     * @var MarketDataRepositoryInterface Repository for market data persistence
     */
    private MarketDataRepositoryInterface $marketDataRepository;
    
    /**
     * @var array Strategy parameters with default values
     */
    private array $parameters = [
        'bb_period' => 20,
        'bb_std_dev' => 2.0,
        'rsi_period' => 14,
        'rsi_oversold' => 30,
        'rsi_overbought' => 70,
        'volume_threshold' => 1.5,
        'min_volatility' => 0.01,
        'lookback_period' => 20,
        'mean_reversion_score_threshold' => 0.60,
        'min_band_touches' => 2,
        'support_bounce_threshold' => 0.03,
        'divergence_lookback' => 10
    ];

    /**
     * Constructor
     * 
     * Initializes the mean reversion strategy with required services and loads
     * parameters from database if available.
     * 
     * @param MarketDataService $marketDataService Service for market data retrieval
     * @param MarketDataRepositoryInterface $marketDataRepository Repository for data persistence
     */
    public function __construct(
        MarketDataService $marketDataService,
        MarketDataRepositoryInterface $marketDataRepository
    ) {
        $this->marketDataService = $marketDataService;
        $this->marketDataRepository = $marketDataRepository;
        $this->loadParametersFromDatabase();
    }

    /**
     * Load strategy parameters from database
     * 
     * Attempts to load custom parameters from SQLite database. Falls back to
     * default parameters if database doesn't exist or query fails.
     * 
     * @return void
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
            $stmt->execute(['MeanReversion']);
            
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $key = $row['parameter_key'];
                $value = $row['parameter_value'];
                
                // Cast to appropriate type
                if ($row['parameter_type'] === 'int') {
                    $value = (int)$value;
                } elseif ($row['parameter_type'] === 'float') {
                    $value = (float)$value;
                } elseif ($row['parameter_type'] === 'bool') {
                    $value = (bool)$value;
                }
                
                $this->parameters[$key] = $value;
            }
        } catch (\Exception $e) {
            // Silently fall back to defaults if database access fails
        }
    }

    /**
     * Get strategy name
     * 
     * @return string Strategy identifier
     */
    public function getName(): string
    {
        return 'MeanReversion';
    }

    /**
     * Get strategy description
     * 
     * @return string Human-readable description of strategy logic
     */
    public function getDescription(): string
    {
        return 'Identifies oversold conditions using Bollinger Bands and RSI, looking for mean reversion opportunities with volume confirmation and bullish divergence patterns.';
    }

    /**
     * Analyze symbol for mean reversion trading opportunities
     * 
     * Performs comprehensive technical analysis using Bollinger Bands, RSI, volume,
     * divergence detection, and support levels to identify oversold conditions with
     * high probability of mean reversion.
     * 
     * @param string $symbol Stock ticker symbol to analyze
     * @param string $date Analysis date (default: 'today')
     * @return array Analysis result with action (BUY/SELL/HOLD), confidence (0-100),
     *               reasoning (string explanation), and metrics (technical indicators)
     */
    public function analyze(string $symbol, string $date = 'today'): array
    {
        try {
            $fundamentals = $this->marketDataService->getFundamentals($symbol);
            $historicalData = $this->marketDataService->getHistoricalPrices($symbol, 100);

            if (empty($fundamentals) || count($historicalData) < 60) {
                return [
                    'action' => 'HOLD',
                    'confidence' => 0,
                    'reasoning' => 'Insufficient historical data for mean reversion analysis',
                    'metrics' => []
                ];
            }

            // Calculate technical indicators
            $bollingerBands = $this->calculateBollingerBands($historicalData);
            $rsi = $this->calculateRSI($historicalData);
            $divergence = $this->detectRSIDivergence($historicalData, $rsi);
            $volumeConfirmation = $this->calculateVolumeConfirmation($historicalData);
            $volatility = $this->calculateVolatility($historicalData);
            $supportBounce = $this->detectSupportBounce($historicalData);
            $bandTouches = $this->countBandTouches($historicalData, $bollingerBands);
            $distanceFromMean = $this->calculateDistanceFromMean($historicalData, $bollingerBands);
            $trendContext = $this->determineTrendContext($historicalData);

            // Calculate mean reversion score
            $meanReversionScore = $this->calculateMeanReversionScore([
                'bb_position' => $bollingerBands['position'],
                'rsi' => $rsi,
                'divergence' => $divergence,
                'volume_confirmation' => $volumeConfirmation,
                'volatility' => $volatility,
                'support_bounce' => $supportBounce
            ]);

            $metrics = [
                'bb_position' => $bollingerBands['position'],
                'lower_band' => $bollingerBands['lower'],
                'middle_band' => $bollingerBands['middle'],
                'upper_band' => $bollingerBands['upper'],
                'rsi' => $rsi,
                'rsi_divergence' => $divergence,
                'oversold_signal' => $rsi < $this->parameters['rsi_oversold'] && $bollingerBands['position'] < 0,
                'volume_confirmation' => $volumeConfirmation,
                'mean_reversion_score' => $meanReversionScore,
                'support_bounce' => $supportBounce,
                'volatility' => $volatility,
                'band_touches' => $bandTouches,
                'distance_from_mean' => $distanceFromMean,
                'trend' => $trendContext,
                'trend_direction' => $trendContext
            ];

            // Determine action
            $result = $this->determineAction($metrics, $fundamentals);
            $result['metrics'] = $metrics;

            return $result;

        } catch (\Exception $e) {
            return [
                'action' => 'HOLD',
                'confidence' => 0,
                'reasoning' => 'Error in mean reversion analysis: ' . $e->getMessage(),
                'metrics' => []
            ];
        }
    }

    /**
     * Calculate Bollinger Bands
     * 
     * Computes upper, middle (SMA), and lower bands based on standard deviation.
     * Also calculates current price position relative to bands (-1 to +1).
     * 
     * @param array $historicalData Historical price data with 'close' values
     * @return array Array containing upper, middle, lower bands, position, and current_price
     */
    private function calculateBollingerBands(array $historicalData): array
    {
        $period = (int)$this->parameters['bb_period'];
        $stdDev = (float)$this->parameters['bb_std_dev'];
        
        $prices = array_column($historicalData, 'close');
        $recentPrices = array_slice($prices, -$period);
        
        $sma = array_sum($recentPrices) / count($recentPrices);
        
        // Calculate standard deviation
        $variance = 0;
        foreach ($recentPrices as $price) {
            $variance += pow($price - $sma, 2);
        }
        $standardDeviation = sqrt($variance / count($recentPrices));
        
        $upperBand = $sma + ($stdDev * $standardDeviation);
        $lowerBand = $sma - ($stdDev * $standardDeviation);
        
        $currentPrice = end($prices);
        
        // Calculate position relative to bands (-1 = lower band, 0 = middle, +1 = upper band)
        $bandWidth = $upperBand - $lowerBand;
        $position = $bandWidth > 0 ? (($currentPrice - $lowerBand) / $bandWidth * 2) - 1 : 0;
        
        return [
            'upper' => $upperBand,
            'middle' => $sma,
            'lower' => $lowerBand,
            'position' => $position,
            'current_price' => $currentPrice
        ];
    }

    /**
     * Calculate Relative Strength Index (RSI)
     * 
     * Computes RSI using average gains and losses over specified period.
     * Returns value between 0-100, where < 30 indicates oversold conditions.
     * 
     * @param array $historicalData Historical price data with 'close' values
     * @return float RSI value (0-100)
     */
    private function calculateRSI(array $historicalData): float
    {
        $period = (int)$this->parameters['rsi_period'];
        $prices = array_column($historicalData, 'close');
        
        if (count($prices) < $period + 1) {
            return 50.0;
        }
        
        $gains = [];
        $losses = [];
        
        for ($i = count($prices) - $period - 1; $i < count($prices); $i++) {
            $change = $prices[$i] - $prices[$i - 1];
            $gains[] = max($change, 0);
            $losses[] = max(-$change, 0);
        }
        
        $avgGain = array_sum($gains) / count($gains);
        $avgLoss = array_sum($losses) / count($losses);
        
        if ($avgLoss == 0) {
            return 100.0;
        }
        
        $rs = $avgGain / $avgLoss;
        $rsi = 100 - (100 / (1 + $rs));
        
        return round($rsi, 2);
    }

    /**
     * Detect RSI divergence patterns
     * 
     * Identifies bullish divergence (price lower lows, RSI higher lows) or
     * bearish divergence (price higher highs, RSI lower highs).
     * 
     * @param array $historicalData Historical price data
     * @param float $currentRSI Current RSI value
     * @return string Divergence type: 'bullish', 'bearish', or 'none'
     */
    private function detectRSIDivergence(array $historicalData, float $currentRSI): string
    {
        $lookback = (int)$this->parameters['divergence_lookback'];
        $prices = array_column($historicalData, 'close');
        $recentPrices = array_slice($prices, -$lookback);
        
        if (count($recentPrices) < 5) {
            return 'none';
        }
        
        // Calculate RSI for each point in lookback period
        $rsiValues = [];
        for ($i = 0; $i < count($recentPrices); $i++) {
            $subData = array_slice($historicalData, -(count($recentPrices) - $i));
            $rsiValues[] = $this->calculateRSI($subData);
        }
        
        // Find price lows and RSI lows
        $priceLows = [];
        $rsiLows = [];
        
        for ($i = 1; $i < count($recentPrices) - 1; $i++) {
            if ($recentPrices[$i] < $recentPrices[$i - 1] && $recentPrices[$i] < $recentPrices[$i + 1]) {
                $priceLows[] = ['index' => $i, 'value' => $recentPrices[$i]];
            }
            if ($rsiValues[$i] < $rsiValues[$i - 1] && $rsiValues[$i] < $rsiValues[$i + 1]) {
                $rsiLows[] = ['index' => $i, 'value' => $rsiValues[$i]];
            }
        }
        
        // Check for bullish divergence (price lower lows, RSI higher lows)
        if (count($priceLows) >= 2 && count($rsiLows) >= 2) {
            $lastPriceLow = end($priceLows);
            $prevPriceLow = prev($priceLows);
            $lastRSILow = end($rsiLows);
            $prevRSILow = prev($rsiLows);
            
            if ($lastPriceLow['value'] < $prevPriceLow['value'] && 
                $lastRSILow['value'] > $prevRSILow['value']) {
                return 'bullish';
            }
            
            // Check for bearish divergence (price higher highs, RSI lower highs)
            if ($lastPriceLow['value'] > $prevPriceLow['value'] && 
                $lastRSILow['value'] < $prevRSILow['value']) {
                return 'bearish';
            }
        }
        
        return 'none';
    }

    /**
     * Calculate volume confirmation
     * 
     * Checks if current volume exceeds threshold multiplier of average volume,
     * confirming genuine market interest in the movement.
     * 
     * @param array $historicalData Historical data with 'volume' values
     * @return bool True if volume exceeds threshold (default 1.5x average)
     */
    private function calculateVolumeConfirmation(array $historicalData): bool
    {
        $threshold = (float)$this->parameters['volume_threshold'];
        $lookback = (int)$this->parameters['lookback_period'];
        
        $volumes = array_column($historicalData, 'volume');
        $recentVolumes = array_slice($volumes, -$lookback);
        
        if (count($recentVolumes) < 2) {
            return false;
        }
        
        $avgVolume = array_sum(array_slice($recentVolumes, 0, -1)) / (count($recentVolumes) - 1);
        $currentVolume = end($recentVolumes);
        
        return $currentVolume >= ($avgVolume * $threshold);
    }

    /**
     * Calculate mean reversion score
     * 
     * Combines multiple indicators with weighted scoring:
     * - BB position: 40% weight
     * - RSI oversold: 30% weight
     * - Bullish divergence: 15% weight
     * - Volume confirmation: 10% weight
     * - Support bounce: 5% weight
     * 
     * @param array $indicators Array of technical indicators
     * @return float Composite score (0.0 to 1.0)
     */
    private function calculateMeanReversionScore(array $indicators): float
    {
        $score = 0;
        $maxScore = 0;
        
        // Bollinger Band position (40% weight)
        $maxScore += 40;
        if ($indicators['bb_position'] < -0.8) {
            $score += 40;
        } elseif ($indicators['bb_position'] < -0.5) {
            $score += 25;
        } elseif ($indicators['bb_position'] < 0) {
            $score += 10;
        }
        
        // RSI oversold (30% weight)
        $maxScore += 30;
        if ($indicators['rsi'] < 25) {
            $score += 30;
        } elseif ($indicators['rsi'] < 30) {
            $score += 20;
        } elseif ($indicators['rsi'] < 40) {
            $score += 10;
        }
        
        // Bullish divergence (15% weight)
        $maxScore += 15;
        if ($indicators['divergence'] === 'bullish') {
            $score += 15;
        }
        
        // Volume confirmation (10% weight)
        $maxScore += 10;
        if ($indicators['volume_confirmation']) {
            $score += 10;
        }
        
        // Support bounce (5% weight)
        $maxScore += 5;
        if ($indicators['support_bounce']) {
            $score += 5;
        }
        
        return round($score / $maxScore, 2);
    }

    /**
     * Detect support level bounce
     * 
     * Identifies if price has bounced from recent support level by checking
     * if current price is above recent minimum by threshold percentage.
     * 
     * @param array $historicalData Historical price data
     * @return bool True if bounce detected from recent support
     */
    private function detectSupportBounce(array $historicalData): bool
    {
        $threshold = (float)$this->parameters['support_bounce_threshold'];
        $prices = array_column($historicalData, 'close');
        
        if (count($prices) < 10) {
            return false;
        }
        
        $recentPrices = array_slice($prices, -10);
        $min = min($recentPrices);
        $current = end($recentPrices);
        
        // Check if we've bounced at least threshold% from recent low
        $bouncePercent = ($current - $min) / $min;
        
        // Also check if the minimum was recent (within last 5 days)
        $minIndex = array_search($min, $recentPrices);
        
        return $bouncePercent >= $threshold && $minIndex >= 5;
    }

    /**
     * Calculate historical volatility
     * 
     * Computes standard deviation of returns over recent period (20 days).
     * Used to ensure sufficient price movement for mean reversion trades.
     * 
     * @param array $historicalData Historical price data
     * @return float Volatility as standard deviation of returns
     */
    private function calculateVolatility(array $historicalData): float
    {
        $prices = array_column($historicalData, 'close');
        $recentPrices = array_slice($prices, -20);
        
        if (count($recentPrices) < 2) {
            return 0.0;
        }
        
        $returns = [];
        for ($i = 1; $i < count($recentPrices); $i++) {
            $returns[] = ($recentPrices[$i] - $recentPrices[$i - 1]) / $recentPrices[$i - 1];
        }
        
        $mean = array_sum($returns) / count($returns);
        $variance = 0;
        foreach ($returns as $return) {
            $variance += pow($return - $mean, 2);
        }
        $variance /= count($returns);
        
        return round(sqrt($variance), 4);
    }

    /**
     * Count Bollinger Band touches
     * 
     * Counts how many times price has touched or crossed below lower Bollinger Band
     * in lookback period. More touches can indicate stronger support level.
     * 
     * @param array $historicalData Historical price data
     * @param array $bollingerBands Current Bollinger Band values
     * @return int Number of lower band touches in lookback period
     */
    private function countBandTouches(array $historicalData, array $bollingerBands): int
    {
        $lookback = (int)$this->parameters['lookback_period'];
        $prices = array_column($historicalData, 'close');
        $recentPrices = array_slice($prices, -$lookback);
        
        $touches = 0;
        
        // Recalculate bands for each historical point
        for ($i = $this->parameters['bb_period']; $i < count($recentPrices); $i++) {
            $window = array_slice($recentPrices, $i - $this->parameters['bb_period'], $this->parameters['bb_period']);
            $sma = array_sum($window) / count($window);
            
            $variance = 0;
            foreach ($window as $price) {
                $variance += pow($price - $sma, 2);
            }
            $stdDev = sqrt($variance / count($window));
            
            $lowerBand = $sma - ($this->parameters['bb_std_dev'] * $stdDev);
            
            // Check if price touched or crossed below lower band
            if ($recentPrices[$i] <= $lowerBand * 1.02) { // 2% tolerance
                $touches++;
            }
        }
        
        return $touches;
    }

    /**
     * Calculate distance from mean
     * 
     * Computes percentage distance of current price from middle Bollinger Band (mean).
     * Negative values indicate price below mean.
     * 
     * @param array $historicalData Historical price data (unused but kept for consistency)
     * @param array $bollingerBands Bollinger Band values with current_price and middle
     * @return float Percentage distance from mean (negative = below mean)
     */
    private function calculateDistanceFromMean(array $historicalData, array $bollingerBands): float
    {
        $currentPrice = $bollingerBands['current_price'];
        $mean = $bollingerBands['middle'];
        
        if ($mean == 0) {
            return 0.0;
        }
        
        return round(($currentPrice - $mean) / $mean, 4);
    }

    /**
     * Determine overall trend context
     * 
     * Compares first half average to second half average of recent prices to identify
     * trend direction (uptrend, downtrend, or sideways).
     * 
     * @param array $historicalData Historical price data
     * @return string Trend direction: 'uptrend', 'downtrend', 'sideways', or 'unknown'
     */
    private function determineTrendContext(array $historicalData): string
    {
        $prices = array_column($historicalData, 'close');
        $recentPrices = array_slice($prices, -20);
        
        if (count($recentPrices) < 10) {
            return 'unknown';
        }
        
        $firstHalf = array_slice($recentPrices, 0, 10);
        $secondHalf = array_slice($recentPrices, -10);
        
        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);
        
        $change = ($secondAvg - $firstAvg) / $firstAvg;
        
        if ($change > 0.05) {
            return 'uptrend';
        } elseif ($change < -0.05) {
            return 'downtrend';
        } else {
            return 'sideways';
        }
    }

    /**
     * Determine trading action based on metrics
     * 
     * Evaluates all technical indicators and metrics to generate trading recommendation.
     * Returns BUY for strong oversold signals with confirmation, HOLD otherwise.
     * 
     * @param array $metrics Calculated technical metrics
     * @param array $fundamentals Fundamental data (unused but available for future enhancements)
     * @return array Action result with action, confidence, and reasoning
     */
    private function determineAction(array $metrics, array $fundamentals): array
    {
        $minVolatility = (float)$this->parameters['min_volatility'];
        $scoreThreshold = (float)$this->parameters['mean_reversion_score_threshold'];
        
        // Check if volatility is sufficient
        if ($metrics['volatility'] < $minVolatility) {
            return [
                'action' => 'HOLD',
                'confidence' => 0,
                'reasoning' => 'Insufficient volatility for mean reversion strategy (volatility: ' . 
                              number_format($metrics['volatility'] * 100, 2) . '%)'
            ];
        }
        
        // Check for overbought conditions
        if ($metrics['rsi'] > $this->parameters['rsi_overbought']) {
            return [
                'action' => 'HOLD',
                'confidence' => 0,
                'reasoning' => 'Stock is overbought (RSI: ' . $metrics['rsi'] . ')'
            ];
        }
        
        // Strong oversold signal with confirmation
        if ($metrics['oversold_signal'] && 
            $metrics['volume_confirmation'] && 
            $metrics['mean_reversion_score'] >= $scoreThreshold) {
            
            $confidence = min(95, 60 + ($metrics['mean_reversion_score'] * 35));
            
            $reasoning = sprintf(
                'Strong oversold mean reversion opportunity: Price %.2f%% below Bollinger Band mean, ' .
                'RSI at %d (oversold < %d), volume confirmation present, mean reversion score %.2f. ',
                $metrics['distance_from_mean'] * 100,
                (int)$metrics['rsi'],
                (int)$this->parameters['rsi_oversold'],
                $metrics['mean_reversion_score']
            );
            
            if ($metrics['rsi_divergence'] === 'bullish') {
                $reasoning .= 'Bullish RSI divergence detected. ';
            }
            
            if ($metrics['support_bounce']) {
                $reasoning .= 'Recent bounce from support level. ';
            }
            
            $reasoning .= sprintf(
                'Current trend: %s. Target mean reversion to $%.2f (middle band).',
                $metrics['trend'],
                $metrics['middle_band']
            );
            
            return [
                'action' => 'BUY',
                'confidence' => (int)$confidence,
                'reasoning' => $reasoning
            ];
        }
        
        // Moderate oversold without all confirmations
        if ($metrics['oversold_signal'] || 
            ($metrics['rsi'] < $this->parameters['rsi_oversold'] && $metrics['bb_position'] < -0.5)) {
            
            $confidence = 30 + ($metrics['mean_reversion_score'] * 20);
            
            $reasoning = sprintf(
                'Potential mean reversion setup developing. RSI: %d, BB Position: %.2f, Score: %.2f. ',
                (int)$metrics['rsi'],
                $metrics['bb_position'],
                $metrics['mean_reversion_score']
            );
            
            if (!$metrics['volume_confirmation']) {
                $reasoning .= 'Waiting for volume confirmation. ';
            }
            
            return [
                'action' => 'HOLD',
                'confidence' => (int)$confidence,
                'reasoning' => $reasoning
            ];
        }
        
        // Neutral or not oversold
        return [
            'action' => 'HOLD',
            'confidence' => 0,
            'reasoning' => sprintf(
                'No mean reversion signal. RSI: %d, BB Position: %.2f, Trend: %s',
                (int)$metrics['rsi'],
                $metrics['bb_position'],
                $metrics['trend']
            )
        ];
    }

    /**
     * Get strategy parameters
     * 
     * @return array Current strategy parameters
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Set strategy parameters
     * 
     * Updates strategy parameters with provided values. Only updates keys that
     * exist in current parameters array.
     * 
     * @param array $parameters Parameters to update
     * @return void
     */
    public function setParameters(array $parameters): void
    {
        foreach ($parameters as $key => $value) {
            if (array_key_exists($key, $this->parameters)) {
                $this->parameters[$key] = $value;
            }
        }
    }

    /**
     * Check if strategy can execute for symbol
     * 
     * Mean reversion strategy can execute for any symbol with sufficient data.
     * 
     * @param string $symbol Stock ticker symbol
     * @return bool Always returns true
     */
    public function canExecute(string $symbol): bool
    {
        return true;
    }

    /**
     * Get required historical days
     * 
     * Returns minimum number of historical data days required for accurate analysis.
     * 
     * @return int Number of days required (100)
     */
    public function getRequiredHistoricalDays(): int
    {
        return 100;
    }
}
