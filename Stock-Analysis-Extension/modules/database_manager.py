"""
Database Manager for Stock Analysis Extension
Handles MySQL database operations including creation, connection, and data management

REQUIREMENTS TRACEABILITY:
==========================
Business Requirements:
- BR-031: Historical analysis review

Business Capabilities:
- BC-301: MySQL Database Persistence
- BC-303: Trade Log Management
- BC-304: Position Management

Functional Requirements:
- FR-1200-1207: Database Operations
  * Store stock prices, fundamentals, technical indicators
  * Store analysis results, trade log, portfolio positions
  * Create database schema on initialization
  * Handle connection errors gracefully
- FR-1300-1305: Data Retrieval
  * Retrieve historical analysis results
  * Retrieve price history for charts
  * Retrieve trade history by date range
  * Retrieve current portfolio positions
  * Support filtering by date, symbol, score

Technical Requirements:
- TR-500-512: DatabaseManager module specification
- TR-801: Performance (< 1 sec database query)
- TR-904: SQL injection prevention (parameterized queries)

Database Schema:
- stock_prices: Historical price data with OHLCV
- stock_fundamentals: Company fundamental metrics
- technical_indicators: Calculated technical indicators
- analysis_results: Comprehensive analysis scores
- portfolios: Portfolio definitions with cash tracking
- portfolio_positions: Current holdings with entry/stop/target
- trade_log: Complete trade history with commissions
- front_accounting_sync: FA integration tracking

Dependencies:
- DEP-101: MySQL Server 8.0+ (critical)

Performance:
- Indexed queries for symbol, date lookups
- Connection pooling for efficiency
- Batch insert operations
- Automatic schema creation
"""

import mysql.connector
from mysql.connector import Error
import pandas as pd
from sqlalchemy import create_engine, text
import logging
from datetime import datetime, timedelta
from typing import Dict, List, Optional, Any
import json

