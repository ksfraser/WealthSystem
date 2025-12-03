# Strategy Parameters Configuration System

## Overview

The Strategy Parameters Configuration System provides a flexible, database-driven approach to managing trading strategy parameters. Instead of hardcoding values in PHP files, all strategy parameters are stored in a SQLite database and can be configured through a web-based UI.

## Features

✅ **Database-Driven**: All parameters stored in SQLite database  
✅ **Web UI**: User-friendly interface for configuration  
✅ **Type Safety**: Parameters validated by type (int, float, bool, string)  
✅ **Range Validation**: Min/max constraints enforced  
✅ **Category Organization**: Parameters grouped by category  
✅ **Export/Import**: JSON export/import for backup and sharing  
✅ **Real-time Updates**: Changes take effect immediately  
✅ **Fallback Defaults**: Uses hardcoded defaults if database unavailable

## Architecture

### Components

1. **Database Table**: `strategy_parameters`
   - Stores all configurable parameters
   - Includes metadata (display name, description, ranges, categories)
   - Supports multiple strategies

2. **Repository Layer**: `StrategyParametersRepository`
   - CRUD operations for parameters
   - Type casting and validation
   - Export/import functionality

3. **Service Layer**: `StrategyConfigurationService`
   - Business logic for parameter management
   - Validation rules
   - Strategy configuration loader

4. **Strategy Integration**
   - Strategies load parameters from database on construction
   - Falls back to hardcoded defaults if database unavailable
   - Can be overridden via `setParameters()` method

5. **Web UI**: `strategy-config.html`
   - Interactive configuration interface
   - Real-time validation
   - Category-based organization

6. **API**: `api/strategy-config.php`
   - RESTful endpoints for configuration
   - Handles AJAX requests from UI

## Setup

### 1. Initialize Database

Run the initialization script to create the database and populate default parameters:

```bash
php scripts/init-strategy-parameters-db.php
```

This will:
- Create `storage/database/stock_analysis.db`
- Create `strategy_parameters` table
- Insert default parameters for all strategies

### 2. Access Web UI

**Authentication Required**: You must be logged in to access the strategy configuration page.

Start your web server and navigate to the configuration page:

```bash
# PHP built-in server
php -S localhost:8000 -t web_ui
```

Then navigate to: `http://localhost:8000/strategy-config.php`

**Note**: The page requires user authentication. In production environments, consider restricting access to administrators only for security.

## Configured Strategies

### Warren Buffett Value Strategy (16 parameters)

**Business Tenets:**
- Minimum Operating History (Years)
- Require Simple Business
- Require Favorable Long-term Prospects

**Management Tenets:**
- Minimum Insider Ownership (%)
- Require Share Buybacks

**Financial Tenets:**
- Minimum ROE (%)
- Minimum Profit Margin (%)
- Maximum Debt-to-Equity
- Minimum FCF Growth

**Value Tenets:**
- Margin of Safety (%)
- Discount Rate (%)
- Perpetual Growth Rate (%)

**Moat Scoring:**
- Economic Moat Weight

**Risk Management:**
- Stop Loss (%)
- Take Profit (%)
- Maximum Position Size

### GARP Strategy (14 parameters)

**Growth Criteria:**
- Minimum Revenue Growth (20%)
- Minimum Earnings Growth (20%)

**Valuation:**
- Maximum PEG Ratio (1.0)

**Quality Filters:**
- Minimum Gross Margin (40%)
- Minimum Market Cap ($500M)

**Financial Strength:**
- Maximum Debt-to-Equity (1.0)
- Minimum Current Ratio (1.5)

**Institutional Interest:**
- Minimum Institutional Ownership (10%)
- Maximum Institutional Ownership (70%)

**Momentum:**
- Minimum 3-Month Momentum (5%)

**Analysis:**
- Lookback Periods (Quarters)

**Risk Management:**
- Stop Loss (20%)
- Take Profit (100%)
- Maximum Position Size (10%)

## Usage

### Via Web UI

1. Open `strategy-config.html` in browser
2. Select strategy from dropdown
3. Modify parameters as needed
4. Click "Save Changes"
5. Export configuration for backup (optional)

### Programmatically

```php
use App\Services\StrategyConfigurationService;
use App\Repositories\StrategyParametersRepository;

// Initialize
$repo = new StrategyParametersRepository($databasePath);
$configService = new StrategyConfigurationService($repo);

// Get configuration
$params = $configService->getConfiguration('Warren Buffett Value Strategy');

// Update configuration
$configService->updateConfiguration('Warren Buffett Value Strategy', [
    'min_roe_percent' => 20.0,  // Increase ROE requirement to 20%
    'margin_of_safety_percent' => 30.0  // Increase margin to 30%
]);

// Export to JSON
$json = $configService->exportToJson('Warren Buffett Value Strategy');
file_put_contents('backup.json', $json);

// Import from JSON
$json = file_get_contents('backup.json');
$configService->importFromJson('Warren Buffett Value Strategy', $json);
```

