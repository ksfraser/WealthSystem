<?php

namespace App\Services;

/**
 * PythonBridgeService - Python bridge script management
 * 
 * Responsible for:
 * - Managing Python bridge script lifecycle
 * - Generating bridge script template
 * - Resolving script paths
 * - Ensuring bridge script exists and is executable
 * 
 * Follows Single Responsibility Principle by focusing ONLY on
 * bridge script management, not execution or response parsing.
 * 
 * @package App\Services
 * @author Stock Analysis System
 * @version 1.0.0
 * 
 * Usage Example:
 * ```php
 * $bridge = new PythonBridgeService('/path/to/scripts');
 * $scriptPath = $bridge->ensureBridgeScript();
 * 
 * // Use with PythonExecutorService
 * $executor = new PythonExecutorService();
 * $result = $executor->executeScript($scriptPath, 'fetch_price_data', ['symbol' => 'AAPL']);
 * ```
 */
class PythonBridgeService
{
    /**
     * Base directory for Python scripts
     * 
     * @var string
     */
    private string $baseDir;
    
    /**
     * Custom script template (optional)
     * 
     * @var string|null
     */
    private ?string $customTemplate;
    
    /**
     * Bridge script filename
     * 
     * @var string
     */
    private const BRIDGE_SCRIPT_NAME = 'python_bridge.py';
    
    /**
     * Constructor
     * 
     * @param string|null $baseDir Base directory for scripts (default: project root)
     * @param string|null $customTemplate Custom Python script template (optional)
     */
    public function __construct(?string $baseDir = null, ?string $customTemplate = null)
    {
        $this->baseDir = $baseDir ?? dirname(__DIR__, 2);
        $this->customTemplate = $customTemplate;
    }
    
    /**
     * Get Python bridge script path
     * 
     * Returns the absolute path where the bridge script should be located.
     * Does not check if the script exists.
     * 
     * @return string Absolute path to bridge script
     */
    public function getBridgeScriptPath(): string
    {
        return $this->baseDir . DIRECTORY_SEPARATOR . self::BRIDGE_SCRIPT_NAME;
    }
    
    /**
     * Ensure bridge script exists
     * 
     * Creates the bridge script if it doesn't exist.
     * Returns the path to the script.
     * 
     * @return string Path to bridge script
     */
    public function ensureBridgeScript(): string
    {
        $scriptPath = $this->getBridgeScriptPath();
        
        if (!file_exists($scriptPath)) {
            $this->createBridgeScript($scriptPath);
        }
        
        return $scriptPath;
    }
    
    /**
     * Get script template
     * 
     * Returns the Python script template used to create the bridge script.
     * Uses custom template if provided, otherwise returns default template.
     * 
     * @return string Python script template
     */
    public function getScriptTemplate(): string
    {
        if ($this->customTemplate !== null) {
            return $this->customTemplate;
        }
        
        return $this->getDefaultTemplate();
    }
    
    /**
     * Get base directory
     * 
     * @return string Base directory path
     */
    public function getBaseDirectory(): string
    {
        return $this->baseDir;
    }
    
    /**
     * Create bridge script at specified path
     * 
     * Writes the Python bridge script to disk and sets executable permissions.
     * 
     * @param string $scriptPath Path where script should be created
     * @return void
     */
    private function createBridgeScript(string $scriptPath): void
    {
        $template = $this->getScriptTemplate();
        
        file_put_contents($scriptPath, $template);
        
        // Set executable permissions (Unix/Linux only)
        if (PHP_OS_FAMILY !== 'Windows') {
            chmod($scriptPath, 0755);
        }
    }
    
    /**
     * Get default Python bridge script template
     * 
     * Returns a complete Python script that:
     * - Accepts function name and JSON parameters as CLI arguments
     * - Routes to appropriate function
     * - Returns results as JSON
     * - Handles errors gracefully
     * 
     * @return string Complete Python script code
     */
    private function getDefaultTemplate(): string
    {
        return <<<'PYTHON'
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
PYTHON;
    }
}
