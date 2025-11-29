<?php

namespace App\Services\Trading;

use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

/**
 * Four Week Rule Strategy (Simplified Turtle Trading)
 * 
 * A simplified version of the classic Turtle Trading System developed by
 * Richard Donchian. This strategy uses 4-week (20-day) breakouts for entry
 * and exit signals.
 * 
 * Strategy Overview:
 * - Entry: Price breaks above 4-week high (20-day high)
 * - Exit: Price breaks below 4-week low (20-day low)
 * - Simpler than full Turtle System (no dual systems or pyramiding)
 * - Trend-following system that captures major price moves
 * 
 * Entry Rules:
 * - BUY: Current price breaks above highest high of past 20 days
 * - SHORT: Current price breaks below lowest low of past 20 days
 * 
 * Exit Rules:
 * - SELL: Price breaks below 20-day low (exit long)
 * - COVER: Price breaks above 20-day high (exit short)
 * 
 * Position Sizing:
 * - Uses ATR (Average True Range) for volatility-based sizing
 * - Risk per trade: configurable (default 2%)
 * - Max position size: configurable (default 25%)
 * 
 * @package App\Services\Trading
 */
class FourWeekRuleStrategyService implements TradingStrategyInterface
{
    private const SIGNAL_BUY = 'BUY';
    private const SIGNAL_SELL = 'SELL';
    private const SIGNAL_SHORT = 'SHORT';
    private const SIGNAL_COVER = 'COVER';
    private const SIGNAL_HOLD = 'HOLD';

    private MarketDataService $marketDataService;
    private MarketDataRepositoryInterface $marketDataRepository;
    
    /**
     * Strategy parameters
     * 
     * @var array
     */
    private array $parameters = [
        'entry_period' => 20,           // 4-week entry period (days)
        'exit_period' => 20,            // 4-week exit period (days)
        'atr_period' => 20,             // ATR calculation period
        'risk_per_trade' => 0.02,       // 2% risk per trade
        'max_position_size' => 0.25,    // Max 25% position
        'atr_multiplier' => 2.0,        // ATR multiplier for stops
        'stop_loss_percent' => 0.08,    // 8% stop loss fallback
        'take_profit_percent' => 0.20   // 20% take profit target
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
        return 'Four Week Rule';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Simplified Turtle Trading system using 4-week (20-day) breakouts. '
             . 'Enters on new highs/lows over past 20 days, exits on opposite breakout. '
             . 'Uses ATR-based position sizing and risk management.';
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
            return $this->createHoldSignal('Insufficient price data for breakout calculation');
        }

        // Get current price
        $currentPriceData = $this->marketDataService->getCurrentPrice($symbol);
        $currentPrice = $currentPriceData['price'] ?? 0;
        
        if ($currentPrice < 0.01) {
            return $this->createHoldSignal('Invalid price data');
        }

        // Calculate entry breakout levels
        $entryPeriodHigh = $this->getHighestPrice($priceHistory, $this->parameters['entry_period']);
        $entryPeriodLow = $this->getLowestPrice($priceHistory, $this->parameters['entry_period']);
        
        // Calculate exit breakout levels
        $exitPeriodHigh = $this->getHighestPrice($priceHistory, $this->parameters['exit_period']);
        $exitPeriodLow = $this->getLowestPrice($priceHistory, $this->parameters['exit_period']);

        if ($entryPeriodHigh === null || $entryPeriodLow === null) {
            return $this->createHoldSignal('Unable to calculate breakout levels');
        }

        // Calculate ATR for position sizing and stops
        $atr = $this->calculateATR($priceHistory, $this->parameters['atr_period']);
        $atrStopDistance = $atr * $this->parameters['atr_multiplier'];

