<?php

declare(strict_types=1);

namespace App\Services\Trading\Strategies;

/**
 * Ichimoku Cloud Strategy
 * 
 * Implements the Ichimoku Kinko Hyo (Ichimoku Cloud) technical analysis method.
 * Uses five components: Tenkan-sen, Kijun-sen, Senkou Span A, Senkou Span B, and Chikou Span.
 * 
 * Signals:
 * - BUY: Price above cloud, bullish crossover, or strong bullish configuration
 * - SELL: Price below cloud, bearish crossover, or strong bearish configuration
 * - HOLD: Price within cloud or unclear trend
 * 
 * Compatible with backtesting framework.
 * 
 * @package App\Services\Trading\Strategies
 */
class IchimokuCloudStrategy
{
    private int $tenkanPeriod;
    private int $kijunPeriod;
    private int $senkouBPeriod;
    private int $displacement;
    
    /**
     * Create new Ichimoku Cloud strategy
     *
     * @param array<string, int> $parameters Optional parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->tenkanPeriod = $parameters['tenkan_period'] ?? 9;
        $this->kijunPeriod = $parameters['kijun_period'] ?? 26;
        $this->senkouBPeriod = $parameters['senkou_b_period'] ?? 52;
        $this->displacement = $parameters['displacement'] ?? 26;
    }
    
    /**
     * Analyze market data and generate trading signal
     *
     * @param string $symbol Stock symbol
     * @param array<int, array<string, mixed>> $historicalData Historical OHLCV data
     * @return array<string, mixed> Trading signal with confidence and details
     */
    public function analyze(string $symbol, array $historicalData): array
    {
        $dataCount = count($historicalData);
        
        // Need enough data for all calculations
        if ($dataCount < $this->senkouBPeriod + $this->displacement) {
            return [
                'signal' => 'HOLD',
                'confidence' => 0.3,
                'strategy' => 'IchimokuCloudStrategy',
                'reason' => 'Insufficient data for Ichimoku Cloud analysis'
            ];
        }
        
        // Calculate Ichimoku components
        $tenkanSen = $this->calculateTenkanSen($historicalData);
        $kijunSen = $this->calculateKijunSen($historicalData);
        $senkouSpanA = $this->calculateSenkouSpanA($tenkanSen, $kijunSen);
        $senkouSpanB = $this->calculateSenkouSpanB($historicalData);
        $chikouSpan = $this->calculateChikouSpan($historicalData);
        
        $currentPrice = $historicalData[$dataCount - 1]['close'];
        
        // Determine cloud boundaries (current position, not displaced)
        $cloudTop = max($senkouSpanA, $senkouSpanB);
        $cloudBottom = min($senkouSpanA, $senkouSpanB);
        
        // Determine price position relative to cloud
        $pricePosition = $this->getPricePosition($currentPrice, $cloudTop, $cloudBottom);
        
        // Detect crossovers
        $crossover = $this->detectCrossover($historicalData, $tenkanSen, $kijunSen);
        
        // Determine cloud color (bullish or bearish)
        $cloudColor = $senkouSpanA > $senkouSpanB ? 'bullish' : 'bearish';
        
        // Generate signal
        $signal = 'HOLD';
        $confidence = 0.5;
        $reason = '';
        
        if ($pricePosition === 'above' && $cloudColor === 'bullish') {
            $signal = 'BUY';
            $confidence = 0.75;
            $reason = 'Price above bullish cloud';
            
            // Check for additional bullish confirmation
            if ($tenkanSen > $kijunSen) {
                $confidence = 0.85;
                $reason .= ' with bullish momentum';
            }
            
            if ($crossover === 'bullish') {
                $confidence = 0.95;
                $reason .= ' with bullish crossover';
            }
        } elseif ($pricePosition === 'below' && $cloudColor === 'bearish') {
            $signal = 'SELL';
            $confidence = 0.75;
            $reason = 'Price below bearish cloud';
            
            // Check for additional bearish confirmation
            if ($tenkanSen < $kijunSen) {
                $confidence = 0.85;
                $reason .= ' with bearish momentum';
            }
            
            if ($crossover === 'bearish') {
                $confidence = 0.95;
                $reason .= ' with bearish crossover';
            }
        } elseif ($crossover === 'bullish') {
            $signal = 'BUY';
            $confidence = 0.7;
            $reason = 'Bullish crossover detected';
        } elseif ($crossover === 'bearish') {
            $signal = 'SELL';
            $confidence = 0.7;
            $reason = 'Bearish crossover detected';
        } elseif ($pricePosition === 'inside') {
            $signal = 'HOLD';
            $confidence = 0.4;
            $reason = 'Price inside cloud - unclear trend';
        }
        
        return [
            'signal' => $signal,
            'confidence' => $confidence,
            'strategy' => 'IchimokuCloudStrategy',
            'reason' => $reason,
            'tenkan_sen' => $tenkanSen,
            'kijun_sen' => $kijunSen,
            'senkou_span_a' => $senkouSpanA,
            'senkou_span_b' => $senkouSpanB,
            'chikou_span' => $chikouSpan,
            'cloud_color' => $cloudColor,
            'price_position' => $pricePosition,
            'crossover' => $crossover
        ];
    }
    
