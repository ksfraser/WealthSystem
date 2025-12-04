<?php

namespace App\Services;

use App\DAO\SectorAnalysisDAO;
use InvalidArgumentException;

/**
 * Advanced Chart Service
 * 
 * Provides advanced visualization capabilities including heatmaps,
 * treemaps, and historical trend charts for portfolio analysis.
 * 
 * @package App\Services
 */
class AdvancedChartService
{
    private SectorAnalysisDAO $dao;

    /**
     * Constructor
     * 
     * @param SectorAnalysisDAO $dao Sector analysis data access object
     */
    public function __construct(SectorAnalysisDAO $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Generate correlation heatmap for sectors
     * 
     * Creates a correlation matrix showing how different sectors
     * move together. Values range from -1 (inverse) to +1 (perfect correlation).
     * 
     * @param array $sectorReturns Associative array of sector => array of returns
     * @return array Heatmap data with labels, correlation matrix, and colors
     */
    public function generateCorrelationHeatmap(array $sectorReturns): array
    {
        $sectors = array_keys($sectorReturns);
        $n = count($sectors);
        $matrix = [];

        // Calculate correlation matrix
        for ($i = 0; $i < $n; $i++) {
            $matrix[$i] = [];
            for ($j = 0; $j < $n; $j++) {
                if ($i === $j) {
                    $matrix[$i][$j] = 1.0; // Perfect self-correlation
                } else {
                    $matrix[$i][$j] = $this->calculateCorrelation(
                        $sectorReturns[$sectors[$i]],
                        $sectorReturns[$sectors[$j]]
                    );
                }
            }
        }

        // Generate color gradient
        $colors = $this->generateColorGradient(-1.0, 1.0, 11);

        return [
            'labels' => $sectors,
            'data' => $matrix,
            'colors' => $colors
        ];
    }

    /**
     * Calculate correlation coefficient between two return series
     * 
     * Pearson correlation coefficient: ρ = Cov(X,Y) / (σₓ × σᵧ)
     * 
     * @param array $returns1 First return series
     * @param array $returns2 Second return series
     * @return float Correlation coefficient (-1 to +1)
     * @throws InvalidArgumentException If insufficient or mismatched data
     */
    public function calculateCorrelation(array $returns1, array $returns2): float
    {
        if (count($returns1) !== count($returns2)) {
            throw new InvalidArgumentException('Array lengths must match');
        }

        if (count($returns1) < 2) {
            throw new InvalidArgumentException('Insufficient data for correlation calculation');
        }

        $n = count($returns1);

        // Calculate means
        $mean1 = array_sum($returns1) / $n;
        $mean2 = array_sum($returns2) / $n;

        // Calculate covariance and standard deviations
        $covariance = 0;
        $variance1 = 0;
        $variance2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $diff1 = $returns1[$i] - $mean1;
            $diff2 = $returns2[$i] - $mean2;

            $covariance += $diff1 * $diff2;
            $variance1 += $diff1 * $diff1;
            $variance2 += $diff2 * $diff2;
        }

        $covariance /= $n;
        $stdDev1 = sqrt($variance1 / $n);
        $stdDev2 = sqrt($variance2 / $n);

        // Handle zero variance
        if ($stdDev1 == 0 || $stdDev2 == 0) {
            return 0.0;
        }

        $correlation = $covariance / ($stdDev1 * $stdDev2);

        // Clamp to valid range (floating point errors)
        return max(-1.0, min(1.0, round($correlation, 4)));
    }

    /**
     * Generate portfolio treemap showing composition by holding size
     * 
     * Creates hierarchical structure grouped by sector, with each
     * holding sized by value and colored by performance.
     * 
     * @param array $holdings Array of holdings with symbol, value, sector, return
     * @return array Treemap data structure for visualization
     */
    public function generatePortfolioTreemap(array $holdings): array
    {
        if (empty($holdings)) {
            return [
                'name' => 'Portfolio',
                'children' => []
            ];
        }

        // Group holdings by sector
        $sectorGroups = [];
        foreach ($holdings as $holding) {
            $sector = $holding['sector'];
            if (!isset($sectorGroups[$sector])) {
                $sectorGroups[$sector] = [];
            }
            $sectorGroups[$sector][] = $holding;
        }

        // Build treemap structure
        $children = [];
        foreach ($sectorGroups as $sector => $sectorHoldings) {
            $sectorNode = [
                'name' => $sector,
                'children' => []
            ];

            foreach ($sectorHoldings as $holding) {
                $color = $this->getPerformanceColor($holding['return']);
                
                $sectorNode['children'][] = [
                    'name' => $holding['symbol'],
                    'value' => $holding['value'],
                    'return' => $holding['return'],
                    'color' => $color
                ];
            }

            $children[] = $sectorNode;
        }

        return [
            'name' => 'Portfolio',
            'children' => $children
        ];
    }