        // Check for entry signals
        if ($currentPrice > $entryPeriodHigh) {
            // Bullish breakout - new 20-day high
            $positionSize = $this->calculatePositionSize($currentPrice, $atr);
            $stopLoss = $currentPrice - $atrStopDistance;
            $takeProfit = $currentPrice * (1 + $this->parameters['take_profit_percent']);
            
            return $this->createBuySignal(
                $symbol,
                $currentPrice,
                $stopLoss,
                $takeProfit,
                $positionSize,
                $entryPeriodHigh,
                $atr,
                'Bullish breakout: Price ' . number_format($currentPrice, 2) 
                . ' broke above 20-day high of ' . number_format($entryPeriodHigh, 2)
            );
        }

        if ($currentPrice < $entryPeriodLow) {
            // Bearish breakout - new 20-day low
            $positionSize = $this->calculatePositionSize($currentPrice, $atr);
            $stopLoss = $currentPrice + $atrStopDistance;
            $takeProfit = $currentPrice * (1 - $this->parameters['take_profit_percent']);
            
            return $this->createShortSignal(
                $symbol,
                $currentPrice,
                $stopLoss,
                $takeProfit,
                $positionSize,
                $entryPeriodLow,
                $atr,
                'Bearish breakout: Price ' . number_format($currentPrice, 2) 
                . ' broke below 20-day low of ' . number_format($entryPeriodLow, 2)
            );
        }

        // Check for exit signals (if already in position)
        if ($exitPeriodLow !== null && $currentPrice < $exitPeriodLow) {
            // Exit long position
            return $this->createSellSignal(
                $currentPrice,
                $exitPeriodLow,
                'Exit long: Price ' . number_format($currentPrice, 2) 
                . ' broke below exit low of ' . number_format($exitPeriodLow, 2)
            );
        }

        if ($exitPeriodHigh !== null && $currentPrice > $exitPeriodHigh) {
            // Cover short position
            return $this->createCoverSignal(
                $currentPrice,
                $exitPeriodHigh,
                'Cover short: Price ' . number_format($currentPrice, 2) 
                . ' broke above exit high of ' . number_format($exitPeriodHigh, 2)
            );
        }

        // No breakout - hold current position or stay out
        $distanceToHigh = (($entryPeriodHigh - $currentPrice) / $currentPrice) * 100;
        $distanceToLow = (($currentPrice - $entryPeriodLow) / $currentPrice) * 100;
        
