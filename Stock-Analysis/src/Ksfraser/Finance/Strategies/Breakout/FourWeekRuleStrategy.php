<?php
namespace Ksfraser\Finance\Strategies\Breakout;

use Ksfraser\Finance\Interfaces\TradingStrategyInterface;
use Ksfraser\Finance\Services\StockDataService;
use Ksfraser\Finance\Constants\StrategyConstants;

/**
 * Four Week Rule Strategy
 * 
 * Refactored from 2000/strategies/fourweekrule.php
 * Buy if price exceeds highs of four preceding weeks, sell if falls below lows
 */
class FourWeekRuleStrategy implements TradingStrategyInterface
{
    private StockDataService $stockDataService;
    private array $parameters;

    public function __construct(StockDataService $stockDataService, array $parameters = [])
    {
        $this->stockDataService = $stockDataService;
        $this->parameters = array_merge([
            'breakout_period' => 28,        // 4 weeks = 28 trading days (original: 20)
            'confirmation_bars' => 1,       // Bars to confirm breakout
            'volume_confirmation' => false,  // Require volume confirmation
            'volume_multiplier' => 1.5,     // Volume must be 50% above average
            'atr_period' => 14,             // ATR calculation for stops
            'stop_loss_atr' => 2.0,         // Stop loss in ATR multiples
            'trailing_stop' => false,       // Use trailing stop
            'min_price' => 5.0,             // Minimum stock price filter
            'max_gap' => 0.05,              // Maximum gap up/down (5%)
            'trend_filter' => false,        // Apply longer-term trend filter
            'trend_period' => 50            // Period for trend filter
        ], $parameters);
    }

    public function generateSignal(string $symbol, ?array $marketData = null): ?array
    {
        if (!$marketData) {
            $marketData = $this->stockDataService->getStockData($symbol, '6m');
        }

        $requiredPeriod = max($this->parameters['breakout_period'], $this->parameters['trend_period']) + 5;
        if (empty($marketData) || count($marketData) < $requiredPeriod) {
            return null;
        }

        $latest = end($marketData);
        $currentPrice = $latest['close'];

        // Price filter
        if ($currentPrice < $this->parameters['min_price']) {
            return null;
        }

        // Get four-week high and low (refactored from original closehigherthan20high/closelowerthan20low)
        $breakoutSignal = $this->checkFourWeekBreakout($marketData, $currentPrice);
        
        if (!$breakoutSignal) {
            return null;
        }

        // Apply additional filters and validations
        $signal = $this->validateBreakoutSignal($breakoutSignal, $currentPrice, $marketData);
        
        return $signal;
    }

    /**
     * Check for four-week rule breakout (refactored from original logic)
     * Original: closehigherthan20high() and closelowerthan20low()
     */
    private function checkFourWeekBreakout(array $marketData, float $currentPrice): ?string
    {
        $breakoutPeriod = $this->parameters['breakout_period'];
        
        // Get the breakout period data (excluding current day)
        $lookbackData = array_slice($marketData, -($breakoutPeriod + 1), $breakoutPeriod);
        
        if (count($lookbackData) < $breakoutPeriod) {
            return null;
        }

        // Calculate four-week high and low
        $fourWeekHigh = max(array_column($lookbackData, 'high'));
        $fourWeekLow = min(array_column($lookbackData, 'low'));

        // Original logic: "Buy if price exceeds highs of four preceding weeks"
        if ($currentPrice > $fourWeekHigh) {
            return 'BUY';
        }
        
        // Original logic: "liquidate when price falls below lows of four preceding weeks"
        if ($currentPrice < $fourWeekLow) {
            return 'SELL';
        }

        return null; // HOLD - do nothing
    }

