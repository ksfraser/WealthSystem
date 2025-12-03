<?php

namespace App\Services\Trading;

use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

/**
 * Warren Buffett Value Strategy
 * 
 * Implements Warren Buffett's investment philosophy based on his 12 investment tenets
 * and intrinsic value calculation methodology.
 * 
 * The strategy evaluates stocks across four dimensions:
 * 1. Business Tenets (4 criteria) - Quality and understandability of the business
 * 2. Management Tenets (3 criteria) - Quality of leadership and capital allocation
 * 3. Financial Tenets (4 criteria) - Financial strength and profitability
 * 4. Value Tenets (1 criteria) - Intrinsic value vs market price
 * 
 * Key Concepts:
 * - Owner Earnings: True economic cash flow available to shareholders
 * - Intrinsic Value: Present value of all future owner earnings
 * - Margin of Safety: Buy below intrinsic value to protect against errors
 * - Economic Moat: Sustainable competitive advantages
 * 
 * @package App\Services\Trading
 */
class WarrenBuffettStrategyService implements TradingStrategyInterface
{
    private MarketDataService $marketDataService;
    private MarketDataRepositoryInterface $marketDataRepository;
    
    private array $parameters = [
        // Business Tenets
        'min_operating_history_years' => 10,
        'require_simple_business' => true,
        'require_favorable_prospects' => true,
        
        // Management Tenets (qualitative - use proxies)
        'min_insider_ownership_percent' => 5.0,
        'require_share_buybacks' => false, // Optional
        
        // Financial Tenets
        'min_roe_percent' => 15.0,
        'min_profit_margin_percent' => 20.0,
        'max_debt_to_equity' => 0.5,
        'min_free_cash_flow_growth' => 0.0, // 0% minimum growth
        
        // Value Tenets
        'margin_of_safety_percent' => 25.0, // Buy at 75% of intrinsic value
        'discount_rate_percent' => 10.0, // 10-year Treasury + 2-3%
        'perpetual_growth_rate' => 3.0, // Terminal growth rate
        
        // Moat Scoring
        'moat_strength_weight' => 0.3,
        
        // Risk Management
        'stop_loss_percent' => 0.15, // 15% below purchase
        'take_profit_percent' => 0.50, // 50% gain (or intrinsic value reached)
        'max_position_size' => 0.15 // 15% of portfolio max
    ];

    public function __construct(
        MarketDataService $marketDataService,
        MarketDataRepositoryInterface $marketDataRepository
    ) {
        $this->marketDataService = $marketDataService;
        $this->marketDataRepository = $marketDataRepository;
        $this->loadParametersFromDatabase();
    }

    /**
     * Load parameters from database if available
     */
    private function loadParametersFromDatabase(): void
    {
        try {
            $databasePath = __DIR__ . '/../../../storage/database/stock_analysis.db';
            if (file_exists($databasePath)) {
                $pdo = new \PDO("sqlite:$databasePath");
                $stmt = $pdo->prepare("
                    SELECT parameter_key, parameter_value, parameter_type
                    FROM strategy_parameters
                    WHERE strategy_name = :strategy_name AND is_active = 1
                ");
                $stmt->execute(['strategy_name' => $this->getName()]);
                
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $value = $this->castParameterValue($row['parameter_value'], $row['parameter_type']);
                    $this->parameters[$row['parameter_key']] = $value;
                }
            }
        } catch (\Exception $e) {
            // If database doesn't exist or query fails, use hardcoded defaults
            error_log("Could not load parameters from database: " . $e->getMessage());
        }
    }

