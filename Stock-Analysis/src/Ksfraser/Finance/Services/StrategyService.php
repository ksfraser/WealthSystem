<?php
namespace Ksfraser\Finance\Services;

use Ksfraser\Finance\Interfaces\DataRepositoryInterface;
use Ksfraser\Finance\Interfaces\TradingStrategyInterface;
use Ksfraser\Finance\Services\StockDataService;
use Ksfraser\Finance\Strategies\Turtle\TurtleStrategy;
use Ksfraser\Finance\Strategies\SupportResistance\SupportResistanceStrategy;
use Ksfraser\Finance\Strategies\TechnicalAnalysis\MovingAverageCrossoverStrategy;
use Ksfraser\Finance\Strategies\Breakout\FourWeekRuleStrategy;

class StrategyService
{
    private DataRepositoryInterface $repository;
    private StockDataService $stockDataService;

    public function __construct(DataRepositoryInterface $repository, StockDataService $stockDataService)
    {
        $this->repository = $repository;
        $this->stockDataService = $stockDataService;
    }

    public function getAllStrategies(): array
    {
        $sql = "SELECT * FROM trading_strategies ORDER BY name";
        return $this->repository->query($sql);
    }

    public function getActiveStrategies(): array
    {
        $sql = "SELECT * FROM trading_strategies WHERE is_active = 1 ORDER BY name";
        return $this->repository->query($sql);
    }

    public function getStrategy(int $strategyId): ?array
    {
        $sql = "SELECT * FROM trading_strategies WHERE id = ?";
        $result = $this->repository->query($sql, [$strategyId]);
        return $result[0] ?? null;
    }

    public function getRecentSignals(int $limit = 10): array
    {
        $sql = "
            SELECT se.*, ts.name as strategy_name 
            FROM strategy_executions se 
            JOIN trading_strategies ts ON se.strategy_id = ts.id 
            ORDER BY se.execution_date DESC 
            LIMIT ?
        ";
        return $this->repository->query($sql, [$limit]);
    }

    public function executeStrategy(int $strategyId, string $symbol, array $parameters = []): array
    {
        $strategy = $this->getStrategy($strategyId);
        if (!$strategy) {
            throw new \Exception("Strategy not found: {$strategyId}");
        }

        // Get current market data
        $marketData = $this->stockDataService->getStockData($symbol, '1y');
        if (empty($marketData)) {
            throw new \Exception("Failed to get market data for {$symbol}");
        }

        // Merge strategy parameters with custom parameters
        $strategyParams = json_decode($strategy['parameters'], true) ?? [];
        $mergedParams = array_merge($strategyParams, $parameters);

        // Execute strategy using new SOLID architecture classes
        $strategyInstance = $this->createStrategyInstance($strategy, $mergedParams);
        $signal = $strategyInstance->generateSignal($symbol, $marketData);

        // Record the execution
        if ($signal) {
            $this->recordStrategyExecution($strategyId, $symbol, $signal);
        }

        return [
            'strategy' => $strategy,
            'symbol' => $symbol,
            'signal' => $signal,
            'market_data' => array_slice($marketData, -5) // Last 5 data points
        ];
    }

    /**
     * Create strategy instance using our new SOLID architecture classes
     */
    private function createStrategyInstance(array $strategy, array $params): TradingStrategyInterface
    {
        switch ($strategy['php_class_name']) {
            case 'TurtleStrategy':
                return new \Ksfraser\Finance\Strategies\Turtle\TurtleStrategy($this->stockDataService, $params);
            case 'SupportResistanceStrategy':
                return new \Ksfraser\Finance\Strategies\SupportResistance\SupportResistanceStrategy($this->stockDataService, $params);
            case 'MovingAverageCrossoverStrategy':
            case 'MACrossoverStrategy': // Legacy name from database
                return new \Ksfraser\Finance\Strategies\TechnicalAnalysis\MovingAverageCrossoverStrategy($this->stockDataService, $params);
            case 'FourWeekRuleStrategy':
                return new \Ksfraser\Finance\Strategies\Breakout\FourWeekRuleStrategy($this->stockDataService, $params);
            default:
                // Fallback to legacy strategy type for backward compatibility
                return $this->createLegacyStrategy($strategy['strategy_type'], $params);
        }
    }

    /**
     * Fallback method for legacy strategy types
     */
    private function createLegacyStrategy(string $strategyType, array $params): TradingStrategyInterface
    {
        switch ($strategyType) {
            case 'turtle':
                return new \Ksfraser\Finance\Strategies\Turtle\TurtleStrategy($this->stockDataService, $params);
            case 'support_resistance':
                return new \Ksfraser\Finance\Strategies\SupportResistance\SupportResistanceStrategy($this->stockDataService, $params);
            case 'technical_analysis':
                return new \Ksfraser\Finance\Strategies\TechnicalAnalysis\MovingAverageCrossoverStrategy($this->stockDataService, $params);
            case 'breakout':
                return new \Ksfraser\Finance\Strategies\Breakout\FourWeekRuleStrategy($this->stockDataService, $params);
            default:
                throw new \Exception("Unknown strategy type: {$strategyType}");
        }
    }

