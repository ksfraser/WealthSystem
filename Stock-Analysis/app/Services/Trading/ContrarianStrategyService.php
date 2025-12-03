<?php

namespace App\Services\Trading;

use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

/**
 * Contrarian Strategy Service
 * 
 * Identifies quality stocks experiencing excessive market panic or selloffs.
 * Buys during overreactions when strong fundamentals suggest recovery potential.
 * 
 * Panic/Oversold Indicators:
 * - Drawdown: 20%+ decline from recent peak
 * - Volume surge: 1.8x average (indicates panic selling)
 * - RSI: < 30 (oversold territory)
 * - Capitulation detection: Extreme selling pressure
 * 
 * Fundamental Quality Requirements:
 * - Fundamental score: 65%+ minimum
 * - P/E ratio: < 15x (value territory)
 * - Price/Book: < 2.5x
 * - ROE: 10%+ return on equity
 * - Debt/Equity: < 2.0x
 * - Current ratio: > 1.2x (liquidity)
 * 
 * Contrarian Signals:
 * - Insider buying during decline (confidence)
 * - Sentiment reversal patterns
 * - Support level bounces
 * - Strong fundamentals + panic = opportunity
 * 
 * Risk Management:
 * - Requires strong fundamentals to justify contrarian position
 * - Multiple confirmation signals needed
 * - Higher confidence when capitulation detected
 * 
 * @package App\Services\Trading
 */
class ContrarianStrategyService implements TradingStrategyInterface
{
    /**
     * @var MarketDataService Market data service for fundamentals and prices
     */
    private MarketDataService $marketDataService;
    
    /**
     * @var MarketDataRepositoryInterface Repository for data persistence
     */
    private MarketDataRepositoryInterface $marketDataRepository;
    
    /**
     * @var array Strategy parameters with default values
     */
    private array $parameters = [
        'min_drawdown_percent' => 0.20,        // 20% minimum drawdown
        'max_drawdown_days' => 60,             // 60 days maximum drawdown period
        'panic_volume_multiplier' => 1.8,      // 1.8x volume for panic (accounts for averaging dilution)
        'min_fundamental_score' => 0.65,       // 65% minimum fundamental strength
        'max_pe_ratio' => 15,                  // 15 P/E maximum for value
        'max_price_to_book' => 2.5,            // 2.5 P/B maximum
        'min_roe' => 0.10,                     // 10% minimum ROE
        'max_debt_to_equity' => 2.0,           // 2.0 maximum debt/equity
        'min_current_ratio' => 1.2,            // 1.2x minimum current ratio
        'rsi_oversold_threshold' => 30,        // RSI below 30 = oversold
        'min_contrarian_score' => 0.60,        // 60% minimum contrarian score
        'insider_buying_days' => 30,           // 30 days to check for insider buying
        'sentiment_reversal_threshold' => 0.60 // 60% for reversal signal
    ];

    /**
     * Constructor
     * 
     * Initializes the contrarian strategy with required services.
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
     * @return void
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
            $stmt->execute(['Contrarian']);
            
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $key = $row['parameter_key'];
                $value = $row['parameter_value'];
                
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
            // Fall back to defaults
        }
    }

    /**
     * Get strategy name
     * 
     * @return string Strategy identifier
     */
    public function getName(): string
    {
        return 'Contrarian';
    }

    /**
     * Get strategy description
     * 
     * @return string Human-readable description of strategy logic
     */
    public function getDescription(): string
    {
        return 'Identifies quality stocks experiencing excessive market selloffs. Buys during panic when strong fundamentals suggest the decline is an overreaction, targeting mean reversion opportunities.';
    }

