<?php

namespace App\Services\Trading;

use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

/**
 * GARP (Growth at Reasonable Price) Strategy Service
 * 
 * Based on Motley Fool's investment methodology, particularly their "Rule Breakers" approach.
 * Combines growth investing with value principles by seeking high-growth companies
 * trading at reasonable valuations (PEG ratio < 1.0).
 * 
 * Key Criteria:
 * 1. Strong Revenue Growth (≥20% YoY)
 * 2. Strong Earnings Growth (≥20% YoY)
 * 3. PEG Ratio < 1.0 (Price/Earnings to Growth)
 * 4. Rule Breaker Characteristics:
 *    - Top dog and first mover in an emerging industry
 *    - Sustainable competitive advantage
 *    - Strong past price appreciation
 *    - Good management and smart backing
 *    - Strong consumer appeal
 *    - Financial strength
 * 5. Accelerating Growth Trends
 * 6. Institutional Interest (but not overcrowded)
 * 
 * @package App\Services\Trading
 */
class GARPStrategyService implements TradingStrategyInterface
{
    private MarketDataService $marketDataService;
    private MarketDataRepositoryInterface $marketDataRepository;
    
    private array $parameters = [
        'min_revenue_growth' => 0.20,      // 20% minimum revenue growth
        'min_earnings_growth' => 0.20,     // 20% minimum earnings growth
        'max_peg_ratio' => 1.0,            // PEG ratio must be < 1.0
        'min_gross_margin' => 0.40,        // 40% minimum gross margin
        'min_market_cap' => 500000000,     // $500M minimum market cap
        'max_debt_to_equity' => 1.0,       // 100% max debt-to-equity
        'min_current_ratio' => 1.5,        // 1.5:1 minimum liquidity
        'min_institutional_ownership' => 0.10, // 10% minimum institutional
        'max_institutional_ownership' => 0.70, // 70% maximum institutional
        'min_price_momentum_3m' => 0.05,   // 5% minimum 3-month momentum
        'lookback_periods' => 4,           // Quarters to analyze
        'stop_loss_percent' => 0.20,       // 20% stop loss
        'take_profit_percent' => 1.00,     // 100% take profit target
        'max_position_size' => 0.10        // 10% of portfolio max
    ];

    public function __construct(
        MarketDataService $marketDataService,
        MarketDataRepositoryInterface $marketDataRepository
    ) {
        $this->marketDataService = $marketDataService;
        $this->marketDataRepository = $marketDataRepository;
    }

    public function getName(): string
    {
        return "GARP (Growth at Reasonable Price) Strategy";
    }

    public function getDescription(): string
    {
        return "Growth at Reasonable Price strategy based on Motley Fool's Rule Breakers methodology. " .
               "Seeks high-growth companies (≥20% revenue/earnings growth) trading at reasonable valuations (PEG < 1.0). " .
               "Emphasizes emerging industry leaders with sustainable competitive advantages, strong momentum, " .
               "and accelerating growth trends. Targets companies with institutional interest but not overcrowded.";
    }

