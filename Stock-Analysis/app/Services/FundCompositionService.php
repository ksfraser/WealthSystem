<?php

namespace App\Services;

use App\DAOs\FundDAO;
use App\DAOs\FundHoldingDAO;
use App\DAOs\FundEligibilityDAO;
use App\Models\Fund;
use App\Models\FundHolding;

/**
 * Fund Composition Service
 * 
 * Analyzes ETF, mutual fund, and segregated fund composition, holdings, and eligibility.
 * Handles multiple fund codes for same underlying fund with different MER tiers.
 */
class FundCompositionService
{
    private FundDAO $fundDAO;
    private FundHoldingDAO $holdingDAO;
    private FundEligibilityDAO $eligibilityDAO;
    private MarketDataService $marketDataService;
    
    // Fund types
    const TYPE_ETF = 'ETF';
    const TYPE_MUTUAL_FUND = 'MUTUAL_FUND';
    const TYPE_SEGREGATED_FUND = 'SEGREGATED_FUND';
    const TYPE_INDEX_FUND = 'INDEX_FUND';
    
    // MER tier levels (Management Expense Ratio)
    const MER_TIER_RETAIL = 'RETAIL';        // Highest MER, lowest minimum
    const MER_TIER_PREFERRED = 'PREFERRED';  // Mid MER, moderate minimum
    const MER_TIER_PREMIUM = 'PREMIUM';      // Lower MER, higher minimum
    const MER_TIER_INSTITUTIONAL = 'INSTITUTIONAL'; // Lowest MER, institutional minimum
    
    public function __construct(
        ?FundDAO $fundDAO = null,
        ?FundHoldingDAO $holdingDAO = null,
        ?FundEligibilityDAO $eligibilityDAO = null,
        ?MarketDataService $marketDataService = null
    ) {
        $this->fundDAO = $fundDAO ?? new FundDAO();
        $this->holdingDAO = $holdingDAO ?? new FundHoldingDAO();
        $this->eligibilityDAO = $eligibilityDAO ?? new FundEligibilityDAO();
        $this->marketDataService = $marketDataService ?? new MarketDataService();
    }
    
    /**
     * Get fund composition with all holdings and weightings
     * 
     * @param string $fundSymbol Fund ticker symbol
     * @return array Fund composition details
     */
    public function getFundComposition(string $fundSymbol): array
    {
        // Get fund metadata
        $fund = $this->fundDAO->getBySymbol($fundSymbol);
        if (!$fund) {
            return ['error' => 'Fund not found'];
        }
        
        // Get all holdings
        $holdings = $this->holdingDAO->getHoldingsByFund($fundSymbol);
        
        // Calculate aggregate metrics
        $totalWeight = array_sum(array_column($holdings, 'weight'));
        $topHoldings = array_slice($holdings, 0, 10);
        
        // Sector allocation
        $sectorAllocation = $this->calculateSectorAllocation($holdings);
        
        // Asset class breakdown
        $assetClassBreakdown = $this->calculateAssetClassBreakdown($holdings);
        
        // Geographic exposure
        $geographicExposure = $this->calculateGeographicExposure($holdings);
        
        return [
            'fund' => [
                'symbol' => $fund->getSymbol(),
                'name' => $fund->getName(),
                'type' => $fund->getType(),
                'family' => $fund->getFundFamily(),
                'mer' => $fund->getMer(),
                'mer_tier' => $fund->getMerTier(),
                'aum' => $fund->getAum(),
                'inception_date' => $fund->getInceptionDate(),
                'expense_ratio' => $fund->getExpenseRatio()
            ],
            'holdings' => [
                'total_count' => count($holdings),
                'total_weight' => $totalWeight,
                'top_10' => $topHoldings,
                'all' => $holdings
            ],
            'allocations' => [
                'sector' => $sectorAllocation,
                'asset_class' => $assetClassBreakdown,
                'geography' => $geographicExposure
            ],
            'concentration' => $this->calculateConcentration($holdings),
            'turnover' => $fund->getTurnoverRate(),
            'tracking_error' => $fund->getTrackingError()
        ];
    }
    
