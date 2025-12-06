<?php

declare(strict_types=1);

namespace App\Optimization;

/**
 * Genetic algorithm optimizer for strategy parameters
 */
class GeneticAlgorithmOptimizer
{
    private int $populationSize;
    private int $generations;
    private float $mutationRate;
    
    public function __construct(int $populationSize = 20, int $generations = 50, float $mutationRate = 0.1)
    {
        $this->populationSize = $populationSize;
        $this->generations = $generations;
        $this->mutationRate = $mutationRate;
    }
    
    /**
     * Run genetic algorithm optimization
     *
     * @param array $parameterRanges Parameter ranges [key => [min, max]]
     * @param callable $fitnessFunction Function to evaluate fitness
     * @return array Best parameters and score
     */
    public function optimize(array $parameterRanges, callable $fitnessFunction): array
    {
        $population = $this->initializePopulation($parameterRanges);
        
        $bestFitness = -INF;
        $bestIndividual = null;
        
        for ($gen = 0; $gen < $this->generations; $gen++) {
            $fitnesses = $this->evaluatePopulation($population, $fitnessFunction);
            
            $maxFitness = max($fitnesses);
            if ($maxFitness > $bestFitness) {
                $bestFitness = $maxFitness;
                $bestIndividual = $population[array_search($maxFitness, $fitnesses)];
            }
            
            $population = $this->evolvePopulation($population, $fitnesses, $parameterRanges);
        }
        
        return [
            'parameters' => $bestIndividual->getAll(),
            'score' => $bestFitness,
            'generations' => $this->generations,
        ];
    }
    
    /**
     * Initialize random population
     *
     * @param array $parameterRanges
     * @return array
     */
    private function initializePopulation(array $parameterRanges): array
    {
        $population = [];
        
        for ($i = 0; $i < $this->populationSize; $i++) {
            $params = [];
            foreach ($parameterRanges as $key => [$min, $max]) {
                $params[$key] = $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
            }
            $population[] = new ParameterSet($params);
        }
        
        return $population;
    }
    
    /**
     * Evaluate fitness for all individuals
     *
     * @param array $population
     * @param callable $fitnessFunction
     * @return array
     */
    private function evaluatePopulation(array $population, callable $fitnessFunction): array
    {
        return array_map($fitnessFunction, $population);
    }
    
    /**
     * Evolve population through selection, crossover, mutation
     *
     * @param array $population
     * @param array $fitnesses
     * @param array $parameterRanges
     * @return array
     */
    private function evolvePopulation(array $population, array $fitnesses, array $parameterRanges): array
    {
        $newPopulation = [];
        
        // Keep best individual (elitism)
        $bestIdx = array_search(max($fitnesses), $fitnesses);
        $newPopulation[] = $population[$bestIdx];
        
        // Generate rest through crossover and mutation
        while (count($newPopulation) < $this->populationSize) {
            $parent1 = $this->selectParent($population, $fitnesses);
            $parent2 = $this->selectParent($population, $fitnesses);
            
            $child = $this->crossover($parent1, $parent2);
            $child = $this->mutate($child, $parameterRanges);
            
            $newPopulation[] = $child;
        }
        
        return $newPopulation;
    }
    
    /**
     * Select parent using tournament selection
     *
     * @param array $population
     * @param array $fitnesses
     * @return ParameterSet
     */
    private function selectParent(array $population, array $fitnesses): ParameterSet
    {
        $idx1 = array_rand($population);
        $idx2 = array_rand($population);
        
        return $fitnesses[$idx1] > $fitnesses[$idx2] 
            ? $population[$idx1] 
            : $population[$idx2];
    }
    
    /**
     * Perform crossover between two parents
     *
     * @param ParameterSet $parent1
     * @param ParameterSet $parent2
     * @return ParameterSet
     */
    private function crossover(ParameterSet $parent1, ParameterSet $parent2): ParameterSet
    {
        $childParams = [];
        
        foreach ($parent1->getAll() as $key => $value) {
            $childParams[$key] = (mt_rand() / mt_getrandmax()) < 0.5 
                ? $parent1->get($key) 
                : $parent2->get($key);
        }
        
        return new ParameterSet($childParams);
    }
    
    /**
     * Mutate parameters with small probability
     *
     * @param ParameterSet $individual
     * @param array $parameterRanges
     * @return ParameterSet
     */
    private function mutate(ParameterSet $individual, array $parameterRanges): ParameterSet
    {
        foreach ($individual->getAll() as $key => $value) {
            if ((mt_rand() / mt_getrandmax()) < $this->mutationRate) {
                [$min, $max] = $parameterRanges[$key];
                $individual->set($key, $min + (mt_rand() / mt_getrandmax()) * ($max - $min));
            }
        }
        
        return $individual;
    }
}
