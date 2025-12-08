<?php

declare(strict_types=1);

namespace WealthSystem\Visualization;

use InvalidArgumentException;

/**
 * Trade distribution analysis chart
 * 
 * Generates various chart types for trade distribution analysis:
 * - Histogram of returns distribution
 * - Win/loss distribution comparison
 * - Profit factor by time period
 * - Trade duration analysis
 * - Position size distribution
 */
class TradeDistributionChart extends ChartGenerator
{
    /**
     * Chart type
     */
    private string $chartType;

    /**
     * Number of histogram bins
     */
    private int $bins;

    /**
     * Available chart types
     */
    private const CHART_TYPES = [
        'returns_histogram',     // Distribution of trade returns
        'win_loss',             // Win/loss comparison
        'profit_factor',        // Profit factor over time
        'duration',             // Trade duration distribution
        'position_size',        // Position size distribution
    ];

    /**
     * Constructor
     * 
     * @param string $chartType Chart type
     * @param int $width Chart width in pixels
     * @param int $height Chart height in pixels
     * @param string $title Chart title
     * @param string $colorScheme Color scheme name
     * @param int $bins Number of histogram bins
     * @throws InvalidArgumentException If chart type is invalid
     */
    public function __construct(
        string $chartType = 'returns_histogram',
        int $width = 800,
        int $height = 600,
        string $title = 'Trade Distribution',
        string $colorScheme = 'default',
        int $bins = 20
    ) {
        if (!in_array($chartType, self::CHART_TYPES)) {
            throw new InvalidArgumentException(
                "Invalid chart type '{$chartType}'. Must be one of: " . implode(', ', self::CHART_TYPES)
            );
        }
        
        parent::__construct($width, $height, $title, $colorScheme);
        $this->chartType = $chartType;
        $this->bins = $bins;
    }

    /**
     * Generate trade distribution chart
     * 
     * @param array<mixed> $data Chart data (structure depends on chart type)
     * @return string SVG markup
     */
    public function generate(array $data): string
    {
        $this->validateData($data);
        
        return match ($this->chartType) {
            'returns_histogram' => $this->generateReturnsHistogram($data),
            'win_loss' => $this->generateWinLossChart($data),
            'profit_factor' => $this->generateProfitFactorChart($data),
            'duration' => $this->generateDurationChart($data),
            'position_size' => $this->generatePositionSizeChart($data),
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
            'returns_histogram' => $this->validateReturnsData($data),
            'win_loss' => $this->validateWinLossData($data),
            'profit_factor' => $this->validateProfitFactorData($data),
            'duration' => $this->validateDurationData($data),
            'position_size' => $this->validatePositionSizeData($data),
        };
    }

    /**
     * Validate returns histogram data
     * 
     * @param array<mixed> $data Chart data
     * @return void
     * @throws InvalidArgumentException If data is invalid
     */
    private function validateReturnsData(array $data): void
    {
        if (!isset($data['returns']) || !is_array($data['returns'])) {
            throw new InvalidArgumentException('Returns histogram data must contain "returns" array');
        }
        
        if (empty($data['returns'])) {
            throw new InvalidArgumentException('Returns array cannot be empty');
        }
    }

    /**
     * Validate win/loss data
     * 
     * @param array<mixed> $data Chart data
     * @return void
     * @throws InvalidArgumentException If data is invalid
     */
    private function validateWinLossData(array $data): void
    {
        if (!isset($data['wins']) || !is_array($data['wins'])) {
            throw new InvalidArgumentException('Win/loss data must contain "wins" array');
        }
        
        if (!isset($data['losses']) || !is_array($data['losses'])) {
            throw new InvalidArgumentException('Win/loss data must contain "losses" array');
        }
    }

