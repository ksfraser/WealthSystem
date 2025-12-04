<?php

namespace App\Services;

use App\DAO\SectorAnalysisDAO;

/**
 * Sector Analysis Chart Service
 * 
 * Prepares sector analysis data for chart visualization.
 * Calculates allocations, comparisons, and risk metrics.
 * 
 * Design Principles:
 * - SRP: Only responsible for chart data preparation
 * - DI: Dependencies injected via constructor
 * - SOLID: Interface-based dependencies
 * - DRY: Reusable calculation methods
 * 
 * @package App\Services
 * @version 1.0.0
 */
class SectorAnalysisChartService
{
    private SectorAnalysisDAO $dao;
    
    /**
     * Constructor with dependency injection
     * 
     * @param SectorAnalysisDAO $dao Sector data access object
     */
    public function __construct(SectorAnalysisDAO $dao)
    {
        $this->dao = $dao;
    }
    
    /**
     * Calculate sector allocation percentages from portfolio data
     * 
     * @param array $portfolioData Portfolio holdings
     * @return array<string, float> Sector => Percentage
     */
    public function calculateSectorAllocation(array $portfolioData): array
    {
        if (empty($portfolioData)) {
            return [];
        }
        
        // Calculate total portfolio value
        $totalValue = array_sum(array_column($portfolioData, 'value'));
        
        if ($totalValue == 0) {
            return [];
        }
        
        // Aggregate by sector
        $sectorTotals = [];
        foreach ($portfolioData as $holding) {
            $sector = $holding['sector'] ?? 'Unknown';
            $value = $holding['value'] ?? 0;
            
            if (!isset($sectorTotals[$sector])) {
                $sectorTotals[$sector] = 0;
            }
            $sectorTotals[$sector] += $value;
        }
        
        // Convert to percentages
        $allocation = [];
        foreach ($sectorTotals as $sector => $value) {
            $allocation[$sector] = round(($value / $totalValue) * 100, 2);
        }
        
        return $allocation;
    }
    
    /**
     * Compare portfolio allocation to benchmark weights
     * 
     * @param array<string, float> $portfolioAllocation Portfolio sector percentages
     * @param array<string, float> $benchmarkWeights Benchmark sector weights
     * @return array{
     *   sectors: array,
     *   overweight: string[],
     *   underweight: string[]
     * }
     */
    public function compareToBenchmark(array $portfolioAllocation, array $benchmarkWeights): array
    {
        $sectors = [];
        $overweight = [];
        $underweight = [];
        
        // Get all unique sectors
        $allSectors = array_unique(array_merge(
            array_keys($portfolioAllocation),
            array_keys($benchmarkWeights)
        ));
        
        foreach ($allSectors as $sector) {
            $portfolioWeight = $portfolioAllocation[$sector] ?? 0;
            $benchmarkWeight = $benchmarkWeights[$sector] ?? 0;
            $difference = $portfolioWeight - $benchmarkWeight;
            
            $sectors[$sector] = [
                'portfolio' => $portfolioWeight,
                'benchmark' => $benchmarkWeight,
                'difference' => round($difference, 2)
            ];
            
            if ($difference > 5) { // More than 5% overweight
                $overweight[] = $sector;
            } elseif ($difference < -5) { // More than 5% underweight
                $underweight[] = $sector;
            }
        }
        
        return [
            'sectors' => $sectors,
            'overweight' => $overweight,
            'underweight' => $underweight
        ];
    }
    
    /**
     * Calculate concentration risk metrics
     * 
     * @param array<string, float> $allocation Sector allocation percentages
     * @return array{
     *   herfindahl_index: float,
     *   top_sector_weight: float,
     *   risk_level: string
     * }
     */
    public function calculateConcentrationRisk(array $allocation): array
    {
        if (empty($allocation)) {
            return [
                'herfindahl_index' => 0,
                'top_sector_weight' => 0,
                'risk_level' => 'NONE'
            ];
        }
        
        // Calculate Herfindahl-Hirschman Index (HHI)
        $hhi = 0;
        foreach ($allocation as $weight) {
            $hhi += pow($weight, 2);
        }
        
        // Find top sector weight
        $topWeight = max($allocation);
        
        // Determine risk level
        $riskLevel = 'LOW';
        if ($topWeight > 40) {
            $riskLevel = 'HIGH';
        } elseif ($topWeight > 25) {
            $riskLevel = 'MEDIUM';
        }
        
        return [
            'herfindahl_index' => round($hhi, 2),
            'top_sector_weight' => round($topWeight, 2),
            'risk_level' => $riskLevel
        ];
    }
    
