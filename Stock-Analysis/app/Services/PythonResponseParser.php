<?php

namespace App\Services;

/**
 * PythonResponseParser - Parse and validate Python script responses
 * 
 * Responsible for:
 * - Parsing JSON responses from Python scripts
 * - Extracting JSON from mixed output (warnings, logs, etc.)
 * - Validating response structure
 * - Error extraction and formatting
 * 
 * Follows Single Responsibility Principle by focusing ONLY on
 * response parsing and validation, not execution or script management.
 * 
 * @package App\Services
 * @author Stock Analysis System
 * @version 1.0.0
 * 
 * Usage Example:
 * ```php
 * $parser = new PythonResponseParser();
 * 
 * // Parse JSON string
 * $result = $parser->parse('{"data": [1, 2, 3]}');
 * 
 * // Parse process manager result
 * $processResult = $executor->executeScript('script.py', 'func', []);
 * $parsed = $parser->parseProcessResult($processResult);
 * 
 * // Extract JSON from mixed output
 * $json = $parser->extractJson("Warning: deprecated\n{\"result\": \"ok\"}");
 * ```
 */
class PythonResponseParser
{
    /**
     * Parse JSON string to array
     * 
     * Decodes JSON and wraps result in standard format with success flag.
     * 
     * @param string $json JSON string to parse
     * 
     * @return array{success: bool, error?: string, ...} Parsed result
     */
    public function parse(string $json): array
    {
        // Check for empty input
        if (empty(trim($json))) {
            return [
                'success' => false,
                'error' => 'Response is empty'
            ];
        }
        
        // Decode JSON
        $decoded = json_decode($json, true);
        
        // Check for JSON errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response: ' . json_last_error_msg()
            ];
        }
        
        // Check if response contains error
        if (is_array($decoded) && isset($decoded['error'])) {
            return [
                'success' => false,
                'error' => $decoded['error']
            ];
        }
        
        // Wrap array results in 'data' key
        if (isset($decoded[0])) {
            // Numeric array - wrap in data
            return array_merge(['success' => true], ['data' => $decoded]);
        }
        
        // Associate array - merge with success flag
        return array_merge(['success' => true], $decoded);
    }
    
    /**
     * Parse result from ProcessManager execution
     * 
     * Extracts JSON from process output, filters warnings,
     * and returns parsed result.
     * 
     * @param array $processResult Result from ProcessManager::executePythonScript()
     * 
     * @return array{success: bool, error?: string, ...} Parsed result
     */
    public function parseProcessResult(array $processResult): array
    {
        // Check if process execution failed
        if (!$processResult['success']) {
            return [
                'success' => false,
                'error' => $processResult['error'] ?? 'Unknown error'
            ];
        }
        
        // Extract JSON from output (may contain warnings)
        $json = $this->extractJson($processResult['output']);
        
        if ($json === null) {
            return [
                'success' => false,
                'error' => 'No JSON found in output: ' . $processResult['output']
            ];
        }
        
        // Parse extracted JSON
        return $this->parse($json);
    }
    
    /**
     * Filter output to extract JSON lines
     * 
     * Removes warning lines, debug output, etc. and returns
     * only lines that appear to be JSON.
     * 
     * @param array $outputLines Lines of output
     * 
     * @return array<string> Filtered JSON lines
     */
    public function filterOutput(array $outputLines): array
    {
        $jsonLines = [];
        
        foreach ($outputLines as $line) {
            $line = trim($line);
            
            // Check if line starts with { or [ (likely JSON)
            if (!empty($line) && ($line[0] === '{' || $line[0] === '[')) {
                $jsonLines[] = $line;
            }
        }
        
        return $jsonLines;
    }
    
    /**
     * Extract JSON from mixed output
     * 
     * Finds the first JSON object or array in the output string.
     * Useful for extracting JSON from output that contains warnings,
     * debug messages, or other non-JSON content.
     * 
     * @param string $output Output string
     * 
     * @return string|null JSON string, or null if not found
     */
    public function extractJson(string $output): ?string
    {
        // Split into lines
        $lines = explode("\n", $output);
        
        // Filter to find JSON lines
        $jsonLines = $this->filterOutput($lines);
        
        // Return first JSON line
        return $jsonLines[0] ?? null;
    }
    
    /**
     * Check if string is valid JSON
     * 
     * @param string $string String to check
     * 
     * @return bool True if valid JSON, false otherwise
     */
    public function isJson(string $string): bool
    {
        if (empty($string)) {
            return false;
        }
        
        json_decode($string);
        
        return json_last_error() === JSON_ERROR_NONE;
    }
}
