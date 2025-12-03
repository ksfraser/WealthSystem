<?php

namespace App\Services;

use App\DAOs\UserRiskPreferencesDAO;
use App\Models\UserRiskPreferences;
use App\Services\Trading\BacktestingFramework;

/**
 * User Backtesting Service
 * 
 * Integrates user risk preferences with backtesting framework.
 * Automatically applies user-specific risk management settings.
 * 
 * Usage:
 * ```php
 * $service = new UserBacktestingService();
 * $results = $service->runBacktestForUser($userId, $strategy, $historicalData);
 * ```
 */
class UserBacktestingService
{
    private UserRiskPreferencesDAO $preferencesDAO;
    
    public function __construct(?UserRiskPreferencesDAO $preferencesDAO = null)
    {
        $this->preferencesDAO = $preferencesDAO ?? new UserRiskPreferencesDAO();
    }
    
    /**
     * Run backtest using user's risk preferences
     * 
     * @param int $userId User ID
     * @param object $strategy Trading strategy instance
     * @param array $historicalData Historical price data
     * @param array $optionOverrides Optional overrides for specific backtest
     * @return array Backtest results
     */
    public function runBacktestForUser(
        int $userId,
        object $strategy,
        array $historicalData,
        array $optionOverrides = []
    ): array {
        // Load user preferences
        $preferences = $this->preferencesDAO->getUserPreferences($userId);
        
        // Get backtesting options from preferences
        $options = $preferences->getBacktestOptions($optionOverrides);
        
        // Check for strategy-specific overrides
        $strategyName = get_class($strategy);
        $strategyOverride = $preferences->getStrategyOverride($strategyName);
        if (!empty($strategyOverride)) {
            $options = array_merge($options, $strategyOverride);
        }
        
        // Initialize backtesting framework with user's capital settings
        $framework = new BacktestingFramework(
            initialCapital: $preferences->getAttribute('initial_capital') ?? 100000,
            commissionRate: $preferences->getAttribute('commission_rate') ?? 0.001,
            slippageRate: $preferences->getAttribute('slippage_rate') ?? 0.0005
        );
        
        // Run backtest with user's risk management settings
        return $framework->runBacktest($strategy, $historicalData, $options);
    }
    
    /**
     * Run portfolio backtest using user's risk preferences
     * 
     * @param int $userId User ID
     * @param array $strategies Array of [name => [strategy, weight]]
     * @param array $historicalData Multi-symbol historical data
     * @param array $optionOverrides Optional overrides
     * @return array Portfolio backtest results
     */
    public function runPortfolioBacktestForUser(
        int $userId,
        array $strategies,
        array $historicalData,
        array $optionOverrides = []
    ): array {
        $preferences = $this->preferencesDAO->getUserPreferences($userId);
        
        // Get base options
        $options = $preferences->getBacktestOptions($optionOverrides);
        
        // Add portfolio-specific settings
        $options['max_positions'] = $preferences->getAttribute('max_positions') ?? 5;
        $options['max_portfolio_exposure'] = $preferences->getAttribute('max_portfolio_exposure') ?? 1.0;
        
        $framework = new BacktestingFramework(
            initialCapital: $preferences->getAttribute('initial_capital') ?? 100000,
            commissionRate: $preferences->getAttribute('commission_rate') ?? 0.001,
            slippageRate: $preferences->getAttribute('slippage_rate') ?? 0.0005
        );
        
        return $framework->runPortfolioBacktest($strategies, $historicalData, $options);
    }
    
