<?php

declare(strict_types=1);

namespace WealthSystem\Backtesting;

use InvalidArgumentException;

/**
 * Position Sizing Calculator
 * 
 * Implements multiple position sizing algorithms for portfolio management:
 * - Fixed Dollar: Fixed dollar amount per position
 * - Fixed Percent: Fixed percentage of portfolio per position
 * - Kelly Criterion: Optimal position size based on win rate and win/loss ratio
 * - Volatility-Based (ATR): Size positions inversely to volatility
 * - Risk Parity: Equal risk contribution across positions
 * 
 * Position sizing is critical for risk management and portfolio construction.
 * Different methods suit different strategies and risk profiles.
 * 
 * @package WealthSystem\Backtesting
 * @author WealthSystem Team
 */
class PositionSizer
{
    /**
     * Calculate position size using fixed dollar amount
     * 
     * Simplest method - same dollar amount for each position.
     * Pros: Simple, predictable
     * Cons: No adjustment for portfolio size or risk
     * 
     * @param float $portfolioValue Current portfolio value
     * @param float $fixedAmount Fixed dollar amount per position
     * @param float $currentPrice Current price of the asset
     * @return array ['shares' => int, 'value' => float, 'percent' => float]
     */
    public function fixedDollar(
        float $portfolioValue,
        float $fixedAmount,
        float $currentPrice
    ): array {
        if ($portfolioValue <= 0) {
            throw new InvalidArgumentException('Portfolio value must be positive');
        }
        if ($fixedAmount <= 0) {
            throw new InvalidArgumentException('Fixed amount must be positive');
        }
        if ($currentPrice <= 0) {
            throw new InvalidArgumentException('Current price must be positive');
        }
        
        // Ensure we don't exceed portfolio value
        $positionValue = min($fixedAmount, $portfolioValue);
        $shares = (int) floor($positionValue / $currentPrice);
        $actualValue = $shares * $currentPrice;
        $percent = $actualValue / $portfolioValue;
        
        return [
            'shares' => $shares,
            'value' => $actualValue,
            'percent' => $percent,
            'method' => 'fixed_dollar'
        ];
    }
    
    /**
     * Calculate position size using fixed percentage of portfolio
     * 
     * Most common method - allocate fixed % of portfolio to each position.
     * Pros: Scales with portfolio, consistent risk exposure
     * Cons: No adjustment for individual asset risk
     * 
     * @param float $portfolioValue Current portfolio value
     * @param float $percent Percentage of portfolio (0.0-1.0)
     * @param float $currentPrice Current price of the asset
     * @return array ['shares' => int, 'value' => float, 'percent' => float]
     */
    public function fixedPercent(
        float $portfolioValue,
        float $percent,
        float $currentPrice
    ): array {
        if ($portfolioValue <= 0) {
            throw new InvalidArgumentException('Portfolio value must be positive');
        }
        if ($percent <= 0 || $percent > 1) {
            throw new InvalidArgumentException('Percent must be between 0 and 1');
        }
        if ($currentPrice <= 0) {
            throw new InvalidArgumentException('Current price must be positive');
        }
        
        $positionValue = $portfolioValue * $percent;
        $shares = (int) floor($positionValue / $currentPrice);
        $actualValue = $shares * $currentPrice;
        $actualPercent = $actualValue / $portfolioValue;
        
        return [
            'shares' => $shares,
            'value' => $actualValue,
            'percent' => $actualPercent,
            'method' => 'fixed_percent'
        ];
    }
    