    /**
     * Get color based on performance
     * 
     * @param float $returnPercent Return percentage
     * @return string RGBA color string
     */
    private function getPerformanceColor(float $returnPercent): string
    {
        if ($returnPercent > 20) {
            return 'rgba(0, 200, 0, 0.8)'; // Dark green
        } elseif ($returnPercent > 10) {
            return 'rgba(100, 200, 100, 0.8)'; // Green
        } elseif ($returnPercent > 0) {
            return 'rgba(150, 250, 150, 0.8)'; // Light green
        } elseif ($returnPercent > -10) {
            return 'rgba(250, 150, 150, 0.8)'; // Light red
        } elseif ($returnPercent > -20) {
            return 'rgba(200, 100, 100, 0.8)'; // Red
        } else {
            return 'rgba(255, 0, 0, 0.8)'; // Dark red
        }
    }

    /**
     * Generate historical sector weights trend chart
     * 
     * Shows how sector allocations have changed over time.
     * 
     * @param int $userId User identifier
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array Chart.js line chart data
     * @throws InvalidArgumentException If invalid date range
     */
    public function generateHistoricalSectorTrends(int $userId, string $startDate, string $endDate): array
    {
        if (strtotime($startDate) > strtotime($endDate)) {
            throw new InvalidArgumentException('Invalid date range: start date must be before end date');
        }

        $historicalData = $this->dao->getHistoricalSectorWeights($userId, $startDate, $endDate);

        if (empty($historicalData)) {
            return [
                'labels' => [],
                'datasets' => []
            ];
        }

        // Extract dates and sectors
        $dates = array_keys($historicalData);
        $sectors = array_keys($historicalData[$dates[0]]);

        // Build datasets (one per sector)
        $datasets = [];
        $colors = $this->getSectorColors();

        foreach ($sectors as $index => $sector) {
            $data = [];
            foreach ($dates as $date) {
                $data[] = $historicalData[$date][$sector] ?? 0;
            }

            $color = $colors[$index % count($colors)];

            $datasets[] = [
                'label' => $sector,
                'data' => $data,
                'borderColor' => $color,
                'backgroundColor' => str_replace('1)', '0.2)', $color),
                'fill' => false,
                'tension' => 0.4
            ];
        }

        return [
            'labels' => $dates,
            'datasets' => $datasets
        ];
    }

    /**
     * Calculate sector concentration trend over time
     * 
     * Tracks HHI (Herfindahl-Hirschman Index) over time to show
     * whether portfolio is becoming more or less concentrated.
     * 
     * @param int $userId User identifier
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array Date => HHI mapping
     */
    public function calculateSectorConcentrationTrend(int $userId, string $startDate, string $endDate): array
    {
        $historicalData = $this->dao->getHistoricalSectorWeights($userId, $startDate, $endDate);

        $concentrationTrend = [];

        foreach ($historicalData as $date => $sectorWeights) {
            $hhi = 0;
            foreach ($sectorWeights as $weight) {
                $hhi += pow($weight, 2);
            }
            $concentrationTrend[$date] = round($hhi, 2);
        }

        return $concentrationTrend;
    }

    /**
     * Generate rebalancing suggestions
     * 
     * Compares current allocation to target and suggests trades.
     * 
     * @param array $currentAllocation Current sector percentages
     * @param array $targetAllocation Target sector percentages
     * @return array Rebalancing suggestions with overweight/underweight sectors
     */
    public function generateRebalancingSuggestions(array $currentAllocation, array $targetAllocation): array
    {
        $overweight = [];
        $underweight = [];
        $threshold = 2.0; // 2% deviation threshold

        $allSectors = array_unique(array_merge(
            array_keys($currentAllocation),
            array_keys($targetAllocation)
        ));

        foreach ($allSectors as $sector) {
            $current = $currentAllocation[$sector] ?? 0;
            $target = $targetAllocation[$sector] ?? 0;
            $difference = $current - $target;

            if (abs($difference) > $threshold) {
                if ($difference > 0) {
                    $overweight[] = [
                        'sector' => $sector,
                        'current' => round($current, 2),
                        'target' => round($target, 2),
                        'difference' => round($difference, 2)
                    ];
                } else {
                    $underweight[] = [
                        'sector' => $sector,
                        'current' => round($current, 2),
                        'target' => round($target, 2),
                        'difference' => round($difference, 2)
                    ];
                }
            }
        }

        return [
            'overweight' => $overweight,
            'underweight' => $underweight,
            'rebalancing_required' => !empty($overweight) || !empty($underweight)
        ];
    }

