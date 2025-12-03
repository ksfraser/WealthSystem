<?php

namespace App\Services\Trading;

use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

/**
 * Quality Dividend Strategy Service
 * 
 * Identifies high-quality dividend stocks with sustainable payouts, consistent growth, and strong fundamentals.
 * 
 * Key Criteria:
 * - Dividend yield: 2.5%-10% (avoid dividend traps)
 * - Consecutive dividend growth: 5+ years minimum
 * - Payout ratio: < 65% for sustainability
 * - FCF coverage: 1.2x minimum (dividends covered by free cash flow)
 * - ROE: 12%+ return on equity
 * - Earnings stability: 5+ years positive earnings
 * 
 * Dividend Aristocrat Identification:
 * - Recognizes stocks with 25+ years of consecutive dividend growth
 * - Provides bonus confidence for aristocrat status
 * 
 * Safety Score Components (0-100%):
 * - Payout ratio (25%): Lower is safer
 * - FCF coverage (25%): Higher is safer
 * - Growth streak (20%): Longer is better
 * - Debt level (15%): Lower is safer
 * - ROE (15%): Higher is better
 * 
 * @package App\Services\Trading
 */
class QualityDividendStrategyService implements TradingStrategyInterface
{
    /**
     * @var MarketDataService Market data service for fundamentals and prices
     */
    private MarketDataService $marketDataService;
    
    /**
     * @var MarketDataRepositoryInterface Repository for data persistence
     */
    private MarketDataRepositoryInterface $marketDataRepository;
    
    /**
     * @var array Strategy parameters with default values
     */
    private array $parameters = [
        'min_dividend_yield' => 0.025,         // 2.5% minimum yield
        'max_dividend_yield' => 0.10,          // 10% maximum (avoid traps)
        'min_dividend_growth_years' => 5,      // 5 years consecutive growth
        'max_payout_ratio' => 0.65,            // 65% maximum sustainable
        'min_fcf_coverage' => 1.2,             // 1.2x free cash flow coverage
        'min_roe' => 0.12,                     // 12% return on equity
        'max_debt_to_equity' => 1.5,           // 1.5 debt/equity ratio
        'min_dividend_growth_rate' => 0.03,    // 3% annual growth minimum
        'max_pe_ratio' => 25,                  // 25 P/E maximum
        'min_market_cap' => 1000000000,        // $1B minimum
        'earnings_stability_years' => 5,       // 5 years positive earnings
        'dividend_aristocrat_years' => 25,     // 25 years for aristocrat status
        'min_revenue_growth' => 0.0,           // 0% minimum (flat acceptable)
        'safety_score_threshold' => 0.65,      // 65% minimum safety score
        'min_earnings_coverage' => 1.5         // 1.5x earnings coverage
    ];

    /**
     * Constructor
     * 
     * Initializes the quality dividend strategy with required services and loads
     * parameters from database if available.
     * 
     * @param MarketDataService $marketDataService Service for market data retrieval
     * @param MarketDataRepositoryInterface $marketDataRepository Repository for data persistence
     */
    public function __construct(
        MarketDataService $marketDataService,
        MarketDataRepositoryInterface $marketDataRepository
    ) {
        $this->marketDataService = $marketDataService;
        $this->marketDataRepository = $marketDataRepository;
        $this->loadParametersFromDatabase();
    }

