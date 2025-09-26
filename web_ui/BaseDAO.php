<?php
/**
 * BaseDAO: Abstract base class for all Data Access Objects
 * 
 * @startuml BaseDAO_Class_Diagram
 * !define RECTANGLE class
 * 
 * abstract class BaseDAO {
 *   # sessionManager : SessionManager
 *   # csvHandler : CsvHandler
 *   # componentName : string
 *   # sessionKey : string
 *   --
 *   + __construct(componentName : string)
 *   # logError(message : string) : void
 *   + getErrors() : array
 *   + clearErrors() : void
 *   + hasErrors() : bool
 *   # setRetryData(data : mixed) : void
 *   + getRetryData() : mixed
 *   + clearRetryData() : void
 *   # readCsv(csvPath : string) : array
 *   # writeCsv(csvPath : string, data : array) : bool
 *   # appendCsv(csvPath : string, data : array) : bool
 *   # validateCsv(csvPath : string, expectedColumns : array) : bool
 *   # findExistingFile(paths : array) : string|null
 *   # handleOperationResult(success : bool, data : mixed) : bool
 * }
 * 
 * class SessionManager {
 *   - instance : SessionManager
 *   - sessionStarted : bool
 *   - initializationError : string
 *   --
 *   + getInstance() : SessionManager
 *   + isSessionActive() : bool
 *   + setRetryData(key : string, data : mixed) : bool
 *   + getRetryData(key : string) : mixed
 *   + clearRetryData(key : string) : bool
 *   + addError(component : string, error : string) : bool
 *   + getErrors(component : string) : array
 *   + clearErrors(component : string) : bool
 * }
 * 
 * class CsvHandler {
 *   - errors : array
 *   --
 *   + read(csvPath : string) : array
 *   + write(csvPath : string, data : array) : bool
 *   + append(csvPath : string, data : array) : bool
 *   + validate(csvPath : string, expectedColumns : array) : bool
 *   + getErrors() : array
 * }
 * 
 * BaseDAO --> SessionManager : uses
 * BaseDAO --> CsvHandler : uses
 * 
 * note right of BaseDAO : Provides base functionality\nfor all DAO classes
 * note right of SessionManager : Singleton pattern\nfor session management
 * note right of CsvHandler : Centralized CSV operations\nwith error handling
 * @enduml
 * 
 * @startuml BaseDAO_Workflow
 * !define RECTANGLE class
 * 
 * participant "Client" as C
 * participant "ConcreteDAO" as DAO
 * participant "BaseDAO" as BASE
 * participant "SessionManager" as SM
 * participant "CsvHandler" as CSV
 * 
 * C -> DAO: operation()
 * activate DAO
 * DAO -> BASE: readCsv(path)
 * activate BASE
 * BASE -> CSV: read(path)
 * activate CSV
 * CSV -> CSV: validate file
 * CSV --> BASE: data / errors
 * deactivate CSV
 * BASE -> BASE: transfer errors
 * BASE -> SM: addError() if needed
 * BASE --> DAO: result
 * deactivate BASE
 * 
 * alt operation fails
 *   DAO -> BASE: setRetryData(data)
 *   BASE -> SM: setRetryData(key, data)
 * else operation succeeds
 *   DAO -> BASE: clearRetryData()
 *   BASE -> SM: clearRetryData(key)
 * end
 * 
 * DAO --> C: result
 * deactivate DAO
 * @enduml
 * 
 * Provides common functionality for error handling, session management, and basic operations.
 * This abstract base class implements the Template Method pattern for DAO operations.
 * 
 * Key Features:
 * - Centralized error handling with session persistence
 * - Retry mechanism for failed operations
 * - Standardized CSV operations with validation
 * - Component-based error tracking
 * 
 * Dependencies:
 * - SessionManager (Singleton): Session-based data persistence
 * - CsvHandler: Centralized CSV file operations
 * 
 * Design Patterns:
 * - Abstract Factory: Base for concrete DAO implementations
 * - Template Method: Common operation workflows
 * - Dependency Injection: SessionManager and CsvHandler composition
 */

require_once __DIR__ . '/SessionManager.php';
require_once __DIR__ . '/CsvHandler.php';

abstract class BaseDAO {
    protected $sessionManager;
    protected $csvHandler;
    protected $componentName;
    protected $sessionKey;
    
    public function __construct($componentName) {
        $this->componentName = $componentName;
        $this->sessionKey = $componentName . '_retry';
        $this->sessionManager = SessionManager::getInstance();
        $this->csvHandler = new CsvHandler();
    }
    
    /**
     * Log an error for this component
     */
    protected function logError($message) {
        $this->sessionManager->addError($this->componentName, $message);
    }
    
    /**
     * Get all errors for this component
     */
    public function getErrors() {
        return $this->sessionManager->getErrors($this->componentName);
    }
    
    /**
     * Clear errors for this component
     */
    public function clearErrors() {
        $this->sessionManager->clearErrors($this->componentName);
    }
    
    /**
     * Check if there are any errors
     */
    public function hasErrors() {
        return !empty($this->getErrors());
    }
    
    /**
     * Store retry data for failed operations
     */
    protected function setRetryData($data) {
        $this->sessionManager->setRetryData($this->sessionKey, $data);
    }
    
    /**
     * Get retry data for failed operations
     */
    public function getRetryData() {
        return $this->sessionManager->getRetryData($this->sessionKey);
    }
    
    /**
     * Clear retry data after successful retry
     */
    public function clearRetryData() {
        $this->sessionManager->clearRetryData($this->sessionKey);
    }
    
    /**
     * Read CSV file using centralized CSV handler
     */
    protected function readCsv($csvPath) {
        $data = $this->csvHandler->read($csvPath);
        
        // Transfer CSV errors to component errors
        foreach ($this->csvHandler->getErrors() as $error) {
            $this->logError($error);
        }
        
        return $data;
    }
    
    /**
     * Write CSV file using centralized CSV handler
     */
    protected function writeCsv($csvPath, $data) {
        $success = $this->csvHandler->write($csvPath, $data);
        
        // Transfer CSV errors to component errors
        foreach ($this->csvHandler->getErrors() as $error) {
            $this->logError($error);
        }
        
        return $success;
    }
    
    /**
     * Append to CSV file using centralized CSV handler
     */
    protected function appendCsv($csvPath, $data) {
        $success = $this->csvHandler->append($csvPath, $data);
        
        // Transfer CSV errors to component errors
        foreach ($this->csvHandler->getErrors() as $error) {
            $this->logError($error);
        }
        
        return $success;
    }
    
    /**
     * Validate CSV file structure
     */
    protected function validateCsv($csvPath, $expectedColumns = null) {
        $valid = $this->csvHandler->validate($csvPath, $expectedColumns);
        
        // Transfer CSV errors to component errors
        foreach ($this->csvHandler->getErrors() as $error) {
            $this->logError($error);
        }
        
        return $valid;
    }
    
    /**
     * Find the first existing file from a list of paths
     */
    protected function findExistingFile($paths) {
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        return null;
    }
    
    /**
     * Handle operation result with retry logic
     */
    protected function handleOperationResult($success, $data = null) {
        if (!$success && $data !== null) {
            $this->setRetryData($data);
        } elseif ($success) {
            $this->clearRetryData();
        }
        
        return $success;
    }
}
