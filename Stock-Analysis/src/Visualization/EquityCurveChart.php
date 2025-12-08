<?php

declare(strict_types=1);

namespace WealthSystem\Visualization;

use InvalidArgumentException;

/**
 * Equity curve visualization
 * 
 * Generates equity curves showing portfolio value over time with:
 * - Portfolio value line chart
 * - Drawdown overlay (underwater equity curve)
 * - Buy/sell trade markers
 * - Benchmark comparison
 * - Multiple portfolio comparison
 * - Percentage vs absolute value modes
 * - Date range filtering
 */
class EquityCurveChart extends ChartGenerator
{
    /**
     * Show drawdown overlay
     */
    private bool $showDrawdown;

    /**
     * Show trade markers
     */
    private bool $showTrades;

    /**
     * Show benchmark comparison
     */
    private bool $showBenchmark;

    /**
     * Use percentage values instead of absolute
     */
    private bool $usePercentage;

    /**
     * Constructor
     * 
     * @param int $width Chart width in pixels
     * @param int $height Chart height in pixels
     * @param string $title Chart title
     * @param string $colorScheme Color scheme name
     * @param bool $showDrawdown Show drawdown overlay
     * @param bool $showTrades Show trade markers
     * @param bool $showBenchmark Show benchmark comparison
     * @param bool $usePercentage Use percentage values
     */
    public function __construct(
        int $width = 800,
        int $height = 600,
        string $title = 'Equity Curve',
        string $colorScheme = 'default',
        bool $showDrawdown = false,
        bool $showTrades = false,
        bool $showBenchmark = false,
        bool $usePercentage = false
    ) {
        parent::__construct($width, $height, $title, $colorScheme);
        $this->showDrawdown = $showDrawdown;
        $this->showTrades = $showTrades;
        $this->showBenchmark = $showBenchmark;
        $this->usePercentage = $usePercentage;
    }

    /**
     * Generate equity curve chart
     * 
     * @param array<mixed> $data Chart data with structure:
     *   [
     *     'dates' => ['2024-01-01', '2024-01-02', ...],
     *     'values' => [100000, 101500, ...],
     *     'benchmark' => [100000, 100800, ...] (optional),
     *     'trades' => [  // optional
     *       ['date' => '2024-01-15', 'type' => 'buy', 'price' => 101500],
     *       ['date' => '2024-01-20', 'type' => 'sell', 'price' => 103000],
     *     ]
     *   ]
     * @return string SVG markup
     */
    public function generate(array $data): string
    {
        $this->validateData($data);
        
        $dates = $data['dates'];
        $values = $data['values'];
        $benchmark = $data['benchmark'] ?? null;
        $trades = $data['trades'] ?? [];
        
        // Convert to percentage if needed
        if ($this->usePercentage) {
            $values = $this->convertToPercentage($values);
            if ($benchmark !== null) {
                $benchmark = $this->convertToPercentage($benchmark);
            }
        }
        
        // Calculate drawdown if needed
        $drawdown = $this->showDrawdown ? $this->calculateDrawdown($values) : null;
        
        // Build SVG
        $svg = $this->createSvgHeader();
        $svg .= $this->createBackground();
        $svg .= $this->createTitle();
        
        // Create chart area
        $svg .= $this->createChartArea($dates, $values, $benchmark, $drawdown, $trades);
        
        // Create legend
        $legend = ['Portfolio' => $this->colors['primary']];
        if ($benchmark !== null) {
            $legend['Benchmark'] = $this->colors['secondary'];
        }
        if ($drawdown !== null) {
            $legend['Drawdown'] = $this->colors['danger'];
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
            throw new InvalidArgumentException('Need at least 2 data points for equity curve');
        }
        
        if (isset($data['benchmark']) && count($data['benchmark']) !== count($data['dates'])) {
            throw new InvalidArgumentException('Benchmark array must have same length as dates');
        }
    }