    /**
     * Load strategy parameters from database
     * 
     * Attempts to load custom parameters from SQLite database. Falls back to
     * default parameters if database doesn't exist or query fails.
     * 
     * @return void
     */
    private function loadParametersFromDatabase(): void
    {
        try {
            $dbPath = __DIR__ . '/../../../storage/database/stock_analysis.db';
            if (!file_exists($dbPath)) {
                return;
            }

            $pdo = new \PDO('sqlite:' . $dbPath);
            $stmt = $pdo->prepare(
                'SELECT parameter_key, parameter_value, parameter_type 
                 FROM strategy_parameters 
                 WHERE strategy_name = ? AND is_active = 1'
            );
            $stmt->execute(['QualityDividend']);
            
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $key = $row['parameter_key'];
                $value = $row['parameter_value'];
                
                if ($row['parameter_type'] === 'int') {
                    $value = (int)$value;
                } elseif ($row['parameter_type'] === 'float') {
                    $value = (float)$value;
                } elseif ($row['parameter_type'] === 'bool') {
                    $value = (bool)$value;
                }
                
                $this->parameters[$key] = $value;
            }
        } catch (\Exception $e) {
            // Silently fall back to defaults
        }
    }

    /**
     * Get strategy name
     * 
     * @return string Strategy identifier
     */
    public function getName(): string
    {
        return 'QualityDividend';
    }

    /**
     * Get strategy description
     * 
     * @return string Human-readable description of strategy logic
     */
    public function getDescription(): string
    {
        return 'Identifies high-quality dividend stocks with sustainable payouts, consistent growth, and strong fundamentals. Focuses on dividend safety, growth streak, and cash flow coverage.';
    }

    /**
     * Analyze symbol for quality dividend investment opportunities
     * 
     * Performs comprehensive dividend analysis including yield, growth history,
     * payout sustainability, FCF coverage, and overall dividend safety scoring.
     * 
     * @param string $symbol Stock ticker symbol to analyze
     * @param string $date Analysis date (default: 'today')
     * @return array Analysis result with action (BUY/SELL/HOLD), confidence (0-100),
     *               reasoning (string explanation), and metrics (dividend metrics)
     */
    public function analyze(string $symbol, string $date = 'today'): array
    {
        try {
            $fundamentals = $this->marketDataService->getFundamentals($symbol);
            $historicalData = $this->marketDataService->getHistoricalPrices($symbol, 60);

            if (empty($fundamentals) || empty($historicalData)) {
                return [
                    'action' => 'HOLD',
                    'confidence' => 0,
                    'reasoning' => 'Insufficient data for dividend analysis',
                    'metrics' => []
                ];
            }

            // Calculate dividend metrics
            $dividendYield = $this->calculateDividendYield($fundamentals);
            $dividendHistory = $fundamentals['dividend_history'] ?? [];
            $growthStreak = $this->calculateDividendGrowthStreak($dividendHistory);
            $avgGrowthRate = $this->calculateAverageDividendGrowth($dividendHistory);
            $payoutRatio = $this->calculatePayoutRatio($fundamentals);
            $fcfCoverage = $this->calculateFCFCoverage($fundamentals);
            $earningsStability = $this->checkEarningsStability($fundamentals);
            $safetyScore = $this->calculateDividendSafetyScore($fundamentals, $dividendHistory);
            
            // Quality metrics
            $debtToEquity = $fundamentals['debt_to_equity'] ?? 0;
            $roe = $fundamentals['roe'] ?? 0;
            $peRatio = $fundamentals['pe_ratio'] ?? 0;
            $revenueGrowth = $this->calculateRevenueGrowth($fundamentals);
            $isDividendAristocrat = $growthStreak >= $this->parameters['dividend_aristocrat_years'];
            
            // Check for dividend cuts
            $hasDividendCut = $this->detectDividendCut($dividendHistory);

            $metrics = [
                'dividend_yield' => $dividendYield,
                'dividend_growth_streak' => $growthStreak,
                'avg_dividend_growth_rate' => $avgGrowthRate,
                'payout_ratio' => $payoutRatio,
                'fcf_coverage' => $fcfCoverage,
                'earnings_stability' => $earningsStability,
                'dividend_safety_score' => $safetyScore,
                'debt_to_equity' => $debtToEquity,
                'roe' => $roe,
                'pe_ratio' => $peRatio,
                'revenue_growth' => $revenueGrowth,
                'is_dividend_aristocrat' => $isDividendAristocrat,
                'has_dividend_cut' => $hasDividendCut
            ];

            $result = $this->determineAction($metrics, $fundamentals);
            $result['metrics'] = $metrics;

            return $result;

        } catch (\Exception $e) {
            return [
                'action' => 'HOLD',
                'confidence' => 0,
                'reasoning' => 'Error in dividend analysis: ' . $e->getMessage(),
                'metrics' => []
            ];
        }
    }

    /**
     * Calculate dividend yield
     * 
     * Computes dividend yield as dividend per share divided by price.
     * 
     * @param array $fundamentals Fundamental data
     * @return float Dividend yield (0.0 to 1.0)
     */
    private function calculateDividendYield(array $fundamentals): float
    {
        if (isset($fundamentals['dividend_yield'])) {
            return (float)$fundamentals['dividend_yield'];
        }
        
        $price = $fundamentals['price'] ?? 0;
        $dividendPerShare = $fundamentals['dividend_per_share'] ?? 0;
        
        if ($price == 0) {
            return 0.0;
        }
        
        return round($dividendPerShare / $price, 4);
    }

    /**
     * Calculate consecutive dividend growth streak
     * 
     * Counts how many consecutive years the dividend has increased.
     * 
     * @param array $dividendHistory Array of dividend history by year
     * @return int Number of consecutive years with dividend growth
     */
    private function calculateDividendGrowthStreak(array $dividendHistory): int
    {
        if (count($dividendHistory) < 2) {
            return 0;
        }
        
        // Sort by year descending
        usort($dividendHistory, function($a, $b) {
            return $b['year'] - $a['year'];
        });
        
        $streak = 0;
        for ($i = 0; $i < count($dividendHistory) - 1; $i++) {
            if ($dividendHistory[$i]['dividend'] > $dividendHistory[$i + 1]['dividend']) {
                $streak++;
            } else {
                break;
            }
        }
        
        return $streak;
    }

    /**
     * Calculate average dividend growth rate
     * 
     * Computes mean annual dividend growth rate over history.
     * 
     * @param array $dividendHistory Array of dividend history
     * @return float Average growth rate (0.0 to 1.0+)
     */
    private function calculateAverageDividendGrowth(array $dividendHistory): float
    {
        if (count($dividendHistory) < 2) {
            return 0.0;
        }
        
        usort($dividendHistory, function($a, $b) {
            return $b['year'] - $a['year'];
        });
        
        $growthRates = [];
        for ($i = 0; $i < count($dividendHistory) - 1; $i++) {
            $current = $dividendHistory[$i]['dividend'];
            $previous = $dividendHistory[$i + 1]['dividend'];
            
            if ($previous > 0) {
                $growthRates[] = ($current - $previous) / $previous;
            }
        }
        
        if (empty($growthRates)) {
            return 0.0;
        }
        
        return round(array_sum($growthRates) / count($growthRates), 4);
    }

    /**
     * Calculate dividend payout ratio
     * 
     * Ratio of dividend per share to earnings per share. Lower is more sustainable.
     * 
     * @param array $fundamentals Fundamental data
     * @return float Payout ratio (0.0 to 1.0+, > 1.0 indicates unsustainable)
     */
    private function calculatePayoutRatio(array $fundamentals): float
    {
        $eps = $fundamentals['earnings_per_share'] ?? 0;
        $dps = $fundamentals['dividend_per_share'] ?? 0;
        
        if ($eps <= 0) {
            return 1.0; // Unsafe if no earnings
        }
        
        return round($dps / $eps, 4);
    }

    /**
     * Calculate free cash flow coverage
     * 
     * Ratio of free cash flow to total dividends paid. Higher indicates more safety.
     * 
     * @param array $fundamentals Fundamental data
     * @return float FCF coverage ratio (1.0+ is good, < 1.0 is warning)
     */
    private function calculateFCFCoverage(array $fundamentals): float
    {
        $fcf = $fundamentals['free_cash_flow'] ?? 0;
        $dividendsPaid = $fundamentals['total_dividends_paid'] ?? 0;
        
        if ($dividendsPaid <= 0) {
            return 0.0;
        }
        
        return round($fcf / $dividendsPaid, 2);
    }

    /**
     * Check earnings stability
     * 
     * Verifies that earnings have been positive for required number of years.
     * 
     * @param array $fundamentals Fundamental data with earnings history
     * @return bool True if earnings stable for required years
     */
    private function checkEarningsStability(array $fundamentals): bool
    {
        $earningsHistory = $fundamentals['earnings_history'] ?? [];
        
        if (count($earningsHistory) < $this->parameters['earnings_stability_years']) {
            return false;
        }
        
        // Check last N years all positive
        $recentYears = array_slice($earningsHistory, 0, $this->parameters['earnings_stability_years']);
        
        foreach ($recentYears as $year) {
            if (($year['eps'] ?? 0) <= 0) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Calculate overall dividend safety score
     * 
     * Combines multiple factors into composite safety score (0-100%):
     * - Payout ratio (25%)
     * - FCF coverage (25%)
     * - Growth streak (20%)
     * - Debt level (15%)
     * - ROE (15%)
     * 
     * @param array $fundamentals Fundamental data
     * @param array $dividendHistory Dividend history
     * @return float Safety score (0.0 to 1.0)
     */
    private function calculateDividendSafetyScore(array $fundamentals, array $dividendHistory): float
    {
        $score = 0;
        $maxScore = 0;
        
        // Payout ratio (25 points)
        $maxScore += 25;
        $payoutRatio = $this->calculatePayoutRatio($fundamentals);
        if ($payoutRatio < 0.50) {
            $score += 25;
        } elseif ($payoutRatio < 0.65) {
            $score += 15;
        } elseif ($payoutRatio < 0.80) {
            $score += 5;
        }
        
        // FCF coverage (25 points)
        $maxScore += 25;
        $fcfCoverage = $this->calculateFCFCoverage($fundamentals);
        if ($fcfCoverage > 1.5) {
            $score += 25;
        } elseif ($fcfCoverage > 1.2) {
            $score += 15;
        } elseif ($fcfCoverage > 1.0) {
            $score += 5;
        }
        
        // Growth streak (20 points)
        $maxScore += 20;
        $growthStreak = $this->calculateDividendGrowthStreak($dividendHistory);
        if ($growthStreak >= 10) {
            $score += 20;
        } elseif ($growthStreak >= 5) {
            $score += 10;
        } elseif ($growthStreak >= 3) {
            $score += 5;
        }
        
        // Debt level (15 points)
        $maxScore += 15;
        $debtToEquity = $fundamentals['debt_to_equity'] ?? 0;
        if ($debtToEquity < 0.50) {
            $score += 15;
        } elseif ($debtToEquity < 1.0) {
            $score += 10;
        } elseif ($debtToEquity < 1.5) {
            $score += 5;
        }
        
        // ROE (15 points)
        $maxScore += 15;
        $roe = $fundamentals['roe'] ?? 0;
        if ($roe > 0.18) {
            $score += 15;
        } elseif ($roe > 0.12) {
            $score += 10;
        } elseif ($roe > 0.08) {
            $score += 5;
        }
        
        return round($score / $maxScore, 2);
    }

    private function calculateRevenueGrowth(array $fundamentals): float
    {
        $revenue = $fundamentals['revenue'] ?? 0;
        $priorRevenue = $fundamentals['prior_year_revenue'] ?? 0;
        
        if ($priorRevenue <= 0) {
            return 0.0;
        }
        
        return round(($revenue - $priorRevenue) / $priorRevenue, 4);
    }

    private function detectDividendCut(array $dividendHistory): bool
    {
        if (count($dividendHistory) < 2) {
            return false;
        }
        
        usort($dividendHistory, function($a, $b) {
            return $b['year'] - $a['year'];
        });
        
        // Check most recent year vs previous
        if ($dividendHistory[0]['dividend'] < $dividendHistory[1]['dividend']) {
            return true;
        }
        
        return false;
    }

    private function determineAction(array $metrics, array $fundamentals): array
    {
        // Check for dividend cut
        if ($metrics['has_dividend_cut']) {
            return [
                'action' => 'HOLD',
                'confidence' => 0,
                'reasoning' => 'Recent dividend cut detected - not a buy signal'
            ];
        }
        
        // Check minimum yield
        if ($metrics['dividend_yield'] < $this->parameters['min_dividend_yield']) {
            return [
                'action' => 'HOLD',
                'confidence' => 0,
                'reasoning' => sprintf(
                    'Dividend yield too low: %.2f%% (minimum %.2f%%)',
                    $metrics['dividend_yield'] * 100,
                    $this->parameters['min_dividend_yield'] * 100
                )
            ];
        }
        
        // Check yield trap (too high might be unsustainable)
        if ($metrics['dividend_yield'] > $this->parameters['max_dividend_yield']) {
            return [
                'action' => 'HOLD',
                'confidence' => 0,
                'reasoning' => sprintf(
                    'Dividend yield suspiciously high: %.2f%% - possible yield trap',
                    $metrics['dividend_yield'] * 100
                )
            ];
        }
        
        // Check payout ratio
        if ($metrics['payout_ratio'] > $this->parameters['max_payout_ratio']) {
            return [
                'action' => 'HOLD',
                'confidence' => 0,
                'reasoning' => sprintf(
                    'Payout ratio too high: %.1f%% (maximum %.1f%%) - unsustainable',
                    $metrics['payout_ratio'] * 100,
                    $this->parameters['max_payout_ratio'] * 100
                )
            ];
        }
        
        // Check FCF coverage
        if ($metrics['fcf_coverage'] < $this->parameters['min_fcf_coverage']) {
            return [
                'action' => 'HOLD',
                'confidence' => 0,
                'reasoning' => sprintf(
                    'Insufficient FCF coverage: %.2fx (minimum %.2fx)',
                    $metrics['fcf_coverage'],
                    $this->parameters['min_fcf_coverage']
                )
            ];
        }
        
        // Quality dividend - BUY signal
        if ($metrics['dividend_growth_streak'] >= $this->parameters['min_dividend_growth_years'] &&
            $metrics['earnings_stability'] &&
            $metrics['dividend_safety_score'] >= $this->parameters['safety_score_threshold']) {
            
            $confidence = 60 + ($metrics['dividend_safety_score'] * 30);
            
            if ($metrics['is_dividend_aristocrat']) {
                $confidence += 10;
            }
            
            $confidence = min(95, $confidence);
            
            $reasoning = sprintf(
                'High-quality dividend stock: %.2f%% yield, %d-year growth streak, %.2fx FCF coverage, ' .
                '%.1f%% payout ratio, %.1f%% safety score. ',
                $metrics['dividend_yield'] * 100,
                $metrics['dividend_growth_streak'],
                $metrics['fcf_coverage'],
                $metrics['payout_ratio'] * 100,
                $metrics['dividend_safety_score'] * 100
            );
            
            if ($metrics['is_dividend_aristocrat']) {
                $reasoning .= 'Dividend Aristocrat status (25+ years growth). ';
            }
            
            $reasoning .= sprintf(
                'ROE: %.1f%%, Debt/Equity: %.2f, P/E: %.1f.',
                $metrics['roe'] * 100,
                $metrics['debt_to_equity'],
                $metrics['pe_ratio']
            );
            
            return [
                'action' => 'BUY',
                'confidence' => (int)$confidence,
                'reasoning' => $reasoning
            ];
        }
        
        // Moderate quality - needs more criteria
        if ($metrics['dividend_growth_streak'] >= 3 &&
            $metrics['dividend_safety_score'] >= 0.50) {
            
            $confidence = 30 + ($metrics['dividend_safety_score'] * 20);
            
            $reasoning = sprintf(
                'Decent dividend profile but lacks full quality criteria. ' .
                'Yield: %.2f%%, Growth streak: %d years, Safety: %.1f%%. ',
                $metrics['dividend_yield'] * 100,
                $metrics['dividend_growth_streak'],
                $metrics['dividend_safety_score'] * 100
            );
            
            if ($metrics['dividend_growth_streak'] < $this->parameters['min_dividend_growth_years']) {
                $reasoning .= sprintf(
                    'Needs %d-year growth streak (has %d). ',
                    $this->parameters['min_dividend_growth_years'],
                    $metrics['dividend_growth_streak']
                );
            }
            
            if (!$metrics['earnings_stability']) {
                $reasoning .= 'Earnings stability concerns. ';
            }
            
            return [
                'action' => 'HOLD',
                'confidence' => (int)$confidence,
                'reasoning' => $reasoning
            ];
        }
        
        // Not a quality dividend stock
        return [
            'action' => 'HOLD',
            'confidence' => 0,
            'reasoning' => sprintf(
                'Does not meet quality dividend criteria. Yield: %.2f%%, Growth streak: %d years, Safety: %.1f%%',
                $metrics['dividend_yield'] * 100,
                $metrics['dividend_growth_streak'],
                $metrics['dividend_safety_score'] * 100
            )
        ];
    }

    /**
     * Get strategy parameters
     * 
     * @return array Current strategy parameters
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Set strategy parameters
     * 
     * Updates strategy parameters with provided values.
     * 
     * @param array $parameters Parameters to update
     * @return void
     */
    public function setParameters(array $parameters): void
    {
        foreach ($parameters as $key => $value) {
            if (array_key_exists($key, $this->parameters)) {
                $this->parameters[$key] = $value;
            }
        }
    }

    /**
     * Check if strategy can execute for symbol
     * 
     * @param string $symbol Stock ticker symbol
     * @return bool Always returns true
     */
    public function canExecute(string $symbol): bool
    {
        return true;
    }

    /**
     * Get required historical days
     * 
     * @return int Number of days required (60)
     */
    public function getRequiredHistoricalDays(): int
    {
        return 60;
    }
}
