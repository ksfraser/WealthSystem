"""Enhanced Database-Enabled Trading Script

This module extends the original trading_script.py with database functionality while maintaining
full CSV compatibility. It supports multiple market cap categories (micro-cap, blue-chip, etc.)
and provides a hybrid CSV + Database approach.

Key Features:
- Backward compatible with original CSV format
- Database storage for scalability and querying
- Support for multiple market cap categories
- Dual write (CSV + Database) for data persistence
- Enhanced portfolio management with metadata

Usage:
    from enhanced_trading_script import EnhancedTradingEngine
    
    engine = EnhancedTradingEngine(market_cap_category='micro')
    engine.process_portfolio(portfolio_data, cash)
"""

from __future__ import annotations

import sys
import os
from pathlib import Path
from datetime import datetime, timedelta
from typing import Dict, List, Any, Optional, Tuple
import pandas as pd
import numpy as np
import json
import logging

# Import the original trading script functions
sys.path.append(str(Path(__file__).parent))
from trading_script import (
    download_price_data, last_trading_date, check_weekend, load_benchmarks,
    trading_day_window, _ensure_df, FetchResult, ASOF_DATE, set_asof
)

# Database imports
try:
    import mysql.connector
    from mysql.connector import Error
    import yaml
    HAS_DB_DEPS = True
except ImportError:
    HAS_DB_DEPS = False
    print("Warning: Database dependencies not installed. CSV-only mode available.")

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


class DatabaseManager:
    """Handles database connections and operations for trading data."""
    
    def __init__(self, config_file: Optional[str] = None):
        self.config = self._load_config(config_file) if HAS_DB_DEPS else None
        self.connection = None
        self.legacy_connection = None
        
    def _load_config(self, config_file: Optional[str] = None) -> Dict[str, Any]:
        """Load database configuration from YAML file."""
        if config_file is None:
            config_file = self._find_config_file()
            
        if not config_file or not os.path.exists(config_file):
            logger.warning("Database configuration file not found. Using CSV-only mode.")
            return {}
            
        with open(config_file, 'r') as file:
            return yaml.safe_load(file)
    
    def _find_config_file(self) -> Optional[str]:
        """Find database configuration file in common locations."""
        possible_files = [
            'db_config.yml',
            'db_config.yaml',
            '../db_config.yml',
            '../db_config.yaml',
            'config/db_config.yml'
        ]
        
        for file_path in possible_files:
            if os.path.exists(file_path):
                return file_path
        return None
    
    def connect(self) -> bool:
        """Establish database connections."""
        if not HAS_DB_DEPS or not self.config:
            return False
            
        try:
            db_config = self.config.get('database', {})
            
            # Connect to main micro-cap database
            micro_cap_config = db_config.get('micro_cap', {})
            self.connection = mysql.connector.connect(
                host=db_config.get('host', 'localhost'),
                port=db_config.get('port', 3306),
                database=micro_cap_config.get('database', 'stock_market_micro_cap_trading'),
                user=db_config.get('username'),
                password=db_config.get('password'),
                charset='utf8mb4'
            )
            
            # Connect to legacy database for historical data
            legacy_config = db_config.get('legacy', {})
            self.legacy_connection = mysql.connector.connect(
                host=db_config.get('host', 'localhost'),
                port=db_config.get('port', 3306),
                database=legacy_config.get('database', 'stock_market_2'),
                user=db_config.get('username'),
                password=db_config.get('password'),
                charset='utf8mb4'
            )
            
            logger.info("Database connections established successfully")
            return True
            
        except Error as e:
            logger.error(f"Database connection failed: {e}")
            return False
    
    def disconnect(self):
        """Close database connections."""
        if self.connection and self.connection.is_connected():
            self.connection.close()
        if self.legacy_connection and self.legacy_connection.is_connected():
            self.legacy_connection.close()
        logger.info("Database connections closed")
    
    def is_connected(self) -> bool:
        """Check if database is connected."""
        return (self.connection and self.connection.is_connected() and
                self.legacy_connection and self.legacy_connection.is_connected())