    /**
     * Format sector allocation data for pie chart
     * 
     * @param array<string, float> $allocation Sector percentages
     * @return array{labels: string[], datasets: array[]}
     */
    public function formatForPieChart(array $allocation): array
    {
        $labels = array_keys($allocation);
        $data = array_values($allocation);
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Portfolio Allocation',
                    'data' => $data,
                    'backgroundColor' => $this->generateColors(count($labels))
                ]
            ]
        ];
    }
    
    /**
     * Format data for comparison bar chart
     * 
     * @param array<string, float> $portfolioAllocation Portfolio percentages
     * @param array<string, float> $benchmarkWeights Benchmark percentages
     * @return array{labels: string[], datasets: array[]}
     */
    public function formatForComparisonChart(array $portfolioAllocation, array $benchmarkWeights): array
    {
        // Get all unique sector labels
        $labels = array_unique(array_merge(
            array_keys($portfolioAllocation),
            array_keys($benchmarkWeights)
        ));
        
        // Prepare data arrays
        $portfolioData = [];
        $benchmarkData = [];
        
        foreach ($labels as $sector) {
            $portfolioData[] = $portfolioAllocation[$sector] ?? 0;
            $benchmarkData[] = $benchmarkWeights[$sector] ?? 0;
        }
        
        return [
            'labels' => array_values($labels),
            'datasets' => [
                [
                    'label' => 'Portfolio',
                    'data' => $portfolioData,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1
                ],
                [
                    'label' => 'S&P 500',
                    'data' => $benchmarkData,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.6)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 1
                ]
            ]
        ];
    }
    
    /**
     * Calculate diversification score (0-100)
     * 
     * @param array<string, float> $allocation Sector percentages
     * @return float Score from 0 (poor) to 100 (excellent)
     */
    public function calculateDiversificationScore(array $allocation): float
    {
        if (empty($allocation)) {
            return 0;
        }
        
        $numSectors = count($allocation);
        $maxWeight = max($allocation);
        $hhi = 0;
        
        foreach ($allocation as $weight) {
            $hhi += pow($weight, 2);
        }
        
        // Score components:
        // 1. Number of sectors (more is better)
        $sectorScore = min(100, ($numSectors / 11) * 100); // 11 GICS sectors
        
        // 2. Max weight (lower is better)  
        $weightScore = max(0, 100 - ($maxWeight * 1.3)); // Penalty for concentration
        
        // 3. HHI (lower is better)
        $hhiScore = max(0, 100 - ($hhi / 22)); // Normalize HHI
        
        // Weighted average
        $totalScore = ($sectorScore * 0.35) + ($weightScore * 0.35) + ($hhiScore * 0.30);
        
        return round($totalScore, 2);
    }
    
    /**
     * Validate sector data structure
     * 
     * @param array $data Sector data to validate
     * @return bool True if valid
     */
    public function validateSectorData(array $data): bool
    {
        foreach ($data as $item) {
            if (!isset($item['sector']) || !isset($item['value'])) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Sanitize sector name to consistent format
     * 
     * @param string $sectorName Raw sector name
     * @return string Sanitized sector name
     */
    public function sanitizeSectorName(string $sectorName): string
    {
        // Trim whitespace
        $sanitized = trim($sectorName);
        
        // Handle abbreviations before title case
        $abbreviations = [
            'tech' => 'technology',
        ];
        
        $lowerSanitized = strtolower($sanitized);
        foreach ($abbreviations as $abbrev => $full) {
            if ($lowerSanitized === $abbrev) {
                $sanitized = $full;
                break;
            }
        }
        
        // Title case
        $sanitized = ucwords(strtolower($sanitized));
        
        return $sanitized;
    }
    
    /**
     * Get portfolio sector analysis for a user
     * 
     * @param int $userId User ID
     * @return array Analysis results
     * @throws \Exception If database error occurs
     */
    public function getPortfolioSectorAnalysis(int $userId): array
    {
        // This method calls DAO which might throw exceptions
        return $this->dao->getPortfolioSectorData($userId);
    }
    
    /**
     * Generate colors for chart
     * 
     * @param int $count Number of colors needed
     * @return string[] Array of color codes
     */
    private function generateColors(int $count): array
    {
        $baseColors = [
            'rgba(54, 162, 235, 0.6)',
            'rgba(255, 99, 132, 0.6)',
            'rgba(75, 192, 192, 0.6)',
            'rgba(255, 206, 86, 0.6)',
            'rgba(153, 102, 255, 0.6)',
            'rgba(255, 159, 64, 0.6)',
            'rgba(201, 203, 207, 0.6)',
            'rgba(255, 99, 71, 0.6)',
            'rgba(144, 238, 144, 0.6)',
            'rgba(173, 216, 230, 0.6)',
            'rgba(240, 128, 128, 0.6)'
        ];
        
        // Repeat colors if needed
        $colors = [];
        for ($i = 0; $i < $count; $i++) {
            $colors[] = $baseColors[$i % count($baseColors)];
        }
        
        return $colors;
    }
}
