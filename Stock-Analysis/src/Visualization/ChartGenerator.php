<?php

declare(strict_types=1);

namespace WealthSystem\Visualization;

use InvalidArgumentException;

/**
 * Abstract base class for chart generation
 * 
 * Provides common functionality for all chart types including:
 * - Data validation
 * - Color scheme management
 * - Dimension handling
 * - SVG generation
 * - Export capabilities
 */
abstract class ChartGenerator
{
    /**
     * Chart width in pixels
     */
    protected int $width;

    /**
     * Chart height in pixels
     */
    protected int $height;

    /**
     * Chart title
     */
    protected string $title;

    /**
     * Color scheme for the chart
     * @var array<string, string>
     */
    protected array $colors;

    /**
     * Padding around the chart content (pixels)
     * @var array{top: int, right: int, bottom: int, left: int}
     */
    protected array $padding;

    /**
     * Font family for text elements
     */
    protected string $fontFamily;

    /**
     * Font size for text elements (pixels)
     */
    protected int $fontSize;

    /**
     * Predefined color schemes
     */
    private const COLOR_SCHEMES = [
        'default' => [
            'background' => '#ffffff',
            'text' => '#333333',
            'grid' => '#e0e0e0',
            'primary' => '#2196F3',
            'secondary' => '#4CAF50',
            'accent' => '#FF9800',
            'danger' => '#F44336',
            'success' => '#4CAF50',
            'warning' => '#FFC107',
            'info' => '#2196F3',
        ],
        'dark' => [
            'background' => '#1e1e1e',
            'text' => '#e0e0e0',
            'grid' => '#3a3a3a',
            'primary' => '#64B5F6',
            'secondary' => '#81C784',
            'accent' => '#FFB74D',
            'danger' => '#E57373',
            'success' => '#81C784',
            'warning' => '#FFD54F',
            'info' => '#64B5F6',
        ],
        'professional' => [
            'background' => '#f8f9fa',
            'text' => '#212529',
            'grid' => '#dee2e6',
            'primary' => '#0d6efd',
            'secondary' => '#6c757d',
            'accent' => '#fd7e14',
            'danger' => '#dc3545',
            'success' => '#198754',
            'warning' => '#ffc107',
            'info' => '#0dcaf0',
        ],
        'colorblind' => [
            'background' => '#ffffff',
            'text' => '#000000',
            'grid' => '#cccccc',
            'primary' => '#0173B2',
            'secondary' => '#029E73',
            'accent' => '#ECE133',
            'danger' => '#CC78BC',
            'success' => '#029E73',
            'warning' => '#DE8F05',
            'info' => '#56B4E9',
        ],
    ];

    /**
     * Constructor
     * 
     * @param int $width Chart width in pixels (default: 800)
     * @param int $height Chart height in pixels (default: 600)
     * @param string $title Chart title
     * @param string $colorScheme Color scheme name ('default', 'dark', 'professional', 'colorblind')
     * @throws InvalidArgumentException If dimensions are invalid
     */
    public function __construct(
        int $width = 800,
        int $height = 600,
        string $title = '',
        string $colorScheme = 'default'
    ) {
        $this->validateDimensions($width, $height);
        
        $this->width = $width;
        $this->height = $height;
        $this->title = $title;
        $this->colors = $this->getColorScheme($colorScheme);
        $this->padding = ['top' => 60, 'right' => 40, 'bottom' => 60, 'left' => 80];
        $this->fontFamily = 'Arial, sans-serif';
        $this->fontSize = 12;
    }

    /**
     * Generate the chart and return SVG string
     * 
     * @param array<mixed> $data Chart data
     * @return string SVG markup
     */
    abstract public function generate(array $data): string;

    /**
     * Validate chart data
     * 
     * @param array<mixed> $data Chart data
     * @return void
     * @throws InvalidArgumentException If data is invalid
     */
    abstract protected function validateData(array $data): void;