class EnhancedTradingEngine:
    """Enhanced trading engine with database support and multi-market-cap capability."""
    
    def __init__(self, 
                 market_cap_category: str = 'micro',
                 data_dir: Optional[Path] = None,
                 enable_database: bool = True,
                 config_file: Optional[str] = None):
        """
        Initialize the enhanced trading engine.
        
        Args:
            market_cap_category: Market cap category ('micro', 'blue-chip', 'small', 'mid', 'large')
            data_dir: Directory for CSV files (defaults to market cap specific folder)
            enable_database: Whether to enable database functionality
            config_file: Path to database configuration file
        """
        self.market_cap_category = market_cap_category.lower()
        self.enable_database = enable_database and HAS_DB_DEPS
        
        # Set up data directory
        if data_dir is None:
            data_dir = Path(f"data_{self.market_cap_category}_cap")
        self.data_dir = Path(data_dir)
        self.data_dir.mkdir(exist_ok=True)
        
        # Set up CSV file paths
        self.portfolio_csv = self.data_dir / f"{self.market_cap_category}_cap_portfolio.csv"
        self.trade_log_csv = self.data_dir / f"{self.market_cap_category}_cap_trade_log.csv"

        # Retry file for failed writes (per portfolio)
        self.retry_file = self.data_dir / f".portfolio_write_retry_{self.market_cap_category}.json"
        
        # Initialize database manager
        self.db = DatabaseManager(config_file) if self.enable_database else None
        if self.db:
            self.db_connected = self.db.connect()
        else:
            self.db_connected = False
            
        logger.info(f"Enhanced Trading Engine initialized for {market_cap_category} cap category")
        logger.info(f"Database enabled: {self.db_connected}")
        logger.info(f"Data directory: {self.data_dir}")

        # Attempt retry if retry file exists
        self._retry_failed_write()
    
    def __del__(self):
        """Cleanup database connections."""
        if self.db:
            self.db.disconnect()
    
    def save_portfolio_data(self, portfolio_df: pd.DataFrame, cash: float, total_equity: float) -> bool:
        """Save portfolio data to both CSV and database. On failure, save to retry file."""
        success = True
        today_iso = last_trading_date().date().isoformat()
        # Always save to CSV for backward compatibility
        csv_ok = self._save_portfolio_csv(portfolio_df, cash, total_equity, today_iso)
        db_ok = True
        if self.db_connected:
            db_ok = self._save_portfolio_database(portfolio_df, cash, total_equity, today_iso)
        success = csv_ok and db_ok
        if not success:
            # Save failed data to retry file
            try:
                retry_data = {
                    'portfolio': portfolio_df.to_dict(orient='records'),
                    'cash': cash,
                    'total_equity': total_equity,
                    'date': today_iso
                }
                with open(self.retry_file, 'w') as f:
                    json.dump(retry_data, f)
                logger.error(f"Portfolio write failed. Data saved for retry in {self.retry_file}")
            except Exception as e:
                logger.error(f"Failed to save retry file: {e}")
        else:
            # Clean up retry file if successful
            if self.retry_file.exists():
                try:
                    self.retry_file.unlink()
                except Exception as e:
                    logger.warning(f"Could not remove retry file: {e}")
        return success

    def _retry_failed_write(self):
        """If a retry file exists, attempt to re-save the failed data."""
        if self.retry_file.exists():
            try:
                with open(self.retry_file, 'r') as f:
                    retry_data = json.load(f)
                logger.info(f"Retrying failed portfolio write from {self.retry_file}")
                df = pd.DataFrame(retry_data['portfolio'])
                cash = retry_data['cash']
                total_equity = retry_data['total_equity']
                # Try again
                ok = self.save_portfolio_data(df, cash, total_equity)
                if ok:
                    logger.info("Retry successful. Removing retry file.")
                    self.retry_file.unlink()
                else:
                    logger.error("Retry failed. Data remains in retry file.")
            except Exception as e:
                logger.error(f"Error during retry: {e}")
    
    def _save_portfolio_csv(self, portfolio_df: pd.DataFrame, cash: float, total_equity: float, date: str) -> bool:
        """Save portfolio data to CSV file (maintains original format)."""
        try:
            # Prepare portfolio rows for CSV
            rows = []
            total_value = 0.0
            total_pnl = 0.0
            
            for _, row in portfolio_df.iterrows():
                ticker = row['ticker']
                shares = float(row['shares'])
                buy_price = float(row['buy_price'])
                cost_basis = float(row['cost_basis'])
                stop_loss = float(row.get('stop_loss', 0))
                
                # Get current price
                try:
                    end_d = last_trading_date()
                    start_d = end_d - pd.Timedelta(days=2)
                    fetch = download_price_data(ticker, start=start_d, end=(end_d + pd.Timedelta(days=1)), progress=False)
                    current_price = float(fetch.df["Close"].iloc[-1]) if not fetch.df.empty else buy_price
                except:
                    current_price = buy_price
                
                market_value = shares * current_price
                pnl = market_value - cost_basis
                total_value += market_value
                total_pnl += pnl
                
                rows.append({
                    "Date": date,
                    "Ticker": ticker,
                    "Shares": shares,
                    "Buy Price": buy_price,
                    "Current Price": current_price,
                    "Stop Loss": stop_loss,
                    "Cost Basis": cost_basis,
                    "Total Value": market_value,
                    "PnL": pnl,
                    "Cash Balance": cash,
                    "Total Equity": total_equity,
                    "Action": "HOLD"
                })
            
            # Add TOTAL row
            rows.append({
                "Date": date,
                "Ticker": "TOTAL",
                "Shares": 0,
                "Buy Price": 0,
                "Current Price": 0,
                "Stop Loss": 0,
                "Cost Basis": 0,
                "Total Value": total_value,
                "PnL": total_pnl,
                "Cash Balance": cash,
                "Total Equity": total_equity,
                "Action": "SUMMARY"
            })
            
            # Save to CSV
            new_df = pd.DataFrame(rows)
            if self.portfolio_csv.exists():
                existing_df = pd.read_csv(self.portfolio_csv)
                combined_df = pd.concat([existing_df, new_df], ignore_index=True)
            else:
                combined_df = new_df
            
            combined_df.to_csv(self.portfolio_csv, index=False)
            logger.info(f"Portfolio data saved to CSV: {self.portfolio_csv}")
            return True
            
        except Exception as e:
            logger.error(f"Failed to save portfolio CSV: {e}")
            return False
    
    def _save_portfolio_database(self, portfolio_df: pd.DataFrame, cash: float, total_equity: float, date: str) -> bool:
        """Save portfolio data to database."""
        try:
            cursor = self.legacy_connection.cursor()
            
            # Save to portfolio_data table
            for _, row in portfolio_df.iterrows():
                ticker = row['ticker']
                shares = float(row['shares'])
                buy_price = float(row['buy_price'])
                cost_basis = float(row['cost_basis'])
                
                # Get current price
                try:
                    end_d = last_trading_date()
                    start_d = end_d - pd.Timedelta(days=2)
                    fetch = download_price_data(ticker, start=start_d, end=(end_d + pd.Timedelta(days=1)), progress=False)
                    current_price = float(fetch.df["Close"].iloc[-1]) if not fetch.df.empty else buy_price
                except:
                    current_price = buy_price
                
                market_value = shares * current_price
                unrealized_pnl = market_value - cost_basis
                
                query = """
                    INSERT INTO portfolio_data 
                    (symbol, date, position_size, avg_cost, current_price, market_value, unrealized_pnl)
                    VALUES (%s, %s, %s, %s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE
                    position_size = VALUES(position_size),
                    avg_cost = VALUES(avg_cost),
                    current_price = VALUES(current_price),
                    market_value = VALUES(market_value),
                    unrealized_pnl = VALUES(unrealized_pnl)
                """
                
                cursor.execute(query, (
                    ticker, date, shares, buy_price, current_price, market_value, unrealized_pnl
                ))
            
            self.legacy_connection.commit()
            cursor.close()
            logger.info("Portfolio data saved to database")
            return True
            
        except Error as e:
            logger.error(f"Failed to save portfolio to database: {e}")
            return False
    
    def log_trade(self, ticker: str, action: str, shares: float, price: float, reason: str) -> bool:
        """Log a trade to both CSV and database."""
        success = True
        today_iso = last_trading_date().date().isoformat()
        
        # Log to CSV
        success &= self._log_trade_csv(ticker, action, shares, price, reason, today_iso)
        
        # Log to database
        if self.db_connected:
            success &= self._log_trade_database(ticker, action, shares, price, reason, today_iso)
        
        return success
    
    def _log_trade_csv(self, ticker: str, action: str, shares: float, price: float, reason: str, date: str) -> bool:
        """Log trade to CSV file."""
        try:
            amount = shares * price
            trade_log = {
                "Date": date,
                "Ticker": ticker,
                "Shares Bought" if action.upper() == "BUY" else "Shares Sold": shares,
                "Buy Price" if action.upper() == "BUY" else "Sell Price": price,
                "Cost Basis" if action.upper() == "BUY" else "Proceeds": amount,
                "PnL": 0.0 if action.upper() == "BUY" else amount,  # Simplified PnL calculation
                "Reason": reason
            }
            
            if self.trade_log_csv.exists():
                df_log = pd.read_csv(self.trade_log_csv)
                df_log = pd.concat([df_log, pd.DataFrame([trade_log])], ignore_index=True)
            else:
                df_log = pd.DataFrame([trade_log])
            
            df_log.to_csv(self.trade_log_csv, index=False)
            logger.info(f"Trade logged to CSV: {action} {shares} {ticker} @ ${price}")
            return True
            
        except Exception as e:
            logger.error(f"Failed to log trade to CSV: {e}")
            return False
    
    def _log_trade_database(self, ticker: str, action: str, shares: float, price: float, reason: str, date: str) -> bool:
        """Log trade to database."""
        try:
            cursor = self.legacy_connection.cursor()
            amount = shares * price
            
            query = """
                INSERT INTO trade_log 
                (symbol, date, action, quantity, price, amount, reasoning)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
            """
            
            cursor.execute(query, (ticker, date, action.upper(), shares, price, amount, reason))
            self.legacy_connection.commit()
            cursor.close()
            
            logger.info(f"Trade logged to database: {action} {shares} {ticker} @ ${price}")
            return True
            
        except Error as e:
            logger.error(f"Failed to log trade to database: {e}")
            return False
    
    def load_portfolio_state(self) -> Tuple[pd.DataFrame, float]:
        """Load portfolio state, preferring database over CSV if available."""
        if self.db_connected:
            try:
                return self._load_portfolio_database()
            except Exception as e:
                logger.warning(f"Failed to load from database, falling back to CSV: {e}")
        
        return self._load_portfolio_csv()
    
    def _load_portfolio_csv(self) -> Tuple[pd.DataFrame, float]:
        """Load portfolio state from CSV file."""
        if not self.portfolio_csv.exists():
            portfolio = pd.DataFrame(columns=["ticker", "shares", "stop_loss", "buy_price", "cost_basis"])
            cash = 10000.0  # Default starting cash
            logger.info("No existing portfolio found, starting with default cash amount")
            return portfolio, cash
        
        df = pd.read_csv(self.portfolio_csv)
        if df.empty:
            portfolio = pd.DataFrame(columns=["ticker", "shares", "stop_loss", "buy_price", "cost_basis"])
            cash = 10000.0
            return portfolio, cash
        
        # Get non-TOTAL rows
        non_total = df[df["Ticker"] != "TOTAL"].copy()
        non_total["Date"] = pd.to_datetime(non_total["Date"], format="mixed", errors="coerce")
        
        latest_date = non_total["Date"].max()
        latest_tickers = non_total[non_total["Date"] == latest_date].copy()
        
        # Filter out sold positions
        sold_mask = latest_tickers["Action"].astype(str).str.startswith("SELL")
        latest_tickers = latest_tickers[~sold_mask].copy()
        
        # Prepare portfolio DataFrame
        portfolio_cols = ["Date", "Cash Balance", "Total Equity", "Action", "Current Price", "PnL", "Total Value"]
        latest_tickers.drop(columns=portfolio_cols, inplace=True, errors="ignore")
        latest_tickers.rename(columns={
            "Cost Basis": "cost_basis",
            "Buy Price": "buy_price", 
            "Shares": "shares",
            "Ticker": "ticker",
            "Stop Loss": "stop_loss"
        }, inplace=True)
        
        # Get cash balance from TOTAL row
        df_total = df[df["Ticker"] == "TOTAL"].copy()
        df_total["Date"] = pd.to_datetime(df_total["Date"], format="mixed", errors="coerce")
        latest_total = df_total.sort_values("Date").iloc[-1]
        cash = float(latest_total["Cash Balance"])
        
        portfolio = latest_tickers.reset_index(drop=True)
        logger.info(f"Portfolio loaded from CSV: {len(portfolio)} positions, ${cash:,.2f} cash")
        return portfolio, cash
    
    def _load_portfolio_database(self) -> Tuple[pd.DataFrame, float]:
        """Load portfolio state from database."""
        cursor = self.db.legacy_connection.cursor(dictionary=True)
        
        # Get latest portfolio data
        query = """
            SELECT symbol, position_size, avg_cost, current_price, market_value, unrealized_pnl
            FROM portfolio_data 
            WHERE date = (SELECT MAX(date) FROM portfolio_data)
            AND position_size > 0
        """
        
        cursor.execute(query)
        rows = cursor.fetchall()
        
        if not rows:
            cursor.close()
            portfolio = pd.DataFrame(columns=["ticker", "shares", "stop_loss", "buy_price", "cost_basis"])
            cash = 10000.0
            return portfolio, cash
        
        # Convert to portfolio format
        portfolio_data = []
        total_market_value = 0
        
        for row in rows:
            portfolio_data.append({
                "ticker": row["symbol"],
                "shares": float(row["position_size"]),
                "buy_price": float(row["avg_cost"]),
                "cost_basis": float(row["position_size"]) * float(row["avg_cost"]),
                "stop_loss": 0.0  # TODO: Add stop_loss to database schema
            })
            total_market_value += float(row["market_value"])
        
        # Estimate cash (this is simplified - in practice, you'd track cash separately)
        cash = max(1000.0, 10000.0 - sum(p["cost_basis"] for p in portfolio_data))
        
        portfolio = pd.DataFrame(portfolio_data)
        cursor.close()
        
        logger.info(f"Portfolio loaded from database: {len(portfolio)} positions, ${cash:,.2f} cash")
        return portfolio, cash
    
    def get_market_data(self, tickers: List[str], days: int = 30) -> Dict[str, pd.DataFrame]:
        """Get market data for multiple tickers with database caching."""
        market_data = {}
        end_date = last_trading_date()
        start_date = end_date - pd.Timedelta(days=days)
        
        for ticker in tickers:
            try:
                # Try to get from database first
                if self.db_connected:
                    db_data = self._get_price_data_from_database(ticker, start_date, end_date)
                    if db_data is not None and not db_data.empty:
                        market_data[ticker] = db_data
                        continue
                
                # Fall back to live data fetch
                fetch = download_price_data(ticker, start=start_date, end=(end_date + pd.Timedelta(days=1)), progress=False)
                if not fetch.df.empty:
                    market_data[ticker] = fetch.df
                    
                    # Save to database for future use
                    if self.db_connected:
                        self._save_price_data_to_database(ticker, fetch.df)
                        
            except Exception as e:
                logger.warning(f"Failed to get market data for {ticker}: {e}")
                
        return market_data
    
    def _get_price_data_from_database(self, ticker: str, start_date: pd.Timestamp, end_date: pd.Timestamp) -> Optional[pd.DataFrame]:
        """Get price data from database."""
        try:
            cursor = self.db.legacy_connection.cursor(dictionary=True)
            
            query = """
                SELECT date, open, high, low, close, adj_close, volume
                FROM historical_prices 
                WHERE symbol = %s AND date BETWEEN %s AND %s
                ORDER BY date
            """
            
            cursor.execute(query, (ticker, start_date.date(), end_date.date()))
            rows = cursor.fetchall()
            cursor.close()
            
            if not rows:
                return None
                
            df = pd.DataFrame(rows)
            df['Date'] = pd.to_datetime(df['date'])
            df.set_index('Date', inplace=True)
            df.rename(columns={
                'open': 'Open',
                'high': 'High', 
                'low': 'Low',
                'close': 'Close',
                'adj_close': 'Adj Close',
                'volume': 'Volume'
            }, inplace=True)
            
            return df
            
        except Error as e:
            logger.error(f"Failed to get price data from database: {e}")
            return None
    
    def _save_price_data_to_database(self, ticker: str, price_df: pd.DataFrame) -> bool:
        """Save price data to database."""
        try:
            cursor = self.db.legacy_connection.cursor()
            
            for date, row in price_df.iterrows():
                query = """
                    INSERT IGNORE INTO historical_prices 
                    (symbol, date, open, high, low, close, adj_close, volume)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                """
                
                cursor.execute(query, (
                    ticker,
                    date.date(),
                    float(row.get('Open', 0)),
                    float(row.get('High', 0)),
                    float(row.get('Low', 0)),
                    float(row.get('Close', 0)),
                    float(row.get('Adj Close', row.get('Close', 0))),
                    int(row.get('Volume', 0))
                ))
            
            self.db.legacy_connection.commit()
            cursor.close()
            return True
            
        except Error as e:
            logger.error(f"Failed to save price data to database: {e}")
            return False
    
    def process_portfolio(self, portfolio: pd.DataFrame | dict | list, cash: float, interactive: bool = True) -> Tuple[pd.DataFrame, float]:
        """Enhanced portfolio processing with database integration."""
        logger.info(f"Processing portfolio for {self.market_cap_category} cap category")
        
        # Use original logic but with enhanced logging and database saving
        portfolio_df = _ensure_df(portfolio)
        
        # Save current state
        total_equity = cash + (portfolio_df['cost_basis'].sum() if not portfolio_df.empty else 0)
        self.save_portfolio_data(portfolio_df, cash, total_equity)
        
        # Continue with original interactive processing if requested
        if interactive:
            # Import original function to leverage existing logic
            from trading_script import process_portfolio as original_process_portfolio
            
            # Temporarily set global CSV paths to our enhanced paths
            import trading_script
            original_portfolio_csv = trading_script.PORTFOLIO_CSV
            original_trade_log_csv = trading_script.TRADE_LOG_CSV
            
            trading_script.PORTFOLIO_CSV = self.portfolio_csv
            trading_script.TRADE_LOG_CSV = self.trade_log_csv
            
            try:
                result = original_process_portfolio(portfolio_df, cash, interactive)
                
                # Save any trades that were made
                if self.trade_log_csv.exists():
                    self._sync_trades_to_database()
                
                return result
                
            finally:
                # Restore original paths
                trading_script.PORTFOLIO_CSV = original_portfolio_csv
                trading_script.TRADE_LOG_CSV = original_trade_log_csv
        
        return portfolio_df, cash
    
    def _sync_trades_to_database(self):
        """Sync recent trades from CSV to database."""
        if not self.db_connected or not self.trade_log_csv.exists():
            return
            
        try:
            trade_df = pd.read_csv(self.trade_log_csv)
            
            # Get the latest trades (last 10 for safety)
            latest_trades = trade_df.tail(10)
            
            cursor = self.db.legacy_connection.cursor()
            
            for _, trade in latest_trades.iterrows():
                # Determine action and extract data
                action = "BUY" if "Shares Bought" in trade and pd.notna(trade.get("Shares Bought")) else "SELL"
                shares = trade.get("Shares Bought" if action == "BUY" else "Shares Sold", 0)
                price = trade.get("Buy Price" if action == "BUY" else "Sell Price", 0)
                amount = trade.get("Cost Basis" if action == "BUY" else "Proceeds", 0)
                
                query = """
                    INSERT IGNORE INTO trade_log 
                    (symbol, date, action, quantity, price, amount, reasoning)
                    VALUES (%s, %s, %s, %s, %s, %s, %s)
                """
                
                cursor.execute(query, (
                    trade["Ticker"],
                    trade["Date"],
                    action,
                    float(shares) if pd.notna(shares) else 0,
                    float(price) if pd.notna(price) else 0,
                    float(amount) if pd.notna(amount) else 0,
                    str(trade.get("Reason", "Manual trade"))
                ))
            
            self.db.legacy_connection.commit()
            cursor.close()
            logger.info("Recent trades synced to database")
            
        except Exception as e:
            logger.error(f"Failed to sync trades to database: {e}")