    /**
     * Validate profit factor data
     * 
     * @param array<mixed> $data Chart data
     * @return void
     * @throws InvalidArgumentException If data is invalid
     */
    private function validateProfitFactorData(array $data): void
    {
        if (!isset($data['dates']) || !is_array($data['dates'])) {
            throw new InvalidArgumentException('Profit factor data must contain "dates" array');
        }
        
        if (!isset($data['values']) || !is_array($data['values'])) {
            throw new InvalidArgumentException('Profit factor data must contain "values" array');
        }
        
        if (count($data['dates']) !== count($data['values'])) {
            throw new InvalidArgumentException('Dates and values must have same length');
        }
    }

    /**
     * Validate duration data
     * 
     * @param array<mixed> $data Chart data
     * @return void
     * @throws InvalidArgumentException If data is invalid
     */
    private function validateDurationData(array $data): void
    {
        if (!isset($data['durations']) || !is_array($data['durations'])) {
            throw new InvalidArgumentException('Duration data must contain "durations" array');
        }
        
        if (empty($data['durations'])) {
            throw new InvalidArgumentException('Durations array cannot be empty');
        }
    }

    /**
     * Validate position size data
     * 
     * @param array<mixed> $data Chart data
     * @return void
     * @throws InvalidArgumentException If data is invalid
     */
    private function validatePositionSizeData(array $data): void
    {
        if (!isset($data['sizes']) || !is_array($data['sizes'])) {
            throw new InvalidArgumentException('Position size data must contain "sizes" array');
        }
        
        if (empty($data['sizes'])) {
            throw new InvalidArgumentException('Sizes array cannot be empty');
        }
    }

    /**
     * Generate returns histogram
     * 
     * Data structure:
     *   [
     *     'returns' => [0.05, -0.02, 0.10, -0.03, ...]  // Trade returns as decimals
     *   ]
     * 
     * @param array<mixed> $data Chart data
     * @return string SVG markup
     */
    private function generateReturnsHistogram(array $data): string
    {
        $returns = $data['returns'];
        
        // Create histogram bins
        $minReturn = min($returns);
        $maxReturn = max($returns);
        $binWidth = ($maxReturn - $minReturn) / $this->bins;
        
        $histogram = array_fill(0, $this->bins, 0);
        
        foreach ($returns as $return) {
            $binIndex = min($this->bins - 1, (int)(($return - $minReturn) / $binWidth));
            $histogram[$binIndex]++;
        }
        
        // Build SVG
        $svg = $this->createSvgHeader();
        $svg .= $this->createBackground();
        $svg .= $this->createTitle();
        
        // Create labels
        $binLabels = [];
        for ($i = 0; $i < $this->bins; $i++) {
            $binStart = $minReturn + ($i * $binWidth);
            $binLabels[] = $this->formatNumber($binStart * 100, 1) . '%';
        }
        
        $svg .= $this->drawHistogram($binLabels, $histogram);
        
        // Add statistics overlay
        $mean = array_sum($returns) / count($returns);
        $svg .= $this->drawStatisticsOverlay($mean, $minReturn, $maxReturn);
        
        $svg .= $this->createSvgFooter();
        
        return $svg;
    }