    /**
     * Analyze symbol for contrarian trading opportunities
     * 
     * Identifies panic selling situations in fundamentally strong companies.
     * Looks for excessive drawdowns with volume confirmation and strong fundamentals.
     * 
     * @param string $symbol Stock ticker symbol to analyze
     * @param string $date Analysis date (default: 'today')
     * @return array Analysis result with action, confidence, reasoning, and metrics
     */
    public function analyze(string $symbol, string $date = 'today'): array
    {
        try {
            $fundamentals = $this->marketDataService->getFundamentals($symbol);
            $historicalData = $this->marketDataService->getHistoricalPrices($symbol, 150);

            if (empty($fundamentals) || count($historicalData) < 100) {
                return [
                    'action' => 'HOLD',
                    'confidence' => 0,
                    'reasoning' => 'Insufficient data for contrarian analysis',
                    'metrics' => []
                ];
            }

            // Calculate drawdown and oversold metrics
            $drawdownData = $this->calculateDrawdown($historicalData);
            $isOversold = $drawdownData['drawdown_percent'] >= $this->parameters['min_drawdown_percent'];
            
            // Calculate RSI
            $rsi = $this->calculateRSI($historicalData, 14);
            
            // Detect panic selling
            $panicData = $this->detectPanicSelling($historicalData);
            
            // Calculate fundamental strength
            $fundamentalScore = $this->calculateFundamentalScore($fundamentals);
            
            // Calculate sentiment metrics
            $sentimentScore = $this->calculateSentimentScore($historicalData);
            $sentimentReversal = $this->detectSentimentReversal($historicalData);
            
            // Check insider buying
            $insiderBuying = $this->checkInsiderBuying($fundamentals);
            
            // Calculate recovery potential
            $recoveryPotential = $this->calculateRecoveryPotential(
                $drawdownData,
                $fundamentalScore,
                $panicData
            );
            
            // Detect capitulation
            $capitulation = $this->detectCapitulation($historicalData, $panicData);
            
            // Calculate contrarian score
            $contrarianScore = $this->calculateContrarianScore([
                'is_oversold' => $isOversold,
                'fundamental_score' => $fundamentalScore,
                'panic_selling' => $panicData['is_panic'],
                'sentiment_reversal' => $sentimentReversal,
                'insider_buying' => $insiderBuying,
                'capitulation' => $capitulation,
                'rsi' => $rsi
            ]);

            $metrics = [
                'is_oversold' => $isOversold,
                'drawdown_percent' => $drawdownData['drawdown_percent'],
                'drawdown_days' => $drawdownData['drawdown_days'],
                'peak_price' => $drawdownData['peak_price'],
                'current_price' => $drawdownData['current_price'],
                'rsi' => $rsi,
                'panic_selling' => $panicData['is_panic'],
                'volume_spike' => $panicData['volume_ratio'],
                'capitulation' => $capitulation,
                'fundamental_score' => $fundamentalScore,
                'sentiment_score' => $sentimentScore,
                'sentiment_reversal' => $sentimentReversal,
                'insider_buying' => $insiderBuying,
                'recovery_potential' => $recoveryPotential,
                'contrarian_score' => $contrarianScore,
                'pe_ratio' => $fundamentals['pe_ratio'] ?? 0,
                'price_to_book' => $fundamentals['price_to_book'] ?? 0,
                'debt_to_equity' => $fundamentals['debt_to_equity'] ?? 0,
                'free_cash_flow' => $fundamentals['free_cash_flow'] ?? 0
            ];

            $result = $this->determineAction($metrics, $fundamentals);
            $result['metrics'] = $metrics;

            return $result;

        } catch (\Exception $e) {
            return [
                'action' => 'HOLD',
                'confidence' => 0,
                'reasoning' => 'Error in contrarian analysis: ' . $e->getMessage(),
                'metrics' => []
            ];
        }
    }

    private function calculateDrawdown(array $historicalData): array
    {
        $prices = array_column($historicalData, 'close');
        $currentPrice = end($prices);
        
        // Find peak in last 100 days
        $peakPrice = 0;
        $peakIndex = 0;
        
        for ($i = max(0, count($prices) - 100); $i < count($prices); $i++) {
            if ($prices[$i] > $peakPrice) {
                $peakPrice = $prices[$i];
                $peakIndex = $i;
            }
        }
        
        $drawdownPercent = 0;
        if ($peakPrice > 0) {
            $drawdownPercent = ($peakPrice - $currentPrice) / $peakPrice;
        }
        
        $drawdownDays = count($prices) - $peakIndex - 1;
        
        return [
            'drawdown_percent' => $drawdownPercent,
            'drawdown_days' => $drawdownDays,
            'peak_price' => $peakPrice,
            'current_price' => $currentPrice
        ];
    }

    private function calculateRSI(array $historicalData, int $period = 14): float
    {
        $prices = array_column($historicalData, 'close');
        
        if (count($prices) < $period + 1) {
            return 50.0;
        }
        
        $gains = [];
        $losses = [];
        
        // Use most recent prices for RSI calculation
        $startIndex = count($prices) - $period - 1;
        
        for ($i = $startIndex; $i < count($prices) - 1; $i++) {
            $change = $prices[$i + 1] - $prices[$i];
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
            return $avgGain > 0 ? 100.0 : 50.0;
        }
        
        if ($avgGain == 0) {
            return 0.0; // All losses = very oversold
        }
        
        $rs = $avgGain / $avgLoss;
        $rsi = 100 - (100 / (1 + $rs));
        
        return round($rsi, 2);
    }