    private function recordStrategyExecution(int $strategyId, string $symbol, array $signal): void
    {
        $sql = "
            INSERT INTO strategy_executions 
            (strategy_id, symbol, execution_date, action, signal_strength, price, reasoning)
            VALUES (?, ?, NOW(), ?, ?, ?, ?)
        ";

        $this->repository->execute($sql, [
            $strategyId,
            $symbol,
            $signal['action'],
            $signal['confidence'],
            $signal['price'],
            $signal['reasoning']
        ]);
    }

    public function updateStrategySettings(int $strategyId, array $parameters, bool $isActive): bool
    {
        $sql = "UPDATE trading_strategies SET parameters = ?, is_active = ?, updated_at = NOW() WHERE id = ?";
        
        return $this->repository->execute($sql, [
            json_encode($parameters),
            $isActive ? 1 : 0,
            $strategyId
        ]);
    }

    /**
     * Get list of available strategy classes with their capabilities
     */
    public function getAvailableStrategyClasses(): array
    {
        return [
            'TurtleStrategy' => [
                'name' => 'Turtle Trading System',
                'description' => 'Classic Turtle Trading with dual breakout systems (System 1 & 2)',
                'class' => \Ksfraser\Finance\Strategies\Turtle\TurtleStrategy::class,
                'type' => 'trend_following',
                'parameters' => [
                    'system' => 1, // 1 or 2
                    'atr_period' => 20,
                    'stop_loss_n' => 2.0,
                    'position_size_n' => 1.0,
                    'max_units' => 4
                ]
            ],
            'SupportResistanceStrategy' => [
                'name' => 'Support & Resistance',
                'description' => 'Buy leading stocks at support levels with technical confirmation',
                'class' => \Ksfraser\Finance\Strategies\SupportResistance\SupportResistanceStrategy::class,
                'type' => 'support_resistance',
                'parameters' => [
                    'lookback_period' => 50,
                    'support_threshold' => 0.02,
                    'volume_confirmation' => true,
                    'trend_filter' => true
                ]
            ],
            'MovingAverageCrossoverStrategy' => [
                'name' => 'Moving Average Crossover',
                'description' => 'EMA/SMA crossover signals with trend and volume confirmation',
                'class' => \Ksfraser\Finance\Strategies\TechnicalAnalysis\MovingAverageCrossoverStrategy::class,
                'type' => 'technical_analysis',
                'parameters' => [
                    'fast_period' => 12,
                    'slow_period' => 26,
                    'ma_type' => 'EMA',
                    'volume_confirmation' => true
                ]
            ],
            'FourWeekRuleStrategy' => [
                'name' => 'Four Week Rule',
                'description' => 'Classic 4-week high/low breakout system',
                'class' => \Ksfraser\Finance\Strategies\Breakout\FourWeekRuleStrategy::class,
                'type' => 'breakout',
                'parameters' => [
                    'breakout_period' => 28,
                    'volume_confirmation' => false,
                    'trend_filter' => false
                ]
            ]
        ];
    }

    /**
     * Create a new strategy instance for testing purposes
     */
    public function createStrategyInstanceByName(string $className, array $parameters = []): TradingStrategyInterface
    {
        switch ($className) {
            case 'TurtleStrategy':
                return new \Ksfraser\Finance\Strategies\Turtle\TurtleStrategy($this->stockDataService, $parameters);
            case 'SupportResistanceStrategy':
                return new \Ksfraser\Finance\Strategies\SupportResistance\SupportResistanceStrategy($this->stockDataService, $parameters);
            case 'MovingAverageCrossoverStrategy':
                return new \Ksfraser\Finance\Strategies\TechnicalAnalysis\MovingAverageCrossoverStrategy($this->stockDataService, $parameters);
            case 'FourWeekRuleStrategy':
                return new \Ksfraser\Finance\Strategies\Breakout\FourWeekRuleStrategy($this->stockDataService, $parameters);
            default:
                throw new \Exception("Unknown strategy class: {$className}");
        }
    }

    public function getMarketData(string $symbol, string $period): array
    {
        return $this->stockDataService->getStockData($symbol, $period);
    }

    public function getTechnicalIndicators(string $symbol, string $period): array
    {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$period}"));

        $sql = "
            SELECT indicator_name, indicator_value, date
            FROM technical_indicators 
            WHERE symbol = ? AND date BETWEEN ? AND ?
            ORDER BY date DESC, indicator_name
        ";

        return $this->repository->query($sql, [$symbol, $startDate, $endDate]);
    }
}
