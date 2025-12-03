# Multi-User Risk Management - Quick Reference

## Overview

✅ **Each user has their own risk management configuration**  
✅ **Settings are stored in the database and persist across sessions**  
✅ **Three preset profiles: Conservative, Balanced, Aggressive**  
✅ **Automatic integration with backtesting - no manual configuration needed**

---

## For Users

### Choose Your Risk Profile

**Conservative** - Capital Preservation
- 5% per trade, max 3 positions
- 8% stop loss, 12% take profit
- Trailing starts at 3% gain
- Frequent profit-taking (5%, 10%, 15%)

**Balanced** - Growth with Protection (DEFAULT)
- 10% per trade, max 5 positions  
- 10% stop loss, 20% take profit
- Trailing starts at 5% gain
- Moderate profit-taking (10%, 20%, 30%)

**Aggressive** - Maximum Growth
- 15% per trade, max 7 positions
- 15% stop loss, 30% take profit  
- Trailing starts at 10% gain
- Let winners run (15%, 40%)

### How to Use

```php
// Run any backtest - your settings are automatically applied
$service = new UserBacktestingService();
$results = $service->runBacktestForUser(123, $strategy, $data);

// View your current settings
$summary = $service->getUserRiskSummary(123);
print_r($summary);

// Change your risk profile
$service->updateUserRiskProfile(123, 'aggressive');

// Fine-tune individual settings
$service->updateUserRiskSetting(123, 'default_stop_loss', 0.12);
$service->updateUserRiskSetting(123, 'trailing_stop_activation', 0.07);
```

---

## For Developers

### Setup for New User

```php
$dao = new UserRiskPreferencesDAO();

// Check if user has preferences
if (!$dao->hasPreferences($userId)) {
    // Create default balanced profile
    $prefs = $dao->createDefaultPreferences($userId);
}
```

### Run Backtest with User Preferences

```php
$service = new UserBacktestingService();

// Simplest usage - all settings automatic
$results = $service->runBacktestForUser(
    userId: $userId,
    strategy: $strategyInstance,
    historicalData: $historicalData
);

// With overrides for specific backtest
$results = $service->runBacktestForUser(
    userId: $userId,
    strategy: $strategyInstance,
    historicalData: $historicalData,
    optionOverrides: [
        'position_size' => 0.15,  // Override just for this test
        'max_holding_days' => 30
    ]
);
```

### Access User Preferences Directly

```php
$dao = new UserRiskPreferencesDAO();
$prefs = $dao->getUserPreferences($userId);

// Get settings
$positionSize = $prefs->getAttribute('default_position_size');
$stopLoss = $prefs->getAttribute('default_stop_loss');
$trailingConfig = $prefs->getTrailingStopConfig();

// Modify settings
$prefs->setRiskProfile('aggressive');
$dao->save($prefs);
```

### Strategy-Specific Overrides

```php
// User wants more aggressive settings for momentum strategies
$service->setStrategyOverride($userId, 'MomentumQualityStrategy', [
    'trailing_stop_distance' => 0.15,
    'position_size' => 0.12
]);

// When that strategy runs, overrides are applied
// Other strategies use default user preferences
```

### Portfolio Backtest

```php
$results = $service->runPortfolioBacktestForUser(
    userId: $userId,
    strategies: [
        'momentum' => [$momentumStrategy, 0.40],
        'mean_reversion' => [$meanRevStrategy, 0.30],
        'dividend' => [$dividendStrategy, 0.30]
    ],
    historicalData: $multiSymbolData
);

// User's max_positions and portfolio exposure limits are applied
```

---

## Configuration Reference

### Database Table: user_risk_preferences

| Field | Type | Description | Example |
|-------|------|-------------|---------|
| `user_id` | INTEGER | User ID (FK) | 123 |
| `default_position_size` | DECIMAL(5,4) | % of capital per trade | 0.10 (10%) |
| `max_positions` | INTEGER | Max concurrent positions | 5 |
| `default_stop_loss` | DECIMAL(5,4) | Fixed stop loss % | 0.10 (10%) |
| `default_take_profit` | DECIMAL(5,4) | Take profit target % | 0.20 (20%) |
| `enable_trailing_stop` | BOOLEAN | Use trailing stops | true |
| `trailing_stop_activation` | DECIMAL(5,4) | Activate after % gain | 0.05 (5%) |
| `trailing_stop_distance` | DECIMAL(5,4) | Trail % below high | 0.10 (10%) |
| `enable_partial_profits` | BOOLEAN | Use partial exits | true |
| `partial_profit_levels` | TEXT (JSON) | Profit-taking rules | [{"profit": 0.10, "sell_pct": 0.25}] |
| `risk_profile` | VARCHAR(20) | Profile name | 'balanced' |
| `commission_rate` | DECIMAL(6,5) | Trading commission | 0.001 (0.1%) |
| `slippage_rate` | DECIMAL(6,5) | Slippage estimate | 0.0005 (0.05%) |
| `initial_capital` | DECIMAL(15,2) | Starting capital | 100000.00 |
| `strategy_overrides` | TEXT (JSON) | Per-strategy settings | {"MomentumStrategy": {...}} |

