<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\PythonBridgeService;

/**
 * Test PythonBridgeService
 * 
 * Tests Python bridge script management and path resolution.
 */
class PythonBridgeServiceTest extends TestCase
{
    private PythonBridgeService $service;
    private string $testDir;
    
    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/python_bridge_test_' . time();
        mkdir($this->testDir, 0755, true);
        $this->service = new PythonBridgeService($this->testDir);
    }
    
    protected function tearDown(): void
    {
        // Clean up test directory
        if (is_dir($this->testDir)) {
            $files = glob($this->testDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testDir);
        }
    }
    
    /**
     * Test get bridge script path
     */
    public function testGetBridgeScriptPath(): void
    {
        $path = $this->service->getBridgeScriptPath();
        
        $this->assertIsString($path);
        $this->assertStringEndsWith('python_bridge.py', $path);
        $this->assertStringContainsString($this->testDir, $path);
    }
    
    /**
     * Test ensure bridge script creates script if not exists
     */
    public function testEnsureBridgeScriptCreatesScript(): void
    {
        $path = $this->service->ensureBridgeScript();
        
        $this->assertFileExists($path);
        $this->assertStringEndsWith('.py', $path);
        
        // Check script has Python shebang
        $content = file_get_contents($path);
        $this->assertStringStartsWith('#!/usr/bin/env python3', $content);
    }
    
    /**
     * Test ensure bridge script returns existing script
     */
    public function testEnsureBridgeScriptReturnsExisting(): void
    {
        $path1 = $this->service->ensureBridgeScript();
        $mtime1 = filemtime($path1);
        
        sleep(1);
        
        $path2 = $this->service->ensureBridgeScript();
        $mtime2 = filemtime($path2);
        
        $this->assertEquals($path1, $path2);
        $this->assertEquals($mtime1, $mtime2, 'Script should not be recreated');
    }
    
    /**
     * Test bridge script has correct functions
     */
    public function testBridgeScriptHasRequiredFunctions(): void
    {
        $path = $this->service->ensureBridgeScript();
        $content = file_get_contents($path);
        
        $this->assertStringContainsString('def fetch_price_data(', $content);
        $this->assertStringContainsString('def get_portfolio_data(', $content);
        $this->assertStringContainsString('def update_symbols(', $content);
        $this->assertStringContainsString('def main():', $content);
    }
    
    /**
     * Test bridge script is executable
     */
    public function testBridgeScriptIsExecutable(): void
    {
        $path = $this->service->ensureBridgeScript();
        
        // On Windows, check file exists; on Unix, check executable bit
        if (PHP_OS_FAMILY === 'Windows') {
            $this->assertFileExists($path);
        } else {
            $perms = fileperms($path);
            $this->assertTrue(($perms & 0x0040) !== 0, 'Script should be executable');
        }
    }
    
    /**
     * Test bridge script uses JSON for communication
     */
    public function testBridgeScriptUsesJson(): void
    {
        $path = $this->service->ensureBridgeScript();
        $content = file_get_contents($path);
        
        $this->assertStringContainsString('import json', $content);
        $this->assertStringContainsString('json.dumps(', $content);
        $this->assertStringContainsString('json.loads(', $content);
    }
    
    /**
     * Test bridge script handles errors
     */
    public function testBridgeScriptHandlesErrors(): void
    {
        $path = $this->service->ensureBridgeScript();
        $content = file_get_contents($path);
        
        $this->assertStringContainsString('try:', $content);
        $this->assertStringContainsString('except', $content);
        $this->assertStringContainsString('"error":', $content);
    }
    
    /**
     * Test get script template returns valid Python code
     */
    public function testGetScriptTemplateReturnsValidPython(): void
    {
        $template = $this->service->getScriptTemplate();
        
        $this->assertIsString($template);
        $this->assertStringStartsWith('#!/usr/bin/env python3', $template);
        $this->assertStringContainsString('import sys', $template);
        $this->assertStringContainsString('import json', $template);
    }
    
    /**
     * Test script template has proper docstrings
     */
    public function testScriptTemplateHasDocstrings(): void
    {
        $template = $this->service->getScriptTemplate();
        
        $this->assertStringContainsString('"""', $template);
        $this->assertStringContainsString('Python Bridge Script', $template);
    }
    
    /**
     * Test get base directory
     */
    public function testGetBaseDirectory(): void
    {
        $baseDir = $this->service->getBaseDirectory();
        
        $this->assertIsString($baseDir);
        $this->assertEquals($this->testDir, $baseDir);
    }
    
    /**
     * Test default base directory
     */
    public function testDefaultBaseDirectory(): void
    {
        $service = new PythonBridgeService();
        $baseDir = $service->getBaseDirectory();
        
        $this->assertIsString($baseDir);
        $this->assertDirectoryExists($baseDir);
    }
    
    /**
     * Test script creation with custom template
     */
    public function testScriptCreationWithCustomTemplate(): void
    {
        $customTemplate = "#!/usr/bin/env python3\nprint('custom')";
        $service = new PythonBridgeService($this->testDir, $customTemplate);
        
        $path = $service->ensureBridgeScript();
        $content = file_get_contents($path);
        
        $this->assertEquals($customTemplate, $content);
    }
    
    /**
     * Test script recreation if deleted
     */
    public function testScriptRecreationIfDeleted(): void
    {
        $path1 = $this->service->ensureBridgeScript();
        $this->assertFileExists($path1);
        
        unlink($path1);
        $this->assertFileDoesNotExist($path1);
        
        $path2 = $this->service->ensureBridgeScript();
        $this->assertFileExists($path2);
        $this->assertEquals($path1, $path2);
    }
    
    /**
     * Test script path is absolute
     */
    public function testScriptPathIsAbsolute(): void
    {
        $path = $this->service->getBridgeScriptPath();
        
        $this->assertTrue(
            $path[0] === '/' || (strlen($path) > 1 && $path[1] === ':'),
            'Path should be absolute'
        );
    }
}
