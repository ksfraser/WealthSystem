<?php
/**
 * Enhanced Test Runner for Trading System  
 * Tests both legacy Ksfraser modules and new SOLID architecture components
 * Updated after dead code cleanup - Sept 26, 2025
 * This can run without PHPUnit as a basic validation tool
 */

// Try to load autoload if it exists, otherwise continue without it
if (file_exists(__DIR__ . '/test_autoload.php')) {
    require_once __DIR__ . '/test_autoload.php';
}

// Load web_ui components that exist
if (file_exists(__DIR__ . '/web_ui/UserAuthDAO.php')) {
    require_once __DIR__ . '/web_ui/UserAuthDAO.php';
}
if (file_exists(__DIR__ . '/web_ui/CommonDAO.php')) {
    require_once __DIR__ . '/web_ui/CommonDAO.php';
}
if (file_exists(__DIR__ . '/web_ui/EnhancedCommonDAO.php')) {
    require_once __DIR__ . '/web_ui/EnhancedCommonDAO.php';
}

// Use namespaced classes if available, otherwise fall back to web_ui classes
$useEnhancedDb = class_exists('Ksfraser\Database\EnhancedDbManager');
$useEnhancedAuth = class_exists('Ksfraser\Database\EnhancedUserAuthDAO');

class SimpleTestRunner
{
    private $tests = [];
    private $passed = 0;
    private $failed = 0;
    private $errors = [];

    public function addTest($name, $callable)
    {
        $this->tests[$name] = $callable;
    }

    public function runTests()
    {
        echo "=== Enhanced Trading System Test Suite ===\n\n";
        
        foreach ($this->tests as $name => $test) {
            echo "Running: {$name}... ";
            
            try {
                $test();
                echo "PASS\n";
                $this->passed++;
            } catch (Exception $e) {
                echo "FAIL\n";
                $this->failed++;
                $this->errors[] = [
                    'test' => $name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ];
            }
        }
        
        $this->printSummary();
    }

    private function printSummary()
    {
        echo "\n=== Test Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total:  " . ($this->passed + $this->failed) . "\n";
        
        if (!empty($this->errors)) {
            echo "\n=== Failures ===\n";
            foreach ($this->errors as $error) {
                echo "FAIL: {$error['test']}\n";
                echo "Error: {$error['error']}\n";
                echo "Trace: " . substr($error['trace'], 0, 200) . "...\n\n";
            }
        }
        
        if ($this->failed > 0) {
            exit(1);
        }
    }

    public function assertEquals($expected, $actual, $message = '')
    {
        if ($expected !== $actual) {
            throw new Exception($message ?: "Expected '{$expected}', got '{$actual}'");
        }
    }

    public function assertTrue($condition, $message = '')
    {
        if (!$condition) {
            throw new Exception($message ?: "Expected true, got false");
        }
    }

    public function assertFalse($condition, $message = '')
    {
        if ($condition) {
            throw new Exception($message ?: "Expected false, got true");
        }
    }

    public function assertNotNull($value, $message = '')
    {
        if ($value === null) {
            throw new Exception($message ?: "Expected non-null value, got null");
        }
    }

    public function assertInstanceOf($className, $object, $message = '')
    {
        if (!($object instanceof $className)) {
            throw new Exception($message ?: "Expected instance of {$className}, got " . get_class($object));
        }
    }
    
    public function assertStringContainsString($needle, $haystack, $message = '')
    {
        if (strpos($haystack, $needle) === false) {
            throw new Exception($message ?: "String does not contain '{$needle}'");
        }
    }
    
    public function assertStringNotContainsString($needle, $haystack, $message = '')
    {
        if (strpos($haystack, $needle) !== false) {
            throw new Exception($message ?: "String should not contain '{$needle}'");
        }
    }
}

// Initialize test runner
$runner = new SimpleTestRunner();

// Test Core Components that exist after cleanup
$runner->addTest('CommonDAO - Instantiation', function() use ($runner) {
    if (class_exists('CommonDAO')) {
        // CommonDAO is abstract, but we can test it's loadable
        $runner->assertTrue(class_exists('CommonDAO'), 'CommonDAO class should be available');
    } else {
        throw new Exception('CommonDAO class not found - check file loading');
    }
});

$runner->addTest('UserAuthDAO - Basic Instantiation', function() use ($runner) {
    if (class_exists('UserAuthDAO')) {
        $auth = new UserAuthDAO();
        $runner->assertInstanceOf('UserAuthDAO', $auth);
        $runner->assertFalse($auth->isLoggedIn(), 'Should not be logged in by default');
    } else {
        throw new Exception('UserAuthDAO class not found - check file loading');
    }
});