    /**
     * Calculate position size using Kelly Criterion
     * 
     * Optimal position sizing based on expected return and win probability.
     * Formula: f* = (p * b - q) / b
     * where p = win probability, q = 1-p, b = win/loss ratio
     * 
     * Pros: Mathematically optimal for maximizing long-term growth
     * Cons: Sensitive to parameter estimation, can be aggressive
     * 
     * Common practice: Use fractional Kelly (e.g., 50% of Kelly) to reduce risk
     * 
     * @param float $portfolioValue Current portfolio value
     * @param float $winProbability Probability of winning trade (0.0-1.0)
     * @param float $avgWin Average winning trade size (as ratio, e.g., 1.5 = 50% gain)
     * @param float $avgLoss Average losing trade size (as ratio, e.g., 0.9 = 10% loss)
     * @param float $currentPrice Current price of the asset
     * @param float $fraction Fraction of Kelly to use (default 0.5 = half Kelly)
     * @return array ['shares' => int, 'value' => float, 'percent' => float, 'kelly_percent' => float]
     */
    public function kellyCriterion(
        float $portfolioValue,
        float $winProbability,
        float $avgWin,
        float $avgLoss,
        float $currentPrice,
        float $fraction = 0.5
    ): array {
        if ($portfolioValue <= 0) {
            throw new InvalidArgumentException('Portfolio value must be positive');
        }
        if ($winProbability < 0 || $winProbability > 1) {
            throw new InvalidArgumentException('Win probability must be between 0 and 1');
        }
        if ($avgWin <= 0) {
            throw new InvalidArgumentException('Average win must be positive');
        }
        if ($avgLoss <= 0) {
            throw new InvalidArgumentException('Average loss must be positive');
        }
        if ($currentPrice <= 0) {
            throw new InvalidArgumentException('Current price must be positive');
        }
        if ($fraction <= 0 || $fraction > 1) {
            throw new InvalidArgumentException('Fraction must be between 0 and 1');
        }
        
        $lossProbability = 1 - $winProbability;
        
        // Calculate win/loss ratio (b in Kelly formula)
        // avgWin and avgLoss are already in ratio form
        $winLossRatio = $avgWin / $avgLoss;
        
        // Kelly formula: f* = (p * b - q) / b
        $kellyPercent = ($winProbability * $winLossRatio - $lossProbability) / $winLossRatio;
        
        // Apply safety constraints
        $kellyPercent = max(0, min($kellyPercent, 1)); // Clamp to [0, 1]
        $adjustedPercent = $kellyPercent * $fraction; // Apply fractional Kelly
        
        // Cap at 25% to prevent over-concentration
        $adjustedPercent = min($adjustedPercent, 0.25);
        
        $positionValue = $portfolioValue * $adjustedPercent;
        $shares = (int) floor($positionValue / $currentPrice);
        $actualValue = $shares * $currentPrice;
        $actualPercent = $actualValue / $portfolioValue;
        
        return [
            'shares' => $shares,
            'value' => $actualValue,
            'percent' => $actualPercent,
            'kelly_percent' => $kellyPercent,
            'adjusted_percent' => $adjustedPercent,
            'method' => 'kelly_criterion'
        ];
    }
    
    /**
     * Calculate position size based on volatility (ATR-based)
     * 
     * Size positions inversely to volatility - smaller positions in volatile assets.
     * Uses ATR (Average True Range) to measure volatility.
     * 
     * Formula: Position Size = (Portfolio Value * Risk Percent) / (ATR * ATR Multiplier)
     * 
     * Pros: Adjusts for individual asset risk, consistent risk per trade
     * Cons: Requires ATR calculation, may undersize in trending markets
     * 
     * @param float $portfolioValue Current portfolio value
     * @param float $riskPercent Percentage of portfolio to risk (e.g., 0.01 = 1%)
     * @param float $atr Average True Range (volatility measure)
     * @param float $currentPrice Current price of the asset
     * @param float $atrMultiplier ATR multiplier for stop loss (default 2.0)
     * @return array ['shares' => int, 'value' => float, 'percent' => float, 'risk_amount' => float]
     */
    public function volatilityBased(
        float $portfolioValue,
        float $riskPercent,
        float $atr,
        float $currentPrice,
        float $atrMultiplier = 2.0
    ): array {
        if ($portfolioValue <= 0) {
            throw new InvalidArgumentException('Portfolio value must be positive');
        }
        if ($riskPercent <= 0 || $riskPercent > 0.1) {
            throw new InvalidArgumentException('Risk percent must be between 0 and 0.1 (10%)');
        }
        if ($atr <= 0) {
            throw new InvalidArgumentException('ATR must be positive');
        }
        if ($currentPrice <= 0) {
            throw new InvalidArgumentException('Current price must be positive');
        }
        if ($atrMultiplier <= 0) {
            throw new InvalidArgumentException('ATR multiplier must be positive');
        }
        
        // Amount we're willing to risk on this trade
        $riskAmount = $portfolioValue * $riskPercent;
        
        // Stop loss distance (in dollars)
        $stopLossDistance = $atr * $atrMultiplier;
        
        // Position size = risk amount / stop loss distance
        $shares = (int) floor($riskAmount / $stopLossDistance);
        
        // Ensure we don't exceed reasonable position size (25% of portfolio)
        $maxShares = (int) floor(($portfolioValue * 0.25) / $currentPrice);
        $shares = min($shares, $maxShares);
        
        $actualValue = $shares * $currentPrice;
        $actualPercent = $actualValue / $portfolioValue;
        $stopLossPrice = $currentPrice - $stopLossDistance;
        
        return [
            'shares' => $shares,
            'value' => $actualValue,
            'percent' => $actualPercent,
            'risk_amount' => $riskAmount,
            'stop_loss_distance' => $stopLossDistance,
            'stop_loss_price' => $stopLossPrice,
            'atr_multiplier' => $atrMultiplier,
            'method' => 'volatility_based'
        ];
    }
    
