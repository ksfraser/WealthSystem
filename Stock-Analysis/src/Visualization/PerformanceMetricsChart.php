<?php

declare(strict_types=1);

namespace WealthSystem\Visualization;

use InvalidArgumentException;

/**
 * Performance metrics comparison chart
 * 
 * Generates various chart types for performance metric comparison:
 * - Bar charts for Sharpe ratio, Sortino ratio, returns, etc.
 * - Grouped bar charts for multi-strategy comparison
 * - Radar charts for multi-metric visualization
 * - Heatmaps for correlation matrices
 * - Scatter plots for risk/return analysis
 */
class PerformanceMetricsChart extends ChartGenerator
{
    /**
     * Chart type
     */
    private string $chartType;

    /**
     * Available chart types
     */
    private const CHART_TYPES = [
        'bar',           // Single or grouped bar chart
        'radar',         // Radar/spider chart for multi-metric
        'heatmap',       // Correlation heatmap
        'scatter',       // Risk/return scatter plot
    ];

    /**
     * Constructor
     * 
     * @param string $chartType Chart type ('bar', 'radar', 'heatmap', 'scatter')
     * @param int $width Chart width in pixels
     * @param int $height Chart height in pixels
     * @param string $title Chart title
     * @param string $colorScheme Color scheme name
     * @throws InvalidArgumentException If chart type is invalid
     */
    public function __construct(
        string $chartType = 'bar',
        int $width = 800,
        int $height = 600,
        string $title = 'Performance Metrics',
        string $colorScheme = 'default'
    ) {
        if (!in_array($chartType, self::CHART_TYPES)) {
            throw new InvalidArgumentException(
                "Invalid chart type '{$chartType}'. Must be one of: " . implode(', ', self::CHART_TYPES)
            );
        }
        
        parent::__construct($width, $height, $title, $colorScheme);
        $this->chartType = $chartType;
    }

    /**
     * Generate performance metrics chart
     * 
     * @param array<mixed> $data Chart data (structure depends on chart type)
     * @return string SVG markup
     */
    public function generate(array $data): string
    {
        $this->validateData($data);
        
        return match ($this->chartType) {
            'bar' => $this->generateBarChart($data),
            'radar' => $this->generateRadarChart($data),
            'heatmap' => $this->generateHeatmap($data),
            'scatter' => $this->generateScatterPlot($data),
        };
    }

    /**
     * Validate chart data
     * 
     * @param array<mixed> $data Chart data
     * @return void
     * @throws InvalidArgumentException If data is invalid
     */
    protected function validateData(array $data): void
    {
        match ($this->chartType) {
            'bar' => $this->validateBarData($data),
            'radar' => $this->validateRadarData($data),
            'heatmap' => $this->validateHeatmapData($data),
            'scatter' => $this->validateScatterData($data),
        };
    }

    /**
     * Validate bar chart data
     * 
     * @param array<mixed> $data Chart data
     * @return void
     * @throws InvalidArgumentException If data is invalid
     */
    private function validateBarData(array $data): void
    {
        if (!isset($data['labels']) || !is_array($data['labels'])) {
            throw new InvalidArgumentException('Bar chart data must contain "labels" array');
        }
        
        if (!isset($data['values']) || !is_array($data['values'])) {
            throw new InvalidArgumentException('Bar chart data must contain "values" array');
        }
        
        if (count($data['labels']) !== count($data['values'])) {
            throw new InvalidArgumentException('Labels and values must have same length');
        }
        
        if (isset($data['series']) && is_array($data['series'])) {
            foreach ($data['series'] as $name => $values) {
                if (count($values) !== count($data['labels'])) {
                    throw new InvalidArgumentException(
                        "Series '{$name}' must have same length as labels"
                    );
                }
            }
        }
    }

    /**
     * Validate radar chart data
     * 
     * @param array<mixed> $data Chart data
     * @return void
     * @throws InvalidArgumentException If data is invalid
     */
    private function validateRadarData(array $data): void
    {
        if (!isset($data['metrics']) || !is_array($data['metrics'])) {
            throw new InvalidArgumentException('Radar chart data must contain "metrics" array');
        }
        
        if (!isset($data['values']) || !is_array($data['values'])) {
            throw new InvalidArgumentException('Radar chart data must contain "values" array');
        }
        
        if (count($data['metrics']) !== count($data['values'])) {
            throw new InvalidArgumentException('Metrics and values must have same length');
        }
        
        if (count($data['metrics']) < 3) {
            throw new InvalidArgumentException('Radar chart requires at least 3 metrics');
        }
    }

