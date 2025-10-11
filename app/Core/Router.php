<?php

namespace App\Core;

use App\Core\Request;
use App\Core\Response;
use App\Core\Interfaces\ControllerInterface;
use App\Core\Interfaces\MiddlewareInterface;

/**
 * Router Class
 * 
 * Handles URL routing with middleware support.
 * Follows Single Responsibility Principle (SRP) and Open/Closed Principle (OCP).
 */
class Router 
{
    private array $routes = [];
    private array $middleware = [];
    private Container $container;
    
    public function __construct(Container $container) 
    {
        $this->container = $container;
    }
    
    /**
     * Register GET route
     */
    public function get(string $path, $handler, array $middleware = []): self 
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }
    
    /**
     * Register POST route
     */
    public function post(string $path, $handler, array $middleware = []): self 
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }
    
    /**
     * Register PUT route
     */
    public function put(string $path, $handler, array $middleware = []): self 
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }
    
    /**
     * Register DELETE route
     */
    public function delete(string $path, $handler, array $middleware = []): self 
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }
    
    /**
     * Register route for any method
     */
    public function any(string $path, $handler, array $middleware = []): self 
    {
        return $this->addRoute('*', $path, $handler, $middleware);
    }
    
    /**
     * Add global middleware
     */
    public function middleware(string $middlewareClass): self 
    {
        $this->middleware[] = $middlewareClass;
        return $this;
    }
    
    /**
     * Dispatch request to appropriate handler
     */
    public function dispatch(Request $request): Response 
    {
        $method = $request->getMethod();
        $uri = $request->getUri();
        
        // Find matching route
        $route = $this->findRoute($method, $uri);
        
        if (!$route) {
            return new Response('404 Not Found', 404);
        }
        
        // Combine global middleware with route-specific middleware
        $middlewareStack = array_merge($this->middleware, $route['middleware']);
        
        // Create middleware chain
        $handler = $this->createMiddlewareChain($middlewareStack, function() use ($route, $request) {
            return $this->callHandler($route['handler'], $request, $route['params']);
        });
        
        // Execute middleware chain
        return $handler($request);
    }
    
    /**
     * Add route to routing table
     */
    private function addRoute(string $method, string $path, $handler, array $middleware): self 
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $this->pathToRegex($path),
            'handler' => $handler,
            'middleware' => $middleware
        ];
        
        return $this;
    }
    
    /**
     * Find route that matches method and URI
     */
    private function findRoute(string $method, string $uri): ?array 
    {
        foreach ($this->routes as $route) {
            // Check method match (or wildcard)
            if ($route['method'] !== '*' && $route['method'] !== $method) {
                continue;
            }
            
            // Check path match
            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract parameters
                $params = array_slice($matches, 1);
                $route['params'] = $params;
                return $route;
            }
        }
        
        return null;
    }
    
    /**
     * Convert route path to regex pattern
     */
    private function pathToRegex(string $path): string 
    {
        // Convert {param} to named capture groups
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '([^/]+)', $path);
        
        // Escape forward slashes and wrap with delimiters
        $pattern = str_replace('/', '\/', $pattern);
        return '/^' . $pattern . '$/';
    }
    
    /**
     * Create middleware chain using closure composition
     */
    private function createMiddlewareChain(array $middlewareStack, callable $finalHandler): callable 
    {
        return array_reduce(
            array_reverse($middlewareStack),
            function($next, $middlewareClass) {
                return function($request) use ($middlewareClass, $next) {
                    $middleware = $this->container->get($middlewareClass);
                    if (!$middleware instanceof MiddlewareInterface) {
                        throw new \InvalidArgumentException("Middleware must implement MiddlewareInterface");
                    }
                    return $middleware->handle($request, $next);
                };
            },
            $finalHandler
        );
    }
    
    /**
     * Call route handler
     */
    private function callHandler($handler, Request $request, array $params): Response 
    {
        // Handle string format: 'ControllerClass@method'
        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$controllerClass, $method] = explode('@', $handler);
            
            $controller = $this->container->get($controllerClass);
            if (!$controller instanceof ControllerInterface) {
                throw new \InvalidArgumentException("Controller must implement ControllerInterface");
            }
            
            return $controller->$method($request, ...$params);
        }
        
        // Handle callable
        if (is_callable($handler)) {
            $result = $handler($request, ...$params);
            
            if ($result instanceof Response) {
                return $result;
            }
            
            // Convert string result to Response
            return new Response((string)$result);
        }
        
        throw new \InvalidArgumentException("Invalid route handler");
    }
}