    /**
     * Compare holdings overlap between two funds
     * 
     * @param string $fund1Symbol First fund symbol
     * @param string $fund2Symbol Second fund symbol
     * @return array Overlap analysis
     */
    public function compareFundOverlap(string $fund1Symbol, string $fund2Symbol): array
    {
        $holdings1 = $this->holdingDAO->getHoldingsByFund($fund1Symbol);
        $holdings2 = $this->holdingDAO->getHoldingsByFund($fund2Symbol);
        
        // Build symbol maps
        $symbols1 = array_column($holdings1, 'holding_symbol');
        $symbols2 = array_column($holdings2, 'holding_symbol');
        
        // Calculate overlap
        $commonSymbols = array_intersect($symbols1, $symbols2);
        $overlapCount = count($commonSymbols);
        
        // Weight-adjusted overlap
        $weightedOverlap = 0.0;
        foreach ($commonSymbols as $symbol) {
            $weight1 = $this->getHoldingWeight($holdings1, $symbol);
            $weight2 = $this->getHoldingWeight($holdings2, $symbol);
            $weightedOverlap += min($weight1, $weight2);
        }
        
        $overlapPercent = ($overlapCount / max(count($symbols1), count($symbols2))) * 100;
        
        return [
            'fund1' => $fund1Symbol,
            'fund2' => $fund2Symbol,
            'common_holdings' => $overlapCount,
            'overlap_percent' => round($overlapPercent, 2),
            'weighted_overlap' => round($weightedOverlap, 2),
            'common_symbols' => array_values($commonSymbols),
            'unique_to_fund1' => array_diff($symbols1, $symbols2),
            'unique_to_fund2' => array_diff($symbols2, $symbols1),
            'interpretation' => $this->interpretOverlap($overlapPercent)
        ];
    }
    
    /**
     * Get all fund codes for same underlying fund (different MER tiers)
     * 
     * @param string $baseFundId Base fund identifier
     * @return array List of fund codes with MER tiers
     */
    public function getFundFamilyVariants(string $baseFundId): array
    {
        $variants = $this->fundDAO->getByBaseFund($baseFundId);
        
        usort($variants, function($a, $b) {
            return $a->getMer() <=> $b->getMer(); // Sort by MER (lowest first)
        });
        
        return array_map(function($fund) {
            return [
                'symbol' => $fund->getSymbol(),
                'name' => $fund->getName(),
                'fund_code' => $fund->getFundCode(),
                'mer' => $fund->getMer(),
                'mer_tier' => $fund->getMerTier(),
                'minimum_investment' => $fund->getMinimumInvestment(),
                'minimum_net_worth' => $fund->getMinimumNetWorth(),
                'is_institutional' => $fund->isInstitutional(),
                'annual_fee_savings' => $this->calculateFeeSavings($fund)
            ];
        }, $variants);
    }
    
    /**
     * Filter funds based on client eligibility (net worth thresholds)
     * 
     * @param float $clientNetWorth Client's net worth
     * @param float $familyNetWorth Family's combined net worth
     * @param array $fundSymbols List of fund symbols to check
     * @return array Eligible and ineligible funds
     */
    public function filterByEligibility(
        float $clientNetWorth,
        float $familyNetWorth,
        array $fundSymbols
    ): array {
        $eligible = [];
        $ineligible = [];
        $upgradeable = [];
        
        foreach ($fundSymbols as $symbol) {
            $fund = $this->fundDAO->getBySymbol($symbol);
            if (!$fund) {
                continue;
            }
            
            $eligibility = $this->checkEligibility($fund, $clientNetWorth, $familyNetWorth);
            
            if ($eligibility['is_eligible']) {
                $eligible[] = [
                    'symbol' => $symbol,
                    'name' => $fund->getName(),
                    'mer' => $fund->getMer(),
                    'mer_tier' => $fund->getMerTier(),
                    'qualified_by' => $eligibility['qualified_by']
                ];
                
                // Check for upgrade opportunities
                $upgrade = $this->checkForUpgrade($fund, $clientNetWorth, $familyNetWorth);
                if ($upgrade) {
                    $upgradeable[] = [
                        'current_fund' => $symbol,
                        'upgrade_to' => $upgrade['symbol'],
                        'mer_savings' => $upgrade['mer_savings'],
                        'annual_savings' => $upgrade['annual_savings']
                    ];
                }
            } else {
                $ineligible[] = [
                    'symbol' => $symbol,
                    'name' => $fund->getName(),
                    'mer' => $fund->getMer(),
                    'mer_tier' => $fund->getMerTier(),
                    'reason' => $eligibility['reason'],
                    'shortfall' => $eligibility['shortfall']
                ];
            }
        }
        
        return [
            'client_net_worth' => $clientNetWorth,
            'family_net_worth' => $familyNetWorth,
            'eligible' => $eligible,
            'ineligible' => $ineligible,
            'upgrade_opportunities' => $upgradeable,
            'total_eligible' => count($eligible),
            'total_ineligible' => count($ineligible)
        ];
    }
    