class DatabaseManager:
    def __init__(self, config: Dict[str, Any]):
        """
        Initialize database manager with configuration
        
        Args:
            config: Database configuration dictionary
        """
        self.config = config
        self.engine = None
        self.connection = None
        self.logger = logging.getLogger(__name__)
        
    def create_connection(self) -> bool:
        """
        Create database connection
        
        Returns:
            bool: True if connection successful, False otherwise
        """
        try:
            # Create SQLAlchemy engine
            connection_string = (
                f"mysql+mysqlconnector://{self.config['user']}:"
                f"{self.config['password']}@{self.config['host']}:"
                f"{self.config['port']}/{self.config['database']}"
            )
            
            self.engine = create_engine(
                connection_string,
                echo=False,
                pool_pre_ping=True,
                pool_recycle=3600
            )
            
            # Test connection
            with self.engine.connect() as conn:
                conn.execute(text("SELECT 1"))
                
            self.logger.info("Database connection established successfully")
            return True
            
        except Exception as e:
            self.logger.error(f"Failed to create database connection: {e}")
            return False
    
    def create_database_schema(self) -> bool:
        """
        Create all necessary tables for stock analysis
        
        Returns:
            bool: True if successful, False otherwise
        """
        try:
            with open('sql/create_tables.sql', 'r') as file:
                schema_sql = file.read()
            
            # Execute schema creation
            with self.engine.connect() as conn:
                # Split and execute each statement
                statements = schema_sql.split(';')
                for statement in statements:
                    if statement.strip():
                        conn.execute(text(statement))
                        
            self.logger.info("Database schema created successfully")
            return True
            
        except Exception as e:
            self.logger.error(f"Failed to create database schema: {e}")
            return False
    
    def insert_stock_data(self, df: pd.DataFrame, table_name: str) -> bool:
        """
        Insert stock data into specified table
        
        Args:
            df: DataFrame containing stock data
            table_name: Target table name
            
        Returns:
            bool: True if successful, False otherwise
        """
        try:
            df.to_sql(
                table_name,
                con=self.engine,
                if_exists='append',
                index=False,
                method='multi',
                chunksize=1000
            )
            
            self.logger.info(f"Inserted {len(df)} rows into {table_name}")
            return True
            
        except Exception as e:
            self.logger.error(f"Failed to insert data into {table_name}: {e}")
            return False
    
    def get_stock_data(self, symbol: str, start_date: str = None, end_date: str = None) -> pd.DataFrame:
        """
        Retrieve stock data for a specific symbol
        
        Args:
            symbol: Stock symbol
            start_date: Start date (YYYY-MM-DD)
            end_date: End date (YYYY-MM-DD)
            
        Returns:
            DataFrame containing stock data
        """
        try:
            query = """
            SELECT * FROM stock_prices 
            WHERE symbol = %s
            """
            params = [symbol]
            
            if start_date:
                query += " AND date >= %s"
                params.append(start_date)
                
            if end_date:
                query += " AND date <= %s"
                params.append(end_date)
                
            query += " ORDER BY date ASC"
            
            df = pd.read_sql(query, con=self.engine, params=params)
            return df
            
        except Exception as e:
            self.logger.error(f"Failed to retrieve stock data for {symbol}: {e}")
            return pd.DataFrame()
    
    def get_portfolio_positions(self, portfolio_id: int = 1) -> pd.DataFrame:
        """
        Get current portfolio positions
        
        Args:
            portfolio_id: Portfolio identifier
            
        Returns:
            DataFrame containing portfolio positions
        """
        try:
            query = """
            SELECT p.*, s.company_name, s.sector, s.market_cap
            FROM portfolio_positions p
            LEFT JOIN stock_fundamentals s ON p.symbol = s.symbol
            WHERE p.portfolio_id = %s AND p.quantity > 0
            ORDER BY p.position_value DESC
            """
            
            df = pd.read_sql(query, con=self.engine, params=[portfolio_id])
            return df
            
        except Exception as e:
            self.logger.error(f"Failed to retrieve portfolio positions: {e}")
            return pd.DataFrame()
    
    def update_analysis_results(self, symbol: str, analysis_data: Dict) -> bool:
        """
        Update or insert analysis results for a symbol
        
        Args:
            symbol: Stock symbol
            analysis_data: Dictionary containing analysis results
            
        Returns:
            bool: True if successful, False otherwise
        """
        try:
            query = """
            INSERT INTO analysis_results 
            (symbol, analysis_date, fundamental_score, technical_score, 
             momentum_score, sentiment_score, overall_score, recommendation, 
             target_price, risk_rating, analysis_data)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
            fundamental_score = VALUES(fundamental_score),
            technical_score = VALUES(technical_score),
            momentum_score = VALUES(momentum_score),
            sentiment_score = VALUES(sentiment_score),
            overall_score = VALUES(overall_score),
            recommendation = VALUES(recommendation),
            target_price = VALUES(target_price),
            risk_rating = VALUES(risk_rating),
            analysis_data = VALUES(analysis_data),
            updated_at = CURRENT_TIMESTAMP
            """
            
            params = [
                symbol,
                datetime.now().date(),
                analysis_data.get('fundamental_score'),
                analysis_data.get('technical_score'), 
                analysis_data.get('momentum_score'),
                analysis_data.get('sentiment_score'),
                analysis_data.get('overall_score'),
                analysis_data.get('recommendation'),
                analysis_data.get('target_price'),
                analysis_data.get('risk_rating'),
                json.dumps(analysis_data.get('details', {}))
            ]
            
            with self.engine.connect() as conn:
                conn.execute(text(query), params)
                
            self.logger.info(f"Updated analysis results for {symbol}")
            return True
            
        except Exception as e:
            self.logger.error(f"Failed to update analysis results for {symbol}: {e}")
            return False
    
    def get_top_recommendations(self, limit: int = 20, min_score: float = 70.0) -> pd.DataFrame:
        """
        Get top stock recommendations based on analysis scores
        
        Args:
            limit: Maximum number of recommendations
            min_score: Minimum overall score threshold
            
        Returns:
            DataFrame containing top recommendations
        """
        try:
            query = """
            SELECT ar.*, sf.company_name, sf.sector, sf.market_cap, sf.pe_ratio
            FROM analysis_results ar
            LEFT JOIN stock_fundamentals sf ON ar.symbol = sf.symbol
            WHERE ar.overall_score >= %s 
            AND ar.analysis_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ORDER BY ar.overall_score DESC, ar.risk_rating ASC
            LIMIT %s
            """
            
            df = pd.read_sql(query, con=self.engine, params=[min_score, limit])
            return df
            
        except Exception as e:
            self.logger.error(f"Failed to retrieve top recommendations: {e}")
            return pd.DataFrame()
    
    def log_trade_transaction(self, trade_data: Dict) -> bool:
        """
        Log a trade transaction to the database
        
        Args:
            trade_data: Dictionary containing trade information
            
        Returns:
            bool: True if successful, False otherwise
        """
        try:
            query = """
            INSERT INTO trade_log 
            (portfolio_id, symbol, trade_type, quantity, price, total_amount, 
             trade_date, strategy, notes)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
            """
            
            params = [
                trade_data.get('portfolio_id', 1),
                trade_data['symbol'],
                trade_data['trade_type'],
                trade_data['quantity'],
                trade_data['price'],
                trade_data['total_amount'],
                trade_data.get('trade_date', datetime.now()),
                trade_data.get('strategy', ''),
                trade_data.get('notes', '')
            ]
            
            with self.engine.connect() as conn:
                conn.execute(text(query), params)
                
            self.logger.info(f"Logged {trade_data['trade_type']} trade for {trade_data['symbol']}")
            return True
            
        except Exception as e:
            self.logger.error(f"Failed to log trade transaction: {e}")
            return False
    
    def close_connection(self):
        """Close database connection"""
        if self.engine:
            self.engine.dispose()
            self.logger.info("Database connection closed")
