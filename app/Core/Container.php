<?php

namespace App\Core;

use ReflectionClass;
use ReflectionException;

/**
 * Dependency Injection Container
 * 
 * Simple DI container implementing Dependency Inversion Principle (DIP).
 * Provides automatic dependency resolution and service registration.
 */
class Container 
{
    private array $bindings = [];
    private array $instances = [];
    
    /**
     * Bind interface to implementation
     */
    public function bind(string $abstract, $concrete = null, bool $singleton = false): void 
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'singleton' => $singleton
        ];
    }
    
    /**
     * Bind singleton instance
     */
    public function singleton(string $abstract, $concrete = null): void 
    {
        $this->bind($abstract, $concrete, true);
    }
    
    /**
     * Bind existing instance
     */
    public function instance(string $abstract, $instance): void 
    {
        $this->instances[$abstract] = $instance;
    }
    
    /**
     * Resolve service from container
     */
    public function get(string $abstract) 
    {
        // Return existing instance if available
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        // Get binding or use abstract as concrete
        $binding = $this->bindings[$abstract] ?? null;
        $concrete = $binding['concrete'] ?? $abstract;
        
        // Handle closure binding
        if ($concrete instanceof \Closure) {
            $instance = $concrete($this);
        } else {
            $instance = $this->build($concrete);
        }
        
        // Store singleton instance
        if ($binding && $binding['singleton']) {
            $this->instances[$abstract] = $instance;
        }
        
        return $instance;
    }
    
    /**
     * Check if service is bound
     */
    public function has(string $abstract): bool 
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
    
    /**
     * Build instance with dependency injection
     */
    private function build(string $concrete) 
    {
        try {
            $reflection = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new \Exception("Cannot build {$concrete}: {$e->getMessage()}");
        }
        
        // Check if class is instantiable
        if (!$reflection->isInstantiable()) {
            throw new \Exception("Cannot build {$concrete}: Class is not instantiable");
        }
        
        $constructor = $reflection->getConstructor();
        
        // No constructor dependencies
        if (!$constructor) {
            return $reflection->newInstance();
        }
        
        // Resolve constructor dependencies
        $dependencies = $this->resolveDependencies($constructor->getParameters());
        
        return $reflection->newInstanceArgs($dependencies);
    }
    
    /**
     * Resolve constructor parameter dependencies
     */
    private function resolveDependencies(array $parameters): array 
    {
        $dependencies = [];
        
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            
            if (!$type || $type->isBuiltin()) {
                // Handle optional parameters with default values
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \Exception("Cannot resolve parameter {$parameter->getName()}");
                }
            } else {
                // Resolve class dependency
                $className = $type->getName();
                $dependencies[] = $this->get($className);
            }
        }
        
        return $dependencies;
    }
}