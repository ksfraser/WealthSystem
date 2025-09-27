#!/usr/bin/env python3
"""
Simple Historical Data Fetcher
Fetches historical stock data from Yahoo Finance and outputs JSON
"""

import yfinance as yf
import pandas as pd
import json
import sys
import argparse
from datetime import datetime, timedelta

def fetch_stock_data(symbol, start_date, end_date):
    """Fetch historical data for a symbol and return as JSON"""
    try:
        # Create yfinance Ticker object
        ticker = yf.Ticker(symbol)
        
        # Fetch historical data
        hist = ticker.history(start=start_date, end=end_date)
        
        if hist.empty:
            print(f"No data found for symbol {symbol}", file=sys.stderr)
            return None
        
        # Convert to list of dictionaries
        data = []
        for date, row in hist.iterrows():
            data.append({
                'Date': date.strftime('%Y-%m-%d'),
                'Open': float(row['Open']) if not pd.isna(row['Open']) else 0.0,
                'High': float(row['High']) if not pd.isna(row['High']) else 0.0,
                'Low': float(row['Low']) if not pd.isna(row['Low']) else 0.0,
                'Close': float(row['Close']) if not pd.isna(row['Close']) else 0.0,
                'Adj Close': float(row['Close']) if not pd.isna(row['Close']) else 0.0,  # yfinance doesn't separate these
                'Volume': int(row['Volume']) if not pd.isna(row['Volume']) else 0
            })
        
        return data
        
    except Exception as e:
        print(f"Error fetching data for {symbol}: {str(e)}", file=sys.stderr)
        return None

def main():
    parser = argparse.ArgumentParser(description='Fetch historical stock data from Yahoo Finance')
    parser.add_argument('--symbol', required=True, help='Stock symbol to fetch')
    parser.add_argument('--start-date', required=True, help='Start date (YYYY-MM-DD)')
    parser.add_argument('--end-date', required=True, help='End date (YYYY-MM-DD)')
    parser.add_argument('--output-format', default='json', choices=['json', 'csv'], help='Output format')
    
    args = parser.parse_args()
    
    # Fetch the data
    data = fetch_stock_data(args.symbol, args.start_date, args.end_date)
    
    if data is None:
        sys.exit(1)
    
    # Output the data
    if args.output_format == 'json':
        print(json.dumps(data, indent=2))
    else:
        # CSV output (for compatibility)
        import pandas as pd
        df = pd.DataFrame(data)
        print(df.to_csv(index=False))

if __name__ == '__main__':
    main()