    public function analyze(string $symbol, string $date = 'today'): array
    {
        // Fetch fundamental and price data
        $fundamentals = $this->marketDataService->getFundamentals($symbol);
        
        if (empty($fundamentals)) {
            return $this->createHoldSignal($symbol, 'Insufficient fundamental data');
        }
        
        // Calculate date range for historical analysis
        $endDate = $date === 'today' ? date('Y-m-d') : $date;
        $startDate = date('Y-m-d', strtotime('-2 years', strtotime($endDate)));
        $priceHistory = $this->marketDataService->getHistoricalPrices($symbol, $startDate, $endDate);
        
        if (empty($priceHistory)) {
            return $this->createHoldSignal($symbol, 'Insufficient price history');
        }
        
        $currentPrice = end($priceHistory)['close'];
        
        // Evaluate GARP Criteria
        $growthScore = $this->evaluateGrowthMetrics($fundamentals);
        $valuationScore = $this->evaluateValuation($fundamentals);
        $ruleBreakerScore = $this->evaluateRuleBreakerCriteria($fundamentals, $priceHistory);
        $momentumScore = $this->evaluateMomentum($priceHistory);
        $financialStrengthScore = $this->evaluateFinancialStrength($fundamentals);
        
        // Calculate PEG ratio (key GARP metric)
        $pegRatio = $this->calculatePEGRatio($fundamentals);
        
        // Check for accelerating growth
        $isAccelerating = $this->isGrowthAccelerating($fundamentals);
        
        // Calculate overall quality score
        $qualityScore = ($growthScore * 0.35) + 
                       ($valuationScore * 0.25) + 
                       ($ruleBreakerScore * 0.20) + 
                       ($momentumScore * 0.10) +
                       ($financialStrengthScore * 0.10);
        
        // Determine signal
        $signal = $this->determineSignal(
            $qualityScore,
            $pegRatio,
            $growthScore,
            $isAccelerating,
            $fundamentals
        );
        
        // Calculate position size
        $positionSize = $this->calculatePositionSize($qualityScore, $pegRatio);
        
        // Calculate stop loss and take profit
        $stopLoss = $currentPrice * (1 - $this->parameters['stop_loss_percent']);
        $takeProfit = $currentPrice * (1 + $this->parameters['take_profit_percent']);
        
        // Calculate confidence
        $confidence = $this->calculateConfidence(
            $qualityScore,
            $pegRatio,
            $isAccelerating,
            $momentumScore
        );
        
        return [
            'signal' => $signal,
            'confidence' => $confidence,
            'reason' => $this->generateReason($signal, $qualityScore, $pegRatio, $growthScore, $isAccelerating),
            'entry_price' => $currentPrice,
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'position_size' => $positionSize,
            'metadata' => [
                'strategy' => 'GARP',
                'quality_score' => round($qualityScore, 2),
                'growth_score' => round($growthScore, 2),
                'valuation_score' => round($valuationScore, 2),
                'rule_breaker_score' => round($ruleBreakerScore, 2),
                'momentum_score' => round($momentumScore, 2),
                'financial_strength_score' => round($financialStrengthScore, 2),
                'peg_ratio' => $pegRatio,
                'is_accelerating' => $isAccelerating,
                'revenue_growth' => $fundamentals['revenue_growth'] ?? null,
                'earnings_growth' => $fundamentals['earnings_growth'] ?? null,
                'timestamp' => $endDate
            ]
        ];
    }

    /**
     * Evaluate Growth Metrics (Revenue & Earnings Growth)
     */
    private function evaluateGrowthMetrics(array $fundamentals): float
    {
        $score = 0;
        $maxScore = 100;
        
        $revenueGrowth = $fundamentals['revenue_growth'] ?? 0;
        $earningsGrowth = $fundamentals['earnings_growth'] ?? 0;
        
        // Revenue growth scoring (0-50 points)
        if ($revenueGrowth >= 0.40) { // 40%+ exceptional
            $score += 50;
        } elseif ($revenueGrowth >= 0.30) { // 30-40% excellent
            $score += 45;
        } elseif ($revenueGrowth >= 0.20) { // 20-30% good
            $score += 35;
        } elseif ($revenueGrowth >= 0.15) { // 15-20% moderate
            $score += 25;
        } elseif ($revenueGrowth >= 0.10) { // 10-15% acceptable
            $score += 15;
        }
        
        // Earnings growth scoring (0-50 points)
        if ($earningsGrowth >= 0.40) { // 40%+ exceptional
            $score += 50;
        } elseif ($earningsGrowth >= 0.30) { // 30-40% excellent
            $score += 45;
        } elseif ($earningsGrowth >= 0.20) { // 20-30% good
            $score += 35;
        } elseif ($earningsGrowth >= 0.15) { // 15-20% moderate
            $score += 25;
        } elseif ($earningsGrowth >= 0.10) { // 10-15% acceptable
            $score += 15;
        }
        
        return min($score, $maxScore);
    }

