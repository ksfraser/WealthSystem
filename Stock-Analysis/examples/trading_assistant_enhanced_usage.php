<?php

/**
 * LLM Trading Assistant - Enhanced Prompt Usage Examples
 * 
 * Demonstrates how to use the enhanced CIBC-style prompts for better
 * trading recommendations with fundamental analysis, scenarios, and sources.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\AI\AIClient;
use App\AI\Providers\OpenAIProvider;
use App\AI\LLMTradingAssistant;

// ============================================================================
// Example 1: Enhanced Prompt (Default) - Full Fundamental Analysis
// ============================================================================

echo "=== Example 1: Enhanced Prompt with Fundamental Analysis ===\n\n";

$apiKey = getenv('OPENAI_API_KEY') ?: 'your-api-key-here';
$provider = new OpenAIProvider($apiKey, 'gpt-4');
$aiClient = new AIClient($provider);
$assistant = new LLMTradingAssistant($aiClient);

// Sample portfolio holdings
$holdings = [
    [
        'ticker' => 'ABCD',
        'shares' => 200,
        'average_price' => 15.00,
        'current_price' => 18.50,
        'unrealized_pnl' => 700.00,
        'unrealized_pnl_pct' => 23.33
    ],
    [
        'ticker' => 'WXYZ',
        'shares' => 300,
        'average_price' => 8.00,
        'current_price' => 7.25,
        'unrealized_pnl' => -225.00,
        'unrealized_pnl_pct' => -9.38
    ]
];

$cashBalance = 10000.00;
$totalEquity = 15475.00;

// Enhanced prompt is the DEFAULT
$config = [
    'max_position_size_pct' => 10,
    'min_confidence' => 0.70,
    'market_cap_limit' => 300000000, // $300M for micro-cap
    'min_cash_reserve' => 1000,
    'focus' => 'micro-cap',
    // 'prompt_style' => 'enhanced',  // This is the default, no need to specify
];

try {
    $recommendation = $assistant->getRecommendations($holdings, $cashBalance, $totalEquity, $config);
    
    if ($recommendation->isValid()) {
        echo "✅ Recommendation received (confidence: {$recommendation->confidence})\n\n";
        
        // Market analysis (enhanced format)
        if ($recommendation->marketAnalysis !== null) {
            echo "📊 Market Analysis:\n";
            echo "  Summary: {$recommendation->marketAnalysis['summary']}\n";
            echo "  Key Themes: " . implode(', ', $recommendation->marketAnalysis['key_themes']) . "\n";
            echo "  Risks: " . implode(', ', $recommendation->marketAnalysis['risks']) . "\n\n";
        } else {
            echo "📊 Analysis: {$recommendation->analysis}\n\n";
        }
        
        // Trade recommendations
        echo "💼 Trade Recommendations:\n";
        foreach ($recommendation->trades as $trade) {
            echo "\n";
            echo "  Ticker: {$trade->ticker}\n";
            echo "  Action: " . strtoupper($trade->action) . "\n";
            echo "  Shares: {$trade->shares} @ \${$trade->price}\n";
            echo "  Stop Loss: \${$trade->stopLoss} (" . round($trade->getStopLossPercent(), 2) . "%)\n";
            
            if ($trade->priceTarget > 0) {
                echo "  Price Target: \${$trade->priceTarget} (+" . round($trade->getUpsidePotential(), 2) . "%)\n";
                echo "  Risk/Reward: " . round($trade->getRiskRewardRatio(), 2) . ":1\n";
            }
            
            echo "  Total Cost: \$" . number_format($trade->getTotalValue(), 2) . "\n";
            
            // Enhanced fields
            if ($trade->fundamentals !== null) {
                echo "\n  📈 Fundamentals:\n";
                foreach ($trade->fundamentals as $key => $value) {
                    echo "    - " . ucwords(str_replace('_', ' ', $key)) . ": {$value}\n";
                }
            }
            
            if ($trade->bullCase !== null) {
                echo "\n  🐂 Bull Case:\n";
                echo "    Thesis: {$trade->bullCase['thesis']}\n";
                echo "    Catalysts:\n";
                foreach ($trade->bullCase['catalysts'] as $catalyst) {
                    echo "      • {$catalyst}\n";
                }
                echo "    Assumptions:\n";
                foreach ($trade->bullCase['assumptions'] as $assumption) {
                    echo "      • {$assumption}\n";
                }
            }
            
            if ($trade->bearCase !== null) {
                echo "\n  🐻 Bear Case:\n";
                echo "    Thesis: {$trade->bearCase['thesis']}\n";
                echo "    Risks:\n";
                foreach ($trade->bearCase['risks'] as $risk) {
                    echo "      • {$risk}\n";
                }
                echo "    Invalidation Triggers:\n";
                foreach ($trade->bearCase['invalidation_triggers'] as $trigger) {
                    echo "      • {$trigger}\n";
                }
            }
            
            if ($trade->scenarios !== null) {
                echo "\n  🎯 Scenarios:\n";
                foreach (['base', 'upside', 'downside'] as $scenario) {
                    if (isset($trade->scenarios[$scenario])) {
                        $s = $trade->scenarios[$scenario];
                        echo "    {$scenario}: {$s['return_pct']}% return ({$s['probability_pct']}% probability)\n";
                        echo "      → {$s['conditions']}\n";
                    }
                }
            }
            
            if ($trade->positionLogic !== null) {
                echo "\n  💡 Position Logic: {$trade->positionLogic}\n";
            }
            
            if (!empty($trade->sources)) {
                echo "\n  📚 Sources:\n";
                foreach ($trade->sources as $source) {
                    echo "    • {$source}\n";
                }
            }
            
            if ($trade->tradeConfidence > 0) {
                echo "\n  ⭐ Trade Confidence: " . ($trade->tradeConfidence * 100) . "%\n";
            }
            
            if ($trade->holdingPeriod !== null) {
                echo "  ⏰ Holding Period: {$trade->holdingPeriod}\n";
            }
            
            echo "  ---\n";
        }
        
        // Portfolio notes (enhanced format)
        if ($recommendation->portfolioNotes !== null) {
            echo "\n📋 Portfolio Notes:\n";
            echo "  Diversification: {$recommendation->portfolioNotes['diversification']}\n";
            echo "  Risk Level: {$recommendation->portfolioNotes['risk_level']}\n";
        }
        
    } else {
        echo "❌ No valid recommendations\n";
        if ($recommendation->error) {
            echo "Error: {$recommendation->error}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// Example 2: Simple Prompt (Legacy) - Quick Recommendations
// ============================================================================

echo "=== Example 2: Simple Prompt (Legacy Format) ===\n\n";

// Use simple prompt style for faster, less detailed responses
$configSimple = [
    'prompt_style' => 'simple',  // Explicitly use simple format
    'max_position_size_pct' => 10,
    'min_confidence' => 0.70,
];

try {
    $recommendation = $assistant->getRecommendations($holdings, $cashBalance, $totalEquity, $configSimple);
    
    if ($recommendation->isValid()) {
        echo "✅ Quick recommendation (confidence: {$recommendation->confidence})\n";
        echo "Analysis: {$recommendation->analysis}\n\n";
        
        foreach ($recommendation->trades as $trade) {
            echo "  • {$trade->action} {$trade->shares} shares of {$trade->ticker} @ \${$trade->price}\n";
            echo "    Reason: {$trade->reason}\n";
            echo "    Stop Loss: \${$trade->stopLoss}\n\n";
        }
    }
} catch (Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// Example 3: Working with Enhanced Response Data
// ============================================================================

echo "=== Example 3: Processing Enhanced Data Programmatically ===\n\n";

// Get enhanced recommendations
$recommendation = $assistant->getRecommendations($holdings, $cashBalance, $totalEquity, [
    'prompt_style' => 'enhanced',
    'min_confidence' => 0.75,
]);

if ($recommendation->isValid()) {
    
    // Convert to array for storage/API responses
    $data = $recommendation->toArray();
    
    // Example: Filter high-confidence trades
    $highConfidenceTrades = array_filter($recommendation->trades, function($trade) {
        return $trade->tradeConfidence >= 0.80;
    });
    
    echo "Found " . count($highConfidenceTrades) . " high-confidence trades (>=80%)\n";
    
    // Example: Calculate total capital allocation
    $totalCapitalNeeded = array_reduce($recommendation->trades, function($sum, $trade) {
        return $sum + $trade->getTotalValue();
    }, 0);
    
    echo "Total capital needed: \$" . number_format($totalCapitalNeeded, 2) . "\n";
    echo "Available cash: \$" . number_format($cashBalance, 2) . "\n";
    echo "Remaining after trades: \$" . number_format($cashBalance - $totalCapitalNeeded, 2) . "\n\n";
    
    // Example: Find best risk/reward trades
    $tradesWithTargets = array_filter($recommendation->trades, fn($t) => $t->priceTarget > 0);
    usort($tradesWithTargets, fn($a, $b) => $b->getRiskRewardRatio() <=> $a->getRiskRewardRatio());
    
    if (!empty($tradesWithTargets)) {
        echo "Best risk/reward ratio: {$tradesWithTargets[0]->ticker} ";
        echo "(" . round($tradesWithTargets[0]->getRiskRewardRatio(), 2) . ":1)\n";
    }
    
    // Example: Check for sources (verify AI cited data)
    $tradesWithSources = array_filter($recommendation->trades, fn($t) => !empty($t->sources));
    echo "\n" . count($tradesWithSources) . " of " . count($recommendation->trades) . " trades cited sources\n";
    
    // Example: Identify trades with data limitations
    foreach ($recommendation->trades as $trade) {
        if ($trade->fundamentals !== null) {
            $unavailableData = array_filter($trade->fundamentals, function($value) {
                return is_string($value) && stripos($value, 'unavailable') !== false;
            });
            
            if (!empty($unavailableData)) {
                echo "⚠️  {$trade->ticker}: Missing data for " . implode(', ', array_keys($unavailableData)) . "\n";
            }
        }
    }
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// Example 4: Custom Configuration for Different Risk Profiles
// ============================================================================

echo "=== Example 4: Different Risk Profiles ===\n\n";

// Conservative investor
$conservativeConfig = [
    'prompt_style' => 'enhanced',
    'max_position_size_pct' => 5,      // Smaller positions
    'min_confidence' => 0.80,           // Higher confidence threshold
    'min_cash_reserve' => 2000,         // Larger cash cushion
    'focus' => 'small-cap',             // Less risky than micro-cap
    'market_cap_limit' => 2000000000,   // $2B cap for small-cap
];

echo "Conservative Profile:\n";
echo "  Max position: 5% of portfolio\n";
echo "  Min confidence: 80%\n";
echo "  Focus: Small-cap stocks\n\n";

// Aggressive investor
$aggressiveConfig = [
    'prompt_style' => 'enhanced',
    'max_position_size_pct' => 15,     // Larger positions
    'min_confidence' => 0.65,           // Lower confidence threshold
    'min_cash_reserve' => 500,          // Minimal cash cushion
    'focus' => 'micro-cap',
    'market_cap_limit' => 300000000,    // True micro-cap
];

echo "Aggressive Profile:\n";
echo "  Max position: 15% of portfolio\n";
echo "  Min confidence: 65%\n";
echo "  Focus: Micro-cap stocks\n\n";

// You can use these configs based on user preferences or account type

echo str_repeat("=", 80) . "\n\n";

// ============================================================================
// Example 5: Verification Best Practices (CIBC Guidelines)
// ============================================================================

echo "=== Example 5: Verification Best Practices ===\n\n";

$recommendation = $assistant->getRecommendations($holdings, $cashBalance, $totalEquity, [
    'prompt_style' => 'enhanced',
]);

if ($recommendation->isValid()) {
    echo "✅ VERIFICATION CHECKLIST:\n\n";
    
    // 1. Check the math
    echo "1️⃣  Math Verification:\n";
    $totalCost = 0;
    foreach ($recommendation->trades as $trade) {
        $totalCost += $trade->getTotalValue();
        
        // Verify stop loss is below entry (for buy orders)
        if ($trade->action === 'buy' && $trade->stopLoss >= $trade->price) {
            echo "   ⚠️  {$trade->ticker}: Stop loss should be BELOW entry price\n";
        }
        
        // Verify position doesn't exceed max
        $maxPosition = $totalEquity * 0.10; // Assuming 10% max
        if ($trade->getTotalValue() > $maxPosition) {
            echo "   ⚠️  {$trade->ticker}: Position exceeds 10% limit\n";
        }
    }
    
    if ($totalCost > $cashBalance) {
        echo "   ⚠️  Total cost (\${$totalCost}) exceeds available cash (\${$cashBalance})\n";
    } else {
        echo "   ✅ Total cost (\${$totalCost}) within available cash\n";
    }
    
    // 2. Check the sources
    echo "\n2️⃣  Source Verification:\n";
    $sourcedTrades = 0;
    foreach ($recommendation->trades as $trade) {
        if (!empty($trade->sources)) {
            $sourcedTrades++;
            echo "   ✅ {$trade->ticker}: Cited " . count($trade->sources) . " source(s)\n";
        } else {
            echo "   ⚠️  {$trade->ticker}: No sources cited\n";
        }
    }
    echo "   {$sourcedTrades} of " . count($recommendation->trades) . " trades cited sources\n";
    
    // 3. Check assumptions
    echo "\n3️⃣  Assumption Review:\n";
    foreach ($recommendation->trades as $trade) {
        if ($trade->bullCase !== null && !empty($trade->bullCase['assumptions'])) {
            echo "   ✅ {$trade->ticker}: Listed " . count($trade->bullCase['assumptions']) . " assumption(s)\n";
        } else {
            echo "   ⚠️  {$trade->ticker}: No assumptions listed\n";
        }
    }
    
    // 4. Check for hallucinations
    echo "\n4️⃣  Hallucination Check:\n";
    foreach ($recommendation->trades as $trade) {
        // Check ticker format (simple validation)
        if (strlen($trade->ticker) < 1 || strlen($trade->ticker) > 5) {
            echo "   🚨 {$trade->ticker}: Suspicious ticker format\n";
        }
        
        // Check for impossible numbers
        if ($trade->fundamentals !== null) {
            foreach ($trade->fundamentals as $key => $value) {
                if (strpos($key, 'margin') !== false && is_numeric($value) && $value > 100) {
                    echo "   🚨 {$trade->ticker}: Impossible margin value ({$value}%)\n";
                }
            }
        }
    }
    echo "   No obvious hallucinations detected\n";
    
    // 5. Validate confidence
    echo "\n5️⃣  Confidence Analysis:\n";
    $avgConfidence = array_reduce($recommendation->trades, 
        fn($sum, $t) => $sum + $t->tradeConfidence, 0) / count($recommendation->trades);
    echo "   Average trade confidence: " . round($avgConfidence * 100, 1) . "%\n";
    echo "   Overall confidence: " . round($recommendation->confidence * 100, 1) . "%\n";
    
    if ($avgConfidence < 0.70) {
        echo "   ⚠️  Low average confidence - consider waiting for better opportunities\n";
    }
    
    echo "\n✅ Verification complete!\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "\n💡 TIP: Always verify AI recommendations before executing real trades!\n";
echo "   - Check math manually\n";
echo "   - Verify tickers exist\n";
echo "   - Confirm prices are current\n";
echo "   - Review sources when provided\n";
echo "   - Validate assumptions make sense\n\n";