    /**
     * Compare MERs across fund family variants
     * 
     * @param string $baseFundId Base fund identifier
     * @param float $investmentAmount Investment amount
     * @return array MER comparison with cost projections
     */
    public function compareMERs(string $baseFundId, float $investmentAmount = 100000): array
    {
        $variants = $this->getFundFamilyVariants($baseFundId);
        
        $comparisons = [];
        foreach ($variants as $variant) {
            $annualFee = $investmentAmount * ($variant['mer'] / 100);
            $fee10Years = $this->projectFees($investmentAmount, $variant['mer'], 10);
            $fee25Years = $this->projectFees($investmentAmount, $variant['mer'], 25);
            
            $comparisons[] = [
                'fund_code' => $variant['fund_code'],
                'mer' => $variant['mer'],
                'mer_tier' => $variant['mer_tier'],
                'minimum_investment' => $variant['minimum_investment'],
                'annual_fee' => round($annualFee, 2),
                'fee_10_years' => round($fee10Years, 2),
                'fee_25_years' => round($fee25Years, 2)
            ];
        }
        
        return [
            'base_fund' => $baseFundId,
            'investment_amount' => $investmentAmount,
            'variants' => $comparisons,
            'mer_range' => [
                'lowest' => min(array_column($comparisons, 'mer')),
                'highest' => max(array_column($comparisons, 'mer'))
            ],
            'potential_savings' => $this->calculatePotentialSavings($comparisons)
        ];
    }
    
    /**
     * Analyze fund performance vs benchmark
     * 
     * @param string $fundSymbol Fund symbol
     * @param string $benchmarkSymbol Benchmark index
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Performance comparison
     */
    public function analyzeFundPerformance(
        string $fundSymbol,
        string $benchmarkSymbol,
        string $startDate,
        string $endDate
    ): array {
        $fundPrices = $this->marketDataService->getHistoricalPrices($fundSymbol, $startDate, $endDate);
        $benchmarkPrices = $this->marketDataService->getHistoricalPrices($benchmarkSymbol, $startDate, $endDate);
        
        if (empty($fundPrices) || empty($benchmarkPrices)) {
            return ['error' => 'Insufficient data'];
        }
        
        $fund = $this->fundDAO->getBySymbol($fundSymbol);
        
        // Calculate returns
        $fundReturn = $this->calculateReturn($fundPrices);
        $benchmarkReturn = $this->calculateReturn($benchmarkPrices);
        
        // Adjust fund return for fees
        $years = $this->calculateYears($startDate, $endDate);
        $totalFees = $fund->getMer() * $years;
        $netReturn = $fundReturn - $totalFees;
        
        // Alpha after fees
        $alpha = $netReturn - $benchmarkReturn;
        
        // Sharpe ratio
        $sharpeRatio = $this->calculateSharpeRatio($fundPrices);
        
        return [
            'fund' => [
                'symbol' => $fundSymbol,
                'name' => $fund->getName(),
                'gross_return' => round($fundReturn, 2),
                'mer' => $fund->getMer(),
                'fees_paid' => round($totalFees, 2),
                'net_return' => round($netReturn, 2)
            ],
            'benchmark' => [
                'symbol' => $benchmarkSymbol,
                'return' => round($benchmarkReturn, 2)
            ],
            'comparison' => [
                'alpha' => round($alpha, 2),
                'outperformance' => $netReturn > $benchmarkReturn,
                'sharpe_ratio' => round($sharpeRatio, 2),
                'tracking_error' => $fund->getTrackingError()
            ],
            'interpretation' => $this->interpretFundPerformance($alpha, $fund->getMer())
        ];
    }
    
    /**
     * Check if client qualifies for specific fund
     * 
     * @param Fund $fund Fund to check
     * @param float $clientNetWorth Client net worth
     * @param float $familyNetWorth Family net worth
     * @return array Eligibility result
     */
    private function checkEligibility(Fund $fund, float $clientNetWorth, float $familyNetWorth): array
    {
        $minNetWorth = $fund->getMinimumNetWorth();
        $allowsFamilyAggregation = $fund->allowsFamilyAggregation();
        
        // Check individual eligibility
        if ($clientNetWorth >= $minNetWorth) {
            return [
                'is_eligible' => true,
                'qualified_by' => 'individual_net_worth',
                'reason' => 'Client net worth meets minimum requirement'
            ];
        }
        
        // Check family aggregation
        if ($allowsFamilyAggregation && $familyNetWorth >= $minNetWorth) {
            return [
                'is_eligible' => true,
                'qualified_by' => 'family_net_worth',
                'reason' => 'Family net worth meets minimum requirement'
            ];
        }
        
        $shortfall = $minNetWorth - max($clientNetWorth, $familyNetWorth);
        
        return [
            'is_eligible' => false,
            'qualified_by' => null,
            'reason' => 'Net worth below minimum requirement',
            'shortfall' => $shortfall
        ];
    }
    
