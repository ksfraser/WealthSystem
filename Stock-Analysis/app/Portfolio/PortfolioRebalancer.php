<?php

declare(strict_types=1);

namespace App\Portfolio;

/**
 * Portfolio Rebalancing Service
 * 
 * Manages portfolio rebalancing to maintain target asset allocations.
 * Accounts for:
 * - Allocation drift (market movements changing portfolio weights)
 * - Transaction costs (commissions, fees)
 * - Tax implications (capital gains on sales)
 * - Rebalancing thresholds (avoid unnecessary trades)
 * 
 * Provides:
 * - Current allocation analysis
 * - Drift calculation
 * - Rebalancing action recommendations
 * - Cost-benefit analysis
 * - Tax-efficient optimization
 * 
 * @package App\Portfolio
 */
class PortfolioRebalancer
{
    /**
     * Rebalancer configuration
     *
     * @var array{
     *     drift_threshold: float,
     *     min_trade_size: float
     * }
     */
    private array $config;

    /**
     * Initialize portfolio rebalancer with optional configuration.
     *
     * @param array{
     *     drift_threshold?: float,
     *     min_trade_size?: float
     * } $config Rebalancer configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'drift_threshold' => 5.0,    // Rebalance when allocation drifts >5%
            'min_trade_size' => 100.0    // Minimum trade value to avoid micro-trades
        ], $config);
    }

    /**
     * Calculate current portfolio allocations as percentages.
     *
     * @param array<array{symbol: string, shares: float, price: float}> $holdings Portfolio holdings
     * @return array<string, float> Symbol => percentage allocation
     */
    public function calculateCurrentAllocations(array $holdings): array
    {
        if (empty($holdings)) {
            return [];
        }

        // Calculate total portfolio value
        $totalValue = 0.0;
        $values = [];

        foreach ($holdings as $holding) {
            $value = $holding['shares'] * $holding['price'];
            $values[$holding['symbol']] = $value;
            $totalValue += $value;
        }

        // Calculate percentage allocations
        $allocations = [];
        foreach ($values as $symbol => $value) {
            $allocations[$symbol] = ($value / $totalValue) * 100;
        }

        return $allocations;
    }

    /**
     * Calculate allocation drift (difference between current and target).
     *
     * @param array<string, float> $current Current allocations (percentages)
     * @param array<string, float> $target Target allocations (percentages)
     * @return array<string, float> Symbol => drift percentage (positive = overweight, negative = underweight)
     */
    public function calculateDrift(array $current, array $target): array
    {
        $drift = [];

        foreach ($target as $symbol => $targetPercent) {
            $currentPercent = $current[$symbol] ?? 0.0;
            $drift[$symbol] = $currentPercent - $targetPercent;
        }

        return $drift;
    }

