<?php

namespace App\Risk;

/**
 * Correlation Matrix Calculator
 * 
 * Calculates correlation matrices for multi-asset portfolio analysis.
 * Supports multiple correlation methods and rolling window correlations.
 * 
 * Correlation measures the strength and direction of the linear relationship
 * between two assets. Values range from -1 (perfect negative) to +1 (perfect positive).
 * 
 * Methods:
 * - Pearson: Linear correlation (parametric)
 * - Spearman: Rank correlation (non-parametric)
 * - Kendall: Rank correlation alternative
 * - Rolling: Time-varying correlation
 * 
 * Example:
 * ```php
 * $corr = new CorrelationMatrix();
 * 
 * $returns = [
 *     'AAPL' => [0.01, 0.02, -0.01, 0.03],
 *     'MSFT' => [0.02, 0.01, -0.02, 0.04],
 *     'GOOGL' => [-0.01, 0.03, 0.02, -0.02],
 * ];
 * 
 * $matrix = $corr->calculate($returns);
 * print_r($matrix);
 * ```
 */
class CorrelationMatrix
{
    /**
     * Calculate correlation matrix using Pearson method
     * 
     * @param array $returns Multi-dimensional array: ['SYMBOL' => [returns...]]
     * @param string $method Correlation method ('pearson', 'spearman', 'kendall')
     * @return array 2D correlation matrix
     */
    public function calculate(array $returns, string $method = 'pearson'): array
    {
        $symbols = array_keys($returns);
        $matrix = [];

        foreach ($symbols as $symbol1) {
            $matrix[$symbol1] = [];
            foreach ($symbols as $symbol2) {
                if ($symbol1 === $symbol2) {
                    $matrix[$symbol1][$symbol2] = 1.0;
                } else {
                    $correlation = match($method) {
                        'spearman' => $this->spearmanCorrelation(
                            $returns[$symbol1],
                            $returns[$symbol2]
                        ),
                        'kendall' => $this->kendallCorrelation(
                            $returns[$symbol1],
                            $returns[$symbol2]
                        ),
                        default => $this->pearsonCorrelation(
                            $returns[$symbol1],
                            $returns[$symbol2]
                        ),
                    };
                    $matrix[$symbol1][$symbol2] = $correlation;
                }
            }
        }

        return $matrix;
    }

    /**
     * Calculate Pearson correlation coefficient
     * 
     * Measures linear relationship between two variables.
     * r = Cov(X,Y) / (StdDev(X) * StdDev(Y))
     * 
     * @param array $x First asset returns
     * @param array $y Second asset returns
     * @return float Correlation (-1 to 1)
     */
    public function pearsonCorrelation(array $x, array $y): float
    {
        if (count($x) !== count($y) || count($x) < 2) {
            return 0.0;
        }

        $n = count($x);
        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;

        $covariance = 0;
        $varX = 0;
        $varY = 0;

        for ($i = 0; $i < $n; $i++) {
            $dx = $x[$i] - $meanX;
            $dy = $y[$i] - $meanY;
            
            $covariance += $dx * $dy;
            $varX += $dx * $dx;
            $varY += $dy * $dy;
        }

        $denominator = sqrt($varX * $varY);
        
        if ($denominator == 0) {
            return 0.0;
        }

        return $covariance / $denominator;
    }

    /**
     * Calculate Spearman rank correlation coefficient
     * 
     * Non-parametric measure based on rank values.
     * Less sensitive to outliers than Pearson.
     * 
     * @param array $x First asset returns
     * @param array $y Second asset returns
     * @return float Correlation (-1 to 1)
     */
    public function spearmanCorrelation(array $x, array $y): float
    {
        if (count($x) !== count($y) || count($x) < 2) {
            return 0.0;
        }

        // Convert to ranks
        $ranksX = $this->rankTransform($x);
        $ranksY = $this->rankTransform($y);

        // Calculate Pearson on ranks
        return $this->pearsonCorrelation($ranksX, $ranksY);
    }