    /**
     * Validate heatmap data
     * 
     * @param array<mixed> $data Chart data
     * @return void
     * @throws InvalidArgumentException If data is invalid
     */
    private function validateHeatmapData(array $data): void
    {
        if (!isset($data['labels']) || !is_array($data['labels'])) {
            throw new InvalidArgumentException('Heatmap data must contain "labels" array');
        }
        
        if (!isset($data['matrix']) || !is_array($data['matrix'])) {
            throw new InvalidArgumentException('Heatmap data must contain "matrix" array');
        }
        
        $size = count($data['labels']);
        if (count($data['matrix']) !== $size) {
            throw new InvalidArgumentException('Matrix must be square (same number of rows as labels)');
        }
        
        foreach ($data['matrix'] as $row) {
            if (!is_array($row) || count($row) !== $size) {
                throw new InvalidArgumentException('Matrix must be square (all rows same length as labels)');
            }
        }
    }

    /**
     * Validate scatter plot data
     * 
     * @param array<mixed> $data Chart data
     * @return void
     * @throws InvalidArgumentException If data is invalid
     */
    private function validateScatterData(array $data): void
    {
        if (!isset($data['points']) || !is_array($data['points'])) {
            throw new InvalidArgumentException('Scatter plot data must contain "points" array');
        }
        
        if (empty($data['points'])) {
            throw new InvalidArgumentException('Scatter plot requires at least one point');
        }
        
        foreach ($data['points'] as $point) {
            if (!isset($point['x']) || !isset($point['y'])) {
                throw new InvalidArgumentException('Each point must have "x" and "y" values');
            }
        }
    }

    /**
     * Generate bar chart
     * 
     * Data structure:
     *   [
     *     'labels' => ['Strategy A', 'Strategy B', ...],
     *     'values' => [1.5, 1.8, ...],  // Single series
     *     'series' => [  // Optional, for grouped bars
     *       'Sharpe' => [1.5, 1.8, ...],
     *       'Sortino' => [2.0, 2.2, ...],
     *     ]
     *   ]
     * 
     * @param array<mixed> $data Chart data
     * @return string SVG markup
     */
    private function generateBarChart(array $data): string
    {
        $svg = $this->createSvgHeader();
        $svg .= $this->createBackground();
        $svg .= $this->createTitle();
        
        $labels = $data['labels'];
        $hasSeries = isset($data['series']) && is_array($data['series']);
        
        if ($hasSeries) {
            // Grouped bar chart
            $series = $data['series'];
            $svg .= $this->drawGroupedBars($labels, $series);
            
            // Create legend
            $legend = [];
            $colorIndex = 0;
            $seriesColors = [$this->colors['primary'], $this->colors['secondary'], 
                           $this->colors['accent'], $this->colors['info']];
            foreach (array_keys($series) as $name) {
                $legend[$name] = $seriesColors[$colorIndex % count($seriesColors)];
                $colorIndex++;
            }
            $svg .= $this->createLegend($legend);
        } else {
            // Single bar chart
            $values = $data['values'];
            $svg .= $this->drawSingleBars($labels, $values);
        }
        
        $svg .= $this->createSvgFooter();
        
        return $svg;
    }

