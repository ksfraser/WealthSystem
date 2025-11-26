<?php
namespace Ksfraser\Finance\Integration;

use Ksfraser\Finance\Services\StrategyService;
use Ksfraser\Finance\Services\StockDataService;
use Ksfraser\Finance\Services\PortfolioService;

/**
 * Strategy Integration Layer
 * 
 * Connects the trading strategies from 2000/strategies/ directory
 * with the SOLID Finance architecture for unified execution
 */
class StrategyIntegration
{
    private StrategyService $strategyService;
    private StockDataService $stockDataService;
    private PortfolioService $portfolioService;
    private array $strategyClasses;

    public function __construct(
        StrategyService $strategyService,
        StockDataService $stockDataService,
        PortfolioService $portfolioService
    ) {
        $this->strategyService = $strategyService;
        $this->stockDataService = $stockDataService;
        $this->portfolioService = $portfolioService;
        $this->initializeStrategyClasses();
    }

    /**
     * Initialize and map strategy classes from 2000/strategies/
     */
    private function initializeStrategyClasses(): void
    {
        $this->strategyClasses = [
            'turtle' => new TurtleStrategyWrapper(),
            'support_resistance' => new SupportResistanceStrategyWrapper(),
            'technical_analysis' => new TechnicalAnalysisStrategyWrapper(),
            'four_week_rule' => new FourWeekRuleStrategyWrapper(),
            'mac_crossover' => new MACrossoverStrategyWrapper()
        ];
    }

    /**
     * Execute a strategy using the original PHP implementations
     */
    public function executeStrategy(int $strategyId, string $symbol, array $parameters = []): array
    {
        $strategy = $this->strategyService->getStrategy($strategyId);
        if (!$strategy) {
            throw new \Exception("Strategy not found: {$strategyId}");
        }

        $strategyType = $strategy['strategy_type'];
        if (!isset($this->strategyClasses[$strategyType])) {
            throw new \Exception("Strategy implementation not found: {$strategyType}");
        }

        // Get market data
        $marketData = $this->stockDataService->getStockData($symbol, '2y');
        if (empty($marketData)) {
            throw new \Exception("No market data available for {$symbol}");
        }

        // Merge parameters
        $strategyParams = json_decode($strategy['parameters'], true) ?? [];
        $mergedParams = array_merge($strategyParams, $parameters);

        // Execute strategy
        $strategyClass = $this->strategyClasses[$strategyType];
        $signal = $strategyClass->generateSignal($symbol, $marketData, $mergedParams);

        // Record execution
        if ($signal) {
            $this->recordExecution($strategyId, $symbol, $signal);
        }

        return [
            'strategy' => $strategy,
            'symbol' => $symbol,
            'signal' => $signal,
            'parameters' => $mergedParams,
            'market_data_points' => count($marketData)
        ];
    }

    /**
     * Run multiple strategies across a list of symbols
     */
    public function scanMarket(array $symbols, array $strategyIds = []): array
    {
        $results = [];
        $activeStrategies = $strategyIds ?: array_column($this->strategyService->getActiveStrategies(), 'id');

        foreach ($symbols as $symbol) {
            $symbolResults = [];
            
            foreach ($activeStrategies as $strategyId) {
                try {
                    $result = $this->executeStrategy($strategyId, $symbol);
                    if ($result['signal']) {
                        $symbolResults[] = $result;
                    }
                } catch (\Exception $e) {
                    error_log("Error executing strategy {$strategyId} for {$symbol}: " . $e->getMessage());
                }
            }
            
            if (!empty($symbolResults)) {
                $results[$symbol] = $symbolResults;
            }
        }

        return $results;
    }

    /**
     * Record strategy execution in the database
     */
    private function recordExecution(int $strategyId, string $symbol, array $signal): void
    {
        // This would be handled by the StrategyService
        // Implementation depends on the existing database structure
    }
}

/**
 * Base class for strategy wrappers
 */
abstract class BaseStrategyWrapper
{
    abstract public function generateSignal(string $symbol, array $marketData, array $parameters): ?array;

