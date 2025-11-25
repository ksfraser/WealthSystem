<?php
/**
 * Test PHP-Python Integration
 * 
 * This script tests that PHP can successfully call the Python analysis module
 * and receive proper JSON responses.
 */

require_once __DIR__ . '/app/Services/PythonIntegrationService.php';

use App\Services\PythonIntegrationService;

echo "===========================================\n";
echo "PHP-Python Integration Test\n";
echo "===========================================\n\n";

// Test 1: Check Python environment
echo "Test 1: Checking Python environment...\n";
$pythonService = new PythonIntegrationService('python');
$envCheck = $pythonService->checkPythonEnvironment();

if ($envCheck['available']) {
    echo "✓ Python is available\n";
    echo "  Version: " . trim($envCheck['version']) . "\n\n";
} else {
    echo "✗ Python is NOT available\n";
    echo "  Please ensure Python 3.8+ is installed and in PATH\n\n";
    exit(1);
}

// Test 2: Check if analysis module exists
echo "Test 2: Checking Python analysis module...\n";
$analysisScript = __DIR__ . '/python_analysis/analysis.py';

if (file_exists($analysisScript)) {
    echo "✓ Analysis module found: {$analysisScript}\n\n";
} else {
    echo "✗ Analysis module NOT found: {$analysisScript}\n\n";
    exit(1);
}

// Test 3: Call Python with sample data
echo "Test 3: Testing Python analysis with sample data...\n";

$sampleData = [
    'symbol' => 'TEST',
    'price_data' => [
        ['date' => '2024-01-01', 'open' => 100, 'high' => 105, 'low' => 99, 'close' => 104, 'volume' => 1000000],
        ['date' => '2024-01-02', 'open' => 104, 'high' => 108, 'low' => 103, 'close' => 107, 'volume' => 1200000],
        ['date' => '2024-01-03', 'open' => 107, 'high' => 110, 'low' => 106, 'close' => 109, 'volume' => 1100000],
        ['date' => '2024-01-04', 'open' => 109, 'high' => 112, 'low' => 108, 'close' => 111, 'volume' => 1300000],
        ['date' => '2024-01-05', 'open' => 111, 'high' => 115, 'low' => 110, 'close' => 114, 'volume' => 1400000],
    ],
    'fundamentals' => [
        'pe_ratio' => 18.5,
        'price_to_book' => 2.5,
        'return_on_equity' => 0.18,
        'debt_to_equity' => 0.45,
        'profit_margin' => 0.15,
        'market_cap' => 5000000000
    ],
    'scoring_weights' => [
        'fundamental' => 0.40,
        'technical' => 0.30,
        'momentum' => 0.20,
        'sentiment' => 0.10
    ]
];

$result = $pythonService->analyzeStock($sampleData);

if ($result['success']) {
    echo "✓ Python analysis successful!\n\n";
    
    $analysis = $result['data'];
    
    echo "Analysis Results:\n";
    echo "  Symbol: " . $analysis['symbol'] . "\n";
    echo "  Overall Score: " . $analysis['overall_score'] . "/100\n";
    echo "  Recommendation: " . $analysis['recommendation'] . "\n";
    echo "  Risk Level: " . $analysis['risk_level'] . "\n";
    echo "  Confidence: " . $analysis['confidence'] . "%\n";
    echo "\n";
    echo "  Dimension Scores:\n";
    echo "    Fundamental: " . $analysis['fundamental_score'] . "\n";
    echo "    Technical: " . $analysis['technical_score'] . "\n";
    echo "    Momentum: " . $analysis['momentum_score'] . "\n";
    echo "    Sentiment: " . $analysis['sentiment_score'] . "\n";
    echo "\n";
    
    if (isset($analysis['target_price'])) {
        echo "  Target Price: $" . number_format($analysis['target_price'], 2) . "\n";
        echo "  Current Price: $" . number_format($analysis['current_price'], 2) . "\n";
        
        if ($analysis['current_price'] > 0) {
            $potentialReturn = (($analysis['target_price'] - $analysis['current_price']) / $analysis['current_price']) * 100;
            echo "  Potential Return: " . number_format($potentialReturn, 2) . "%\n";
        }
    }
    
    echo "\n";
    echo "===========================================\n";
    echo "✓ All tests passed!\n";
    echo "PHP-Python integration is working correctly.\n";
    echo "===========================================\n";
    
} else {
    echo "✗ Python analysis FAILED\n";
    echo "  Error: " . $result['error'] . "\n\n";
    echo "Troubleshooting:\n";
    echo "1. Check Python dependencies: pip install pandas numpy ta\n";
    echo "2. Test Python directly:\n";
    echo "   python python_analysis/analysis.py analyze '{\"symbol\":\"TEST\",\"price_data\":[],\"fundamentals\":{}}'\n";
    echo "3. Check Python error output above\n\n";
    exit(1);
}
