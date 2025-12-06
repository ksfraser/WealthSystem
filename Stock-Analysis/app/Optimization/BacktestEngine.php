<?php

declare(strict_types=1);

namespace App\Optimization;

use App\Services\Trading\TradingStrategyInterface;

/**
 * Backtesting engine for strategy evaluation
 */
class BacktestEngine
{
    /**
     * Run backtest on historical data
     *
     * @param TradingStrategyInterface $strategy
     * @param array $historicalData Price history
     * @param float $initialCapital Starting capital
     * @return array Backtest results
     */
    public function run(TradingStrategyInterface $strategy, array $historicalData, float $initialCapital = 10000.0): array
    {
        $capital = $initialCapital;
        $position = 0.0;
        $trades = [];
        
        foreach ($historicalData as $i => $data) {
            $signal = $strategy->analyze($data['symbol'], 'today');
            
            if ($signal['signal'] === 'BUY' && $capital > 0) {
                // Buy with all available capital
                $shares = $capital / $data['close'];
                $position += $shares;
                $capital = 0;
                
                $trades[] = [
                    'type' => 'BUY',
                    'price' => $data['close'],
                    'shares' => $shares,
                    'timestamp' => $i,
                ];
            } elseif ($signal['signal'] === 'SELL' && $position > 0) {
                // Sell all shares
                $capital = $position * $data['close'];
                
                $trades[] = [
                    'type' => 'SELL',
                    'price' => $data['close'],
                    'shares' => $position,
                    'timestamp' => $i,
                ];
                
                $position = 0;
            }
        }
        
        // Close any open position at end
        $finalData = end($historicalData);
        $finalValue = $capital + ($position * $finalData['close']);
        
        $returns = ($finalValue - $initialCapital) / $initialCapital;
        
        return [
            'initial_capital' => $initialCapital,
            'final_value' => $finalValue,
            'returns' => $returns,
            'returns_percent' => $returns * 100,
            'total_trades' => count($trades),
            'trades' => $trades,
        ];
    }
    
    /**
     * Calculate Sharpe ratio from backtest results
     *
     * @param array $trades Trade history
     * @param array $historicalData Price data
     * @param float $riskFreeRate Risk-free rate
     * @return float Sharpe ratio
     */
    public function calculateSharpeRatio(array $trades, array $historicalData, float $riskFreeRate = 0.02): float
    {
        if (empty($trades)) {
            return 0.0;
        }
        
        $returns = [];
        for ($i = 0; $i < count($trades) - 1; $i += 2) {
            if (isset($trades[$i + 1])) {
                $buyPrice = $trades[$i]['price'];
                $sellPrice = $trades[$i + 1]['price'];
                $returns[] = ($sellPrice - $buyPrice) / $buyPrice;
            }
        }
        
        if (empty($returns)) {
            return 0.0;
        }
        
        $mean = array_sum($returns) / count($returns);
        $variance = array_sum(array_map(fn($r) => pow($r - $mean, 2), $returns)) / count($returns);
        $stdDev = sqrt($variance);
        
        if ($stdDev == 0) {
            return 0.0;
        }
        
        return ($mean - $riskFreeRate / 252) / $stdDev;
    }
}
