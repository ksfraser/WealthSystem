<?php
/**
 * CsvHandler: Centralized CSV operations for the entire application
 * 
 * @startuml CsvHandler_Class_Diagram
 * !define RECTANGLE class
 * 
 * class CsvHandler {
 *   - errors : array
 *   --
 *   + read(csvPath : string) : array
 *   + write(csvPath : string, data : array) : bool
 *   + append(csvPath : string, data : array) : bool
 *   + validate(csvPath : string, expectedColumns : array) : bool
 *   + getErrors() : array
 *   + hasErrors() : bool
 *   + clearErrors() : void
 *   - logError(message : string) : void
 * }
 * 
 * note right of CsvHandler : Centralized CSV operations\nwith comprehensive\nerror handling and\nvalidation
 * @enduml
 * 
 * @startuml CsvHandler_Activity_Read
 * start
 * :Read CSV Request;
 * :Clear previous errors;
 * 
 * if (File exists?) then (no)
 *   :Log "file not found" error;
 *   :Return empty array;
 *   stop
 * endif
 * 
 * if (File readable?) then (no)
 *   :Log "not readable" error;
 *   :Return empty array;
 *   stop
 * endif
 * 
 * :Open file handle;
 * if (Handle opened?) then (no)
 *   :Log "cannot open" error;
 *   :Return empty array;
 *   stop
 * endif
 * 
 * :Read header row;
 * if (Header exists?) then (no)
 *   :Log "no header" error;
 *   :Close file;
 *   :Return empty array;
 *   stop
 * endif
 * 
 * :Initialize data array;
 * while (More rows?) is (yes)
 *   :Read row;
 *   if (Row column count matches header?) then (no)
 *     :Log column mismatch error;
 *   else (yes)
 *     :Combine with header to create associative array;
 *     :Add to data array;
 *   endif
 * endwhile (no)
 * 
 * :Close file;
 * :Return data array;
 * stop
 * @enduml
 * 
 * @startuml CsvHandler_Activity_Write
 * start
 * :Write CSV Request;
 * :Clear previous errors;
 * 
 * if (Data provided?) then (no)
 *   :Log "no data" error;
 *   :Return false;
 *   stop
 * endif
 * 
 * :Ensure directory exists;
 * if (Directory created/exists?) then (no)
 *   :Log directory error;
 *   :Return false;
 *   stop
 * endif
 * 
 * :Open file for writing;
 * if (File opened?) then (no)
 *   :Log "cannot open" error;
 *   :Return false;
 *   stop
 * endif
 * 
 * :Extract header from first row;
 * :Write header to file;
 * if (Header write successful?) then (no)
 *   :Log header write error;
 *   :Close file;
 *   :Return false;
 *   stop
 * endif
 * 
 * while (More data rows?) is (yes)
 *   :Write row to file;
 *   if (Row write successful?) then (no)
 *     :Log row write error;
 *     :Close file;
 *     :Return false;
 *     stop
 *   endif
 * endwhile (no)
 * 
 * :Close file;
 * :Return true;
 * stop
 * @enduml
 * 
 * Handles all CSV reading and writing with error handling and validation.
 * Provides a centralized, robust interface for CSV operations across the application.
 * 
 * Key Features:
 * - Comprehensive error handling with detailed error messages
 * - File existence and permissions validation
 * - Header/data consistency checking
 * - Directory auto-creation for write operations
 * - Support for read, write, and append operations
 * - Column validation against expected schemas
 * 
 * Design Patterns:
 * - Facade: Simplifies complex CSV operations
 * - Error Collector: Accumulates errors for batch reporting
 * 
 * Error Handling Philosophy:
 * - Fail gracefully with informative error messages
 * - Continue processing when possible (e.g., skip malformed rows)
 * - Provide detailed context in error messages (file paths, line numbers)
 */
class CsvHandler {
    private $errors = [];
    
