<?php

declare(strict_types=1);

namespace WealthSystem\Visualization;

use InvalidArgumentException;

/**
 * Drawdown analysis chart
 * 
 * Generates underwater equity curves showing drawdown over time with:
 * - Underwater equity curve (drawdown from peak)
 * - Drawdown period highlighting
 * - Recovery time annotations
 * - Maximum drawdown markers
 * - Multiple strategy comparison
 * - Duration analysis
 */
class DrawdownChart extends ChartGenerator
{
    /**
     * Highlight drawdown periods
     */
    private bool $highlightPeriods;

    /**
     * Show recovery annotations
     */
    private bool $showRecoveryTime;

    /**
     * Show max drawdown markers
     */
    private bool $showMaxDrawdown;

    /**
     * Constructor
     * 
     * @param int $width Chart width in pixels
     * @param int $height Chart height in pixels
     * @param string $title Chart title
     * @param string $colorScheme Color scheme name
     * @param bool $highlightPeriods Highlight drawdown periods
     * @param bool $showRecoveryTime Show recovery time annotations
     * @param bool $showMaxDrawdown Show max drawdown markers
     */
    public function __construct(
        int $width = 800,
        int $height = 600,
        string $title = 'Drawdown Analysis',
        string $colorScheme = 'default',
        bool $highlightPeriods = true,
        bool $showRecoveryTime = true,
        bool $showMaxDrawdown = true
    ) {
        parent::__construct($width, $height, $title, $colorScheme);
        $this->highlightPeriods = $highlightPeriods;
        $this->showRecoveryTime = $showRecoveryTime;
        $this->showMaxDrawdown = $showMaxDrawdown;
    }

    /**
     * Generate drawdown chart
     * 
     * @param array<mixed> $data Chart data with structure:
     *   [
     *     'dates' => ['2024-01-01', '2024-01-02', ...],
     *     'values' => [100000, 101500, 99000, ...],
     *     'strategies' => [  // optional, for comparison
     *       'Strategy A' => [100000, 101000, 98000, ...],
     *       'Strategy B' => [100000, 102000, 101000, ...],
     *     ]
     *   ]
     * @return string SVG markup
     */
    public function generate(array $data): string
    {
        $this->validateData($data);
        
        $dates = $data['dates'];
        $values = $data['values'];
        $strategies = $data['strategies'] ?? [];
        
        // Calculate drawdown
        $drawdown = $this->calculateDrawdown($values);
        $drawdownPeriods = $this->identifyDrawdownPeriods($drawdown, $dates);
        $maxDrawdown = $this->findMaxDrawdown($drawdown, $dates);
        
        // Calculate drawdowns for strategies
        $strategyDrawdowns = [];
        foreach ($strategies as $name => $strategyValues) {
            $strategyDrawdowns[$name] = $this->calculateDrawdown($strategyValues);
        }
        
        // Build SVG
        $svg = $this->createSvgHeader();
        $svg .= $this->createBackground();
        $svg .= $this->createTitle();
        
        // Create chart area
        $svg .= $this->createChartArea(
            $dates,
            $drawdown,
            $strategyDrawdowns,
            $drawdownPeriods,
            $maxDrawdown
        );
        
        // Create legend
        $legend = ['Main' => $this->colors['primary']];
        $colorIndex = 0;
        $compareColors = [$this->colors['secondary'], $this->colors['accent'], $this->colors['info']];
        foreach (array_keys($strategyDrawdowns) as $name) {
            $legend[$name] = $compareColors[$colorIndex % count($compareColors)];
            $colorIndex++;
        }
        $svg .= $this->createLegend($legend);
        
        $svg .= $this->createSvgFooter();
        
        return $svg;
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
        if (!isset($data['dates']) || !is_array($data['dates'])) {
            throw new InvalidArgumentException('Data must contain "dates" array');
        }
        
        if (!isset($data['values']) || !is_array($data['values'])) {
            throw new InvalidArgumentException('Data must contain "values" array');
        }
        
        if (count($data['dates']) !== count($data['values'])) {
            throw new InvalidArgumentException('Dates and values arrays must have same length');
        }
        
        if (count($data['dates']) < 2) {
            throw new InvalidArgumentException('Need at least 2 data points for drawdown chart');
        }
        
        if (isset($data['strategies'])) {
            foreach ($data['strategies'] as $name => $values) {
                if (count($values) !== count($data['dates'])) {
                    throw new InvalidArgumentException(
                        "Strategy '{$name}' values must have same length as dates"
                    );
                }
            }
        }
    }

