<?php

declare(strict_types=1);

namespace App\Services\Trading\Strategies;

use App\Crypto\ETFPremiumDiscountTracker;

/**
 * Crypto ETF Arbitrage Strategy
 * 
 * Trades cryptocurrency ETFs based on premium/discount to NAV.
 * 
 * Core Logic:
 * - BUY when ETF trades at ≥3% discount to NAV
 * - SELL when premium narrows or reverses to ≥2% premium
 * - Higher discount = higher confidence
 * 
 * Risk Management:
 * - Position sizing based on confidence
 * - Stop loss if discount widens beyond entry
 * - Target profit when premium returns
 * 
 * @package App\Services\Trading\Strategies
 */
class CryptoETFArbitrageStrategy
{
    private ETFPremiumDiscountTracker $tracker;
    private float $entryThreshold = -3.0; // -3% discount minimum
    private float $exitThreshold = 2.0;   // +2% premium target
    
    public function __construct(ETFPremiumDiscountTracker $tracker)
    {
        $this->tracker = $tracker;
    }
    
    /**
     * Analyze market data and generate trading signal
     * 
     * @param array $data Market data including premium/discount
     * @return array Trading signal
     */
    public function analyze(array $data): array
    {
        $premiumDiscount = $data['premium_discount_percent'];
        $etfSymbol = $data['etf_symbol'];
        
        // Determine action
        $action = 'HOLD';
        if ($premiumDiscount <= $this->entryThreshold) {
            $action = 'BUY';
        } elseif ($premiumDiscount >= $this->exitThreshold) {
            $action = 'SELL';
        }
        
        // Calculate confidence
        $confidence = $this->calculateConfidence($premiumDiscount);
        
        // Assess risk
        $avgDiscount = $this->tracker->getAveragePremiumDiscount($etfSymbol, 30);
        $correlation = 0.95; // Mock - would calculate from tracking data
        $riskLevel = $this->assessRisk($premiumDiscount, $correlation);
        
        return [
            'action' => $action,
            'confidence' => $confidence,
            'premium_discount' => $premiumDiscount,
            'risk_level' => $riskLevel,
            'reason' => $this->generateReason($action, $premiumDiscount),
            'timestamp' => time()
        ];
    }
    
    /**
     * Calculate confidence score (0-100)
     * 
     * @param float $premiumDiscount Premium/discount percentage
     * @return int Confidence score
     */
    public function calculateConfidence(float $premiumDiscount): int
    {
        // Higher absolute value = higher confidence
        $absValue = abs($premiumDiscount);
        
        if ($absValue >= 10) {
            return 95;
        } elseif ($absValue >= 7) {
            return 85;
        } elseif ($absValue >= 5) {
            return 75;
        } elseif ($absValue >= 3) {
            return 60;
        }
        
        return 30;
    }
    
    /**
     * Calculate optimal entry price
     * 
     * @param float $nav Current NAV
     * @param float $targetDiscount Target discount percentage
     * @param float $fees Trading fees percentage
     * @return float Entry price
     */
    public function calculateEntryPrice(
        float $nav,
        float $targetDiscount,
        float $fees = 0.25
    ): float {
        $discountPrice = $nav * (1 + $targetDiscount / 100);
        $withFees = $discountPrice * (1 + $fees / 100);
        
        return round($withFees, 4);
    }
    
    /**
     * Calculate optimal exit price
     * 
     * @param float $nav Current NAV
     * @param float $targetPremium Target premium percentage
     * @param float $fees Trading fees percentage
     * @return float Exit price
     */
    public function calculateExitPrice(
        float $nav,
        float $targetPremium,
        float $fees = 0.25
    ): float {
        $premiumPrice = $nav * (1 + $targetPremium / 100);
        $afterFees = $premiumPrice * (1 - $fees / 100);
        
        return round($afterFees, 4);
    }
    
    /**
     * Calculate expected profit
     * 
     * @param float $entryPrice Entry price
     * @param float $exitPrice Exit price
     * @param float $fees Total fees percentage
     * @return array Profit data
     */
    public function calculateExpectedProfit(
        float $entryPrice,
        float $exitPrice,
        float $fees = 0.25
    ): array {
        $grossProfit = $exitPrice - $entryPrice;
        $totalFees = ($entryPrice + $exitPrice) * ($fees / 100);
        $netProfit = $grossProfit - $totalFees;
        $profitPercent = ($netProfit / $entryPrice) * 100;
        
        return [
            'gross_profit' => round($grossProfit, 4),
            'total_fees' => round($totalFees, 4),
            'net_profit' => round($netProfit, 4),
            'profit_percent' => round($profitPercent, 2),
            'profit_after_fees' => round($netProfit, 4)
        ];
    }
    
    /**
     * Assess risk level for trade
     * 
     * @param float $premiumDiscount Current premium/discount
     * @param float $correlation ETF-crypto correlation
     * @return string Risk level: low, medium, high
     */
    public function assessRisk(float $premiumDiscount, float $correlation): string
    {
        // Low risk: Large discount + high correlation
        if (abs($premiumDiscount) >= 7 && $correlation >= 0.90) {
            return 'low';
        }
        
        // High risk: Small discount or low correlation
        if (abs($premiumDiscount) < 4 || $correlation < 0.80) {
            return 'high';
        }
        
        return 'medium';
    }
    
