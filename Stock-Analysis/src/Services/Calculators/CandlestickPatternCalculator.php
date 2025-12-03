<?php

namespace App\Services\Calculators;

use InvalidArgumentException;

/**
 * Candlestick Pattern Calculator
 * Provides detection and analysis of candlestick patterns using TA-Lib
 * 
 * Returns pattern values as integers:
 * - +100: Bullish pattern detected
 * - -100: Bearish pattern detected
 * - 0: No pattern detected
 * 
 * @author WealthSystem
 * @version 1.0
 */
class CandlestickPatternCalculator
{
    /**
     * Pattern reliability classifications based on historical performance
     */
    private const HIGH_RELIABILITY = [
        'ENGULFING', 'MORNING_STAR', 'EVENING_STAR', 'THREE_WHITE_SOLDIERS',
        'THREE_BLACK_CROWS', 'HAMMER', 'SHOOTING_STAR', 'PIERCING', 'DARK_CLOUD_COVER'
    ];
    
    private const MEDIUM_RELIABILITY = [
        'DOJI', 'HARAMI', 'INVERTED_HAMMER', 'HANGING_MAN', 'BELT_HOLD',
        'MARUBOZU', 'SPINNING_TOP', 'DRAGONFLY_DOJI', 'GRAVESTONE_DOJI'
    ];
    
    /**
     * All 63 candlestick patterns available in TA-Lib
     */
    private const ALL_PATTERNS = [
        // Bullish Reversal Patterns
        'HAMMER', 'INVERTED_HAMMER', 'ENGULFING', 'PIERCING', 
        'MORNING_STAR', 'MORNING_DOJI_STAR', 'THREE_WHITE_SOLDIERS',
        'THREE_INSIDE', 'THREE_OUTSIDE', 'ABANDONED_BABY',
        'BREAKAWAY', 'CONCEALING_BABY_SWALLOW', 'KICKING',
        
        // Bearish Reversal Patterns
        'SHOOTING_STAR', 'HANGING_MAN', 'DARK_CLOUD_COVER',
        'EVENING_STAR', 'EVENING_DOJI_STAR', 'THREE_BLACK_CROWS',
        'IDENTICAL_THREE_CROWS', 'UPSIDE_GAP_TWO_CROWS',
        
        // Indecision Patterns
        'DOJI', 'DOJI_STAR', 'DRAGONFLY_DOJI', 'GRAVESTONE_DOJI',
        'LONG_LEGGED_DOJI', 'RICKSHAW_MAN', 'SPINNING_TOP',
        'HIGH_WAVE', 'HARAMI', 'HARAMI_CROSS',
        
        // Continuation Patterns
        'MARUBOZU', 'BELT_HOLD', 'SEPARATING_LINES', 'THRUSTING',
        'ON_NECK', 'IN_NECK', 'GAP_SIDE_SIDE_WHITE',
        
        // Complex Patterns
        'THREE_LINE_STRIKE', 'ADVANCE_BLOCK', 'STICK_SANDWICH',
        'HOMING_PIGEON', 'LADDER_BOTTOM', 'MATCHING_LOW',
        'MAT_HOLD', 'RISE_FALL_THREE_METHODS', 'TAKURI',
        'TASUKI_GAP', 'TRISTAR', 'UNIQUE_THREE_RIVER',
        'UPSIDE_DOWNSIDE_GAP_THREE_METHODS', 'HIKKAKE',
        'HIKKAKE_MOD', 'STALLED_PATTERN', 'TWO_CROWS',
        'COUNTERATTACK', 'KICKING_BY_LENGTH'
    ];
    