    /**
     * Evaluate Valuation (primarily PEG ratio)
     */
    private function evaluateValuation(array $fundamentals): float
    {
        $score = 0;
        $maxScore = 100;
        
        $pegRatio = $this->calculatePEGRatio($fundamentals);
        $peRatio = $fundamentals['pe_ratio'] ?? null;
        
        // PEG ratio scoring (0-70 points)
        if ($pegRatio > 0) {
            if ($pegRatio < 0.5) { // Extremely attractive
                $score += 70;
            } elseif ($pegRatio < 0.75) { // Very attractive
                $score += 60;
            } elseif ($pegRatio < 1.0) { // Attractive (meets GARP criteria)
                $score += 50;
            } elseif ($pegRatio < 1.5) { // Fair
                $score += 30;
            } elseif ($pegRatio < 2.0) { // Slightly expensive
                $score += 15;
            }
            // > 2.0 gets 0 points
        }
        
        // P/E ratio reasonableness check (0-30 points)
        if ($peRatio !== null && $peRatio > 0) {
            if ($peRatio < 20) { // Low P/E
                $score += 30;
            } elseif ($peRatio < 30) { // Moderate P/E
                $score += 25;
            } elseif ($peRatio < 50) { // High but acceptable for growth
                $score += 15;
            } elseif ($peRatio < 75) { // Very high
                $score += 5;
            }
            // > 75 gets 0 points
        }
        
        return min($score, $maxScore);
    }

    /**
     * Evaluate Rule Breaker Criteria (Motley Fool methodology)
     */
    private function evaluateRuleBreakerCriteria(array $fundamentals, array $priceHistory): float
    {
        $score = 0;
        $maxScore = 100;
        
        // 1. Top dog / first mover (market cap & market position)
        $marketCap = $fundamentals['market_cap'] ?? 0;
        if ($marketCap >= 10000000000) { // $10B+ established leader
            $score += 15;
        } elseif ($marketCap >= 5000000000) { // $5B+ strong player
            $score += 20;
        } elseif ($marketCap >= 1000000000) { // $1B+ emerging leader
            $score += 25; // Sweet spot for Rule Breakers
        } elseif ($marketCap >= 500000000) { // $500M+ small-cap
            $score += 15;
        }
        
        // 2. Sustainable competitive advantage (margins)
        $grossMargin = $fundamentals['gross_margin'] ?? $fundamentals['profit_margin'] ?? 0;
        if ($grossMargin >= 0.60) { // 60%+ exceptional
            $score += 20;
        } elseif ($grossMargin >= 0.50) { // 50-60% excellent
            $score += 15;
        } elseif ($grossMargin >= 0.40) { // 40-50% good
            $score += 10;
        }
        
        // 3. Strong past price appreciation (1-year return)
        $oneYearReturn = $this->calculatePriceReturn($priceHistory, 252); // ~1 year
        if ($oneYearReturn >= 0.50) { // 50%+ exceptional
            $score += 20;
        } elseif ($oneYearReturn >= 0.30) { // 30-50% excellent
            $score += 15;
        } elseif ($oneYearReturn >= 0.15) { // 15-30% good
            $score += 10;
        } elseif ($oneYearReturn >= 0.05) { // 5-15% moderate
            $score += 5;
        }
        
        // 4. Good management (ROE as proxy)
        $roe = $fundamentals['return_on_equity'] ?? 0;
        if ($roe >= 0.25) { // 25%+ exceptional
            $score += 15;
        } elseif ($roe >= 0.20) { // 20-25% excellent
            $score += 12;
        } elseif ($roe >= 0.15) { // 15-20% good
            $score += 8;
        }
        
        // 5. Strong consumer appeal (brand value / revenue per share)
        $revenuePerShare = ($fundamentals['revenue'] ?? 0) / max(($fundamentals['shares_outstanding'] ?? 1), 1);
        if ($revenuePerShare >= 50) { // High revenue per share
            $score += 10;
        } elseif ($revenuePerShare >= 20) {
            $score += 7;
        } elseif ($revenuePerShare >= 10) {
            $score += 5;
        }
        
        // 6. Financial strength (current ratio, low debt)
        $currentRatio = $fundamentals['current_ratio'] ?? 0;
        $debtToEquity = $fundamentals['debt_to_equity'] ?? 0;
        
        if ($currentRatio >= 2.0 && $debtToEquity <= 0.5) { // Very strong
            $score += 20;
        } elseif ($currentRatio >= 1.5 && $debtToEquity <= 1.0) { // Strong
            $score += 15;
        } elseif ($currentRatio >= 1.0 && $debtToEquity <= 1.5) { // Adequate
            $score += 10;
        }
        
        return min($score, $maxScore);
    }

