<?php
namespace Ksfraser\Finance\Strategies\TechnicalAnalysis;

use Ksfraser\Finance\Interfaces\TradingStrategyInterface;
use Ksfraser\Finance\Services\StockDataService;
use Ksfraser\Finance\Constants\StrategyConstants;

/**
 * Moving Average Crossover Strategy
 * 
 * Refactored from 2000/strategies/macrossover.php
 * Implements exponential moving average crossover signals with enhanced features
 */
class MovingAverageCrossoverStrategy implements TradingStrategyInterface
{
    private StockDataService $stockDataService;
    private array $parameters;

    public function __construct(StockDataService $stockDataService, array $parameters = [])
    {
        $this->stockDataService = $stockDataService;
        $this->parameters = array_merge([
            'fast_period' => 12,            // Fast EMA period (original: 12)
            'slow_period' => 26,            // Slow EMA period (original: 26)
            'signal_period' => 9,           // Signal line EMA period
            'use_ema' => true,              // Use EMA instead of SMA
            'confirmation_bars' => 1,       // Bars to confirm crossover
            'volume_confirmation' => false,  // Require volume confirmation
            'trend_filter' => false,        // Apply trend filter
            'trend_period' => 50,           // Period for trend filter
            'min_separation' => 0.005,      // Minimum separation between MAs (0.5%)
            'stop_loss_atr' => 2.0,         // Stop loss in ATR units
            'atr_period' => 14              // ATR calculation period
        ], $parameters);
    }

    public function generateSignal(string $symbol, ?array $marketData = null): ?array
    {
        if (!$marketData) {
            $marketData = $this->stockDataService->getStockData($symbol, '1y');
        }

        $requiredPeriod = max($this->parameters['slow_period'], $this->parameters['trend_period']) + 10;
        if (empty($marketData) || count($marketData) < $requiredPeriod) {
            return null;
        }

        $latest = end($marketData);
        $currentPrice = $latest['close'];

        // Calculate moving averages
        $fastMA = $this->calculateMA($marketData, $this->parameters['fast_period']);
        $slowMA = $this->calculateMA($marketData, $this->parameters['slow_period']);

        // Check for crossover (refactored from macrossoverrule logic)
        $crossoverSignal = $this->detectCrossover($marketData, $fastMA, $slowMA);
        
        if (!$crossoverSignal) {
            return null;
        }

        // Apply additional filters and confirmations
        $signal = $this->validateSignal($crossoverSignal, $currentPrice, $marketData, $fastMA, $slowMA);
        
        return $signal;
    }

    /**
     * Detect crossover signals (refactored from original macrossoverrule logic)
     * Original: "short > long == BUY, short < long == SELL"
     */
    private function detectCrossover(array $marketData, float $currentFastMA, float $currentSlowMA): ?string
    {
        $confirmationBars = $this->parameters['confirmation_bars'];
        $minSeparation = $this->parameters['min_separation'];
        
        // Calculate previous MAs for crossover detection
        $prevData = array_slice($marketData, 0, -1);
        if (count($prevData) < max($this->parameters['fast_period'], $this->parameters['slow_period'])) {
            return null;
        }
        
        $prevFastMA = $this->calculateMA($prevData, $this->parameters['fast_period']);
        $prevSlowMA = $this->calculateMA($prevData, $this->parameters['slow_period']);

        // Check separation requirement
        $currentSeparation = abs($currentFastMA - $currentSlowMA) / $currentSlowMA;
        if ($currentSeparation < $minSeparation) {
            return null; // MAs too close together
        }

        // Golden Cross: Fast MA crosses above Slow MA (BUY signal)
        if ($currentFastMA > $currentSlowMA && $prevFastMA <= $prevSlowMA) {
            return 'BUY';
        }
        
        // Death Cross: Fast MA crosses below Slow MA (SELL signal)  
        if ($currentFastMA < $currentSlowMA && $prevFastMA >= $prevSlowMA) {
            return 'SELL';
        }

        // Current state without crossover (similar to original HOLD logic)
        if ($currentFastMA > $currentSlowMA) {
            return 'BULLISH_HOLD'; // Fast above slow, but no fresh crossover
        } elseif ($currentFastMA < $currentSlowMA) {
            return 'BEARISH_HOLD'; // Fast below slow, but no fresh crossover
        }

        return null; // No clear signal
    }

