<?php

declare(strict_types=1);

namespace App\Backtesting;

/**
 * Signal Accuracy Tracker
 * 
 * Tracks trading signal predictions and measures accuracy by comparing
 * predicted direction against actual price movement.
 * 
 * Features:
 * - Overall accuracy tracking
 * - Per-strategy accuracy
 * - Per-symbol accuracy
 * - Per-sector accuracy
 * - Per-index accuracy
 * - Confidence correlation analysis
 * - Timeframe-based accuracy
 * 
 * @package App\Backtesting
 */
class SignalAccuracyTracker
{
    /** @var array<int, array<string, mixed>> Recorded signals */
    private array $signals = [];
    
    /**
     * Record a trading signal and its outcome
     *
     * @param string $symbol Stock symbol
     * @param string $signal BUY, SELL, or HOLD
     * @param float $signalPrice Price when signal was generated
     * @param float $actualPrice Actual price after timeframe
     * @param float $confidence Signal confidence (0-1)
     * @param int $daysForward Days forward to measure
     * @param string $strategy Strategy name
     * @param string|null $sector Stock sector (optional)
     * @param string|null $index Index membership (optional)
     * @return void
     */
    public function recordSignal(
        string $symbol,
        string $signal,
        float $signalPrice,
        float $actualPrice,
        float $confidence,
        int $daysForward,
        string $strategy,
        ?string $sector = null,
        ?string $index = null
    ): void {
        // Don't track HOLD signals (no prediction to validate)
        if ($signal === 'HOLD') {
            return;
        }
        
        $priceChange = $actualPrice - $signalPrice;
        $priceChangePercent = ($priceChange / $signalPrice) * 100;
        
        // Determine if prediction was correct
        $correct = false;
        if ($signal === 'BUY' && $priceChange > 0) {
            $correct = true;
        } elseif ($signal === 'SELL' && $priceChange < 0) {
            $correct = true;
        }
        
        $this->signals[] = [
            'symbol' => $symbol,
            'signal' => $signal,
            'signal_price' => $signalPrice,
            'actual_price' => $actualPrice,
            'price_change' => $priceChange,
            'price_change_percent' => $priceChangePercent,
            'confidence' => $confidence,
            'days_forward' => $daysForward,
            'strategy' => $strategy,
            'sector' => $sector,
            'index' => $index,
            'correct' => $correct,
            'timestamp' => time()
        ];
    }
    
    /**
     * Get overall accuracy percentage
     *
     * @return float Accuracy percentage (0-100)
     */
    public function getAccuracy(): float
    {
        if (empty($this->signals)) {
            return 0.0;
        }
        
        $correct = array_filter($this->signals, fn($s) => $s['correct']);
        
        return (count($correct) / count($this->signals)) * 100;
    }
    
    /**
     * Get accuracy by strategy
     *
     * @return array<string, float> Strategy => accuracy percentage
     */
    public function getAccuracyByStrategy(): array
    {
        $byStrategy = [];
        
        foreach ($this->signals as $signal) {
            $strategy = $signal['strategy'];
            
            if (!isset($byStrategy[$strategy])) {
                $byStrategy[$strategy] = ['total' => 0, 'correct' => 0];
            }
            
            $byStrategy[$strategy]['total']++;
            if ($signal['correct']) {
                $byStrategy[$strategy]['correct']++;
            }
        }
        
        $accuracy = [];
        foreach ($byStrategy as $strategy => $stats) {
            $accuracy[$strategy] = ($stats['correct'] / $stats['total']) * 100;
        }
        
        return $accuracy;
    }
    
    /**
     * Get accuracy by symbol
     *
     * @return array<string, float> Symbol => accuracy percentage
     */
    public function getAccuracyBySymbol(): array
    {
        $bySymbol = [];
        
        foreach ($this->signals as $signal) {
            $symbol = $signal['symbol'];
            
            if (!isset($bySymbol[$symbol])) {
                $bySymbol[$symbol] = ['total' => 0, 'correct' => 0];
            }
            
            $bySymbol[$symbol]['total']++;
            if ($signal['correct']) {
                $bySymbol[$symbol]['correct']++;
            }
        }
        
        $accuracy = [];
        foreach ($bySymbol as $symbol => $stats) {
            $accuracy[$symbol] = ($stats['correct'] / $stats['total']) * 100;
        }
        
        return $accuracy;
    }
    