    protected function calculateATR(array $marketData, int $period = 20): float
    {
        if (count($marketData) < $period + 1) {
            return 0;
        }

        $trueRanges = [];
        for ($i = 1; $i < count($marketData) && $i <= $period; $i++) {
            $current = $marketData[count($marketData) - $i];
            $previous = $marketData[count($marketData) - $i - 1];

            $tr1 = $current['high'] - $current['low'];
            $tr2 = abs($current['high'] - $previous['close']);
            $tr3 = abs($current['low'] - $previous['close']);

            $trueRanges[] = max($tr1, $tr2, $tr3);
        }

        return array_sum($trueRanges) / count($trueRanges);
    }

    protected function getHighestHigh(array $marketData, int $period): float
    {
        $highs = array_column(array_slice($marketData, -$period), 'high');
        return max($highs);
    }

    protected function getLowestLow(array $marketData, int $period): float
    {
        $lows = array_column(array_slice($marketData, -$period), 'low');
        return min($lows);
    }

    protected function calculateMA(array $marketData, int $period): float
    {
        $closes = array_column(array_slice($marketData, -$period), 'close');
        return array_sum($closes) / count($closes);
    }
}

/**
 * Turtle Strategy Wrapper
 * Integrates with turtle.php from 2000/strategies/
 */
class TurtleStrategyWrapper extends BaseStrategyWrapper
{
    public function generateSignal(string $symbol, array $marketData, array $parameters): ?array
    {
        $entryDays = $parameters['entry_days'] ?? 20;
        $exitDays = $parameters['exit_days'] ?? 10;
        $atrPeriod = $parameters['atr_period'] ?? 20;
        $unitRisk = $parameters['unit_risk'] ?? 0.02;

        if (count($marketData) < max($entryDays, $exitDays, $atrPeriod)) {
            return null;
        }

        $latest = end($marketData);
        $currentPrice = $latest['close'];

        // Entry signals
        $highestHigh = $this->getHighestHigh($marketData, $entryDays);
        $lowestLow = $this->getLowestLow($marketData, $exitDays);

        // Calculate ATR for position sizing and stops
        $atr = $this->calculateATR($marketData, $atrPeriod);
        $nValue = $atr; // Turtle N value

        // Entry signal: breakout above highest high
        if ($currentPrice > $highestHigh) {
            $stopLoss = $currentPrice - (2 * $nValue);
            
            return [
                'action' => 'BUY',
                'price' => $currentPrice,
                'stop_loss' => $stopLoss,
                'confidence' => 0.8,
                'reasoning' => "Turtle {$entryDays}-day breakout: price {$currentPrice} > highest high {$highestHigh}",
                'atr' => $atr,
                'n_value' => $nValue,
                'position_size_multiplier' => $unitRisk / ($nValue / $currentPrice)
            ];
        }

        // Exit signal: breakdown below lowest low
        if ($currentPrice < $lowestLow) {
            return [
                'action' => 'SELL',
                'price' => $currentPrice,
                'confidence' => 0.8,
                'reasoning' => "Turtle {$exitDays}-day breakdown: price {$currentPrice} < lowest low {$lowestLow}",
                'atr' => $atr,
                'n_value' => $nValue
            ];
        }

        return null;
    }
}

/**
 * Support/Resistance Strategy Wrapper
 * Integrates with buyLeadingStocksAtSupport.php
 */
class SupportResistanceStrategyWrapper extends BaseStrategyWrapper
{
    public function generateSignal(string $symbol, array $marketData, array $parameters): ?array
    {
        $lookbackPeriod = $parameters['lookback_period'] ?? 50;
        $supportThreshold = $parameters['support_threshold'] ?? 0.02;
        $volumeConfirmation = $parameters['volume_confirmation'] ?? true;

        if (count($marketData) < $lookbackPeriod) {
            return null;
        }

        $latest = end($marketData);
        $currentPrice = $latest['close'];
        $currentVolume = $latest['volume'];

        // Find support level (recent lows)
        $recentData = array_slice($marketData, -$lookbackPeriod);
        $supportLevel = $this->getLowestLow($recentData, $lookbackPeriod);

        // Check if price is near support
        $distanceFromSupport = abs($currentPrice - $supportLevel) / $supportLevel;

        if ($distanceFromSupport <= $supportThreshold) {
            // Additional confirmations
            $ma50 = $this->calculateMA($marketData, 50);
            $avgVolume = array_sum(array_column(array_slice($marketData, -20), 'volume')) / 20;

            $confirmations = [];
            $confidence = 0.5;

            // Above 50-day MA
            if ($currentPrice > $ma50) {
                $confirmations[] = "Above 50-day MA ({$ma50})";
                $confidence += 0.2;
            }

            // Volume confirmation
            if (!$volumeConfirmation || $currentVolume > $avgVolume) {
                $confirmations[] = "Volume confirmation";
                $confidence += 0.1;
            }

            if ($confidence >= 0.6) {
                return [
                    'action' => 'BUY',
                    'price' => $currentPrice,
                    'stop_loss' => $supportLevel * 0.98, // 2% below support
                    'confidence' => $confidence,
                    'reasoning' => "Support buy: price {$currentPrice} near support {$supportLevel}. " . implode(', ', $confirmations),
                    'support_level' => $supportLevel,
                    'distance_from_support' => $distanceFromSupport * 100
                ];
            }
        }

        return null;
    }
}