    /**
     * Detect all candlestick patterns in the provided OHLC data
     * 
     * @param array $data OHLC data with keys: open, high, low, close
     * @return array Detected patterns with values and metadata
     * @throws InvalidArgumentException If data is invalid
     */
    public function detectAllPatterns(array $data): array
    {
        $this->validateData($data);
        
        $detected = [];
        
        foreach (self::ALL_PATTERNS as $pattern) {
            $result = $this->detectPattern($pattern, $data);
            $lastIndex = count($result) - 1;
            
            if ($result[$lastIndex] != 0) {
                $detected[] = [
                    'pattern' => $pattern,
                    'value' => $result[$lastIndex],
                    'direction' => $result[$lastIndex] > 0 ? 'BULLISH' : 'BEARISH',
                    'reliability' => $this->getReliability($pattern),
                    'full_results' => $result
                ];
            }
        }
        
        return $detected;
    }
    
    /**
     * Detect a specific candlestick pattern
     * 
     * @param string $patternName Name of pattern to detect
     * @param array $data OHLC data
     * @return array Pattern values for each data point
     * @throws InvalidArgumentException If pattern unknown or data invalid
     */
    public function detectPattern(string $patternName, array $data): array
    {
        $this->validateData($data);
        
        $pattern = strtoupper($patternName);
        
        if (!in_array($pattern, self::ALL_PATTERNS)) {
            throw new InvalidArgumentException("Unknown pattern: {$patternName}");
        }
        
        $open = $data['open'];
        $high = $data['high'];
        $low = $data['low'];
        $close = $data['close'];
        
        // Map pattern names to trader functions
        return match($pattern) {
            // Bullish Reversal
            'HAMMER' => trader_cdlhammer($open, $high, $low, $close),
            'INVERTED_HAMMER' => trader_cdlinvertedhammer($open, $high, $low, $close),
            'ENGULFING' => trader_cdlengulfing($open, $high, $low, $close),
            'PIERCING' => trader_cdlpiercing($open, $high, $low, $close),
            'MORNING_STAR' => trader_cdlmorningstar($open, $high, $low, $close),
            'MORNING_DOJI_STAR' => trader_cdlmorningdojistar($open, $high, $low, $close),
            'THREE_WHITE_SOLDIERS' => trader_cdl3whitesoldiers($open, $high, $low, $close),
            'THREE_INSIDE' => trader_cdl3inside($open, $high, $low, $close),
            'THREE_OUTSIDE' => trader_cdl3outside($open, $high, $low, $close),
            'ABANDONED_BABY' => trader_cdlabandonedbaby($open, $high, $low, $close),
            'BREAKAWAY' => trader_cdlbreakaway($open, $high, $low, $close),
            'CONCEALING_BABY_SWALLOW' => trader_cdlconcealbabyswall($open, $high, $low, $close),
            'KICKING' => trader_cdlkicking($open, $high, $low, $close),
            'KICKING_BY_LENGTH' => trader_cdlkickingbylength($open, $high, $low, $close),
            
            // Bearish Reversal
            'SHOOTING_STAR' => trader_cdlshootingstar($open, $high, $low, $close),
            'HANGING_MAN' => trader_cdlhangingman($open, $high, $low, $close),
            'DARK_CLOUD_COVER' => trader_cdldarkcloudcover($open, $high, $low, $close),
            'EVENING_STAR' => trader_cdleveningstar($open, $high, $low, $close),
            'EVENING_DOJI_STAR' => trader_cdleveningdojistar($open, $high, $low, $close),
            'THREE_BLACK_CROWS' => trader_cdl3blackcrows($open, $high, $low, $close),
            'IDENTICAL_THREE_CROWS' => trader_cdlidentical3crows($open, $high, $low, $close),
            'UPSIDE_GAP_TWO_CROWS' => trader_cdlupsidegap2crows($open, $high, $low, $close),
            'TWO_CROWS' => trader_cdl2crows($open, $high, $low, $close),
            
            // Indecision Patterns
            'DOJI' => trader_cdldoji($open, $high, $low, $close),
            'DOJI_STAR' => trader_cdldojistar($open, $high, $low, $close),
            'DRAGONFLY_DOJI' => trader_cdldragonflydoji($open, $high, $low, $close),
            'GRAVESTONE_DOJI' => trader_cdlgravestonedoji($open, $high, $low, $close),
            'LONG_LEGGED_DOJI' => trader_cdllongleggeddoji($open, $high, $low, $close),
            'RICKSHAW_MAN' => trader_cdlrickshawman($open, $high, $low, $close),
            'SPINNING_TOP' => trader_cdlspinningtop($open, $high, $low, $close),
            'HIGH_WAVE' => trader_cdlhighwave($open, $high, $low, $close),
            'HARAMI' => trader_cdlharami($open, $high, $low, $close),
            'HARAMI_CROSS' => trader_cdlharamicross($open, $high, $low, $close),
            
            // Continuation Patterns
            'MARUBOZU' => trader_cdlmarubozu($open, $high, $low, $close),
            'BELT_HOLD' => trader_cdlbelthold($open, $high, $low, $close),
            'SEPARATING_LINES' => trader_cdlseparatinglines($open, $high, $low, $close),
            'THRUSTING' => trader_cdlthrusting($open, $high, $low, $close),
            'ON_NECK' => trader_cdlonneck($open, $high, $low, $close),
            'IN_NECK' => trader_cdlinneck($open, $high, $low, $close),
            'GAP_SIDE_SIDE_WHITE' => trader_cdlgapsidesidewhite($open, $high, $low, $close),
            
            // Complex Patterns
            'THREE_LINE_STRIKE' => trader_cdl3linestrike($open, $high, $low, $close),
            'ADVANCE_BLOCK' => trader_cdladvanceblock($open, $high, $low, $close),
            'STICK_SANDWICH' => trader_cdlsticksandwich($open, $high, $low, $close),
            'HOMING_PIGEON' => trader_cdlhomingpigeon($open, $high, $low, $close),
            'LADDER_BOTTOM' => trader_cdlladderbottom($open, $high, $low, $close),
            'MATCHING_LOW' => trader_cdlmatchinglow($open, $high, $low, $close),
            'MAT_HOLD' => trader_cdlmathold($open, $high, $low, $close),
            'RISE_FALL_THREE_METHODS' => trader_cdlrisefall3methods($open, $high, $low, $close),
            'TAKURI' => trader_cdltakuri($open, $high, $low, $close),
            'TASUKI_GAP' => trader_cdltasukigap($open, $high, $low, $close),
            'TRISTAR' => trader_cdltristar($open, $high, $low, $close),
            'UNIQUE_THREE_RIVER' => trader_cdlunique3river($open, $high, $low, $close),
            'UPSIDE_DOWNSIDE_GAP_THREE_METHODS' => trader_cdlxsidegap3methods($open, $high, $low, $close),
            'HIKKAKE' => trader_cdlhikkake($open, $high, $low, $close),
            'HIKKAKE_MOD' => trader_cdlhikkakemod($open, $high, $low, $close),
            'STALLED_PATTERN' => trader_cdlstalledpattern($open, $high, $low, $close),
            'COUNTERATTACK' => trader_cdlcounterattack($open, $high, $low, $close),
            
            default => throw new InvalidArgumentException("Pattern not yet implemented: {$pattern}")
        };
    }
    
