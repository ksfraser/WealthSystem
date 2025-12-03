<?php

namespace App\Models;

use App\Core\Interfaces\ModelInterface;

/**
 * User Risk Preferences Model
 * 
 * Stores and manages per-user risk management configuration including:
 * - Position sizing and portfolio limits
 * - Stop loss and take profit settings
 * - Trailing stop configuration
 * - Partial profit-taking rules
 * - Risk profile presets (conservative/balanced/aggressive)
 * 
 * @property int $user_id
 * @property float $default_position_size
 * @property int $max_positions
 * @property float|null $default_stop_loss
 * @property float|null $default_take_profit
 * @property bool $enable_trailing_stop
 * @property float $trailing_stop_activation
 * @property float $trailing_stop_distance
 * @property bool $enable_partial_profits
 * @property array $partial_profit_levels
 * @property string $risk_profile
 */
class UserRiskPreferences extends BaseModel implements ModelInterface
{
    protected array $validationRules = [
        'user_id' => ['required', 'integer', 'min:1'],
        'default_position_size' => ['required', 'float', 'min:0.01', 'max:1.0'],
        'max_positions' => ['required', 'integer', 'min:1', 'max:50'],
        'risk_profile' => ['required', 'in:conservative,balanced,aggressive']
    ];
    
    /**
     * Initialize with default values
     */
    public function __construct(array $data = [])
    {
        // Set default attributes based on balanced risk profile
        $this->attributes = [
            'user_id' => null,
            'default_position_size' => 0.10,
            'max_positions' => 5,
            'max_portfolio_exposure' => 1.00,
            'default_stop_loss' => 0.10,
            'default_take_profit' => 0.20,
            'default_max_holding_days' => null,
            'enable_trailing_stop' => true,
            'trailing_stop_activation' => 0.05,
            'trailing_stop_distance' => 0.10,
            'enable_partial_profits' => true,
            'partial_profit_levels' => [
                ['profit' => 0.10, 'sell_pct' => 0.25],
                ['profit' => 0.20, 'sell_pct' => 0.50],
                ['profit' => 0.30, 'sell_pct' => 1.00]
            ],
            'risk_profile' => 'balanced',
            'commission_rate' => 0.001,
            'slippage_rate' => 0.0005,
            'initial_capital' => 100000.00,
            'strategy_overrides' => [],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        parent::__construct($data);
    }
    
    /**
     * Get attribute value
     * 
     * @param string $key
     * @return mixed
     */
    public function getAttribute(string $key)
    {
        return $this->attributes[$key] ?? null;
    }
    
    /**
     * Get user ID
     */
    public function getUserId(): ?int
    {
        return $this->attributes['user_id'] ?? null;
    }
    
    /**
     * Set user ID
     */
    public function setUserId(int $userId): void
    {
        $this->attributes['user_id'] = $userId;
    }
    
    /**
     * Get risk profile
     */
    public function getRiskProfile(): string
    {
        return $this->attributes['risk_profile'] ?? 'balanced';
    }
    
    /**
     * Set risk profile and apply preset configuration
     */
    public function setRiskProfile(string $profile): void
    {
        $presets = [
            'conservative' => [
                'default_position_size' => 0.05,
                'max_positions' => 3,
                'default_stop_loss' => 0.08,
                'default_take_profit' => 0.12,
                'trailing_stop_activation' => 0.03,
                'trailing_stop_distance' => 0.08,
                'partial_profit_levels' => [
                    ['profit' => 0.05, 'sell_pct' => 0.20],
                    ['profit' => 0.10, 'sell_pct' => 0.50],
                    ['profit' => 0.15, 'sell_pct' => 1.00]
                ]
            ],
            'balanced' => [
                'default_position_size' => 0.10,
                'max_positions' => 5,
                'default_stop_loss' => 0.10,
                'default_take_profit' => 0.20,
                'trailing_stop_activation' => 0.05,
                'trailing_stop_distance' => 0.10,
                'partial_profit_levels' => [
                    ['profit' => 0.10, 'sell_pct' => 0.25],
                    ['profit' => 0.20, 'sell_pct' => 0.50],
                    ['profit' => 0.30, 'sell_pct' => 1.00]
                ]
            ],
            'aggressive' => [
                'default_position_size' => 0.15,
                'max_positions' => 7,
                'default_stop_loss' => 0.15,
                'default_take_profit' => 0.30,
                'trailing_stop_activation' => 0.10,
                'trailing_stop_distance' => 0.15,
                'partial_profit_levels' => [
                    ['profit' => 0.15, 'sell_pct' => 0.30],
                    ['profit' => 0.40, 'sell_pct' => 1.00]
                ]
            ]
        ];
        
        if (isset($presets[$profile])) {
            $this->attributes['risk_profile'] = $profile;
            foreach ($presets[$profile] as $key => $value) {
                $this->attributes[$key] = $value;
            }
        }
    }
    
    /**
     * Get backtesting options array from preferences
     * 
     * @param array $overrides Additional options to override preferences
     * @return array Formatted options for BacktestingFramework
     */
    public function getBacktestOptions(array $overrides = []): array
    {
        $options = [
            'position_size' => $this->attributes['default_position_size'],
            'stop_loss' => $this->attributes['default_stop_loss'],
            'take_profit' => $this->attributes['default_take_profit'],
            'max_holding_days' => $this->attributes['default_max_holding_days'],
            'trailing_stop' => $this->attributes['enable_trailing_stop'],
            'trailing_stop_activation' => $this->attributes['trailing_stop_activation'],
            'trailing_stop_distance' => $this->attributes['trailing_stop_distance'],
            'partial_profit_taking' => $this->attributes['enable_partial_profits'],
            'profit_levels' => $this->attributes['partial_profit_levels']
        ];
        
        // Apply any overrides
        return array_merge($options, $overrides);
    }
    
    /**
     * Get strategy-specific override if exists
     * 
     * @param string $strategyName
     * @return array Options specific to strategy or empty array
     */
    public function getStrategyOverride(string $strategyName): array
    {
        $overrides = $this->attributes['strategy_overrides'] ?? [];
        return $overrides[$strategyName] ?? [];
    }
    
    /**
     * Set strategy-specific override
     * 
     * @param string $strategyName
     * @param array $options
     */
    public function setStrategyOverride(string $strategyName, array $options): void
    {
        if (!isset($this->attributes['strategy_overrides'])) {
            $this->attributes['strategy_overrides'] = [];
        }
        $this->attributes['strategy_overrides'][$strategyName] = $options;
    }
    
    /**
     * Get trailing stop configuration
     */
    public function getTrailingStopConfig(): array
    {
        return [
            'enabled' => $this->attributes['enable_trailing_stop'] ?? false,
            'activation' => $this->attributes['trailing_stop_activation'] ?? 0.05,
            'distance' => $this->attributes['trailing_stop_distance'] ?? 0.10
        ];
    }
    
    /**
     * Get partial profit levels
     */
    public function getPartialProfitLevels(): array
    {
        return $this->attributes['partial_profit_levels'] ?? [];
    }
    
    /**
     * Prepare data for database storage
     * Converts arrays to JSON
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        
        // Convert arrays to JSON for database storage
        if (isset($data['partial_profit_levels']) && is_array($data['partial_profit_levels'])) {
            $data['partial_profit_levels'] = json_encode($data['partial_profit_levels']);
        }
        if (isset($data['strategy_overrides']) && is_array($data['strategy_overrides'])) {
            $data['strategy_overrides'] = json_encode($data['strategy_overrides']);
        }
        
        // Convert booleans to integers for SQLite
        $data['enable_trailing_stop'] = $data['enable_trailing_stop'] ? 1 : 0;
        $data['enable_partial_profits'] = $data['enable_partial_profits'] ? 1 : 0;
        
        return $data;
    }
    
    /**
     * Load data from database (deserialize JSON fields)
     */
    public function fromArray(array $data): void
    {
        // Deserialize JSON fields
        if (isset($data['partial_profit_levels']) && is_string($data['partial_profit_levels'])) {
            $data['partial_profit_levels'] = json_decode($data['partial_profit_levels'], true) ?? [];
        }
        if (isset($data['strategy_overrides']) && is_string($data['strategy_overrides'])) {
            $data['strategy_overrides'] = json_decode($data['strategy_overrides'], true) ?? [];
        }
        
        // Convert integers to booleans
        if (isset($data['enable_trailing_stop'])) {
            $data['enable_trailing_stop'] = (bool)$data['enable_trailing_stop'];
        }
        if (isset($data['enable_partial_profits'])) {
            $data['enable_partial_profits'] = (bool)$data['enable_partial_profits'];
        }
        
        parent::fromArray($data);
    }
    
    /**
     * Validate partial profit levels format
     */
    private function validatePartialProfitLevels(array $levels): bool
    {
        foreach ($levels as $level) {
            if (!isset($level['profit']) || !isset($level['sell_pct'])) {
                return false;
            }
            if (!is_numeric($level['profit']) || !is_numeric($level['sell_pct'])) {
                return false;
            }
            if ($level['profit'] <= 0 || $level['sell_pct'] <= 0 || $level['sell_pct'] > 1) {
                return false;
            }
        }
        return true;
    }
}