    /**
     * Draw single series bar chart
     * 
     * @param array<string> $labels X-axis labels
     * @param array<float> $values Bar values
     * @return string SVG markup
     */
    private function drawSingleBars(array $labels, array $values): string
    {
        $svg = '<g class="bar-chart">';
        
        // Calculate scales
        $minValue = min(0, min($values));
        $maxValue = max($values);
        $range = $maxValue - $minValue;
        $minValue -= $range * 0.1;
        $maxValue += $range * 0.1;
        
        // Create grid and axes
        $svg .= $this->createGrid(10, count($labels));
        $yLabels = $this->createValueLabels($minValue, $maxValue, 10);
        $svg .= $this->createAxisLabels($labels, $yLabels, '', '');
        
        // Draw bars
        $chartWidth = $this->getChartWidth();
        $chartHeight = $this->getChartHeight();
        $x0 = $this->padding['left'];
        $y0 = $this->padding['top'];
        
        $barWidth = $chartWidth / count($labels) * 0.7;
        $barSpacing = $chartWidth / count($labels);
        
        foreach ($values as $idx => $value) {
            $barHeight = abs($value - $minValue) / ($maxValue - $minValue) * $chartHeight;
            $x = $x0 + ($idx * $barSpacing) + ($barSpacing - $barWidth) / 2;
            $y = $y0 + $chartHeight - $barHeight;
            
            $color = $value >= 0 ? $this->colors['success'] : $this->colors['danger'];
            
            $svg .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" fill="%s"/>',
                $x, $y, $barWidth, $barHeight, $color
            );
            
            // Value label
            $labelY = $value >= 0 ? $y - 5 : $y + $barHeight + 15;
            $svg .= sprintf(
                '<text x="%d" y="%d" font-family="%s" font-size="%d" fill="%s" text-anchor="middle">%.2f</text>',
                $x + $barWidth / 2, $labelY, $this->fontFamily, $this->fontSize - 2, 
                $this->colors['text'], $value
            );
        }
        
        $svg .= '</g>';
        
