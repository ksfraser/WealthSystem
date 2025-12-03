#!/usr/bin/env python3
"""
Centralized Database & Table Generator
Single source of truth for all database schemas and table creation
"""

import mysql.connector
from mysql.connector import Error
import yaml
import sys
from pathlib import Path
from datetime import datetime

class DatabaseArchitect:
    """Centralized database and table management system."""
    
    def __init__(self, config_file="db_config_refactored.yml"):
        """Initialize with database configuration."""
        self.config = self.load_config(config_file)
        self.db_config = self.config['database']
        
    def load_config(self, config_file):
        """Load database configuration."""
        try:
            with open(config_file, 'r') as file:
                return yaml.safe_load(file)
        except FileNotFoundError:
            print(f"Config file {config_file} not found, using current config")
            with open('db_config.yml', 'r') as file:
                return yaml.safe_load(file)
    
    def get_connection(self, database_name):
        """Get database connection."""
        return mysql.connector.connect(
            host=self.db_config['host'],
            port=self.db_config['port'],
            user=self.db_config['username'],
            password=self.db_config['password'],
            database=database_name
        )
    
    def get_micro_cap_tables(self):
        """Get table definitions for micro-cap database (CSV-mirrored only)."""
        return {
            'portfolio_data': '''
                CREATE TABLE IF NOT EXISTS portfolio_data (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    shares DECIMAL(15,4) NOT NULL DEFAULT 0,
                    stop_loss DECIMAL(15,4) NOT NULL DEFAULT 0,
                    buy_price DECIMAL(15,4) NOT NULL DEFAULT 0,
                    cost_basis DECIMAL(15,2) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_symbol_date (symbol, date),
                    INDEX idx_symbol (symbol),
                    INDEX idx_date (date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
                COMMENT='Mirrors micro_cap_portfolio.csv - original trading_script.py data only'
            ''',
            
            'trade_log': '''
                CREATE TABLE IF NOT EXISTS trade_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    date DATE NOT NULL,
                    ticker VARCHAR(10) NOT NULL,
                    shares_bought DECIMAL(15,4) DEFAULT NULL,
                    buy_price DECIMAL(15,4) DEFAULT NULL,
                    cost_basis DECIMAL(15,2) DEFAULT NULL,
                    shares_sold DECIMAL(15,4) DEFAULT NULL,
                    sell_price DECIMAL(15,4) DEFAULT NULL,
                    proceeds DECIMAL(15,2) DEFAULT NULL,
                    reason TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_ticker (ticker),
                    INDEX idx_date (date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
                COMMENT='Mirrors micro_cap_trade_log.csv - original trading records only'
            ''',
            
            'historical_prices': '''
                CREATE TABLE IF NOT EXISTS historical_prices (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    open DECIMAL(15,4) NOT NULL DEFAULT 0,
                    high DECIMAL(15,4) NOT NULL DEFAULT 0,
                    low DECIMAL(15,4) NOT NULL DEFAULT 0,
                    close DECIMAL(15,4) NOT NULL DEFAULT 0,
                    adj_close DECIMAL(15,4) NOT NULL DEFAULT 0,
                    volume BIGINT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_symbol_date (symbol, date),
                    INDEX idx_symbol (symbol),
                    INDEX idx_date (date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
                COMMENT='Basic price history for micro-cap stocks'
            '''
        }
    
    def get_master_tables(self):
        """Get table definitions for master database (all enhanced features)."""
        return {
            # Multi-market cap portfolios
            'portfolios_blue_chip': '''
                CREATE TABLE IF NOT EXISTS portfolios_blue_chip (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    shares DECIMAL(15,4) NOT NULL DEFAULT 0,
                    avg_cost DECIMAL(15,4) NOT NULL DEFAULT 0,
                    current_price DECIMAL(15,4) NOT NULL DEFAULT 0,
                    market_value DECIMAL(15,2) NOT NULL DEFAULT 0,
                    unrealized_pnl DECIMAL(15,2) NOT NULL DEFAULT 0,
                    stop_loss DECIMAL(15,4) NOT NULL DEFAULT 0,
                    risk_score DECIMAL(5,2) DEFAULT NULL,
                    portfolio_weight DECIMAL(5,4) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_symbol_date (symbol, date),
                    INDEX idx_symbol (symbol),
                    INDEX idx_date (date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                COMMENT='Blue-chip portfolio data with enhanced features'
            ''',
            
            'portfolios_small_cap': '''
                CREATE TABLE IF NOT EXISTS portfolios_small_cap (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    shares DECIMAL(15,4) NOT NULL DEFAULT 0,
                    avg_cost DECIMAL(15,4) NOT NULL DEFAULT 0,
                    current_price DECIMAL(15,4) NOT NULL DEFAULT 0,
                    market_value DECIMAL(15,2) NOT NULL DEFAULT 0,
                    unrealized_pnl DECIMAL(15,2) NOT NULL DEFAULT 0,
                    stop_loss DECIMAL(15,4) NOT NULL DEFAULT 0,
                    risk_score DECIMAL(5,2) DEFAULT NULL,
                    portfolio_weight DECIMAL(5,4) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_symbol_date (symbol, date),
                    INDEX idx_symbol (symbol),
                    INDEX idx_date (date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                COMMENT='Small-cap portfolio data with enhanced features'
            ''',
            
            # Enhanced trade tracking
            'trades_enhanced': '''
                CREATE TABLE IF NOT EXISTS trades_enhanced (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    portfolio_type ENUM('micro_cap', 'blue_chip', 'small_cap', 'mid_cap', 'large_cap') NOT NULL,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    action ENUM('BUY', 'SELL') NOT NULL,
                    quantity DECIMAL(15,4) NOT NULL,
                    price DECIMAL(15,4) NOT NULL,
                    amount DECIMAL(15,2) NOT NULL,
                    fees DECIMAL(10,2) DEFAULT 0,
                    reasoning TEXT,
                    llm_session_id VARCHAR(50) DEFAULT NULL,
                    risk_score DECIMAL(5,2) DEFAULT NULL,
                    strategy VARCHAR(50) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_portfolio_type (portfolio_type),
                    INDEX idx_symbol (symbol),
                    INDEX idx_date (date),
                    INDEX idx_action (action)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                COMMENT='Enhanced trade tracking for all market cap categories'
            ''',
            
            # Analytics and reporting
            'portfolio_performance': '''
                CREATE TABLE IF NOT EXISTS portfolio_performance (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    portfolio_type ENUM('micro_cap', 'blue_chip', 'small_cap', 'mid_cap', 'large_cap') NOT NULL,
                    date DATE NOT NULL,
                    total_value DECIMAL(15,2) NOT NULL,
                    cash_balance DECIMAL(15,2) NOT NULL,
                    total_equity DECIMAL(15,2) NOT NULL,
                    daily_return DECIMAL(8,4) DEFAULT NULL,
                    total_return DECIMAL(8,4) DEFAULT NULL,
                    volatility DECIMAL(8,4) DEFAULT NULL,
                    sharpe_ratio DECIMAL(8,4) DEFAULT NULL,
                    max_drawdown DECIMAL(8,4) DEFAULT NULL,
                    positions_count INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_portfolio_date (portfolio_type, date),
                    INDEX idx_portfolio_type (portfolio_type),
                    INDEX idx_date (date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                COMMENT='Portfolio performance metrics and analytics'
            ''',
            
            # LLM and automation
            'llm_interactions': '''
                CREATE TABLE IF NOT EXISTS llm_interactions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    session_id VARCHAR(50) NOT NULL,
                    interaction_id VARCHAR(50) NOT NULL,
                    prompt_type VARCHAR(50) NOT NULL,
                    ticker VARCHAR(10) DEFAULT NULL,
                    prompt_text TEXT NOT NULL,
                    response_text TEXT NOT NULL,
                    response_time_ms INT NOT NULL,
                    tokens_used INT DEFAULT NULL,
                    cost_usd DECIMAL(8,4) DEFAULT NULL,
                    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_session_id (session_id),
                    INDEX idx_prompt_type (prompt_type),
                    INDEX idx_ticker (ticker),
                    INDEX idx_timestamp (timestamp)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                COMMENT='LLM interaction tracking for automation and analysis'
            ''',
            
            'trading_sessions': '''
                CREATE TABLE IF NOT EXISTS trading_sessions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    session_id VARCHAR(50) NOT NULL UNIQUE,
                    portfolio_type ENUM('micro_cap', 'blue_chip', 'small_cap', 'mid_cap', 'large_cap') NOT NULL,
                    start_time TIMESTAMP NOT NULL,
                    end_time TIMESTAMP NULL DEFAULT NULL,
                    trades_executed INT DEFAULT 0,
                    performance DECIMAL(8,4) DEFAULT NULL,
                    risk_tolerance ENUM('conservative', 'moderate', 'aggressive') DEFAULT 'moderate',
                    automation_enabled BOOLEAN DEFAULT FALSE,
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_session_id (session_id),
                    INDEX idx_portfolio_type (portfolio_type),
                    INDEX idx_start_time (start_time)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                COMMENT='Trading session tracking and management'
            ''',
            
            # Web UI and preferences
            'user_preferences': '''
                CREATE TABLE IF NOT EXISTS user_preferences (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id VARCHAR(50) NOT NULL DEFAULT 'default_user',
                    preference_key VARCHAR(100) NOT NULL,
                    preference_value TEXT NOT NULL,
                    category VARCHAR(50) DEFAULT 'general',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_user_key (user_id, preference_key),
                    INDEX idx_user_id (user_id),
                    INDEX idx_category (category)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                COMMENT='User preferences for web UI and application settings'
            '''
        }
    
    def create_all_tables(self):
        """Create all tables in their respective databases."""
        results = {
            'micro_cap': {'database': self.db_config['micro_cap']['database'], 'tables': {}},
            'master': {'database': self.db_config['master']['database'], 'tables': {}}
        }
        
        # Create micro-cap tables (CSV-mirrored only)
        print(f"Creating tables in {results['micro_cap']['database']} (CSV-mirrored data only)...")
        print("-" * 60)
        
        try:
            conn = self.get_connection(results['micro_cap']['database'])
            cursor = conn.cursor()
            
            for table_name, table_sql in self.get_micro_cap_tables().items():
                try:
                    cursor.execute(table_sql)
                    results['micro_cap']['tables'][table_name] = 'SUCCESS'
                    print(f"✓ {table_name}")
                except Error as e:
                    results['micro_cap']['tables'][table_name] = f'ERROR: {e}'
                    print(f"✗ {table_name}: {e}")
            
            conn.commit()
            cursor.close()
            conn.close()
            
        except Error as e:
            print(f"✗ Connection to micro-cap database failed: {e}")
        
        # Create master tables (all enhanced features)
        print(f"\nCreating tables in {results['master']['database']} (all enhanced features)...")
        print("-" * 60)
        
        try:
            conn = self.get_connection(results['master']['database'])
            cursor = conn.cursor()
            
            for table_name, table_sql in self.get_master_tables().items():
                try:
                    cursor.execute(table_sql)
                    results['master']['tables'][table_name] = 'SUCCESS'
                    print(f"✓ {table_name}")
                except Error as e:
                    results['master']['tables'][table_name] = f'ERROR: {e}'
                    print(f"✗ {table_name}: {e}")
            
            conn.commit()
            cursor.close()
            conn.close()
            
        except Error as e:
            print(f"✗ Connection to master database failed: {e}")
        
        return results
    
    def launch_php_server(self, port=8080):
        """Launch PHP development server."""
        import subprocess
        import os
        
        web_dir = Path("web_ui")
        if not web_dir.exists():
            print(f"Web directory {web_dir} does not exist")
            return False
        
        try:
            print(f"Starting PHP server on localhost:{port}")
            process = subprocess.Popen(
                ['php', '-S', f'localhost:{port}'],
                cwd=web_dir,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE
            )
            print(f"✓ PHP server started (PID: {process.pid})")
            print(f"Visit: http://localhost:{port}")
            return process
        except Exception as e:
            print(f"✗ Failed to start PHP server: {e}")
            return None

