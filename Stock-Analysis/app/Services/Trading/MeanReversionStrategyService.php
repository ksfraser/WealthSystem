<?php

namespace App\Services\Trading;

use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

class MeanReversionStrategyService implements TradingStrategyInterface
{
    private MarketDataService $marketDataService;
    private MarketDataRepositoryInterface $marketDataRepository;
    
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

    public function __construct(
        MarketDataService $marketDataService,
        MarketDataRepositoryInterface $marketDataRepository
    ) {
        $this->marketDataService = $marketDataService;
        $this->marketDataRepository = $marketDataRepository;
        $this->loadParametersFromDatabase();
    }

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

    public function getName(): string
    {
        return 'MeanReversion';
    }

    public function getDescription(): string
    {
        return 'Identifies oversold conditions using Bollinger Bands and RSI, looking for mean reversion opportunities with volume confirmation and bullish divergence patterns.';
    }

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

    private function calculateDistanceFromMean(array $historicalData, array $bollingerBands): float
    {
        $currentPrice = $bollingerBands['current_price'];
        $mean = $bollingerBands['middle'];
        
        if ($mean == 0) {
            return 0.0;
        }
        
        return round(($currentPrice - $mean) / $mean, 4);
    }

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

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        foreach ($parameters as $key => $value) {
            if (array_key_exists($key, $this->parameters)) {
                $this->parameters[$key] = $value;
            }
        }
    }

    public function canExecute(string $symbol): bool
    {
        return true;
    }

    public function getRequiredHistoricalDays(): int
    {
        return 100;
    }
}
