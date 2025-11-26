<?php

namespace App\Services;

use App\Security\ProcessManager;

/**
 * PythonExecutorService - Secure Python script execution
 * 
 * Responsible for:
 * - Executing Python scripts via ProcessManager
 * - Managing temporary files for data exchange
 * - Checking Python environment availability
 * - Command building and timeout management
 * 
 * Follows Single Responsibility Principle by focusing ONLY on
 * script execution and temp file management, not parsing or bridge management.
 * 
 * @package App\Services
 * @author Stock Analysis System
 * @version 1.0.0
 * 
 * Security Features:
 * - Uses ProcessManager for whitelisted script execution
 * - Automatic temp file cleanup on destruction
 * - Secure temp file permissions
 * - Timeout enforcement
 * 
 * Usage Example:
 * ```php
 * $processManager = new ProcessManager('/path/to/scripts');
 * $executor = new PythonExecutorService($processManager);
 * 
 * // Simple execution
 * $result = $executor->executeScript('trading_script.py', 'fetch_data', ['symbol' => 'AAPL']);
 * 
 * // With temp file (for large data)
 * $data = ['symbols' => ['AAPL', 'GOOGL', 'MSFT']];
 * $result = $executor->executeWithTempFile('trading_script.py', 'analyze', $data);
 * ```
 */
class PythonExecutorService
{
    /**
     * Process manager for secure script execution
     * 
     * @var ProcessManager
     */
    private ProcessManager $processManager;
    
    /**
     * Temporary files created during execution
     * 
     * Tracked for automatic cleanup on destruction.
     * 
     * @var array<string>
     */
    private array $tempFiles = [];
    
    /**
     * Constructor
     * 
     * @param ProcessManager|null $processManager Process manager instance (optional)
     */
    public function __construct(?ProcessManager $processManager = null)
    {
        $this->processManager = $processManager ?? new ProcessManager();
    }
    
    /**
     * Destructor - cleanup temp files
     */
    public function __destruct()
    {
        foreach ($this->tempFiles as $file) {
            $this->deleteTempFile($file);
        }
    }
    
    /**
     * Check if Python environment is available
     * 
     * @return array{available: bool, version: string}
     */
    public function checkPythonEnvironment(): array
    {
        $available = $this->processManager->isPythonAvailable();
        $version = $available ? $this->processManager->getPythonVersion() : 'Not available';
        
        return [
            'available' => $available,
            'version' => $version
        ];
    }
    
    /**
     * Execute Python script with arguments
     * 
     * Executes a whitelisted Python script via ProcessManager.
     * Arguments are passed as command-line parameters.
     * 
     * @param string $scriptName Script filename (must be whitelisted)
     * @param string $function Function name to call
     * @param array $params Parameters to pass
     * @param int|null $timeout Timeout in seconds (optional)
     * 
     * @return array{success: bool, output: string, error: string}
     */
    public function executeScript(
        string $scriptName,
        string $function,
        array $params,
        ?int $timeout = null
    ): array {
        // Build arguments array
        $args = [
            $function,
            json_encode($params)
        ];
        
        // Execute via ProcessManager
        return $this->processManager->executePythonScript($scriptName, $args, $timeout);
    }
    
    /**
     * Execute Python script using temp file for data
     * 
     * Creates a temporary JSON file containing the data, executes the script
     * with the file path as an argument, then cleans up the temp file.
     * 
     * Useful for large data sets or when avoiding shell escaping issues.
     * 
     * @param string $scriptName Script filename (must be whitelisted)
     * @param string $function Function name to call
     * @param array $data Data to pass via temp file
     * @param int|null $timeout Timeout in seconds (optional)
     * 
     * @return array{success: bool, output: string, error: string}
     */
    public function executeWithTempFile(
        string $scriptName,
        string $function,
        array $data,
        ?int $timeout = null
    ): array {
        // Create temp file with data
        $tempFile = $this->createTempFile($data, 'python_exec');
        
        try {
            // Execute script with temp file path
            $args = [
                $function,
                '--file',
                $tempFile
            ];
            
            $result = $this->processManager->executePythonScript($scriptName, $args, $timeout);
            
            return $result;
            
        } finally {
            // Always cleanup temp file
            $this->deleteTempFile($tempFile);
        }
    }
    
    /**
     * Create temporary JSON file
     * 
     * Creates a temp file containing JSON-encoded data.
     * File is tracked for automatic cleanup.
     * 
     * @param array $data Data to write
     * @param string $prefix Filename prefix (optional)
     * 
     * @return string Path to created temp file
     */
    public function createTempFile(array $data, string $prefix = 'php_python'): string
    {
        // Create temp file with recognizable prefix
        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir . DIRECTORY_SEPARATOR . $prefix . '_' . uniqid() . '.json';
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($tempFile, $json);
        
        // Set secure permissions (Unix/Linux only)
        if (PHP_OS_FAMILY !== 'Windows') {
            chmod($tempFile, 0600); // Owner read/write only
        }
        
        // Track for cleanup
        $this->tempFiles[] = $tempFile;
        
        return $tempFile;
    }
    
    /**
     * Delete temporary file
     * 
     * Safely deletes a temporary file if it exists.
     * 
     * @param string $filePath Path to file to delete
     * 
     * @return bool True if deleted, false if file doesn't exist
     */
    public function deleteTempFile(string $filePath): bool
    {
        if (file_exists($filePath)) {
            unlink($filePath);
            
            // Remove from tracking array
            $this->tempFiles = array_filter(
                $this->tempFiles,
                fn($f) => $f !== $filePath
            );
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get temp directory path
     * 
     * @return string Temp directory path
     */
    public function getTempDirectory(): string
    {
        return sys_get_temp_dir();
    }
    
    /**
     * Get process manager
     * 
     * @return ProcessManager
     */
    public function getProcessManager(): ProcessManager
    {
        return $this->processManager;
    }
}
