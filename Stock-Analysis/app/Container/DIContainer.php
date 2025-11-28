<?php

namespace App\Container;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use Psr\Container\ContainerInterface;

/**
 * Dependency Injection Container
 * 
 * PSR-11 compliant container with auto-wiring, singleton support,
 * and method injection capabilities.
 * 
 * Follows SOLID principles:
 * - Single Responsibility: Manages only dependency resolution
 * - Open/Closed: Extensible via bindings without modification
 * - Liskov Substitution: Implements PSR-11 ContainerInterface
 * - Interface Segregation: Clean, focused interface
 * - Dependency Inversion: Depends on abstractions (PSR-11)
 */
class DIContainer implements DIContainerInterface
{
    /**
     * Container bindings
     * @var array<string, array{concrete: mixed, singleton: bool}>
     */
    private array $bindings = [];
    
    /**
     * Resolved singleton instances
     * @var array<string, object>
     */
    private array $instances = [];
    
    /**
     * {@inheritdoc}
     */
    public function bind(string $abstract, $concrete = null, bool $singleton = false): void
    {
        // Don't override existing binding
        if (isset($this->bindings[$abstract])) {
            return;
        }
        
        // Default to binding to itself
        if ($concrete === null) {
            $concrete = $abstract;
        }
        
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }
    
    /**
     * {@inheritdoc}
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
        $this->bindings[$abstract] = [
            'concrete' => $instance,
            'singleton' => true
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || 
               isset($this->instances[$id]) ||
               class_exists($id);
    }
    
    /**
     * {@inheritdoc}
     */
    public function get(string $id)
    {
        // Check if we have a singleton instance
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        
        // Check if we have a binding
        if (!isset($this->bindings[$id])) {
            // Try to auto-resolve if it's a class
            if (class_exists($id)) {
                return $this->resolve($id);
            }
            
            throw new NotFoundException("Binding not found for: {$id}");
        }
        
        $binding = $this->bindings[$id];
        $concrete = $binding['concrete'];
        
        // Resolve the binding
        $instance = $this->resolve($concrete);
        
        // Cache singleton instances
        if ($binding['singleton']) {
            $this->instances[$id] = $instance;
        }
        
        return $instance;
    }
    
    /**
     * {@inheritdoc}
     */
    public function make(string $abstract, array $parameters = [])
    {
        // Get concrete type
        $concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;
        
        // Resolve with parameters
        return $this->resolve($concrete, $parameters);
    }
    
    /**
     * {@inheritdoc}
     */
    public function isSingleton(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) && 
               $this->bindings[$abstract]['singleton'] === true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function call(callable $callable, array $parameters = [])
    {
        if (is_array($callable)) {
            [$object, $method] = $callable;
            
            try {
                $reflection = new ReflectionClass($object);
                $methodReflection = $reflection->getMethod($method);
                $dependencies = $this->resolveDependencies($methodReflection->getParameters(), $parameters);
                
                return $methodReflection->invokeArgs($object, $dependencies);
            } catch (ReflectionException $e) {
                throw new ContainerException("Failed to call method: {$e->getMessage()}", 0, $e);
            }
        }
        
        // For closures and other callables
        return call_user_func_array($callable, $parameters);
    }
    
    /**
     * Resolve a concrete type with auto-wiring
     * 
     * @param mixed $concrete Class name or Closure
     * @param array $parameters Override parameters
     * @return object Resolved instance
     * @throws ContainerException If resolution fails
     */
    private function resolve($concrete, array $parameters = []): object
    {
        // Handle closures
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }
        
        // Handle direct instances
        if (is_object($concrete)) {
            return $concrete;
        }
        
        // Auto-wire class
        try {
            $reflection = new ReflectionClass($concrete);
            
            if (!$reflection->isInstantiable()) {
                throw new ContainerException("Class {$concrete} is not instantiable");
            }
            
            $constructor = $reflection->getConstructor();
            
            // No constructor - simple instantiation
            if ($constructor === null) {
                return $reflection->newInstance();
            }
            
            // Resolve constructor dependencies
            $dependencies = $this->resolveDependencies(
                $constructor->getParameters(),
                $parameters
            );
            
            return $reflection->newInstanceArgs($dependencies);
            
        } catch (ReflectionException $e) {
            throw new ContainerException("Failed to resolve {$concrete}: {$e->getMessage()}", 0, $e);
        }
    }
    
    /**
     * Resolve method/constructor dependencies
     * 
     * @param ReflectionParameter[] $parameters
     * @param array $overrides Override parameters
     * @return array Resolved dependencies
     * @throws ContainerException If dependency cannot be resolved
     */
    private function resolveDependencies(array $parameters, array $overrides = []): array
    {
        $dependencies = [];
        
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            
            // Check for override by name
            if (array_key_exists($name, $overrides)) {
                $dependencies[] = $overrides[$name];
                continue;
            }
            
            // Try to get type hint
            $type = $parameter->getType();
            
            if ($type && $type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                // Resolve class dependency
                $className = $type->getName();
                $dependencies[] = $this->get($className);
                continue;
            }
            
            // Check if parameter has default value
            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }
            
            throw new ContainerException(
                "Cannot resolve parameter {$name} for {$parameter->getDeclaringClass()->getName()}"
            );
        }
        
        return $dependencies;
    }
}