    /**
     * Get accuracy by sector
     *
     * @return array<string, float> Sector => accuracy percentage
     */
    public function getAccuracyBySector(): array
    {
        $bySector = [];
        
        foreach ($this->signals as $signal) {
            $sector = $signal['sector'];
            
            if ($sector === null) {
                continue;
            }
            
            if (!isset($bySector[$sector])) {
                $bySector[$sector] = ['total' => 0, 'correct' => 0];
            }
            
            $bySector[$sector]['total']++;
            if ($signal['correct']) {
                $bySector[$sector]['correct']++;
            }
        }
        
        $accuracy = [];
        foreach ($bySector as $sector => $stats) {
            $accuracy[$sector] = ($stats['correct'] / $stats['total']) * 100;
        }
        
        return $accuracy;
    }
    
    /**
     * Get accuracy by index
     *
     * @return array<string, float> Index => accuracy percentage
     */
    public function getAccuracyByIndex(): array
    {
        $byIndex = [];
        
        foreach ($this->signals as $signal) {
            $index = $signal['index'];
            
            if ($index === null) {
                continue;
            }
            
            if (!isset($byIndex[$index])) {
                $byIndex[$index] = ['total' => 0, 'correct' => 0];
            }
            
            $byIndex[$index]['total']++;
            if ($signal['correct']) {
                $byIndex[$index]['correct']++;
            }
        }
        
        $accuracy = [];
        foreach ($byIndex as $index => $stats) {
            $accuracy[$index] = ($stats['correct'] / $stats['total']) * 100;
        }
        
        return $accuracy;
    }
    
    /**
     * Get accuracy by timeframe
     *
     * @return array<int, float> Days forward => accuracy percentage
     */
    public function getAccuracyByTimeframe(): array
    {
        $byTimeframe = [];
        
        foreach ($this->signals as $signal) {
            $days = $signal['days_forward'];
            
            if (!isset($byTimeframe[$days])) {
                $byTimeframe[$days] = ['total' => 0, 'correct' => 0];
            }
            
            $byTimeframe[$days]['total']++;
            if ($signal['correct']) {
                $byTimeframe[$days]['correct']++;
            }
        }
        
        $accuracy = [];
        foreach ($byTimeframe as $days => $stats) {
            $accuracy[$days] = ($stats['correct'] / $stats['total']) * 100;
        }
        
        ksort($accuracy);
        
        return $accuracy;
    }
    
    /**
     * Get detailed statistics
     *
     * @return array<string, mixed> Detailed statistics
     */
    public function getDetailedStats(): array
    {
        if (empty($this->signals)) {
            return [
                'total_signals' => 0,
                'correct_signals' => 0,
                'incorrect_signals' => 0,
                'overall_accuracy' => 0.0,
                'avg_correct_move_percent' => 0.0,
                'avg_incorrect_move_percent' => 0.0,
                'high_confidence_accuracy' => 0.0,
                'low_confidence_accuracy' => 0.0,
                'confidence_correlation' => 0.0
            ];
        }
        
        $correct = array_filter($this->signals, fn($s) => $s['correct']);
        $incorrect = array_filter($this->signals, fn($s) => !$s['correct']);
        
        // Calculate average price movements
        $correctMoves = array_map(fn($s) => abs($s['price_change_percent']), $correct);
        $incorrectMoves = array_map(fn($s) => abs($s['price_change_percent']), $incorrect);
        
        $avgCorrectMove = !empty($correctMoves) ? array_sum($correctMoves) / count($correctMoves) : 0.0;
        $avgIncorrectMove = !empty($incorrectMoves) ? array_sum($incorrectMoves) / count($incorrectMoves) : 0.0;
        
        // Confidence analysis
        $highConfidence = array_filter($this->signals, fn($s) => $s['confidence'] >= 0.70);
        $lowConfidence = array_filter($this->signals, fn($s) => $s['confidence'] < 0.70);
        
        $highConfCorrect = array_filter($highConfidence, fn($s) => $s['correct']);
        $lowConfCorrect = array_filter($lowConfidence, fn($s) => $s['correct']);
        
        $highConfAccuracy = !empty($highConfidence) ? 
            (count($highConfCorrect) / count($highConfidence)) * 100 : 0.0;
        $lowConfAccuracy = !empty($lowConfidence) ? 
            (count($lowConfCorrect) / count($lowConfidence)) * 100 : 0.0;
        
        // Confidence correlation (simple: difference between high/low conf accuracy)
        $confidenceCorrelation = $highConfAccuracy - $lowConfAccuracy;
        
        return [
            'total_signals' => count($this->signals),
            'correct_signals' => count($correct),
            'incorrect_signals' => count($incorrect),
            'overall_accuracy' => $this->getAccuracy(),
            'avg_correct_move_percent' => $avgCorrectMove,
            'avg_incorrect_move_percent' => $avgIncorrectMove,
            'high_confidence_accuracy' => $highConfAccuracy,
            'low_confidence_accuracy' => $lowConfAccuracy,
            'confidence_correlation' => $confidenceCorrelation
        ];
    }
    