    /**
     * Determine if portfolio needs rebalancing based on drift threshold.
     *
     * @param array<array{symbol: string, shares: float, price: float}> $holdings Portfolio holdings
     * @param array<string, float> $targetAllocations Target allocations (percentages)
     * @param float $threshold Drift threshold percentage
     * @return bool True if any position exceeds drift threshold
     */
    public function needsRebalancing(array $holdings, array $targetAllocations, float $threshold): bool
    {
        $current = $this->calculateCurrentAllocations($holdings);
        $drift = $this->calculateDrift($current, $targetAllocations);

        foreach ($drift as $driftPercent) {
            if (abs($driftPercent) > $threshold) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate rebalancing actions to achieve target allocations.
     *
     * @param array<array{symbol: string, shares: float, price: float, cost_basis?: float}> $holdings Portfolio holdings
     * @param array<string, float> $targetAllocations Target allocations (percentages, must sum to 100)
     * @return array<array{
     *     symbol: string,
     *     action: string,
     *     shares: int,
     *     price: float,
     *     current_value: float,
     *     target_value: float,
     *     difference: float,
     *     cost_basis?: float
     * }> Array of rebalancing actions
     * @throws \InvalidArgumentException If target allocations don't sum to 100%
     */
    public function generateRebalancingActions(array $holdings, array $targetAllocations): array
    {
        // Validate target allocations sum to 100%
        $totalTarget = array_sum($targetAllocations);
        if (abs($totalTarget - 100.0) > 0.1) {
            throw new \InvalidArgumentException(
                "Target allocations must sum to 100%, got {$totalTarget}%"
            );
        }

        // Calculate total portfolio value
        $totalValue = 0.0;
        $holdingsMap = [];

        foreach ($holdings as $holding) {
            $value = $holding['shares'] * $holding['price'];
            $totalValue += $value;
            $holdingsMap[$holding['symbol']] = $holding;
        }

        // Generate actions for each position
        $actions = [];

        foreach ($targetAllocations as $symbol => $targetPercent) {
            $targetValue = ($targetPercent / 100) * $totalValue;
            $holding = $holdingsMap[$symbol] ?? null;

            if ($holding === null) {
                // Position doesn't exist, need to buy
                $currentValue = 0.0;
                $price = 0.0;  // Would need market data for new positions
                continue;  // Skip new positions for now
            }

            $currentValue = $holding['shares'] * $holding['price'];
            $difference = $targetValue - $currentValue;

            // Skip if difference is below minimum trade size
            if (abs($difference) < $this->config['min_trade_size']) {
                continue;
            }

            // Determine action and calculate shares
            if ($difference > 0) {
                // Need to BUY
                $sharesToTrade = (int) floor($difference / $holding['price']);
                if ($sharesToTrade > 0) {
                    $actions[] = [
                        'symbol' => $symbol,
                        'action' => 'BUY',
                        'shares' => $sharesToTrade,
                        'price' => $holding['price'],
                        'current_value' => $currentValue,
                        'target_value' => $targetValue,
                        'difference' => $difference
                    ];
                }
            } else {
                // Need to SELL
                $sharesToTrade = (int) ceil(abs($difference) / $holding['price']);
                if ($sharesToTrade > 0) {
                    $action = [
                        'symbol' => $symbol,
                        'action' => 'SELL',
                        'shares' => $sharesToTrade,
                        'price' => $holding['price'],
                        'current_value' => $currentValue,
                        'target_value' => $targetValue,
                        'difference' => $difference
                    ];

                    // Include cost basis for tax calculations
                    if (isset($holding['cost_basis'])) {
                        $action['cost_basis'] = $holding['cost_basis'];
                    }

                    $actions[] = $action;
                }
            }
        }

        return $actions;
    }

    /**
     * Calculate transaction costs for rebalancing actions.
     *
     * @param array<array{action: string, shares: int, price: float}> $actions Rebalancing actions
     * @param float $feeRate Transaction fee rate (e.g., 0.01 for 1%)
     * @return float Total transaction cost
     */
    public function calculateTransactionCosts(array $actions, float $feeRate): float
    {
        $totalCost = 0.0;

        foreach ($actions as $action) {
            $tradeValue = $action['shares'] * $action['price'];
            $totalCost += $tradeValue * $feeRate;
        }

        return $totalCost;
    }

    /**
     * Estimate tax impact of selling positions.
     *
     * @param array<array{
     *     action: string,
     *     shares: int,
     *     price: float,
     *     cost_basis?: float
     * }> $actions Rebalancing actions
     * @param float $taxRate Capital gains tax rate (e.g., 0.20 for 20%)
     * @return float Estimated tax liability
     */
    public function estimateTaxImpact(array $actions, float $taxRate): float
    {
        $totalTax = 0.0;

        foreach ($actions as $action) {
            // Only SELL actions trigger capital gains tax
            if ($action['action'] !== 'SELL') {
                continue;
            }

            // Skip if no cost basis provided
            if (!isset($action['cost_basis'])) {
                continue;
            }

            // Calculate capital gain
            $saleProceeds = $action['shares'] * $action['price'];
            $costBasis = $action['shares'] * $action['cost_basis'];
            $capitalGain = $saleProceeds - $costBasis;

            // Only tax positive gains
            if ($capitalGain > 0) {
                $totalTax += $capitalGain * $taxRate;
            }
        }

        return $totalTax;
    }

    /**
     * Generate comprehensive rebalancing summary with costs and recommendations.
     *
     * @param array<array{
     *     symbol: string,
     *     shares: float,
     *     price: float,
     *     cost_basis?: float
     * }> $holdings Portfolio holdings
     * @param array<string, float> $targetAllocations Target allocations (percentages)
     * @param float $feeRate Transaction fee rate
     * @param float $taxRate Capital gains tax rate
     * @param array{tax_efficient?: bool} $options Additional options
     * @return array{
     *     actions: array,
     *     transaction_costs: float,
     *     estimated_tax: float,
     *     total_cost: float,
     *     portfolio_value: float,
     *     recommendations: array<string>
     * } Rebalancing summary
     */
    public function generateSummary(
        array $holdings,
        array $targetAllocations,
        float $feeRate,
        float $taxRate,
        array $options = []
    ): array {
        // Calculate portfolio value
        $portfolioValue = 0.0;
        foreach ($holdings as $holding) {
            $portfolioValue += $holding['shares'] * $holding['price'];
        }

        // Generate actions
        $actions = $this->generateRebalancingActions($holdings, $targetAllocations);

        // Apply tax-efficient optimization if requested
        if ($options['tax_efficient'] ?? false) {
            $actions = $this->optimizeForTaxEfficiency($actions);
        }

        // Calculate costs
        $transactionCosts = $this->calculateTransactionCosts($actions, $feeRate);
        $estimatedTax = $this->estimateTaxImpact($actions, $taxRate);
        $totalCost = $transactionCosts + $estimatedTax;

        // Generate recommendations
        $recommendations = $this->generateRecommendations(
            $actions,
            $totalCost,
            $portfolioValue
        );

        return [
            'actions' => $actions,
            'transaction_costs' => $transactionCosts,
            'estimated_tax' => $estimatedTax,
            'total_cost' => $totalCost,
            'portfolio_value' => $portfolioValue,
            'recommendations' => $recommendations
        ];
    }

    /**
     * Optimize rebalancing actions for tax efficiency.
     * Prefers selling positions with lower capital gains.
     *
     * @param array<array{
     *     symbol: string,
     *     action: string,
     *     shares: int,
     *     price: float,
     *     cost_basis?: float
     * }> $actions Original actions
     * @return array<array> Tax-optimized actions
     */
    private function optimizeForTaxEfficiency(array $actions): array
    {
        // Separate SELL actions
        $sellActions = array_filter($actions, fn($a) => $a['action'] === 'SELL');
        $otherActions = array_filter($actions, fn($a) => $a['action'] !== 'SELL');

        // Sort SELL actions by capital gain (lowest first)
        usort($sellActions, function ($a, $b) {
            $gainA = isset($a['cost_basis']) 
                ? ($a['price'] - $a['cost_basis']) * $a['shares']
                : 0;
            $gainB = isset($b['cost_basis'])
                ? ($b['price'] - $b['cost_basis']) * $b['shares']
                : 0;
            return $gainA <=> $gainB;
        });

        return array_merge($sellActions, array_values($otherActions));
    }

    /**
     * Generate rebalancing recommendations based on analysis.
     *
     * @param array<array> $actions Rebalancing actions
     * @param float $totalCost Total rebalancing cost
     * @param float $portfolioValue Portfolio value
     * @return array<string> Array of recommendation messages
     */
    private function generateRecommendations(
        array $actions,
        float $totalCost,
        float $portfolioValue
    ): array {
        $recommendations = [];

        // Cost-benefit analysis
        $costPercentage = ($totalCost / $portfolioValue) * 100;
        if ($costPercentage > 2.0) {
            $recommendations[] = sprintf(
                'High rebalancing cost (%.2f%% of portfolio). Consider waiting for larger drift.',
                $costPercentage
            );
        } elseif ($costPercentage < 0.5) {
            $recommendations[] = 'Rebalancing cost is reasonable. Good time to rebalance.';
        }

        // Action count analysis
        $actionCount = count($actions);
        if ($actionCount === 0) {
            $recommendations[] = 'Portfolio is already well-balanced. No action needed.';
        } elseif ($actionCount > 5) {
            $recommendations[] = 'Multiple trades required. Consider phasing rebalancing over time.';
        }

        // Tax efficiency
        $sellActions = array_filter($actions, fn($a) => $a['action'] === 'SELL');
        if (count($sellActions) > 0) {
            $recommendations[] = 'Review tax implications before selling. Consider tax-loss harvesting opportunities.';
        }

        return $recommendations;
    }

    /**
     * Get rebalancer configuration.
     *
     * @return array{
     *     drift_threshold: float,
     *     min_trade_size: float
     * } Current configuration
     */
    public function getConfiguration(): array
    {
        return $this->config;
    }
}
