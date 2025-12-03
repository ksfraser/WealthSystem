<?php

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * Technical Indicator Service
 * Provides access to 150+ TA-Lib technical analysis functions
 * 
 * Wraps TA-Lib trader extension with caching, validation, and error handling
 * 
 * @author WealthSystem
 * @version 1.0
 */
class TechnicalIndicatorService
{
    private array $cache = [];
    private int $cacheTTL = 3600; // 1 hour
    private bool $taLibAvailable = false;
    
    /**
     * Moving average types for TA-Lib functions
     */
    public const MA_TYPE_SMA = TRADER_MA_TYPE_SMA;      // Simple
    public const MA_TYPE_EMA = TRADER_MA_TYPE_EMA;      // Exponential
    public const MA_TYPE_WMA = TRADER_MA_TYPE_WMA;      // Weighted
    public const MA_TYPE_DEMA = TRADER_MA_TYPE_DEMA;    // Double Exponential
    public const MA_TYPE_TEMA = TRADER_MA_TYPE_TEMA;    // Triple Exponential
    public const MA_TYPE_TRIMA = TRADER_MA_TYPE_TRIMA;  // Triangular
    public const MA_TYPE_KAMA = TRADER_MA_TYPE_KAMA;    // Kaufman Adaptive
    public const MA_TYPE_MAMA = TRADER_MA_TYPE_MAMA;    // MESA Adaptive
    public const MA_TYPE_T3 = TRADER_MA_TYPE_T3;        // Triple Exponential T3
    
    public function __construct()
    {
        $this->taLibAvailable = extension_loaded('trader');
        
        if (!$this->taLibAvailable) {
            error_log('WARNING: TA-Lib trader extension not loaded. Technical indicators unavailable.');
        }
    }
    
    /**
     * Check if TA-Lib is available
     */
    public function isAvailable(): bool
    {
        return $this->taLibAvailable;
    }
    
    /**
     * Ensure TA-Lib is available, throw exception if not
     */
    private function ensureAvailable(): void
    {
        if (!$this->taLibAvailable) {
            throw new RuntimeException(
                'TA-Lib trader extension not installed. ' .
                'Please install: pecl install trader'
            );
        }
    }
    
    // ============================================================================
    // OVERLAP STUDIES (Moving Averages & Bands)
    // ============================================================================
    
    /**
     * Simple Moving Average (SMA)
     * 
     * @param array $data Price data
     * @param int $period Time period (default: 30)
     * @return array SMA values
     */
    public function sma(array $data, int $period = 30): array
    {
        $this->ensureAvailable();
        $cacheKey = $this->buildCacheKey('SMA', ['period' => $period], $data);
        
        if ($cached = $this->getCached($cacheKey)) {
            return $cached;
        }
        
        $result = trader_sma($data, $period);
        $this->setCached($cacheKey, $result);
        
        return $result;
    }
    
    /**
     * Exponential Moving Average (EMA)
     * 
     * @param array $data Price data
     * @param int $period Time period (default: 30)
     * @return array EMA values
     */
    public function ema(array $data, int $period = 30): array
    {
        $this->ensureAvailable();
        $cacheKey = $this->buildCacheKey('EMA', ['period' => $period], $data);
        
        if ($cached = $this->getCached($cacheKey)) {
            return $cached;
        }
        
        $result = trader_ema($data, $period);
        $this->setCached($cacheKey, $result);
        
        return $result;
    }
    
    /**
     * Weighted Moving Average (WMA)
     * 
     * @param array $data Price data
     * @param int $period Time period (default: 30)
     * @return array WMA values
     */
    public function wma(array $data, int $period = 30): array
    {
        $this->ensureAvailable();
        $cacheKey = $this->buildCacheKey('WMA', ['period' => $period], $data);
        
        if ($cached = $this->getCached($cacheKey)) {
            return $cached;
        }
        
        $result = trader_wma($data, $period);
        $this->setCached($cacheKey, $result);
        
        return $result;
    }
    