    /**
     * Validate chart dimensions
     * 
     * @param int $width Width in pixels
     * @param int $height Height in pixels
     * @return void
     * @throws InvalidArgumentException If dimensions are invalid
     */
    protected function validateDimensions(int $width, int $height): void
    {
        if ($width < 100 || $width > 5000) {
            throw new InvalidArgumentException("Width must be between 100 and 5000 pixels, got {$width}");
        }
        
        if ($height < 100 || $height > 5000) {
            throw new InvalidArgumentException("Height must be between 100 and 5000 pixels, got {$height}");
        }
    }

    /**
     * Get color scheme by name
     * 
     * @param string $name Color scheme name
     * @return array<string, string> Color scheme
     * @throws InvalidArgumentException If color scheme doesn't exist
     */
    protected function getColorScheme(string $name): array
    {
        if (!isset(self::COLOR_SCHEMES[$name])) {
            throw new InvalidArgumentException(
                "Unknown color scheme '{$name}'. Available: " . implode(', ', array_keys(self::COLOR_SCHEMES))
            );
        }
        
        return self::COLOR_SCHEMES[$name];
    }

    /**
     * Set custom color scheme
     * 
     * @param array<string, string> $colors Custom color scheme
     * @return void
     */
    public function setColorScheme(array $colors): void
    {
        $this->colors = array_merge($this->colors, $colors);
    }

    /**
     * Set chart padding
     * 
     * @param int $top Top padding (pixels)
     * @param int $right Right padding (pixels)
     * @param int $bottom Bottom padding (pixels)
     * @param int $left Left padding (pixels)
     * @return void
     */
    public function setPadding(int $top, int $right, int $bottom, int $left): void
    {
        $this->padding = ['top' => $top, 'right' => $right, 'bottom' => $bottom, 'left' => $left];
    }

    /**
     * Set font properties
     * 
     * @param string $family Font family
     * @param int $size Font size in pixels
     * @return void
     */
    public function setFont(string $family, int $size): void
    {
        $this->fontFamily = $family;
        $this->fontSize = $size;
    }

    /**
     * Get chart width excluding padding
     * 
     * @return int Chart width in pixels
     */
    protected function getChartWidth(): int
    {
        return $this->width - $this->padding['left'] - $this->padding['right'];
    }

    /**
     * Get chart height excluding padding
     * 
     * @return int Chart height in pixels
     */
    protected function getChartHeight(): int
    {
        return $this->height - $this->padding['top'] - $this->padding['bottom'];
    }

    /**
     * Create SVG header
     * 
     * @return string SVG header markup
     */
    protected function createSvgHeader(): string
    {
        return sprintf(
            '<svg width="%d" height="%d" xmlns="http://www.w3.org/2000/svg">',
            $this->width,
            $this->height
        );
    }

    /**
     * Create SVG footer
     * 
     * @return string SVG footer markup
     */
    protected function createSvgFooter(): string
    {
        return '</svg>';
    }

    /**
     * Create background rectangle
     * 
     * @return string SVG rectangle markup
     */
    protected function createBackground(): string
    {
        return sprintf(
            '<rect width="%d" height="%d" fill="%s"/>',
            $this->width,
            $this->height,
            $this->colors['background']
        );
    }

    /**
     * Create chart title
     * 
     * @return string SVG text markup
     */
    protected function createTitle(): string
    {
        if (empty($this->title)) {
            return '';
        }
        
        return sprintf(
            '<text x="%d" y="%d" font-family="%s" font-size="%d" font-weight="bold" fill="%s" text-anchor="middle">%s</text>',
            $this->width / 2,
            30,
            $this->fontFamily,
            $this->fontSize + 4,
            $this->colors['text'],
            htmlspecialchars($this->title)
        );
    }

