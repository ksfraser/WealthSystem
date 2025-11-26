<?php
namespace Ksfraser\Finance\Services;

use Ksfraser\Finance\Interfaces\DataRepositoryInterface;

class PortfolioService
{
    private DataRepositoryInterface $repository;

    public function __construct(DataRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getPortfolioSummary(): array
    {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_positions,
                    SUM(CASE WHEN is_open = 1 THEN 1 ELSE 0 END) as open_positions,
                    SUM(CASE WHEN is_open = 0 THEN realized_pnl ELSE 0 END) as total_realized_pnl,
                    SUM(CASE WHEN is_open = 1 THEN unrealized_pnl ELSE 0 END) as total_unrealized_pnl,
                    SUM(CASE WHEN is_open = 1 THEN quantity * current_price ELSE 0 END) as market_value
                FROM portfolio_positions
            ";
            
            $summary = $this->repository->query($sql)[0] ?? [];
            
            // Get strategy breakdown
            $strategyBreakdown = $this->getStrategyBreakdown();
            
            // Calculate total portfolio value
            $totalValue = ($summary['market_value'] ?? 0) + ($summary['total_realized_pnl'] ?? 0);
            
            return [
                'summary' => $summary,
                'strategy_breakdown' => $strategyBreakdown,
                'total_portfolio_value' => $totalValue,
                'performance_metrics' => $this->getPerformanceMetrics()
            ];
        } catch (\Exception $e) {
            throw new \Exception("Failed to get portfolio summary: " . $e->getMessage());
        }
    }

    public function getStrategyBreakdown(): array
    {
        $sql = "
            SELECT 
                ts.name as strategy_name,
                ts.id as strategy_id,
                COUNT(pp.id) as positions,
                SUM(CASE WHEN pp.is_open = 1 THEN 1 ELSE 0 END) as open_positions,
                SUM(CASE WHEN pp.is_open = 0 THEN pp.realized_pnl ELSE 0 END) as realized_pnl,
                SUM(CASE WHEN pp.is_open = 1 THEN pp.unrealized_pnl ELSE 0 END) as unrealized_pnl
            FROM trading_strategies ts
            LEFT JOIN portfolio_positions pp ON ts.id = pp.strategy_id
            WHERE ts.is_active = 1
            GROUP BY ts.id, ts.name
            ORDER BY realized_pnl + unrealized_pnl DESC
        ";
        
        return $this->repository->query($sql);
    }

    public function getPerformanceMetrics(): array
    {
        try {
            // Get all closed positions for performance calculation
            $sql = "
                SELECT 
                    realized_pnl,
                    entry_date,
                    exit_date,
                    DATEDIFF(exit_date, entry_date) as days_held
                FROM portfolio_positions 
                WHERE is_open = 0 AND realized_pnl IS NOT NULL
                ORDER BY exit_date
            ";
            
            $closedPositions = $this->repository->query($sql);
            
            if (empty($closedPositions)) {
                return [
                    'total_trades' => 0,
                    'winning_trades' => 0,
                    'losing_trades' => 0,
                    'win_rate' => 0,
                    'average_win' => 0,
                    'average_loss' => 0,
                    'profit_factor' => 0,
                    'total_pnl' => 0
                ];
            }
            
            $totalTrades = count($closedPositions);
            $winningTrades = array_filter($closedPositions, fn($pos) => $pos['realized_pnl'] > 0);
            $losingTrades = array_filter($closedPositions, fn($pos) => $pos['realized_pnl'] <= 0);
            
            $totalWinAmount = array_sum(array_column($winningTrades, 'realized_pnl'));
            $totalLossAmount = abs(array_sum(array_column($losingTrades, 'realized_pnl')));
            
            return [
                'total_trades' => $totalTrades,
                'winning_trades' => count($winningTrades),
                'losing_trades' => count($losingTrades),
                'win_rate' => $totalTrades > 0 ? (count($winningTrades) / $totalTrades) * 100 : 0,
                'average_win' => count($winningTrades) > 0 ? $totalWinAmount / count($winningTrades) : 0,
                'average_loss' => count($losingTrades) > 0 ? $totalLossAmount / count($losingTrades) : 0,
                'profit_factor' => $totalLossAmount > 0 ? $totalWinAmount / $totalLossAmount : 0,
                'total_pnl' => array_sum(array_column($closedPositions, 'realized_pnl')),
                'average_holding_period' => $totalTrades > 0 ? array_sum(array_column($closedPositions, 'days_held')) / $totalTrades : 0
            ];
        } catch (\Exception $e) {
            throw new \Exception("Failed to calculate performance metrics: " . $e->getMessage());
        }
    }

    public function openPosition(array $positionData): bool
    {
        try {
            $sql = "
                INSERT INTO portfolio_positions 
                (symbol, strategy_id, entry_date, entry_price, quantity, position_type, stop_loss_price, take_profit_price, risk_amount)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            return $this->repository->execute($sql, [
                $positionData['symbol'],
                $positionData['strategy_id'],
                $positionData['entry_date'],
                $positionData['entry_price'],
                $positionData['quantity'],
                $positionData['position_type'],
                $positionData['stop_loss_price'] ?? null,
                $positionData['take_profit_price'] ?? null,
                $positionData['risk_amount'] ?? null
            ]);
        } catch (\Exception $e) {
            throw new \Exception("Failed to open position: " . $e->getMessage());
        }
    }

