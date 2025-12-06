<?php

declare(strict_types=1);

namespace App\Optimization;

/**
 * Parameter configuration for strategy optimization
 */
class ParameterSet
{
    private array $parameters;
    
    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }
    
    public function get(string $key, $default = null)
    {
        return $this->parameters[$key] ?? $default;
    }
    
    public function set(string $key, $value): void
    {
        $this->parameters[$key] = $value;
    }
    
    public function getAll(): array
    {
        return $this->parameters;
    }
    
    public function toArray(): array
    {
        return $this->parameters;
    }
}