    /**
     * Get only bullish patterns from data
     * 
     * @param array $data OHLC data
     * @return array Detected bullish patterns
     */
    public function getBullishPatterns(array $data): array
    {
        $all = $this->detectAllPatterns($data);
        
        return array_filter($all, function($pattern) {
            return $pattern['direction'] === 'BULLISH';
        });
    }
    
    /**
     * Get only bearish patterns from data
     * 
     * @param array $data OHLC data
     * @return array Detected bearish patterns
     */
    public function getBearishPatterns(array $data): array
    {
        $all = $this->detectAllPatterns($data);
        
        return array_filter($all, function($pattern) {
            return $pattern['direction'] === 'BEARISH';
        });
    }
    
    /**
     * Get pattern strength/confidence score
     * 
     * @param string $patternName Name of pattern
     * @param array $data OHLC data
     * @return int Strength from -100 to +100
     */
    public function getPatternStrength(string $patternName, array $data): int
    {
        $result = $this->detectPattern($patternName, $data);
        $lastIndex = count($result) - 1;
        
        return $result[$lastIndex];
    }
    
    /**
     * Get reliability classification for a pattern
     * 
     * @param string $pattern Pattern name
     * @return string HIGH, MEDIUM, or LOW
     */
    public function getReliability(string $pattern): string
    {
        $pattern = strtoupper($pattern);
        
        if (in_array($pattern, self::HIGH_RELIABILITY)) {
            return 'HIGH';
        }
        
        if (in_array($pattern, self::MEDIUM_RELIABILITY)) {
            return 'MEDIUM';
        }
        
        return 'LOW';
    }
    
