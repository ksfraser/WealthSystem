<?php
namespace Ksfraser\Finance\Constants;

/**
 * Strategy Constants
 * 
 * Refactored from 2000/strategies/strategiesConstants.php
 * Provides consistent constants for trading actions across all strategies
 */
class StrategyConstants
{
    // Trading Actions
    const BUY = 10;
    const SELL = 20;
    const HOLD = 30;
    const STOP_LOSS = 40;
    const TAKE_PROFIT = 50;
    
    // Signal Strength Levels
    const SIGNAL_WEAK = 0.3;
    const SIGNAL_MODERATE = 0.5;
    const SIGNAL_STRONG = 0.7;
    const SIGNAL_VERY_STRONG = 0.9;
    
    // Risk Levels
    const RISK_VERY_LOW = 0.1;
    const RISK_LOW = 0.2;
    const RISK_MODERATE = 0.3;
    const RISK_HIGH = 0.4;
    const RISK_VERY_HIGH = 0.5;
    
    // Position Sizing Constants
    const MIN_POSITION_SIZE = 0.01;  // 1% minimum
    const MAX_POSITION_SIZE = 0.10;  // 10% maximum
    const DEFAULT_POSITION_SIZE = 0.02; // 2% default
    
    // Stop Loss Types
    const STOP_FIXED_PERCENT = 1;
    const STOP_ATR_MULTIPLE = 2;
    const STOP_SUPPORT_RESISTANCE = 3;
    const STOP_TRAILING = 4;
    
    // Market Conditions
    const MARKET_BULLISH = 1;
    const MARKET_BEARISH = 2;
    const MARKET_SIDEWAYS = 3;
    const MARKET_VOLATILE = 4;
    
    // Strategy Types
    const STRATEGY_TREND_FOLLOWING = 'trend_following';
    const STRATEGY_MEAN_REVERSION = 'mean_reversion';
    const STRATEGY_BREAKOUT = 'breakout';
    const STRATEGY_SUPPORT_RESISTANCE = 'support_resistance';
    const STRATEGY_TURTLE = 'turtle';
    const STRATEGY_TECHNICAL_ANALYSIS = 'technical_analysis';
    
    /**
     * Convert numeric action to string
     */
    public static function actionToString(int $action): string
    {
        switch ($action) {
            case self::BUY:
                return 'BUY';
            case self::SELL:
                return 'SELL';
            case self::HOLD:
                return 'HOLD';
            case self::STOP_LOSS:
                return 'STOP_LOSS';
            case self::TAKE_PROFIT:
                return 'TAKE_PROFIT';
            default:
                return 'UNKNOWN';
        }
    }
    
    /**
     * Convert string action to numeric
     */
    public static function stringToAction(string $action): int
    {
        switch (strtoupper($action)) {
            case 'BUY':
                return self::BUY;
            case 'SELL':
                return self::SELL;
            case 'HOLD':
                return self::HOLD;
            case 'STOP_LOSS':
                return self::STOP_LOSS;
            case 'TAKE_PROFIT':
                return self::TAKE_PROFIT;
            default:
                return self::HOLD;
        }
    }
    
    /**
     * Get signal strength category
     */
    public static function getSignalStrengthCategory(float $confidence): string
    {
        if ($confidence >= self::SIGNAL_VERY_STRONG) {
            return 'Very Strong';
        } elseif ($confidence >= self::SIGNAL_STRONG) {
            return 'Strong';
        } elseif ($confidence >= self::SIGNAL_MODERATE) {
            return 'Moderate';
        } elseif ($confidence >= self::SIGNAL_WEAK) {
            return 'Weak';
        } else {
            return 'Very Weak';
        }
    }
    
    /**
     * Get risk level category
     */
    public static function getRiskLevelCategory(float $risk): string
    {
        if ($risk >= self::RISK_VERY_HIGH) {
            return 'Very High';
        } elseif ($risk >= self::RISK_HIGH) {
            return 'High';
        } elseif ($risk >= self::RISK_MODERATE) {
            return 'Moderate';
        } elseif ($risk >= self::RISK_LOW) {
            return 'Low';
        } else {
            return 'Very Low';
        }
    }
    
    /**
     * Calculate position size based on risk and account size
     */
    public static function calculatePositionSize(float $accountSize, float $riskAmount, float $stopDistance): float
    {
        if ($stopDistance <= 0) {
            return self::MIN_POSITION_SIZE;
        }
        
        $dollarRisk = $accountSize * $riskAmount;
        $positionSize = $dollarRisk / $stopDistance;
        
        // Apply limits
        $maxDollarPosition = $accountSize * self::MAX_POSITION_SIZE;
        $minDollarPosition = $accountSize * self::MIN_POSITION_SIZE;
        
        return max($minDollarPosition, min($maxDollarPosition, $positionSize));
    }
    
    /**
     * Validate trading action
     */
    public static function isValidAction(int $action): bool
    {
        return in_array($action, [
            self::BUY,
            self::SELL,
            self::HOLD,
            self::STOP_LOSS,
            self::TAKE_PROFIT
        ]);
    }
    
    /**
     * Get all available actions
     */
    public static function getAllActions(): array
    {
        return [
            self::BUY => 'Buy',
            self::SELL => 'Sell',
            self::HOLD => 'Hold',
            self::STOP_LOSS => 'Stop Loss',
            self::TAKE_PROFIT => 'Take Profit'
        ];
    }
    
    /**
     * Get all strategy types
     */
    public static function getAllStrategyTypes(): array
    {
        return [
            self::STRATEGY_TREND_FOLLOWING => 'Trend Following',
            self::STRATEGY_MEAN_REVERSION => 'Mean Reversion',
            self::STRATEGY_BREAKOUT => 'Breakout',
            self::STRATEGY_SUPPORT_RESISTANCE => 'Support & Resistance',
            self::STRATEGY_TURTLE => 'Turtle Trading',
            self::STRATEGY_TECHNICAL_ANALYSIS => 'Technical Analysis'
        ];
    }
}
