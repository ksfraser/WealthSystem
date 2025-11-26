<?php

/**
 * Application Bootstrap
 * 
 * Initializes the MVC application with dependency injection.
 * Sets up the router and service container for the refactored system.
 */

// Include existing bootstrap for database connections and legacy system
require_once __DIR__ . '/../web_ui/includes/bootstrap.php';

// Include all our new core classes
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $file = __DIR__ . '/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\ServiceContainer;
use App\Core\Router;
use App\Core\Request;
use App\Security\SessionManager;

// Initialize secure session management
SessionManager::start();

// Set security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Strict Transport Security for HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Content Security Policy (adjust as needed for your application)
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;");

// Bootstrap the service container
$container = ServiceContainer::bootstrap();

// Get router from container
$router = $container->get('App\\Core\\Router');

// Dashboard routes
$router->get('/', function() use ($container) {
    $controller = $container->get('App\\Controllers\\Web\\DashboardController');
    return $controller->index();
});

$router->get('/dashboard', function() use ($container) {
    $controller = $container->get('App\\Controllers\\Web\\DashboardController');
    return $controller->index();
});

$router->get('/portfolio', function() use ($container) {
    $controller = $container->get('App\\Controllers\\Web\\DashboardController');
    return $controller->portfolio();
});

$router->get('/settings', function() use ($container) {
    $controller = $container->get('App\\Controllers\\Web\\DashboardController');
    return $controller->settings();
});

// Bank Import routes
$router->get('/bank-import', function() use ($container) {
    $controller = $container->get('App\\Controllers\\Web\\BankImportController');
    return $controller->index();
});

$router->post('/bank-import/upload', function() use ($container) {
    $controller = $container->get('App\\Controllers\\Web\\BankImportController');
    return $controller->upload();
});

$router->get('/bank-import/accounts', function() use ($container) {
    $controller = $container->get('App\\Controllers\\Web\\BankImportController');
    return $controller->accounts();
});

$router->post('/bank-import/accounts', function() use ($container) {
    $controller = $container->get('App\\Controllers\\Web\\BankImportController');
    return $controller->createAccount();
});

// API routes (if needed)
$router->get('/api/portfolio', function() use ($container) {
    $controller = $container->get('App\\Controllers\\Web\\DashboardController');
    return $controller->portfolioData();
});

$router->get('/api/accounts', function() use ($container) {
    $controller = $container->get('App\\Controllers\\Web\\BankImportController');
    return $controller->accountsData();
});

// Handle the request
try {
    $request = new Request();
    $response = $router->dispatch($request);
    
    // Send response
    $response->send();
    
} catch (\Exception $e) {
    // Simple error handling
    http_response_code(500);
    echo "Application Error: " . $e->getMessage();
    
    // Log error if logging is available
    if (function_exists('error_log')) {
        error_log("MVC Application Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    }
}