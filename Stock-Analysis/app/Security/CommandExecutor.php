<?php

namespace App\Security;

/**
 * Command Executor
 * 
 * Provides secure command execution with input validation and sandboxing.
 * Prevents command injection attacks.
 */
class CommandExecutor
{
    // Whitelisted commands
    private const ALLOWED_COMMANDS = [
        'python' => [
            'executable' => 'python',
            'baseDir' => null, // Set in constructor
            'allowedScripts' => [
                'enhanced_automation.py',
                'simple_automation.py',
                'database_architect.py',
                'trading_script.py'
            ]
        ]
    ];
    
    private string $baseDir;
    private array $config;
    
    public function __construct(?string $baseDir = null)
    {
        $this->baseDir = $baseDir ?? dirname(__DIR__, 2);
        $this->config = self::ALLOWED_COMMANDS;
        $this->config['python']['baseDir'] = $this->baseDir;
    }
    
    /**
     * Execute a Python script securely
     * 
     * @param string $scriptName The script filename (must be whitelisted)
     * @param array $args Associative array of command arguments
     * @param int $timeout Maximum execution time in seconds
     * @return array ['success' => bool, 'output' => string, 'exit_code' => int]
     */
    public function executePythonScript(
        string $scriptName,
        array $args = [],
        int $timeout = 30
    ): array {
        // Validate script is whitelisted
        if (!$this->isScriptAllowed($scriptName)) {
            return [
                'success' => false,
                'output' => "Script '{$scriptName}' is not allowed",
                'exit_code' => 1,
                'error' => 'Script not whitelisted'
            ];
        }
        
        // Build script path securely
        $scriptPath = $this->buildSecurePath($scriptName);
        if (!file_exists($scriptPath)) {
            return [
                'success' => false,
                'output' => "Script not found: {$scriptName}",
                'exit_code' => 1,
                'error' => 'Script file not found'
            ];
        }
        
        // Build command with validated arguments
        $command = $this->buildPythonCommand($scriptPath, $args);
        
        // Execute with timeout
        return $this->executeCommand($command, $timeout);
    }
    
    /**
     * Check if script is in whitelist
     */
    private function isScriptAllowed(string $scriptName): bool
    {
        // Remove any path components
        $scriptName = basename($scriptName);
        
        return in_array(
            $scriptName,
            $this->config['python']['allowedScripts'],
            true
        );
    }
    
    /**
     * Build secure file path (prevents directory traversal)
     */
    private function buildSecurePath(string $scriptName): string
    {
        // Remove any path separators
        $scriptName = basename($scriptName);
        
        // Build full path
        $fullPath = $this->baseDir . DIRECTORY_SEPARATOR . $scriptName;
        
        // Resolve to absolute path and verify it's within base directory
        $realPath = realpath(dirname($fullPath));
        $realBase = realpath($this->baseDir);
        
        if ($realPath === false || $realBase === false) {
            throw new \RuntimeException('Invalid path');
        }
        
        if (strpos($realPath, $realBase) !== 0) {
            throw new \RuntimeException('Path traversal attempt detected');
        }
        
        return $fullPath;
    }
    
    /**
     * Build Python command with validated arguments
     */
    private function buildPythonCommand(string $scriptPath, array $args): string
    {
        $pythonExe = $this->findPythonExecutable();
        
        // Escape script path
        $escapedScript = escapeshellarg($scriptPath);
        
        // Build argument string
        $argString = '';
        foreach ($args as $key => $value) {
            if (is_int($key)) {
                // Positional argument
                $argString .= ' ' . escapeshellarg($value);
            } else {
                // Named argument
                $key = $this->validateArgumentName($key);
                $argString .= ' --' . $key . ' ' . escapeshellarg($value);
            }
        }
        
        return $pythonExe . ' ' . $escapedScript . $argString;
    }
    
    /**
     * Find Python executable
     */
    private function findPythonExecutable(): string
    {
        // Try common Python locations
        $possiblePaths = [
            'python',
            'python3',
            '/usr/bin/python3',
            '/usr/local/bin/python3',
            'C:\\Python311\\python.exe',
            'C:\\Python310\\python.exe',
            'C:\\Program Files\\Python311\\python.exe'
        ];
        
        foreach ($possiblePaths as $path) {
            $output = [];
            $returnVar = 0;
            @exec($path . ' --version 2>&1', $output, $returnVar);
            
            if ($returnVar === 0 && !empty($output)) {
                return $path;
            }
        }
        
        // Default to 'python'
        return 'python';
    }
    
    /**
     * Validate argument name (prevent injection)
     */
    private function validateArgumentName(string $name): string
    {
        // Only allow alphanumeric and underscores
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException('Invalid argument name');
        }
        
        return $name;
    }
    
    /**
     * Execute command with timeout
     */
    private function executeCommand(string $command, int $timeout): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w']   // stderr
        ];
        
        $process = proc_open($command, $descriptors, $pipes);
        
        if (!is_resource($process)) {
            return [
                'success' => false,
                'output' => 'Failed to start process',
                'exit_code' => 1,
                'error' => 'Process creation failed'
            ];
        }
        
        // Close stdin
        fclose($pipes[0]);
        
        // Set non-blocking mode
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        
        // Read output with timeout
        $output = '';
        $error = '';
        $startTime = time();
        
        while (time() - $startTime < $timeout) {
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            
            $output .= stream_get_contents($pipes[1]);
            $error .= stream_get_contents($pipes[2]);
            
            usleep(100000); // 100ms
        }
        
        // Get final output
        $output .= stream_get_contents($pipes[1]);
        $error .= stream_get_contents($pipes[2]);
        
        // Close pipes
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        // Check if process is still running (timeout)
        $status = proc_get_status($process);
        if ($status['running']) {
            proc_terminate($process, 9); // SIGKILL
            proc_close($process);
            
            return [
                'success' => false,
                'output' => $output,
                'exit_code' => 124,
                'error' => 'Command timed out after ' . $timeout . ' seconds'
            ];
        }
        
        // Get exit code
        $exitCode = proc_close($process);
        
        return [
            'success' => $exitCode === 0,
            'output' => $output,
            'error_output' => $error,
            'exit_code' => $exitCode
        ];
    }
    
    /**
     * Execute Python code snippet (for simple operations)
     * WARNING: Only use with hardcoded, trusted code
     */
    public function executePythonCode(string $code, int $timeout = 10): array
    {
        // Validate code doesn't contain dangerous patterns
        $dangerousPatterns = [
            '/import\s+os/',
            '/import\s+subprocess/',
            '/import\s+sys/',
            '/__import__/',
            '/exec\s*\(/',
            '/eval\s*\(/',
            '/open\s*\(/',
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $code)) {
                return [
                    'success' => false,
                    'output' => 'Code contains dangerous operations',
                    'exit_code' => 1,
                    'error' => 'Dangerous code pattern detected'
                ];
            }
        }
        
        // Execute code with -c flag
        $pythonExe = $this->findPythonExecutable();
        $command = $pythonExe . ' -c ' . escapeshellarg($code);
        
        return $this->executeCommand($command, $timeout);
    }
    
    /**
     * Check if Python is available
     */
    public function isPythonAvailable(): bool
    {
        $result = $this->executeCommand($this->findPythonExecutable() . ' --version', 5);
        return $result['success'];
    }
    
    /**
     * Get Python version
     */
    public function getPythonVersion(): ?string
    {
        $result = $this->executeCommand($this->findPythonExecutable() . ' --version', 5);
        
        if ($result['success']) {
            return trim($result['output']);
        }
        
        return null;
    }
}
