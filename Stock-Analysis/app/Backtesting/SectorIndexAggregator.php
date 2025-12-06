<?php

declare(strict_types=1);

namespace App\Backtesting;

/**
 * Sector and Index Performance Aggregator
 * 
 * Aggregates and analyzes trading strategy performance by:
 * - Sector (Technology, Healthcare, Financial, Energy, etc.)
 * - Index (NASDAQ, NYSE, S&P 500, etc.)
 * - Sector + Strategy combinations
 * - Index + Strategy combinations
 * 
 * Provides sector rotation analysis and performance comparison
 * 
 * @package App\Backtesting
 */
class SectorIndexAggregator
{
    /**
     * @var array<string, array<string, mixed>> Results by symbol
     */
    private array $results = [];
    
    /**
     * Add a backtest result
     *
     * @param string $symbol Stock symbol
     * @param string $sector Sector classification
     * @param string $index Index membership
     * @param string $strategy Strategy name
     * @param float $return Return percentage
     * @return void
     */
    public function addResult(string $symbol, string $sector, string $index, string $strategy, float $return): void
    {
        $this->results[] = [
            'symbol' => $symbol,
            'sector' => $sector,
            'index' => $index,
            'strategy' => $strategy,
            'return' => $return
        ];
    }
    
    /**
     * Get performance aggregated by sector
     *
     * @param string|null $strategyFilter Optional strategy filter
     * @return array<string, array<string, mixed>> Sector performance data
     */
    public function getPerformanceBySector(?string $strategyFilter = null): array
    {
        $results = $strategyFilter 
            ? array_filter($this->results, fn($r) => $r['strategy'] === $strategyFilter)
            : $this->results;
        
        $sectors = [];
        
        foreach ($results as $result) {
            $sector = $result['sector'];
            
            if (!isset($sectors[$sector])) {
                $sectors[$sector] = [
                    'returns' => [],
                    'count' => 0,
                    'total_return' => 0.0,
                    'min_return' => PHP_FLOAT_MAX,
                    'max_return' => PHP_FLOAT_MIN
                ];
            }
            
            $sectors[$sector]['returns'][] = $result['return'];
            $sectors[$sector]['count']++;
            $sectors[$sector]['total_return'] += $result['return'];
            $sectors[$sector]['min_return'] = min($sectors[$sector]['min_return'], $result['return']);
            $sectors[$sector]['max_return'] = max($sectors[$sector]['max_return'], $result['return']);
        }
        
        // Calculate averages and volatility
        foreach ($sectors as $sector => &$data) {
            $data['average_return'] = $data['total_return'] / $data['count'];
            $data['volatility'] = $this->calculateVolatility($data['returns']);
            unset($data['returns']); // Remove raw data
        }
        
        return $sectors;
    }
    
    /**
     * Get performance aggregated by index
     *
     * @param string|null $strategyFilter Optional strategy filter
     * @return array<string, array<string, mixed>> Index performance data
     */
    public function getPerformanceByIndex(?string $strategyFilter = null): array
    {
        $results = $strategyFilter 
            ? array_filter($this->results, fn($r) => $r['strategy'] === $strategyFilter)
            : $this->results;
        
        $indices = [];
        
        foreach ($results as $result) {
            $index = $result['index'];
            
            if (!isset($indices[$index])) {
                $indices[$index] = [
                    'returns' => [],
                    'count' => 0,
                    'total_return' => 0.0,
                    'min_return' => PHP_FLOAT_MAX,
                    'max_return' => PHP_FLOAT_MIN
                ];
            }
            
            $indices[$index]['returns'][] = $result['return'];
            $indices[$index]['count']++;
            $indices[$index]['total_return'] += $result['return'];
            $indices[$index]['min_return'] = min($indices[$index]['min_return'], $result['return']);
            $indices[$index]['max_return'] = max($indices[$index]['max_return'], $result['return']);
        }
        
        // Calculate averages and volatility
        foreach ($indices as $index => &$data) {
            $data['average_return'] = $data['total_return'] / $data['count'];
            $data['volatility'] = $this->calculateVolatility($data['returns']);
            unset($data['returns']);
        }
        
        return $indices;
    }
    
