<?php

namespace App\Security;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

/**
 * ProcessManager - Secure Python script execution
 * 
 * Provides secure execution of Python scripts with:
 * - Command whitelist enforcement
 * - Command injection prevention
 * - Path traversal prevention
 * - Timeout enforcement
 * - Output capture with error handling
 * 
 * Uses Symfony Process component for secure process execution.
 * 
 * @package App\Security
 * @author Stock Analysis System
 * @version 1.0.0
 * 
 * Security Features:
 * - Only whitelisted scripts can be executed
 * - All paths are validated against directory traversal
 * - Command injection patterns are blocked
 * - Process timeout enforced (default 60s)
 * - Output and errors captured separately
 * 
 * Usage Example:
 * ```php
 * $pm = new ProcessManager('/path/to/scripts');
 * 
 * // Check Python availability
 * if ($pm->isPythonAvailable()) {
 *     echo $pm->getPythonVersion();
 * }
 * 
 * // Execute whitelisted script
 * $result = $pm->executePythonScript('trading_script.py', ['--market-cap' => 'micro']);
 * if ($result['success']) {
 *     echo $result['output'];
 * } else {
 *     error_log($result['error']);
 * }
 * ```
 * 
 * @see https://symfony.com/doc/current/components/process.html
 */
class ProcessManager
{
    /**
     * Whitelist of allowed Python scripts
     * 
     * Only scripts in this list can be executed. This prevents
     * arbitrary code execution and limits attack surface.
     * 
     * @var array<string>
     */
    private const SCRIPT_WHITELIST = [
        'trading_script.py',
        'simple_automation.py',
        'enhanced_automation.py',
        'Generate_Graph.py'
    ];
    
    /**
     * Default timeout for script execution (seconds)
     * 
     * @var int
     */
    private const DEFAULT_TIMEOUT = 60;
    
    /**
     * Base directory for script execution
     * 
     * All script paths are resolved relative to this directory.
     * 
     * @var string
     */
    private string $baseDir;
    
    /**
     * Python executable path (null = auto-detect)
     * 
     * @var string|null
     */
    private ?string $pythonPath;
    
    /**
     * Constructor
     * 
     * @param string|null $baseDir Base directory for scripts (default: project root)
     * @param string|null $pythonPath Python executable path (default: auto-detect)
     */
    public function __construct(?string $baseDir = null, ?string $pythonPath = null)
    {
        $this->baseDir = $baseDir ?? dirname(__DIR__, 2);
        $this->pythonPath = $pythonPath;
    }
    