    /**
     * Draw histogram bars
     * 
     * @param array<string> $labels Bin labels
     * @param array<int> $counts Bin counts
     * @return string SVG markup
     */
    private function drawHistogram(array $labels, array $counts): string
    {
        $svg = '<g class="histogram">';
        
        $maxCount = max($counts);
        
        // Create grid
        $svg .= $this->createGrid(10, count($labels));
        
        // Create axis labels (sample labels for readability)
        $sampledLabels = [];
        $sampleStep = max(1, (int)(count($labels) / 8));
        for ($i = 0; $i < count($labels); $i += $sampleStep) {
            $sampledLabels[] = $labels[$i];
        }
        
        $yLabels = [];
        for ($i = 0; $i <= 10; $i++) {
            $yLabels[] = (string)(int)($maxCount * $i / 10);
        }
        $yLabels = array_reverse($yLabels);
        
        $svg .= $this->createAxisLabels($sampledLabels, $yLabels, 'Return', 'Frequency');
        
        // Draw bars
        $chartWidth = $this->getChartWidth();
        $chartHeight = $this->getChartHeight();
        $x0 = $this->padding['left'];
        $y0 = $this->padding['top'];
        
        $barWidth = $chartWidth / count($counts);
        
        foreach ($counts as $idx => $count) {
            if ($count == 0) {
                continue;
            }
            
            $barHeight = ($count / $maxCount) * $chartHeight;
            $x = $x0 + ($idx * $barWidth);
            $y = $y0 + $chartHeight - $barHeight;
            
            // Color based on return sign (determined by bin position)
            $binMidpoint = (float)$labels[$idx];
            $color = $binMidpoint >= 0 ? $this->colors['success'] : $this->colors['danger'];
            
            $svg .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" fill="%s" stroke="white" stroke-width="1"/>',
                $x, $y, $barWidth - 1, $barHeight, $color
            );
        }
        
        $svg .= '</g>';
        
