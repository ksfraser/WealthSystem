<?php

declare(strict_types=1);

namespace WealthSystem\Backtesting;

use InvalidArgumentException;

/**
 * Multi-Symbol Backtest Engine
 * 
 * Portfolio-level backtesting across multiple symbols simultaneously:
 * - Coordinate strategy execution across multiple assets
 * - Track portfolio-level metrics (correlation, diversification)
 * - Enforce position limits and sector exposure constraints
 * - Calculate portfolio returns, Sharpe ratio, and risk metrics
 * - Support for both long and short positions
 * 
 * This engine enables realistic portfolio backtesting where:
 * - Capital allocation decisions affect each other
 * - Correlations between assets matter
 * - Sector concentration can be limited
 * - Rebalancing triggers portfolio-wide adjustments
 * 
 * @package WealthSystem\Backtesting
 * @author WealthSystem Team
 */
class MultiSymbolBacktestEngine
{
    private array $config;
    private ShortSellingBacktestEngine $engine;
    private array $state;
    private array $strategies; // ['symbol' => strategy_instance]
    
    /**
     * @param array $config Configuration options
     *   - initial_capital: Starting capital (default: 100000)
     *   - max_position_size: Max position as % of portfolio (default: 0.15 = 15%)
     *   - max_sector_exposure: Max sector allocation (default: 0.40 = 40%)
     *   - max_positions: Maximum number of concurrent positions (default: 10)
     *   - rebalance_threshold: Trigger rebalance if drift > threshold (default: 0.05 = 5%)
     *   - correlation_limit: Max correlation between positions (default: 0.7)
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'initial_capital' => 100000.0,
            'max_position_size' => 0.15,
            'max_sector_exposure' => 0.40,
            'max_positions' => 10,
            'rebalance_threshold' => 0.05,
            'correlation_limit' => 0.7,
            'risk_free_rate' => 0.02
        ], $config);
        
        // Initialize single-symbol engine for position management
        $this->engine = new ShortSellingBacktestEngine([
            'initial_capital' => $this->config['initial_capital'],
            'commission_rate' => $config['commission_rate'] ?? 0.001,
            'slippage_rate' => $config['slippage_rate'] ?? 0.0005,
            'margin_requirement' => $config['margin_requirement'] ?? 1.5,
            'short_interest_rate' => $config['short_interest_rate'] ?? 0.03
        ]);
        
        $this->strategies = [];
        $this->initializeState();
    }
    
    /**
     * Initialize state tracking
     */
    private function initializeState(): void
    {
        $this->state = [
            'portfolio_values' => [],
            'returns' => [],
            'positions_history' => [],
            'rebalances' => [],
            'sector_exposures' => [],
            'correlations' => [],
            'signals_generated' => 0,
            'signals_executed' => 0,
            'signals_rejected' => []
        ];
    }
    
    /**
     * Register a strategy for a symbol
     * 
     * @param string $symbol Stock symbol
     * @param callable $strategy Strategy function that takes (symbol, historicalData, currentPrice)
     *                           and returns ['action' => 'BUY'|'SELL'|'HOLD', 'confidence' => 0-1]
     * @param array $metadata Optional metadata ['sector' => string, 'industry' => string]
     */
    public function registerStrategy(string $symbol, callable $strategy, array $metadata = []): void
    {
        $this->strategies[$symbol] = [
            'strategy' => $strategy,
            'sector' => $metadata['sector'] ?? 'Unknown',
            'industry' => $metadata['industry'] ?? 'Unknown',
            'returns' => [],
            'prices' => []
        ];
    }
    