    /**
     * Calculate drawdown from equity curve
     * 
     * @param array<float> $values Equity values
     * @return array<float> Drawdown values (negative percentages)
     */
    private function calculateDrawdown(array $values): array
    {
        $drawdown = [];
        $peak = $values[0];
        
        foreach ($values as $value) {
            if ($value > $peak) {
                $peak = $value;
            }
            $dd = $peak > 0 ? (($value - $peak) / $peak) * 100 : 0;
            $drawdown[] = $dd;
        }
        
        return $drawdown;
    }

    /**
     * Identify drawdown periods (sequences below zero)
     * 
     * @param array<float> $drawdown Drawdown values
     * @param array<string> $dates Date labels
     * @return array<array{start: int, end: int, startDate: string, endDate: string, depth: float, duration: int}> Drawdown periods
     */
    private function identifyDrawdownPeriods(array $drawdown, array $dates): array
    {
        $periods = [];
        $inDrawdown = false;
        $startIdx = 0;
        $maxDepth = 0;
        
        foreach ($drawdown as $idx => $dd) {
            if ($dd < 0 && !$inDrawdown) {
                // Start of drawdown period
                $inDrawdown = true;
                $startIdx = $idx;
                $maxDepth = $dd;
            } elseif ($dd < 0 && $inDrawdown) {
                // Continuing drawdown
                $maxDepth = min($maxDepth, $dd);
            } elseif ($dd >= 0 && $inDrawdown) {
                // End of drawdown period (recovery)
                $inDrawdown = false;
                $periods[] = [
                    'start' => $startIdx,
                    'end' => $idx - 1,
                    'startDate' => $dates[$startIdx],
                    'endDate' => $dates[$idx - 1],
                    'depth' => $maxDepth,
                    'duration' => $idx - $startIdx,
                ];
            }
        }
        
        // Handle ongoing drawdown at end
        if ($inDrawdown) {
            $lastIdx = count($drawdown) - 1;
            $periods[] = [
                'start' => $startIdx,
                'end' => $lastIdx,
                'startDate' => $dates[$startIdx],
                'endDate' => $dates[$lastIdx],
                'depth' => $maxDepth,
                'duration' => $lastIdx - $startIdx + 1,
            ];
        }
        
        return $periods;
    }

    /**
     * Find maximum drawdown
     * 
     * @param array<float> $drawdown Drawdown values
     * @param array<string> $dates Date labels
     * @return array{index: int, date: string, value: float} Max drawdown info
     */
    private function findMaxDrawdown(array $drawdown, array $dates): array
    {
        $minIdx = 0;
        $minValue = 0;
        
        foreach ($drawdown as $idx => $dd) {
            if ($dd < $minValue) {
                $minValue = $dd;
                $minIdx = $idx;
            }
        }
        
        return [
            'index' => $minIdx,
            'date' => $dates[$minIdx],
            'value' => $minValue,
        ];
    }

    /**
     * Create chart area with drawdown curve
     * 
     * @param array<string> $dates Date labels
     * @param array<float> $drawdown Main drawdown values
     * @param array<string, array<float>> $strategyDrawdowns Strategy drawdowns
     * @param array<array<string, mixed>> $drawdownPeriods Drawdown periods
     * @param array{index: int, date: string, value: float} $maxDrawdown Max drawdown info
     * @return string SVG markup
     */
    private function createChartArea(
        array $dates,
        array $drawdown,
        array $strategyDrawdowns,
        array $drawdownPeriods,
        array $maxDrawdown
    ): string {
        $svg = '<g class="chart-area">';
        
        // Calculate scales
        $minValue = min($drawdown);
        foreach ($strategyDrawdowns as $dd) {
            $minValue = min($minValue, min($dd));
        }
        
        $maxValue = 0; // Drawdown is always 0 or negative
        
        // Add 10% padding to min
        $minValue *= 1.1;
        
        // Create grid
        $svg .= $this->createGrid(10, 5);
        
        // Create axis labels
        $xLabels = $this->selectDateLabels($dates, 6);
        $yLabels = $this->createValueLabels($minValue, $maxValue, 10);
        $svg .= $this->createAxisLabels($xLabels, $yLabels, 'Date', 'Drawdown (%)');
        
        // Highlight drawdown periods (if enabled)
        if ($this->highlightPeriods) {
            $svg .= $this->drawDrawdownPeriods($drawdownPeriods, count($dates));
        }
        
        // Draw strategy drawdowns
        $colorIndex = 0;
        $compareColors = [$this->colors['secondary'], $this->colors['accent'], $this->colors['info']];
        foreach ($strategyDrawdowns as $name => $dd) {
            $color = $compareColors[$colorIndex % count($compareColors)];
            $svg .= $this->drawLine($dd, $minValue, $maxValue, $color, 2, "strategy-{$colorIndex}");
            $colorIndex++;
        }
        
        // Draw main drawdown curve
        $svg .= $this->drawLine($drawdown, $minValue, $maxValue, $this->colors['primary'], 3, 'main');
        
        // Draw zero line
        $svg .= $this->drawZeroLine();
        
        // Mark maximum drawdown (if enabled)
        if ($this->showMaxDrawdown) {
            $svg .= $this->drawMaxDrawdownMarker($maxDrawdown, $minValue, $maxValue, count($dates));
        }
        
        // Add recovery time annotations (if enabled)
        if ($this->showRecoveryTime) {
            $svg .= $this->drawRecoveryAnnotations($drawdownPeriods, count($dates));
        }
        
        $svg .= '</g>';
        
        return $svg;
    }