# Factory function for easy usage
def create_trading_engine(market_cap_category: str = 'micro', 
                         data_dir: Optional[str] = None,
                         enable_database: bool = True) -> EnhancedTradingEngine:
    """
    Factory function to create a trading engine for specific market cap category.
    
    Args:
        market_cap_category: 'micro', 'blue-chip', 'small', 'mid', 'large'
        data_dir: Custom data directory (optional)
        enable_database: Whether to enable database functionality
    
    Returns:
        EnhancedTradingEngine instance
    """
    data_path = Path(data_dir) if data_dir else None
    return EnhancedTradingEngine(
        market_cap_category=market_cap_category,
        data_dir=data_path,
        enable_database=enable_database
    )


# Convenience functions for different market categories
def create_micro_cap_engine(data_dir: Optional[str] = None) -> EnhancedTradingEngine:
    """Create trading engine for micro-cap stocks."""
    return create_trading_engine('micro', data_dir)

def create_blue_chip_engine(data_dir: Optional[str] = None) -> EnhancedTradingEngine:
    """Create trading engine for blue-chip stocks.""" 
    return create_trading_engine('blue-chip', data_dir)

def create_small_cap_engine(data_dir: Optional[str] = None) -> EnhancedTradingEngine:
    """Create trading engine for small-cap stocks."""
    return create_trading_engine('small', data_dir)


if __name__ == "__main__":
    # Example usage
    print("Enhanced Trading Script - Database + CSV Integration")
    print("Available market cap categories: micro, blue-chip, small, mid, large")
    
    # Example for micro-cap
    micro_engine = create_micro_cap_engine()
    portfolio, cash = micro_engine.load_portfolio_state()
    print(f"Micro-cap portfolio: {len(portfolio)} positions, ${cash:,.2f} cash")
    
    # Example for blue-chip  
    blue_chip_engine = create_blue_chip_engine()
    portfolio, cash = blue_chip_engine.load_portfolio_state()
    print(f"Blue-chip portfolio: {len(portfolio)} positions, ${cash:,.2f} cash")