    /**
     * Double Exponential Moving Average (DEMA)
     * 
     * @param array $data Price data
     * @param int $period Time period (default: 30)
     * @return array DEMA values
     */
    public function dema(array $data, int $period = 30): array
    {
        $this->ensureAvailable();
        $result = trader_dema($data, $period);
        return $result;
    }
    
    /**
     * Triple Exponential Moving Average (TEMA)
     * 
     * @param array $data Price data
     * @param int $period Time period (default: 30)
     * @return array TEMA values
     */
    public function tema(array $data, int $period = 30): array
    {
        $this->ensureAvailable();
        $result = trader_tema($data, $period);
        return $result;
    }
    
    /**
     * Bollinger Bands
     * 
     * @param array $data Price data
     * @param int $period Time period (default: 20)
     * @param float $nbDevUp Upper deviation (default: 2.0)
     * @param float $nbDevDn Lower deviation (default: 2.0)
     * @param int $maType Moving average type (default: SMA)
     * @return array [upper_band, middle_band, lower_band]
     */
    public function bollingerBands(
        array $data,
        int $period = 20,
        float $nbDevUp = 2.0,
        float $nbDevDn = 2.0,
        int $maType = self::MA_TYPE_SMA
    ): array {
        $this->ensureAvailable();
        $cacheKey = $this->buildCacheKey('BBANDS', [
            'period' => $period,
            'up' => $nbDevUp,
            'dn' => $nbDevDn,
            'ma' => $maType
        ], $data);
        
        if ($cached = $this->getCached($cacheKey)) {
            return $cached;
        }
        
        $result = trader_bbands($data, $period, $nbDevUp, $nbDevDn, $maType);
        $this->setCached($cacheKey, $result);
        
        return [
            'upper' => $result[0],
            'middle' => $result[1],
            'lower' => $result[2]
        ];
    }
    
    /**
     * Parabolic SAR (Stop and Reverse)
     * 
     * @param array $high High prices
     * @param array $low Low prices
     * @param float $acceleration Acceleration factor (default: 0.02)
     * @param float $maximum Maximum acceleration (default: 0.2)
     * @return array SAR values
     */
    public function parabolicSAR(
        array $high,
        array $low,
        float $acceleration = 0.02,
        float $maximum = 0.2
    ): array {
        $this->ensureAvailable();
        $result = trader_sar($high, $low, $acceleration, $maximum);
        return $result;
    }
    
    // ============================================================================
    // MOMENTUM INDICATORS
    // ============================================================================
    
    /**
     * Relative Strength Index (RSI)
     * 
     * @param array $data Price data
     * @param int $period Time period (default: 14)
     * @return array RSI values (0-100)
     */
    public function rsi(array $data, int $period = 14): array
    {
        $this->ensureAvailable();
        $cacheKey = $this->buildCacheKey('RSI', ['period' => $period], $data);
        
        if ($cached = $this->getCached($cacheKey)) {
            return $cached;
        }
        
        $result = trader_rsi($data, $period);
        $this->setCached($cacheKey, $result);
        
        return $result;
    }
    
    /**
     * MACD (Moving Average Convergence/Divergence)
     * 
     * @param array $data Price data
     * @param int $fastPeriod Fast EMA period (default: 12)
     * @param int $slowPeriod Slow EMA period (default: 26)
     * @param int $signalPeriod Signal line period (default: 9)
     * @return array [macd, signal, histogram]
     */
    public function macd(
        array $data,
        int $fastPeriod = 12,
        int $slowPeriod = 26,
        int $signalPeriod = 9
    ): array {
        $this->ensureAvailable();
        $cacheKey = $this->buildCacheKey('MACD', [
            'fast' => $fastPeriod,
            'slow' => $slowPeriod,
            'signal' => $signalPeriod
        ], $data);
        
        if ($cached = $this->getCached($cacheKey)) {
            return $cached;
        }
        
        $result = trader_macd($data, $fastPeriod, $slowPeriod, $signalPeriod);
        $this->setCached($cacheKey, $result);
        
        return [
            'macd' => $result[0],
            'signal' => $result[1],
            'histogram' => $result[2]
        ];
    }
    