    private function detectPanicSelling(array $historicalData): array
    {
        if (count($historicalData) < 70) {
            return ['is_panic' => false, 'volume_ratio' => 1.0];
        }
        
        $volumes = array_column($historicalData, 'volume');
        $prices = array_column($historicalData, 'close');
        
        // Check last 60 days for volume spike during decline (wider window to catch recent panic)
        // Compare recent 60 days to older baseline
        $recentVolumes = array_slice($volumes, -60);
        $olderVolumes = array_slice($volumes, -120, 60); // Days 60-120 back
        $avgOlderVolume = array_sum($olderVolumes) / count($olderVolumes);
        $avgRecentVolume = array_sum($recentVolumes) / count($recentVolumes);
        
        $volumeRatio = $avgOlderVolume > 0 ? $avgRecentVolume / $avgOlderVolume : 1.0;
        
        // Check for significant price decline in recent period
        $recentPrices = array_slice($prices, -60);
        $priceChange = ($recentPrices[count($recentPrices) - 1] - $recentPrices[0]) / $recentPrices[0];
        
        $isPanic = $volumeRatio >= $this->parameters['panic_volume_multiplier'] && $priceChange < -0.15;
        
        return [
            'is_panic' => $isPanic,
            'volume_ratio' => round($volumeRatio, 2)
        ];
    }

    private function calculateFundamentalScore(array $fundamentals): float
    {
        $score = 0;
        $maxScore = 0;
        
        // ROE (20 points)
        $maxScore += 20;
        $roe = $fundamentals['roe'] ?? 0;
        if ($roe > 0.20) {
            $score += 20;
        } elseif ($roe > 0.15) {
            $score += 15;
        } elseif ($roe > 0.10) {
            $score += 10;
        }
        
        // Profit margin (15 points)
        $maxScore += 15;
        $margin = $fundamentals['profit_margin'] ?? 0;
        if ($margin > 0.15) {
            $score += 15;
        } elseif ($margin > 0.10) {
            $score += 10;
        } elseif ($margin > 0.05) {
            $score += 5;
        }
        
        // Debt level (20 points)
        $maxScore += 20;
        $debt = $fundamentals['debt_to_equity'] ?? 0;
        if ($debt < 0.50) {
            $score += 20;
        } elseif ($debt < 1.0) {
            $score += 15;
        } elseif ($debt < 2.0) {
            $score += 10;
        }
        
        // Free cash flow (15 points)
        $maxScore += 15;
        $fcf = $fundamentals['free_cash_flow'] ?? 0;
        if ($fcf > 1000000000) {
            $score += 15;
        } elseif ($fcf > 0) {
            $score += 10;
        }
        
        // Current ratio (15 points)
        $maxScore += 15;
        $currentRatio = $fundamentals['current_ratio'] ?? 0;
        if ($currentRatio > 2.0) {
            $score += 15;
        } elseif ($currentRatio > 1.5) {
            $score += 10;
        } elseif ($currentRatio > 1.2) {
            $score += 5;
        }
        
        // Revenue growth (15 points)
        $maxScore += 15;
        $revenue = $fundamentals['revenue'] ?? 0;
        $priorRevenue = $fundamentals['prior_year_revenue'] ?? 0;
        if ($priorRevenue > 0) {
            $revenueGrowth = ($revenue - $priorRevenue) / $priorRevenue;
            if ($revenueGrowth > 0.15) {
                $score += 15;
            } elseif ($revenueGrowth > 0.10) {
                $score += 10;
            } elseif ($revenueGrowth > 0) {
                $score += 5;
            }
        }
        
        return round($score / $maxScore, 2);
    }

    private function calculateSentimentScore(array $historicalData): float
    {
        // Sentiment based on recent price action and volume trends
        if (count($historicalData) < 20) {
            return 0.5;
        }
        
        $prices = array_column($historicalData, 'close');
        $volumes = array_column($historicalData, 'volume');
        
        $recentPrices = array_slice($prices, -10);
        $priceChange = ($recentPrices[count($recentPrices) - 1] - $recentPrices[0]) / $recentPrices[0];
        
        // Negative sentiment if declining
        $sentimentScore = 0.5 + ($priceChange * 2); // Scale price change to sentiment
        $sentimentScore = max(0, min(1, $sentimentScore)); // Clamp to 0-1
        
        return round($sentimentScore, 2);
    }