    /**
     * Draw line chart
     * 
     * @param array<float> $values Y values
     * @param float $minValue Y-axis minimum
     * @param float $maxValue Y-axis maximum
     * @param string $color Line color
     * @param int $strokeWidth Line width
     * @param string $className CSS class name
     * @return string SVG markup
     */
    private function drawLine(
        array $values,
        float $minValue,
        float $maxValue,
        string $color,
        int $strokeWidth,
        string $className
    ): string {
        $points = $this->calculateLinePoints($values, $minValue, $maxValue);
        
        return sprintf(
            '<polyline class="%s" points="%s" fill="none" stroke="%s" stroke-width="%d"/>',
            $className,
            implode(' ', array_map(fn($p) => "{$p[0]},{$p[1]}", $points)),
            $color,
            $strokeWidth
        );
    }

    /**
     * Draw zero line (baseline)
     * 
     * @return string SVG markup
     */
    private function drawZeroLine(): string
    {
        $chartWidth = $this->getChartWidth();
        $x0 = $this->padding['left'];
        $y0 = $this->padding['top'];
        
        // Zero is at the top of the chart (max value)
        $zeroY = $y0;
        
        return sprintf(
            '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="2" stroke-dasharray="5,5"/>',
            $x0,
            $zeroY,
            $x0 + $chartWidth,
            $zeroY,
            $this->colors['text']
        );
    }

    /**
     * Draw drawdown period highlights
     * 
     * @param array<array<string, mixed>> $periods Drawdown periods
     * @param int $totalPoints Total number of data points
     * @return string SVG markup
     */
    private function drawDrawdownPeriods(array $periods, int $totalPoints): string
    {
        $svg = '<g class="drawdown-periods">';
        
        $chartWidth = $this->getChartWidth();
        $chartHeight = $this->getChartHeight();
        $x0 = $this->padding['left'];
        $y0 = $this->padding['top'];
        
        foreach ($periods as $period) {
            $startX = $x0 + ($chartWidth * $period['start'] / max(1, $totalPoints - 1));
            $endX = $x0 + ($chartWidth * $period['end'] / max(1, $totalPoints - 1));
            $width = $endX - $startX;
            
            $svg .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" fill="%s" opacity="0.1"/>',
                $startX,
                $y0,
                $width,
                $chartHeight,
                $this->colors['danger']
            );
        }
        
        $svg .= '</g>';
        
