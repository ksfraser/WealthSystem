<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\PythonExecutorService;
use App\Security\ProcessManager;

/**
 * Test PythonExecutorService
 * 
 * Tests Python script execution with temp file management and security.
 */
class PythonExecutorServiceTest extends TestCase
{
    private PythonExecutorService $service;
    private string $testDir;
    
    protected function setUp(): void
    {
        $this->testDir = dirname(__DIR__, 2);
        $processManager = new ProcessManager($this->testDir);
        $this->service = new PythonExecutorService($processManager);
    }
    
    /**
     * Test check Python environment
     */
    public function testCheckPythonEnvironment(): void
    {
        $result = $this->service->checkPythonEnvironment();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('available', $result);
        $this->assertArrayHasKey('version', $result);
        $this->assertIsBool($result['available']);
    }
    
    /**
     * Test execute script with non-whitelisted script fails
     */
    public function testExecuteScriptNonWhitelistedFails(): void
    {
        $result = $this->service->executeScript(
            'malicious_script.py',
            'some_function',
            []
        );
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not whitelisted', $result['error']);
    }
    
    /**
     * Test execute script result structure
     */
    public function testExecuteScriptResultStructure(): void
    {
        $result = $this->service->executeScript(
            'nonexistent.py',
            'test_function',
            []
        );
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('output', $result);
        $this->assertArrayHasKey('error', $result);
    }
    
    /**
     * Test create temp file for data
     */
    public function testCreateTempFileForData(): void
    {
        $data = ['test' => 'value', 'number' => 123];
        $filePath = $this->service->createTempFile($data);
        
        $this->assertFileExists($filePath);
        $this->assertStringEndsWith('.json', $filePath);
        
        $content = file_get_contents($filePath);
        $decoded = json_decode($content, true);
        
        $this->assertEquals($data, $decoded);
        
        // Cleanup
        $this->service->deleteTempFile($filePath);
    }
    
    /**
     * Test create temp file with custom suffix
     */
    public function testCreateTempFileWithCustomSuffix(): void
    {
        $data = ['test' => 'data'];
        $filePath = $this->service->createTempFile($data, 'custom');
        
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('custom', $filePath);
        $this->assertStringEndsWith('.json', $filePath);
        
        // Cleanup
        $this->service->deleteTempFile($filePath);
    }
    
    /**
     * Test delete temp file
     */
    public function testDeleteTempFile(): void
    {
        $data = ['test' => 'value'];
        $filePath = $this->service->createTempFile($data);
        
        $this->assertFileExists($filePath);
        
        $result = $this->service->deleteTempFile($filePath);
        
        $this->assertTrue($result);
        $this->assertFileDoesNotExist($filePath);
    }
    
    /**
     * Test delete non-existent temp file returns false
     */
    public function testDeleteNonExistentTempFile(): void
    {
        $result = $this->service->deleteTempFile('/nonexistent/file.json');
        
        $this->assertFalse($result);
    }
    
    /**
     * Test get temp directory
     */
    public function testGetTempDirectory(): void
    {
        $tempDir = $this->service->getTempDirectory();
        
        $this->assertIsString($tempDir);
        $this->assertDirectoryExists($tempDir);
    }
    
    /**
     * Test execute with temp file
     */
    public function testExecuteWithTempFile(): void
    {
        $data = ['symbol' => 'AAPL', 'period' => '1y'];
        
        $result = $this->service->executeWithTempFile(
            'trading_script.py',
            'fetch_price_data',
            $data
        );
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }
    
    /**
     * Test execute with timeout
     */
    public function testExecuteWithTimeout(): void
    {
        $result = $this->service->executeScript(
            'trading_script.py',
            'test_function',
            [],
            1 // 1 second timeout
        );
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }
    
    /**
     * Test get process manager
     */
    public function testGetProcessManager(): void
    {
        $pm = $this->service->getProcessManager();
        
        $this->assertInstanceOf(ProcessManager::class, $pm);
    }
    
    /**
     * Test execute script builds correct command
     */
    public function testExecuteScriptBuildsCorrectCommand(): void
    {
        // This test verifies the command structure by checking the result
        // Since we can't directly access the command, we check behavior
        $result = $this->service->executeScript(
            'trading_script.py',
            'test_function',
            ['param1' => 'value1']
        );
        
        // Should fail with "not found" or similar, not with command structure error
        if (!$result['success']) {
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
     * Test cleanup temp files on destruct
     */
    public function testCleanupTempFilesOnDestruct(): void
    {
        $service = new PythonExecutorService(new ProcessManager($this->testDir));
        
        $file1 = $service->createTempFile(['test' => 1]);
        $file2 = $service->createTempFile(['test' => 2]);
        
        $this->assertFileExists($file1);
        $this->assertFileExists($file2);
        
        // Trigger destructor
        unset($service);
        
        // Files should be cleaned up
        $this->assertFileDoesNotExist($file1);
        $this->assertFileDoesNotExist($file2);
    }
    
    /**
     * Test temp file has secure permissions
     */
    public function testTempFileHasSecurePermissions(): void
    {
        $data = ['secure' => 'data'];
        $filePath = $this->service->createTempFile($data);
        
        $this->assertFileExists($filePath);
        
        // On Unix, check permissions are restrictive
        if (PHP_OS_FAMILY !== 'Windows') {
            $perms = fileperms($filePath);
            $mode = $perms & 0777;
            
            // Should be readable/writable by owner only (0600) or similar
            $this->assertLessThanOrEqual(0644, $mode);
        }
        
        // Cleanup
        $this->service->deleteTempFile($filePath);
    }
}
