<?php
// Global PHPUnit bootstrap for test-mode navigation injection
if (!defined('TEST_MODE_9f3b2c')) {
    define('TEST_MODE_9f3b2c', true);
}

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Register App namespace autoloader for MVC components
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    if (strpos($class, 'App\\') === 0) {
        $file = __DIR__ . '/../app/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});
