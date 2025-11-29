<?php

namespace App\Services\Trading;

use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

/**
 * Turtle Trading System
 * 
 * Implementation of the famous Turtle Trading System developed by Richard Dennis.
 * 
 * Strategy Overview:
 * - System 1: 20-day breakout entry, 10-day exit
 * - System 2: 55-day breakout entry, 20-day exit
 * - Position sizing based on volatility (ATR)
 * - Risk management: 2% per trade maximum
 * 
 * Entry Rules:
 * - BUY: Price breaks above 20-day high (System 1) or 55-day high (System 2)
 * - SHORT: Price breaks below 20-day low (System 1) or 55-day low (System 2)
 * 
 * Exit Rules:
 * - SELL: Price breaks below 10-day low (System 1) or 20-day low (System 2)
 * - COVER: Price breaks above 10-day high (System 1) or 20-day high (System 2)
 * 
 * @package App\Services\Trading
 */
class TurtleStrategyService implements TradingStrategyInterface
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
        'system' => 'BOTH',        // 'ONE', 'TWO', or 'BOTH'
        'system1_entry' => 20,     // Days for breakout entry (System 1)
        'system1_exit' => 10,      // Days for breakout exit (System 1)
        'system2_entry' => 55,     // Days for breakout entry (System 2)
        'system2_exit' => 20,      // Days for breakout exit (System 2)
        'atr_period' => 20,        // ATR period for volatility
        'risk_per_trade' => 0.02,  // 2% risk per trade
        'max_units' => 4,          // Maximum pyramiding units
        'unit_atr_multiple' => 0.5 // Add units every 0.5 ATR move
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
        return 'Turtle Trading System';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Classic trend-following strategy using price breakouts. '
             . 'Enters on 20/55-day highs, exits on 10/20-day lows. '
             . 'Position sizing based on volatility (ATR). '
             . 'System 1: 20/10 days. System 2: 55/20 days.';
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

        if (empty($priceHistory)) {
            return $this->createHoldSignal('Insufficient price data');
        }

        // Get current price
        $currentPriceData = $this->marketDataService->getCurrentPrice($symbol);
        $currentPrice = $currentPriceData['price'] ?? 0;
        
        if ($currentPrice < 0.01) {
            return $this->createHoldSignal('Invalid price data');
        }

        // Calculate signals based on configured system
        $system = $this->parameters['system'];
        
        if ($system === 'BOTH') {
            $signal1 = $this->calculateSystem1Signal($symbol, $currentPrice, $priceHistory);
            $signal2 = $this->calculateSystem2Signal($symbol, $currentPrice, $priceHistory);
            
            return $this->combineSystems($signal1, $signal2, $currentPrice, $priceHistory);
        } elseif ($system === 'ONE') {
            return $this->calculateSystem1Signal($symbol, $currentPrice, $priceHistory);
        } elseif ($system === 'TWO') {
            return $this->calculateSystem2Signal($symbol, $currentPrice, $priceHistory);
        }

        return $this->createHoldSignal('Invalid system configuration');
    }

    /**
     * Calculate System 1 signal (20-day entry, 10-day exit)
     * 
     * @param string $symbol Stock symbol
     * @param float $currentPrice Current price
     * @param array $priceHistory Historical prices
     * @return array Trading signal
     */
    private function calculateSystem1Signal(string $symbol, float $currentPrice, array $priceHistory): array
    {
        $entryDays = $this->parameters['system1_entry'];
        $exitDays = $this->parameters['system1_exit'];

        $high = $this->getHighestPrice($priceHistory, $entryDays);
        $low = $this->getLowestPrice($priceHistory, $entryDays);
        $exitHigh = $this->getHighestPrice($priceHistory, $exitDays);
        $exitLow = $this->getLowestPrice($priceHistory, $exitDays);

        // Entry signals
        if ($currentPrice > $high) {
            return $this->createBuySignal($symbol, $currentPrice, $priceHistory, 'System 1: 20-day breakout above ' . number_format($high, 2));
        }
        
        if ($currentPrice < $low) {
            return $this->createShortSignal($symbol, $currentPrice, $priceHistory, 'System 1: 20-day breakdown below ' . number_format($low, 2));
        }

        // Exit signals
        if ($currentPrice < $exitLow) {
            return $this->createSellSignal($currentPrice, 'System 1: 10-day low break ' . number_format($exitLow, 2));
        }
        
        if ($currentPrice > $exitHigh) {
            return $this->createCoverSignal($currentPrice, 'System 1: 10-day high break ' . number_format($exitHigh, 2));
        }

        return $this->createHoldSignal('System 1: No breakout');
    }

    /**
     * Calculate System 2 signal (55-day entry, 20-day exit)
     * 
     * @param string $symbol Stock symbol
     * @param float $currentPrice Current price
     * @param array $priceHistory Historical prices
     * @return array Trading signal
     */
    private function calculateSystem2Signal(string $symbol, float $currentPrice, array $priceHistory): array
    {
        $entryDays = $this->parameters['system2_entry'];
        $exitDays = $this->parameters['system2_exit'];

        $high = $this->getHighestPrice($priceHistory, $entryDays);
        $low = $this->getLowestPrice($priceHistory, $entryDays);
        $exitHigh = $this->getHighestPrice($priceHistory, $exitDays);
        $exitLow = $this->getLowestPrice($priceHistory, $exitDays);

        // Entry signals
        if ($currentPrice > $high) {
            return $this->createBuySignal($symbol, $currentPrice, $priceHistory, 'System 2: 55-day breakout above ' . number_format($high, 2));
        }
        
        if ($currentPrice < $low) {
            return $this->createShortSignal($symbol, $currentPrice, $priceHistory, 'System 2: 55-day breakdown below ' . number_format($low, 2));
        }

        // Exit signals
        if ($currentPrice < $exitLow) {
            return $this->createSellSignal($currentPrice, 'System 2: 20-day low break ' . number_format($exitLow, 2));
        }
        
        if ($currentPrice > $exitHigh) {
            return $this->createCoverSignal($currentPrice, 'System 2: 20-day high break ' . number_format($exitHigh, 2));
        }

        return $this->createHoldSignal('System 2: No breakout');
    }

    /**
     * Combine System 1 and System 2 signals
     * 
     * @param array $signal1 System 1 signal
     * @param array $signal2 System 2 signal
     * @param float $currentPrice Current price
     * @param array $priceHistory Historical prices
     * @return array Combined signal
     */
    private function combineSystems(array $signal1, array $signal2, float $currentPrice, array $priceHistory): array
    {
        // If both systems agree, return with higher confidence
        if ($signal1['signal'] === $signal2['signal']) {
            $signal1['confidence'] = min(1.0, $signal1['confidence'] * 1.5);
            $signal1['reason'] = 'Both systems agree: ' . $signal1['reason'];
            return $signal1;
        }

        // If one is HOLD, return the other
        if ($signal1['signal'] === self::SIGNAL_HOLD) {
            return $signal2;
        }
        
        if ($signal2['signal'] === self::SIGNAL_HOLD) {
            return $signal1;
        }

        // Systems disagree on action - prefer System 2 (longer-term)
        $signal2['reason'] .= ' (System 1 disagrees: ' . $signal1['signal'] . ')';
        $signal2['confidence'] *= 0.7; // Reduce confidence due to disagreement
        return $signal2;
    }

    /**
     * Create a BUY signal
     */
    private function createBuySignal(string $symbol, float $currentPrice, array $priceHistory, string $reason): array
    {
        $atr = $this->calculateATR($priceHistory, $this->parameters['atr_period']);
        $stopLoss = $currentPrice - (2 * $atr);
        $takeProfit = $currentPrice + (4 * $atr);
        $positionSize = $this->calculatePositionSize($atr);

        return [
            'signal' => self::SIGNAL_BUY,
            'confidence' => 0.75,
            'reason' => $reason,
            'entry_price' => $currentPrice,
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'position_size' => $positionSize,
            'metadata' => [
                'atr' => $atr,
                'risk_reward_ratio' => 2.0,
                'max_units' => $this->parameters['max_units'],
                'unit_atr_multiple' => $this->parameters['unit_atr_multiple']
            ]
        ];
    }

    /**
     * Create a SHORT signal
     */
    private function createShortSignal(string $symbol, float $currentPrice, array $priceHistory, string $reason): array
    {
        $atr = $this->calculateATR($priceHistory, $this->parameters['atr_period']);
        $stopLoss = $currentPrice + (2 * $atr);
        $takeProfit = $currentPrice - (4 * $atr);
        $positionSize = $this->calculatePositionSize($atr);

        return [
            'signal' => self::SIGNAL_SHORT,
            'confidence' => 0.75,
            'reason' => $reason,
            'entry_price' => $currentPrice,
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'position_size' => $positionSize,
            'metadata' => [
                'atr' => $atr,
                'risk_reward_ratio' => 2.0,
                'max_units' => $this->parameters['max_units'],
                'unit_atr_multiple' => $this->parameters['unit_atr_multiple']
            ]
        ];
    }

    /**
     * Create a SELL signal
     */
    private function createSellSignal(float $currentPrice, string $reason): array
    {
        return [
            'signal' => self::SIGNAL_SELL,
            'confidence' => 0.80,
            'reason' => $reason,
            'entry_price' => $currentPrice,
            'stop_loss' => null,
            'take_profit' => null,
            'position_size' => 1.0, // Exit entire position
            'metadata' => []
        ];
    }

    /**
     * Create a COVER signal
     */
    private function createCoverSignal(float $currentPrice, string $reason): array
    {
        return [
            'signal' => self::SIGNAL_COVER,
            'confidence' => 0.80,
            'reason' => $reason,
            'entry_price' => $currentPrice,
            'stop_loss' => null,
            'take_profit' => null,
            'position_size' => 1.0, // Exit entire position
            'metadata' => []
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
     * Get highest price over specified period
     * 
     * @param array $priceHistory Price history
     * @param int $days Number of days to look back
     * @return float Highest price
     */
    private function getHighestPrice(array $priceHistory, int $days): float
    {
        $recent = array_slice($priceHistory, -$days);
        $highs = array_column($recent, 'high');
        return !empty($highs) ? max($highs) : 0.0;
    }

    /**
     * Get lowest price over specified period
     * 
     * @param array $priceHistory Price history
     * @param int $days Number of days to look back
     * @return float Lowest price
     */
    private function getLowestPrice(array $priceHistory, int $days): float
    {
        $recent = array_slice($priceHistory, -$days);
        $lows = array_column($recent, 'low');
        return !empty($lows) ? min($lows) : PHP_FLOAT_MAX;
    }

    /**
     * Calculate Average True Range (ATR)
     * 
     * @param array $priceHistory Price history
     * @param int $period ATR period
     * @return float ATR value
     */
    private function calculateATR(array $priceHistory, int $period): float
    {
        if (count($priceHistory) < $period + 1) {
            return 0.0;
        }

        $trueRanges = [];
        
        for ($i = 1; $i < count($priceHistory); $i++) {
            $high = $priceHistory[$i]['high'];
            $low = $priceHistory[$i]['low'];
            $prevClose = $priceHistory[$i - 1]['close'];

            $tr = max(
                $high - $low,
                abs($high - $prevClose),
                abs($low - $prevClose)
            );

            $trueRanges[] = $tr;
        }

        $recentTR = array_slice($trueRanges, -$period);
        return array_sum($recentTR) / count($recentTR);
    }

    /**
     * Calculate position size based on ATR and risk parameters
     * 
     * @param float $atr Average True Range
     * @return float Position size as percentage (0.0 to 1.0)
     */
    private function calculatePositionSize(float $atr): float
    {
        if ($atr <= 0) {
            return 0.01; // Minimum 1%
        }

        // Risk 2% of portfolio per trade
        // Position size = (Portfolio Risk %) / (ATR / Price)
        // Simplified: Start with 1 unit = 2% risk
        $baseSize = $this->parameters['risk_per_trade'];
        
        return min($baseSize, 0.25); // Cap at 25% of portfolio per position
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
        // Need enough data for the longest period (System 2 entry = 55 days)
        // Plus extra for ATR calculation
        return max(
            $this->parameters['system1_entry'],
            $this->parameters['system2_entry'],
            $this->parameters['atr_period']
        ) + 10; // Buffer for calculations
    }
}