### In Strategy Classes

Strategies automatically load parameters from database:

```php
// Warren Buffett Strategy loads params on construction
$strategy = new WarrenBuffettStrategyService($marketDataService, $repository);

// Parameters are loaded from database automatically
$params = $strategy->getParameters();
echo $params['min_roe_percent'];  // Will show database value (e.g., 15.0)

// Can still override programmatically if needed
$strategy->setParameters(['min_roe_percent' => 18.0]);
```

## Database Schema

```sql
CREATE TABLE strategy_parameters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    strategy_name VARCHAR(100) NOT NULL,
    parameter_key VARCHAR(100) NOT NULL,
    parameter_value TEXT NOT NULL,
    parameter_type VARCHAR(20) NOT NULL DEFAULT 'float',
    display_name VARCHAR(200) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    min_value DECIMAL(20,6),
    max_value DECIMAL(20,6),
    display_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(strategy_name, parameter_key)
);
```

## API Endpoints

### GET `/api/strategy-config.php?action=list_strategies`
Returns list of all configured strategies.

### GET `/api/strategy-config.php?action=get_config&strategy={name}`
Returns all parameters for specified strategy.

### POST `/api/strategy-config.php`
```json
{
    "action": "save_config",
    "strategy": "Warren Buffett Value Strategy",
    "parameters": {
        "min_roe_percent": 20.0,
        "margin_of_safety_percent": 30.0
    }
}
```

### GET `/api/strategy-config.php?action=export&strategy={name}`
Exports strategy configuration as downloadable JSON.

## Adding New Strategies

To add parameters for a new strategy:

1. Add INSERT statements to migration SQL:

```sql
INSERT INTO strategy_parameters (strategy_name, parameter_key, parameter_value, parameter_type, display_name, description, category, min_value, max_value, display_order) VALUES
('My New Strategy', 'param1', '10.0', 'float', 'Parameter 1', 'Description here', 'Category', 0, 100, 1),
('My New Strategy', 'param2', '1', 'bool', 'Parameter 2', 'Enable feature', 'Category', 0, 1, 2);
```

2. Re-run initialization:

```bash
# Drop existing table and recreate
sqlite3 storage/database/stock_analysis.db "DROP TABLE IF EXISTS strategy_parameters;"
php scripts/init-strategy-parameters-db.php
```

3. Strategy will automatically load parameters on construction

## Best Practices

1. **Always validate**: Use `validateParameter()` before saving
2. **Backup regularly**: Export configurations before major changes
3. **Document ranges**: Set appropriate min/max values
4. **Use categories**: Organize parameters logically
5. **Test after changes**: Run tests after modifying parameters

## Troubleshooting

### Parameters not loading?

Check that database exists:
```bash
ls -la storage/database/stock_analysis.db
```

Re-initialize if needed:
```bash
php scripts/init-strategy-parameters-db.php
```

### UI not working?

1. Check browser console for JavaScript errors
2. Verify API endpoint is accessible
3. Check file permissions on database

### Tests failing?

Strategies use database values, not hardcoded defaults. Reset database:
```bash
sqlite3 storage/database/stock_analysis.db "DROP TABLE IF EXISTS strategy_parameters;"
php scripts/init-strategy-parameters-db.php
```

## Files

```
Stock-Analysis/
├── database/
│   └── migrations/
│       └── create_strategy_parameters_table.sql
├── app/
│   ├── Repositories/
│   │   ├── StrategyParametersRepositoryInterface.php
│   │   └── StrategyParametersRepository.php
│   └── Services/
│       ├── StrategyConfigurationService.php
│       └── Trading/
│           ├── WarrenBuffettStrategyService.php (loads from DB)
│           └── GARPStrategyService.php (loads from DB)
├── web_ui/
│   ├── strategy-config.html
│   └── api/
│       └── strategy-config.php
├── scripts/
│   └── init-strategy-parameters-db.php
└── storage/
    └── database/
        └── stock_analysis.db (created by init script)
```

## Future Enhancements

- [ ] User authentication/authorization
- [ ] Parameter change history/audit log
- [ ] A/B testing different parameter sets
- [ ] Machine learning parameter optimization
- [ ] Multi-user configurations
- [ ] Configuration templates/presets
