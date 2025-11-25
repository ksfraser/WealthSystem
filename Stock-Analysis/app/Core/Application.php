<?php

namespace App\Core;

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Container;

/**
 * Application Core
 * 
 * Main application class that bootstraps the MVC framework.
 * Follows Single Responsibility Principle (SRP) - application lifecycle management.
 */
class Application 
{
    private Container $container;
    private Router $router;
    private array $config;
    
    public function __construct(array $config = []) 
    {
        $this->config = $config;
        $this->container = new Container();
        $this->router = new Router($this->container);
        
        $this->bootstrap();
    }
    
    /**
     * Get container instance
     */
    public function getContainer(): Container 
    {
        return $this->container;
    }
    
    /**
     * Get router instance
     */
    public function getRouter(): Router 
    {
        return $this->router;
    }
    
    /**
     * Run the application
     */
    public function run(): void 
    {
        try {
            $request = new Request();
            $response = $this->router->dispatch($request);
            $response->send();
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Bootstrap application services
     */
    private function bootstrap(): void 
    {
        // Register core services
        $this->container->instance(Container::class, $this->container);
        $this->container->instance(Router::class, $this->router);
        $this->container->instance(Application::class, $this);
        
        // Register configuration
        $this->container->instance('config', $this->config);
        
        // Load service providers if configured
        if (isset($this->config['providers'])) {
            foreach ($this->config['providers'] as $provider) {
                $this->registerProvider($provider);
            }
        }
    }
    
    /**
     * Register service provider
     */
    private function registerProvider(string $providerClass): void 
    {
        $provider = new $providerClass($this->container);
        
        if (method_exists($provider, 'register')) {
            $provider->register();
        }
    }
    
    /**
     * Handle uncaught exceptions
     */
    private function handleException(\Throwable $e): void 
    {
        // In development, show detailed error
        if (($this->config['debug'] ?? false)) {
            $response = new Response(
                $this->formatException($e),
                500,
                ['Content-Type' => 'text/html']
            );
        } else {
            // In production, show generic error
            $response = new Response('Internal Server Error', 500);
        }
        
        $response->send();
    }
    
    /**
     * Format exception for display
     */
    private function formatException(\Throwable $e): string 
    {
        return sprintf(
            '<h1>%s</h1><p>%s</p><p>File: %s Line: %d</p><pre>%s</pre>',
            get_class($e),
            htmlspecialchars($e->getMessage()),
            $e->getFile(),
            $e->getLine(),
            htmlspecialchars($e->getTraceAsString())
        );
    }
}