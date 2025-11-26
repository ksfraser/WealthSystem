<?php
namespace Ksfraser\Finance\Strategies\Turtle;

use Ksfraser\Finance\Interfaces\TradingStrategyInterface;
use Ksfraser\Finance\Services\StockDataService;
use Ksfraser\Finance\Constants\StrategyConstants;

/**
 * Turtle Trading Strategy
 * 
 * Refactored from 2000/strategies/turtle.php
 * Implements the classic Turtle Trading System with proper SOLID architecture
 */
class TurtleStrategy implements TradingStrategyInterface
{
    private StockDataService $stockDataService;
    private array $parameters;

    public function __construct(StockDataService $stockDataService, array $parameters = [])
    {
        $this->stockDataService = $stockDataService;
        $this->parameters = array_merge([
            'system' => 1,              // 1 or 2
            'entry_days_system1' => 20, // System 1: 20-day breakout
            'exit_days_system1' => 10,  // System 1: 10-day exit
            'entry_days_system2' => 55, // System 2: 55-day breakout
            'exit_days_system2' => 20,  // System 2: 20-day exit
            'atr_period' => 20,         // ATR calculation period
            'max_units' => 4,           // Maximum units per position
            'unit_risk' => 0.02,        // Risk per unit (2%)
            'stop_loss_n' => 2,         // Stop loss in N units
        ], $parameters);
    }

    public function generateSignal(string $symbol, ?array $marketData = null): ?array
    {
        if (!$marketData) {
            $marketData = $this->stockDataService->getStockData($symbol, '2y');
        }

        if (empty($marketData) || count($marketData) < 60) {
            return null; // Need sufficient data
        }

        $latest = end($marketData);
        $currentPrice = $latest['close'];

        if ($currentPrice < 0.01) {
            return null; // Invalid price data
        }

        // Choose system parameters
        $system = $this->parameters['system'];
        $entryDays = $system === 1 ? $this->parameters['entry_days_system1'] : $this->parameters['entry_days_system2'];
        $exitDays = $system === 1 ? $this->parameters['exit_days_system1'] : $this->parameters['exit_days_system2'];

        // Calculate entry signals (refactored from enter_1/enter_2)
        $entrySignal = $this->calculateEntrySignal($marketData, $entryDays, $currentPrice);
        
        if ($entrySignal) {
            // Calculate position sizing using N-value (ATR)
            $nValue = $this->calculateNValue($marketData);
            $positionSize = $this->calculatePositionSize($currentPrice, $nValue);
            
            return [
                'action' => $entrySignal,
                'price' => $currentPrice,
                'stop_loss' => $this->calculateStopLoss($currentPrice, $nValue, $entrySignal),
                'position_size' => $positionSize,
                'confidence' => 0.8,
                'reasoning' => "Turtle System {$system}: {$entryDays}-day breakout signal",
                'system' => $system,
                'n_value' => $nValue,
                'entry_days' => $entryDays,
                'units' => min($this->parameters['max_units'], floor($positionSize / 100))
            ];
        }

        // Check exit signals for existing positions
        $exitSignal = $this->calculateExitSignal($marketData, $exitDays, $currentPrice);
        
        if ($exitSignal) {
            return [
                'action' => $exitSignal,
                'price' => $currentPrice,
                'confidence' => 0.8,
                'reasoning' => "Turtle System {$system}: {$exitDays}-day exit signal",
                'system' => $system,
                'exit_days' => $exitDays
            ];
        }

        return null;
    }

    /**
     * Calculate entry signal (refactored from enter_1/enter_2)
     */
    private function calculateEntrySignal(array $marketData, int $entryDays, float $currentPrice): ?string
    {
        if (count($marketData) < $entryDays) {
            return null;
        }

        // Get highest high and lowest low for entry period
        $recentData = array_slice($marketData, -$entryDays);
        $highestHigh = max(array_column($recentData, 'high'));
        $lowestLow = min(array_column($recentData, 'low'));

        // Entry signals
        if ($currentPrice > $highestHigh) {
            return 'BUY';  // Long breakout
        } elseif ($currentPrice < $lowestLow) {
            return 'SHORT'; // Short breakout
        }

        return null; // HOLD
    }