    /**
     * Generate accuracy report
     *
     * @return string Formatted report
     */
    public function generateReport(): string
    {
        $stats = $this->getDetailedStats();
        $byStrategy = $this->getAccuracyByStrategy();
        $bySymbol = $this->getAccuracyBySymbol();
        $bySector = $this->getAccuracyBySector();
        $byIndex = $this->getAccuracyByIndex();
        
        $report = str_repeat('=', 80) . "\n";
        $report .= "SIGNAL PREDICTION ACCURACY REPORT\n";
        $report .= str_repeat('=', 80) . "\n\n";
        
        $report .= "Overall Statistics:\n";
        $report .= str_repeat('-', 80) . "\n";
        $report .= sprintf("Total Signals: %d\n", $stats['total_signals']);
        $report .= sprintf("Correct Predictions: %d\n", $stats['correct_signals']);
        $report .= sprintf("Incorrect Predictions: %d\n", $stats['incorrect_signals']);
        $report .= sprintf("Overall Accuracy: %.2f%%\n\n", $stats['overall_accuracy']);
        
        $report .= sprintf("Avg Correct Move: %.2f%%\n", $stats['avg_correct_move_percent']);
        $report .= sprintf("Avg Incorrect Move: %.2f%%\n\n", $stats['avg_incorrect_move_percent']);
        
        $report .= "Confidence Analysis:\n";
        $report .= sprintf("High Confidence (>=70%%) Accuracy: %.2f%%\n", $stats['high_confidence_accuracy']);
        $report .= sprintf("Low Confidence (<70%%) Accuracy: %.2f%%\n", $stats['low_confidence_accuracy']);
        $report .= sprintf("Confidence Correlation: %.2f%%\n\n", $stats['confidence_correlation']);
        
        if (!empty($byStrategy)) {
            $report .= "Accuracy By Strategy:\n";
            $report .= str_repeat('-', 80) . "\n";
            foreach ($byStrategy as $strategy => $accuracy) {
                $report .= sprintf("%-40s: %6.2f%%\n", $strategy, $accuracy);
            }
            $report .= "\n";
        }
        
        if (!empty($bySymbol)) {
            $report .= "Accuracy By Symbol:\n";
            $report .= str_repeat('-', 80) . "\n";
            foreach ($bySymbol as $symbol => $accuracy) {
                $report .= sprintf("%-10s: %6.2f%%\n", $symbol, $accuracy);
            }
            $report .= "\n";
        }
        
        if (!empty($bySector)) {
            $report .= "Accuracy By Sector:\n";
            $report .= str_repeat('-', 80) . "\n";
            foreach ($bySector as $sector => $accuracy) {
                $report .= sprintf("%-30s: %6.2f%%\n", $sector, $accuracy);
            }
            $report .= "\n";
        }
        
        if (!empty($byIndex)) {
            $report .= "Accuracy By Index:\n";
            $report .= str_repeat('-', 80) . "\n";
            foreach ($byIndex as $index => $accuracy) {
                $report .= sprintf("%-20s: %6.2f%%\n", $index, $accuracy);
            }
            $report .= "\n";
        }
        
        $report .= str_repeat('=', 80) . "\n";
        
        return $report;
    }
    
    /**
     * Export signals to CSV
     *
     * @return string CSV content
     */
    public function exportToCSV(): string
    {
        $csv = "Symbol,Signal,Signal Price,Actual Price,Price Change %,Confidence,Days Forward,Strategy,Sector,Index,Correct\n";
        
        foreach ($this->signals as $signal) {
            $csv .= sprintf(
                "%s,%s,%.2f,%.2f,%.2f,%.2f,%d,%s,%s,%s,%s\n",
                $signal['symbol'],
                $signal['signal'],
                $signal['signal_price'],
                $signal['actual_price'],
                $signal['price_change_percent'],
                $signal['confidence'],
                $signal['days_forward'],
                $signal['strategy'],
                $signal['sector'] ?? '',
                $signal['index'] ?? '',
                $signal['correct'] ? 'Yes' : 'No'
            );
        }
        
        return $csv;
    }
    
    /**
     * Get all recorded signals
     *
     * @return array<int, array<string, mixed>> All signals
     */
    public function getSignals(): array
    {
        return $this->signals;
    }
    
    /**
     * Clear all recorded signals
     *
     * @return void
     */
    public function clear(): void
    {
        $this->signals = [];
    }
}