    private function detectSentimentReversal(array $historicalData): bool
    {
        if (count($historicalData) < 50) {
            return false;
        }
        
        $prices = array_column($historicalData, 'close');
        $volumes = array_column($historicalData, 'volume');
        
        // Check for higher lows in last 20 days (bottoming pattern)
        $recentPrices = array_slice($prices, -20);
        $lows = [];
        
        for ($i = 1; $i < count($recentPrices) - 1; $i++) {
            if ($recentPrices[$i] <= $recentPrices[$i - 1] && $recentPrices[$i] <= $recentPrices[$i + 1]) {
                $lows[] = ['index' => $i, 'price' => $recentPrices[$i]];
            }
        }
        
        // Check if lows are rising (at least 2 lows, latest higher than first)
        $risingLows = false;
        if (count($lows) >= 2) {
            $risingLows = $lows[count($lows) - 1]['price'] > $lows[0]['price'];
        }
        
        // Check for volume increase in recent period
        $recentVolumes = array_slice($volumes, -20);
        $olderVolumes = array_slice($volumes, -50, 30);
        $avgRecent = array_sum($recentVolumes) / count($recentVolumes);
        $avgOlder = array_sum($olderVolumes) / count($olderVolumes);
        $volumeIncreasing = $avgRecent > ($avgOlder * 1.1); // 10% increase
        
        return $risingLows || $volumeIncreasing; // Either condition = reversal sign
    }

    private function checkInsiderBuying(array $fundamentals): bool
    {
        $transactions = $fundamentals['insider_transactions'] ?? [];
        
        if (empty($transactions)) {
            return false;
        }
        
        $recentBuys = 0;
        $cutoffDate = date('Y-m-d', strtotime('-' . $this->parameters['insider_buying_days'] . ' days'));
        
        foreach ($transactions as $transaction) {
            if ($transaction['date'] >= $cutoffDate && strtoupper($transaction['type']) === 'BUY') {
                $recentBuys++;
            }
        }
        
        return $recentBuys >= 2; // At least 2 insider buys
    }

    private function calculateRecoveryPotential(array $drawdownData, float $fundamentalScore, array $panicData): float
    {
        // Recovery potential based on drawdown size, fundamental strength, and panic
        $drawdownComponent = min(1.0, $drawdownData['drawdown_percent'] / 0.40); // 40% drawdown = max
        $fundamentalComponent = $fundamentalScore;
        $panicComponent = $panicData['is_panic'] ? 1.0 : 0.5;
        
        // Weighted average
        $potential = ($drawdownComponent * 0.35) + ($fundamentalComponent * 0.45) + ($panicComponent * 0.20);
        
        return round($potential, 2);
    }

    private function detectCapitulation(array $historicalData, array $panicData): bool
    {
        // Capitulation: panic selling + sustained price weakness
        if (!$panicData['is_panic']) {
            return false;
        }
        
        if (count($historicalData) < 60) {
            return false;
        }
        
        $prices = array_column($historicalData, 'close');
        $recent60 = array_slice($prices, -60);
        
        // Check for significant decline over 60-day window matching panic
        $priceChange = ($recent60[count($recent60) - 1] - $recent60[0]) / $recent60[0];
        
        // Capitulation = panic volume + large decline
        return $priceChange < -0.25 && $panicData['volume_ratio'] >= 1.8;
    }

    private function calculateContrarianScore(array $indicators): float
    {
        $score = 0;
        $maxScore = 0;
        
        // Oversold condition (25 points)
        $maxScore += 25;
        if ($indicators['is_oversold']) {
            $score += 25;
        }
        
        // Fundamental strength (25 points)
        $maxScore += 25;
        $score += $indicators['fundamental_score'] * 25;
        
        // Panic selling (20 points)
        $maxScore += 20;
        if ($indicators['panic_selling']) {
            $score += 20;
        }
        
        // RSI oversold (15 points)
        $maxScore += 15;
        if ($indicators['rsi'] < 30) {
            $score += 15;
        } elseif ($indicators['rsi'] < 40) {
            $score += 10;
        }
        
        // Sentiment reversal (10 points)
        $maxScore += 10;
        if ($indicators['sentiment_reversal']) {
            $score += 10;
        }
        
        // Insider buying (5 points)
        $maxScore += 5;
        if ($indicators['insider_buying']) {
            $score += 5;
        }
        
        return round($score / $maxScore, 2);
    }