        return $this->createHoldSignal(
            'No breakout: Price within range. ' 
            . number_format($distanceToHigh, 1) . '% below high, '
            . number_format($distanceToLow, 1) . '% above low'
        );
    }

    /**
     * Calculate Average True Range (ATR)
     * 
     * @param array $priceHistory Historical price data
     * @param int $period ATR period
     * @return float ATR value
     */
    private function calculateATR(array $priceHistory, int $period): float
    {
        $trueRanges = [];
        
        for ($i = 1; $i < count($priceHistory); $i++) {
            $high = $priceHistory[$i]['high'];
            $low = $priceHistory[$i]['low'];
            $prevClose = $priceHistory[$i - 1]['close'];
            
            $trueRange = max(
                $high - $low,
                abs($high - $prevClose),
                abs($low - $prevClose)
            );
            
            $trueRanges[] = $trueRange;
        }
        
        // Get last N true ranges
        $recentTR = array_slice($trueRanges, -$period);
        
        return array_sum($recentTR) / count($recentTR);
    }

    /**
     * Calculate position size based on ATR and risk parameters
     * 
     * @param float $price Current price
     * @param float $atr Average True Range
     * @return float Position size as decimal (e.g., 0.05 = 5%)
     */
    private function calculatePositionSize(float $price, float $atr): float
    {
        if ($atr <= 0 || $price <= 0) {
            return $this->parameters['risk_per_trade'];
        }

        // Calculate risk-based position size
        // Risk per trade / ATR stop distance percentage
        $atrStopPercent = ($atr * $this->parameters['atr_multiplier']) / $price;
        $positionSize = $this->parameters['risk_per_trade'] / $atrStopPercent;
        
        // Cap at max position size
        return min($positionSize, $this->parameters['max_position_size']);
    }

    /**
     * Get highest price over specified period
     * 
     * @param array $priceHistory Price history
     * @param int $period Number of days
     * @return float|null Highest price or null if insufficient data
     */
    private function getHighestPrice(array $priceHistory, int $period): ?float
    {
        if (count($priceHistory) < $period) {
            return null;
        }

        $recentPrices = array_slice($priceHistory, -$period);
        $highs = array_column($recentPrices, 'high');
        
        return max($highs);
    }

    /**
     * Get lowest price over specified period
     * 
     * @param array $priceHistory Price history
     * @param int $period Number of days
     * @return float|null Lowest price or null if insufficient data
     */
    private function getLowestPrice(array $priceHistory, int $period): ?float
    {
        if (count($priceHistory) < $period) {
            return null;
        }

        $recentPrices = array_slice($priceHistory, -$period);
        $lows = array_column($recentPrices, 'low');
        
        return min($lows);
    }

    /**
     * Create a BUY signal
     */
    private function createBuySignal(
        string $symbol,
        float $price,
        float $stopLoss,
        float $takeProfit,
        float $positionSize,
        float $breakoutLevel,
        float $atr,
        string $reason
    ): array {
        return [
            'signal' => self::SIGNAL_BUY,
            'confidence' => 0.80,
            'reason' => $reason,
            'entry_price' => $price,
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'position_size' => $positionSize,
            'metadata' => [
                'breakout_level' => $breakoutLevel,
                'entry_period' => $this->parameters['entry_period'],
                'atr' => $atr,
                'atr_stop_distance' => $atr * $this->parameters['atr_multiplier'],
                'risk_reward_ratio' => ($takeProfit - $price) / ($price - $stopLoss)
            ]
        ];
    }

    /**
     * Create a SHORT signal
     */
    private function createShortSignal(
        string $symbol,
        float $price,
        float $stopLoss,
        float $takeProfit,
        float $positionSize,
        float $breakoutLevel,
        float $atr,
        string $reason
    ): array {
        return [
            'signal' => self::SIGNAL_SHORT,
            'confidence' => 0.80,
            'reason' => $reason,
            'entry_price' => $price,
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'position_size' => $positionSize,
            'metadata' => [
                'breakout_level' => $breakoutLevel,
                'entry_period' => $this->parameters['entry_period'],
                'atr' => $atr,
                'atr_stop_distance' => $atr * $this->parameters['atr_multiplier'],
                'risk_reward_ratio' => ($price - $takeProfit) / ($stopLoss - $price)
            ]
        ];
    }

    /**
     * Create a SELL signal
     */
    private function createSellSignal(float $price, float $exitLevel, string $reason): array
    {
        return [
            'signal' => self::SIGNAL_SELL,
            'confidence' => 0.90,
            'reason' => $reason,
            'entry_price' => $price,
            'stop_loss' => null,
            'take_profit' => null,
            'position_size' => 1.0, // Exit entire position
            'metadata' => [
                'exit_level' => $exitLevel,
                'exit_period' => $this->parameters['exit_period']
            ]
        ];
    }

    /**
     * Create a COVER signal
     */
    private function createCoverSignal(float $price, float $exitLevel, string $reason): array
    {
        return [
            'signal' => self::SIGNAL_COVER,
            'confidence' => 0.90,
            'reason' => $reason,
            'entry_price' => $price,
            'stop_loss' => null,
            'take_profit' => null,
            'position_size' => 1.0, // Exit entire position
            'metadata' => [
                'exit_level' => $exitLevel,
                'exit_period' => $this->parameters['exit_period']
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
        // Need entry period + buffer for calculation
        return max($this->parameters['entry_period'], $this->parameters['atr_period']) + 5;
    }
}