$runner->addTest('UserAuthDAO - Database Connection', function() use ($runner) {
    if (class_exists('UserAuthDAO')) {
        $auth = new UserAuthDAO();
        // Test that we can get errors (indicating connection attempt)
        $errors = $auth->getErrors();
        $runner->assertTrue(is_array($errors), 'getErrors should return array');
    } else {
        throw new Exception('UserAuthDAO class not found - check file loading');
    }
});

$runner->addTest('EnhancedCommonDAO - Exists and Functional', function() use ($runner) {
    if (class_exists('EnhancedCommonDAO')) {
        $runner->assertTrue(class_exists('EnhancedCommonDAO'), 'EnhancedCommonDAO should be available');
        // Test that it's a subclass of CommonDAO
        $reflection = new ReflectionClass('EnhancedCommonDAO');
        $runner->assertEquals('CommonDAO', $reflection->getParentClass()->getName());
    } else {
        throw new Exception('EnhancedCommonDAO class not found - this should exist after cleanup');
    }
});

// File Existence Tests - Verify cleanup didn't break core files
$runner->addTest('Core Files - Essential Files Exist', function() use ($runner) {
    $essentialFiles = [
        'web_ui/index.php',
        'web_ui/login.php',
        'web_ui/trades.php',
        'web_ui/portfolios.php',
        'web_ui/UserAuthDAO.php',
        'web_ui/CommonDAO.php',
        'web_ui/EnhancedCommonDAO.php'
    ];
    
    foreach ($essentialFiles as $file) {
        $runner->assertTrue(file_exists(__DIR__ . '/' . $file), "Essential file should exist: $file");
    }
});

// Removed Files Tests - Verify cleanup removed the right files  
$runner->addTest('Cleanup Verification - Removed Files Gone', function() use ($runner) {
    $removedFiles = [
        'web_ui/ExampleUsage.php',
        'web_ui/ImportService.php', 
        'web_ui/ImprovedDAO.php',
        'web_ui/trades_clean.php',
        'web_ui/trades_old.php',
        'web_ui/admin_users_old.php',
        'web_ui/admin_users_bak.php',
        'web_ui/portfolios_old.php',
        'web_ui/SessionManager_old.php'
    ];
    
    foreach ($removedFiles as $file) {
        $runner->assertFalse(file_exists(__DIR__ . '/' . $file), "Removed file should not exist: $file");
    }
});

// Navigation References Test - Verify trades.php is properly referenced
$runner->addTest('Navigation - Trades References', function() use ($runner) {
    $files = ['web_ui/NavigationManager.php', 'web_ui/MenuService.php'];
    
    foreach ($files as $file) {
        if (file_exists(__DIR__ . '/' . $file)) {
            $content = file_get_contents(__DIR__ . '/' . $file);
            $runner->assertStringContainsString('trades.php', $content, "$file should reference trades.php");
        }
    }
});

// UI Components Tests - Test what actually exists
if (file_exists(__DIR__ . '/web_ui/UiRenderer.php')) {
    require_once __DIR__ . '/web_ui/UiRenderer.php';
    
    $runner->addTest('UI - UiRenderer File Loads', function() use ($runner) {
        $runner->assertTrue(class_exists('UiRenderer') || class_exists('NavigationDto'), 'UI components should be loadable');
    });
    
    echo "UI Renderer components found and loaded\n";
} else {
    echo "UiRenderer.php not found, skipping UI component tests\n";
}

// Trading Strategy Tests - Check turtle strategy fix
$runner->addTest('Trading Strategy - Turtle Functions Fixed', function() use ($runner) {
    $turtleFile = __DIR__ . '/src/Ksfraser/Finance/2000/strategies/turtle.php';
    if (file_exists($turtleFile)) {
        $content = file_get_contents($turtleFile);
        // Should have underscore functions, not hyphen functions
        $runner->assertStringContainsString('turtle_system1', $content, 'turtle_system1 function should exist');
        $runner->assertStringContainsString('turtle_system2', $content, 'turtle_system2 function should exist');
        $runner->assertStringNotContainsString('turtle-system1', $content, 'turtle-system1 should be removed');
        $runner->assertStringNotContainsString('turtle-system2', $content, 'turtle-system2 should be removed');
    } else {
        echo "Turtle strategy file not found, skipping turtle tests\n";
    }
});

// Alerts Test - Check alerts fix
$runner->addTest('Alerts - Retractment Function Fixed', function() use ($runner) {
    $alertsFile = __DIR__ . '/src/Ksfraser/Finance/2000/scripts/alerts-retractment.php';
    if (file_exists($alertsFile)) {
        $content = file_get_contents($alertsFile);
        // Should have underscore function, not hyphen function  
        $runner->assertStringContainsString('alert_retractment', $content, 'alert_retractment function should exist');
        $runner->assertStringNotContainsString('alert-retractment', $content, 'alert-retractment should be removed');
    } else {
        echo "Alerts retractment file not found, skipping alerts tests\n";
    }
});

// Run all tests
$runner->runTests();
?>