    /**
     * Cast parameter value to appropriate type
     */
    private function castParameterValue($value, string $type)
    {
        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
            case 'double':
            case 'decimal':
                return (float) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            default:
                return $value;
        }
    }

    public function getName(): string
    {
        return "Warren Buffett Value Strategy";
    }

    public function getDescription(): string
    {
        return "Value investing strategy based on Warren Buffett's 12 investment tenets. " .
               "Evaluates business quality, management quality, financial strength, and intrinsic value. " .
               "Focuses on high-quality businesses with economic moats trading below intrinsic value. " .
               "Emphasizes long-term ownership of excellent companies at fair prices.";
    }

    public function analyze(string $symbol, string $date = 'today'): array
    {
        // Fetch fundamental and price data
        $fundamentals = $this->marketDataService->getFundamentals($symbol);
        
        // Calculate date range for 10 years of historical data
        $endDate = $date === 'today' ? date('Y-m-d') : $date;
        $startDate = date('Y-m-d', strtotime('-10 years', strtotime($endDate)));
        $priceHistory = $this->marketDataService->getHistoricalPrices($symbol, $startDate, $endDate);
        
        if (empty($priceHistory)) {
            return $this->createHoldSignal($symbol, 'Insufficient price history');
        }
        
        $currentPrice = end($priceHistory)['close'];
        
        // Evaluate the 12 Investment Tenets
        $businessScore = $this->evaluateBusinessTenets($fundamentals, $priceHistory);
        $managementScore = $this->evaluateManagementTenets($fundamentals);
        $financialScore = $this->evaluateFinancialTenets($fundamentals, $priceHistory);
        
        // Calculate intrinsic value
        $intrinsicValue = $this->calculateIntrinsicValue($fundamentals, $priceHistory);
        
        // Calculate margin of safety
        $marginOfSafety = $intrinsicValue > 0 
            ? (($intrinsicValue - $currentPrice) / $intrinsicValue) 
            : -1.0;
        
        // Evaluate value tenet
        $valueScore = $this->evaluateValueTenet($currentPrice, $intrinsicValue);
        
        // Calculate economic moat strength
        $moatStrength = $this->calculateMoatStrength($fundamentals, $priceHistory);
        
        // Calculate overall quality score
        $qualityScore = ($businessScore * 0.25) + ($managementScore * 0.20) + 
                       ($financialScore * 0.35) + ($moatStrength * 0.20);
        
        // Determine signal
        $signal = $this->determineSignal(
            $qualityScore,
            $valueScore,
            $marginOfSafety,
            $currentPrice,
            $intrinsicValue
        );
        
        // Calculate position size based on quality and margin of safety
        $positionSize = $this->calculatePositionSize($qualityScore, $marginOfSafety);
        
        // Calculate stop loss and take profit
        $stopLoss = $currentPrice * (1 - $this->parameters['stop_loss_percent']);
        $targetPrice = min(
            $intrinsicValue,
            $currentPrice * (1 + $this->parameters['take_profit_percent'])
        );
        
        // Calculate confidence based on all factors
        $confidence = $this->calculateConfidence(
            $qualityScore,
            $valueScore,
            $marginOfSafety,
            $moatStrength
        );
        
        return [
            'signal' => $signal,
            'confidence' => $confidence,
            'reason' => $this->generateReason($signal, $qualityScore, $marginOfSafety, $intrinsicValue, $currentPrice),
            'entry_price' => $currentPrice,
            'stop_loss' => $stopLoss,
            'take_profit' => $targetPrice,
            'position_size' => $positionSize,
            'metadata' => [
                'strategy' => 'Warren Buffett Value',
                'business_score' => round($businessScore, 2),
                'management_score' => round($managementScore, 2),
                'financial_score' => round($financialScore, 2),
                'value_score' => round($valueScore, 2),
                'quality_score' => round($qualityScore, 2),
                'moat_strength' => round($moatStrength, 2),
                'intrinsic_value' => round($intrinsicValue, 2),
                'current_price' => round($currentPrice, 2),
                'margin_of_safety' => round($marginOfSafety * 100, 2),
                'margin_required' => $this->parameters['margin_of_safety_percent'],
                'owner_earnings' => $this->calculateOwnerEarnings($fundamentals),
                'roe' => $fundamentals['return_on_equity'] ?? null,
                'debt_to_equity' => $fundamentals['debt_to_equity'] ?? null,
                'profit_margin' => $fundamentals['profit_margin'] ?? null
            ]
        ];
    }

    /**
     * Evaluate Business Tenets (4 criteria)
     * 
     * 1. Is the business simple and understandable?
     * 2. Does the business have a consistent operating history?
     * 3. Does the business have favorable long-term prospects?
     * 4. Does the business have an economic moat?
     */
    private function evaluateBusinessTenets(array $fundamentals, array $priceHistory): float
    {
        $score = 0;
        $maxScore = 4;
        
        // 1. Business simplicity (use sector/industry as proxy)
        // Simpler businesses: Consumer Staples, Industrials, Healthcare
        $simpleSectors = ['Consumer Staples', 'Industrials', 'Healthcare', 'Consumer Discretionary'];
        $sector = $fundamentals['sector'] ?? '';
        if (in_array($sector, $simpleSectors) || !$this->parameters['require_simple_business']) {
            $score += 1.0;
        }
        
        // 2. Consistent operating history (10+ years of data, positive earnings)
        if (count($priceHistory) >= 365 * $this->parameters['min_operating_history_years']) {
            $score += 1.0;
        } else {
            $score += count($priceHistory) / (365 * $this->parameters['min_operating_history_years']);
        }
        
        // 3. Favorable long-term prospects (revenue growth, market cap growth)
        $revenueGrowth = $fundamentals['revenue_growth'] ?? 0;
        if ($revenueGrowth > 0.10) { // 10%+ growth
            $score += 1.0;
        } elseif ($revenueGrowth > 0.05) { // 5-10% growth
            $score += 0.7;
        } elseif ($revenueGrowth > 0) { // Positive growth
            $score += 0.4;
        }
        
        // 4. Economic moat existence (use ROE consistency as proxy)
        $roe = $fundamentals['return_on_equity'] ?? 0;
        if ($roe > 0.20) { // Exceptional ROE suggests moat
            $score += 1.0;
        } elseif ($roe > 0.15) {
            $score += 0.7;
        } elseif ($roe > 0.10) {
            $score += 0.4;
        }
        
        return ($score / $maxScore) * 100; // Return as 0-100 score
    }

    /**
     * Evaluate Management Tenets (3 criteria)
     * 
     * 1. Is management rational in capital allocation?
     * 2. Is management candid with shareholders?
     * 3. Does management resist the institutional imperative?
     * 
     * Note: These are qualitative and difficult to measure programmatically.
     * We use proxies: insider ownership, share buybacks, capital efficiency.
     */
    private function evaluateManagementTenets(array $fundamentals): float
    {
        $score = 0;
        $maxScore = 3;
        
        // 1. Capital allocation rationality (use ROE and ROIC as proxies)
        $roe = $fundamentals['return_on_equity'] ?? 0;
        $roic = $fundamentals['return_on_assets'] ?? 0; // Proxy for ROIC
        
        if ($roe > 0.15 && $roic > 0.10) {
            $score += 1.0; // Efficient capital allocation
        } elseif ($roe > 0.10 || $roic > 0.07) {
            $score += 0.6;
        } else {
            $score += 0.3;
        }
        
        // 2. Candor with shareholders (proxy: low debt, transparent metrics)
        $debtToEquity = $fundamentals['debt_to_equity'] ?? 1.0;
        if ($debtToEquity < 0.3) {
            $score += 1.0; // Conservative debt suggests transparency
        } elseif ($debtToEquity < 0.6) {
            $score += 0.7;
        } else {
            $score += 0.3;
        }
        
        // 3. Resist institutional imperative (proxy: consistent strategy)
        // Use profit margin stability as proxy for consistent strategy
        $profitMargin = $fundamentals['profit_margin'] ?? 0;
        if ($profitMargin > 0.15) {
            $score += 1.0; // Strong margins suggest disciplined strategy
        } elseif ($profitMargin > 0.10) {
            $score += 0.7;
        } else {
            $score += 0.4;
        }
        
        return ($score / $maxScore) * 100; // Return as 0-100 score
    }

    /**
     * Evaluate Financial Tenets (4 criteria)
     * 
     * 1. Focus on return on equity (ROE), not earnings per share
     * 2. Calculate "owner earnings" (true economic cash flow)
     * 3. Look for companies with high profit margins
     * 4. Ensure every dollar retained creates at least one dollar of market value
     */
    private function evaluateFinancialTenets(array $fundamentals, array $priceHistory): float
    {
        $score = 0;
        $maxScore = 4;
        
        // 1. Return on Equity (ROE > 15%)
        $roe = $fundamentals['return_on_equity'] ?? 0;
        $minROE = $this->parameters['min_roe_percent'] / 100;
        
        if ($roe >= $minROE) {
            $score += 1.0;
        } elseif ($roe >= $minROE * 0.8) { // Within 80% of target
            $score += 0.7;
        } elseif ($roe >= $minROE * 0.6) {
            $score += 0.4;
        }
        
        // 2. Owner Earnings (positive and growing)
        $ownerEarnings = $this->calculateOwnerEarnings($fundamentals);
        if ($ownerEarnings > 0) {
            $score += 1.0;
        } elseif ($ownerEarnings > -1000000) { // Small negative acceptable
            $score += 0.5;
        }
        
        // 3. High Profit Margins (> 20%)
        $profitMargin = $fundamentals['profit_margin'] ?? 0;
        $minMargin = $this->parameters['min_profit_margin_percent'] / 100;
        
        if ($profitMargin >= $minMargin) {
            $score += 1.0;
        } elseif ($profitMargin >= $minMargin * 0.75) {
            $score += 0.7;
        } elseif ($profitMargin >= $minMargin * 0.5) {
            $score += 0.4;
        }
        
        // 4. Retained earnings creating value (ROE consistency)
        // If ROE is stable/growing, retained earnings are creating value
        if ($roe > 0.12) {
            $score += 1.0;
        } elseif ($roe > 0.08) {
            $score += 0.6;
        } else {
            $score += 0.2;
        }
        
        return ($score / $maxScore) * 100; // Return as 0-100 score
    }

    /**
     * Evaluate Value Tenet (1 criteria)
     * 
     * Determine the intrinsic value of the business.
     * Only buy with a margin of safety (price < intrinsic value * 0.75)
     */
    private function evaluateValueTenet(float $currentPrice, float $intrinsicValue): float
    {
        if ($intrinsicValue <= 0 || $currentPrice <= 0) {
            return 0;
        }
        
        $priceToValue = $currentPrice / $intrinsicValue;
        $marginOfSafety = 1 - $priceToValue;
        $requiredMargin = $this->parameters['margin_of_safety_percent'] / 100;
        
        // Score based on margin of safety
        if ($marginOfSafety >= $requiredMargin) {
            return 100; // Excellent value
        } elseif ($marginOfSafety >= $requiredMargin * 0.6) {
            return 70; // Good value
        } elseif ($marginOfSafety >= 0) {
            return 40; // Fair value
        } else {
            return 0; // Overvalued
        }
    }

    /**
     * Calculate Owner Earnings
     * 
     * Owner Earnings = Net Income 
     *                 + Depreciation/Amortization 
     *                 - Capital Expenditures 
     *                 - Working Capital Increases
     * 
     * This represents the true cash available to owners.
     */
    private function calculateOwnerEarnings(array $fundamentals): float
    {
        $netIncome = $fundamentals['net_income'] ?? 0;
        $depreciation = $fundamentals['depreciation_amortization'] ?? 0;
        $capex = $fundamentals['capital_expenditures'] ?? 0;
        $workingCapitalChange = $fundamentals['working_capital_change'] ?? 0;
        
        // If we don't have detailed cash flow data, use operating cash flow as proxy
        $operatingCashFlow = $fundamentals['operating_cash_flow'] ?? null;
        
        if ($operatingCashFlow !== null) {
            // Operating Cash Flow - CapEx is a good approximation
            return $operatingCashFlow - abs($capex);
        }
        
        // Otherwise calculate from components
        $ownerEarnings = $netIncome + $depreciation - abs($capex) - $workingCapitalChange;
        
        return $ownerEarnings;
    }

    /**
     * Calculate Intrinsic Value using Discounted Cash Flow (DCF)
     * 
     * Projects owner earnings 10 years into the future and discounts
     * to present value.
     */
    private function calculateIntrinsicValue(array $fundamentals, array $priceHistory): float
    {
        $ownerEarnings = $this->calculateOwnerEarnings($fundamentals);
        
        if ($ownerEarnings <= 0) {
            return 0; // Can't value company with no earnings
        }
        
        $sharesOutstanding = $fundamentals['shares_outstanding'] ?? 1;
        $ownerEarningsPerShare = $ownerEarnings / $sharesOutstanding;
        
        $discountRate = $this->parameters['discount_rate_percent'] / 100;
        $growthRate = $this->parameters['perpetual_growth_rate'] / 100;
        
        // Estimate growth rate from historical data if available
        $historicalGrowth = $fundamentals['earnings_growth'] ?? $growthRate;
        $projectedGrowth = min($historicalGrowth, 0.15); // Cap at 15% for conservatism
        
        // Project 10 years of owner earnings
        $presentValue = 0;
        $futureEarnings = $ownerEarningsPerShare;
        
        for ($year = 1; $year <= 10; $year++) {
            $futureEarnings *= (1 + $projectedGrowth);
            $discountFactor = pow(1 + $discountRate, $year);
            $presentValue += $futureEarnings / $discountFactor;
        }
        
        // Terminal value (perpetuity after year 10)
        $terminalValue = ($futureEarnings * (1 + $growthRate)) / ($discountRate - $growthRate);
        $discountedTerminalValue = $terminalValue / pow(1 + $discountRate, 10);
        
        $intrinsicValuePerShare = $presentValue + $discountedTerminalValue;
        
        return max(0, $intrinsicValuePerShare);
    }

    /**
     * Calculate Economic Moat Strength
     * 
     * A moat is a sustainable competitive advantage. We evaluate:
     * - Brand strength (profit margins)
     * - Cost advantages (ROA, efficiency)
     * - Network effects (market cap growth)
     * - Switching costs (customer retention proxy)
     * - Regulatory advantages (sector-specific)
     */
    private function calculateMoatStrength(array $fundamentals, array $priceHistory): float
    {
        $moatScore = 0;
        $maxScore = 5;
        
        // 1. Brand strength (high profit margins suggest pricing power)
        $profitMargin = $fundamentals['profit_margin'] ?? 0;
        if ($profitMargin > 0.25) {
            $moatScore += 1.0;
        } elseif ($profitMargin > 0.15) {
            $moatScore += 0.7;
        } elseif ($profitMargin > 0.10) {
            $moatScore += 0.4;
        }
        
        // 2. Cost advantages (high ROA suggests efficiency)
        $roa = $fundamentals['return_on_assets'] ?? 0;
        if ($roa > 0.15) {
            $moatScore += 1.0;
        } elseif ($roa > 0.10) {
            $moatScore += 0.7;
        } elseif ($roa > 0.05) {
            $moatScore += 0.4;
        }
        
        // 3. Network effects (market cap growth suggests expanding network)
        $marketCap = $fundamentals['market_cap'] ?? 0;
        if ($marketCap > 1000000000) { // > $1B
            $moatScore += 1.0;
        } elseif ($marketCap > 300000000) { // > $300M
            $moatScore += 0.6;
        }
        
        // 4. Switching costs (proxy: low debt suggests customer loyalty)
        $debtToEquity = $fundamentals['debt_to_equity'] ?? 1.0;
        if ($debtToEquity < 0.3) {
            $moatScore += 1.0;
        } elseif ($debtToEquity < 0.5) {
            $moatScore += 0.6;
        }
        
        // 5. Regulatory advantages (certain sectors have built-in moats)
        $regulatedSectors = ['Utilities', 'Healthcare', 'Financials'];
        $sector = $fundamentals['sector'] ?? '';
        if (in_array($sector, $regulatedSectors)) {
            $moatScore += 1.0;
        } else {
            $moatScore += 0.5; // Partial credit for other sectors
        }
        
        return ($moatScore / $maxScore) * 100; // Return as 0-100 score
    }

    /**
     * Determine trading signal based on quality and value
     */
    private function determineSignal(
        float $qualityScore,
        float $valueScore,
        float $marginOfSafety,
        float $currentPrice,
        float $intrinsicValue
    ): string {
        $requiredMargin = $this->parameters['margin_of_safety_percent'] / 100;
        
        // BUY: High quality + Good value + Margin of safety
        if ($qualityScore >= 70 && $valueScore >= 70 && $marginOfSafety >= $requiredMargin) {
            return 'BUY';
        }
        
        // BUY: Exceptional quality even with smaller margin
        if ($qualityScore >= 85 && $marginOfSafety >= $requiredMargin * 0.6) {
            return 'BUY';
        }
        
        // SELL: Overvalued (price > 110% of intrinsic value)
        if ($intrinsicValue > 0 && $currentPrice > $intrinsicValue * 1.10) {
            return 'SELL';
        }
        
        // SELL: Low quality
        if ($qualityScore < 50) {
            return 'SELL';
        }
        
        return 'HOLD';
    }

    /**
     * Calculate position size based on quality and margin of safety
     */
    private function calculatePositionSize(float $qualityScore, float $marginOfSafety): float
    {
        $baseSize = $this->parameters['max_position_size'];
        
        // Scale position size based on quality and value
        $qualityFactor = $qualityScore / 100;
        $valueFactor = max(0, min(1, $marginOfSafety * 2)); // 50% margin = 1.0 factor
        
        $positionSize = $baseSize * $qualityFactor * $valueFactor;
        
        return round(min($positionSize, $this->parameters['max_position_size']), 4);
    }

    /**
     * Calculate confidence based on all factors
     */
    private function calculateConfidence(
        float $qualityScore,
        float $valueScore,
        float $marginOfSafety,
        float $moatStrength
    ): float {
        $qualityWeight = 0.35;
        $valueWeight = 0.35;
        $marginWeight = 0.20;
        $moatWeight = 0.10;
        
        $marginScore = max(0, min(100, $marginOfSafety * 200)); // Convert margin to 0-100 scale
        
        $confidence = (
            ($qualityScore * $qualityWeight) +
            ($valueScore * $valueWeight) +
            ($marginScore * $marginWeight) +
            ($moatStrength * $moatWeight)
        ) / 100;
        
        return round(min(1.0, max(0.0, $confidence)), 4);
    }

    /**
     * Generate human-readable reason for signal
     */
    private function generateReason(
        string $signal,
        float $qualityScore,
        float $marginOfSafety,
        float $intrinsicValue,
        float $currentPrice
    ): string {
        $marginPercent = round($marginOfSafety * 100, 1);
        
        if ($signal === 'BUY') {
            return "High-quality business (score: {$qualityScore}/100) trading at " .
                   "{$marginPercent}% below intrinsic value ($" . round($intrinsicValue, 2) . "). " .
                   "Meets Warren Buffett's criteria for a margin of safety purchase.";
        } elseif ($signal === 'SELL') {
            if ($qualityScore < 50) {
                return "Low-quality business (score: {$qualityScore}/100) does not meet " .
                       "Buffett's standards for excellence.";
            } else {
                return "Trading above intrinsic value ($" . round($intrinsicValue, 2) . "). " .
                       "Price ($" . round($currentPrice, 2) . ") exceeds fair value - " .
                       "margin of safety violated.";
            }
        } else {
            return "Business quality acceptable but insufficient margin of safety " .
                   "({$marginPercent}% vs required {$this->parameters['margin_of_safety_percent']}%). " .
                   "Waiting for better value.";
        }
    }

    /**
     * Create a default HOLD signal
     */
    private function createHoldSignal(string $symbol, string $reason): array
    {
        return [
            'signal' => 'HOLD',
            'confidence' => 0.0,
            'reason' => $reason,
            'entry_price' => null,
            'stop_loss' => null,
            'take_profit' => null,
            'position_size' => 0.0,
            'metadata' => [
                'strategy' => 'Warren Buffett Value',
                'error' => $reason
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
        // Check if we have sufficient fundamental data
        try {
            $fundamentals = $this->marketDataService->getFundamentals($symbol);
            
            // Need at least basic financial data
            $requiredFields = ['net_income', 'total_assets', 'total_equity', 'revenue'];
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
        // Need 10 years of data for proper DCF valuation and consistency analysis
        return 365 * 10;
    }
}
