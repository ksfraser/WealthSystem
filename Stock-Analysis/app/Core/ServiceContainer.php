<?php

namespace App\Core;

use App\Core\Container;

// Import repositories
use App\Repositories\UserRepository;
use App\Repositories\BankAccountRepository;
use App\Repositories\PortfolioRepository;

// Import services
use App\Services\AuthenticationService;
use App\Services\BankImportService;
use App\Services\PortfolioService;
use App\Services\NavigationService;

/**
 * Service Container Configuration
 * 
 * Binds all dependencies for the refactored MVC application.
 * Integrates with existing DAO system while providing modern DI.
 */
class ServiceContainer
{
    private static ?Container $container = null;
    
    /**
     * Get configured container instance
     */
    public static function getInstance(): Container
    {
        if (self::$container === null) {
            self::$container = self::createContainer();
        }
        
        return self::$container;
    }
    
    /**
     * Create and configure the dependency injection container
     */
    private static function createContainer(): Container
    {
        $container = new Container();
        
        // Bind core services
        $container->instance('App\\Core\\Container', $container);
        
        // Bind router
        $container->bind(
            'App\\Core\\Router',
            function($container) {
                return new \App\Core\Router($container);
            },
            true
        );
        
        // Bind repositories (concrete implementations)
        $container->bind(
            'App\\Repositories\\Interfaces\\UserRepositoryInterface',
            'App\\Repositories\\UserRepository',
            true // singleton
        );
        
        $container->bind(
            'App\\Repositories\\Interfaces\\BankAccountRepositoryInterface',
            'App\\Repositories\\BankAccountRepository',
            true // singleton
        );
        
        $container->bind(
            'App\\Repositories\\Interfaces\\PortfolioRepositoryInterface',
            'App\\Repositories\\PortfolioRepository',
            true // singleton
        );
        
        // Bind services with proper dependencies
        $container->bind(
            'App\\Services\\Interfaces\\AuthenticationServiceInterface',
            function($container) {
                $userRepo = $container->get('App\\Repositories\\Interfaces\\UserRepositoryInterface');
                return new AuthenticationService($userRepo);
            },
            true // singleton
        );
        
        $container->bind(
            'App\\Services\\Interfaces\\BankImportServiceInterface',
            function($container) {
                $bankAccountRepo = $container->get('App\\Repositories\\Interfaces\\BankAccountRepositoryInterface');
                $portfolioRepo = $container->get('App\\Repositories\\Interfaces\\PortfolioRepositoryInterface');
                return new BankImportService($bankAccountRepo, $portfolioRepo);
            },
            true // singleton
        );
        
        $container->bind(
            'App\\Services\\Interfaces\\PortfolioServiceInterface',
            function($container) {
                $portfolioRepo = $container->get('App\\Repositories\\Interfaces\\PortfolioRepositoryInterface');
                $marketDataService = $container->get('App\\Services\\Interfaces\\MarketDataServiceInterface');
                return new PortfolioService($portfolioRepo, $marketDataService);
            },
            true // singleton
        );
        
        $container->bind(
            'App\\Services\\Interfaces\\NavigationServiceInterface',
            function($container) {
                $authService = $container->get('App\\Services\\Interfaces\\AuthenticationServiceInterface');
                return new NavigationService($authService);
            },
            true // singleton
        );
        
        // Bind market data service
        $container->bind(
            'App\\Services\\Interfaces\\MarketDataServiceInterface',
            function($container) {
                return new \App\Services\MarketDataService();
            },
            true // singleton
        );
        
        // Bind view service
        $container->bind(
            'App\\Services\\ViewService',
            function($container) {
                return new \App\Services\ViewService();
            },
            true // singleton
        );
        
        // Bind controllers with proper dependencies
        $container->bind(
            'App\\Controllers\\Web\\DashboardController',
            function($container) {
                $authService = $container->get('App\\Services\\Interfaces\\AuthenticationServiceInterface');
                $portfolioService = $container->get('App\\Services\\Interfaces\\PortfolioServiceInterface');
                $navService = $container->get('App\\Services\\Interfaces\\NavigationServiceInterface');
                
                $controller = new \App\Controllers\Web\DashboardController(
                    $authService,
                    $portfolioService,
                    $navService
                );
                $controller->setContainer($container);
                return $controller;
            }
        );
        
        $container->bind(
            'App\\Controllers\\Web\\BankImportController',
            function($container) {
                $authService = $container->get('App\\Services\\Interfaces\\AuthenticationServiceInterface');
                $importService = $container->get('App\\Services\\Interfaces\\BankImportServiceInterface');
                $navService = $container->get('App\\Services\\Interfaces\\NavigationServiceInterface');
                $bankAccountRepo = $container->get('App\\Repositories\\Interfaces\\BankAccountRepositoryInterface');
                
                $controller = new \App\Controllers\Web\BankImportController(
                    $authService,
                    $importService,
                    $navService,
                    $bankAccountRepo
                );
                $controller->setContainer($container);
                return $controller;
            }
        );
        
        return $container;
    }
    
    /**
     * Bootstrap the application with configured dependencies
     */
    public static function bootstrap(): Container
    {
        $container = self::getInstance();
        
        // Perform any additional initialization here
        
        return $container;
    }
    
    /**
     * Get a service from the container
     */
    public static function get(string $serviceName)
    {
        return self::getInstance()->get($serviceName);
    }
    
    /**
     * Check if a service is bound in the container
     */
    public static function has(string $serviceName): bool
    {
        return self::getInstance()->has($serviceName);
    }
    
    /**
     * Clear container instance (useful for testing)
     */
    public static function reset(): void
    {
        self::$container = null;
    }
}