    /**
     * Calculate Kendall's tau correlation coefficient
     * 
     * Another non-parametric rank correlation.
     * Counts concordant and discordant pairs.
     * 
     * @param array $x First asset returns
     * @param array $y Second asset returns
     * @return float Correlation (-1 to 1)
     */
    public function kendallCorrelation(array $x, array $y): float
    {
        if (count($x) !== count($y) || count($x) < 2) {
            return 0.0;
        }

        $n = count($x);
        $concordant = 0;
        $discordant = 0;

        for ($i = 0; $i < $n - 1; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $signX = $this->sign($x[$j] - $x[$i]);
                $signY = $this->sign($y[$j] - $y[$i]);
                
                if ($signX * $signY > 0) {
                    $concordant++;
                } elseif ($signX * $signY < 0) {
                    $discordant++;
                }
            }
        }

        $pairs = $n * ($n - 1) / 2;
        
        if ($pairs == 0) {
            return 0.0;
        }

        return ($concordant - $discordant) / $pairs;
    }

    /**
     * Calculate rolling correlation
     * 
     * Computes correlation over a moving window to capture time-varying relationships.
     * 
     * @param array $x First asset returns
     * @param array $y Second asset returns
     * @param int $window Window size
     * @param string $method Correlation method
     * @return array Array of correlation values over time
     */
    public function rollingCorrelation(
        array $x,
        array $y,
        int $window = 30,
        string $method = 'pearson'
    ): array {
        if (count($x) !== count($y) || count($x) < $window) {
            return [];
        }

        $correlations = [];
        $n = count($x);

        for ($i = $window - 1; $i < $n; $i++) {
            $windowX = array_slice($x, $i - $window + 1, $window);
            $windowY = array_slice($y, $i - $window + 1, $window);
            
            $correlation = match($method) {
                'spearman' => $this->spearmanCorrelation($windowX, $windowY),
                'kendall' => $this->kendallCorrelation($windowX, $windowY),
                default => $this->pearsonCorrelation($windowX, $windowY),
            };
            
            $correlations[] = $correlation;
        }

        return $correlations;
    }

    /**
     * Get correlation statistics for a pair
     * 
     * @param array $x First asset returns
     * @param array $y Second asset returns
     * @param int $rollingWindow Window for rolling correlation
     * @return array Statistics
     */
    public function correlationStats(array $x, array $y, int $rollingWindow = 30): array
    {
        $pearson = $this->pearsonCorrelation($x, $y);
        $spearman = $this->spearmanCorrelation($x, $y);
        $rolling = $this->rollingCorrelation($x, $y, $rollingWindow);

        return [
            'pearson' => $pearson,
            'spearman' => $spearman,
            'kendall' => $this->kendallCorrelation($x, $y),
            'rolling_mean' => !empty($rolling) ? array_sum($rolling) / count($rolling) : 0,
            'rolling_min' => !empty($rolling) ? min($rolling) : 0,
            'rolling_max' => !empty($rolling) ? max($rolling) : 0,
            'rolling_latest' => !empty($rolling) ? end($rolling) : 0,
            'strength' => $this->correlationStrength(abs($pearson)),
        ];
    }

    /**
     * Find highly correlated pairs
     * 
     * @param array $returns Multi-dimensional array of returns
     * @param float $threshold Correlation threshold (default: 0.7)
     * @return array Array of correlated pairs
     */
    public function findCorrelatedPairs(array $returns, float $threshold = 0.7): array
    {
        $matrix = $this->calculate($returns);
        $pairs = [];

        $symbols = array_keys($returns);
        
        for ($i = 0; $i < count($symbols); $i++) {
            for ($j = $i + 1; $j < count($symbols); $j++) {
                $symbol1 = $symbols[$i];
                $symbol2 = $symbols[$j];
                $correlation = $matrix[$symbol1][$symbol2];
                
                if (abs($correlation) >= $threshold) {
                    $pairs[] = [
                        'symbol1' => $symbol1,
                        'symbol2' => $symbol2,
                        'correlation' => $correlation,
                        'type' => $correlation > 0 ? 'positive' : 'negative',
                    ];
                }
            }
        }

        // Sort by absolute correlation (strongest first)
        usort($pairs, fn($a, $b) => abs($b['correlation']) <=> abs($a['correlation']));

        return $pairs;
    }

    /**
     * Calculate portfolio diversification score
     * 
     * Lower average correlation indicates better diversification.
     * 
     * @param array $returns Multi-dimensional array of returns
     * @return array Diversification metrics
     */
    public function diversificationScore(array $returns): array
    {
        if (count($returns) < 2) {
            return [
                'score' => 1.0,
                'avg_correlation' => 0.0,
                'max_correlation' => 0.0,
                'min_correlation' => 0.0,
            ];
        }

        $matrix = $this->calculate($returns);
        $correlations = [];

        $symbols = array_keys($returns);
        
        for ($i = 0; $i < count($symbols); $i++) {
            for ($j = $i + 1; $j < count($symbols); $j++) {
                $symbol1 = $symbols[$i];
                $symbol2 = $symbols[$j];
                $correlations[] = $matrix[$symbol1][$symbol2];
            }
        }

        if (empty($correlations)) {
            return [
                'score' => 1.0,
                'avg_correlation' => 0.0,
                'max_correlation' => 0.0,
                'min_correlation' => 0.0,
            ];
        }

        $avgCorrelation = array_sum($correlations) / count($correlations);
        
        // Diversification score: 1 - avg_correlation
        // Score of 1.0 = perfect diversification (no correlation)
        // Score of 0.0 = no diversification (perfect correlation)
        $score = 1.0 - abs($avgCorrelation);

        return [
            'score' => max(0, $score),
            'avg_correlation' => $avgCorrelation,
            'max_correlation' => max($correlations),
            'min_correlation' => min($correlations),
            'interpretation' => $this->diversificationInterpretation($score),
        ];
    }

    /**
     * Convert correlation matrix to distance matrix
     * 
     * Used for clustering algorithms.
     * Distance = sqrt(2 * (1 - correlation))
     * 
     * @param array $correlationMatrix Correlation matrix
     * @return array Distance matrix
     */
    public function toDistanceMatrix(array $correlationMatrix): array
    {
        $distanceMatrix = [];
        
        foreach ($correlationMatrix as $symbol1 => $row) {
            $distanceMatrix[$symbol1] = [];
            foreach ($row as $symbol2 => $correlation) {
                $distance = sqrt(2 * (1 - $correlation));
                $distanceMatrix[$symbol1][$symbol2] = $distance;
            }
        }

        return $distanceMatrix;
    }

    /**
     * Transform data to ranks (for non-parametric correlations)
     * 
     * @param array $data Data array
     * @return array Rank array
     */
    private function rankTransform(array $data): array
    {
        $sorted = $data;
        arsort($sorted);
        
        $ranks = [];
        $rank = 1;
        
        foreach ($sorted as $key => $value) {
            $ranks[$key] = $rank++;
        }

        // Restore original order
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = $ranks[$key];
        }

        return $result;
    }

    /**
     * Sign function
     * 
     * @param float $value Value
     * @return int Sign (-1, 0, or 1)
     */
    private function sign(float $value): int
    {
        if ($value > 0) return 1;
        if ($value < 0) return -1;
        return 0;
    }

    /**
     * Interpret correlation strength
     * 
     * @param float $correlation Absolute correlation value
     * @return string Interpretation
     */
    private function correlationStrength(float $correlation): string
    {
        $abs = abs($correlation);
        
        if ($abs >= 0.9) return 'very_strong';
        if ($abs >= 0.7) return 'strong';
        if ($abs >= 0.5) return 'moderate';
        if ($abs >= 0.3) return 'weak';
        return 'very_weak';
    }

    /**
     * Interpret diversification score
     * 
     * @param float $score Diversification score
     * @return string Interpretation
     */
    private function diversificationInterpretation(float $score): string
    {
        if ($score >= 0.8) return 'excellent';
        if ($score >= 0.6) return 'good';
        if ($score >= 0.4) return 'moderate';
        if ($score >= 0.2) return 'poor';
        return 'very_poor';
    }
}
