<?php

declare(strict_types=1);

namespace App\Optimization;

/**
 * Grid search optimizer for strategy parameters
 */
class GridSearchOptimizer
{
    /**
     * Run grid search optimization
     *
     * @param array $parameterGrid Parameter ranges to test
     * @param callable $evaluationFunction Function to evaluate parameter set
     * @return array Best parameters and score
     */
    public function optimize(array $parameterGrid, callable $evaluationFunction): array
    {
        $combinations = $this->generateCombinations($parameterGrid);
        
        $bestScore = -INF;
        $bestParameters = null;
        
        foreach ($combinations as $params) {
            $paramSet = new ParameterSet($params);
            $score = $evaluationFunction($paramSet);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestParameters = $params;
            }
        }
        
        return [
            'parameters' => $bestParameters,
            'score' => $bestScore,
            'evaluated' => count($combinations),
        ];
    }
    
    /**
     * Generate all parameter combinations from grid
     *
     * @param array $parameterGrid
     * @return array
     */
    private function generateCombinations(array $parameterGrid): array
    {
        if (empty($parameterGrid)) {
            return [[]];
        }
        
        $firstKey = array_key_first($parameterGrid);
        $firstValues = $parameterGrid[$firstKey];
        $remainingGrid = $parameterGrid;
        unset($remainingGrid[$firstKey]);
        
        $subCombinations = $this->generateCombinations($remainingGrid);
        
        $combinations = [];
        foreach ($firstValues as $value) {
            foreach ($subCombinations as $subCombo) {
                $combinations[] = array_merge([$firstKey => $value], $subCombo);
            }
        }
        
        return $combinations;
    }
}