    /**
     * Stochastic Oscillator
     * 
     * @param array $high High prices
     * @param array $low Low prices
     * @param array $close Close prices
     * @param int $fastKPeriod Fast %K period (default: 5)
     * @param int $slowKPeriod Slow %K period (default: 3)
     * @param int $slowKMAType Slow %K MA type (default: SMA)
     * @param int $slowDPeriod Slow %D period (default: 3)
     * @param int $slowDMAType Slow %D MA type (default: SMA)
     * @return array [slowK, slowD]
     */
    public function stochastic(
        array $high,
        array $low,
        array $close,
        int $fastKPeriod = 5,
        int $slowKPeriod = 3,
        int $slowKMAType = self::MA_TYPE_SMA,
        int $slowDPeriod = 3,
        int $slowDMAType = self::MA_TYPE_SMA
    ): array {
        $this->ensureAvailable();
        
        $result = trader_stoch(
            $high, $low, $close,
            $fastKPeriod, $slowKPeriod, $slowKMAType,
            $slowDPeriod, $slowDMAType
        );
        
        return [
            'slowK' => $result[0],
            'slowD' => $result[1]
        ];
    }
    
    /**
     * Commodity Channel Index (CCI)
     * 
     * @param array $high High prices
     * @param array $low Low prices
     * @param array $close Close prices
     * @param int $period Time period (default: 14)
     * @return array CCI values
     */
    public function cci(array $high, array $low, array $close, int $period = 14): array
    {
        $this->ensureAvailable();
        $result = trader_cci($high, $low, $close, $period);
        return $result;
    }
    
    /**
     * Money Flow Index (MFI)
     * 
     * @param array $high High prices
     * @param array $low Low prices
     * @param array $close Close prices
     * @param array $volume Volume
     * @param int $period Time period (default: 14)
     * @return array MFI values (0-100)
     */
    public function mfi(
        array $high,
        array $low,
        array $close,
        array $volume,
        int $period = 14
    ): array {
        $this->ensureAvailable();
        $result = trader_mfi($high, $low, $close, $volume, $period);
        return $result;
    }
    
    /**
     * Rate of Change (ROC)
     * 
     * @param array $data Price data
     * @param int $period Time period (default: 10)
     * @return array ROC values (percentage)
     */
    public function roc(array $data, int $period = 10): array
    {
        $this->ensureAvailable();
        $result = trader_roc($data, $period);
        return $result;
    }
    
    /**
     * Momentum
     * 
     * @param array $data Price data
     * @param int $period Time period (default: 10)
     * @return array Momentum values
     */
    public function momentum(array $data, int $period = 10): array
    {
        $this->ensureAvailable();
        $result = trader_mom($data, $period);
        return $result;
    }
    
    /**
     * Williams %R
     * 
     * @param array $high High prices
     * @param array $low Low prices
     * @param array $close Close prices
     * @param int $period Time period (default: 14)
     * @return array Williams %R values (-100 to 0)
     */
    public function williamsR(array $high, array $low, array $close, int $period = 14): array
    {
        $this->ensureAvailable();
        $result = trader_willr($high, $low, $close, $period);
        return $result;
    }
    
    // ============================================================================
    // VOLATILITY INDICATORS
    // ============================================================================
    
    /**
     * Average True Range (ATR)
     * 
     * @param array $high High prices
     * @param array $low Low prices
     * @param array $close Close prices
     * @param int $period Time period (default: 14)
     * @return array ATR values
     */
    public function atr(array $high, array $low, array $close, int $period = 14): array
    {
        $this->ensureAvailable();
        $cacheKey = $this->buildCacheKey('ATR', ['period' => $period], array_merge($high, $low, $close));
        
        if ($cached = $this->getCached($cacheKey)) {
            return $cached;
        }
        
        $result = trader_atr($high, $low, $close, $period);
        $this->setCached($cacheKey, $result);
        
        return $result;
    }
    