    /**
     * Evaluate Price Momentum
     */
    private function evaluateMomentum(array $priceHistory): float
    {
        $score = 0;
        $maxScore = 100;
        
        // 3-month momentum (0-40 points)
        $threeMonthReturn = $this->calculatePriceReturn($priceHistory, 63); // ~3 months
        if ($threeMonthReturn >= 0.20) { // 20%+ strong
            $score += 40;
        } elseif ($threeMonthReturn >= 0.10) { // 10-20% good
            $score += 30;
        } elseif ($threeMonthReturn >= 0.05) { // 5-10% moderate
            $score += 20;
        } elseif ($threeMonthReturn >= 0) { // Positive
            $score += 10;
        }
        
        // 6-month momentum (0-30 points)
        $sixMonthReturn = $this->calculatePriceReturn($priceHistory, 126); // ~6 months
        if ($sixMonthReturn >= 0.30) { // 30%+ strong
            $score += 30;
        } elseif ($sixMonthReturn >= 0.20) { // 20-30% good
            $score += 25;
        } elseif ($sixMonthReturn >= 0.10) { // 10-20% moderate
            $score += 15;
        } elseif ($sixMonthReturn >= 0) { // Positive
            $score += 5;
        }
        
        // Relative strength (price vs moving averages) (0-30 points)
        if (count($priceHistory) >= 50) {
            $currentPrice = end($priceHistory)['close'];
            $ma50 = $this->calculateMovingAverage($priceHistory, 50);
            
            if ($currentPrice > $ma50 * 1.10) { // 10%+ above MA
                $score += 30;
            } elseif ($currentPrice > $ma50 * 1.05) { // 5-10% above
                $score += 20;
            } elseif ($currentPrice > $ma50) { // Above MA
                $score += 10;
            }
        }
        
        return min($score, $maxScore);
    }

    /**
     * Evaluate Financial Strength
     */
    private function evaluateFinancialStrength(array $fundamentals): float
    {
        $score = 0;
        $maxScore = 100;
        
        // Cash flow health (0-40 points)
        $operatingCashFlow = $fundamentals['operating_cash_flow'] ?? 0;
        $freeCashFlow = $fundamentals['free_cash_flow'] ?? 0;
        $revenue = $fundamentals['revenue'] ?? 1;
        
        if ($freeCashFlow > 0 && $revenue > 0) {
            $fcfMargin = $freeCashFlow / $revenue;
            if ($fcfMargin >= 0.20) { // 20%+ exceptional
                $score += 40;
            } elseif ($fcfMargin >= 0.15) { // 15-20% excellent
                $score += 35;
            } elseif ($fcfMargin >= 0.10) { // 10-15% good
                $score += 25;
            } elseif ($fcfMargin >= 0.05) { // 5-10% moderate
                $score += 15;
            }
        }
        
        // Balance sheet strength (0-30 points)
        $currentRatio = $fundamentals['current_ratio'] ?? 0;
        $debtToEquity = $fundamentals['debt_to_equity'] ?? 999;
        
        if ($currentRatio >= 2.0 && $debtToEquity <= 0.5) {
            $score += 30;
        } elseif ($currentRatio >= 1.5 && $debtToEquity <= 1.0) {
            $score += 25;
        } elseif ($currentRatio >= 1.0 && $debtToEquity <= 1.5) {
            $score += 15;
        }
        
        // Profitability (0-30 points)
        $netMargin = $fundamentals['profit_margin'] ?? 0;
        if ($netMargin >= 0.20) { // 20%+ exceptional
            $score += 30;
        } elseif ($netMargin >= 0.15) { // 15-20% excellent
            $score += 25;
        } elseif ($netMargin >= 0.10) { // 10-15% good
            $score += 20;
        } elseif ($netMargin >= 0.05) { // 5-10% moderate
            $score += 10;
        }
        
        return min($score, $maxScore);
    }

