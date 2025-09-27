#!/usr/bin/env php
<?php
/**
 * Test Runner for Stock Data System
 * Provides convenient test execution with various options
 */

require_once __DIR__ . '/vendor/autoload.php';

class TestRunner
{
    private $baseDir;
    private $phpUnitBinary;

    public function __construct()
    {
        $this->baseDir = __DIR__;
        $this->phpUnitBinary = $this->baseDir . '/vendor/bin/phpunit';
        
        if (!file_exists($this->phpUnitBinary)) {
            $this->phpUnitBinary = 'phpunit'; // Fallback to global installation
        }
    }

    public function run($args = [])
    {
        echo "🧪 Stock Data System Test Runner\n";
        echo "================================\n\n";

        if (empty($args) || in_array('--help', $args) || in_array('-h', $args)) {
            $this->showHelp();
            return;
        }

        // Parse command line arguments
        $testSuite = null;
        $coverage = false;
        $verbose = false;
        $filter = null;

        foreach ($args as $arg) {
            if (strpos($arg, '--suite=') === 0) {
                $testSuite = substr($arg, 8);
            } elseif ($arg === '--coverage') {
                $coverage = true;
            } elseif ($arg === '--verbose' || $arg === '-v') {
                $verbose = true;
            } elseif (strpos($arg, '--filter=') === 0) {
                $filter = substr($arg, 9);
            }
        }

        $this->executeTests($testSuite, $coverage, $verbose, $filter);
    }

    private function executeTests($testSuite = null, $coverage = false, $verbose = false, $filter = null)
    {
        $command = $this->phpUnitBinary;
        
        // Add configuration file
        $command .= ' --configuration phpunit.xml';

        // Add test suite if specified
        if ($testSuite) {
            $command .= ' --testsuite "' . $testSuite . '"';
            echo "Running test suite: {$testSuite}\n";
        } else {
            echo "Running all tests...\n";
        }

        // Add coverage if requested
        if ($coverage) {
            $command .= ' --coverage-html tests/coverage/html --coverage-text';
            echo "Generating code coverage report...\n";
        }

        // Add verbosity
        if ($verbose) {
            $command .= ' --verbose';
        }

        // Add filter if specified
        if ($filter) {
            $command .= ' --filter "' . $filter . '"';
            echo "Filtering tests: {$filter}\n";
        }

        echo "\nExecuting: {$command}\n";
        echo str_repeat('-', 50) . "\n";

        // Create coverage directory if needed
        if ($coverage) {
            $coverageDir = $this->baseDir . '/tests/coverage';
            if (!is_dir($coverageDir)) {
                mkdir($coverageDir, 0755, true);
            }
            if (!is_dir($coverageDir . '/html')) {
                mkdir($coverageDir . '/html', 0755, true);
            }
        }

        // Execute the command
        $startTime = microtime(true);
        passthru($command, $exitCode);
        $endTime = microtime(true);

        $executionTime = round($endTime - $startTime, 2);
        
        echo str_repeat('-', 50) . "\n";
        echo "Test execution completed in {$executionTime} seconds\n";

        if ($exitCode === 0) {
            echo "✅ All tests passed!\n";
        } else {
            echo "❌ Some tests failed (exit code: {$exitCode})\n";
        }

        if ($coverage) {
            $coverageFile = $this->baseDir . '/tests/coverage/html/index.html';
            if (file_exists($coverageFile)) {
                echo "📊 Coverage report generated: {$coverageFile}\n";
            }
        }

        echo "\n";
    }

    private function showHelp()
    {
        echo "Usage: php test_runner.php [options]\n\n";
        echo "Options:\n";
        echo "  --suite=<name>    Run specific test suite\n";
        echo "                    Available suites:\n";
        echo "                    - 'Stock Data Unit Tests'\n";
        echo "                    - 'Stock Data Integration Tests'\n";
        echo "                    - 'Database'\n";
        echo "                    - 'Auth'\n";
        echo "                    - 'All'\n";
        echo "  --coverage        Generate code coverage report\n";
        echo "  --verbose, -v     Enable verbose output\n";
        echo "  --filter=<pattern> Run tests matching pattern\n";
        echo "  --help, -h        Show this help message\n\n";
        
        echo "Examples:\n";
        echo "  php test_runner.php                              # Run all tests\n";
        echo "  php test_runner.php --suite='Stock Data Unit Tests'  # Run unit tests only\n";
        echo "  php test_runner.php --coverage                   # Run with coverage\n";
        echo "  php test_runner.php --filter=YahooFinance       # Run Yahoo Finance tests\n";
        echo "  php test_runner.php --verbose --coverage        # Verbose with coverage\n\n";

        echo "Quick Commands:\n";
        echo "  php test_runner.php unit         # Shortcut for unit tests\n";
        echo "  php test_runner.php integration  # Shortcut for integration tests\n";
        echo "  php test_runner.php fast         # Run unit tests without coverage\n";
        echo "  php test_runner.php full         # Run all tests with coverage\n\n";
    }

    public function handleShortcuts($args)
    {
        if (empty($args)) {
            return $args;
        }

        $command = $args[0];
        
        switch ($command) {
            case 'unit':
                return ['--suite=Stock Data Unit Tests'];
            case 'integration':
                return ['--suite=Stock Data Integration Tests'];
            case 'fast':
                return ['--suite=Stock Data Unit Tests', '--verbose'];
            case 'full':
                return ['--coverage', '--verbose'];
            default:
                return $args;
        }
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    $args = array_slice($argv, 1);
    
    $runner = new TestRunner();
    $processedArgs = $runner->handleShortcuts($args);
    $runner->run($processedArgs);
}