    public function closePosition(int $positionId, array $exitData): bool
    {
        try {
            $sql = "
                UPDATE portfolio_positions 
                SET is_open = 0, exit_date = ?, exit_price = ?, realized_pnl = ?, exit_reason = ?
                WHERE id = ? AND is_open = 1
            ";
            
            return $this->repository->execute($sql, [
                $exitData['exit_date'],
                $exitData['exit_price'],
                $exitData['realized_pnl'],
                $exitData['exit_reason'] ?? 'Manual close',
                $positionId
            ]);
        } catch (\Exception $e) {
            throw new \Exception("Failed to close position: " . $e->getMessage());
        }
    }

    public function updatePositionPrices(): int
    {
        try {
            // Get all open positions
            $sql = "SELECT id, symbol, entry_price, quantity, position_type FROM portfolio_positions WHERE is_open = 1";
            $openPositions = $this->repository->query($sql);
            
            $updatedCount = 0;
            
            foreach ($openPositions as $position) {
                // Get current price
                $priceSql = "SELECT close_price FROM market_data WHERE symbol = ? ORDER BY date DESC LIMIT 1";
                $priceResult = $this->repository->query($priceSql, [$position['symbol']]);
                
                if (!empty($priceResult)) {
                    $currentPrice = $priceResult[0]['close_price'];
                    
                    // Calculate unrealized P&L
                    $unrealizedPnl = $this->calculateUnrealizedPnL(
                        $position['entry_price'],
                        $currentPrice,
                        $position['quantity'],
                        $position['position_type']
                    );
                    
                    // Update position
                    $updateSql = "UPDATE portfolio_positions SET current_price = ?, unrealized_pnl = ? WHERE id = ?";
                    if ($this->repository->execute($updateSql, [$currentPrice, $unrealizedPnl, $position['id']])) {
                        $updatedCount++;
                    }
                }
            }
            
            return $updatedCount;
        } catch (\Exception $e) {
            throw new \Exception("Failed to update position prices: " . $e->getMessage());
        }
    }

    private function calculateUnrealizedPnL(float $entryPrice, float $currentPrice, int $quantity, string $positionType): float
    {
        if ($positionType === 'LONG') {
            return ($currentPrice - $entryPrice) * $quantity;
        } else { // SHORT
            return ($entryPrice - $currentPrice) * $quantity;
        }
    }

    public function getOpenPositions(): array
    {
        $sql = "
            SELECT 
                pp.*,
                ts.name as strategy_name,
                md.close_price as current_market_price
            FROM portfolio_positions pp
            JOIN trading_strategies ts ON pp.strategy_id = ts.id
            LEFT JOIN (
                SELECT symbol, close_price, 
                       ROW_NUMBER() OVER (PARTITION BY symbol ORDER BY date DESC) as rn
                FROM market_data
            ) md ON pp.symbol = md.symbol AND md.rn = 1
            WHERE pp.is_open = 1
            ORDER BY pp.entry_date DESC
        ";
        
        return $this->repository->query($sql);
    }

    public function getPositionHistory(int $limit = 50): array
    {
        $sql = "
            SELECT 
                pp.*,
                ts.name as strategy_name
            FROM portfolio_positions pp
            JOIN trading_strategies ts ON pp.strategy_id = ts.id
            WHERE pp.is_open = 0
            ORDER BY pp.exit_date DESC
            LIMIT ?
        ";
        
        return $this->repository->query($sql, [$limit]);
    }

    public function getRiskMetrics(): array
    {
        try {
            // Get risk exposure by strategy
            $strategySql = "
                SELECT 
                    ts.name as strategy_name,
                    SUM(pp.risk_amount) as total_risk,
                    COUNT(*) as position_count,
                    AVG(pp.risk_amount) as avg_risk_per_position
                FROM portfolio_positions pp
                JOIN trading_strategies ts ON pp.strategy_id = ts.id
                WHERE pp.is_open = 1
                GROUP BY ts.id, ts.name
            ";
            
            $strategyRisk = $this->repository->query($strategySql);
            
            // Get overall risk metrics
            $overallSql = "
                SELECT 
                    SUM(risk_amount) as total_portfolio_risk,
                    COUNT(*) as total_open_positions,
                    MAX(risk_amount) as max_position_risk,
                    AVG(risk_amount) as avg_position_risk
                FROM portfolio_positions 
                WHERE is_open = 1
            ";
            
            $overallRisk = $this->repository->query($overallSql)[0] ?? [];
            
            return [
                'strategy_breakdown' => $strategyRisk,
                'overall_metrics' => $overallRisk
            ];
        } catch (\Exception $e) {
            throw new \Exception("Failed to get risk metrics: " . $e->getMessage());
        }
    }
}