    /**
     * Normalized Average True Range (NATR)
     * 
     * @param array $high High prices
     * @param array $low Low prices
     * @param array $close Close prices
     * @param int $period Time period (default: 14)
     * @return array NATR values (percentage)
     */
    public function natr(array $high, array $low, array $close, int $period = 14): array
    {
        $this->ensureAvailable();
        $result = trader_natr($high, $low, $close, $period);
        return $result;
    }
    
    /**
     * True Range
     * 
     * @param array $high High prices
     * @param array $low Low prices
     * @param array $close Close prices
     * @return array True Range values
     */
    public function trueRange(array $high, array $low, array $close): array
    {
        $this->ensureAvailable();
        $result = trader_trange($high, $low, $close);
        return $result;
    }
    
    /**
     * Standard Deviation
     * 
     * @param array $data Price data
     * @param int $period Time period (default: 20)
     * @param float $nbDev Number of deviations (default: 1.0)
     * @return array Standard deviation values
     */
    public function stdDev(array $data, int $period = 20, float $nbDev = 1.0): array
    {
        $this->ensureAvailable();
        $result = trader_stddev($data, $period, $nbDev);
        return $result;
    }
    
    // ============================================================================
    // VOLUME INDICATORS
    // ============================================================================
    
    /**
     * On Balance Volume (OBV)
     * 
     * @param array $close Close prices
     * @param array $volume Volume
     * @return array OBV values
     */
    public function obv(array $close, array $volume): array
    {
        $this->ensureAvailable();
        $cacheKey = $this->buildCacheKey('OBV', [], array_merge($close, $volume));
        
        if ($cached = $this->getCached($cacheKey)) {
            return $cached;
        }
        
        $result = trader_obv($close, $volume);
        $this->setCached($cacheKey, $result);
        
        return $result;
    }
    
    /**
     * Chaikin A/D Line
     * 
     * @param array $high High prices
     * @param array $low Low prices
     * @param array $close Close prices
     * @param array $volume Volume
     * @return array A/D Line values
     */
    public function adLine(array $high, array $low, array $close, array $volume): array
    {
        $this->ensureAvailable();
        $result = trader_ad($high, $low, $close, $volume);
        return $result;
    }
    
    /**
     * Chaikin A/D Oscillator
     * 
     * @param array $high High prices
     * @param array $low Low prices
     * @param array $close Close prices
     * @param array $volume Volume
     * @param int $fastPeriod Fast period (default: 3)
     * @param int $slowPeriod Slow period (default: 10)
     * @return array ADOSC values
     */
    public function adOscillator(
        array $high,
        array $low,
        array $close,
        array $volume,
        int $fastPeriod = 3,
        int $slowPeriod = 10
    ): array {
        $this->ensureAvailable();
        $result = trader_adosc($high, $low, $close, $volume, $fastPeriod, $slowPeriod);
        return $result;
    }
    
    // ============================================================================
    // TREND INDICATORS
    // ============================================================================
    
    /**
     * Average Directional Movement Index (ADX)
     * 
     * @param array $high High prices
     * @param array $low Low prices
     * @param array $close Close prices
     * @param int $period Time period (default: 14)
     * @return array ADX values (0-100)
     */
    public function adx(array $high, array $low, array $close, int $period = 14): array
    {
        $this->ensureAvailable();
        $cacheKey = $this->buildCacheKey('ADX', ['period' => $period], array_merge($high, $low, $close));
        
        if ($cached = $this->getCached($cacheKey)) {
            return $cached;
        }
        
        $result = trader_adx($high, $low, $close, $period);
        $this->setCached($cacheKey, $result);
        
        return $result;
    }
    
