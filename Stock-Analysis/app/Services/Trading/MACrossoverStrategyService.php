<?php

namespace App\Services\Trading;

use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

/**
 * Moving Average Crossover Strategy
 * 
 * Classic trend-following strategy using two moving averages of different periods.
 * 
 * Strategy Overview:
 * - Uses a fast MA (e.g., 50-day) and slow MA (e.g., 200-day)
 * - BUY signal when fast MA crosses above slow MA (Golden Cross)
 * - SELL signal when fast MA crosses below slow MA (Death Cross)
 * - Trend confirmation using price position relative to MAs
 * 
 * Entry Rules:
 * - BUY: Fast MA > Slow MA AND price > Fast MA (uptrend confirmation)
 * - Exit when Fast MA < Slow MA
 * 
 * Popular Configurations:
 * - 50/200 SMA (Golden Cross/Death Cross)
 * - 20/50 EMA (shorter-term trends)
 * - 5/20 EMA (very short-term)
 * 
 * @package App\Services\Trading
 */
class MACrossoverStrategyService implements TradingStrategyInterface
{
    private const SIGNAL_BUY = 'BUY';
    private const SIGNAL_SELL = 'SELL';
    private const SIGNAL_HOLD = 'HOLD';

    private MarketDataService $marketDataService;
    private MarketDataRepositoryInterface $marketDataRepository;
    
    /**
     * Strategy parameters
     * 
     * @var array
     */
    private array $parameters = [
        'fast_period' => 50,           // Fast moving average period
        'slow_period' => 200,          // Slow moving average period
        'ma_type' => 'SMA',           // 'SMA' or 'EMA'
        'confirm_with_price' => true, // Require price confirmation
        'min_crossover_gap' => 0.001, // Minimum gap between MAs (0.1%)
        'position_size' => 0.05,      // Default position size (5%)
        'stop_loss_percent' => 0.05,  // 5% stop loss
        'take_profit_percent' => 0.15 // 15% take profit
    ];

    public function __construct(
        MarketDataService $marketDataService,
        MarketDataRepositoryInterface $marketDataRepository
    ) {
        $this->marketDataService = $marketDataService;
        $this->marketDataRepository = $marketDataRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Moving Average Crossover';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Trend-following strategy using moving average crossovers. '
             . 'Generates BUY signal on Golden Cross (fast MA crosses above slow MA), '
             . 'SELL signal on Death Cross (fast MA crosses below slow MA). '
             . 'Default: 50/200 SMA crossover with price confirmation.';
    }

    /**
     * {@inheritdoc}
     */
    public function analyze(string $symbol, string $date = 'today'): array
    {
        // Validate inputs
        if (empty($symbol)) {
            throw new \InvalidArgumentException('Symbol cannot be empty');
        }

        // Get historical price data
        $requiredDays = $this->getRequiredHistoricalDays();
        $priceHistory = $this->marketDataService->getHistoricalPrices($symbol, null, null, $requiredDays);

        if (empty($priceHistory) || count($priceHistory) < $requiredDays) {
            return $this->createHoldSignal('Insufficient price data for MA calculation');
        }

        // Get current price
        $currentPriceData = $this->marketDataService->getCurrentPrice($symbol);
        $currentPrice = $currentPriceData['price'] ?? 0;
        
        if ($currentPrice < 0.01) {
            return $this->createHoldSignal('Invalid price data');
        }

        // Calculate moving averages
        $fastMA = $this->calculateMA($priceHistory, $this->parameters['fast_period']);
        $slowMA = $this->calculateMA($priceHistory, $this->parameters['slow_period']);
        
        // Get previous MAs for crossover detection
        $prevFastMA = $this->calculateMA($priceHistory, $this->parameters['fast_period'], 1);
        $prevSlowMA = $this->calculateMA($priceHistory, $this->parameters['slow_period'], 1);

        if ($fastMA === null || $slowMA === null || $prevFastMA === null || $prevSlowMA === null) {
            return $this->createHoldSignal('Unable to calculate moving averages');
        }

        // Detect crossovers
        $goldenCross = $this->detectGoldenCross($prevFastMA, $prevSlowMA, $fastMA, $slowMA);
        $deathCross = $this->detectDeathCross($prevFastMA, $prevSlowMA, $fastMA, $slowMA);

        // Generate signals
        if ($goldenCross) {
            // Confirm with price if required
            if ($this->parameters['confirm_with_price'] && $currentPrice < $fastMA) {
                return $this->createHoldSignal('Golden Cross detected but price below fast MA');
            }
            
            return $this->createBuySignal(
                $symbol, 
                $currentPrice, 
                $fastMA, 
                $slowMA,
                'Golden Cross: Fast MA (' . number_format($fastMA, 2) . ') crossed above Slow MA (' . number_format($slowMA, 2) . ')'
            );
        }

        if ($deathCross) {
            return $this->createSellSignal(
                $currentPrice,
                $fastMA,
                $slowMA,
                'Death Cross: Fast MA (' . number_format($fastMA, 2) . ') crossed below Slow MA (' . number_format($slowMA, 2) . ')'
            );
        }

        // Check current trend (no crossover, but in position)
        if ($fastMA > $slowMA) {
            // Uptrend
            if ($currentPrice > $fastMA) {
                return $this->createHoldSignal('Uptrend continues: Fast MA above Slow MA, price above Fast MA');
            } else {
                return $this->createHoldSignal('Uptrend weakening: Price below Fast MA');
            }
        } else {
            // Downtrend or no trend
            return $this->createHoldSignal('No bullish signal: Fast MA below Slow MA');
        }
    }

