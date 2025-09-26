<?php
/**
 * UAT Automation Helper Script
 * Performs automated checks for the UAT scenarios
 * 
 * Usage: php run_uat.php
 */

class UATRunner {
    private $results = [];
    private $baseUrl;
    private $errors = [];

    public function __construct($baseUrl = null) {
        // Try to detect if we're running on a web server
        $this->baseUrl = $baseUrl ?: $this->detectBaseUrl();
    }

    private function detectBaseUrl() {
        // For local testing, assume localhost
        $possibleUrls = [
            'http://localhost/ChatGPT-Micro-Cap-Experiment/web_ui/',
            'http://localhost:8080/web_ui/',
            'http://127.0.0.1/web_ui/',
            __DIR__ . '/web_ui/' // File system fallback
        ];
        
        return $possibleUrls[0]; // Default to first option
    }

    /**
     * Run all automated UAT checks
     */
    public function runAll() {
        echo "🧪 Starting User Acceptance Testing\n";
        echo "Base URL: {$this->baseUrl}\n\n";

        $this->checkFileExistence();
        $this->checkFileContent();
        $this->checkDatabaseClasses();
        $this->checkNavigationReferences();
        $this->checkPHPSyntax();
        
        $this->printResults();
    }

    /**
     * UAT Scenario Support: Verify critical files exist
     */
    private function checkFileExistence() {
        echo "📁 Checking File Existence...\n";

        // Files that MUST exist
        $requiredFiles = [
            'web_ui/index.php' => 'Main entry point',
            'web_ui/login.php' => 'Authentication page', 
            'web_ui/trades.php' => 'Trading functionality',
            'web_ui/portfolios.php' => 'Portfolio management',
            'web_ui/analytics.php' => 'Analytics page',
            'web_ui/UserAuthDAO.php' => 'Authentication backend',
            'web_ui/CommonDAO.php' => 'Database base class',
            'web_ui/EnhancedCommonDAO.php' => 'Enhanced database operations'
        ];

        foreach ($requiredFiles as $file => $description) {
            $exists = file_exists(__DIR__ . '/' . $file);
            $this->recordResult("File Exists: $file", $exists, $description);
            if (!$exists) {
                $this->errors[] = "CRITICAL: Required file missing - $file ($description)";
            }
        }

        // Files that MUST NOT exist (removed during cleanup)
        $prohibitedFiles = [
            'web_ui/ExampleUsage.php' => 'Orphaned example file',
            'web_ui/ImportService.php' => 'Orphaned import service',
            'web_ui/ImprovedDAO.php' => 'Orphaned DAO implementation',
            'web_ui/trades_clean.php' => 'Duplicate trades file',
            'web_ui/trades_old.php' => 'Old trades file',
            'web_ui/admin_users_old.php' => 'Old admin backup',
            'web_ui/admin_users_bak.php' => 'Admin backup file',
            'web_ui/portfolios_old.php' => 'Old portfolios backup',
            'web_ui/SessionManager_old.php' => 'Old session manager backup'
        ];

        foreach ($prohibitedFiles as $file => $description) {
            $exists = file_exists(__DIR__ . '/' . $file);
            $this->recordResult("File Absent: $file", !$exists, $description);
            if ($exists) {
                $this->errors[] = "WARNING: File should have been removed - $file ($description)";
            }
        }
    }

    /**
     * Check content of key files for proper cleanup
     */
    private function checkFileContent() {
        echo "📄 Checking File Content...\n";

        // Check turtle strategy for fixed function names
        $turtleFile = 'src/Ksfraser/Finance/2000/strategies/turtle.php';
        if (file_exists(__DIR__ . '/' . $turtleFile)) {
            $content = file_get_contents(__DIR__ . '/' . $turtleFile);
            
            $hasUnderscoreFuncs = strpos($content, 'turtle_system1') !== false && 
                                 strpos($content, 'turtle_system2') !== false;
            $hasHyphenFuncs = strpos($content, 'turtle-system1') !== false || 
                             strpos($content, 'turtle-system2') !== false;
            
            $this->recordResult('Turtle Strategy: Uses underscore functions', $hasUnderscoreFuncs, 'PHPDocumentor compatible function names');
            $this->recordResult('Turtle Strategy: No hyphen functions', !$hasHyphenFuncs, 'Removed PHPDocumentor-incompatible names');
        }

        // Check alerts for fixed function names
        $alertsFile = 'src/Ksfraser/Finance/2000/scripts/alerts-retractment.php';
        if (file_exists(__DIR__ . '/' . $alertsFile)) {
            $content = file_get_contents(__DIR__ . '/' . $alertsFile);
            
            $hasUnderscoreFunc = strpos($content, 'alert_retractment') !== false;
            $hasHyphenFunc = strpos($content, 'alert-retractment') !== false;
            
            $this->recordResult('Alerts: Uses underscore function', $hasUnderscoreFunc, 'PHPDocumentor compatible function name');
            $this->recordResult('Alerts: No hyphen function', !$hasHyphenFunc, 'Removed PHPDocumentor-incompatible name');
        }
    }