    /**
     * Generate color gradient
     * 
     * Creates color gradient from red (negative) through white (neutral) to green (positive).
     * 
     * @param float $min Minimum value
     * @param float $max Maximum value
     * @param int $steps Number of color steps
     * @return array Array of RGB color strings
     */
    public function generateColorGradient(float $min, float $max, int $steps): array
    {
        $colors = [];
        $range = $max - $min;

        for ($i = 0; $i < $steps; $i++) {
            $value = $min + ($range * $i / ($steps - 1));

            if ($value < 0) {
                // Red to white gradient
                $intensity = (int)(255 * (1 + $value));
                $colors[] = "rgb(255, {$intensity}, {$intensity})";
            } elseif ($value > 0) {
                // White to green gradient
                $intensity = (int)(255 * (1 - $value));
                $colors[] = "rgb({$intensity}, 255, {$intensity})";
            } else {
                // Neutral (white)
                $colors[] = "rgb(255, 255, 255)";
            }
        }

        return $colors;
    }

    /**
     * Format correlation matrix for Chart.js heatmap
     * 
     * @param array $matrix 2D correlation matrix
     * @param array $labels Sector labels
     * @return array Chart.js heatmap format
     */
    public function formatForChartJsHeatmap(array $matrix, array $labels): array
    {
        $data = [];
        $n = count($matrix);

        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                $data[] = [
                    'x' => $x,
                    'y' => $y,
                    'v' => $matrix[$y][$x]
                ];
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Correlation',
                    'data' => $data,
                    'backgroundColor' => function($context) {
                        $value = $context['raw']['v'];
                        return $this->getCorrelationColor($value);
                    },
                    'borderWidth' => 1,
                    'borderColor' => 'rgba(0, 0, 0, 0.1)',
                    'width' => function($context) {
                        return $context['chart']['chartArea']['width'] / count($labels);
                    },
                    'height' => function($context) {
                        return $context['chart']['chartArea']['height'] / count($labels);
                    }
                ]
            ],
            'xLabels' => $labels,
            'yLabels' => $labels
        ];
    }

    /**
     * Get color for correlation value
     * 
     * @param float $correlation Correlation value (-1 to +1)
     * @return string RGB color string
     */
    private function getCorrelationColor(float $correlation): string
    {
        if ($correlation > 0.7) {
            return 'rgba(0, 150, 0, 0.8)'; // Dark green
        } elseif ($correlation > 0.3) {
            return 'rgba(100, 200, 100, 0.6)'; // Light green
        } elseif ($correlation > -0.3) {
            return 'rgba(200, 200, 200, 0.4)'; // Gray
        } elseif ($correlation > -0.7) {
            return 'rgba(200, 100, 100, 0.6)'; // Light red
        } else {
            return 'rgba(150, 0, 0, 0.8)'; // Dark red
        }
    }

    /**
     * Get predefined sector colors
     * 
     * @return array Array of RGBA color strings
     */
    private function getSectorColors(): array
    {
        return [
            'rgba(54, 162, 235, 1)',   // Blue
            'rgba(255, 99, 132, 1)',   // Red
            'rgba(255, 206, 86, 1)',   // Yellow
            'rgba(75, 192, 192, 1)',   // Teal
            'rgba(153, 102, 255, 1)',  // Purple
            'rgba(255, 159, 64, 1)',   // Orange
            'rgba(199, 199, 199, 1)',  // Gray
            'rgba(83, 102, 255, 1)',   // Indigo
            'rgba(255, 99, 255, 1)',   // Pink
            'rgba(0, 200, 100, 1)',    // Green
            'rgba(200, 100, 0, 1)'     // Brown
        ];
    }
}