    /**
     * Run multi-symbol backtest
     * 
     * @param array $marketData Market data by symbol ['AAPL' => [price_bars], 'MSFT' => [price_bars]]
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array Backtest results
     */
    public function runBacktest(array $marketData, string $startDate, string $endDate): array
    {
        // Validate input
        if (empty($marketData)) {
            throw new InvalidArgumentException('Market data cannot be empty');
        }
        
        if (empty($this->strategies)) {
            throw new InvalidArgumentException('No strategies registered');
        }
        
        // Get all unique dates across all symbols
        $allDates = $this->extractAllDates($marketData);
        $allDates = array_filter($allDates, fn($d) => $d >= $startDate && $d <= $endDate);
        sort($allDates);
        
        if (empty($allDates)) {
            throw new InvalidArgumentException('No data in specified date range');
        }
        
        // Run backtest day by day
        foreach ($allDates as $date) {
            $this->processDay($date, $marketData);
        }
        
        // Calculate final metrics
        $metrics = $this->calculatePortfolioMetrics();
        
        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
                'trading_days' => count($allDates)
            ],
            'initial_capital' => $this->config['initial_capital'],
            'final_value' => end($this->state['portfolio_values'])['net_worth'] ?? $this->config['initial_capital'],
            'portfolio_values' => $this->state['portfolio_values'],
            'returns' => $this->state['returns'],
            'metrics' => $metrics,
            'trades' => $this->engine->getState()['trades'],
            'rebalances' => $this->state['rebalances'],
            'sector_exposures' => $this->state['sector_exposures'],
            'signals_stats' => [
                'generated' => $this->state['signals_generated'],
                'executed' => $this->state['signals_executed'],
                'rejected' => count($this->state['signals_rejected']),
                'rejection_reasons' => $this->aggregateRejectionReasons()
            ]
        ];
    }
    
    /**
     * Process a single day of trading
     */
    private function processDay(string $date, array $marketData): void
    {
        // Get current prices for all symbols
        $currentPrices = [];
        foreach ($marketData as $symbol => $bars) {
            $bar = $this->findBarForDate($bars, $date);
            if ($bar) {
                $currentPrices[$symbol] = $bar['close'];
                
                // Track price history
                if (isset($this->strategies[$symbol])) {
                    $this->strategies[$symbol]['prices'][] = $bar['close'];
                }
            }
        }
        
        // Check margin requirements (for short positions)
        $marginCalls = $this->engine->checkMarginRequirements($currentPrices, $date);
        
        // Handle any margin calls with forced liquidation
        foreach ($marginCalls as $call) {
            $this->engine->forceLiquidate($call['symbol'], $call['current_price'], $date);
        }
        
        // Calculate current portfolio value
        $portfolioValue = $this->engine->calculatePortfolioValue($currentPrices);
        $this->state['portfolio_values'][] = array_merge($portfolioValue, ['date' => $date]);
        
        // Calculate return if we have previous value
        if (count($this->state['portfolio_values']) > 1) {
            $prevValue = $this->state['portfolio_values'][count($this->state['portfolio_values']) - 2]['net_worth'];
            $currentValue = $portfolioValue['net_worth'];
            $return = ($currentValue - $prevValue) / $prevValue;
            $this->state['returns'][] = ['date' => $date, 'return' => $return];
        }
        
        // Generate signals for each symbol
        $signals = [];
        foreach ($this->strategies as $symbol => $strategyData) {
            if (!isset($currentPrices[$symbol])) {
                continue; // No data for this symbol today
            }
            
            $historicalData = $this->getHistoricalData($marketData[$symbol], $date);
            if (count($historicalData) < 20) {
                continue; // Need minimum data for strategies
            }
            
            $signal = call_user_func(
                $strategyData['strategy'],
                $symbol,
                $historicalData,
                $currentPrices[$symbol]
            );
            
            if ($signal && $signal['action'] !== 'HOLD') {
                $signals[] = array_merge($signal, [
                    'symbol' => $symbol,
                    'date' => $date,
                    'price' => $currentPrices[$symbol],
                    'sector' => $strategyData['sector']
                ]);
                $this->state['signals_generated']++;
            }
        }
        
        // Execute signals (with portfolio constraints)
        foreach ($signals as $signal) {
            $this->executeSignal($signal, $portfolioValue, $date);
        }
        
        // Track sector exposures
        $this->trackSectorExposure($portfolioValue, $currentPrices, $date);
        
        // Check if rebalancing is needed
        if ($this->shouldRebalance($portfolioValue)) {
            $this->rebalancePortfolio($currentPrices, $date);
        }
    }
    
    /**
     * Execute a trading signal with portfolio constraints
     */
    private function executeSignal(array $signal, array $portfolioValue, string $date): void
    {
        $symbol = $signal['symbol'];
        $action = $signal['action'];
        $price = $signal['price'];
        $confidence = $signal['confidence'] ?? 0.5;
        
        // Check portfolio constraints
        $violation = $this->checkPortfolioConstraints($signal, $portfolioValue);
        if ($violation) {
            $this->state['signals_rejected'][] = array_merge($signal, [
                'rejection_reason' => $violation
            ]);
            return;
        }
        
        // Calculate position size based on confidence and portfolio value
        $maxPositionValue = $portfolioValue['net_worth'] * $this->config['max_position_size'];
        $targetPositionValue = $maxPositionValue * $confidence;
        $shares = (int) floor($targetPositionValue / $price);
        
        if ($shares <= 0) {
            $this->state['signals_rejected'][] = array_merge($signal, [
                'rejection_reason' => 'Insufficient shares calculated'
            ]);
            return;
        }
        
        // Execute based on action
        if ($action === 'BUY') {
            $result = $this->engine->enterLongPosition($symbol, $shares, $price, $date);
        } elseif ($action === 'SELL') {
            $result = $this->engine->exitLongPosition($symbol, null, $price, $date);
        } elseif ($action === 'SHORT') {
            $result = $this->engine->enterShortPosition($symbol, $shares, $price, $date);
        } elseif ($action === 'COVER') {
            $result = $this->engine->exitShortPosition($symbol, null, $price, $date);
        } else {
            return; // Unknown action
        }
        
        if ($result['success'] ?? false) {
            $this->state['signals_executed']++;
        } else {
            $this->state['signals_rejected'][] = array_merge($signal, [
                'rejection_reason' => $result['error'] ?? 'Unknown error'
            ]);
        }
    }
    
    /**
     * Check if signal violates portfolio constraints
     * 
     * @return string|null Violation description or null if OK
     */
    private function checkPortfolioConstraints(array $signal, array $portfolioValue): ?string
    {
        $action = $signal['action'];
        
        // Check max positions limit
        $engineState = $this->engine->getState();
        $currentPositions = count($engineState['long_positions']) + count($engineState['short_positions']);
        
        if (($action === 'BUY' || $action === 'SHORT') && $currentPositions >= $this->config['max_positions']) {
            return 'Max positions limit reached';
        }
        
        // Check sector exposure limit (for new positions)
        if ($action === 'BUY' || $action === 'SHORT') {
            $sector = $signal['sector'] ?? 'Unknown';
            $sectorExposure = $this->calculateSectorExposure($sector, $portfolioValue);
            
            if ($sectorExposure >= $this->config['max_sector_exposure']) {
                return "Sector exposure limit reached for {$sector}";
            }
        }
        
        // Check correlation with existing positions (for diversification)
        if ($action === 'BUY') {
            $symbol = $signal['symbol'];
            $avgCorrelation = $this->calculateAverageCorrelation($symbol);
            
            if ($avgCorrelation > $this->config['correlation_limit']) {
                return "High correlation with existing positions";
            }
        }
        
        return null; // No violations
    }
    
    /**
     * Calculate sector exposure as % of portfolio
     */
    private function calculateSectorExposure(string $sector, array $portfolioValue): float
    {
        $engineState = $this->engine->getState();
        $sectorValue = 0.0;
        
        // Sum up positions in this sector
        foreach ($engineState['long_positions'] as $symbol => $position) {
            if (isset($this->strategies[$symbol]) && 
                $this->strategies[$symbol]['sector'] === $sector) {
                $sectorValue += $position['shares'] * ($position['cost_basis'] ?? 0);
            }
        }
        
        return $portfolioValue['net_worth'] > 0 ? 
            $sectorValue / $portfolioValue['net_worth'] : 0.0;
    }
    
    /**
     * Calculate average correlation between symbol and existing positions
     */
    private function calculateAverageCorrelation(string $newSymbol): float
    {
        $engineState = $this->engine->getState();
        
        if (empty($engineState['long_positions'])) {
            return 0.0;
        }
        
        $correlations = [];
        $newPrices = $this->strategies[$newSymbol]['prices'] ?? [];
        
        if (count($newPrices) < 20) {
            return 0.0; // Not enough data
        }
        
        foreach ($engineState['long_positions'] as $symbol => $position) {
            if (!isset($this->strategies[$symbol])) {
                continue;
            }
            
            $existingPrices = $this->strategies[$symbol]['prices'] ?? [];
            
            if (count($existingPrices) < 20) {
                continue;
            }
            
            // Calculate correlation
            $correlation = $this->calculateCorrelation($newPrices, $existingPrices);
            $correlations[] = abs($correlation); // Use absolute value
        }
        
        return !empty($correlations) ? array_sum($correlations) / count($correlations) : 0.0;
    }
    
    /**
     * Calculate Pearson correlation between two price series
     */
    private function calculateCorrelation(array $series1, array $series2): float
    {
        $n = min(count($series1), count($series2));
        
        if ($n < 2) {
            return 0.0;
        }
        
        // Trim to same length
        $series1 = array_slice($series1, -$n);
        $series2 = array_slice($series2, -$n);
        
        $mean1 = array_sum($series1) / $n;
        $mean2 = array_sum($series2) / $n;
        
        $numerator = 0.0;
        $denom1 = 0.0;
        $denom2 = 0.0;
        
        for ($i = 0; $i < $n; $i++) {
            $diff1 = $series1[$i] - $mean1;
            $diff2 = $series2[$i] - $mean2;
            
            $numerator += $diff1 * $diff2;
            $denom1 += $diff1 * $diff1;
            $denom2 += $diff2 * $diff2;
        }
        
        $denominator = sqrt($denom1 * $denom2);
        
        return $denominator > 0 ? $numerator / $denominator : 0.0;
    }
    
    /**
     * Track sector exposure over time
     */
    private function trackSectorExposure(array $portfolioValue, array $currentPrices, string $date): void
    {
        $engineState = $this->engine->getState();
        $sectorExposures = [];
        
        foreach ($engineState['long_positions'] as $symbol => $position) {
            if (!isset($this->strategies[$symbol], $currentPrices[$symbol])) {
                continue;
            }
            
            $sector = $this->strategies[$symbol]['sector'];
            $value = $position['shares'] * $currentPrices[$symbol];
            
            if (!isset($sectorExposures[$sector])) {
                $sectorExposures[$sector] = 0.0;
            }
            
            $sectorExposures[$sector] += $value;
        }
        
        // Convert to percentages
        $netWorth = $portfolioValue['net_worth'];
        foreach ($sectorExposures as $sector => $value) {
            $sectorExposures[$sector] = $netWorth > 0 ? $value / $netWorth : 0.0;
        }
        
        $this->state['sector_exposures'][] = array_merge([
            'date' => $date
        ], $sectorExposures);
    }
    
    /**
     * Check if portfolio rebalancing is needed
     */
    private function shouldRebalance(array $portfolioValue): bool
    {
        $engineState = $this->engine->getState();
        
        // Rebalance if any position drifts beyond threshold
        $netWorth = $portfolioValue['net_worth'];
        $targetWeight = 1.0 / max(count($engineState['long_positions']), 1);
        
        foreach ($engineState['long_positions'] as $symbol => $position) {
            $currentWeight = ($position['shares'] * ($position['cost_basis'] ?? 0)) / $netWorth;
            $drift = abs($currentWeight - $targetWeight);
            
            if ($drift > $this->config['rebalance_threshold']) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Rebalance portfolio to target weights
     */
    private function rebalancePortfolio(array $currentPrices, string $date): void
    {
        // Simple rebalance: Equal weight all positions
        // (More sophisticated strategies could use optimization)
        
        $engineState = $this->engine->getState();
        $positionCount = count($engineState['long_positions']);
        
        if ($positionCount === 0) {
            return;
        }
        
        $this->state['rebalances'][] = [
            'date' => $date,
            'positions' => $positionCount,
            'reason' => 'Drift threshold exceeded'
        ];
        
        // Note: Actual rebalancing would adjust positions here
        // For simplicity, we just log the event
    }
    
    /**
     * Extract all unique dates from market data
     */
    private function extractAllDates(array $marketData): array
    {
        $dates = [];
        
        foreach ($marketData as $symbol => $bars) {
            foreach ($bars as $bar) {
                $dates[] = $bar['date'];
            }
        }
        
        return array_values(array_unique($dates));
    }
    
    /**
     * Find price bar for specific date
     */
    private function findBarForDate(array $bars, string $date): ?array
    {
        foreach ($bars as $bar) {
            if ($bar['date'] === $date) {
                return $bar;
            }
        }
        
        return null;
    }
    
    /**
     * Get historical data up to (and including) specified date
     */
    private function getHistoricalData(array $bars, string $date): array
    {
        $historical = [];
        
        foreach ($bars as $bar) {
            if ($bar['date'] <= $date) {
                $historical[] = $bar;
            }
        }
        
        return $historical;
    }
    
    /**
     * Calculate portfolio-level metrics
     */
    private function calculatePortfolioMetrics(): array
    {
        if (empty($this->state['returns'])) {
            return $this->getEmptyMetrics();
        }
        
        $returns = array_column($this->state['returns'], 'return');
        $portfolioValues = $this->state['portfolio_values'];
        
        // Calculate basic stats
        $totalReturn = ($portfolioValues[count($portfolioValues) - 1]['net_worth'] - 
                       $portfolioValues[0]['net_worth']) / 
                       $portfolioValues[0]['net_worth'];
        
        $avgReturn = array_sum($returns) / count($returns);
        $stdReturn = $this->calculateStandardDeviation($returns);
        
        // Sharpe ratio (annualized)
        $tradingDaysPerYear = 252;
        $annualizedReturn = $avgReturn * $tradingDaysPerYear;
        $annualizedVol = $stdReturn * sqrt($tradingDaysPerYear);
        $sharpeRatio = $annualizedVol > 0 ? 
            ($annualizedReturn - $this->config['risk_free_rate']) / $annualizedVol : 0.0;
        
        // Maximum drawdown
        $maxDrawdown = $this->calculateMaxDrawdown($portfolioValues);
        
        // Sortino ratio (downside deviation)
        $downsideReturns = array_filter($returns, fn($r) => $r < 0);
        $downsideStd = !empty($downsideReturns) ? 
            $this->calculateStandardDeviation($downsideReturns) : 0.0;
        $annualizedDownside = $downsideStd * sqrt($tradingDaysPerYear);
        $sortinoRatio = $annualizedDownside > 0 ? 
            ($annualizedReturn - $this->config['risk_free_rate']) / $annualizedDownside : 0.0;
        
        // Win rate
        $winningDays = count(array_filter($returns, fn($r) => $r > 0));
        $losingDays = count(array_filter($returns, fn($r) => $r < 0));
        $winRate = ($winningDays + $losingDays) > 0 ? 
            $winningDays / ($winningDays + $losingDays) : 0.0;
        
        return [
            'total_return' => $totalReturn,
            'total_return_pct' => $totalReturn * 100,
            'annualized_return' => $annualizedReturn,
            'avg_daily_return' => $avgReturn,
            'volatility' => $stdReturn,
            'annualized_volatility' => $annualizedVol,
            'sharpe_ratio' => $sharpeRatio,
            'sortino_ratio' => $sortinoRatio,
            'max_drawdown' => $maxDrawdown,
            'win_rate' => $winRate,
            'winning_days' => $winningDays,
            'losing_days' => $losingDays,
            'total_days' => count($returns)
        ];
    }
    
    /**
     * Calculate maximum drawdown
     */
    private function calculateMaxDrawdown(array $portfolioValues): float
    {
        $maxDrawdown = 0.0;
        $peak = $portfolioValues[0]['net_worth'];
        
        foreach ($portfolioValues as $value) {
            $current = $value['net_worth'];
            $peak = max($peak, $current);
            $drawdown = ($peak - $current) / $peak;
            $maxDrawdown = max($maxDrawdown, $drawdown);
        }
        
        return $maxDrawdown;
    }
    
    /**
     * Calculate standard deviation
     */
    private function calculateStandardDeviation(array $values): float
    {
        $n = count($values);
        
        if ($n < 2) {
            return 0.0;
        }
        
        $mean = array_sum($values) / $n;
        $sumSquaredDiff = array_sum(array_map(
            fn($x) => ($x - $mean) ** 2,
            $values
        ));
        
        return sqrt($sumSquaredDiff / ($n - 1));
    }
    
    /**
     * Get empty metrics structure
     */
    private function getEmptyMetrics(): array
    {
        return [
            'total_return' => 0.0,
            'total_return_pct' => 0.0,
            'annualized_return' => 0.0,
            'avg_daily_return' => 0.0,
            'volatility' => 0.0,
            'annualized_volatility' => 0.0,
            'sharpe_ratio' => 0.0,
            'sortino_ratio' => 0.0,
            'max_drawdown' => 0.0,
            'win_rate' => 0.0,
            'winning_days' => 0,
            'losing_days' => 0,
            'total_days' => 0
        ];
    }
    
    /**
     * Aggregate rejection reasons for statistics
     */
    private function aggregateRejectionReasons(): array
    {
        $reasons = [];
        
        foreach ($this->state['signals_rejected'] as $rejection) {
            $reason = $rejection['rejection_reason'];
            
            if (!isset($reasons[$reason])) {
                $reasons[$reason] = 0;
            }
            
            $reasons[$reason]++;
        }
        
        return $reasons;
    }
}