    /**
     * Get performance by sector and strategy
     *
     * @return array<string, array<string, array<string, mixed>>> Nested performance data
     */
    public function getPerformanceBySectorAndStrategy(): array
    {
        $sectorStrategy = [];
        
        foreach ($this->results as $result) {
            $sector = $result['sector'];
            $strategy = $result['strategy'];
            
            if (!isset($sectorStrategy[$sector])) {
                $sectorStrategy[$sector] = [];
            }
            
            if (!isset($sectorStrategy[$sector][$strategy])) {
                $sectorStrategy[$sector][$strategy] = [
                    'count' => 0,
                    'total_return' => 0.0
                ];
            }
            
            $sectorStrategy[$sector][$strategy]['count']++;
            $sectorStrategy[$sector][$strategy]['total_return'] += $result['return'];
        }
        
        // Calculate averages
        foreach ($sectorStrategy as $sector => &$strategies) {
            foreach ($strategies as $strategy => &$data) {
                $data['average_return'] = $data['total_return'] / $data['count'];
            }
        }
        
        return $sectorStrategy;
    }
    
    /**
     * Get performance by index and strategy
     *
     * @return array<string, array<string, array<string, mixed>>> Nested performance data
     */
    public function getPerformanceByIndexAndStrategy(): array
    {
        $indexStrategy = [];
        
        foreach ($this->results as $result) {
            $index = $result['index'];
            $strategy = $result['strategy'];
            
            if (!isset($indexStrategy[$index])) {
                $indexStrategy[$index] = [];
            }
            
            if (!isset($indexStrategy[$index][$strategy])) {
                $indexStrategy[$index][$strategy] = [
                    'count' => 0,
                    'total_return' => 0.0
                ];
            }
            
            $indexStrategy[$index][$strategy]['count']++;
            $indexStrategy[$index][$strategy]['total_return'] += $result['return'];
        }
        
        // Calculate averages
        foreach ($indexStrategy as $index => &$strategies) {
            foreach ($strategies as $strategy => &$data) {
                $data['average_return'] = $data['total_return'] / $data['count'];
            }
        }
        
        return $indexStrategy;
    }
    
    /**
     * Get best performing sector
     *
     * @return array<string, mixed> Sector data
     */
    public function getBestPerformingSector(): array
    {
        $sectors = $this->getPerformanceBySector();
        
        if (empty($sectors)) {
            return [];
        }
        
        $best = null;
        $maxReturn = PHP_FLOAT_MIN;
        
        foreach ($sectors as $sector => $data) {
            if ($data['average_return'] > $maxReturn) {
                $maxReturn = $data['average_return'];
                $best = ['sector' => $sector] + $data;
            }
        }
        
        return $best ?? [];
    }
    
    /**
     * Get worst performing sector
     *
     * @return array<string, mixed> Sector data
     */
    public function getWorstPerformingSector(): array
    {
        $sectors = $this->getPerformanceBySector();
        
        if (empty($sectors)) {
            return [];
        }
        
        $worst = null;
        $minReturn = PHP_FLOAT_MAX;
        
        foreach ($sectors as $sector => $data) {
            if ($data['average_return'] < $minReturn) {
                $minReturn = $data['average_return'];
                $worst = ['sector' => $sector] + $data;
            }
        }
        
        return $worst ?? [];
    }
    
    /**
     * Get best performing index
     *
     * @return array<string, mixed> Index data
     */
    public function getBestPerformingIndex(): array
    {
        $indices = $this->getPerformanceByIndex();
        
        if (empty($indices)) {
            return [];
        }
        
        $best = null;
        $maxReturn = PHP_FLOAT_MIN;
        
        foreach ($indices as $index => $data) {
            if ($data['average_return'] > $maxReturn) {
                $maxReturn = $data['average_return'];
                $best = ['index' => $index] + $data;
            }
        }
        
        return $best ?? [];
    }
    
    /**
     * Get top N performing sectors
     *
     * @param int $limit Number of sectors to return
     * @return array<int, array<string, mixed>> Top sectors
     */
    public function getTopPerformingSectors(int $limit = 5): array
    {
        $sectors = $this->getPerformanceBySector();
        
        $sectorArray = [];
        foreach ($sectors as $sector => $data) {
            $sectorArray[] = ['sector' => $sector] + $data;
        }
        
        usort($sectorArray, fn($a, $b) => $b['average_return'] <=> $a['average_return']);
        
        return array_slice($sectorArray, 0, $limit);
    }
    