    /**
     * Generate trading signal from pattern detection
     * 
     * @param array $data OHLC data with volume
     * @param array $options Configuration options
     * @return array|null Trading signal or null if no signal
     */
    public function generateSignal(array $data, array $options = []): ?array
    {
        $this->validateData($data);
        
        $minReliability = $options['min_reliability'] ?? 'MEDIUM';
        $requireVolumeConfirmation = $options['require_volume'] ?? false;
        
        // Detect all patterns
        $patterns = $this->detectAllPatterns($data);
        
        // Filter by reliability
        $filtered = array_filter($patterns, function($pattern) use ($minReliability) {
            if ($minReliability === 'HIGH') {
                return $pattern['reliability'] === 'HIGH';
            } elseif ($minReliability === 'MEDIUM') {
                return in_array($pattern['reliability'], ['HIGH', 'MEDIUM']);
            }
            return true;
        });
        
        if (empty($filtered)) {
            return null;
        }
        
        // Get most recent pattern
        $pattern = reset($filtered);
        $lastIndex = count($data['close']) - 1;
        $close = $data['close'][$lastIndex];
        $low = $data['low'][$lastIndex];
        $high = $data['high'][$lastIndex];
        
        // Volume confirmation if required
        if ($requireVolumeConfirmation && isset($data['volume'])) {
            $volume = $data['volume'][$lastIndex];
            $avgVolume = array_sum(array_slice($data['volume'], -20)) / 20;
            
            if ($volume < $avgVolume * 1.2) {
                return null; // Insufficient volume
            }
        }
        
        // Calculate targets based on pattern type
        if ($pattern['direction'] === 'BULLISH') {
            $stopLoss = $low * 0.97; // 3% below low
            $target = $close + (($close - $stopLoss) * 2); // 2:1 reward/risk
            $signal = 'BUY';
        } else {
            $stopLoss = $high * 1.03; // 3% above high
            $target = $close - (($stopLoss - $close) * 2); // 2:1 reward/risk
            $signal = 'SELL';
        }
        
        $confidence = match($pattern['reliability']) {
            'HIGH' => 0.75,
            'MEDIUM' => 0.65,
            default => 0.55
        };
        
        return [
            'signal' => $signal,
            'pattern' => $pattern['pattern'],
            'direction' => $pattern['direction'],
            'confidence' => $confidence,
            'reliability' => $pattern['reliability'],
            'entry_price' => $close,
            'stop_loss' => round($stopLoss, 2),
            'take_profit' => round($target, 2),
            'risk_reward_ratio' => 2.0,
            'reasoning' => sprintf(
                '%s %s pattern detected with %s reliability',
                $pattern['direction'],
                $pattern['pattern'],
                $pattern['reliability']
            )
        ];
    }
    
    /**
     * Validate OHLC data structure
     * 
     * @param array $data Data to validate
     * @throws InvalidArgumentException If data invalid
     */
    private function validateData(array $data): void
    {
        $required = ['open', 'high', 'low', 'close'];
        
        foreach ($required as $key) {
            if (!isset($data[$key])) {
                throw new InvalidArgumentException("Missing required data: {$key}");
            }
            
            if (!is_array($data[$key])) {
                throw new InvalidArgumentException("{$key} must be an array");
            }
            
            if (count($data[$key]) < 1) {
                throw new InvalidArgumentException("{$key} array cannot be empty");
            }
        }
        
        // Validate all arrays same length
        $lengths = array_map('count', [$data['open'], $data['high'], $data['low'], $data['close']]);
        if (count(array_unique($lengths)) > 1) {
            throw new InvalidArgumentException("All OHLC arrays must have same length");
        }
    }
    