    /**
     * Validate and enhance the breakout signal
     */
    private function validateBreakoutSignal(string $breakoutSignal, float $currentPrice, array $marketData): ?array
    {
        $confirmations = [];
        $confidence = 0.7; // Base confidence for four-week breakout

        // Get four-week levels for reference
        $breakoutPeriod = $this->parameters['breakout_period'];
        $lookbackData = array_slice($marketData, -($breakoutPeriod + 1), $breakoutPeriod);
        $fourWeekHigh = max(array_column($lookbackData, 'high'));
        $fourWeekLow = min(array_column($lookbackData, 'low'));

        // Calculate gap size
        $previousClose = $marketData[count($marketData) - 2]['close'];
        $gapSize = abs($currentPrice - $previousClose) / $previousClose;
        
        // Gap filter - avoid excessive gaps
        if ($gapSize > $this->parameters['max_gap']) {
            $confidence -= 0.2;
            $confirmations[] = "Large gap (" . number_format($gapSize * 100, 1) . "%)";
        }

        // Volume confirmation
        if ($this->parameters['volume_confirmation']) {
            $volumeConfirmed = $this->checkVolumeConfirmation($marketData);
            if ($volumeConfirmed) {
                $confirmations[] = "Volume confirmed";
                $confidence += 0.1;
            } else {
                $confidence -= 0.15;
            }
        }

        // Trend filter
        if ($this->parameters['trend_filter']) {
            $trendAligned = $this->checkTrendAlignment($marketData, $breakoutSignal);
            if ($trendAligned) {
                $confirmations[] = "Trend aligned";
                $confidence += 0.1;
            } else {
                $confidence -= 0.1;
            }
        }

        // Breakout strength
        if ($breakoutSignal === 'BUY') {
            $breakoutStrength = ($currentPrice - $fourWeekHigh) / $fourWeekHigh;
            if ($breakoutStrength > 0.01) { // > 1% above high
                $confirmations[] = "Strong breakout";
                $confidence += 0.05;
            }
        } else {
            $breakoutStrength = ($fourWeekLow - $currentPrice) / $fourWeekLow;
            if ($breakoutStrength > 0.01) { // > 1% below low
                $confirmations[] = "Strong breakdown";
                $confidence += 0.05;
            }
        }

        // Only return signal if confidence is sufficient
        if ($confidence < 0.6) {
            return null;
        }

        // Calculate stop loss and position sizing
        $atr = $this->calculateATR($marketData, $this->parameters['atr_period']);
        $stopLoss = $this->calculateStopLoss($currentPrice, $breakoutSignal, $atr, $fourWeekHigh, $fourWeekLow);

        $reasoning = "Four Week Rule: ";
        if ($breakoutSignal === 'BUY') {
            $reasoning .= "Close {$currentPrice} > 4-week high {$fourWeekHigh}";
        } else {
            $reasoning .= "Close {$currentPrice} < 4-week low {$fourWeekLow}";
        }
        
        if (!empty($confirmations)) {
            $reasoning .= ". " . implode(', ', $confirmations);
        }

        return [
            'action' => $breakoutSignal,
            'price' => $currentPrice,
            'stop_loss' => $stopLoss,
            'confidence' => $confidence,
            'reasoning' => $reasoning,
            'four_week_high' => $fourWeekHigh,
            'four_week_low' => $fourWeekLow,
            'breakout_strength' => ($breakoutSignal === 'BUY' ? 
                ($currentPrice - $fourWeekHigh) / $fourWeekHigh : 
                ($fourWeekLow - $currentPrice) / $fourWeekLow) * 100,
            'atr' => $atr,
            'gap_size' => $gapSize * 100,
            'confirmations' => $confirmations
        ];
    }

    /**
     * Check volume confirmation
     */
    private function checkVolumeConfirmation(array $marketData): bool
    {
        $latest = end($marketData);
        $recentVolumes = array_column(array_slice($marketData, -20), 'volume');
        $avgVolume = array_sum($recentVolumes) / count($recentVolumes);
        
        return $latest['volume'] > $avgVolume * $this->parameters['volume_multiplier'];
    }

    /**
     * Check trend alignment
     */
    private function checkTrendAlignment(array $marketData, string $signal): bool
    {
        $trendPeriod = $this->parameters['trend_period'];
        $trendData = array_slice($marketData, -$trendPeriod);
        
        $firstPrice = $trendData[0]['close'];
        $lastPrice = end($trendData)['close'];
        $trendDirection = ($lastPrice - $firstPrice) / $firstPrice;
        
        if ($signal === 'BUY') {
            return $trendDirection > 0; // Only buy in uptrend
        } else {
            return $trendDirection < 0; // Only sell in downtrend
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
    private function calculateStopLoss(float $currentPrice, string $signal, float $atr, float $fourWeekHigh, float $fourWeekLow): float
    {
        $atrStopLoss = $currentPrice + ($signal === 'BUY' ? -1 : 1) * ($this->parameters['stop_loss_atr'] * $atr);
        
        if ($signal === 'BUY') {
            // For buys, use the more conservative of ATR stop or four-week low
            return max($atrStopLoss, $fourWeekLow);
        } else {
            // For sells, use the more conservative of ATR stop or four-week high
            return min($atrStopLoss, $fourWeekHigh);
        }
    }

    public function getName(): string
    {
        return "Four Week Rule Strategy";
    }

    public function getDescription(): string
    {
        return "Classic Four Week Rule: Buy when price exceeds 4-week high, sell when it falls below 4-week low. " .
               "Enhanced with volume confirmation and trend filters.";
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
        $required = ['breakout_period'];
        
        foreach ($required as $param) {
            if (!isset($parameters[$param]) || !is_numeric($parameters[$param]) || $parameters[$param] <= 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Legacy method for compatibility with original fourweekrule function
     */
    public function fourweekrule(string $symbol, string $date): int
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

    /**
     * Method to check if close is higher than 20-day high (original functionality)
     */
    public function closehigherthan20high(string $symbol, string $date): bool
    {
        $signal = $this->generateSignal($symbol);
        return $signal && $signal['action'] === 'BUY';
    }

    /**
     * Method to check if close is lower than 20-day low (original functionality)
     */
    public function closelowerthan20low(string $symbol, string $date): bool
    {
        $signal = $this->generateSignal($symbol);
        return $signal && $signal['action'] === 'SELL';
    }
}