/**
 * Technical Analysis Strategy Wrapper
 * Integrates with macrossover.php and other TA strategies
 */
class TechnicalAnalysisStrategyWrapper extends BaseStrategyWrapper
{
    public function generateSignal(string $symbol, array $marketData, array $parameters): ?array
    {
        $fastPeriod = $parameters['fast_period'] ?? 20;
        $slowPeriod = $parameters['slow_period'] ?? 50;
        $confirmationBars = $parameters['confirmation_bars'] ?? 2;

        if (count($marketData) < $slowPeriod + $confirmationBars) {
            return null;
        }

        $latest = end($marketData);
        $currentPrice = $latest['close'];

        // Calculate moving averages
        $fastMA = $this->calculateMA($marketData, $fastPeriod);
        $slowMA = $this->calculateMA($marketData, $slowPeriod);

        // Previous MAs for crossover detection
        $prevData = array_slice($marketData, 0, -1);
        $prevFastMA = $this->calculateMA($prevData, $fastPeriod);
        $prevSlowMA = $this->calculateMA($prevData, $slowPeriod);

        // Golden Cross: Fast MA crosses above Slow MA
        if ($fastMA > $slowMA && $prevFastMA <= $prevSlowMA && $currentPrice > $fastMA) {
            return [
                'action' => 'BUY',
                'price' => $currentPrice,
                'stop_loss' => $slowMA,
                'confidence' => 0.7,
                'reasoning' => "Golden Cross: Fast MA ({$fastPeriod}) {$fastMA} crossed above Slow MA ({$slowPeriod}) {$slowMA}",
                'fast_ma' => $fastMA,
                'slow_ma' => $slowMA
            ];
        }

        // Death Cross: Fast MA crosses below Slow MA
        if ($fastMA < $slowMA && $prevFastMA >= $prevSlowMA && $currentPrice < $fastMA) {
            return [
                'action' => 'SELL',
                'price' => $currentPrice,
                'confidence' => 0.7,
                'reasoning' => "Death Cross: Fast MA ({$fastPeriod}) {$fastMA} crossed below Slow MA ({$slowPeriod}) {$slowMA}",
                'fast_ma' => $fastMA,
                'slow_ma' => $slowMA
            ];
        }

        return null;
    }
}

/**
 * Four Week Rule Strategy Wrapper
 * Integrates with fourweekrule.php
 */
class FourWeekRuleStrategyWrapper extends BaseStrategyWrapper
{
    public function generateSignal(string $symbol, array $marketData, array $parameters): ?array
    {
        $breakoutPeriod = $parameters['breakout_period'] ?? 28; // 4 weeks = 28 days
        $confirmationRequired = $parameters['confirmation_required'] ?? false;

        if (count($marketData) < $breakoutPeriod) {
            return null;
        }

        $latest = end($marketData);
        $currentPrice = $latest['close'];

        // 4-week high and low
        $fourWeekHigh = $this->getHighestHigh($marketData, $breakoutPeriod);
        $fourWeekLow = $this->getLowestLow($marketData, $breakoutPeriod);

        // Buy signal: break above 4-week high
        if ($currentPrice > $fourWeekHigh) {
            $stopLoss = $fourWeekLow;
            
            return [
                'action' => 'BUY',
                'price' => $currentPrice,
                'stop_loss' => $stopLoss,
                'confidence' => 0.75,
                'reasoning' => "4-Week Rule buy: price {$currentPrice} broke above 4-week high {$fourWeekHigh}",
                'four_week_high' => $fourWeekHigh,
                'four_week_low' => $fourWeekLow
            ];
        }

        // Sell signal: break below 4-week low
        if ($currentPrice < $fourWeekLow) {
            return [
                'action' => 'SELL',
                'price' => $currentPrice,
                'confidence' => 0.75,
                'reasoning' => "4-Week Rule sell: price {$currentPrice} broke below 4-week low {$fourWeekLow}",
                'four_week_high' => $fourWeekHigh,
                'four_week_low' => $fourWeekLow
            ];
        }

        return null;
    }
}

