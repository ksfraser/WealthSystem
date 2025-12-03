#!/usr/bin/env python3
"""
Database Table Setup Script
Creates the required tables for enhanced trading functionality
"""

import sys
from pathlib import Path
import mysql.connector
from mysql.connector import Error
import yaml

# Add current directory to path
sys.path.append(str(Path(__file__).parent))

def load_database_config():
    """Load database configuration from YAML file."""
    try:
        with open('db_config.yml', 'r') as file:
            config = yaml.safe_load(file)
        return config['database']
    except Exception as e:
        print(f"Error loading database config: {e}")
        return None

def create_tables_for_database(db_config, db_name, description):
    """Create required tables in a specific database."""
    print(f"\n{description}")
    print("-" * 50)
    
    try:
        # Connect to the specific database
        connection = mysql.connector.connect(
            host=db_config['host'],
            port=db_config.get('port', 3306),
            user=db_config['username'],
            password=db_config['password'],
            database=db_name
        )
        
        cursor = connection.cursor()
        
        # Portfolio data table
        portfolio_table = """
        CREATE TABLE IF NOT EXISTS portfolio_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            symbol VARCHAR(10) NOT NULL,
            date DATE NOT NULL,
            position_size DECIMAL(15,4) NOT NULL DEFAULT 0,
            avg_cost DECIMAL(15,4) NOT NULL DEFAULT 0,
            current_price DECIMAL(15,4) NOT NULL DEFAULT 0,
            market_value DECIMAL(15,2) NOT NULL DEFAULT 0,
            unrealized_pnl DECIMAL(15,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_symbol_date (symbol, date),
            INDEX idx_date (date),
            INDEX idx_symbol (symbol)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """
        
        # Trade log table
        trade_log_table = """
        CREATE TABLE IF NOT EXISTS trade_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            symbol VARCHAR(10) NOT NULL,
            date DATE NOT NULL,
            action ENUM('BUY', 'SELL') NOT NULL,
            quantity DECIMAL(15,4) NOT NULL,
            price DECIMAL(15,4) NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            reasoning TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_symbol (symbol),
            INDEX idx_date (date),
            INDEX idx_action (action)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """
        
        # Historical prices table
        historical_prices_table = """
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
        """
        
        # Create tables
        tables = [
            ("portfolio_data", portfolio_table),
            ("trade_log", trade_log_table),
            ("historical_prices", historical_prices_table)
        ]
        
        for table_name, create_sql in tables:
            try:
                cursor.execute(create_sql)
                print(f"✓ Table '{table_name}' created/verified")
            except Error as e:
                print(f"✗ Error creating table '{table_name}': {e}")
        
        connection.commit()
        cursor.close()
        connection.close()
        print(f"✓ Database '{db_name}' setup complete")
        
    except Error as e:
        print(f"✗ Database connection failed: {e}")
        return False
    
    return True

def main():
    print("=" * 60)
    print("DATABASE TABLE SETUP")
    print("=" * 60)
    
    # Load configuration
    db_config = load_database_config()
    if not db_config:
        print("Failed to load database configuration")
        return
    
    print(f"Host: {db_config['host']}")
    print(f"Username: {db_config['username']}")
    
    # Set up tables for each database
    databases = [
        (db_config['legacy']['database'], db_config['legacy']['description']),
        (db_config['micro_cap']['database'], db_config['micro_cap']['description']),
        (db_config['blue_chip']['database'], db_config['blue_chip']['description'])
    ]
    
    success_count = 0
    for db_name, description in databases:
        if create_tables_for_database(db_config, db_name, description):
            success_count += 1
    
    print("\n" + "=" * 60)
    print("SETUP SUMMARY")
    print("=" * 60)
    
    if success_count == len(databases):
        print("🎉 All databases setup successfully!")
        print("\nNext steps:")
        print("1. Run: python enhanced_trading_script.py")
        print("2. Tables will be automatically populated during trading")
        print("3. Database features are now fully enabled")
    else:
        print(f"⚠ {success_count}/{len(databases)} databases setup successfully")
        print("Check error messages above for details")

if __name__ == "__main__":
    main()