    /**
     * Check for upgrade opportunities to lower MER tiers
     */
    private function checkForUpgrade(Fund $currentFund, float $clientNetWorth, float $familyNetWorth): ?array
    {
        $baseFundId = $currentFund->getBaseFundId();
        if (!$baseFundId) {
            return null;
        }
        
        $variants = $this->fundDAO->getByBaseFund($baseFundId);
        
        foreach ($variants as $variant) {
            // Skip current fund
            if ($variant->getSymbol() === $currentFund->getSymbol()) {
                continue;
            }
            
            // Skip if MER is higher or same
            if ($variant->getMer() >= $currentFund->getMer()) {
                continue;
            }
            
            // Check if client qualifies
            $eligibility = $this->checkEligibility($variant, $clientNetWorth, $familyNetWorth);
            if ($eligibility['is_eligible']) {
                $merSavings = $currentFund->getMer() - $variant->getMer();
                
                return [
                    'symbol' => $variant->getSymbol(),
                    'name' => $variant->getName(),
                    'mer' => $variant->getMer(),
                    'mer_savings' => round($merSavings, 2),
                    'annual_savings' => round($merSavings * 1000, 2) // Per $100k invested
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Calculate sector allocation from holdings
     */
    private function calculateSectorAllocation(array $holdings): array
    {
        $sectors = [];
        
        foreach ($holdings as $holding) {
            $sector = $holding['sector'] ?? 'Unknown';
            if (!isset($sectors[$sector])) {
                $sectors[$sector] = 0.0;
            }
            $sectors[$sector] += $holding['weight'];
        }
        
        arsort($sectors);
        
        return $sectors;
    }
    
    /**
     * Calculate asset class breakdown
     */
    private function calculateAssetClassBreakdown(array $holdings): array
    {
        $assetClasses = [];
        
        foreach ($holdings as $holding) {
            $assetClass = $holding['asset_class'] ?? 'Equity';
            if (!isset($assetClasses[$assetClass])) {
                $assetClasses[$assetClass] = 0.0;
            }
            $assetClasses[$assetClass] += $holding['weight'];
        }
        
        arsort($assetClasses);
        
        return $assetClasses;
    }
    
    /**
     * Calculate geographic exposure
     */
    private function calculateGeographicExposure(array $holdings): array
    {
        $regions = [];
        
        foreach ($holdings as $holding) {
            $region = $holding['region'] ?? 'US';
            if (!isset($regions[$region])) {
                $regions[$region] = 0.0;
            }
            $regions[$region] += $holding['weight'];
        }
        
        arsort($regions);
        
        return $regions;
    }
    
    /**
     * Calculate concentration metrics
     */
    private function calculateConcentration(array $holdings): array
    {
        if (empty($holdings)) {
            return ['top_10_concentration' => 0, 'herfindahl_index' => 0];
        }
        
        $weights = array_column($holdings, 'weight');
        rsort($weights);
        
        $top10 = array_sum(array_slice($weights, 0, 10));
        
        // Herfindahl-Hirschman Index
        $hhi = 0.0;
        foreach ($weights as $weight) {
            $hhi += pow($weight, 2);
        }
        
        return [
            'top_10_concentration' => round($top10, 2),
            'herfindahl_index' => round($hhi, 4),
            'concentration_level' => $this->interpretConcentration($top10)
        ];
    }
    
    /**
     * Project fees over time with compound returns
     */
    private function projectFees(float $principal, float $mer, int $years, float $annualReturn = 7.0): float
    {
        $totalFees = 0.0;
        $balance = $principal;
        
        for ($i = 0; $i < $years; $i++) {
            $annualFee = $balance * ($mer / 100);
            $totalFees += $annualFee;
            
            // Compound growth after fees
            $netReturn = ($annualReturn - $mer) / 100;
            $balance *= (1 + $netReturn);
        }
        
        return $totalFees;
    }
    
    /**
     * Calculate potential savings between highest and lowest MER
     */
    private function calculatePotentialSavings(array $comparisons): array
    {
        if (count($comparisons) < 2) {
            return ['annual' => 0, 'ten_year' => 0, 'twenty_five_year' => 0];
        }
        
        $highest = $comparisons[count($comparisons) - 1];
        $lowest = $comparisons[0];
        
        return [
            'annual' => $highest['annual_fee'] - $lowest['annual_fee'],
            'ten_year' => $highest['fee_10_years'] - $lowest['fee_10_years'],
            'twenty_five_year' => $highest['fee_25_years'] - $lowest['fee_25_years']
        ];
    }
    
    /**
     * Get holding weight by symbol
     */
    private function getHoldingWeight(array $holdings, string $symbol): float
    {
        foreach ($holdings as $holding) {
            if ($holding['holding_symbol'] === $symbol) {
                return $holding['weight'];
            }
        }
        return 0.0;
    }
    
    /**
     * Calculate return from price history
     */
    private function calculateReturn(array $prices): float
    {
        if (count($prices) < 2) {
            return 0.0;
        }
        
        $startPrice = $prices[0]['close'];
        $endPrice = $prices[count($prices) - 1]['close'];
        
        return (($endPrice - $startPrice) / $startPrice) * 100;
    }
    
    /**
     * Calculate years between dates
     */
    private function calculateYears(string $startDate, string $endDate): float
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = $start->diff($end);
        
        return $interval->y + ($interval->m / 12) + ($interval->d / 365);
    }
    
    /**
     * Calculate Sharpe ratio
     */
    private function calculateSharpeRatio(array $prices, float $riskFreeRate = 0.04): float
    {
        if (count($prices) < 2) {
            return 0.0;
        }
        
        $returns = [];
        for ($i = 1; $i < count($prices); $i++) {
            $returns[] = ($prices[$i]['close'] - $prices[$i-1]['close']) / $prices[$i-1]['close'];
        }
        
        $avgReturn = array_sum($returns) / count($returns);
        $variance = 0.0;
        foreach ($returns as $return) {
            $variance += pow($return - $avgReturn, 2);
        }
        $stdDev = sqrt($variance / count($returns));
        
        if ($stdDev == 0) {
            return 0.0;
        }
        
        $annualizedReturn = $avgReturn * 252;
        $annualizedStdDev = $stdDev * sqrt(252);
        
        return ($annualizedReturn - $riskFreeRate) / $annualizedStdDev;
    }
    
    /**
     * Calculate fee savings vs retail tier
     */
    private function calculateFeeSavings(Fund $fund): float
    {
        $baseFundId = $fund->getBaseFundId();
        if (!$baseFundId) {
            return 0.0;
        }
        
        $retailFund = $this->fundDAO->getRetailVersion($baseFundId);
        if (!$retailFund) {
            return 0.0;
        }
        
        return ($retailFund->getMer() - $fund->getMer()) * 1000; // Per $100k
    }
    
    /**
     * Interpret overlap percentage
     */
    private function interpretOverlap(float $overlapPercent): string
    {
        if ($overlapPercent > 80) {
            return 'Very High - Funds are highly redundant';
        } elseif ($overlapPercent > 60) {
            return 'High - Significant overlap, consider diversification';
        } elseif ($overlapPercent > 40) {
            return 'Moderate - Some overlap but still provides diversification';
        } elseif ($overlapPercent > 20) {
            return 'Low - Good diversification between funds';
        } else {
            return 'Very Low - Excellent diversification';
        }
    }
    
    /**
     * Interpret concentration level
     */
    private function interpretConcentration(float $top10Percent): string
    {
        if ($top10Percent > 50) {
            return 'High - Concentrated in top holdings';
        } elseif ($top10Percent > 35) {
            return 'Moderate - Some concentration';
        } else {
            return 'Low - Well diversified';
        }
    }
    
    /**
     * Interpret fund performance vs benchmark
     */
    private function interpretFundPerformance(float $alpha, float $mer): string
    {
        if ($alpha > $mer) {
            return 'Excellent - Alpha exceeds fees, active management adds value';
        } elseif ($alpha > 0) {
            return 'Good - Positive alpha but fees erode some gains';
        } elseif ($alpha > -$mer) {
            return 'Fair - Underperforming but fees are the main drag';
        } else {
            return 'Poor - Underperforming even before fees, consider index alternative';
        }
    }
}
