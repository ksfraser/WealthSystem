<?php

namespace App\Services;

/**
 * Python Integration Service
 * 
 * Provides integration with existing Python scripts for data fetching and analysis.
 * Bridges PHP application with Python trading_script.py and other data sources.
 */
class PythonIntegrationService
{
    private string $pythonPath;
    private string $scriptPath;
    
    public function __construct(string $pythonPath = 'python', string $scriptPath = null)
    {
        $this->pythonPath = $pythonPath;
        $this->scriptPath = $scriptPath ?? dirname(__DIR__, 2);
    }
    
    /**
     * Fetch price data for a symbol using trading_script.py
     */
    public function fetchPriceData(string $symbol, ?string $period = '1y'): array
    {
        $command = $this->buildPythonCommand('fetch_price_data', [
            'symbol' => $symbol,
            'period' => $period
        ]);
        
        return $this->executePythonCommand($command);
    }
    
    /**
     * Get portfolio data using existing Python systems
     */
    public function getPortfolioData(string $portfolioFile = null): array
    {
        $portfolioFile = $portfolioFile ?? 'Scripts and CSV Files/chatgpt_portfolio_update.csv';
        
        $command = $this->buildPythonCommand('get_portfolio_data', [
            'file' => $portfolioFile
        ]);
        
        return $this->executePythonCommand($command);
    }
    
    /**
     * Update prices for multiple symbols
     */
    public function updateMultipleSymbols(array $symbols): array
    {
        $command = $this->buildPythonCommand('update_symbols', [
            'symbols' => $symbols
        ]);
        
        return $this->executePythonCommand($command);
    }
    
    /**
     * Build Python command with parameters
     */
    private function buildPythonCommand(string $function, array $params = []): string
    {
        $pythonScript = $this->scriptPath . '/python_bridge.py';
        
        // Create a simple Python bridge script if it doesn't exist
        if (!file_exists($pythonScript)) {
            $this->createPythonBridge($pythonScript);
        }
        
        $paramString = json_encode($params);
        return sprintf('%s "%s" "%s" \'%s\'', $this->pythonPath, $pythonScript, $function, $paramString);
    }
    
    /**
     * Execute Python command and return result
     */
    private function executePythonCommand(string $command): array
    {
        try {
            $output = [];
            $returnCode = 0;
            
            exec($command . ' 2>&1', $output, $returnCode);
            
            if ($returnCode !== 0) {
                return [
                    'success' => false,
                    'error' => implode("\n", $output)
                ];
            }
            
            $jsonOutput = implode("\n", $output);
            $result = json_decode($jsonOutput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Invalid JSON response: ' . $jsonOutput
                ];
            }
            
            return array_merge(['success' => true], $result);
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create Python bridge script
     */
    private function createPythonBridge(string $scriptPath): void
    {
        $pythonCode = '#!/usr/bin/env python3
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
';
        
        file_put_contents($scriptPath, $pythonCode);
        chmod($scriptPath, 0755);
    }
    
    /**
     * Check if Python environment is available
     */
    public function checkPythonEnvironment(): array
    {
        $command = $this->pythonPath . ' --version 2>&1';
        exec($command, $output, $returnCode);
        
        return [
            'available' => $returnCode === 0,
            'version' => implode("\n", $output),
            'python_path' => $this->pythonPath
        ];
    }
    
    /**
     * Analyze stock using Python AI analysis module
     * 
     * @param array $stockData Stock data including symbol, price_data, fundamentals
     * @return array Analysis results
     */
    public function analyzeStock(array $stockData): array
    {
        $pythonScript = dirname(__DIR__, 2) . '/python_analysis/analysis.py';
        
        if (!file_exists($pythonScript)) {
            return [
                'success' => false,
                'error' => 'Python analysis module not found: ' . $pythonScript
            ];
        }
        
        try {
            $jsonInput = json_encode($stockData);
            
            $command = sprintf(
                '%s "%s" analyze %s 2>&1',
                $this->pythonPath,
                $pythonScript,
                escapeshellarg($jsonInput)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                return [
                    'success' => false,
                    'error' => 'Python execution failed: ' . implode("\n", $output)
                ];
            }
            
            $outputJson = implode("\n", $output);
            $result = json_decode($outputJson, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Invalid JSON response from Python'
                ];
            }
            
            if (isset($result['error'])) {
                return [
                    'success' => false,
                    'error' => $result['error']
                ];
            }
            
            return [
                'success' => true,
                'data' => $result
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Python integration error: ' . $e->getMessage()
            ];
        }
    }
}