        return $svg;
    }

    /**
     * Draw maximum drawdown marker
     * 
     * @param array{index: int, date: string, value: float} $maxDrawdown Max drawdown info
     * @param float $minValue Y-axis minimum
     * @param float $maxValue Y-axis maximum
     * @param int $totalPoints Total number of data points
     * @return string SVG markup
     */
    private function drawMaxDrawdownMarker(
        array $maxDrawdown,
        float $minValue,
        float $maxValue,
        int $totalPoints
    ): string {
        [$x, $y] = $this->valueToCoords(
            $maxDrawdown['index'],
            $maxDrawdown['value'],
            $totalPoints,
            $minValue,
            $maxValue
        );
        
        $svg = '<g class="max-drawdown-marker">';
        
        // Circle marker
        $svg .= sprintf(
            '<circle cx="%d" cy="%d" r="5" fill="%s" stroke="white" stroke-width="2"/>',
            $x,
            $y,
            $this->colors['danger']
        );
        
        // Label
        $label = sprintf('Max DD: %.2f%%', $maxDrawdown['value']);
        $svg .= sprintf(
            '<text x="%d" y="%d" font-family="%s" font-size="%d" fill="%s" text-anchor="middle">%s</text>',
            $x,
            $y + 20,
            $this->fontFamily,
            $this->fontSize - 1,
            $this->colors['danger'],
            $label
        );
        
        $svg .= '</g>';
        
        return $svg;
    }

    /**
     * Draw recovery time annotations
     * 
     * @param array<array<string, mixed>> $periods Drawdown periods
     * @param int $totalPoints Total number of data points
     * @return string SVG markup
     */
    private function drawRecoveryAnnotations(array $periods, int $totalPoints): string
    {
        $svg = '<g class="recovery-annotations">';
        
        $chartWidth = $this->getChartWidth();
        $chartHeight = $this->getChartHeight();
        $x0 = $this->padding['left'];
        $y0 = $this->padding['top'];
        
        foreach ($periods as $period) {
            // Only annotate significant drawdowns (> 5%)
            if ($period['depth'] > -5) {
                continue;
            }
            
            $midX = $x0 + ($chartWidth * (($period['start'] + $period['end']) / 2) / max(1, $totalPoints - 1));
            $labelY = $y0 + $chartHeight / 3;
            
            $label = sprintf('%d days', $period['duration']);
            $svg .= sprintf(
                '<text x="%d" y="%d" font-family="%s" font-size="%d" fill="%s" text-anchor="middle" opacity="0.7">%s</text>',
                $midX,
                $labelY,
                $this->fontFamily,
                $this->fontSize - 2,
                $this->colors['text'],
                $label
            );
        }
        
        $svg .= '</g>';
        
        return $svg;
    }

    /**
     * Calculate line points for polyline
     * 
     * @param array<float> $values Y values
     * @param float $minValue Y-axis minimum
     * @param float $maxValue Y-axis maximum
     * @return array<array{int, int}> Array of [x, y] coordinates
     */
    private function calculateLinePoints(array $values, float $minValue, float $maxValue): array
    {
        $points = [];
        $count = count($values);
        
        foreach ($values as $idx => $value) {
            $points[] = $this->valueToCoords($idx, $value, $count, $minValue, $maxValue);
        }
        
        return $points;
    }

    /**
     * Convert value index and amount to chart coordinates
     * 
     * @param int $index Value index
     * @param float $value Value amount
     * @param int $total Total number of values
     * @param float $minValue Y-axis minimum
     * @param float $maxValue Y-axis maximum
     * @return array{int, int} [x, y] coordinates
     */
    private function valueToCoords(
        int $index,
        float $value,
        int $total,
        float $minValue,
        float $maxValue
    ): array {
        $chartWidth = $this->getChartWidth();
        $chartHeight = $this->getChartHeight();
        $x0 = $this->padding['left'];
        $y0 = $this->padding['top'];
        
        $x = (int)($x0 + ($chartWidth * $index / max(1, $total - 1)));
        $y = (int)($y0 + $chartHeight - ($chartHeight * ($value - $minValue) / ($maxValue - $minValue)));
        
        return [$x, $y];
    }

    /**
     * Select date labels to display (sampling for readability)
     * 
     * @param array<string> $dates All dates
     * @param int $maxLabels Maximum number of labels to show
     * @return array<string> Selected date labels
     */
    private function selectDateLabels(array $dates, int $maxLabels): array
    {
        $count = count($dates);
        if ($count <= $maxLabels) {
            return $dates;
        }
        
        $step = (int)ceil($count / $maxLabels);
        $selected = [];
        
        for ($i = 0; $i < $count; $i += $step) {
            $selected[] = $dates[$i];
        }
        
        // Always include last date
        if (end($selected) !== end($dates)) {
            $selected[] = end($dates);
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
            $labels[] = $this->formatNumber($value, 1) . '%';
        }
        
        return array_reverse($labels);
    }

    /**
     * Set period highlighting
     * 
     * @param bool $highlight Highlight drawdown periods
     * @return void
     */
    public function setHighlightPeriods(bool $highlight): void
    {
        $this->highlightPeriods = $highlight;
    }

    /**
     * Set recovery time annotations visibility
     * 
     * @param bool $show Show recovery time annotations
     * @return void
     */
    public function setShowRecoveryTime(bool $show): void
    {
        $this->showRecoveryTime = $show;
    }

    /**
     * Set max drawdown marker visibility
     * 
     * @param bool $show Show max drawdown marker
     * @return void
     */
    public function setShowMaxDrawdown(bool $show): void
    {
        $this->showMaxDrawdown = $show;
    }
}
