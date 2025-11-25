<?php

require_once __DIR__ . '/CoreInterfaces.php';

/**
 * Simple Dependency Injection Container
 */
class DIContainer
{
    private $bindings = [];
    private $instances = [];

    public function bind($abstract, $concrete)
    {
        if (is_callable($concrete)) {
            $this->bindings[$abstract] = $concrete;
        } else {
            $this->bindings[$abstract] = function() use ($concrete) {
                return $concrete;
            };
        }
    }

    public function singleton($abstract, $concrete)
    {
        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null;
    }

    public function resolve($abstract)
    {
        // Check if we have a singleton instance
        if (array_key_exists($abstract, $this->instances)) {
            if ($this->instances[$abstract] === null) {
                $this->instances[$abstract] = $this->build($abstract);
            }
            return $this->instances[$abstract];
        }

        return $this->build($abstract);
    }

    private function build($abstract)
    {
        if (!isset($this->bindings[$abstract])) {
            throw new Exception("No binding found for: " . $abstract);
        }

        $concrete = $this->bindings[$abstract];
        return $concrete($this);
    }

    public function instance($abstract, $instance)
    {
        $this->instances[$abstract] = $instance;
    }
}

/**
 * Service locator pattern for global access
 */
class App
{
    private static $container;

    public static function setContainer(DIContainer $container)
    {
        self::$container = $container;
    }

    public static function make($abstract)
    {
        if (!self::$container) {
            throw new Exception("Container not set");
        }
        return self::$container->resolve($abstract);
    }

    public static function bind($abstract, $concrete)
    {
        if (!self::$container) {
            self::$container = new DIContainer();
        }
        self::$container->bind($abstract, $concrete);
    }

    public static function singleton($abstract, $concrete)
    {
        if (!self::$container) {
            self::$container = new DIContainer();
        }
        self::$container->singleton($abstract, $concrete);
    }
}
