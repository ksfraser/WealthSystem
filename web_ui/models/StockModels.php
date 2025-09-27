<?php
/**
 * Stock Model Classes
 * 
 * Object-oriented models for stock data with validation and business logic
 */

/**
 * Stock Information Model
 */
class Stock {
    public $id;
    public $symbol;
    public $name;
    public $exchange;
    public $sector;
    public $industry;
    public $market_cap;
    public $description;
    public $country;
    public $currency;
    public $is_active;
    public $created_at;
    public $updated_at;
    
    public function __construct(array $data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * Validate stock data
     */
    public function validate(): array {
        $errors = [];
        
        if (empty($this->symbol)) {
            $errors[] = 'Symbol is required';
        } elseif (!preg_match('/^[A-Z0-9.-]{1,10}$/', $this->symbol)) {
            $errors[] = 'Symbol must be 1-10 uppercase characters, numbers, dots, or dashes';
        }
        
        if (empty($this->name)) {
            $errors[] = 'Company name is required';
        }
        
        if (empty($this->exchange)) {
            $errors[] = 'Exchange is required';
        }
        
        return $errors;
    }
    
    /**
     * Convert to array
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'name' => $this->name,
            'exchange' => $this->exchange,
            'sector' => $this->sector,
            'industry' => $this->industry,
            'market_cap' => $this->market_cap,
            'description' => $this->description,
            'country' => $this->country,
            'currency' => $this->currency,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}

/**
 * Price Data Model
 */
class PriceData {
    public $date;
    public $open;
    public $high;
    public $low;
    public $close;
    public $adj_close;
    public $volume;
    public $split_coefficient;
    public $dividend_amount;
    public $data_source;
    public $created_at;
    public $updated_at;
    
    public function __construct(array $data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        
        // Set defaults
        $this->split_coefficient = $this->split_coefficient ?? 1.0;
        $this->dividend_amount = $this->dividend_amount ?? 0.0;
        $this->data_source = $this->data_source ?? 'yahoo';
    }
    
    /**
     * Validate price data
     */
    public function validate(): array {
        $errors = [];
        
        if (empty($this->date)) {
            $errors[] = 'Date is required';
        } elseif (!strtotime($this->date)) {
            $errors[] = 'Invalid date format';
        }
        
        if ($this->open !== null && $this->open < 0) {
            $errors[] = 'Open price cannot be negative';
        }
        
        if ($this->high !== null && $this->high < 0) {
            $errors[] = 'High price cannot be negative';
        }
        
        if ($this->low !== null && $this->low < 0) {
            $errors[] = 'Low price cannot be negative';
        }
        
        if ($this->close !== null && $this->close < 0) {
            $errors[] = 'Close price cannot be negative';
        }
        
        if ($this->volume !== null && $this->volume < 0) {
            $errors[] = 'Volume cannot be negative';
        }
        
        // Validate price relationships
        if ($this->high !== null && $this->low !== null && $this->high < $this->low) {
            $errors[] = 'High price cannot be less than low price';
        }
        
        return $errors;
    }
    
    /**
     * Calculate price change from previous close
     */
    public function calculateChange(float $previousClose): array {
        if ($previousClose <= 0 || $this->close === null) {
            return ['change' => 0, 'change_percent' => 0];
        }
        
        $change = $this->close - $previousClose;
        $changePercent = ($change / $previousClose) * 100;
        
        return [
            'change' => round($change, 2),
            'change_percent' => round($changePercent, 2)
        ];
    }
    
    /**
     * Get intraday range
     */
    public function getIntradayRange(): array {
        if ($this->high === null || $this->low === null) {
            return ['range' => 0, 'range_percent' => 0];
        }
        
        $range = $this->high - $this->low;
        $rangePercent = $this->low > 0 ? ($range / $this->low) * 100 : 0;
        
        return [
            'range' => round($range, 2),
            'range_percent' => round($rangePercent, 2)
        ];
    }
    
    /**
     * Convert to array
     */
    public function toArray(): array {
        return [
            'date' => $this->date,
            'open' => $this->open,
            'high' => $this->high,
            'low' => $this->low,
            'close' => $this->close,
            'adj_close' => $this->adj_close,
            'volume' => $this->volume,
            'split_coefficient' => $this->split_coefficient,
            'dividend_amount' => $this->dividend_amount,
            'data_source' => $this->data_source,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}

/**
 * Fundamental Data Model
 */
class FundamentalData {
    public $report_date;
    public $market_cap;
    public $enterprise_value;
    public $pe_ratio;
    public $forward_pe;
    public $peg_ratio;
    public $price_to_book;
    public $price_to_sales;
    public $ev_to_revenue;
    public $ev_to_ebitda;
    public $gross_margin;
    public $operating_margin;
    public $net_margin;
    public $return_on_equity;
    public $return_on_assets;
    public $return_on_invested_capital;
    public $debt_to_equity;
    public $current_ratio;
    public $quick_ratio;
    public $cash_ratio;
    public $revenue_growth_yoy;
    public $earnings_growth_yoy;
    public $free_cash_flow_growth;
    public $earnings_per_share;
    public $book_value_per_share;
    public $cash_per_share;
    public $revenue_per_share;
    public $dividend_yield;
    public $dividend_per_share;
    public $payout_ratio;
    public $beta;
    public $volatility_30d;
    public $analyst_rating;
    public $target_price;
    public $analyst_count;
    public $data_source;
    public $created_at;
    public $updated_at;
    
    public function __construct(array $data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        
        $this->data_source = $this->data_source ?? 'yahoo';
        $this->report_date = $this->report_date ?? date('Y-m-d');
    }
    
    /**
     * Get valuation category based on PE ratio
     */
    public function getValuationCategory(): string {
        if ($this->pe_ratio === null || $this->pe_ratio <= 0) {
            return 'Unknown';
        }
        
        if ($this->pe_ratio < 15) {
            return 'Undervalued';
        } elseif ($this->pe_ratio < 25) {
            return 'Fair Value';
        } elseif ($this->pe_ratio < 40) {
            return 'Expensive';
        } else {
            return 'Overvalued';
        }
    }
    
    /**
     * Get financial health score (0-100)
     */
    public function getFinancialHealthScore(): float {
        $score = 0;
        $components = 0;
        
        // Profitability (30% weight)
        if ($this->return_on_equity !== null) {
            $score += ($this->return_on_equity > 15 ? 30 : ($this->return_on_equity > 10 ? 20 : 10));
            $components++;
        }
        
        // Liquidity (20% weight)
        if ($this->current_ratio !== null) {
            $score += ($this->current_ratio > 2 ? 20 : ($this->current_ratio > 1.5 ? 15 : 10));
            $components++;
        }
        
        // Leverage (20% weight)
        if ($this->debt_to_equity !== null) {
            $score += ($this->debt_to_equity < 0.3 ? 20 : ($this->debt_to_equity < 0.6 ? 15 : 10));
            $components++;
        }
        
        // Growth (20% weight)
        if ($this->revenue_growth_yoy !== null) {
            $score += ($this->revenue_growth_yoy > 20 ? 20 : ($this->revenue_growth_yoy > 10 ? 15 : 10));
            $components++;
        }
        
        // Margins (10% weight)
        if ($this->net_margin !== null) {
            $score += ($this->net_margin > 15 ? 10 : ($this->net_margin > 8 ? 7 : 5));
            $components++;
        }
        
        return $components > 0 ? $score / $components * 20 : 0; // Normalize to 100
    }
    
    /**
     * Convert to array
     */
    public function toArray(): array {
        return get_object_vars($this);
    }
}

/**
 * News Item Model
 */
class NewsItem {
    public $id;
    public $news_id;
    public $headline;
    public $summary;
    public $content;
    public $author;
    public $source;
    public $source_url;
    public $published_at;
    public $sentiment_score;
    public $sentiment_label;
    public $confidence_score;
    public $category;
    public $importance;
    public $created_at;
    public $updated_at;
    
    // Valid categories
    const CATEGORIES = ['EARNINGS', 'GENERAL', 'MERGER', 'REGULATORY', 'ANALYST', 'PRODUCT', 'MANAGEMENT'];
    
    // Valid importance levels
    const IMPORTANCE_LEVELS = ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];
    
    // Valid sentiment labels
    const SENTIMENT_LABELS = ['POSITIVE', 'NEGATIVE', 'NEUTRAL'];
    
    public function __construct(array $data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        
        // Set defaults
        $this->category = $this->category ?? 'GENERAL';
        $this->importance = $this->importance ?? 'MEDIUM';
    }
    
    /**
     * Validate news item
     */
    public function validate(): array {
        $errors = [];
        
        if (empty($this->headline)) {
            $errors[] = 'Headline is required';
        }
        
        if (empty($this->source)) {
            $errors[] = 'Source is required';
        }
        
        if (empty($this->published_at)) {
            $errors[] = 'Published date is required';
        } elseif (!strtotime($this->published_at)) {
            $errors[] = 'Invalid published date format';
        }
        
        if ($this->category && !in_array($this->category, self::CATEGORIES)) {
            $errors[] = 'Invalid category';
        }
        
        if ($this->importance && !in_array($this->importance, self::IMPORTANCE_LEVELS)) {
            $errors[] = 'Invalid importance level';
        }
        
        if ($this->sentiment_label && !in_array($this->sentiment_label, self::SENTIMENT_LABELS)) {
            $errors[] = 'Invalid sentiment label';
        }
        
        if ($this->sentiment_score !== null && ($this->sentiment_score < -1 || $this->sentiment_score > 1)) {
            $errors[] = 'Sentiment score must be between -1 and 1';
        }
        
        return $errors;
    }
    
    /**
     * Get sentiment indicator
     */
    public function getSentimentIndicator(): string {
        if ($this->sentiment_score === null) {
            return '●'; // Neutral
        }
        
        if ($this->sentiment_score > 0.1) {
            return '▲'; // Positive
        } elseif ($this->sentiment_score < -0.1) {
            return '▼'; // Negative
        } else {
            return '●'; // Neutral
        }
    }
    
    /**
     * Get time ago string
     */
    public function getTimeAgo(): string {
        if (!$this->published_at) {
            return 'Unknown';
        }
        
        $timestamp = strtotime($this->published_at);
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' minutes ago';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' hours ago';
        } elseif ($diff < 604800) {
            return floor($diff / 86400) . ' days ago';
        } else {
            return date('M j, Y', $timestamp);
        }
    }
    
    /**
     * Convert to array
     */
    public function toArray(): array {
        return get_object_vars($this);
    }
}

/**
 * Technical Indicators Model
 */
class TechnicalIndicators {
    public $date;
    public $sma_20;
    public $sma_50;
    public $sma_200;
    public $ema_12;
    public $ema_26;
    public $macd;
    public $macd_signal;
    public $macd_histogram;
    public $rsi_14;
    public $rsi_30;
    public $stoch_k;
    public $stoch_d;
    public $bb_upper;
    public $bb_middle;
    public $bb_lower;
    public $bb_width;
    public $atr_14;
    public $adx_14;
    public $cci_14;
    public $williams_r;
    public $momentum_10;
    public $roc_10;
    public $obv;
    public $vwap;
    public $pivot_point;
    public $resistance_1;
    public $resistance_2;
    public $support_1;
    public $support_2;
    public $created_at;
    public $updated_at;
    
    public function __construct(array $data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * Get trend indication based on moving averages
     */
    public function getTrendIndication(): array {
        $signals = [];
        
        // SMA trend
        if ($this->sma_20 !== null && $this->sma_50 !== null) {
            if ($this->sma_20 > $this->sma_50) {
                $signals[] = 'Short-term uptrend (SMA 20 > 50)';
            } else {
                $signals[] = 'Short-term downtrend (SMA 20 < 50)';
            }
        }
        
        if ($this->sma_50 !== null && $this->sma_200 !== null) {
            if ($this->sma_50 > $this->sma_200) {
                $signals[] = 'Long-term uptrend (SMA 50 > 200)';
            } else {
                $signals[] = 'Long-term downtrend (SMA 50 < 200)';
            }
        }
        
        // RSI signals
        if ($this->rsi_14 !== null) {
            if ($this->rsi_14 > 70) {
                $signals[] = 'Overbought (RSI > 70)';
            } elseif ($this->rsi_14 < 30) {
                $signals[] = 'Oversold (RSI < 30)';
            }
        }
        
        // MACD signals
        if ($this->macd !== null && $this->macd_signal !== null) {
            if ($this->macd > $this->macd_signal) {
                $signals[] = 'Bullish momentum (MACD > Signal)';
            } else {
                $signals[] = 'Bearish momentum (MACD < Signal)';
            }
        }
        
        return $signals;
    }
    
    /**
     * Get overall technical score (0-100)
     */
    public function getTechnicalScore(float $currentPrice): float {
        $score = 50; // Neutral starting point
        $signals = 0;
        
        // Moving average signals
        if ($this->sma_20 !== null) {
            if ($currentPrice > $this->sma_20) {
                $score += 5;
            } else {
                $score -= 5;
            }
            $signals++;
        }
        
        if ($this->sma_50 !== null) {
            if ($currentPrice > $this->sma_50) {
                $score += 7;
            } else {
                $score -= 7;
            }
            $signals++;
        }
        
        if ($this->sma_200 !== null) {
            if ($currentPrice > $this->sma_200) {
                $score += 10;
            } else {
                $score -= 10;
            }
            $signals++;
        }
        
        // RSI signals
        if ($this->rsi_14 !== null) {
            if ($this->rsi_14 > 70) {
                $score -= 8; // Overbought
            } elseif ($this->rsi_14 < 30) {
                $score += 8; // Oversold
            }
            $signals++;
        }
        
        // MACD signals
        if ($this->macd !== null && $this->macd_signal !== null) {
            if ($this->macd > $this->macd_signal) {
                $score += 6; // Bullish
            } else {
                $score -= 6; // Bearish
            }
            $signals++;
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Convert to array
     */
    public function toArray(): array {
        return get_object_vars($this);
    }
}

/**
 * Analysis Results Model
 */
class AnalysisResults {
    public $id;
    public $analysis_date;
    public $fundamental_score;
    public $technical_score;
    public $momentum_score;
    public $sentiment_score;
    public $news_score;
    public $overall_score;
    public $confidence_level;
    public $recommendation;
    public $target_price;
    public $stop_loss;
    public $take_profit;
    public $risk_level;
    public $volatility_assessment;
    public $risk_factors;
    public $llm_analysis;
    public $llm_reasoning;
    public $llm_model;
    public $llm_tokens_used;
    public $analysis_version;
    public $data_freshness_score;
    public $created_at;
    public $updated_at;
    
    // Valid recommendations
    const RECOMMENDATIONS = ['STRONG_BUY', 'BUY', 'HOLD', 'SELL', 'STRONG_SELL'];
    
    // Valid risk levels
    const RISK_LEVELS = ['LOW', 'MEDIUM', 'HIGH', 'VERY_HIGH'];
    
    public function __construct(array $data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        
        $this->analysis_version = $this->analysis_version ?? '1.0';
        $this->analysis_date = $this->analysis_date ?? date('Y-m-d');
    }
    
    /**
     * Get recommendation color
     */
    public function getRecommendationColor(): string {
        switch ($this->recommendation) {
            case 'STRONG_BUY':
                return '#28a745'; // Green
            case 'BUY':
                return '#6cb33f'; // Light green
            case 'HOLD':
                return '#ffc107'; // Yellow
            case 'SELL':
                return '#fd7e14'; // Orange
            case 'STRONG_SELL':
                return '#dc3545'; // Red
            default:
                return '#6c757d'; // Gray
        }
    }
    
    /**
     * Get risk level color
     */
    public function getRiskLevelColor(): string {
        switch ($this->risk_level) {
            case 'LOW':
                return '#28a745'; // Green
            case 'MEDIUM':
                return '#ffc107'; // Yellow
            case 'HIGH':
                return '#fd7e14'; // Orange
            case 'VERY_HIGH':
                return '#dc3545'; // Red
            default:
                return '#6c757d'; // Gray
        }
    }
    
    /**
     * Get confidence indicator
     */
    public function getConfidenceIndicator(): string {
        if ($this->confidence_level === null) {
            return 'Unknown';
        }
        
        if ($this->confidence_level >= 80) {
            return 'Very High';
        } elseif ($this->confidence_level >= 60) {
            return 'High';
        } elseif ($this->confidence_level >= 40) {
            return 'Medium';
        } elseif ($this->confidence_level >= 20) {
            return 'Low';
        } else {
            return 'Very Low';
        }
    }
    
    /**
     * Calculate expected return percentage
     */
    public function getExpectedReturn(float $currentPrice): ?float {
        if ($this->target_price === null || $currentPrice <= 0) {
            return null;
        }
        
        return (($this->target_price - $currentPrice) / $currentPrice) * 100;
    }
    
    /**
     * Convert to array
     */
    public function toArray(): array {
        return get_object_vars($this);
    }
}