    /**
     * Calculate PEG Ratio (Price/Earnings to Growth)
     */
    private function calculatePEGRatio(array $fundamentals): float
    {
        $peRatio = $fundamentals['pe_ratio'] ?? null;
        $earningsGrowth = $fundamentals['earnings_growth'] ?? null;
        
        if ($peRatio === null || $earningsGrowth === null || $earningsGrowth <= 0) {
            return 999; // Invalid or undefined
        }
        
        // PEG = PE / (Growth Rate * 100)
        // Example: PE of 20 with 20% growth = 20 / 20 = 1.0
        $pegRatio = $peRatio / ($earningsGrowth * 100);
        
        return round($pegRatio, 2);
    }

    /**
     * Check if growth is accelerating (quarter-over-quarter improvement)
     */
    private function isGrowthAccelerating(array $fundamentals): bool
    {
        // Check if recent quarter growth > prior quarter growth
        $recentQtrGrowth = $fundamentals['revenue_growth_qtd'] ?? $fundamentals['revenue_growth'] ?? null;
        $priorQtrGrowth = $fundamentals['revenue_growth_prior_qtr'] ?? null;
        
        if ($recentQtrGrowth !== null && $priorQtrGrowth !== null) {
            return $recentQtrGrowth > $priorQtrGrowth;
        }
        
        // Alternative: check if growth rate is above historical average
        $currentGrowth = $fundamentals['revenue_growth'] ?? 0;
        $avgGrowth = $fundamentals['revenue_growth_3y_avg'] ?? $currentGrowth;
        
        return $currentGrowth > $avgGrowth * 1.1; // 10% above average
    }

    /**
     * Determine trading signal
     */
    private function determineSignal(
        float $qualityScore,
        float $pegRatio,
        float $growthScore,
        bool $isAccelerating,
        array $fundamentals
    ): string {
        $revenueGrowth = $fundamentals['revenue_growth'] ?? 0;
        $earningsGrowth = $fundamentals['earnings_growth'] ?? 0;
        $marketCap = $fundamentals['market_cap'] ?? 0;
        
        // BUY: Excellent GARP opportunity
        if ($qualityScore >= 70 && 
            $pegRatio > 0 && 
            $pegRatio < $this->parameters['max_peg_ratio'] &&
            $growthScore >= 60 &&
            $revenueGrowth >= $this->parameters['min_revenue_growth'] &&
            $marketCap >= $this->parameters['min_market_cap']) {
            return 'BUY';
        }
        
        // BUY: Accelerating growth with good valuation
        if ($isAccelerating && 
            $pegRatio > 0 && 
            $pegRatio < 1.2 &&
            $qualityScore >= 65 &&
            $growthScore >= 55) {
            return 'BUY';
        }
        
        // SELL: Growth slowing or overvalued
        if ($pegRatio > 2.0 || 
            $growthScore < 40 ||
            $revenueGrowth < 0.05) { // < 5% growth
            return 'SELL';
        }
        
        // SELL: Poor financial health
        $debtToEquity = $fundamentals['debt_to_equity'] ?? 0;
        if ($debtToEquity > $this->parameters['max_debt_to_equity'] * 1.5) {
            return 'SELL';
        }
        
        return 'HOLD';
    }

    /**
     * Calculate position size
     */
    private function calculatePositionSize(float $qualityScore, float $pegRatio): float
    {
        $baseSize = $this->parameters['max_position_size'];
        
        // Scale by quality
        $qualityFactor = $qualityScore / 100;
        
        // Scale by PEG ratio (better valuation = larger position)
        $valuationFactor = 1.0;
        if ($pegRatio > 0 && $pegRatio < 2.0) {
            $valuationFactor = max(0.5, 1.5 - ($pegRatio * 0.5));
        }
        
        $positionSize = $baseSize * $qualityFactor * $valuationFactor;
        
        return round(min($positionSize, $this->parameters['max_position_size']), 4);
    }

