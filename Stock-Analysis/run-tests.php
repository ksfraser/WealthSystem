<?php

/**
 * Test Runner Dispatcher
 * 
 * Unified test entry point that allows running different test suites
 * without changing the command line. This enables pre-approved terminal
 * commands in automated workflows.
 * 
 * Usage:
 *   php run-tests.php                    # Run all tests
 *   php run-tests.php trading            # Run trading strategy tests
 *   php run-tests.php services           # Run all service tests
 *   php run-tests.php repositories       # Run repository tests
 *   php run-tests.php container          # Run DI container tests
 *   php run-tests.php unit               # Run unit tests only
 *   php run-tests.php integration        # Run integration tests
 * 
 * Exit Codes:
 *   0 - All tests passed
 *   1 - Test failures or errors
 *   2 - Invalid arguments
 */

// Ensure we're running from the correct directory
$projectRoot = __DIR__;
chdir($projectRoot);

// Parse command line arguments
$suite = $argv[1] ?? 'all';
$options = array_slice($argv, 2);

// Define test suite paths
$testSuites = [
    'all' => [
        'tests/Services/',
        'tests/Repositories/',
        'tests/Container/'
    ],
    'trading' => [
        'tests/Services/Trading/'
    ],
    'services' => [
        'tests/Services/'
    ],
    'repositories' => [
        'tests/Repositories/'
    ],
    'container' => [
        'tests/Container/'
    ],
    'unit' => [
        'tests/Services/',
        'tests/Repositories/',
        'tests/Container/'
    ],
    'integration' => [
        'tests/Integration/'
    ]
];

// Validate suite
if (!isset($testSuites[$suite])) {
    echo "Error: Unknown test suite '{$suite}'\n\n";
    echo "Available suites:\n";
    foreach (array_keys($testSuites) as $availableSuite) {
        echo "  - {$availableSuite}\n";
    }
    exit(2);
}

// Build PHPUnit command
$paths = $testSuites[$suite];
$pathArgs = implode(' ', $paths);

// Add common options
$commonOptions = [
    '--colors=auto',
    '--stop-on-failure'
];

// Merge with user-provided options
$allOptions = array_merge($commonOptions, $options);
$optionsString = implode(' ', $allOptions);

// Build and execute command
$command = sprintf(
    'php vendor/bin/phpunit %s %s',
    $pathArgs,
    $optionsString
);

echo "Running test suite: {$suite}\n";
echo "Command: {$command}\n";
echo str_repeat('-', 80) . "\n\n";

// Execute PHPUnit
passthru($command, $exitCode);

// Return PHPUnit's exit code
exit($exitCode);
