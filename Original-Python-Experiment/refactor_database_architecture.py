#!/usr/bin/env python3
"""
Database Architecture Refactoring Plan
Implements the new database separation strategy
"""

import sys
import yaml
import os
from pathlib import Path

def create_refactored_database_config():
    """Create the new database configuration with proper separation."""
    
    # Load existing configuration from db_config_refactored.yml if it exists
    config_file = 'db_config_refactored.yml'
    existing_config = {}
    
    if os.path.exists(config_file):
        try:
            with open(config_file, 'r') as f:
                existing_config = yaml.safe_load(f)
        except Exception as e:
            print(f"Warning: Could not load existing config: {e}")
    
    # Get database connection details from existing config or use defaults
    db_config = existing_config.get('database', {})
    
    new_config = {
        'database': {
            'host': db_config.get('host', 'localhost'),
            'port': db_config.get('port', 3306),
            'username': db_config.get('username', 'root'),
            'password': db_config.get('password', ''),
            'pool': {
                'max_connections': 10,
                'pool_name': 'trading_pool',
                'pool_reset_session': True
            },
            
            # MICRO-CAP DATABASE: Only CSV-mirrored data
            'micro_cap': {
                'database': 'stock_market_micro_cap_trading',
                'description': 'CSV-mirrored data only - original trading_script.py functionality',
                'purpose': 'csv_mirror',
                'tables': [
                    'portfolio_data',    # Mirrors micro_cap_portfolio.csv
                    'trade_log',         # Mirrors micro_cap_trade_log.csv
                    'historical_prices'  # Basic price history for micro-cap stocks
                ]
            },
            
            # MASTER DATABASE: All enhanced features and new data
            'master': {
                'database': 'stock_market_2',
                'description': 'Master database for all enhanced features, analytics, and web UI',
                'purpose': 'enhanced_features',
                'tables': [
                    # Multi-market cap portfolios
                    'portfolios_blue_chip',
                    'portfolios_small_cap',
                    'portfolios_mid_cap',
                    'portfolios_large_cap',
                    
                    # Enhanced trade tracking
                    'trades_blue_chip',
                    'trades_small_cap', 
                    'trades_mid_cap',
                    'trades_large_cap',
                    
                    # Analytics and reporting
                    'portfolio_performance',
                    'risk_metrics',
                    'market_analysis',
                    
                    # LLM and automation
                    'llm_interactions',
                    'trading_sessions',
                    'automation_logs',
                    
                    # Web UI and user management
                    'user_preferences',
                    'dashboard_configs',
                    'alerts_notifications',
                    
                    # Advanced features
                    'backtesting_results',
                    'strategy_configurations',
                    'market_sentiment'
                ]
            },
            
            # Legacy alias for backward compatibility
            'legacy': {
                'database': 'stock_market_2',
                'description': 'Alias for master database (backward compatibility)'
            }
        },
        
        # Rest of configuration
        'api': {
            'rate_limits': {
                'llm_requests_per_minute': 10,
                'market_data_requests_per_minute': 60
            },
            'timeouts': {
                'llm_timeout_seconds': 30,
                'market_data_timeout_seconds': 10
            }
        },
        
        'application': {
            'logging': {
                'backup_count': 5,
                'file': 'enhanced_trading.log',
                'level': 'INFO',
                'max_size_mb': 100
            },
            'risk': {
                'default_tolerance': 'moderate',
                'max_position_size': 0.1,
                'micro_cap': {
                    'conservative': {'max_stocks': 15, 'position_limit': 0.05, 'stop_loss': 0.15},
                    'moderate': {'max_stocks': 12, 'position_limit': 0.08, 'stop_loss': 0.2},
                    'aggressive': {'max_stocks': 8, 'position_limit': 0.12, 'stop_loss': 0.25}
                },
                'blue_chip': {
                    'conservative': {'max_stocks': 8, 'position_limit': 0.15, 'stop_loss': 0.1},
                    'moderate': {'max_stocks': 6, 'position_limit': 0.2, 'stop_loss': 0.12},
                    'aggressive': {'max_stocks': 5, 'position_limit': 0.25, 'stop_loss': 0.15}
                }
            }
        },
        
        'data': {
            'base_dir': 'data',
            'micro_cap_dir': 'data_micro_cap',
            'blue_chip_dir': 'data_blue_chip',
            'small_cap_dir': 'data_small_cap',
            'automation_dir': 'automation_data'
        },
        
        'web_ui': {
            'enabled': True,
            'host': 'localhost',
            'port': 8080,
            'php_server': True,
            'document_root': 'web_ui',
            'auto_launch': False
        }
    }
    
    return new_config