    /**
     * Calculate confidence level
     */
    private function calculateConfidence(
        float $qualityScore,
        float $pegRatio,
        bool $isAccelerating,
        float $momentumScore
    ): float {
        $confidence = $qualityScore / 100; // Base confidence from quality
        
        // Boost for excellent PEG ratio
        if ($pegRatio > 0 && $pegRatio < 0.75) {
            $confidence *= 1.2;
        } elseif ($pegRatio > 2.0) {
            $confidence *= 0.7;
        }
        
        // Boost for accelerating growth
        if ($isAccelerating) {
            $confidence *= 1.15;
        }
        
        // Factor in momentum
        $confidence = ($confidence * 0.7) + (($momentumScore / 100) * 0.3);
        
        return round(min(max($confidence, 0.0), 1.0), 3);
    }

    /**
     * Generate reason for signal
     */
    private function generateReason(
        string $signal,
        float $qualityScore,
        float $pegRatio,
        float $growthScore,
        bool $isAccelerating
    ): string {
        $reasons = [];
        
        if ($signal === 'BUY') {
            $reasons[] = "Quality Score: " . round($qualityScore, 1);
            $reasons[] = "PEG: " . ($pegRatio < 999 ? round($pegRatio, 2) : 'N/A');
            $reasons[] = "Growth Score: " . round($growthScore, 1);
            if ($isAccelerating) {
                $reasons[] = "Accelerating growth";
            }
            return "Strong GARP opportunity - " . implode(", ", $reasons);
        } elseif ($signal === 'SELL') {
            if ($pegRatio > 2.0) {
                $reasons[] = "Overvalued (PEG: " . round($pegRatio, 2) . ")";
            }
            if ($growthScore < 40) {
                $reasons[] = "Weak growth";
            }
            return implode(", ", $reasons);
        }
        
        return "Moderate quality (Score: " . round($qualityScore, 1) . "), hold for now";
    }

    /**
     * Calculate price return over period
     */
    private function calculatePriceReturn(array $priceHistory, int $periods): float
    {
        if (count($priceHistory) < $periods + 1) {
            return 0;
        }
        
        $endPrice = end($priceHistory)['close'];
        $startPrice = $priceHistory[count($priceHistory) - $periods - 1]['close'];
        
        if ($startPrice <= 0) {
            return 0;
        }
        
        return ($endPrice - $startPrice) / $startPrice;
    }

    /**
     * Calculate moving average
     */
    private function calculateMovingAverage(array $priceHistory, int $period): float
    {
        if (count($priceHistory) < $period) {
            return 0;
        }
        
        $sum = 0;
        $recentPrices = array_slice($priceHistory, -$period);
        
        foreach ($recentPrices as $price) {
            $sum += $price['close'];
        }
        
        return $sum / $period;
    }

    /**
     * Create HOLD signal
     */
    private function createHoldSignal(string $symbol, string $reason): array
    {
        return [
            'signal' => 'HOLD',
            'confidence' => 0.3,
            'reason' => $reason,
            'entry_price' => null,
            'stop_loss' => null,
            'take_profit' => null,
            'position_size' => 0,
            'metadata' => [
                'strategy' => 'GARP',
                'symbol' => $symbol,
                'timestamp' => date('Y-m-d')
            ]
        ];
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }

    public function canExecute(string $symbol): bool
    {
        try {
            $fundamentals = $this->marketDataService->getFundamentals($symbol);
            
            // Need at least revenue, earnings, and growth data
            $requiredFields = ['revenue', 'revenue_growth', 'earnings_growth'];
            foreach ($requiredFields as $field) {
                if (!isset($fundamentals[$field]) || $fundamentals[$field] === null) {
                    return false;
                }
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getRequiredHistoricalDays(): int
    {
        // Need 2 years for momentum and trend analysis
        return 365 * 2;
    }
}