    /**
     * Generate backtestable signals from historical data
     * 
     * @param array $historicalData Array of historical price data
     * @return array Trading signals
     */
    public function generateBacktestSignals(array $historicalData): array
    {
        $signals = [];
        
        foreach ($historicalData as $data) {
            $premiumDiscount = (($data['market_price'] - $data['nav']) / $data['nav']) * 100;
            
            $signal = $this->analyze([
                'etf_symbol' => 'BTCC.TO',
                'market_price' => $data['market_price'],
                'nav' => $data['nav'],
                'premium_discount_percent' => $premiumDiscount
            ]);
            
            if ($signal['action'] !== 'HOLD') {
                $signals[] = array_merge($signal, [
                    'date' => $data['date'],
                    'price' => $data['market_price']
                ]);
            }
        }
        
        return $signals;
    }
    
    /**
     * Check if meets minimum entry threshold
     * 
     * @param float $premiumDiscount Current premium/discount
     * @return bool True if meets threshold
     */
    public function meetsEntryThreshold(float $premiumDiscount): bool
    {
        return $premiumDiscount <= $this->entryThreshold;
    }
    
    /**
     * Check if should exit position
     * 
     * @param float $entryDiscount Entry discount percentage
     * @param float $currentPremiumDiscount Current premium/discount
     * @return bool True if should exit
     */
    public function shouldExit(
        float $entryDiscount,
        float $currentPremiumDiscount
    ): bool {
        // Exit if premium has narrowed significantly
        $improvement = $currentPremiumDiscount - $entryDiscount;
        
        // Exit conditions:
        // 1. Reached target premium
        // 2. Improved by at least 5 percentage points
        // 3. Discount widened beyond stop loss (-15%)
        
        if ($currentPremiumDiscount >= $this->exitThreshold) {
            return true;
        }
        
        if ($improvement >= 5.0) {
            return true;
        }
        
        if ($currentPremiumDiscount < -15.0) {
            return true; // Stop loss
        }
        
        return false;
    }
    
    /**
     * Calculate position size based on confidence and risk
     * 
     * @param float $portfolioValue Total portfolio value
     * @param int $confidence Confidence score (0-100)
     * @param float $riskPercent Maximum risk percentage
     * @return float Position size in dollars
     */
    public function calculatePositionSize(
        float $portfolioValue,
        int $confidence,
        float $riskPercent = 2.0
    ): float {
        // Base allocation on confidence
        $baseAllocation = ($confidence / 100) * ($riskPercent / 100) * $portfolioValue;
        
        // Cap at 10% of portfolio
        $maxPosition = $portfolioValue * 0.10;
        
        return min($baseAllocation, $maxPosition);
    }
    
    /**
     * Generate strategy performance report
     * 
     * @param array $trades Array of completed trades
     * @return array Performance report
     */
    public function generateReport(array $trades): array
    {
        if (empty($trades)) {
            return [
                'total_trades' => 0,
                'win_rate' => 0,
                'average_return' => 0
            ];
        }
        
        $totalTrades = count($trades) / 2; // Buy + Sell = 1 trade
        $wins = 0;
        $totalReturn = 0;
        
        for ($i = 0; $i < count($trades); $i += 2) {
            if (isset($trades[$i + 1])) {
                $buyPrice = $trades[$i]['price'];
                $sellPrice = $trades[$i + 1]['price'];
                $return = (($sellPrice - $buyPrice) / $buyPrice) * 100;
                
                $totalReturn += $return;
                if ($return > 0) {
                    $wins++;
                }
            }
        }
        
        return [
            'total_trades' => (int) $totalTrades,
            'wins' => $wins,
            'losses' => $totalTrades - $wins,
            'win_rate' => $totalTrades > 0 ? round(($wins / $totalTrades) * 100, 2) : 0,
            'average_return' => $totalTrades > 0 ? round($totalReturn / $totalTrades, 2) : 0
        ];
    }
    
    /**
     * Get backtesting configuration
     * 
     * @return array Configuration for backtesting engine
     */
    public function getBacktestConfig(): array
    {
        return [
            'strategy_name' => 'CryptoETFArbitrage',
            'parameters' => [
                'entry_threshold' => $this->entryThreshold,
                'exit_threshold' => $this->exitThreshold,
                'min_confidence' => 60,
                'max_position_size' => 10.0
            ],
            'requires_nav_data' => true,
            'requires_premium_discount' => true
        ];
    }
    
    /**
     * Generate human-readable reason for action
     * 
     * @param string $action Trading action
     * @param float $premiumDiscount Premium/discount percentage
     * @return string Reason
     */
    private function generateReason(string $action, float $premiumDiscount): string
    {
        if ($action === 'BUY') {
            return sprintf(
                'ETF trading at %.2f%% discount - arbitrage opportunity',
                abs($premiumDiscount)
            );
        } elseif ($action === 'SELL') {
            return sprintf(
                'ETF premium at %.2f%% - take profit',
                $premiumDiscount
            );
        }
        
        return 'Premium/discount within neutral range';
    }
}