    /**
     * Calculate correlation between two sectors
     *
     * @param string $sector1 First sector
     * @param string $sector2 Second sector
     * @return float Correlation coefficient (-1 to 1)
     */
    public function getSectorCorrelation(string $sector1, string $sector2): float
    {
        $returns1 = [];
        $returns2 = [];
        
        foreach ($this->results as $result) {
            if ($result['sector'] === $sector1) {
                $returns1[] = $result['return'];
            } elseif ($result['sector'] === $sector2) {
                $returns2[] = $result['return'];
            }
        }
        
        if (count($returns1) < 2 || count($returns2) < 2) {
            return 0.0;
        }
        
        // Calculate correlation coefficient
        $count = min(count($returns1), count($returns2));
        $returns1 = array_slice($returns1, 0, $count);
        $returns2 = array_slice($returns2, 0, $count);
        
        $mean1 = array_sum($returns1) / $count;
        $mean2 = array_sum($returns2) / $count;
        
        $numerator = 0.0;
        $denominator1 = 0.0;
        $denominator2 = 0.0;
        
        for ($i = 0; $i < $count; $i++) {
            $diff1 = $returns1[$i] - $mean1;
            $diff2 = $returns2[$i] - $mean2;
            $numerator += $diff1 * $diff2;
            $denominator1 += $diff1 * $diff1;
            $denominator2 += $diff2 * $diff2;
        }
        
        $denominator = sqrt($denominator1 * $denominator2);
        
        return $denominator > 0 ? $numerator / $denominator : 0.0;
    }
    
    /**
     * Generate sector rotation analysis report
     *
     * @return string Formatted report
     */
    public function generateSectorRotationReport(): string
    {
        $report = str_repeat('=', 80) . "\n";
        $report .= "SECTOR ROTATION ANALYSIS\n";
        $report .= str_repeat('=', 80) . "\n\n";
        
        $sectors = $this->getPerformanceBySector();
        
        if (empty($sectors)) {
            $report .= "No sector data available\n";
            return $report;
        }
        
        // Best and worst performers
        $best = $this->getBestPerformingSector();
        $worst = $this->getWorstPerformingSector();
        
        $report .= "Best Performing Sector:\n";
        $report .= str_repeat('-', 80) . "\n";
        $report .= sprintf("Sector: %s\n", $best['sector']);
        $report .= sprintf("Average Return: %.2f%%\n", $best['average_return']);
        $report .= sprintf("Volatility: %.2f%%\n\n", $best['volatility']);
        
        $report .= "Worst Performing Sector:\n";
        $report .= str_repeat('-', 80) . "\n";
        $report .= sprintf("Sector: %s\n", $worst['sector']);
        $report .= sprintf("Average Return: %.2f%%\n", $worst['average_return']);
        $report .= sprintf("Volatility: %.2f%%\n\n", $worst['volatility']);
        
        // All sectors ranked
        $topSectors = $this->getTopPerformingSectors(count($sectors));
        
        $report .= "Sector Performance Ranking:\n";
        $report .= str_repeat('-', 80) . "\n";
        $report .= sprintf("%-20s %15s %15s %15s\n", "Sector", "Avg Return", "Volatility", "Count");
        $report .= str_repeat('-', 80) . "\n";
        
        foreach ($topSectors as $sector) {
            $report .= sprintf(
                "%-20s %14.2f%% %14.2f%% %15d\n",
                $sector['sector'],
                $sector['average_return'],
                $sector['volatility'],
                $sector['count']
            );
        }
        
        $report .= "\n" . str_repeat('=', 80) . "\n";
        
        return $report;
    }
    
    /**
     * Export sector performance to CSV
     *
     * @return string CSV content
     */
    public function exportSectorPerformanceToCSV(): string
    {
        $sectors = $this->getPerformanceBySector();
        
        $csv = "Sector,Count,Average Return,Min Return,Max Return,Volatility\n";
        
        foreach ($sectors as $sector => $data) {
            $csv .= sprintf(
                "%s,%d,%.2f,%.2f,%.2f,%.2f\n",
                $sector,
                $data['count'],
                $data['average_return'],
                $data['min_return'],
                $data['max_return'],
                $data['volatility']
            );
        }
        
        return $csv;
    }
    
    /**
     * Export index performance to CSV
     *
     * @return string CSV content
     */
    public function exportIndexPerformanceToCSV(): string
    {
        $indices = $this->getPerformanceByIndex();
        
        $csv = "Index,Count,Average Return,Min Return,Max Return,Volatility\n";
        
        foreach ($indices as $index => $data) {
            $csv .= sprintf(
                "%s,%d,%.2f,%.2f,%.2f,%.2f\n",
                $index,
                $data['count'],
                $data['average_return'],
                $data['min_return'],
                $data['max_return'],
                $data['volatility']
            );
        }
        
        return $csv;
    }
    
    /**
     * Calculate volatility (standard deviation) of returns
     *
     * @param array<float> $returns Array of returns
     * @return float Volatility
     */
    private function calculateVolatility(array $returns): float
    {
        if (count($returns) < 2) {
            return 0.0;
        }
        
        $mean = array_sum($returns) / count($returns);
        $variance = 0.0;
        
        foreach ($returns as $return) {
            $variance += pow($return - $mean, 2);
        }
        
        $variance /= count($returns);
        
        return sqrt($variance);
    }
}
