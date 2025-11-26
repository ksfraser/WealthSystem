#!/usr/bin/env python3
"""
CSV to Database Import Script

This script imports data from the Python trading CSV files into the legacy database tables
so that the PHP migration scripts can then migrate them to per-symbol tables.

Usage:
    python3 import-csv-to-database.py [options]
"""

import sys
import os
import pandas as pd
import mysql.connector
from mysql.connector import Error
from datetime import datetime, timedelta
import yaml
import argparse
from pathlib import Path
import logging

# Configure logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

class CSVToDatabaseImporter:
    def __init__(self, config_file=None):
        """Initialize the importer with database configuration."""
        self.config = self.load_config(config_file)
        self.connection = None
        self.cursor = None
        
    def load_config(self, config_file):
        """Load database configuration from YAML file."""
        if config_file is None:
            # Look for config files in common locations
            possible_files = [
                'db_config.yml',
                'db_config.yaml',
                '../db_config.yml',
                '../db_config.yaml'
            ]
            
            for file_path in possible_files:
                if os.path.exists(file_path):
                    config_file = file_path
                    break
                    
        if not config_file or not os.path.exists(config_file):
            raise FileNotFoundError("Database configuration file not found. Please create db_config.yml")
            
        with open(config_file, 'r') as file:
            config = yaml.safe_load(file)
            
        return config
        
    def connect_to_database(self):
        """Establish database connection."""
        try:
            db_config = self.config['database']
            legacy_config = db_config['legacy']
            
            self.connection = mysql.connector.connect(
                host=db_config['host'],
                port=db_config.get('port', 3306),
                database=legacy_config['database'],
                user=db_config['username'],
                password=db_config['password'],
                charset='utf8mb4',
                use_unicode=True
            )
            
            self.cursor = self.connection.cursor()
            logger.info("Successfully connected to database")
            
        except Error as e:
            logger.error(f"Error connecting to database: {e}")
            raise
            
    def disconnect_from_database(self):
        """Close database connection."""
        if self.cursor:
            self.cursor.close()
        if self.connection:
            self.connection.close()
        logger.info("Database connection closed")
        
    def import_portfolio_data(self, csv_file):
        """Import portfolio data from CSV file."""
        logger.info(f"Importing portfolio data from {csv_file}")
        
        if not os.path.exists(csv_file):
            logger.warning(f"Portfolio CSV file not found: {csv_file}")
            return 0
            
        try:
            df = pd.read_csv(csv_file)
            
            # Expected columns: Date, Symbol, Position_Size, Avg_Cost, Current_Price, Market_Value, Unrealized_PnL
            required_columns = ['Date', 'Symbol']
            missing_columns = [col for col in required_columns if col not in df.columns]
            
            if missing_columns:
                logger.error(f"Missing required columns in portfolio CSV: {missing_columns}")
                return 0
                
            insert_query = """
                INSERT IGNORE INTO portfolio_data 
                (symbol, date, position_size, avg_cost, current_price, market_value, unrealized_pnl)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
            """
            
            rows_inserted = 0
            for _, row in df.iterrows():
                try:
                    # Parse date
                    date_str = str(row['Date'])
                    if '/' in date_str:
                        date_obj = datetime.strptime(date_str, '%m/%d/%Y').date()
                    else:
                        date_obj = datetime.strptime(date_str, '%Y-%m-%d').date()
                    
                    values = (
                        str(row['Symbol']).upper(),
                        date_obj,
                        float(row.get('Position_Size', 0)),
                        float(row.get('Avg_Cost', 0)),
                        float(row.get('Current_Price', 0)),
                        float(row.get('Market_Value', 0)),
                        float(row.get('Unrealized_PnL', 0))
                    )
                    
                    self.cursor.execute(insert_query, values)
                    rows_inserted += 1
                    
                except (ValueError, KeyError) as e:
                    logger.warning(f"Error processing row {row.name}: {e}")
                    continue
                    
            self.connection.commit()
            logger.info(f"Imported {rows_inserted} portfolio records")
            return rows_inserted
            
        except Exception as e:
            logger.error(f"Error importing portfolio data: {e}")
            return 0
            
    def import_trade_log(self, csv_file):
        """Import trade log data from CSV file."""
        logger.info(f"Importing trade log from {csv_file}")
        
        if not os.path.exists(csv_file):
            logger.warning(f"Trade log CSV file not found: {csv_file}")
            return 0
            
        try:
            df = pd.read_csv(csv_file)
            
            # Expected columns: Date, Symbol, Action, Quantity, Price, Amount
            required_columns = ['Date', 'Symbol', 'Action']
            missing_columns = [col for col in required_columns if col not in df.columns]
            
            if missing_columns:
                logger.error(f"Missing required columns in trade log CSV: {missing_columns}")
                return 0
                
            insert_query = """
                INSERT IGNORE INTO trade_log 
                (symbol, date, action, quantity, price, amount, reasoning)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
            """
            
            rows_inserted = 0
            for _, row in df.iterrows():
                try:
                    # Parse date
                    date_str = str(row['Date'])
                    if '/' in date_str:
                        date_obj = datetime.strptime(date_str, '%m/%d/%Y').date()
                    else:
                        date_obj = datetime.strptime(date_str, '%Y-%m-%d').date()
                    
                    # Normalize action
                    action = str(row['Action']).upper()
                    if action not in ['BUY', 'SELL', 'HOLD']:
                        logger.warning(f"Unknown action '{action}', skipping row")
                        continue
                    
                    values = (
                        str(row['Symbol']).upper(),
                        date_obj,
                        action,
                        float(row.get('Quantity', 0)),
                        float(row.get('Price', 0)),
                        float(row.get('Amount', 0)),
                        str(row.get('Reasoning', ''))[:500]  # Limit reasoning text
                    )
                    
                    self.cursor.execute(insert_query, values)
                    rows_inserted += 1
                    
                except (ValueError, KeyError) as e:
                    logger.warning(f"Error processing row {row.name}: {e}")
                    continue
                    
            self.connection.commit()
            logger.info(f"Imported {rows_inserted} trade log records")
            return rows_inserted
            
        except Exception as e:
            logger.error(f"Error importing trade log: {e}")
            return 0
            
    def generate_sample_price_data(self, symbols, days=30):
        """Generate sample historical price data for testing."""
        logger.info(f"Generating sample price data for {len(symbols)} symbols, {days} days")
        
        insert_query = """
            INSERT IGNORE INTO historical_prices 
            (symbol, date, open, high, low, close, adj_close, volume)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
        """
        
        import random
        
        rows_inserted = 0
        start_date = datetime.now() - timedelta(days=days)
        
        for symbol in symbols:
            base_price = random.uniform(10, 200)  # Random base price
            
            for i in range(days):
                current_date = start_date + timedelta(days=i)
                
                # Skip weekends
                if current_date.weekday() >= 5:
                    continue
                    
                # Simulate price movement
                daily_change = random.uniform(-0.1, 0.1)  # ±10% daily change
                base_price *= (1 + daily_change)
                base_price = max(1.0, base_price)  # Don't go below $1
                
                # Generate OHLC data
                open_price = round(base_price * random.uniform(0.98, 1.02), 2)
                high_price = round(open_price * random.uniform(1.0, 1.08), 2)
                low_price = round(open_price * random.uniform(0.92, 1.0), 2)
                close_price = round(low_price + random.random() * (high_price - low_price), 2)
                volume = random.randint(100000, 2000000)
                
                values = (
                    symbol,
                    current_date.date(),
                    open_price,
                    high_price,
                    low_price,
                    close_price,
                    close_price,  # adj_close = close for simplicity
                    volume
                )
                
                self.cursor.execute(insert_query, values)
                rows_inserted += 1
                
        self.connection.commit()
        logger.info(f"Generated {rows_inserted} sample price records")
        return rows_inserted
        
    def import_all_csv_files(self, csv_directory):
        """Import all relevant CSV files from a directory."""
        csv_dir = Path(csv_directory)
        
        if not csv_dir.exists():
            logger.error(f"CSV directory not found: {csv_directory}")
            return
            
        # Look for portfolio and trade log files
        portfolio_files = list(csv_dir.glob("*portfolio*.csv"))
        trade_log_files = list(csv_dir.glob("*trade*log*.csv"))
        
        total_imported = 0
        
        for file_path in portfolio_files:
            total_imported += self.import_portfolio_data(str(file_path))
            
        for file_path in trade_log_files:
            total_imported += self.import_trade_log(str(file_path))
            
        logger.info(f"Total records imported: {total_imported}")
        
    def run_import(self, options):
        """Run the complete import process."""
        try:
            self.connect_to_database()
            
            if options.get('csv_directory'):
                self.import_all_csv_files(options['csv_directory'])
                
            if options.get('portfolio_file'):
                self.import_portfolio_data(options['portfolio_file'])
                
            if options.get('trade_log_file'):
                self.import_trade_log(options['trade_log_file'])
                
            if options.get('generate_sample'):
                symbols = options.get('sample_symbols', ['IBM', 'AAPL', 'GOOGL', 'TSLA', 'MSFT'])
                days = options.get('sample_days', 30)
                self.generate_sample_price_data(symbols, days)
                
        finally:
            self.disconnect_from_database()

def main():
    parser = argparse.ArgumentParser(description='Import CSV data to legacy database tables')
    parser.add_argument('--csv-dir', help='Directory containing CSV files to import')
    parser.add_argument('--portfolio-file', help='Specific portfolio CSV file to import')
    parser.add_argument('--trade-log-file', help='Specific trade log CSV file to import')
    parser.add_argument('--config', help='Database configuration file')
    parser.add_argument('--generate-sample', action='store_true', help='Generate sample data for testing')
    parser.add_argument('--sample-days', type=int, default=30, help='Number of days of sample data to generate')
    
    args = parser.parse_args()
    
    if not any([args.csv_dir, args.portfolio_file, args.trade_log_file, args.generate_sample]):
        print("Error: Please specify at least one import option")
        parser.print_help()
        sys.exit(1)
        
    options = {
        'csv_directory': args.csv_dir,
        'portfolio_file': args.portfolio_file,
        'trade_log_file': args.trade_log_file,
        'generate_sample': args.generate_sample,
        'sample_days': args.sample_days
    }
    
    try:
        importer = CSVToDatabaseImporter(args.config)
        importer.run_import(options)
        print("Import completed successfully!")
        
    except Exception as e:
        logger.error(f"Import failed: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
