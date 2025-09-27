<?php
/**
 * Cron-compatible Auto-Fetch Script
 * Can be run by cron, Task Scheduler, or manually
 * Usage: php cron_auto_fetch.php
 */

// Change to the web_ui directory
chdir(__DIR__);

require_once 'AutoFetchService.php';
require_once 'StockDAO.php';

try {
    // Initialize services with database connection if possible
    $stockDAO = null;
    try {
        require_once 'includes/config.php';
        $stockDAO = new StockDAO($db);
    } catch (Exception $e) {
        // Fall back to file-based logging if database unavailable
        error_log("Database unavailable for auto-fetch, using file logging: " . $e->getMessage());
    }
    
    $autoFetch = new AutoFetchService($stockDAO);
    
    // Get current status
    $status = $autoFetch->getStatus();
    
    if (!$status['enabled']) {
        echo "Auto-fetch is disabled\n";
        exit(0);
    }
    
    if (!$status['should_fetch_today']) {
        echo "Data already fetched today (" . $status['last_fetch_date'] . ")\n";
        exit(0);
    }
    
    // Perform the fetch
    echo "Starting daily stock data fetch...\n";
    $result = $autoFetch->performAutoFetchIfNeeded();
    
    if ($result['success']) {
        echo "SUCCESS: " . $result['message'] . "\n";
        exit(0);
    } else {
        echo "ERROR: " . $result['message'] . "\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    error_log("Cron auto-fetch error: " . $e->getMessage());
    exit(1);
}