    /**
     * ADX with +DI and -DI
     * 
     * @param array $high High prices
     * @param array $low Low prices
     * @param array $close Close prices
     * @param int $period Time period (default: 14)
     * @return array [adx, plusDI, minusDI]
     */
    public function adxr(array $high, array $low, array $close, int $period = 14): array
    {
        $this->ensureAvailable();
        
        $adx = trader_adx($high, $low, $close, $period);
        $plusDI = trader_plus_di($high, $low, $close, $period);
        $minusDI = trader_minus_di($high, $low, $close, $period);
        
        return [
            'adx' => $adx,
            'plusDI' => $plusDI,
            'minusDI' => $minusDI
        ];
    }
    
    /**
     * Aroon Oscillator
     * 
     * @param array $high High prices
     * @param array $low Low prices
     * @param int $period Time period (default: 14)
     * @return array [aroonDown, aroonUp]
     */
    public function aroon(array $high, array $low, int $period = 14): array
    {
        $this->ensureAvailable();
        $result = trader_aroon($high, $low, $period);
        
        return [
            'down' => $result[0],
            'up' => $result[1]
        ];
    }
    
    /**
     * Aroon Oscillator (Up - Down)
     * 
     * @param array $high High prices
     * @param array $low Low prices
     * @param int $period Time period (default: 14)
     * @return array Aroon Oscillator values (-100 to +100)
     */
    public function aroonOscillator(array $high, array $low, int $period = 14): array
    {
        $this->ensureAvailable();
        $result = trader_aroonosc($high, $low, $period);
        return $result;
    }
    
    // ============================================================================
    // STATISTIC FUNCTIONS
    // ============================================================================
    
    /**
     * Beta (Market Correlation)
     * 
     * @param array $data0 Stock prices
     * @param array $data1 Market prices
     * @param int $period Time period (default: 5)
     * @return array Beta values
     */
    public function beta(array $data0, array $data1, int $period = 5): array
    {
        $this->ensureAvailable();
        $result = trader_beta($data0, $data1, $period);
        return $result;
    }
    
    /**
     * Pearson Correlation Coefficient
     * 
     * @param array $data0 First dataset
     * @param array $data1 Second dataset
     * @param int $period Time period (default: 30)
     * @return array Correlation values (-1 to +1)
     */
    public function correlation(array $data0, array $data1, int $period = 30): array
    {
        $this->ensureAvailable();
        $result = trader_correl($data0, $data1, $period);
        return $result;
    }
    
    /**
     * Linear Regression
     * 
     * @param array $data Price data
     * @param int $period Time period (default: 14)
     * @return array Linear regression values
     */
    public function linearReg(array $data, int $period = 14): array
    {
        $this->ensureAvailable();
        $result = trader_linearreg($data, $period);
        return $result;
    }
    
    /**
     * Linear Regression Slope
     * 
     * @param array $data Price data
     * @param int $period Time period (default: 14)
     * @return array Slope values
     */
    public function linearRegSlope(array $data, int $period = 14): array
    {
        $this->ensureAvailable();
        $result = trader_linearreg_slope($data, $period);
        return $result;
    }
    
    // ============================================================================
    // PRICE TRANSFORM
    // ============================================================================
    
    /**
     * Average Price
     * 
     * @param array $open Open prices
     * @param array $high High prices
     * @param array $low Low prices
     * @param array $close Close prices
     * @return array Average price values
     */
    public function avgPrice(array $open, array $high, array $low, array $close): array
    {
        $this->ensureAvailable();
        $result = trader_avgprice($open, $high, $low, $close);
        return $result;
    }
    
    /**
     * Median Price
     * 
     * @param array $high High prices
     * @param array $low Low prices
     * @return array Median price values
     */
    public function medPrice(array $high, array $low): array
    {
        $this->ensureAvailable();
        $result = trader_medprice($high, $low);
        return $result;
    }
    
    /**
     * Typical Price
     * 
     * @param array $high High prices
     * @param array $low Low prices
     * @param array $close Close prices
     * @return array Typical price values
     */
    public function typPrice(array $high, array $low, array $close): array
    {
        $this->ensureAvailable();
        $result = trader_typprice($high, $low, $close);
        return $result;
    }
    