    /**
     * Validate and enhance the crossover signal with additional confirmations
     */
    private function validateSignal(string $crossoverSignal, float $currentPrice, array $marketData, float $fastMA, float $slowMA): ?array
    {
        if (!in_array($crossoverSignal, ['BUY', 'SELL'])) {
            return null; // Only process clear crossover signals
        }

        $confirmations = [];
        $confidence = 0.6; // Base confidence for crossover

        // Price confirmation (price should be above/below the fast MA)
        if ($crossoverSignal === 'BUY' && $currentPrice > $fastMA) {
            $confirmations[] = "Price above fast MA";
            $confidence += 0.1;
        } elseif ($crossoverSignal === 'SELL' && $currentPrice < $fastMA) {
            $confirmations[] = "Price below fast MA";
            $confidence += 0.1;
        }

        // Volume confirmation
        if ($this->parameters['volume_confirmation']) {
            $volumeConfirmed = $this->checkVolumeConfirmation($marketData);
            if ($volumeConfirmed) {
                $confirmations[] = "Volume confirmed";
                $confidence += 0.1;
            } else {
                $confidence -= 0.1; // Reduce confidence if volume doesn't confirm
            }
        }

        // Trend filter
        if ($this->parameters['trend_filter']) {
            $trendConfirmed = $this->checkTrendFilter($marketData, $crossoverSignal);
            if ($trendConfirmed) {
                $confirmations[] = "Trend aligned";
                $confidence += 0.1;
            } else {
                $confidence -= 0.15; // Significant penalty for counter-trend trades
            }
        }

        // MA separation strength
        $separation = abs($fastMA - $slowMA) / $slowMA;
        if ($separation > 0.02) { // > 2% separation
            $confirmations[] = "Strong MA separation";
            $confidence += 0.05;
        }

        // Only return signal if confidence is sufficient
        if ($confidence < 0.5) {
            return null;
        }

        // Calculate stop loss and take profit
        $atr = $this->calculateATR($marketData, $this->parameters['atr_period']);
        $stopLoss = $this->calculateStopLoss($currentPrice, $crossoverSignal, $atr, $slowMA);
        $takeProfit = $this->calculateTakeProfit($currentPrice, $crossoverSignal, $atr);

        return [
            'action' => $crossoverSignal,
            'price' => $currentPrice,
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'confidence' => $confidence,
            'reasoning' => "MA Crossover ({$this->parameters['fast_period']}/{$this->parameters['slow_period']}): " .
                          "Fast MA {$fastMA} " . ($crossoverSignal === 'BUY' ? '>' : '<') . 
                          " Slow MA {$slowMA}. " . implode(', ', $confirmations),
            'fast_ma' => $fastMA,
            'slow_ma' => $slowMA,
            'ma_separation' => $separation * 100,
            'atr' => $atr,
            'confirmations' => $confirmations
        ];
    }

    /**
     * Calculate moving average (EMA or SMA based on parameters)
     */
    private function calculateMA(array $marketData, int $period): float
    {
        if ($this->parameters['use_ema']) {
            return $this->calculateEMA($marketData, $period);
        } else {
            return $this->calculateSMA($marketData, $period);
        }
    }

    /**
     * Calculate Exponential Moving Average (matches original 12/26 EMA logic)
     */
    private function calculateEMA(array $marketData, int $period): float
    {
        $closes = array_column($marketData, 'close');
        $k = 2 / ($period + 1); // Smoothing factor
        
        // Start with SMA for the first EMA value
        $sma = array_sum(array_slice($closes, 0, $period)) / $period;
        $ema = $sma;
        
        // Calculate EMA for remaining values
        for ($i = $period; $i < count($closes); $i++) {
            $ema = ($closes[$i] * $k) + ($ema * (1 - $k));
        }
        
        return $ema;
    }

