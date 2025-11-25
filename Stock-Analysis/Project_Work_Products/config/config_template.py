# Configuration settings for Stock Analysis Extension
# Copy this to config.py and update with your actual credentials

import os
from pathlib import Path

# Database Configuration
DATABASE_CONFIG = {
    'host': 'localhost',
    'port': 3306,
    'database': 'stock_analysis',
    'user': 'your_username',
    'password': 'your_password',
    'charset': 'utf8mb4',
    'autocommit': True
}

# FrontAccounting Integration
FRONTACCOUNTING_CONFIG = {
    'api_url': 'http://localhost/frontaccounting/api',
    'username': 'admin',
    'password': 'password',
    'company_id': 1,
    'fiscal_year': 2025
}

# API Keys (get from respective providers)
API_KEYS = {
    'finnhub': 'your_finnhub_api_key',  # Free tier available at finnhub.io
    'alpha_vantage': 'your_alpha_vantage_key',  # Free tier available at alphavantage.co
    'fmp': 'your_fmp_key'  # Financial Modeling Prep (optional)
}

# File Paths
BASE_DIR = Path(__file__).parent.parent
DATA_DIR = BASE_DIR / 'data'
REPORTS_DIR = BASE_DIR / 'reports'
LOGS_DIR = BASE_DIR / 'logs'

# Analysis Settings
ANALYSIS_CONFIG = {
    'lookback_period_days': 252,  # 1 year of trading days
    'min_volume': 100000,  # Minimum daily volume
    'min_market_cap': 1000000000,  # $1B minimum market cap for "normal" stocks
    'max_pe_ratio': 50,
    'min_dividend_yield': 0.0,
    'sectors_to_analyze': [
        'Technology', 'Healthcare', 'Financial Services', 
        'Consumer Cyclical', 'Industrials', 'Communication Services',
        'Consumer Defensive', 'Energy', 'Utilities', 'Real Estate', 'Basic Materials'
    ]
}

# Risk Management
RISK_CONFIG = {
    'max_position_size': 0.05,  # 5% max per position
    'stop_loss_pct': 0.15,  # 15% stop loss
    'take_profit_pct': 0.25,  # 25% take profit
    'max_correlation': 0.7,  # Max correlation between positions
    'var_confidence': 0.05,  # 95% VaR confidence
    'max_sector_exposure': 0.25  # 25% max per sector
}

# Scoring Weights (should sum to 1.0)
SCORING_WEIGHTS = {
    'fundamental': 0.40,
    'technical': 0.30,
    'momentum': 0.20,
    'sentiment': 0.10
}

# Create directories if they don't exist
for directory in [DATA_DIR, REPORTS_DIR, LOGS_DIR]:
    directory.mkdir(exist_ok=True)