    /**
     * Weighted Close Price
     * 
     * @param array $high High prices
     * @param array $low Low prices
     * @param array $close Close prices
     * @return array Weighted close price values
     */
    public function wclPrice(array $high, array $low, array $close): array
    {
        $this->ensureAvailable();
        $result = trader_wclprice($high, $low, $close);
        return $result;
    }
    
    // ============================================================================
    // UTILITY METHODS
    // ============================================================================
    
    /**
     * Get all available TA-Lib functions
     * 
     * @return array List of function names
     */
    public function getAvailableFunctions(): array
    {
        if (!$this->taLibAvailable) {
            return [];
        }
        
        return get_extension_funcs('trader');
    }
    
    /**
     * Get unstable period for an indicator
     * 
     * @param int $functionId TA-Lib function ID
     * @return int Unstable period
     */
    public function getUnstablePeriod(int $functionId): int
    {
        if (!$this->taLibAvailable) {
            return 0;
        }
        
        return trader_get_unstable_period($functionId);
    }
    
    /**
     * Calculate multiple indicators at once
     * 
     * @param array $data OHLCV data
     * @param array $indicators List of indicators to calculate
     * @return array Calculated indicators
     */
    public function calculateMultiple(array $data, array $indicators): array
    {
        $results = [];
        
        foreach ($indicators as $indicator => $params) {
            try {
                $results[$indicator] = $this->calculate($indicator, $data, $params ?? []);
            } catch (\Exception $e) {
                error_log("Failed to calculate {$indicator}: " . $e->getMessage());
                $results[$indicator] = null;
            }
        }
        
        return $results;
    }
    
    /**
     * Generic indicator calculator
     * 
     * @param string $indicator Indicator name
     * @param array $data Price data
     * @param array $params Parameters
     * @return array|null Result or null if failed
     */
    private function calculate(string $indicator, array $data, array $params): ?array
    {
        $indicator = strtolower($indicator);
        
        return match($indicator) {
            'sma' => $this->sma($data['close'] ?? $data, $params['period'] ?? 30),
            'ema' => $this->ema($data['close'] ?? $data, $params['period'] ?? 30),
            'rsi' => $this->rsi($data['close'] ?? $data, $params['period'] ?? 14),
            'macd' => $this->macd($data['close'] ?? $data),
            'bbands' => $this->bollingerBands($data['close'] ?? $data),
            'atr' => $this->atr($data['high'], $data['low'], $data['close'], $params['period'] ?? 14),
            'adx' => $this->adx($data['high'], $data['low'], $data['close'], $params['period'] ?? 14),
            'obv' => $this->obv($data['close'], $data['volume']),
            'stoch' => $this->stochastic($data['high'], $data['low'], $data['close']),
            default => null
        };
    }
    
    // ============================================================================
    // CACHING
    // ============================================================================
    
    /**
     * Build cache key
     */
    private function buildCacheKey(string $indicator, array $params, array $data): string
    {
        $dataHash = md5(serialize(array_slice($data, -50))); // Hash last 50 points
        $paramsHash = md5(serialize($params));
        
        return "indicator:{$indicator}:{$paramsHash}:{$dataHash}";
    }
    
    /**
     * Get cached result
     */
    private function getCached(string $key): ?array
    {
        if (!isset($this->cache[$key])) {
            return null;
        }
        
        $cached = $this->cache[$key];
        
        if (time() - $cached['timestamp'] > $this->cacheTTL) {
            unset($this->cache[$key]);
            return null;
        }
        
        return $cached['data'];
    }
    
    /**
     * Set cached result
     */
    private function setCached(string $key, array $data): void
    {
        $this->cache[$key] = [
            'data' => $data,
            'timestamp' => time()
        ];
        
        // Limit cache size
        if (count($this->cache) > 100) {
            $this->cache = array_slice($this->cache, -50, null, true);
        }
    }
    
    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
    
    /**
     * Set cache TTL
     */
    public function setCacheTTL(int $seconds): void
    {
        $this->cacheTTL = $seconds;
    }
}