    /**
     * Convert values to percentage returns
     * 
     * @param array<float> $values Absolute values
     * @return array<float> Percentage returns (0-100 scale)
     */
    private function convertToPercentage(array $values): array
    {
        $initial = $values[0];
        return array_map(fn($v) => (($v - $initial) / $initial) * 100, $values);
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
     * Create chart area with equity curve
     * 
     * @param array<string> $dates Date labels
     * @param array<float> $values Portfolio values
     * @param array<float>|null $benchmark Benchmark values
     * @param array<float>|null $drawdown Drawdown values
     * @param array<array<string, mixed>> $trades Trade markers
     * @return string SVG markup
     */
    private function createChartArea(
        array $dates,
        array $values,
        ?array $benchmark,
        ?array $drawdown,
        array $trades
    ): string {
        $svg = '<g class="chart-area">';
        
        // Calculate scales
        $minValue = min($values);
        $maxValue = max($values);
        
        if ($benchmark !== null) {
            $minValue = min($minValue, min($benchmark));
            $maxValue = max($maxValue, max($benchmark));
        }
        
        if ($drawdown !== null) {
            $minValue = min($minValue, min($drawdown));
        }
        
        // Add 10% padding to min/max
        $range = $maxValue - $minValue;
        $minValue -= $range * 0.1;
        $maxValue += $range * 0.1;
        
        // Create grid
        $svg .= $this->createGrid(10, 5);
        
        // Create axis labels
        $xLabels = $this->selectDateLabels($dates, 6);
        $yLabels = $this->createValueLabels($minValue, $maxValue, 10);
        $yAxisLabel = $this->usePercentage ? 'Return (%)' : 'Portfolio Value';
        $svg .= $this->createAxisLabels($xLabels, $yLabels, 'Date', $yAxisLabel);
        
        // Draw drawdown area (if enabled)
        if ($drawdown !== null) {
            $svg .= $this->drawDrawdownArea($drawdown, $minValue, $maxValue, count($dates));
        }
        
        // Draw benchmark line (if provided)
        if ($benchmark !== null) {
            $svg .= $this->drawLine($benchmark, $minValue, $maxValue, $this->colors['secondary'], 2, 'benchmark');
        }
        
        // Draw portfolio line
        $svg .= $this->drawLine($values, $minValue, $maxValue, $this->colors['primary'], 3, 'portfolio');
        
        // Draw trade markers (if enabled)
        if ($this->showTrades && !empty($trades)) {
            $svg .= $this->drawTradeMarkers($trades, $dates, $values, $minValue, $maxValue);
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
     * Draw drawdown area (underwater equity curve)
     * 
     * @param array<float> $drawdown Drawdown values
     * @param float $minValue Y-axis minimum
     * @param float $maxValue Y-axis maximum
     * @param int $count Number of points
     * @return string SVG markup
     */
    private function drawDrawdownArea(
        array $drawdown,
        float $minValue,
        float $maxValue,
        int $count
    ): string {
        $points = $this->calculateLinePoints($drawdown, $minValue, $maxValue);
        
        // Create filled area below zero line
        $chartWidth = $this->getChartWidth();
        $chartHeight = $this->getChartHeight();
        $x0 = $this->padding['left'];
        $y0 = $this->padding['top'];
        
        // Calculate y position of zero line
        $zeroY = $y0 + $chartHeight - ($chartHeight * (0 - $minValue) / ($maxValue - $minValue));
        
        // Build polygon points (drawdown line + baseline)
        $polygonPoints = array_map(fn($p) => "{$p[0]},{$p[1]}", $points);
        $polygonPoints[] = ($x0 + $chartWidth) . ',' . $zeroY;
        $polygonPoints[] = $x0 . ',' . $zeroY;
        
        return sprintf(
            '<polygon class="drawdown-area" points="%s" fill="%s" opacity="0.3"/>',
            implode(' ', $polygonPoints),
            $this->colors['danger']
        );
    }

    /**
     * Draw trade markers
     * 
     * @param array<array<string, mixed>> $trades Trade data
     * @param array<string> $dates All dates
     * @param array<float> $values Portfolio values
     * @param float $minValue Y-axis minimum
     * @param float $maxValue Y-axis maximum
     * @return string SVG markup
     */
    private function drawTradeMarkers(
        array $trades,
        array $dates,
        array $values,
        float $minValue,
        float $maxValue
    ): string {
        $svg = '<g class="trade-markers">';
        
        $dateIndex = array_flip($dates);
        
        foreach ($trades as $trade) {
            $date = $trade['date'];
            $type = $trade['type'];
            
            if (!isset($dateIndex[$date])) {
                continue;
            }
            
            $idx = $dateIndex[$date];
            $value = $values[$idx];
            
            [$x, $y] = $this->valueToCoords($idx, $value, count($values), $minValue, $maxValue);
            
            // Draw marker (triangle for buy, inverted triangle for sell)
            $color = $type === 'buy' ? $this->colors['success'] : $this->colors['danger'];
            
            if ($type === 'buy') {
                // Upward triangle
                $svg .= sprintf(
                    '<polygon points="%d,%d %d,%d %d,%d" fill="%s" stroke="white" stroke-width="1"/>',
                    $x, $y - 10,
                    $x - 6, $y,
                    $x + 6, $y,
                    $color
                );
            } else {
                // Downward triangle
                $svg .= sprintf(
                    '<polygon points="%d,%d %d,%d %d,%d" fill="%s" stroke="white" stroke-width="1"/>',
                    $x, $y + 10,
                    $x - 6, $y,
                    $x + 6, $y,
                    $color
                );
            }
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
            $labels[] = $this->usePercentage
                ? $this->formatNumber($value, 1) . '%'
                : $this->formatNumber($value, 0, true);
        }
        
        return array_reverse($labels);
    }

    /**
     * Set drawdown visibility
     * 
     * @param bool $show Show drawdown overlay
     * @return void
     */
    public function setShowDrawdown(bool $show): void
    {
        $this->showDrawdown = $show;
    }

    /**
     * Set trade markers visibility
     * 
     * @param bool $show Show trade markers
     * @return void
     */
    public function setShowTrades(bool $show): void
    {
        $this->showTrades = $show;
    }

    /**
     * Set benchmark visibility
     * 
     * @param bool $show Show benchmark comparison
     * @return void
     */
    public function setShowBenchmark(bool $show): void
    {
        $this->showBenchmark = $show;
    }

    /**
     * Set percentage mode
     * 
     * @param bool $use Use percentage values
     * @return void
     */
    public function setUsePercentage(bool $use): void
    {
        $this->usePercentage = $use;
    }
}