        return $svg;
    }

    /**
     * Draw statistics overlay (mean, median lines)
     * 
     * @param float $mean Mean return
     * @param float $min Min return
     * @param float $max Max return
     * @return string SVG markup
     */
    private function drawStatisticsOverlay(float $mean, float $min, float $max): string
    {
        $svg = '<g class="statistics-overlay">';
        
        $chartWidth = $this->getChartWidth();
        $chartHeight = $this->getChartHeight();
        $x0 = $this->padding['left'];
        $y0 = $this->padding['top'];
        
        // Mean line
        $meanX = $x0 + (($mean - $min) / ($max - $min)) * $chartWidth;
        
        $svg .= sprintf(
            '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="2" stroke-dasharray="5,5"/>',
            $meanX, $y0, $meanX, $y0 + $chartHeight, $this->colors['info']
        );
        
        // Mean label
        $svg .= sprintf(
            '<text x="%d" y="%d" font-family="%s" font-size="%d" fill="%s" text-anchor="middle">Mean: %.2f%%</text>',
            $meanX, $y0 + 20, $this->fontFamily, $this->fontSize - 1, 
            $this->colors['info'], $mean * 100
        );
        
        $svg .= '</g>';
        
        return $svg;
    }

    /**
     * Generate win/loss comparison chart
     * 
     * Data structure:
     *   [
     *     'wins' => [0.05, 0.10, 0.03, ...],    // Winning trade returns
     *     'losses' => [-0.02, -0.05, ...]        // Losing trade returns
     *   ]
     * 
     * @param array<mixed> $data Chart data
     * @return string SVG markup
     */
    private function generateWinLossChart(array $data): string
    {
        $wins = $data['wins'];
        $losses = $data['losses'];
        
        $avgWin = !empty($wins) ? array_sum($wins) / count($wins) : 0;
        $avgLoss = !empty($losses) ? array_sum($losses) / count($losses) : 0;
        $winRate = count($wins) / (count($wins) + count($losses)) * 100;
        
        // Build SVG
        $svg = $this->createSvgHeader();
        $svg .= $this->createBackground();
        $svg .= $this->createTitle();
        
        // Create bar chart with win/loss stats
        $labels = ['Win Count', 'Loss Count', 'Avg Win', 'Avg Loss', 'Win Rate'];
        $values = [count($wins), count($losses), $avgWin * 100, $avgLoss * 100, $winRate];
        $colors = [
            $this->colors['success'],
            $this->colors['danger'],
            $this->colors['success'],
            $this->colors['danger'],
            $this->colors['info']
        ];
        
        $svg .= $this->drawColoredBars($labels, $values, $colors);
        
        $svg .= $this->createSvgFooter();
        
        return $svg;
    }

    /**
     * Draw bars with custom colors
     * 
     * @param array<string> $labels Bar labels
     * @param array<float> $values Bar values
     * @param array<string> $colors Bar colors
     * @return string SVG markup
     */
    private function drawColoredBars(array $labels, array $values, array $colors): string
    {
        $svg = '<g class="colored-bars">';
        
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
        
        $barWidth = $chartWidth / count($labels) * 0.6;
        $barSpacing = $chartWidth / count($labels);
        
        foreach ($values as $idx => $value) {
            $barHeight = abs($value - $minValue) / ($maxValue - $minValue) * $chartHeight;
            $x = $x0 + ($idx * $barSpacing) + ($barSpacing - $barWidth) / 2;
            $y = $y0 + $chartHeight - $barHeight;
            
            $svg .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" fill="%s"/>',
                $x, $y, $barWidth, $barHeight, $colors[$idx]
            );
            
            // Value label
            $labelY = $y - 5;
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
     * Generate profit factor over time chart
     * 
     * Data structure:
     *   [
     *     'dates' => ['2024-01', '2024-02', ...],
     *     'values' => [1.8, 2.1, 1.5, ...]  // Profit factor by period
     *   ]
     * 
     * @param array<mixed> $data Chart data
     * @return string SVG markup
     */
    private function generateProfitFactorChart(array $data): string
    {
        $dates = $data['dates'];
        $values = $data['values'];
        
        // Build SVG
        $svg = $this->createSvgHeader();
        $svg .= $this->createBackground();
        $svg .= $this->createTitle();
        
        $svg .= $this->drawLineChart($dates, $values, 'Date', 'Profit Factor');
        
        // Draw threshold line at 1.0
        $svg .= $this->drawThresholdLine(1.0);
        
        $svg .= $this->createSvgFooter();
        
        return $svg;
    }

    /**
     * Draw line chart
     * 
     * @param array<string> $labels X-axis labels
     * @param array<float> $values Y values
     * @param string $xLabel X-axis label
     * @param string $yLabel Y-axis label
     * @return string SVG markup
     */
    private function drawLineChart(array $labels, array $values, string $xLabel, string $yLabel): string
    {
        $svg = '<g class="line-chart">';
        
        $minValue = min(0, min($values));
        $maxValue = max($values);
        $range = $maxValue - $minValue;
        $minValue -= $range * 0.1;
        $maxValue += $range * 0.1;
        
        // Create grid and axes
        $svg .= $this->createGrid(10, 5);
        $xLabels = $this->selectLabels($labels, 6);
        $yLabels = $this->createValueLabels($minValue, $maxValue, 10);
        $svg .= $this->createAxisLabels($xLabels, $yLabels, $xLabel, $yLabel);
        
        // Draw line
        $chartWidth = $this->getChartWidth();
        $chartHeight = $this->getChartHeight();
        $x0 = $this->padding['left'];
        $y0 = $this->padding['top'];
        
        $points = [];
        foreach ($values as $idx => $value) {
            $x = $x0 + ($chartWidth * $idx / max(1, count($values) - 1));
            $y = $y0 + $chartHeight - (($value - $minValue) / ($maxValue - $minValue) * $chartHeight);
            $points[] = "{$x},{$y}";
        }
        
        $svg .= sprintf(
            '<polyline points="%s" fill="none" stroke="%s" stroke-width="3"/>',
            implode(' ', $points), $this->colors['primary']
        );
        
        $svg .= '</g>';
        
        return $svg;
    }

    /**
     * Draw threshold line (e.g., profit factor = 1.0)
     * 
     * @param float $threshold Threshold value
     * @return string SVG markup
     */
    private function drawThresholdLine(float $threshold): string
    {
        $chartWidth = $this->getChartWidth();
        $chartHeight = $this->getChartHeight();
        $x0 = $this->padding['left'];
        $y0 = $this->padding['top'];
        
        // Assuming threshold line at specific Y position (simplified)
        $thresholdY = $y0 + $chartHeight / 2;
        
        return sprintf(
            '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="2" stroke-dasharray="5,5" opacity="0.5"/>
             <text x="%d" y="%d" font-family="%s" font-size="%d" fill="%s">Threshold: %.1f</text>',
            $x0, $thresholdY, $x0 + $chartWidth, $thresholdY, $this->colors['warning'],
            $x0 + 10, $thresholdY - 10, $this->fontFamily, $this->fontSize - 1, 
            $this->colors['warning'], $threshold
        );
    }

    /**
     * Generate trade duration distribution chart
     * 
     * Data structure:
     *   [
     *     'durations' => [1, 3, 5, 2, 10, ...]  // Trade durations in days
     *   ]
     * 
     * @param array<mixed> $data Chart data
     * @return string SVG markup
     */
    private function generateDurationChart(array $data): string
    {
        $durations = $data['durations'];
        
        // Create histogram bins
        $minDuration = min($durations);
        $maxDuration = max($durations);
        $binWidth = max(1, ($maxDuration - $minDuration) / $this->bins);
        
        $histogram = array_fill(0, $this->bins, 0);
        
        foreach ($durations as $duration) {
            $binIndex = min($this->bins - 1, (int)(($duration - $minDuration) / $binWidth));
            $histogram[$binIndex]++;
        }
        
        // Build SVG
        $svg = $this->createSvgHeader();
        $svg .= $this->createBackground();
        $svg .= $this->createTitle();
        
        // Create labels
        $binLabels = [];
        for ($i = 0; $i < $this->bins; $i++) {
            $binStart = $minDuration + ($i * $binWidth);
            $binLabels[] = (string)(int)$binStart;
        }
        
        $svg .= $this->drawHistogram($binLabels, $histogram);
        
        $svg .= $this->createSvgFooter();
        
        return $svg;
    }

    /**
     * Generate position size distribution chart
     * 
     * Data structure:
     *   [
     *     'sizes' => [10000, 15000, 12000, ...]  // Position sizes in dollars
     *   ]
     * 
     * @param array<mixed> $data Chart data
     * @return string SVG markup
     */
    private function generatePositionSizeChart(array $data): string
    {
        $sizes = $data['sizes'];
        
        // Create histogram bins
        $minSize = min($sizes);
        $maxSize = max($sizes);
        $binWidth = ($maxSize - $minSize) / $this->bins;
        
        $histogram = array_fill(0, $this->bins, 0);
        
        foreach ($sizes as $size) {
            $binIndex = min($this->bins - 1, (int)(($size - $minSize) / $binWidth));
            $histogram[$binIndex]++;
        }
        
        // Build SVG
        $svg = $this->createSvgHeader();
        $svg .= $this->createBackground();
        $svg .= $this->createTitle();
        
        // Create labels
        $binLabels = [];
        for ($i = 0; $i < $this->bins; $i++) {
            $binStart = $minSize + ($i * $binWidth);
            $binLabels[] = $this->formatNumber($binStart, 0, true);
        }
        
        $svg .= $this->drawHistogram($binLabels, $histogram);
        
        $svg .= $this->createSvgFooter();
        
        return $svg;
    }

    /**
     * Select labels to display (sampling for readability)
     * 
     * @param array<string> $labels All labels
     * @param int $maxLabels Maximum number of labels to show
     * @return array<string> Selected labels
     */
    private function selectLabels(array $labels, int $maxLabels): array
    {
        $count = count($labels);
        if ($count <= $maxLabels) {
            return $labels;
        }
        
        $step = (int)ceil($count / $maxLabels);
        $selected = [];
        
        for ($i = 0; $i < $count; $i += $step) {
            $selected[] = $labels[$i];
        }
        
        if (end($selected) !== end($labels)) {
            $selected[] = end($labels);
        }
        
        return $selected;
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
     * Set number of histogram bins
     * 
     * @param int $bins Number of bins
     * @return void
     */
    public function setBins(int $bins): void
    {
        $this->bins = max(5, min(50, $bins));
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
