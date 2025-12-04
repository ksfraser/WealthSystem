#!/usr/bin/env python3
"""
Python Bridge Script for ChatGPT Micro Cap Portfolio
Provides interface between PHP application and existing Python trading systems
"""

import sys
import json
import os
from pathlib import Path

# Add current directory to Python path
current_dir = Path(__file__).parent
sys.path.append(str(current_dir))

try:
    # Try to import existing trading script
    import trading_script
except ImportError:
    trading_script = None

def fetch_price_data(params):
    """Fetch price data for a symbol"""
    symbol = params.get("symbol")
    period = params.get("period", "1y")
    
    if not trading_script:
        return {"error": "Trading script not available"}
    
    try:
        # Use the existing download_price_data function
        result = trading_script.download_price_data(symbol, period=period)
        if hasattr(result, "data") and not result.data.empty:
            # Convert DataFrame to JSON-serializable format
            data = result.data.to_dict("records")
            return {"data": data, "source": result.source}
        else:
            return {"error": "No data available"}
    except Exception as e:
        return {"error": str(e)}

def get_portfolio_data(params):
    """Get portfolio data from CSV file"""
    portfolio_file = params.get("file", "Scripts and CSV Files/chatgpt_portfolio_update.csv")
    
    try:
        import pandas as pd
        
        if os.path.exists(portfolio_file):
            df = pd.read_csv(portfolio_file)
            return {"data": df.to_dict("records")}
        else:
            return {"error": f"Portfolio file not found: {portfolio_file}"}
    except Exception as e:
        return {"error": str(e)}

def update_symbols(params):
    """Update multiple symbols"""
    symbols = params.get("symbols", [])
    
    results = {}
    for symbol in symbols:
        result = fetch_price_data({"symbol": symbol})
        results[symbol] = result
    
    return {"results": results}

def main():
    if len(sys.argv) != 3:
        print(json.dumps({"error": "Usage: python_bridge.py <function> <params>"}))
        return
    
    function_name = sys.argv[1]
    params_json = sys.argv[2]
    
    try:
        params = json.loads(params_json)
    except json.JSONDecodeError:
        print(json.dumps({"error": "Invalid JSON parameters"}))
        return
    
    # Route to appropriate function
    functions = {
        "fetch_price_data": fetch_price_data,
        "get_portfolio_data": get_portfolio_data,
        "update_symbols": update_symbols
    }
    
    if function_name not in functions:
        print(json.dumps({"error": f"Unknown function: {function_name}"}))
        return
    
    result = functions[function_name](params)
    print(json.dumps(result))

if __name__ == "__main__":
    main()