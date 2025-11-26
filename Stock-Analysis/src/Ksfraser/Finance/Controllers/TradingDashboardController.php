<?php
namespace Ksfraser\Finance\Controllers;

use Ksfraser\Finance\Services\StrategyService;
use Ksfraser\Finance\Services\PortfolioService;
use Ksfraser\Finance\Services\BacktestingService;

class TradingDashboardController
{
    private StrategyService $strategyService;
    private PortfolioService $portfolioService;
    private BacktestingService $backtestingService;

    public function __construct(
        StrategyService $strategyService,
        PortfolioService $portfolioService,
        BacktestingService $backtestingService
    ) {
        $this->strategyService = $strategyService;
        $this->portfolioService = $portfolioService;
        $this->backtestingService = $backtestingService;
    }

    public function dashboard(): array
    {
        try {
            $portfolioSummary = $this->portfolioService->getPortfolioSummary();
            $activeStrategies = $this->strategyService->getActiveStrategies();
            $recentSignals = $this->strategyService->getRecentSignals(10);
            $performanceMetrics = $this->portfolioService->getPerformanceMetrics();
            
            return [
                'success' => true,
                'data' => [
                    'portfolio' => $portfolioSummary,
                    'strategies' => $activeStrategies,
                    'recent_signals' => $recentSignals,
                    'performance' => $performanceMetrics
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to load dashboard: ' . $e->getMessage()
            ];
        }
    }

    public function strategiesList(): array
    {
        try {
            $strategies = $this->strategyService->getAllStrategies();
            $strategyPerformance = [];
            
            foreach ($strategies as $strategy) {
                $performance = $this->backtestingService->getStrategyPerformance($strategy['id']);
                $strategyPerformance[$strategy['id']] = $performance;
            }
            
            return [
                'success' => true,
                'data' => [
                    'strategies' => $strategies,
                    'performance' => $strategyPerformance
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to load strategies: ' . $e->getMessage()
            ];
        }
    }

    public function executeStrategy(): array
    {
        try {
            $strategyId = $_POST['strategy_id'] ?? null;
            $symbol = $_POST['symbol'] ?? null;
            $parameters = json_decode($_POST['parameters'] ?? '{}', true);
            
            if (!$strategyId || !$symbol) {
                return [
                    'success' => false,
                    'error' => 'Strategy ID and symbol are required'
                ];
            }
            
            $result = $this->strategyService->executeStrategy($strategyId, $symbol, $parameters);
            
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Strategy execution failed: ' . $e->getMessage()
            ];
        }
    }

    public function backtestStrategy(): array
    {
        try {
            $strategyId = $_POST['strategy_id'] ?? null;
            $symbol = $_POST['symbol'] ?? '';
            $startDate = $_POST['start_date'] ?? null;
            $endDate = $_POST['end_date'] ?? null;
            $initialCapital = floatval($_POST['initial_capital'] ?? 100000);
            $parameters = json_decode($_POST['parameters'] ?? '{}', true);
            
            if (!$strategyId || !$startDate || !$endDate) {
                return [
                    'success' => false,
                    'error' => 'Strategy ID, start date, and end date are required'
                ];
            }
            
            $result = $this->backtestingService->runBacktest(
                $strategyId,
                $symbol,
                $startDate,
                $endDate,
                $initialCapital,
                $parameters
            );
            
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Backtesting failed: ' . $e->getMessage()
            ];
        }
    }

    public function updateStrategySettings(): array
    {
        try {
            $strategyId = $_POST['strategy_id'] ?? null;
            $parameters = json_decode($_POST['parameters'] ?? '{}', true);
            $isActive = filter_var($_POST['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);
            
            if (!$strategyId) {
                return [
                    'success' => false,
                    'error' => 'Strategy ID is required'
                ];
            }
            
            $result = $this->strategyService->updateStrategySettings($strategyId, $parameters, $isActive);
            
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to update strategy settings: ' . $e->getMessage()
            ];
        }
    }

    public function getMarketData(): array
    {
        try {
            $symbol = $_GET['symbol'] ?? null;
            $period = $_GET['period'] ?? '1y';
            
            if (!$symbol) {
                return [
                    'success' => false,
                    'error' => 'Symbol is required'
                ];
            }
            
            $marketData = $this->strategyService->getMarketData($symbol, $period);
            $technicalIndicators = $this->strategyService->getTechnicalIndicators($symbol, $period);
            
            return [
                'success' => true,
                'data' => [
                    'market_data' => $marketData,
                    'technical_indicators' => $technicalIndicators
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to load market data: ' . $e->getMessage()
            ];
        }
    }
}