    /**
     * Detect Golden Cross (bullish crossover)
     * 
     * @param float $prevFastMA Previous fast MA
     * @param float $prevSlowMA Previous slow MA
     * @param float $fastMA Current fast MA
     * @param float $slowMA Current slow MA
     * @return bool True if golden cross detected
     */
    private function detectGoldenCross(float $prevFastMA, float $prevSlowMA, float $fastMA, float $slowMA): bool
    {
        // Previously: Fast MA was below Slow MA
        // Currently: Fast MA is above Slow MA
        $crossedOver = ($prevFastMA <= $prevSlowMA) && ($fastMA > $slowMA);
        
        // Check minimum gap to avoid false signals
        if ($crossedOver) {
            $gap = ($fastMA - $slowMA) / $slowMA;
            return $gap >= $this->parameters['min_crossover_gap'];
        }
        
        return false;
    }

    /**
     * Detect Death Cross (bearish crossover)
     * 
     * @param float $prevFastMA Previous fast MA
     * @param float $prevSlowMA Previous slow MA
     * @param float $fastMA Current fast MA
     * @param float $slowMA Current slow MA
     * @return bool True if death cross detected
     */
    private function detectDeathCross(float $prevFastMA, float $prevSlowMA, float $fastMA, float $slowMA): bool
    {
        // Previously: Fast MA was above Slow MA
        // Currently: Fast MA is below Slow MA
        $crossedUnder = ($prevFastMA >= $prevSlowMA) && ($fastMA < $slowMA);
        
        // Check minimum gap to avoid false signals
        if ($crossedUnder) {
            $gap = ($slowMA - $fastMA) / $slowMA;
            return $gap >= $this->parameters['min_crossover_gap'];
        }
        
        return false;
    }

    /**
     * Calculate Simple or Exponential Moving Average
     * 
     * @param array $priceHistory Price history
     * @param int $period MA period
     * @param int $offset Offset from end (0 = most recent, 1 = previous, etc.)
     * @return float|null Moving average or null if insufficient data
     */
    private function calculateMA(array $priceHistory, int $period, int $offset = 0): ?float
    {
        $count = count($priceHistory);
        
        if ($count < $period + $offset) {
            return null;
        }

        // Get the relevant slice of data
        $endIndex = $count - $offset;
        $startIndex = $endIndex - $period;
        $slice = array_slice($priceHistory, $startIndex, $period);

        if ($this->parameters['ma_type'] === 'EMA') {
            return $this->calculateEMA($slice, $period);
        }

        // Default: Simple Moving Average (SMA)
        $closes = array_column($slice, 'close');
        return array_sum($closes) / count($closes);
    }

