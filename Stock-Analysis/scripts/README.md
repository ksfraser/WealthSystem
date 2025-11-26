# Symbol Management Scripts

This directory contains scripts for managing the per-symbol table architecture in your ChatGPT Micro-Cap Experiment.

## Overview

The per-symbol table system addresses filesystem limitations by separating large monolithic tables into individual tables for each stock symbol. This prevents export file size issues and improves performance.

## Scripts

### 1. AddNewSymbol.php
Creates all necessary tables for a new stock symbol.

**Usage:**
```powershell
php scripts/AddNewSymbol.php IBM
php scripts/AddNewSymbol.php MSFT
```

**What it does:**
- Registers the symbol in the symbol registry
- Creates 7 tables per symbol:
  - `SYMBOL_prices` (historical price data)
  - `SYMBOL_indicators` (technical indicators)
  - `SYMBOL_patterns` (candlestick patterns)
  - `SYMBOL_support_resistance` (support/resistance levels)
  - `SYMBOL_signals` (trading signals)
  - `SYMBOL_earnings` (earnings data)
  - `SYMBOL_dividends` (dividend data)

### 2. BulkImportSymbols.php
Creates tables for multiple symbols at once.

**Usage:**
```powershell
# From command line list
php scripts/BulkImportSymbols.php --symbols=IBM,MSFT,AAPL,GOOGL

# From file
php scripts/BulkImportSymbols.php --file=symbols.txt

# Dry run (see what would be done)
php scripts/BulkImportSymbols.php --symbols=IBM,MSFT --dry-run
```

**File format (symbols.txt):**
```
IBM
MSFT
AAPL
# Comments start with #
GOOGL
```

### 3. MigrateToPerSymbolTables.php
Migrates existing data from monolithic tables to per-symbol tables.

**Usage:**
```powershell
# Migrate all symbols
php scripts/MigrateToPerSymbolTables.php

# Migrate specific symbol
php scripts/MigrateToPerSymbolTables.php --symbol=IBM

# Dry run to see what would be migrated
php scripts/MigrateToPerSymbolTables.php --dry-run

# Custom batch size
php scripts/MigrateToPerSymbolTables.php --batch-size=500
```

**Legacy tables it can migrate:**
- `historical_prices` → `SYMBOL_prices`
- `technical_indicators` → `SYMBOL_indicators`
- `candlestick_patterns` → `SYMBOL_patterns`
- `support_resistance` → `SYMBOL_support_resistance`
- `trading_signals` → `SYMBOL_signals`
- `earnings_data` → `SYMBOL_earnings`
- `dividend_data` → `SYMBOL_dividends`

### 4. ManageSymbols.php
View and manage existing symbols and their tables.

**Usage:**
```powershell
# List all symbols
php scripts/ManageSymbols.php list

# Show statistics for a symbol
php scripts/ManageSymbols.php stats IBM

# Check table integrity
php scripts/ManageSymbols.php check
php scripts/ManageSymbols.php check IBM

# Remove symbol and all its data (destructive!)
php scripts/ManageSymbols.php remove IBM

# Deactivate/activate symbols
php scripts/ManageSymbols.php deactivate IBM
php scripts/ManageSymbols.php activate IBM

# Clean up orphaned tables
php scripts/ManageSymbols.php cleanup --verbose
```

## Integration with Your Code

### Legacy Code Integration
Update your existing PHP code to use the new `DynamicStockDataAccess` class:

```php
require_once 'DynamicStockDataAccess.php';

$dataAccess = new DynamicStockDataAccess();

// Insert price data
$dataAccess->insertPriceData('IBM', $priceDataArray);

// Get price data for analysis
$prices = $dataAccess->getPriceDataForAnalysis('IBM', 200);

// Insert technical indicators
$dataAccess->insertTechnicalIndicator('IBM', [
    'indicator_type' => 'RSI',
    'date' => '2024-01-01',
    'value' => 65.5
]);
```

### ChatGPT Mini Code Integration
When your ChatGPT system adds new symbols, use the AddNewSymbol script:

```php
// In your ChatGPT integration code
$symbol = 'NEWSTOCK';

// Add symbol via script
$command = "php " . __DIR__ . "/scripts/AddNewSymbol.php {$symbol}";
exec($command, $output, $returnCode);

if ($returnCode === 0) {
    echo "Symbol {$symbol} ready for analysis";
} else {
    echo "Failed to add symbol {$symbol}";
}
```

## Workflow

### For New Symbols
1. **Add Symbol:** `php scripts/AddNewSymbol.php SYMBOL`
2. **Import Data:** Use your existing data import processes with `DynamicStockDataAccess`
3. **Run Analysis:** Your job processors will automatically use the new tables

### For Existing Data Migration
1. **Backup:** Always backup your database before migration
2. **Dry Run:** `php scripts/MigrateToPerSymbolTables.php --dry-run`
3. **Migrate:** `php scripts/MigrateToPerSymbolTables.php`
4. **Verify:** `php scripts/ManageSymbols.php check`
5. **Update Code:** Replace direct SQL queries with `DynamicStockDataAccess` calls

## Benefits

### Solved Problems
- **Filesystem Limitations:** No more huge export files causing data loss
- **Performance:** Smaller tables = faster queries and exports
- **Scalability:** Add unlimited symbols without table size concerns
- **Maintenance:** Easy to backup/restore individual symbol data

### New Capabilities
- **Per-Symbol Exports:** Export data for individual symbols
- **Selective Analysis:** Run analysis on specific symbols only
- **Easy Cleanup:** Remove old symbols and their data easily
- **Better Monitoring:** Track table sizes and record counts per symbol

## Database Schema

Each symbol gets these tables created automatically:

```sql
-- Price data
CREATE TABLE SYMBOL_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL,
    date DATE NOT NULL,
    open DECIMAL(10,4) NOT NULL,
    high DECIMAL(10,4) NOT NULL,
    low DECIMAL(10,4) NOT NULL,
    close DECIMAL(10,4) NOT NULL,
    volume BIGINT,
    -- ... additional columns
);

-- Technical indicators
CREATE TABLE SYMBOL_indicators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL,
    date DATE NOT NULL,
    indicator_name VARCHAR(50) NOT NULL,
    value DECIMAL(15,6),
    -- ... additional columns
);

-- And 5 more tables per symbol...
```

## Troubleshooting

### Common Issues

1. **Permission Errors:** Ensure PHP has database CREATE/DROP table permissions
2. **Memory Issues:** Use smaller `--batch-size` for large migrations
3. **Missing Tables:** Run `php scripts/ManageSymbols.php check` to identify issues
4. **Orphaned Tables:** Use `php scripts/ManageSymbols.php cleanup` to remove unused tables

### Getting Help

- Use `--help` option on any script for detailed usage
- Use `--dry-run` to preview changes before executing
- Check logs for detailed error messages during migration

## Next Steps

1. **Test Migration:** Start with a few symbols using dry-run mode
2. **Update Job Processors:** The system is already updated to use the new architecture
3. **Monitor Performance:** Use the stats command to track table sizes
4. **Gradual Migration:** Migrate symbols in batches if you have many

The per-symbol table architecture ensures your analysis system can scale without filesystem limitations while maintaining all existing functionality.