    /**
     * Check if Python is available on the system
     * 
     * Attempts to run 'python --version' to verify Python installation.
     * 
     * @return bool True if Python is available, false otherwise
     */
    public function isPythonAvailable(): bool
    {
        try {
            $pythonCmd = $this->pythonPath ?? 'python';
            $process = new Process([$pythonCmd, '--version']);
            $process->setTimeout(5);
            $process->run();
            
            return $process->isSuccessful();
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get Python version string
     * 
     * Returns the output of 'python --version'.
     * 
     * @return string Python version string, or error message if unavailable
     */
    public function getPythonVersion(): string
    {
        if (!$this->isPythonAvailable()) {
            return 'Python not available';
        }
        
        try {
            $pythonCmd = $this->pythonPath ?? 'python';
            $process = new Process([$pythonCmd, '--version']);
            $process->setTimeout(5);
            $process->run();
            
            // Python 2 outputs to stderr, Python 3 to stdout
            $output = trim($process->getOutput() ?: $process->getErrorOutput());
            
            return $output ?: 'Unknown version';
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
    
    /**
     * Execute a Python script securely
     * 
     * Only scripts in the whitelist can be executed. Paths are validated
     * against directory traversal. Arguments are properly escaped.
     * 
     * @param string $scriptName Name of the script (must be in whitelist)
     * @param array $args Arguments to pass to script (optional)
     * @param int|null $timeout Timeout in seconds (default: 60)
     * 
     * @return array{
     *   success: bool,
     *   output: string,
     *   error: string,
     *   exit_code?: int
     * } Execution result
     * 
     * Result Structure:
     * - success: true if script executed successfully (exit code 0)
     * - output: stdout from the script
     * - error: stderr from the script, or error message
     * - exit_code: process exit code (if available)
     * 
     * @throws \InvalidArgumentException If script name is invalid
     */
    public function executePythonScript(
        string $scriptName,
        array $args = [],
        ?int $timeout = null
    ): array {
        // Validate script name against whitelist
        if (!$this->isScriptWhitelisted($scriptName)) {
            return [
                'success' => false,
                'output' => '',
                'error' => "Script '{$scriptName}' is not whitelisted for execution"
            ];
        }
        
        // Prevent directory traversal
        if ($this->containsDirectoryTraversal($scriptName)) {
            return [
                'success' => false,
                'output' => '',
                'error' => "Script path contains directory traversal characters"
            ];
        }
        
        // Construct full script path
        $scriptPath = $this->baseDir . DIRECTORY_SEPARATOR . $scriptName;
        
        // Check if script exists
        if (!file_exists($scriptPath)) {
            return [
                'success' => false,
                'output' => '',
                'error' => "Script not found: {$scriptName}"
            ];
        }
        
        // Check Python availability
        if (!$this->isPythonAvailable()) {
            return [
                'success' => false,
                'output' => '',
                'error' => 'Python is not available on this system'
            ];
        }
        
        try {
            // Build command array
            $pythonCmd = $this->pythonPath ?? 'python';
            $command = [$pythonCmd, $scriptPath];
            
            // Add arguments (properly escaped by Symfony Process)
            $command = array_merge($command, $this->buildArguments($args));
            
            // Create and configure process
            $process = new Process($command);
            $process->setTimeout($timeout ?? self::DEFAULT_TIMEOUT);
            $process->setWorkingDirectory($this->baseDir);
            
            // Execute
            $process->run();
            
            return [
                'success' => $process->isSuccessful(),
                'output' => trim($process->getOutput()),
                'error' => trim($process->getErrorOutput()),
                'exit_code' => $process->getExitCode()
            ];
            
        } catch (ProcessTimedOutException $e) {
            return [
                'success' => false,
                'output' => '',
                'error' => 'Script execution timed out after ' . ($timeout ?? self::DEFAULT_TIMEOUT) . ' seconds',
                'exit_code' => -1
            ];
            
        } catch (ProcessFailedException $e) {
            return [
                'success' => false,
                'output' => '',
                'error' => 'Process failed: ' . $e->getMessage(),
                'exit_code' => $e->getProcess()->getExitCode()
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => '',
                'error' => 'Execution error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if script is in whitelist
     * 
     * Performs case-sensitive comparison against whitelist.
     * 
     * @param string $scriptName Script name to check
     * @return bool True if whitelisted, false otherwise
     */
    private function isScriptWhitelisted(string $scriptName): bool
    {
        // Extract just the filename (in case path is provided)
        $basename = basename($scriptName);
        
        return in_array($basename, self::SCRIPT_WHITELIST, true);
    }
    
    /**
     * Check if path contains directory traversal patterns
     * 
     * Detects attempts to access parent directories or absolute paths.
     * 
     * Patterns checked:
     * - ../ (parent directory)
     * - ..\\ (Windows parent directory)
     * - / at start (absolute path on Unix)
     * - C: or similar (Windows drive letter)
     * 
     * @param string $path Path to check
     * @return bool True if traversal detected, false otherwise
     */
    private function containsDirectoryTraversal(string $path): bool
    {
        // Check for common traversal patterns
        $dangerousPatterns = [
            '..',           // Parent directory
            chr(0),         // Null byte
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return true;
            }
        }
        
        // Check for absolute paths
        // Unix: starts with /
        // Windows: starts with drive letter (C:, D:, etc)
        if (
            $path[0] === '/' ||
            (strlen($path) > 1 && $path[1] === ':')
        ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Build command-line arguments from array
     * 
     * Converts associative or indexed arrays to command-line arguments.
     * 
     * Formats:
     * - ['arg1', 'arg2'] → ['arg1', 'arg2']
     * - ['--flag' => 'value'] → ['--flag', 'value']
     * - ['key' => 'value'] → ['--key', 'value']
     * 
     * Symfony Process handles proper escaping automatically.
     * 
     * @param array $args Arguments array
     * @return array<string> Command-line arguments
     */
    private function buildArguments(array $args): array
    {
        $result = [];
        
        foreach ($args as $key => $value) {
            if (is_int($key)) {
                // Positional argument
                $result[] = (string) $value;
            } else {
                // Named argument
                // Add -- prefix if not present
                $flag = str_starts_with($key, '-') ? $key : '--' . $key;
                $result[] = $flag;
                $result[] = (string) $value;
            }
        }
        
        return $result;
    }
}
