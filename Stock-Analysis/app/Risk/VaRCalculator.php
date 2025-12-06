<?php

declare(strict_types=1);

namespace App\Risk;

/**
 * Value at Risk (VaR) calculator using historical simulation
 */
class VaRCalculator
{
    /**
     * Calculate VaR using historical returns
     *
     * @param array $returns Historical returns
     * @param float $confidenceLevel Confidence level (e.g., 0.95 for 95%)
     * @return float VaR value
     */
    public function calculateHistorical(array $returns, float $confidenceLevel = 0.95): float
    {
        if (empty($returns)) {
            return 0.0;
        }
        
        sort($returns);
        
        $index = (int) floor((1 - $confidenceLevel) * count($returns));
        
        return abs($returns[$index]);
    }
    
    /**
     * Calculate VaR using parametric method (assumes normal distribution)
     *
     * @param float $mean Mean of returns
     * @param float $stdDev Standard deviation of returns
     * @param float $confidenceLevel Confidence level
     * @return float VaR value
     */
    public function calculateParametric(float $mean, float $stdDev, float $confidenceLevel = 0.95): float
    {
        // Z-scores for common confidence levels
        $zScores = [
            0.90 => 1.28,
            0.95 => 1.645,
            0.99 => 2.326,
        ];
        
        $zScore = $zScores[$confidenceLevel] ?? 1.645;
        
        return abs($mean - ($zScore * $stdDev));
    }
    
    /**
     * Calculate VaR for a portfolio with a given position value
     *
     * @param float $portfolioValue Total portfolio value
     * @param array $returns Historical returns
     * @param float $confidenceLevel Confidence level
     * @return float VaR in dollar terms
     */
    public function calculatePortfolioVaR(float $portfolioValue, array $returns, float $confidenceLevel = 0.95): float
    {
        $percentageVaR = $this->calculateHistorical($returns, $confidenceLevel);
        
        return $portfolioValue * $percentageVaR;
    }
}