    /**
     * Read CSV file and return associative array
     */
    public function read($csvPath) {
        $this->errors = [];
        
        if (!file_exists($csvPath)) {
            $this->logError("CSV file not found: $csvPath");
            return [];
        }
        
        if (!is_readable($csvPath)) {
            $this->logError("CSV file not readable: $csvPath");
            return [];
        }
        
        try {
            $handle = fopen($csvPath, 'r');
            if (!$handle) {
                $this->logError("Cannot open CSV file: $csvPath");
                return [];
            }
            
            $header = fgetcsv($handle);
            if (!$header) {
                $this->logError("CSV file has no header: $csvPath");
                fclose($handle);
                return [];
            }
            
            $data = [];
            $lineNumber = 2; // Start from line 2 (after header)
            
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) !== count($header)) {
                    $this->logError("Line $lineNumber has " . count($row) . " columns, expected " . count($header) . " in $csvPath");
                    $lineNumber++;
                    continue;
                }
                
                $data[] = array_combine($header, $row);
                $lineNumber++;
            }
            
            fclose($handle);
            return $data;
            
        } catch (Exception $e) {
            $this->logError('CSV read failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Write data to CSV file
     */
    public function write($csvPath, $data) {
        $this->errors = [];
        
        if (empty($data)) {
            $this->logError("No data provided for CSV write");
            return false;
        }
        
        try {
            // Ensure directory exists
            $dir = dirname($csvPath);
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    $this->logError("Cannot create directory: $dir");
                    return false;
                }
            }
            
            $handle = fopen($csvPath, 'w');
            if (!$handle) {
                $this->logError("Cannot open CSV file for writing: $csvPath");
                return false;
            }
            
            // Write header
            $header = array_keys($data[0]);
            if (!fputcsv($handle, $header)) {
                $this->logError("Failed to write CSV header to: $csvPath");
                fclose($handle);
                return false;
            }
            
            // Write data rows
            foreach ($data as $row) {
                if (!fputcsv($handle, $row)) {
                    $this->logError("Failed to write CSV row to: $csvPath");
                    fclose($handle);
                    return false;
                }
            }
            
            fclose($handle);
            return true;
            
        } catch (Exception $e) {
            $this->logError('CSV write failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Append data to existing CSV file
     */
    public function append($csvPath, $data) {
        $this->errors = [];
        
        if (empty($data)) {
            $this->logError("No data provided for CSV append");
            return false;
        }
        
        try {
            // If file doesn't exist, create it with header
            if (!file_exists($csvPath)) {
                return $this->write($csvPath, $data);
            }
            
            $handle = fopen($csvPath, 'a');
            if (!$handle) {
                $this->logError("Cannot open CSV file for appending: $csvPath");
                return false;
            }
            
            // Write data rows (no header needed for append)
            foreach ($data as $row) {
                if (!fputcsv($handle, $row)) {
                    $this->logError("Failed to append CSV row to: $csvPath");
                    fclose($handle);
                    return false;
                }
            }
            
            fclose($handle);
            return true;
            
        } catch (Exception $e) {
            $this->logError('CSV append failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate CSV file structure
     */
    public function validate($csvPath, $expectedColumns = null) {
        $this->errors = [];
        
        if (!file_exists($csvPath)) {
            $this->logError("CSV file not found: $csvPath");
            return false;
        }
        
        try {
            $handle = fopen($csvPath, 'r');
            if (!$handle) {
                $this->logError("Cannot open CSV file: $csvPath");
                return false;
            }
            
            $header = fgetcsv($handle);
            if (!$header) {
                $this->logError("CSV file has no header: $csvPath");
                fclose($handle);
                return false;
            }
            
            // Check expected columns if provided
            if ($expectedColumns !== null) {
                $missing = array_diff($expectedColumns, $header);
                if (!empty($missing)) {
                    $this->logError("CSV missing expected columns: " . implode(', ', $missing));
                    fclose($handle);
                    return false;
                }
            }
            
            fclose($handle);
            return true;
            
        } catch (Exception $e) {
            $this->logError('CSV validation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get errors from last operation
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Check if last operation had errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Clear errors
     */
    public function clearErrors() {
        $this->errors = [];
    }
    
    private function logError($message) {
        $this->errors[] = $message;
    }
}