    /**
     * Create grid lines
     * 
     * @param int $horizontalLines Number of horizontal grid lines
     * @param int $verticalLines Number of vertical grid lines
     * @return string SVG line markup
     */
    protected function createGrid(int $horizontalLines, int $verticalLines): string
    {
        $svg = '<g class="grid">';
        
        $chartWidth = $this->getChartWidth();
        $chartHeight = $this->getChartHeight();
        $x0 = $this->padding['left'];
        $y0 = $this->padding['top'];
        
        // Horizontal grid lines
        for ($i = 0; $i <= $horizontalLines; $i++) {
            $y = $y0 + ($chartHeight * $i / $horizontalLines);
            $svg .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="1" opacity="0.3"/>',
                $x0,
                $y,
                $x0 + $chartWidth,
                $y,
                $this->colors['grid']
            );
        }
        
        // Vertical grid lines
        for ($i = 0; $i <= $verticalLines; $i++) {
            $x = $x0 + ($chartWidth * $i / $verticalLines);
            $svg .= sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="1" opacity="0.3"/>',
                $x,
                $y0,
                $x,
                $y0 + $chartHeight,
                $this->colors['grid']
            );
        }
        
        $svg .= '</g>';
        return $svg;
    }

    /**
     * Create axis labels
     * 
     * @param array<string> $xLabels X-axis labels
     * @param array<string> $yLabels Y-axis labels
     * @param string $xAxisLabel X-axis label
     * @param string $yAxisLabel Y-axis label
     * @return string SVG text markup
     */
    protected function createAxisLabels(
        array $xLabels,
        array $yLabels,
        string $xAxisLabel = '',
        string $yAxisLabel = ''
    ): string {
        $svg = '<g class="axis-labels">';
        
        $chartWidth = $this->getChartWidth();
        $chartHeight = $this->getChartHeight();
        $x0 = $this->padding['left'];
        $y0 = $this->padding['top'];
        
        // X-axis labels
        $xCount = count($xLabels);
        if ($xCount > 0) {
            for ($i = 0; $i < $xCount; $i++) {
                $x = $x0 + ($chartWidth * $i / max(1, $xCount - 1));
                $y = $y0 + $chartHeight + 20;
                $svg .= sprintf(
                    '<text x="%d" y="%d" font-family="%s" font-size="%d" fill="%s" text-anchor="middle">%s</text>',
                    $x,
                    $y,
                    $this->fontFamily,
                    $this->fontSize,
                    $this->colors['text'],
                    htmlspecialchars($xLabels[$i])
                );
            }
        }
        
        // Y-axis labels
        $yCount = count($yLabels);
        if ($yCount > 0) {
            for ($i = 0; $i < $yCount; $i++) {
                $x = $x0 - 10;
                $y = $y0 + ($chartHeight * (1 - $i / max(1, $yCount - 1))) + 5;
                $svg .= sprintf(
                    '<text x="%d" y="%d" font-family="%s" font-size="%d" fill="%s" text-anchor="end">%s</text>',
                    $x,
                    $y,
                    $this->fontFamily,
                    $this->fontSize,
                    $this->colors['text'],
                    htmlspecialchars($yLabels[$i])
                );
            }
        }
        
        // X-axis title
        if (!empty($xAxisLabel)) {
            $svg .= sprintf(
                '<text x="%d" y="%d" font-family="%s" font-size="%d" fill="%s" text-anchor="middle">%s</text>',
                $x0 + $chartWidth / 2,
                $this->height - 10,
                $this->fontFamily,
                $this->fontSize,
                $this->colors['text'],
                htmlspecialchars($xAxisLabel)
            );
        }
        
        // Y-axis title (rotated)
        if (!empty($yAxisLabel)) {
            $svg .= sprintf(
                '<text x="%d" y="%d" font-family="%s" font-size="%d" fill="%s" text-anchor="middle" transform="rotate(-90 %d %d)">%s</text>',
                20,
                $y0 + $chartHeight / 2,
                $this->fontFamily,
                $this->fontSize,
                $this->colors['text'],
                20,
                $y0 + $chartHeight / 2,
                htmlspecialchars($yAxisLabel)
            );
        }
        
        $svg .= '</g>';
        return $svg;
    }

    /**
     * Create legend
     * 
     * @param array<string, string> $items Legend items (label => color)
     * @param string $position Legend position ('top-right', 'top-left', 'bottom-right', 'bottom-left')
     * @return string SVG markup
     */
    protected function createLegend(array $items, string $position = 'top-right'): string
    {
        if (empty($items)) {
            return '';
        }
        
        $svg = '<g class="legend">';
        
        $itemHeight = 20;
        $itemWidth = 150;
        $legendHeight = count($items) * $itemHeight + 20;
        $legendWidth = $itemWidth + 20;
        
        // Calculate legend position
        [$x, $y] = $this->getLegendPosition($position, $legendWidth, $legendHeight);
        
        // Legend background
        $svg .= sprintf(
            '<rect x="%d" y="%d" width="%d" height="%d" fill="%s" stroke="%s" stroke-width="1" opacity="0.9"/>',
            $x,
            $y,
            $legendWidth,
            $legendHeight,
            $this->colors['background'],
            $this->colors['grid']
        );
        
        // Legend items
        $itemY = $y + 15;
        foreach ($items as $label => $color) {
            // Color box
            $svg .= sprintf(
                '<rect x="%d" y="%d" width="12" height="12" fill="%s"/>',
                $x + 10,
                $itemY,
                $color
            );
            
            // Label
            $svg .= sprintf(
                '<text x="%d" y="%d" font-family="%s" font-size="%d" fill="%s">%s</text>',
                $x + 28,
                $itemY + 10,
                $this->fontFamily,
                $this->fontSize - 1,
                $this->colors['text'],
                htmlspecialchars($label)
            );
            
            $itemY += $itemHeight;
        }
        
        $svg .= '</g>';
        return $svg;
    }

    /**
     * Get legend position coordinates
     * 
     * @param string $position Position name
     * @param int $legendWidth Legend width
     * @param int $legendHeight Legend height
     * @return array{int, int} [x, y] coordinates
     */
    private function getLegendPosition(string $position, int $legendWidth, int $legendHeight): array
    {
        $margin = 10;
        
        return match ($position) {
            'top-right' => [
                $this->width - $legendWidth - $margin,
                $this->padding['top'] + $margin
            ],
            'top-left' => [
                $this->padding['left'] + $margin,
                $this->padding['top'] + $margin
            ],
            'bottom-right' => [
                $this->width - $legendWidth - $margin,
                $this->height - $this->padding['bottom'] - $legendHeight - $margin
            ],
            'bottom-left' => [
                $this->padding['left'] + $margin,
                $this->height - $this->padding['bottom'] - $legendHeight - $margin
            ],
            default => [
                $this->width - $legendWidth - $margin,
                $this->padding['top'] + $margin
            ]
        };
    }

    /**
     * Format number for display
     * 
     * @param float $value Number to format
     * @param int $decimals Number of decimal places
     * @param bool $compact Use compact notation (K, M, B)
     * @return string Formatted number
     */
    protected function formatNumber(float $value, int $decimals = 2, bool $compact = false): string
    {
        if (!$compact) {
            return number_format($value, $decimals);
        }
        
        $abs = abs($value);
        $sign = $value < 0 ? '-' : '';
        
        if ($abs >= 1000000000) {
            return $sign . number_format($abs / 1000000000, $decimals) . 'B';
        } elseif ($abs >= 1000000) {
            return $sign . number_format($abs / 1000000, $decimals) . 'M';
        } elseif ($abs >= 1000) {
            return $sign . number_format($abs / 1000, $decimals) . 'K';
        }
        
        return $sign . number_format($abs, $decimals);
    }

    /**
     * Export chart to file
     * 
     * @param string $data SVG data
     * @param string $filename Output filename
     * @return bool Success
     */
    public function exportToFile(string $data, string $filename): bool
    {
        return file_put_contents($filename, $data) !== false;
    }

    /**
     * Get available color schemes
     * 
     * @return array<string> Color scheme names
     */
    public static function getAvailableColorSchemes(): array
    {
        return array_keys(self::COLOR_SCHEMES);
    }
}