    private function determineAction(array $metrics, array $fundamentals): array
    {
        // Insufficient drawdown
        if (!$metrics['is_oversold']) {
            return [
                'action' => 'HOLD',
                'confidence' => 0,
                'reasoning' => sprintf(
                    'Insufficient drawdown: %.1f%% (minimum %.1f%% required)',
                    $metrics['drawdown_percent'] * 100,
                    $this->parameters['min_drawdown_percent'] * 100
                )
            ];
        }
        
        // Weak fundamentals - avoid value trap
        if ($metrics['fundamental_score'] < $this->parameters['min_fundamental_score']) {
            return [
                'action' => 'HOLD',
                'confidence' => 0,
                'reasoning' => sprintf(
                    'Weak fundamentals: %.1f%% score (minimum %.1f%%). May be value trap - ROE: %.1f%%, FCF: $%.0fM',
                    $metrics['fundamental_score'] * 100,
                    $this->parameters['min_fundamental_score'] * 100,
                    ($fundamentals['roe'] ?? 0) * 100,
                    ($fundamentals['free_cash_flow'] ?? 0) / 1000000
                )
            ];
        }
        
        // Strong contrarian opportunity - BUY signal
        if ($metrics['contrarian_score'] >= $this->parameters['min_contrarian_score'] &&
            $metrics['fundamental_score'] >= $this->parameters['min_fundamental_score']) {
            
            $confidence = 60 + ($metrics['contrarian_score'] * 25) + ($metrics['fundamental_score'] * 10);
            $confidence = min(95, (int)$confidence);
            
            $reasoning = sprintf(
                'Strong contrarian opportunity: %.1f%% drawdown from peak ($%.2f to $%.2f), %.1f%% contrarian score, %.1f%% fundamental strength. ',
                $metrics['drawdown_percent'] * 100,
                $metrics['peak_price'],
                $metrics['current_price'],
                $metrics['contrarian_score'] * 100,
                $metrics['fundamental_score'] * 100
            );
            
            if ($metrics['panic_selling']) {
                $reasoning .= sprintf('Panic selling detected (%.1fx volume spike). ', $metrics['volume_spike']);
            }
            
            if ($metrics['capitulation']) {
                $reasoning .= 'Capitulation signal. ';
            }
            
            $reasoning .= sprintf(
                'RSI: %.1f (oversold). ',
                $metrics['rsi']
            );
            
            if ($metrics['sentiment_reversal']) {
                $reasoning .= 'Sentiment reversal detected (higher lows). ';
            }
            
            if ($metrics['insider_buying']) {
                $reasoning .= 'Recent insider buying. ';
            }
            
            $reasoning .= sprintf(
                'Recovery potential: %.1f%%.',
                $metrics['recovery_potential'] * 100
            );
            
            return [
                'action' => 'BUY',
                'confidence' => $confidence,
                'reasoning' => $reasoning
            ];
        }
        
        // Moderate opportunity
        if ($metrics['contrarian_score'] >= 0.50) {
            $confidence = 35 + ($metrics['contrarian_score'] * 20);
            
            return [
                'action' => 'HOLD',
                'confidence' => (int)$confidence,
                'reasoning' => sprintf(
                    'Moderate contrarian signal: %.1f%% score, %.1f%% drawdown. Need stronger setup or fundamental improvement.',
                    $metrics['contrarian_score'] * 100,
                    $metrics['drawdown_percent'] * 100
                )
            ];
        }
        
        // Weak contrarian setup
        return [
            'action' => 'HOLD',
            'confidence' => 0,
            'reasoning' => sprintf(
                'Weak contrarian setup: %.1f%% score (minimum %.1f%%). Insufficient oversold conditions.',
                $metrics['contrarian_score'] * 100,
                $this->parameters['min_contrarian_score'] * 100
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
     * Updates strategy parameters with provided values.
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
     * Requires 150 days for accurate drawdown and panic detection.
     * 
     * @return int Number of days required (150)
     */
    public function getRequiredHistoricalDays(): int
    {
        return 150;
    }
}