        return $svg;
    }

    /**
     * Draw grouped bar chart
     * 
     * @param array<string> $labels X-axis labels
     * @param array<string, array<float>> $series Series data
     * @return string SVG markup
     */
    private function drawGroupedBars(array $labels, array $series): string
    {
        $svg = '<g class="grouped-bar-chart">';
        
        // Calculate scales
        $allValues = [];
        foreach ($series as $values) {
            $allValues = array_merge($allValues, $values);
        }
        
        $minValue = min(0, min($allValues));
        $maxValue = max($allValues);
        $range = $maxValue - $minValue;
        $minValue -= $range * 0.1;
        $maxValue += $range * 0.1;
        
        // Create grid and axes
        $svg .= $this->createGrid(10, count($labels));
        $yLabels = $this->createValueLabels($minValue, $maxValue, 10);
        $svg .= $this->createAxisLabels($labels, $yLabels, '', '');
        
        // Draw bars
        $chartWidth = $this->getChartWidth();
        $chartHeight = $this->getChartHeight();
        $x0 = $this->padding['left'];
        $y0 = $this->padding['top'];
        
        $seriesCount = count($series);
        $groupWidth = $chartWidth / count($labels) * 0.8;
        $barWidth = $groupWidth / $seriesCount * 0.9;
        $groupSpacing = $chartWidth / count($labels);
        
        $seriesColors = [$this->colors['primary'], $this->colors['secondary'], 
                        $this->colors['accent'], $this->colors['info']];
        $colorIndex = 0;
        
        foreach ($series as $name => $values) {
            $color = $seriesColors[$colorIndex % count($seriesColors)];
            
            foreach ($values as $idx => $value) {
                $barHeight = abs($value - $minValue) / ($maxValue - $minValue) * $chartHeight;
                $x = $x0 + ($idx * $groupSpacing) + ($groupSpacing - $groupWidth) / 2 + 
                     ($colorIndex * $barWidth);
                $y = $y0 + $chartHeight - $barHeight;
                
                $svg .= sprintf(
                    '<rect x="%d" y="%d" width="%d" height="%d" fill="%s"/>',
                    $x, $y, $barWidth, $barHeight, $color
                );
            }
            
            $colorIndex++;
        }
        
        $svg .= '</g>';
        
        return $svg;
    }

    /**
     * Generate radar chart
     * 
     * Data structure:
     *   [
     *     'metrics' => ['Sharpe', 'Sortino', 'Win Rate', 'Profit Factor', ...],
     *     'values' => [1.5, 2.0, 0.65, 1.8, ...],  // Normalized 0-1
     *     'series' => [  // Optional, for comparison
     *       'Strategy A' => [1.5, 2.0, 0.65, ...],
     *       'Strategy B' => [1.2, 1.8, 0.70, ...],
     *     ]
     *   ]
     * 
     * @param array<mixed> $data Chart data
     * @return string SVG markup
     */
    private function generateRadarChart(array $data): string
    {
        $svg = $this->createSvgHeader();
        $svg .= $this->createBackground();
        $svg .= $this->createTitle();
        
        $metrics = $data['metrics'];
        $hasSeries = isset($data['series']) && is_array($data['series']);
        
        // Calculate center and radius
        $centerX = $this->width / 2;
        $centerY = $this->height / 2;
        $radius = min($this->getChartWidth(), $this->getChartHeight()) / 2 - 50;
        
        // Draw radar grid
        $svg .= $this->drawRadarGrid($metrics, $centerX, $centerY, $radius);
        
        if ($hasSeries) {
            // Multiple series
            $series = $data['series'];
            $colorIndex = 0;
            $seriesColors = [$this->colors['primary'], $this->colors['secondary'], 
                           $this->colors['accent'], $this->colors['info']];
            
            foreach ($series as $name => $values) {
                $color = $seriesColors[$colorIndex % count($seriesColors)];
                $svg .= $this->drawRadarPolygon($values, $centerX, $centerY, $radius, 
                                               count($metrics), $color);
                $colorIndex++;
            }
            
            // Create legend
            $legend = [];
            $colorIndex = 0;
            foreach (array_keys($series) as $name) {
                $legend[$name] = $seriesColors[$colorIndex % count($seriesColors)];
                $colorIndex++;
            }
            $svg .= $this->createLegend($legend);
        } else {
            // Single series
            $values = $data['values'];
            $svg .= $this->drawRadarPolygon($values, $centerX, $centerY, $radius, 
                                           count($metrics), $this->colors['primary']);
        }
        
        $svg .= $this->createSvgFooter();
        
        return $svg;
    }

    /**
     * Draw radar chart grid
     * 
     * @param array<string> $metrics Metric labels
     * @param int $centerX Center X coordinate
     * @param int $centerY Center Y coordinate
     * @param int $radius Chart radius
     * @return string SVG markup
     */
    private function drawRadarGrid(array $metrics, int $centerX, int $centerY, int $radius): string
    {
        $svg = '<g class="radar-grid">';
        
        $metricCount = count($metrics);
        $angleStep = 2 * M_PI / $metricCount;
        
        // Draw concentric circles (5 levels)
        for ($level = 1; $level <= 5; $level++) {
            $r = $radius * $level / 5;
            $svg .= sprintf(
                '<circle cx="%d" cy="%d" r="%d" fill="none" stroke="%s" stroke-width="1" opacity="0.3"/>',
                $centerX, $centerY, $r, $this->colors['grid']
            );
        }
        
        // Draw axis lines and labels
        for ($i = 0; $i < $metricCount; $i++) {
            $angle = $angleStep * $i - M_PI / 2;
            $x = $centerX + $radius * cos($angle);
            $y = $centerY + $radius * sin($angle);
            
            // Axis line
            $svg .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="1" opacity="0.3"/>',
                $centerX, $centerY, $x, $y, $this->colors['grid']
            );
            
            // Label
            $labelX = $centerX + ($radius + 30) * cos($angle);
            $labelY = $centerY + ($radius + 30) * sin($angle);
            $svg .= sprintf(
                '<text x="%d" y="%d" font-family="%s" font-size="%d" fill="%s" text-anchor="middle">%s</text>',
                $labelX, $labelY, $this->fontFamily, $this->fontSize - 1, 
                $this->colors['text'], htmlspecialchars($metrics[$i])
            );
        }
        
        $svg .= '</g>';
        
        return $svg;
    }

    /**
     * Draw radar polygon
     * 
     * @param array<float> $values Metric values (0-1 normalized)
     * @param int $centerX Center X coordinate
     * @param int $centerY Center Y coordinate
     * @param int $radius Chart radius
     * @param int $metricCount Number of metrics
     * @param string $color Polygon color
     * @return string SVG markup
     */
    private function drawRadarPolygon(
        array $values,
        int $centerX,
        int $centerY,
        int $radius,
        int $metricCount,
        string $color
    ): string {
        $angleStep = 2 * M_PI / $metricCount;
        $points = [];
        
        for ($i = 0; $i < $metricCount; $i++) {
            $angle = $angleStep * $i - M_PI / 2;
            $value = max(0, min(1, $values[$i])); // Clamp to 0-1
            $r = $radius * $value;
            $x = $centerX + $r * cos($angle);
            $y = $centerY + $r * sin($angle);
            $points[] = "{$x},{$y}";
        }
        
        return sprintf(
            '<polygon points="%s" fill="%s" fill-opacity="0.3" stroke="%s" stroke-width="2"/>',
            implode(' ', $points), $color, $color
        );
    }

    /**
     * Generate correlation heatmap
     * 
     * Data structure:
     *   [
     *     'labels' => ['AAPL', 'MSFT', 'GOOGL', ...],
     *     'matrix' => [
     *       [1.0, 0.8, 0.6, ...],
     *       [0.8, 1.0, 0.7, ...],
     *       [0.6, 0.7, 1.0, ...],
     *       ...
     *     ]
     *   ]
     * 
     * @param array<mixed> $data Chart data
     * @return string SVG markup
     */
    private function generateHeatmap(array $data): string
    {
        $svg = $this->createSvgHeader();
        $svg .= $this->createBackground();
        $svg .= $this->createTitle();
        
        $labels = $data['labels'];
        $matrix = $data['matrix'];
        
        $svg .= $this->drawHeatmapCells($labels, $matrix);
        
        $svg .= $this->createSvgFooter();
        
        return $svg;
    }

    /**
     * Draw heatmap cells
     * 
     * @param array<string> $labels Row/column labels
     * @param array<array<float>> $matrix Correlation matrix
     * @return string SVG markup
     */
    private function drawHeatmapCells(array $labels, array $matrix): string
    {
        $svg = '<g class="heatmap">';
        
        $size = count($labels);
        $chartWidth = $this->getChartWidth();
        $chartHeight = $this->getChartHeight();
        $x0 = $this->padding['left'];
        $y0 = $this->padding['top'];
        
        $cellWidth = $chartWidth / $size;
        $cellHeight = $chartHeight / $size;
        
        // Draw cells
        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                $value = $matrix[$row][$col];
                $color = $this->getHeatmapColor($value);
                
                $x = $x0 + $col * $cellWidth;
                $y = $y0 + $row * $cellHeight;
                
                $svg .= sprintf(
                    '<rect x="%d" y="%d" width="%d" height="%d" fill="%s" stroke="white" stroke-width="1"/>',
                    $x, $y, $cellWidth, $cellHeight, $color
                );
                
                // Value label
                $svg .= sprintf(
                    '<text x="%d" y="%d" font-family="%s" font-size="%d" fill="white" text-anchor="middle">%.2f</text>',
                    $x + $cellWidth / 2, $y + $cellHeight / 2 + 5, 
                    $this->fontFamily, $this->fontSize - 2, $value
                );
            }
        }
        
        // Draw labels
        for ($i = 0; $i < $size; $i++) {
            // Row labels
            $svg .= sprintf(
                '<text x="%d" y="%d" font-family="%s" font-size="%d" fill="%s" text-anchor="end">%s</text>',
                $x0 - 10, $y0 + ($i + 0.5) * $cellHeight + 5, 
                $this->fontFamily, $this->fontSize - 1, 
                $this->colors['text'], htmlspecialchars($labels[$i])
            );
            
            // Column labels (rotated)
            $svg .= sprintf(
                '<text x="%d" y="%d" font-family="%s" font-size="%d" fill="%s" text-anchor="start" transform="rotate(-45 %d %d)">%s</text>',
                $x0 + ($i + 0.5) * $cellWidth, $y0 - 10,
                $this->fontFamily, $this->fontSize - 1, 
                $this->colors['text'],
                $x0 + ($i + 0.5) * $cellWidth, $y0 - 10,
                htmlspecialchars($labels[$i])
            );
        }
        
        $svg .= '</g>';
        
        return $svg;
    }

    /**
     * Get color for heatmap cell based on value
     * 
     * @param float $value Correlation value (-1 to 1)
     * @return string Hex color code
     */
    private function getHeatmapColor(float $value): string
    {
        // Blue (negative) to White (zero) to Red (positive)
        if ($value < 0) {
            $intensity = (int)(abs($value) * 255);
            return sprintf('#%02x%02x%02x', 255 - $intensity, 255 - $intensity, 255);
        } else {
            $intensity = (int)($value * 255);
            return sprintf('#%02x%02x%02x', 255, 255 - $intensity, 255 - $intensity);
        }
    }

    /**
     * Generate scatter plot
     * 
     * Data structure:
     *   [
     *     'points' => [
     *       ['x' => 0.15, 'y' => 1.5, 'label' => 'Strategy A'],
     *       ['x' => 0.20, 'y' => 1.2, 'label' => 'Strategy B'],
     *       ...
     *     ],
     *     'xLabel' => 'Risk (Volatility)',
     *     'yLabel' => 'Return (Sharpe Ratio)'
     *   ]
     * 
     * @param array<mixed> $data Chart data
     * @return string SVG markup
     */
    private function generateScatterPlot(array $data): string
    {
        $svg = $this->createSvgHeader();
        $svg .= $this->createBackground();
        $svg .= $this->createTitle();
        
        $points = $data['points'];
        $xLabel = $data['xLabel'] ?? 'X';
        $yLabel = $data['yLabel'] ?? 'Y';
        
        $svg .= $this->drawScatterPoints($points, $xLabel, $yLabel);
        
        $svg .= $this->createSvgFooter();
        
        return $svg;
    }

    /**
     * Draw scatter plot points
     * 
     * @param array<array<string, mixed>> $points Point data
     * @param string $xLabel X-axis label
     * @param string $yLabel Y-axis label
     * @return string SVG markup
     */
    private function drawScatterPoints(array $points, string $xLabel, string $yLabel): string
    {
        $svg = '<g class="scatter-plot">';
        
        // Find min/max for scales
        $xValues = array_column($points, 'x');
        $yValues = array_column($points, 'y');
        
        $minX = min($xValues);
        $maxX = max($xValues);
        $minY = min($yValues);
        $maxY = max($yValues);
        
        // Add padding
        $xRange = $maxX - $minX;
        $yRange = $maxY - $minY;
        $minX -= $xRange * 0.1;
        $maxX += $xRange * 0.1;
        $minY -= $yRange * 0.1;
        $maxY += $yRange * 0.1;
        
        // Create grid and axes
        $svg .= $this->createGrid(10, 10);
        $xLabels = $this->createNumericLabels($minX, $maxX, 10);
        $yLabels = $this->createNumericLabels($minY, $maxY, 10);
        $svg .= $this->createAxisLabels($xLabels, $yLabels, $xLabel, $yLabel);
        
        // Draw points
        $chartWidth = $this->getChartWidth();
        $chartHeight = $this->getChartHeight();
        $x0 = $this->padding['left'];
        $y0 = $this->padding['top'];
        
        foreach ($points as $point) {
            $x = $x0 + ($point['x'] - $minX) / ($maxX - $minX) * $chartWidth;
            $y = $y0 + $chartHeight - ($point['y'] - $minY) / ($maxY - $minY) * $chartHeight;
            
            // Draw circle
            $svg .= sprintf(
                '<circle cx="%d" cy="%d" r="5" fill="%s" stroke="white" stroke-width="1"/>',
                $x, $y, $this->colors['primary']
            );
            
            // Draw label if provided
            if (isset($point['label'])) {
                $svg .= sprintf(
                    '<text x="%d" y="%d" font-family="%s" font-size="%d" fill="%s">%s</text>',
                    $x + 8, $y - 8, 
                    $this->fontFamily, $this->fontSize - 2, 
                    $this->colors['text'], htmlspecialchars($point['label'])
                );
            }
        }
        
        $svg .= '</g>';
        
        return $svg;
    }

    /**
     * Create numeric labels for axis
     * 
     * @param float $min Minimum value
     * @param float $max Maximum value
     * @param int $count Number of labels
     * @return array<string> Formatted labels
     */
    private function createNumericLabels(float $min, float $max, int $count): array
    {
        $labels = [];
        $step = ($max - $min) / ($count - 1);
        
        for ($i = 0; $i < $count; $i++) {
            $value = $min + ($step * $i);
            $labels[] = $this->formatNumber($value, 2);
        }
        
        return $labels;
    }

    /**
     * Create value labels for Y-axis
     * 
     * @param float $minValue Minimum value
     * @param float $maxValue Maximum value
     * @param int $count Number of labels
     * @return array<string> Formatted value labels
     */
    private function createValueLabels(float $minValue, float $maxValue, int $count): array
    {
        $labels = [];
        $step = ($maxValue - $minValue) / ($count - 1);
        
        for ($i = 0; $i < $count; $i++) {
            $value = $minValue + ($step * $i);
            $labels[] = $this->formatNumber($value, 2);
        }
        
        return array_reverse($labels);
    }

    /**
     * Get available chart types
     * 
     * @return array<string> Chart type names
     */
    public static function getAvailableChartTypes(): array
    {
        return self::CHART_TYPES;
    }
}