    /**
     * Calculate Simple Moving Average
     */
    private function calculateSMA(array $marketData, int $period): float
    {
        $closes = array_column(array_slice($marketData, -$period), 'close');
        return array_sum($closes) / count($closes);
    }

    /**
     * Check volume confirmation
     */
    private function checkVolumeConfirmation(array $marketData): bool
    {
        $latest = end($marketData);
        $recentVolumes = array_column(array_slice($marketData, -20), 'volume');
        $avgVolume = array_sum($recentVolumes) / count($recentVolumes);
        
        return $latest['volume'] > $avgVolume * 1.2; // 20% above average
    }

    /**
     * Check trend filter alignment
     */
    private function checkTrendFilter(array $marketData, string $signal): bool
    {
        $trendMA = $this->calculateSMA($marketData, $this->parameters['trend_period']);
        $currentPrice = end($marketData)['close'];
        
        if ($signal === 'BUY') {
            return $currentPrice > $trendMA; // Only buy in uptrend
        } else {
            return $currentPrice < $trendMA; // Only sell in downtrend
        }
    }

    /**
     * Calculate Average True Range
     */
    private function calculateATR(array $marketData, int $period): float
    {
        if (count($marketData) < $period + 1) {
            return 0;
        }

        $trueRanges = [];
        $data = array_slice($marketData, -($period + 1));

        for ($i = 1; $i < count($data); $i++) {
            $current = $data[$i];
            $previous = $data[$i - 1];

            $tr1 = $current['high'] - $current['low'];
            $tr2 = abs($current['high'] - $previous['close']);
            $tr3 = abs($current['low'] - $previous['close']);

            $trueRanges[] = max($tr1, $tr2, $tr3);
        }

        return array_sum($trueRanges) / count($trueRanges);
    }

    /**
     * Calculate stop loss
     */
    private function calculateStopLoss(float $currentPrice, string $signal, float $atr, float $slowMA): float
    {
        $atrStopLoss = $currentPrice + ($signal === 'BUY' ? -1 : 1) * ($this->parameters['stop_loss_atr'] * $atr);
        $maStopLoss = $slowMA;
        
        // Use the more conservative stop loss
        if ($signal === 'BUY') {
            return max($atrStopLoss, $maStopLoss * 0.98); // 2% buffer below MA
        } else {
            return min($atrStopLoss, $maStopLoss * 1.02); // 2% buffer above MA
        }
    }

    /**
     * Calculate take profit target
     */
    private function calculateTakeProfit(float $currentPrice, string $signal, float $atr): float
    {
        $riskRewardRatio = 2.0; // 2:1 reward to risk
        $riskAmount = $this->parameters['stop_loss_atr'] * $atr;
        
        if ($signal === 'BUY') {
            return $currentPrice + ($riskAmount * $riskRewardRatio);
        } else {
            return $currentPrice - ($riskAmount * $riskRewardRatio);
        }
    }

    public function getName(): string
    {
        return "Moving Average Crossover Strategy";
    }

    public function getDescription(): string
    {
        $maType = $this->parameters['use_ema'] ? 'EMA' : 'SMA';
        return "Enhanced MA crossover strategy using {$this->parameters['fast_period']}/{$this->parameters['slow_period']} {$maType} " .
               "with volume and trend confirmations. Refactored from original macrossover.php logic.";
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }

    public function validateParameters(array $parameters): bool
    {
        $required = ['fast_period', 'slow_period'];
        
        foreach ($required as $param) {
            if (!isset($parameters[$param]) || !is_numeric($parameters[$param])) {
                return false;
            }
        }

        return $parameters['fast_period'] > 0 && 
               $parameters['slow_period'] > 0 && 
               $parameters['fast_period'] < $parameters['slow_period'];
    }

    /**
     * Legacy method for compatibility with original macrossoverrule function
     */
    public function macrossoverrule(string $symbol, string $date): int
    {
        $signal = $this->generateSignal($symbol);
        
        if (!$signal) {
            return StrategyConstants::HOLD;
        }

        switch ($signal['action']) {
            case 'BUY':
                return StrategyConstants::BUY;
            case 'SELL':
                return StrategyConstants::SELL;
            default:
                return StrategyConstants::HOLD;
        }
    }
}