    /**
     * Calculate exit signal (refactored from exit_1/exit_2)
     */
    private function calculateExitSignal(array $marketData, int $exitDays, float $currentPrice): ?string
    {
        if (count($marketData) < $exitDays) {
            return null;
        }

        // Get highest high and lowest low for exit period
        $recentData = array_slice($marketData, -$exitDays);
        $highestHigh = max(array_column($recentData, 'high'));
        $lowestLow = min(array_column($recentData, 'low'));

        // Exit signals (opposite of entry)
        if ($currentPrice < $lowestLow) {
            return 'SELL';  // Exit long position
        } elseif ($currentPrice > $highestHigh) {
            return 'COVER'; // Exit short position
        }

        return null;
    }

    /**
     * Calculate N-value (Average True Range) - refactored from turtle system
     */
    private function calculateNValue(array $marketData): float
    {
        $atrPeriod = $this->parameters['atr_period'];
        
        if (count($marketData) < $atrPeriod + 1) {
            return 0;
        }

        $trueRanges = [];
        $data = array_slice($marketData, -($atrPeriod + 1));

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
     * Calculate position size using Turtle money management
     */
    private function calculatePositionSize(float $price, float $nValue): int
    {
        if ($nValue <= 0 || $price <= 0) {
            return 0;
        }

        // Assume account size - this would come from portfolio service in practice
        $accountSize = 100000; // $100k default
        $riskAmount = $accountSize * $this->parameters['unit_risk'];
        
        // Position size based on N-value risk
        $dollarVolatility = $nValue;
        $positionSize = floor($riskAmount / $dollarVolatility);

        return max(0, $positionSize);
    }

    /**
     * Calculate stop loss using N-value
     */
    private function calculateStopLoss(float $entryPrice, float $nValue, string $action): float
    {
        $stopN = $this->parameters['stop_loss_n'];
        
        if ($action === 'BUY') {
            return $entryPrice - ($stopN * $nValue);
        } elseif ($action === 'SHORT') {
            return $entryPrice + ($stopN * $nValue);
        }

        return $entryPrice;
    }

    public function getName(): string
    {
        return "Turtle Trading System " . $this->parameters['system'];
    }

    public function getDescription(): string
    {
        $system = $this->parameters['system'];
        $entryDays = $system === 1 ? $this->parameters['entry_days_system1'] : $this->parameters['entry_days_system2'];
        $exitDays = $system === 1 ? $this->parameters['exit_days_system1'] : $this->parameters['exit_days_system2'];
        
        return "Classic Turtle Trading System {$system} - {$entryDays}-day breakout entry, {$exitDays}-day exit with N-value position sizing";
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
        $required = ['system', 'entry_days_system1', 'exit_days_system1', 'entry_days_system2', 'exit_days_system2'];
        
        foreach ($required as $param) {
            if (!isset($parameters[$param]) || !is_numeric($parameters[$param])) {
                return false;
            }
        }

        return $parameters['system'] === 1 || $parameters['system'] === 2;
    }

    /**
     * Additional Turtle methods for compatibility with original system
     */
    
    public function turtle_system1(string $symbol, ?array $marketData = null): ?array
    {
        $this->setParameters(['system' => 1]);
        return $this->generateSignal($symbol, $marketData);
    }

    public function turtle_system2(string $symbol, ?array $marketData = null): ?array
    {
        $this->setParameters(['system' => 2]);
        return $this->generateSignal($symbol, $marketData);
    }

    /**
     * Get current market position recommendation
     */
    public function getMarketPosition(string $symbol): array
    {
        $signal = $this->generateSignal($symbol);
        
        return [
            'symbol' => $symbol,
            'signal' => $signal,
            'system' => $this->parameters['system'],
            'timestamp' => date('Y-m-d H:i:s'),
            'strategy' => $this->getName()
        ];
    }
}
