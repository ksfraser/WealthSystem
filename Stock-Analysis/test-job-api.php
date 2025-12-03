<?php
// Test script for job manager API

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'list';

ob_start();
try {
    require __DIR__ . '/web_ui/job_manager_api.php';
    $output = ob_get_clean();
    
    echo "API Response:\n";
    echo $output . "\n\n";
    
    $decoded = json_decode($output, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✅ Valid JSON response\n";
        if (isset($decoded['error'])) {
            echo "❌ Error: " . $decoded['error'] . "\n";
        } else {
            echo "Success!\n";
        }
    } else {
        echo "❌ Invalid JSON: " . json_last_error_msg() . "\n";
        echo "Raw output:\n" . $output . "\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