    /**
     * Calculate Tenkan-sen (Conversion Line)
     * (9-period high + 9-period low) / 2
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @return float Tenkan-sen value
     */
    private function calculateTenkanSen(array $data): float
    {
        $recentData = array_slice($data, -$this->tenkanPeriod);
        $highs = array_column($recentData, 'high');
        $lows = array_column($recentData, 'low');
        
        return (max($highs) + min($lows)) / 2;
    }
    
    /**
     * Calculate Kijun-sen (Base Line)
     * (26-period high + 26-period low) / 2
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @return float Kijun-sen value
     */
    private function calculateKijunSen(array $data): float
    {
        $recentData = array_slice($data, -$this->kijunPeriod);
        $highs = array_column($recentData, 'high');
        $lows = array_column($recentData, 'low');
        
        return (max($highs) + min($lows)) / 2;
    }
    
    /**
     * Calculate Senkou Span A (Leading Span A)
     * (Tenkan-sen + Kijun-sen) / 2, plotted 26 periods ahead
     *
     * @param float $tenkanSen Tenkan-sen value
     * @param float $kijunSen Kijun-sen value
     * @return float Senkou Span A value
     */
    private function calculateSenkouSpanA(float $tenkanSen, float $kijunSen): float
    {
        return ($tenkanSen + $kijunSen) / 2;
    }
    
    /**
     * Calculate Senkou Span B (Leading Span B)
     * (52-period high + 52-period low) / 2, plotted 26 periods ahead
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @return float Senkou Span B value
     */
    private function calculateSenkouSpanB(array $data): float
    {
        $dataCount = count($data);
        
        if ($dataCount < $this->senkouBPeriod) {
            // Fall back to available data
            $recentData = $data;
        } else {
            $recentData = array_slice($data, -$this->senkouBPeriod);
        }
        
        $highs = array_column($recentData, 'high');
        $lows = array_column($recentData, 'low');
        
        return (max($highs) + min($lows)) / 2;
    }
    
    /**
     * Calculate Chikou Span (Lagging Span)
     * Current closing price, plotted 26 periods in the past
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @return float Chikou Span value
     */
    private function calculateChikouSpan(array $data): float
    {
        $dataCount = count($data);
        return $data[$dataCount - 1]['close'];
    }
    
    /**
     * Determine price position relative to cloud
     *
     * @param float $price Current price
     * @param float $cloudTop Cloud top boundary
     * @param float $cloudBottom Cloud bottom boundary
     * @return string Position: 'above', 'below', or 'inside'
     */
    private function getPricePosition(float $price, float $cloudTop, float $cloudBottom): string
    {
        if ($price > $cloudTop) {
            return 'above';
        } elseif ($price < $cloudBottom) {
            return 'below';
        } else {
            return 'inside';
        }
    }
    
    /**
     * Detect Tenkan-sen/Kijun-sen crossover
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @param float $currentTenkan Current Tenkan-sen
     * @param float $currentKijun Current Kijun-sen
     * @return string|null Crossover type: 'bullish', 'bearish', or null
     */
    private function detectCrossover(array $data, float $currentTenkan, float $currentKijun): ?string
    {
        $dataCount = count($data);
        
        if ($dataCount < $this->kijunPeriod + 2) {
            return null;
        }
        
        // Calculate previous period values
        $previousData = array_slice($data, 0, -1);
        $previousTenkan = $this->calculateTenkanSen($previousData);
        $previousKijun = $this->calculateKijunSen($previousData);
        
        // Detect crossover
        $previousAbove = $previousTenkan > $previousKijun;
        $currentAbove = $currentTenkan > $currentKijun;
        
        if (!$previousAbove && $currentAbove) {
            return 'bullish';  // Tenkan crossed above Kijun
        } elseif ($previousAbove && !$currentAbove) {
            return 'bearish';  // Tenkan crossed below Kijun
        }
        
        return null;
    }
}