    /**
     * Calculate Exponential Moving Average
     * 
     * @param array $priceData Price data slice
     * @param int $period EMA period
     * @return float EMA value
     */
    private function calculateEMA(array $priceData, int $period): float
    {
        $closes = array_column($priceData, 'close');
        $multiplier = 2 / ($period + 1);
        
        // Start with SMA as the initial EMA
        $ema = array_sum(array_slice($closes, 0, $period)) / $period;
        
        // Calculate EMA for remaining data
        for ($i = $period; $i < count($closes); $i++) {
            $ema = ($closes[$i] * $multiplier) + ($ema * (1 - $multiplier));
        }
        
        return $ema;
    }

    /**
     * Create a BUY signal
     */
    private function createBuySignal(string $symbol, float $currentPrice, float $fastMA, float $slowMA, string $reason): array
    {
        $stopLoss = $currentPrice * (1 - $this->parameters['stop_loss_percent']);
        $takeProfit = $currentPrice * (1 + $this->parameters['take_profit_percent']);
        
        return [
            'signal' => self::SIGNAL_BUY,
            'confidence' => $this->calculateConfidence($currentPrice, $fastMA, $slowMA),
            'reason' => $reason,
            'entry_price' => $currentPrice,
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'position_size' => $this->parameters['position_size'],
            'metadata' => [
                'fast_ma' => $fastMA,
                'slow_ma' => $slowMA,
                'fast_period' => $this->parameters['fast_period'],
                'slow_period' => $this->parameters['slow_period'],
                'ma_type' => $this->parameters['ma_type'],
                'price_above_fast_ma' => $currentPrice > $fastMA,
                'ma_spread_percent' => (($fastMA - $slowMA) / $slowMA) * 100
            ]
        ];
    }

    /**
     * Create a SELL signal
     */
    private function createSellSignal(float $currentPrice, float $fastMA, float $slowMA, string $reason): array
    {
        return [
            'signal' => self::SIGNAL_SELL,
            'confidence' => 0.80,
            'reason' => $reason,
            'entry_price' => $currentPrice,
            'stop_loss' => null,
            'take_profit' => null,
            'position_size' => 1.0, // Exit entire position
            'metadata' => [
                'fast_ma' => $fastMA,
                'slow_ma' => $slowMA,
                'fast_period' => $this->parameters['fast_period'],
                'slow_period' => $this->parameters['slow_period'],
                'ma_type' => $this->parameters['ma_type'],
                'ma_spread_percent' => (($slowMA - $fastMA) / $slowMA) * 100
            ]
        ];
    }

    /**
     * Create a HOLD signal
     */
    private function createHoldSignal(string $reason): array
    {
        return [
            'signal' => self::SIGNAL_HOLD,
            'confidence' => 0.0,
            'reason' => $reason,
            'entry_price' => null,
            'stop_loss' => null,
            'take_profit' => null,
            'position_size' => null,
            'metadata' => []
        ];
    }

    /**
     * Calculate confidence based on price position and MA spread
     * 
     * @param float $price Current price
     * @param float $fastMA Fast moving average
     * @param float $slowMA Slow moving average
     * @return float Confidence (0.0 to 1.0)
     */
    private function calculateConfidence(float $price, float $fastMA, float $slowMA): float
    {
        $baseConfidence = 0.70;
        
        // Bonus for price above fast MA (confirms trend)
        if ($price > $fastMA) {
            $baseConfidence += 0.10;
        }
        
        // Bonus for wider MA spread (stronger trend)
        $maSpread = (($fastMA - $slowMA) / $slowMA) * 100;
        if ($maSpread > 2.0) { // More than 2% spread
            $baseConfidence += 0.10;
        } elseif ($maSpread > 5.0) { // More than 5% spread
            $baseConfidence += 0.15;
        }
        
        return min(1.0, $baseConfidence);
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function canExecute(string $symbol): bool
    {
        try {
            $requiredDays = $this->getRequiredHistoricalDays();
            $priceHistory = $this->marketDataService->getHistoricalPrices($symbol, null, null, $requiredDays);
            
            return count($priceHistory) >= $requiredDays;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredHistoricalDays(): int
    {
        // Need slow period + buffer for accurate MA calculation
        return $this->parameters['slow_period'] + 20;
    }
}
