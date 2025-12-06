<?php

declare(strict_types=1);

namespace App\Risk;

/**
 * Conditional Value at Risk (CVaR) calculator
 * Also known as Expected Shortfall
 */
class CVaRCalculator
{
    /**
     * Calculate CVaR using historical returns
     * CVaR represents the expected loss given that the loss exceeds VaR
     *
     * @param array $returns Historical returns
     * @param float $confidenceLevel Confidence level
     * @return float CVaR value
     */
    public function calculateHistorical(array $returns, float $confidenceLevel = 0.95): float
    {
        if (empty($returns)) {
            return 0.0;
        }
        
        sort($returns);
        
        $index = (int) floor((1 - $confidenceLevel) * count($returns));
        
        // CVaR is the average of all losses beyond VaR
        $tailLosses = array_slice($returns, 0, max(1, $index));
        
        if (empty($tailLosses)) {
            return 0.0;
        }
        
        return abs(array_sum($tailLosses) / count($tailLosses));
    }
    
    /**
     * Calculate CVaR for a portfolio
     *
     * @param float $portfolioValue Total portfolio value
     * @param array $returns Historical returns
     * @param float $confidenceLevel Confidence level
     * @return float CVaR in dollar terms
     */
    public function calculatePortfolioCVaR(float $portfolioValue, array $returns, float $confidenceLevel = 0.95): float
    {
        $percentageCVaR = $this->calculateHistorical($returns, $confidenceLevel);
        
        return $portfolioValue * $percentageCVaR;
    }
    
    /**
     * Calculate the ratio of CVaR to VaR
     * Higher ratio indicates fatter tails in loss distribution
     *
     * @param array $returns Historical returns
     * @param float $confidenceLevel Confidence level
     * @return float CVaR/VaR ratio
     */
    public function calculateCVaRToVaRRatio(array $returns, float $confidenceLevel = 0.95): float
    {
        $varCalc = new VaRCalculator();
        $var = $varCalc->calculateHistorical($returns, $confidenceLevel);
        
        if ($var == 0.0) {
            return 0.0;
        }
        
        $cvar = $this->calculateHistorical($returns, $confidenceLevel);
        
        return $cvar / $var;
    }
}