def main():
    print("=" * 70)
    print("CENTRALIZED DATABASE & TABLE GENERATOR")
    print("=" * 70)
    print("Single source of truth for all database schemas and table creation")
    
    # Initialize database architect
    architect = DatabaseArchitect()
    
    # Create all tables
    print(f"\nGenerating tables with new architecture:")
    print(f"• Micro-cap DB: CSV-mirrored data only")
    print(f"• Master DB: All enhanced features and web UI data")
    
    results = architect.create_all_tables()
    
    # Summary
    print("\n" + "=" * 70)
    print("CREATION SUMMARY")
    print("=" * 70)
    
    total_success = 0
    total_tables = 0
    
    for db_type, db_info in results.items():
        print(f"\n{db_info['database']} ({db_type}):")
        for table, status in db_info['tables'].items():
            total_tables += 1
            if status == 'SUCCESS':
                total_success += 1
                print(f"  ✓ {table}")
            else:
                print(f"  ✗ {table}: {status}")
    
    print(f"\nOverall: {total_success}/{total_tables} tables created successfully")
    
    if total_success == total_tables:
        print("\n🎉 Database architecture setup complete!")
        print("\nNext steps:")
        print("1. Update enhanced scripts to use new architecture")
        print("2. Launch web UI: cd web_ui && php -S localhost:8080")
        print("3. Access dashboard at http://localhost:8080")
    else:
        print("\n⚠ Some tables failed to create. Check error messages above.")

if __name__ == "__main__":
    main()
