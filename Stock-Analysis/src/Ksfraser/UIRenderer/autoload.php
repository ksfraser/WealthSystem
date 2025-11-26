<?php

// Ksfraser Package Autoloader
// This file provides PSR-4 autoloading for all Ksfraser packages

spl_autoload_register(function ($class) {
    // Check if this is a class from our namespace
    $prefix = 'Ksfraser\\';
    $base_dir = dirname(__DIR__) . '/';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