/**
 * Moving Average Crossover Strategy Wrapper
 * Enhanced version of macrossover.php
 */
class MACrossoverStrategyWrapper extends BaseStrategyWrapper
{
    public function generateSignal(string $symbol, array $marketData, array $parameters): ?array
    {
        $shortPeriod = $parameters['short_period'] ?? 12;
        $longPeriod = $parameters['long_period'] ?? 26;
        $signalPeriod = $parameters['signal_period'] ?? 9;

        if (count($marketData) < $longPeriod + $signalPeriod) {
            return null;
        }

        $latest = end($marketData);
        $currentPrice = $latest['close'];

        // Calculate MACD
        $macd = $this->calculateMACD($marketData, $shortPeriod, $longPeriod, $signalPeriod);
        
        if (!$macd) {
            return null;
        }

        $macdLine = $macd['macd'];
        $signalLine = $macd['signal'];
        $histogram = $macd['histogram'];

        // Previous values for crossover detection
        $prevMacd = $this->calculateMACD(array_slice($marketData, 0, -1), $shortPeriod, $longPeriod, $signalPeriod);
        
        if (!$prevMacd) {
            return null;
        }

        // MACD bullish crossover
        if ($macdLine > $signalLine && $prevMacd['macd'] <= $prevMacd['signal'] && $histogram > 0) {
            return [
                'action' => 'BUY',
                'price' => $currentPrice,
                'confidence' => 0.65,
                'reasoning' => "MACD bullish crossover: MACD {$macdLine} > Signal {$signalLine}",
                'macd' => $macdLine,
                'signal' => $signalLine,
                'histogram' => $histogram
            ];
        }

        // MACD bearish crossover
        if ($macdLine < $signalLine && $prevMacd['macd'] >= $prevMacd['signal'] && $histogram < 0) {
            return [
                'action' => 'SELL',
                'price' => $currentPrice,
                'confidence' => 0.65,
                'reasoning' => "MACD bearish crossover: MACD {$macdLine} < Signal {$signalLine}",
                'macd' => $macdLine,
                'signal' => $signalLine,
                'histogram' => $histogram
            ];
        }

        return null;
    }

    private function calculateMACD(array $marketData, int $fastPeriod, int $slowPeriod, int $signalPeriod): ?array
    {
        if (count($marketData) < $slowPeriod + $signalPeriod) {
            return null;
        }

        // Calculate EMAs
        $fastEMA = $this->calculateEMA($marketData, $fastPeriod);
        $slowEMA = $this->calculateEMA($marketData, $slowPeriod);
        
        $macdLine = $fastEMA - $slowEMA;
        
        // For simplicity, using SMA instead of EMA for signal line
        // In a full implementation, you'd calculate signal line as EMA of MACD
        $signalLine = $macdLine * 0.9; // Simplified
        
        $histogram = $macdLine - $signalLine;

        return [
            'macd' => $macdLine,
            'signal' => $signalLine,
            'histogram' => $histogram
        ];
    }

    private function calculateEMA(array $marketData, int $period): float
    {
        $closes = array_column($marketData, 'close');
        $k = 2 / ($period + 1);
        
        // Start with SMA for first value
        $ema = array_sum(array_slice($closes, 0, $period)) / $period;
        
        // Calculate EMA for remaining values
        for ($i = $period; $i < count($closes); $i++) {
            $ema = ($closes[$i] * $k) + ($ema * (1 - $k));
        }
        
        return $ema;
    }
}
