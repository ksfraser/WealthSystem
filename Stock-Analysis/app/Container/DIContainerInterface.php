<?php

namespace App\Container;

use Psr\Container\ContainerInterface;

/**
 * Dependency Injection Container Interface
 * 
 * PSR-11 compatible container with auto-wiring support.
 * Follows Single Responsibility Principle and Dependency Inversion.
 */
interface DIContainerInterface extends ContainerInterface
{
    /**
     * Bind a concrete implementation to an interface or class
     * 
     * @param string $abstract Interface or class name
     * @param callable|string|null $concrete Implementation (class name, factory, or null for auto-wire)
     * @param bool $singleton Whether to return the same instance
     * @return void
     */
    public function bind(string $abstract, $concrete = null, bool $singleton = false): void;
    
    /**
     * Bind as singleton (single instance shared)
     * 
     * @param string $abstract Interface or class name
     * @param callable|string|null $concrete Implementation
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void;
    
    /**
     * Register an existing instance
     * 
     * @param string $abstract Interface or class name
     * @param object $instance The instance to register
     * @return void
     */
    public function instance(string $abstract, object $instance): void;
    
    /**
     * Resolve a dependency from the container
     * 
     * @param string $abstract Interface or class name
     * @param array $parameters Additional parameters for construction
     * @return mixed The resolved instance
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function make(string $abstract, array $parameters = []);
    
    /**
     * Check if a binding exists
     * 
     * @param string $id Identifier
     * @return bool True if binding exists
     */
    public function has(string $id): bool;
    
    /**
     * Get an entry from the container
     * 
     * @param string $id Identifier
     * @return mixed The entry
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function get(string $id);
    
    /**
     * Call a method/function with dependency injection
     * 
     * @param callable $callable Method or function to call
     * @param array $parameters Additional parameters
     * @return mixed Return value of the callable
     */
    public function call(callable $callable, array $parameters = []);
    
    /**
     * Check if entry is bound as singleton
     * 
     * @param string $abstract Interface or class name
     * @return bool True if singleton
     */
    public function isSingleton(string $abstract): bool;
}