    /**
     * Get list of all supported patterns
     * 
     * @return array Pattern names
     */
    public function getSupportedPatterns(): array
    {
        return self::ALL_PATTERNS;
    }
    
    /**
     * Get pattern description and interpretation
     * 
     * @param string $pattern Pattern name
     * @return array Pattern metadata
     */
    public function getPatternInfo(string $pattern): array
    {
        $pattern = strtoupper($pattern);
        
        $descriptions = [
            'HAMMER' => [
                'type' => 'BULLISH_REVERSAL',
                'description' => 'Small body at top, long lower shadow (2x body), appears at bottom',
                'reliability' => 'HIGH',
                'confirmation' => 'Next candle closes above hammer high',
                'target' => 'Height of preceding downtrend',
                'invalidation' => 'Close below hammer low'
            ],
            'SHOOTING_STAR' => [
                'type' => 'BEARISH_REVERSAL',
                'description' => 'Small body at bottom, long upper shadow (2x body), appears at top',
                'reliability' => 'HIGH',
                'confirmation' => 'Next candle closes below shooting star low',
                'target' => 'Height of preceding uptrend',
                'invalidation' => 'Close above shooting star high'
            ],
            'ENGULFING' => [
                'type' => 'REVERSAL',
                'description' => 'Large candle completely engulfs previous candle body',
                'reliability' => 'HIGH',
                'confirmation' => 'Volume above average',
                'target' => '2x the engulfing candle range',
                'invalidation' => 'Close back inside engulfing candle'
            ],
            'DOJI' => [
                'type' => 'INDECISION',
                'description' => 'Open and close at same price, indicates indecision',
                'reliability' => 'MEDIUM',
                'confirmation' => 'Needs confirmation from next candle',
                'target' => 'Direction of breakout',
                'invalidation' => 'None (neutral pattern)'
            ],
            'MORNING_STAR' => [
                'type' => 'BULLISH_REVERSAL',
                'description' => 'Three-candle pattern: down, small body/doji, up',
                'reliability' => 'HIGH',
                'confirmation' => 'Third candle closes above midpoint of first',
                'target' => 'Height of first candle',
                'invalidation' => 'Close below pattern low'
            ],
            'EVENING_STAR' => [
                'type' => 'BEARISH_REVERSAL',
                'description' => 'Three-candle pattern: up, small body/doji, down',
                'reliability' => 'HIGH',
                'confirmation' => 'Third candle closes below midpoint of first',
                'target' => 'Height of first candle',
                'invalidation' => 'Close above pattern high'
            ],
            'THREE_WHITE_SOLDIERS' => [
                'type' => 'BULLISH_CONTINUATION',
                'description' => 'Three consecutive bullish candles with higher closes',
                'reliability' => 'HIGH',
                'confirmation' => 'Each candle opens within previous body',
                'target' => 'Height of pattern x 1.5',
                'invalidation' => 'Close below pattern low'
            ],
            'THREE_BLACK_CROWS' => [
                'type' => 'BEARISH_CONTINUATION',
                'description' => 'Three consecutive bearish candles with lower closes',
                'reliability' => 'HIGH',
                'confirmation' => 'Each candle opens within previous body',
                'target' => 'Height of pattern x 1.5',
                'invalidation' => 'Close above pattern high'
            ]
        ];
        
        return $descriptions[$pattern] ?? [
            'type' => 'UNKNOWN',
            'description' => 'Pattern description not available',
            'reliability' => $this->getReliability($pattern),
            'confirmation' => 'Refer to technical analysis literature',
            'target' => 'Varies by market context',
            'invalidation' => 'Varies by pattern'
        ];
    }
}