    /**
     * Verify database classes load correctly
     */
    private function checkDatabaseClasses() {
        echo "🗄️  Checking Database Classes...\n";

        try {
            require_once __DIR__ . '/web_ui/CommonDAO.php';
            $this->recordResult('CommonDAO: Loads correctly', true, 'Base DAO class available');
            
            require_once __DIR__ . '/web_ui/EnhancedCommonDAO.php';  
            $this->recordResult('EnhancedCommonDAO: Loads correctly', true, 'Enhanced DAO class available');
            
            // Check inheritance
            $reflection = new ReflectionClass('EnhancedCommonDAO');
            $parentName = $reflection->getParentClass()->getName();
            $correctInheritance = $parentName === 'CommonDAO';
            $this->recordResult('EnhancedCommonDAO: Inherits from CommonDAO', $correctInheritance, 'Proper class hierarchy');

            require_once __DIR__ . '/web_ui/UserAuthDAO.php';
            $auth = new UserAuthDAO();
            $this->recordResult('UserAuthDAO: Instantiates correctly', true, 'Authentication class functional');
            
        } catch (Exception $e) {
            $this->recordResult('Database Classes: Load without errors', false, 'Error: ' . $e->getMessage());
            $this->errors[] = "CRITICAL: Database class loading failed - " . $e->getMessage();
        }
    }

    /**
     * Check navigation files reference correct pages
     */
    private function checkNavigationReferences() {
        echo "🧭 Checking Navigation References...\n";

        $navFiles = [
            'web_ui/NavigationManager.php',
            'web_ui/MenuService.php', 
            'web_ui/UiRendererNew.php'
        ];

        foreach ($navFiles as $file) {
            if (file_exists(__DIR__ . '/' . $file)) {
                $content = file_get_contents(__DIR__ . '/' . $file);
                
                // Should reference trades.php
                $hasTrades = strpos($content, 'trades.php') !== false;
                $this->recordResult("$file: References trades.php", $hasTrades, 'Main trades page linked correctly');
                
                // Should NOT reference old files
                $hasOldTrades = strpos($content, 'trades_old.php') !== false || strpos($content, 'trades_clean.php') !== false;
                $this->recordResult("$file: No old trades references", !$hasOldTrades, 'No broken links to removed files');
            }
        }
    }

    /**
     * Quick PHP syntax check on key files
     */
    private function checkPHPSyntax() {
        echo "⚡ Checking PHP Syntax...\n";

        $keyFiles = [
            'web_ui/index.php',
            'web_ui/login.php', 
            'web_ui/trades.php',
            'web_ui/portfolios.php',
            'web_ui/UserAuthDAO.php',
            'web_ui/CommonDAO.php',
            'web_ui/EnhancedCommonDAO.php'
        ];

        foreach ($keyFiles as $file) {
            if (file_exists(__DIR__ . '/' . $file)) {
                $output = [];
                $returnCode = 0;
                exec("php -l \"" . __DIR__ . '/' . $file . "\" 2>&1", $output, $returnCode);
                
                $syntaxOk = $returnCode === 0;
                $this->recordResult("PHP Syntax: $file", $syntaxOk, $syntaxOk ? 'Valid PHP syntax' : 'Syntax error: ' . implode(' ', $output));
                
                if (!$syntaxOk) {
                    $this->errors[] = "CRITICAL: Syntax error in $file - " . implode(' ', $output);
                }
            }
        }
    }

    /**
     * Record test result
     */
    private function recordResult($test, $passed, $description) {
        $status = $passed ? '✅ PASS' : '❌ FAIL';
        echo "  $status: $test\n";
        
        $this->results[] = [
            'test' => $test,
            'passed' => $passed,
            'description' => $description
        ];
    }

    /**
     * Print final results summary
     */
    private function printResults() {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "🎯 UAT AUTOMATION RESULTS\n";
        echo str_repeat('=', 60) . "\n";

        $total = count($this->results);
        $passed = count(array_filter($this->results, function($r) { return $r['passed']; }));
        $failed = $total - $passed;

        echo "Total Tests: $total\n";
        echo "✅ Passed: $passed\n"; 
        echo "❌ Failed: $failed\n";

        $successRate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
        echo "📊 Success Rate: {$successRate}%\n";

        if (!empty($this->errors)) {
            echo "\n🚨 CRITICAL ISSUES:\n";
            foreach ($this->errors as $error) {
                echo "   • $error\n";
            }
        }

        if ($failed === 0) {
            echo "\n🎉 All automated checks passed! System ready for manual UAT.\n";
            echo "📋 Next: Follow manual UAT scenarios in UAT_Plan.md\n";
        } else {
            echo "\n⚠️  Some automated checks failed. Review issues before manual UAT.\n";
        }

        echo "\n💡 Manual UAT still required for:\n";
        echo "   • User authentication flow\n";
        echo "   • Database operations\n"; 
        echo "   • Browser-based functionality\n";
        echo "   • User interface behavior\n";
        echo "\n🔗 See UAT_Plan.md for complete testing scenarios.\n";
    }
}

// Run UAT automation if called from command line
if (php_sapi_name() === 'cli') {
    $runner = new UATRunner();
    $runner->runAll();
}
?>