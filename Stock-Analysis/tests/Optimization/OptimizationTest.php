<?php

declare(strict_types=1);

namespace Tests\Optimization;

use PHPUnit\Framework\TestCase;
use App\Optimization\ParameterSet;
use App\Optimization\GridSearchOptimizer;
use App\Optimization\GeneticAlgorithmOptimizer;

class OptimizationTest extends TestCase
{
    public function testParameterSet(): void
    {
        $params = new ParameterSet(['a' => 1, 'b' => 2]);
        
        $this->assertSame(1, $params->get('a'));
        $this->assertSame(2, $params->get('b'));
        $this->assertNull($params->get('c'));
    }
    
    public function testParameterSetDefault(): void
    {
        $params = new ParameterSet();
        
        $this->assertSame('default', $params->get('missing', 'default'));
    }
    
    public function testParameterSetModify(): void
    {
        $params = new ParameterSet(['a' => 1]);
        
        $params->set('b', 2);
        
        $this->assertSame(2, $params->get('b'));
    }
    
    public function testParameterSetGetAll(): void
    {
        $data = ['x' => 10, 'y' => 20];
        $params = new ParameterSet($data);
        
        $this->assertSame($data, $params->getAll());
    }
    
    public function testGridSearchOptimizer(): void
    {
        $optimizer = new GridSearchOptimizer();
        
        $grid = [
            'param1' => [1, 2, 3],
            'param2' => [10, 20],
        ];
        
        $result = $optimizer->optimize($grid, function($params) {
            return $params->get('param1') + $params->get('param2');
        });
        
        $this->assertSame(['param1' => 3, 'param2' => 20], $result['parameters']);
        $this->assertSame(23, $result['score']);
        $this->assertSame(6, $result['evaluated']);
    }
    
    public function testGridSearchEmptyGrid(): void
    {
        $optimizer = new GridSearchOptimizer();
        
        $result = $optimizer->optimize([], function($params) {
            return 0;
        });
        
        $this->assertSame([], $result['parameters']);
    }
    
    public function testGridSearchMinimization(): void
    {
        $optimizer = new GridSearchOptimizer();
        
        $grid = [
            'value' => [1, 2, 3, 4, 5],
        ];
        
        $result = $optimizer->optimize($grid, function($params) {
            // Maximize negative (minimize positive)
            return -abs($params->get('value') - 3);
        });
        
        $this->assertSame(['value' => 3], $result['parameters']);
        $this->assertSame(0, $result['score']);
    }
    
    public function testGeneticAlgorithmOptimizer(): void
    {
        $optimizer = new GeneticAlgorithmOptimizer(10, 5, 0.1);
        
        $ranges = [
            'x' => [0.0, 10.0],
            'y' => [0.0, 10.0],
        ];
        
        $result = $optimizer->optimize($ranges, function($params) {
            // Maximize: -(x-5)^2 - (y-5)^2 (peak at x=5, y=5)
            $x = $params->get('x');
            $y = $params->get('y');
            return -(pow($x - 5, 2) + pow($y - 5, 2));
        });
        
        $this->assertArrayHasKey('parameters', $result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('generations', $result);
        $this->assertSame(5, $result['generations']);
    }
    
    public function testGeneticAlgorithmConvergence(): void
    {
        $optimizer = new GeneticAlgorithmOptimizer(20, 10, 0.1);
        
        $ranges = ['value' => [0.0, 100.0]];
        
        $result = $optimizer->optimize($ranges, function($params) {
            return $params->get('value');
        });
        
        // Should find value close to 100
        $this->assertGreaterThan(90, $result['parameters']['value']);
    }
    
    public function testGeneticAlgorithmMultipleParameters(): void
    {
        $optimizer = new GeneticAlgorithmOptimizer(15, 8, 0.15);
        
        $ranges = [
            'a' => [0.0, 10.0],
            'b' => [0.0, 10.0],
            'c' => [0.0, 10.0],
        ];
        
        $result = $optimizer->optimize($ranges, function($params) {
            return $params->get('a') + $params->get('b') + $params->get('c');
        });
        
        $this->assertArrayHasKey('a', $result['parameters']);
        $this->assertArrayHasKey('b', $result['parameters']);
        $this->assertArrayHasKey('c', $result['parameters']);
    }
}