def create_php_web_interface():
    """Create the PHP web interface files."""
    
    web_dir = Path("web_ui")
    web_dir.mkdir(exist_ok=True)
    
    # Create index.php
    index_content = '''<?php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Trading System - Dashboard</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 20px; background: #f5f5f5; 
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card { 
            background: white; padding: 20px; margin: 10px 0; border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .success { border-left: 4px solid #28a745; }
        .info { border-left: 4px solid #007bff; }
        .warning { border-left: 4px solid #ffc107; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .btn { 
            display: inline-block; padding: 10px 20px; background: #007bff; color: white; 
            text-decoration: none; border-radius: 4px; margin: 5px;
        }
        .btn:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Enhanced Trading System</h1>
            <p>Centralized dashboard for multi-market cap trading operations</p>
        </div>
        
        <div class="grid">
            <div class="card success">
                <h3>System Status</h3>
                <p><strong>Server Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
                <p><strong>PHP Version:</strong> <?= phpversion() ?></p>
                <p><strong>Status:</strong> <span style="color: green;">Online</span></p>
            </div>
            
            <div class="card info">
                <h3>Database Architecture</h3>
                <p><strong>Micro-cap DB:</strong> CSV-mirrored data only</p>
                <p><strong>Master DB:</strong> All enhanced features</p>
                <p><strong>Separation:</strong> Clean data organization</p>
            </div>
        </div>
        
        <div class="card">
            <h3>Quick Actions</h3>
            <a href="portfolios.php" class="btn">View Portfolios</a>
            <a href="trades.php" class="btn">Trade History</a>
            <a href="analytics.php" class="btn">Analytics</a>
            <a href="database.php" class="btn">Database Manager</a>
            <a href="automation.php" class="btn">Automation</a>
        </div>
        
        <div class="card">
            <h3>Database Connection Test</h3>
            <?php
            $databases = [
                ['name' => 'Master Database', 'db' => 'stock_market_2'],
                ['name' => 'Micro-cap Database', 'db' => 'stock_market_micro_cap_trading']
            ];
            
            foreach ($databases as $database) {
                try {
                    // Load configuration from YAML file
                    $configFile = '../db_config_refactored.yml';
                    $config = parseDbConfig($configFile);
                    
                    if (!$config) {
                        echo "<p style='color: red;'>✗ Cannot load database configuration</p>";
                        continue;
                    }
                    
                    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$database['db']}";
                    $pdo = new PDO($dsn, $config['username'], $config['password']);
                    echo "<p style='color: green;'>✓ {$database['name']} connection successful</p>";
                } catch(PDOException $e) {
                    echo "<p style='color: red;'>✗ {$database['name']} connection failed</p>";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>'''
    
    with open(web_dir / "index.php", "w", encoding="utf-8") as f:
        f.write(index_content)
    
    # Create database management page
    database_content = '''<?php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Database Management</h1>
        
        <div class="card">
            <h3>Database Architecture Overview</h3>
            <table>
                <tr>
                    <th>Database</th>
                    <th>Purpose</th>
                    <th>Tables</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>stock_market_micro_cap_trading</td>
                    <td>CSV-mirrored data only</td>
                    <td>portfolio_data, trade_log, historical_prices</td>
                    <td style="color: green;">Active</td>
                </tr>
                <tr>
                    <td>stock_market_2</td>
                    <td>Master database - all enhanced features</td>
                    <td>All new tables and analytics</td>
                    <td style="color: green;">Active</td>
                </tr>
            </table>
        </div>
        
        <div class="card">
            <h3>Table Management</h3>
            <p>Centralized location for creating and managing all database tables</p>
            <a href="create_tables.php" class="btn">Create/Update Tables</a>
            <a href="backup.php" class="btn">Backup Databases</a>
            <a href="migrate.php" class="btn">Data Migration</a>
        </div>
        
        <a href="index.php" class="btn">Back to Dashboard</a>
    </div>
</body>
</html>'''
    
    with open(web_dir / "database.php", "w", encoding="utf-8") as f:
        f.write(database_content)
    
    print(f"✓ Created PHP web interface in: {web_dir.absolute()}")
    return web_dir

def main():
    print("=" * 60)
    print("DATABASE REFACTORING & PHP WEB UI SETUP")
    print("=" * 60)
    
    # Create new database configuration
    print("\n1. Creating refactored database configuration...")
    new_config = create_refactored_database_config()
    
    # Backup current config
    backup_file = Path("db_config_backup.yml")
    if Path("db_config.yml").exists():
        import shutil
        shutil.copy("db_config.yml", backup_file)
        print(f"✓ Backed up current config to: {backup_file}")
    
    # Write new configuration
    with open("db_config_refactored.yml", "w") as f:
        yaml.dump(new_config, f, default_flow_style=False)
    print("✓ Created refactored configuration: db_config_refactored.yml")
    
    # Create PHP web interface
    print("\n2. Creating PHP web interface...")
    web_dir = create_php_web_interface()
    
    print("\n" + "=" * 60)
    print("REFACTORING SUMMARY")
    print("=" * 60)
    
    print("""
DATABASE ARCHITECTURE:
✓ stock_market_micro_cap_trading: CSV-mirrored data ONLY
  - portfolio_data (mirrors micro_cap_portfolio.csv)
  - trade_log (mirrors micro_cap_trade_log.csv)  
  - historical_prices (basic price history)

✓ stock_market_2: ALL enhanced features and new functionality
  - Multi-market cap portfolios (blue-chip, small-cap, etc.)
  - Advanced analytics and reporting
  - LLM interactions and automation logs
  - Web UI data and user preferences
  - Backtesting and strategy data

PHP WEB UI:
✓ Created web interface foundation
✓ Database connection testing
✓ Centralized table management
✓ Dashboard for all trading operations

NEXT STEPS:
1. Review db_config_refactored.yml
2. Update enhanced scripts to use new architecture
3. Launch PHP web server: cd web_ui && php -S localhost:8080
4. Visit http://localhost:8080 for web dashboard
""")

if __name__ == "__main__":
    main()