### Partial Profit Levels Format

```json
[
    {"profit": 0.10, "sell_pct": 0.25},
    {"profit": 0.20, "sell_pct": 0.50},
    {"profit": 0.30, "sell_pct": 1.00}
]
```

Meaning:
- At 10% profit, sell 25% of position
- At 20% profit, sell 50% of original position
- At 30% profit, sell remaining position

### Strategy Overrides Format

```json
{
    "MomentumQualityStrategy": {
        "trailing_stop_distance": 0.15,
        "position_size": 0.12,
        "trailing_stop_activation": 0.08
    },
    "MeanReversionStrategy": {
        "trailing_stop_distance": 0.08,
        "partial_profit_taking": false
    }
}
```

---

## Migration & Setup

### Run Database Migration

```bash
# SQLite
sqlite3 your_database.db < database/migrations/create_user_risk_preferences_table.sql

# MySQL
mysql -u username -p database_name < database/migrations/create_user_risk_preferences_table.sql
```

### Populate Preferences for Existing Users

```php
// One-time setup script
require 'vendor/autoload.php';

use App\DAOs\UserRiskPreferencesDAO;

$dao = new UserRiskPreferencesDAO();
$users = getAllUsers(); // Your user retrieval logic

foreach ($users as $user) {
    if (!$dao->hasPreferences($user['id'])) {
        // Create with balanced profile by default
        $dao->createDefaultPreferences($user['id'], 'balanced');
        echo "Created preferences for user {$user['id']}\n";
    }
}
```

---

## API Endpoints (Example)

### GET /api/users/{userId}/risk-preferences

```json
{
    "profile": "balanced",
    "position_sizing": {
        "default_size": "10%",
        "max_positions": 5,
        "max_exposure": "100%"
    },
    "risk_management": {
        "stop_loss": "10%",
        "take_profit": "20%",
        "max_holding_days": null
    },
    "trailing_stop": {
        "enabled": true,
        "activation": "5% gain",
        "distance": "10% below high"
    },
    "partial_profits": {
        "enabled": true,
        "levels": [
            "At 10% gain, sell 25%",
            "At 20% gain, sell 50%",
            "At 30% gain, sell 100%"
        ]
    }
}
```

### PUT /api/users/{userId}/risk-profile

```json
{
    "profile": "aggressive"
}
```

### PATCH /api/users/{userId}/risk-preferences

```json
{
    "default_stop_loss": 0.12,
    "trailing_stop_activation": 0.07
}
```

### POST /api/users/{userId}/strategy-overrides

```json
{
    "strategy": "MomentumQualityStrategy",
    "overrides": {
        "trailing_stop_distance": 0.15,
        "position_size": 0.12
    }
}
```

---

## Testing

### Run Tests

```bash
cd Stock-Analysis
php vendor/bin/phpunit tests/Unit/Models/UserRiskPreferencesTest.php --testdox
```

**Expected Output**:
```
User Risk Preferences (Tests\Unit\Models\UserRiskPreferences)
 ✔ Default initialization balanced
 ✔ Set conservative profile
 ✔ Set aggressive profile
 ✔ Get backtest options
 ✔ Get backtest options with overrides
 ✔ Strategy overrides
 ✔ Partial profit levels
 ✔ Trailing stop config
 ✔ To array for database
 ✔ From array from database
 ✔ Risk profile differences

OK (11 tests, 68 assertions)
```

---

## FAQ

**Q: What happens when a new user is created?**  
A: No preferences exist initially. On first backtest, default "balanced" profile is auto-created.

**Q: Can users customize individual settings?**  
A: Yes! They can choose a preset profile or modify any individual setting.

**Q: Are strategy-specific overrides required?**  
A: No, they're optional. Most users will use the same settings for all strategies.

**Q: Can I temporarily override settings for a single backtest?**  
A: Yes, use `optionOverrides` parameter - it won't change the user's saved preferences.

**Q: What if a user deletes their account?**  
A: Foreign key cascade delete automatically removes their preferences.

**Q: How do I add a new risk profile preset?**  
A: Insert into `user_risk_preferences` with `user_id = 0` (template), then update `UserRiskPreferences::setRiskProfile()`.

**Q: Can admin override user settings?**  
A: Yes, admin can use `UserRiskPreferencesDAO` to modify any user's settings.

---

## Benefits

✅ **No Manual Configuration**: Run backtests without specifying options  
✅ **Consistency**: Same risk settings across all backtests for a user  
✅ **Flexibility**: Easy to change profiles or fine-tune individual settings  
✅ **Strategy-Specific**: Different settings for different strategy types  
✅ **Persistent**: Settings survive server restarts and sessions  
✅ **Scalable**: Each user independent, no global configuration conflicts  
✅ **Auditable**: All settings stored in database with timestamps  

---

## Summary

**For Users**: Choose a risk profile (conservative/balanced/aggressive) and the system handles the rest.

**For Developers**: Call `runBacktestForUser()` with user ID - preferences are automatically loaded and applied.

**For Admins**: Manage user risk preferences through DAO or via API endpoints.

**Result**: Personalized risk management without code changes or manual configuration.