    /**
     * Calculate position sizes for risk parity allocation
     * 
     * Allocate capital so each position contributes equal risk to portfolio.
     * Risk is measured by volatility (standard deviation of returns).
     * 
     * Formula: Weight_i = (1/Vol_i) / Sum(1/Vol_j) for all j
     * 
     * Pros: Balanced risk exposure, diversification-focused
     * Cons: Requires volatility estimates, may underweight high-return assets
     * 
     * @param float $portfolioValue Current portfolio value
     * @param array $assets Array of ['symbol' => string, 'volatility' => float, 'price' => float]
     * @return array Array of position sizes per asset
     */
    public function riskParity(float $portfolioValue, array $assets): array {
        if ($portfolioValue <= 0) {
            throw new InvalidArgumentException('Portfolio value must be positive');
        }
        if (empty($assets)) {
            throw new InvalidArgumentException('Assets array cannot be empty');
        }
        
        // Validate asset data
        foreach ($assets as $asset) {
            if (!isset($asset['symbol'], $asset['volatility'], $asset['price'])) {
                throw new InvalidArgumentException('Each asset must have symbol, volatility, and price');
            }
            if ($asset['volatility'] <= 0) {
                throw new InvalidArgumentException('Volatility must be positive for ' . $asset['symbol']);
            }
            if ($asset['price'] <= 0) {
                throw new InvalidArgumentException('Price must be positive for ' . $asset['symbol']);
            }
        }
        
        // Calculate inverse volatility for each asset
        $inverseVols = [];
        $sumInverseVol = 0;
        
        foreach ($assets as $asset) {
            $inverseVol = 1 / $asset['volatility'];
            $inverseVols[$asset['symbol']] = $inverseVol;
            $sumInverseVol += $inverseVol;
        }
        
        // Calculate weights (proportional to inverse volatility)
        $positions = [];
        $totalValue = 0;
        
        foreach ($assets as $asset) {
            $weight = $inverseVols[$asset['symbol']] / $sumInverseVol;
            $positionValue = $portfolioValue * $weight;
            $shares = (int) floor($positionValue / $asset['price']);
            $actualValue = $shares * $asset['price'];
            
            $positions[$asset['symbol']] = [
                'shares' => $shares,
                'value' => $actualValue,
                'percent' => $actualValue / $portfolioValue,
                'target_weight' => $weight,
                'volatility' => $asset['volatility'],
                'inverse_volatility' => $inverseVols[$asset['symbol']]
            ];
            
            $totalValue += $actualValue;
        }
        
        return [
            'positions' => $positions,
            'total_value' => $totalValue,
            'cash_remaining' => $portfolioValue - $totalValue,
            'method' => 'risk_parity'
        ];
    }
    
    /**
     * Calculate maximum safe position size given leverage and margin requirements
     * 
     * Determines maximum position size based on:
     * - Available buying power
     * - Margin requirements
     * - Leverage limits
     * 
     * @param float $portfolioValue Current portfolio value
     * @param float $availableCash Cash available for trading
     * @param float $marginRequirement Margin requirement (e.g., 0.5 = 50%)
     * @param float $maxLeverage Maximum allowed leverage (e.g., 2.0 = 2x)
     * @param float $currentPrice Current price of the asset
     * @return array ['shares' => int, 'value' => float, 'margin_used' => float, 'leverage' => float]
     */
    public function maxPositionWithMargin(
        float $portfolioValue,
        float $availableCash,
        float $marginRequirement,
        float $maxLeverage,
        float $currentPrice
    ): array {
        if ($portfolioValue <= 0) {
            throw new InvalidArgumentException('Portfolio value must be positive');
        }
        if ($availableCash < 0) {
            throw new InvalidArgumentException('Available cash cannot be negative');
        }
        if ($marginRequirement <= 0 || $marginRequirement > 1) {
            throw new InvalidArgumentException('Margin requirement must be between 0 and 1');
        }
        if ($maxLeverage < 1) {
            throw new InvalidArgumentException('Max leverage must be >= 1');
        }
        if ($currentPrice <= 0) {
            throw new InvalidArgumentException('Current price must be positive');
        }
        
        // Maximum position value based on buying power
        $maxPositionValue = $availableCash / $marginRequirement;
        
        // Apply leverage limit
        $leveragedMaxValue = $portfolioValue * $maxLeverage;
        $maxPositionValue = min($maxPositionValue, $leveragedMaxValue);
        
        $shares = (int) floor($maxPositionValue / $currentPrice);
        $actualValue = $shares * $currentPrice;
        $marginUsed = $actualValue * $marginRequirement;
        $leverage = $actualValue / $portfolioValue;
        
        return [
            'shares' => $shares,
            'value' => $actualValue,
            'margin_used' => $marginUsed,
            'leverage' => $leverage,
            'buying_power_used' => $marginUsed,
            'method' => 'max_margin'
        ];
    }
}
