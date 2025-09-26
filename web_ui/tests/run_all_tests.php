<?php
/**
 * RBAC Test Suite Runner
 * 
 * Runs all RBAC tests and provides a comprehensive report
 */

echo "🧪 RBAC Test Suite Runner\n";
echo "========================\n\n";

$testFiles = [
    'RBAC Service Tests' => [
        'rbac/test_rbac_service.php' => 'Tests core RBACService functionality'
    ],
    'UserAuthDAO Integration Tests' => [
        'rbac/test_userauth_rbac.php' => 'Tests UserAuthDAO RBAC integration'
    ],
    'Migration and Setup Tests' => [
        'rbac/verify_rbac.php' => 'Verifies RBAC migration results'
    ]
];

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

foreach ($testFiles as $category => $tests) {
    echo "📋 $category\n";
    echo str_repeat("-", strlen($category) + 4) . "\n";
    
    foreach ($tests as $testFile => $description) {
        echo "  Running: $description\n";
        echo "  File: $testFile\n";
        
        $totalTests++;
        
        // Change to web_ui directory for proper path resolution
        $originalDir = getcwd();
        chdir(__DIR__ . '/..');
        
        // Capture output
        ob_start();
        $error = null;
        
        try {
            include $testFile;
            $output = ob_get_clean();
            
            // Check if test passed (look for success indicators)
            if (strpos($output, '❌') !== false || strpos($output, 'failed') !== false || strpos($output, 'Error') !== false) {
                echo "  Result: ❌ FAILED\n";
                $failedTests++;
                echo "  Output: " . substr(trim($output), 0, 200) . "...\n";
            } else {
                echo "  Result: ✅ PASSED\n";
                $passedTests++;
            }
            
        } catch (Exception $e) {
            ob_end_clean();
            echo "  Result: ❌ EXCEPTION\n";
            echo "  Error: " . $e->getMessage() . "\n";
            $failedTests++;
        } catch (Error $e) {
            ob_end_clean();
            echo "  Result: ❌ ERROR\n";
            echo "  Error: " . $e->getMessage() . "\n";
            $failedTests++;
        }
        
        // Restore directory
        chdir($originalDir);
        
        echo "\n";
    }
}

// Summary
echo "📊 Test Summary\n";
echo "===============\n";
echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests ✅\n";
echo "Failed: $failedTests " . ($failedTests > 0 ? '❌' : '✅') . "\n";

if ($failedTests === 0) {
    echo "\n🎉 All tests passed! RBAC system is working correctly.\n";
} else {
    echo "\n⚠️  Some tests failed. Please review the output above.\n";
    exit(1);
}
?>