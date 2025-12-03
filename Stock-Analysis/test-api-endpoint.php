<?php

// Simulate the API request
$_GET['action'] = 'list_strategies';

// Capture output
ob_start();
require __DIR__ . '/web_ui/api/strategy-config.php';
$output = ob_get_clean();

echo $output;
