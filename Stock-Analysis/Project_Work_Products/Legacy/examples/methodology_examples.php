<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Ksfraser\MotleyFool\MotleyFoolAnalyzer;
use Ksfraser\WarrenBuffett\BuffettAnalyzer;
use Ksfraser\IPlace\IPlaceAnalyzer;
use Ksfraser\Evaluation\EvaluationAnalyzer;
use Ksfraser\Analysis\UnifiedAnalyzer;

// Example usage of all investment methodology analyzers

echo "=== Investment Methodology Analyzers Example ===\n\n";

try {
    // Initialize analyzers
    $motleyFool = new MotleyFoolAnalyzer();
    $buffett = new BuffettAnalyzer();
    $iPlace = new IPlaceAnalyzer();
    $evaluation = new EvaluationAnalyzer();
    $unified = new UnifiedAnalyzer();
    
    // Example stock ID (replace with actual stock ID from your database)
    $stockId = 1;
    
    echo "Analyzing Stock ID: $stockId\n";
    echo str_repeat("=", 50) . "\n\n";
    
    // 1. Motley Fool Analysis
    echo "1. MOTLEY FOOL ANALYSIS\n";
    echo str_repeat("-", 30) . "\n";
    
    $mfRecommendation = $motleyFool->getRecommendation($stockId);
    if (!empty($mfRecommendation)) {
        echo "Score: {$mfRecommendation['score']}/10 ({$mfRecommendation['percentage']}%)\n";
        echo "Recommendation: {$mfRecommendation['recommendation']}\n";
        echo "Risk Level: {$mfRecommendation['risk_level']}\n";
        
        $criteria = $motleyFool->getCriteriaBreakdown($stockId);
        if (!empty($criteria)) {
            echo "\nCriteria Breakdown:\n";
            foreach ($criteria as $category => $details) {
                echo "  $category: " . ($details[array_keys($details)[0]] ? '✓' : '✗') . "\n";
            }
        }
    } else {
        echo "No Motley Fool data available for this stock.\n";
    }
    
    echo "\n";
    
    // 2. Warren Buffett Analysis
    echo "2. WARREN BUFFETT ANALYSIS\n";
    echo str_repeat("-", 30) . "\n";
    
    $buffettRecommendation = $buffett->getBuffettRecommendation($stockId);
    if (!empty($buffettRecommendation)) {
        echo "Tenets Score: {$buffettRecommendation['tenets_score']}/12 ({$buffettRecommendation['tenets_percentage']}%)\n";
        echo "Recommendation: {$buffettRecommendation['recommendation']}\n";
        echo "Risk Level: {$buffettRecommendation['risk_level']}\n";
        if ($buffettRecommendation['margin_of_safety'] !== null) {
            echo "Margin of Safety: {$buffettRecommendation['margin_of_safety']}%\n";
        }
        
        $tenetsBreakdown = $buffett->getTenetsBreakdown($stockId);
        if (!empty($tenetsBreakdown)) {
            echo "\nTenets Breakdown:\n";
            foreach ($tenetsBreakdown as $category => $tenets) {
                echo "  $category:\n";
                foreach ($tenets as $tenet => $value) {
                    echo "    $tenet: " . ($value ? '✓' : '✗') . "\n";
                }
            }
        }
    } else {
        echo "No Warren Buffett data available for this stock.\n";
    }
    
    echo "\n";
    
    // 3. IPlace Analysis
    echo "3. IPLACE ANALYSIS\n";
    echo str_repeat("-", 30) . "\n";
    
    $iPlaceScore = $iPlace->calculateCompositeScore($stockId);
    if (!empty($iPlaceScore)) {
        echo "Composite Score: {$iPlaceScore['composite_score']}/100\n";
        echo "Recommendation: {$iPlaceScore['recommendation']}\n";
        
        echo "\nIndividual Scores:\n";
        foreach ($iPlaceScore['individual_scores'] as $metric => $score) {
            echo "  " . ucfirst(str_replace('_', ' ', $metric)) . ": {$score}/100\n";
        }
        
        $metrics = $iPlace->getMetricsBreakdown($stockId);
        if (!empty($metrics)) {
            echo "\nKey Metrics:\n";
            foreach ($metrics as $category => $data) {
                echo "  $category:\n";
                foreach ($data as $metric => $value) {
                    echo "    " . ucfirst(str_replace('_', ' ', $metric)) . ": $value\n";
                }
            }
        }
    } else {
        echo "No IPlace data available for this stock.\n";
    }
    
    echo "\n";
    
    // 4. Comprehensive Evaluation Analysis
    echo "4. COMPREHENSIVE EVALUATION ANALYSIS\n";
    echo str_repeat("-", 40) . "\n";
    
    $comprehensive = $evaluation->getComprehensiveEvaluation($stockId);
    if (!empty($comprehensive['overall_score'])) {
        echo "Overall Score: {$comprehensive['overall_score']['overall_score']}/100\n";
        echo "Grade: {$comprehensive['overall_score']['grade']}\n";
        echo "Recommendation: {$comprehensive['recommendation']}\n";
        
        echo "\nIndividual Evaluation Scores:\n";
        foreach ($comprehensive['overall_score']['individual_scores'] as $eval => $score) {
            echo "  " . ucfirst($eval) . ": {$score}/100\n";
        }
    } else {
        echo "No comprehensive evaluation data available for this stock.\n";
    }
    
    echo "\n";
    
    // 5. Unified Analysis
    echo "5. UNIFIED ANALYSIS (ALL METHODOLOGIES)\n";
    echo str_repeat("-", 45) . "\n";
    
    $unifiedAnalysis = $unified->getComprehensiveAnalysis($stockId);
    if (!isset($unifiedAnalysis['error'])) {
        $unifiedScore = $unifiedAnalysis['unified_score'];
        $consensus = $unifiedAnalysis['consensus_recommendation'];
        $risk = $unifiedAnalysis['risk_assessment'];
        
        echo "Stock: {$unifiedAnalysis['stock_info']['symbol']} - {$unifiedAnalysis['stock_info']['name']}\n";
        echo "Unified Score: {$unifiedScore['unified_score']}/100 (Grade: {$unifiedScore['grade']})\n";
        echo "Confidence Level: {$unifiedScore['confidence_level']}\n";
        echo "Consensus: {$consensus['consensus']}\n";
        echo "Overall Risk: {$risk['overall_risk']}\n";
        
        echo "\nMethodology Breakdown:\n";
        foreach ($unifiedScore['individual_scores'] as $methodology => $score) {
            echo "  " . ucfirst(str_replace('_', ' ', $methodology)) . ": {$score}/100\n";
        }
        
        echo "\nSignal Distribution:\n";
        echo "  Buy Signals: {$consensus['signal_distribution']['buy']}\n";
        echo "  Hold Signals: {$consensus['signal_distribution']['hold']}\n";
        echo "  Sell Signals: {$consensus['signal_distribution']['sell']}\n";
        echo "  Consensus Confidence: {$consensus['confidence']}\n";
    } else {
        echo $unifiedAnalysis['error'] . "\n";
    }
    
    echo "\n";
    
    // 6. Top Stocks Examples
    echo "6. TOP STOCKS BY METHODOLOGY\n";
    echo str_repeat("-", 35) . "\n";
    
    // Top Motley Fool stocks
    echo "Top Motley Fool Stocks (Score >= 7):\n";
    $topMF = $motleyFool->getStocksByMinScore(7);
    foreach (array_slice($topMF, 0, 5) as $stock) {
        echo "  {$stock['symbol']}: {$stock['total_score']}/10\n";
    }
    
    echo "\nTop Warren Buffett Stocks (Score >= 9):\n";
    $topBuffett = $buffett->getHighQualityBuffettStocks(9);
    foreach (array_slice($topBuffett, 0, 5) as $stock) {
        echo "  {$stock['symbol']}: {$stock['total_score']}/12\n";
    }
    
    echo "\nTop IPlace Stocks:\n";
    $topIPlace = $iPlace->getTopIPlaceStocks(5);
    foreach ($topIPlace as $stock) {
        echo "  {$stock['symbol']}: Growth {$stock['earnings_growth_rate']}%\n";
    }
    
    echo "\nTop Unified Stocks:\n";
    $topUnified = $unified->getTopUnifiedStocks(5);
    foreach ($topUnified as $stock) {
        echo "  {$stock['stock_info']['symbol']}: {$stock['unified_score']}/100 ({$stock['grade']})\n";
    }
    
    echo "\n";
    
    // 7. Specific Analysis Examples
    echo "7. SPECIFIC ANALYSIS EXAMPLES\n";
    echo str_repeat("-", 35) . "\n";
    
    // Margin of safety analysis
    echo "Stocks with Margin of Safety > 25%:\n";
    $marginStocks = $buffett->getStocksWithMarginOfSafety(25);
    foreach (array_slice($marginStocks, 0, 5) as $stock) {
        echo "  {$stock['symbol']}: {$stock['marginsafety']}% margin\n";
    }
    
    // Perfect Motley Fool scores
    echo "\nPerfect Motley Fool Stocks (10/10):\n";
    $perfectMF = $motleyFool->getPerfectScoreStocks();
    foreach (array_slice($perfectMF, 0, 5) as $stock) {
        echo "  {$stock['symbol']}: 10/10\n";
    }
    
    // Top evaluation stocks
    echo "\nTop Comprehensive Evaluation Stocks:\n";
    $topEval = $evaluation->getTopRatedStocks(5);
    foreach ($topEval as $stock) {
        echo "  {$stock['stock_info']['symbol']}: {$stock['overall_score']}/100 ({$stock['grade']})\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Make sure your database connection is properly configured.\n";
}

echo "\n=== Analysis Complete ===\n";