    /**
     * Get user's current risk configuration summary
     * 
     * @param int $userId
     * @return array Human-readable summary
     */
    public function getUserRiskSummary(int $userId): array
    {
        $preferences = $this->preferencesDAO->getUserPreferences($userId);
        
        $trailingConfig = $preferences->getTrailingStopConfig();
        $partialLevels = $preferences->getPartialProfitLevels();
        
        return [
            'profile' => $preferences->getRiskProfile(),
            'position_sizing' => [
                'default_size' => ($preferences->getAttribute('default_position_size') * 100) . '%',
                'max_positions' => $preferences->getAttribute('max_positions'),
                'max_exposure' => ($preferences->getAttribute('max_portfolio_exposure') * 100) . '%'
            ],
            'risk_management' => [
                'stop_loss' => $preferences->getAttribute('default_stop_loss') 
                    ? ($preferences->getAttribute('default_stop_loss') * 100) . '%' 
                    : 'None',
                'take_profit' => $preferences->getAttribute('default_take_profit')
                    ? ($preferences->getAttribute('default_take_profit') * 100) . '%'
                    : 'None',
                'max_holding_days' => $preferences->getAttribute('default_max_holding_days') ?? 'None'
            ],
            'trailing_stop' => [
                'enabled' => $trailingConfig['enabled'],
                'activation' => ($trailingConfig['activation'] * 100) . '% gain',
                'distance' => ($trailingConfig['distance'] * 100) . '% below high'
            ],
            'partial_profits' => [
                'enabled' => $preferences->getAttribute('enable_partial_profits'),
                'levels' => array_map(function($level) {
                    return sprintf(
                        'At %d%% gain, sell %d%%',
                        $level['profit'] * 100,
                        $level['sell_pct'] * 100
                    );
                }, $partialLevels)
            ],
            'costs' => [
                'commission' => ($preferences->getAttribute('commission_rate') * 100) . '%',
                'slippage' => ($preferences->getAttribute('slippage_rate') * 100) . '%',
                'initial_capital' => '$' . number_format($preferences->getAttribute('initial_capital'), 2)
            ]
        ];
    }
    
    /**
     * Update user's risk profile (applies preset configuration)
     * 
     * @param int $userId
     * @param string $profile conservative|balanced|aggressive
     * @return bool
     */
    public function updateUserRiskProfile(int $userId, string $profile): bool
    {
        $preferences = $this->preferencesDAO->getUserPreferences($userId);
        $preferences->setRiskProfile($profile);
        return $this->preferencesDAO->save($preferences);
    }
    
    /**
     * Update specific risk setting for user
     * 
     * @param int $userId
     * @param string $setting Setting name
     * @param mixed $value New value
     * @return bool
     */
    public function updateUserRiskSetting(int $userId, string $setting, $value): bool
    {
        return $this->preferencesDAO->updateField($userId, $setting, $value);
    }
    
    /**
     * Set strategy-specific risk override for user
     * 
     * Example: User wants more aggressive settings for momentum strategies
     * 
     * @param int $userId
     * @param string $strategyName Strategy class name
     * @param array $overrides Risk settings specific to this strategy
     * @return bool
     */
    public function setStrategyOverride(int $userId, string $strategyName, array $overrides): bool
    {
        $preferences = $this->preferencesDAO->getUserPreferences($userId);
        $preferences->setStrategyOverride($strategyName, $overrides);
        return $this->preferencesDAO->save($preferences);
    }
    
    /**
     * Compare different risk profiles for user decision-making
     * 
     * @return array Comparison of conservative, balanced, aggressive profiles
     */
    public function compareRiskProfiles(): array
    {
        $profiles = ['conservative', 'balanced', 'aggressive'];
        $comparison = [];
        
        foreach ($profiles as $profile) {
            $template = $this->preferencesDAO->getProfileTemplate($profile);
            
            $comparison[$profile] = [
                'position_size' => ($template->getAttribute('default_position_size') * 100) . '%',
                'max_positions' => $template->getAttribute('max_positions'),
                'stop_loss' => ($template->getAttribute('default_stop_loss') * 100) . '%',
                'take_profit' => ($template->getAttribute('default_take_profit') * 100) . '%',
                'trailing_activation' => ($template->getAttribute('trailing_stop_activation') * 100) . '%',
                'trailing_distance' => ($template->getAttribute('trailing_stop_distance') * 100) . '%',
                'partial_profit_levels' => count($template->getPartialProfitLevels()),
                'best_for' => $this->getProfileDescription($profile)
            ];
        }
        
        return $comparison;
    }
    
    /**
     * Get description of risk profile
     */
    private function getProfileDescription(string $profile): string
    {
        $descriptions = [
            'conservative' => 'Capital preservation, smaller positions, tight stops, frequent profit-taking',
            'balanced' => 'Growth with protection, moderate positions, balanced risk management',
            'aggressive' => 'Maximum growth, larger positions, wider stops, let winners run'
        ];
        
        return $descriptions[$profile] ?? 'Unknown profile';
    }
}
