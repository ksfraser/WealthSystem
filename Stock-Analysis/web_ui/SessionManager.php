<?php
/**
 * SessionManager: Centralized session management for the entire application
 * 
 * @startuml SessionManager_Class_Diagram
 * !define RECTANGLE class
 * 
 * class SessionManager {
 *   - {static} instance : SessionManager
 *   - {static} sessionStarted : bool
 *   - {static} initializationError : string
 *   --
 *   - __construct()
 *   - initializeSession() : void
 *   - ensureSessionPath() : void
 *   - createSessionPath(path : string) : bool
 *   - canUseSession() : bool
 *   --
 *   + {static} getInstance() : SessionManager
 *   + isSessionActive() : bool
 *   + getInitializationError() : string|null
 *   + setRetryData(key : string, data : mixed) : bool
 *   + getRetryData(key : string) : mixed|null
 *   + clearRetryData(key : string) : bool
 *   + addError(component : string, error : string) : bool
 *   + getErrors(component : string) : array
 *   + clearErrors(component : string) : bool
 *   + getAllErrors() : array
 *   + clearAllErrors() : bool
 *   + set(key : string, value : mixed) : bool
 *   + get(key : string, default : mixed) : mixed
 *   + has(key : string) : bool
 *   + remove(key : string) : bool
 *   + destroy() : void
 * }
 * 
 * note right of SessionManager : Singleton Pattern\nManages PHP sessions\nwith error handling\nand retry mechanisms
 * 
 * SessionManager --> SessionManager : creates singleton instance
 * @enduml
 * 
 * @startuml SessionManager_State_Diagram
 * [*] --> NotInitialized : new SessionManager()
 * 
 * NotInitialized --> InitializingPath : ensureSessionPath()
 * InitializingPath --> PathCreated : path exists/created
 * InitializingPath --> PathFailed : path creation failed
 * 
 * PathCreated --> SessionStarting : session_start()
 * PathFailed --> SessionStarting : fallback to temp
 * 
 * SessionStarting --> Active : session started successfully
 * SessionStarting --> Failed : headers sent / other error
 * 
 * Active --> Active : normal operations
 * Failed --> Failed : operations return false/null
 * 
 * Active --> [*] : destroy()
 * Failed --> [*] : destroy()
 * @enduml
 * 
 * @startuml SessionManager_Sequence
 * participant "Client" as C
 * participant "SessionManager" as SM
 * participant "PHP Session" as PS
 * participant "Filesystem" as FS
 * 
 * C -> SM: getInstance()
 * activate SM
 * SM -> SM: __construct() (first time only)
 * SM -> SM: initializeSession()
 * SM -> SM: ensureSessionPath()
 * SM -> FS: check session_save_path()
 * FS --> SM: path status
 * 
 * alt path doesn't exist
 *   SM -> FS: mkdir(path, 0755, true)
 *   FS --> SM: success/failure
 * end
 * 
 * SM -> PS: session_start()
 * PS --> SM: success/failure
 * SM --> C: SessionManager instance
 * deactivate SM
 * 
 * C -> SM: setRetryData(key, data)
 * SM -> PS: $_SESSION['retry_data'][key] = data
 * 
 * C -> SM: addError(component, error)
 * SM -> PS: $_SESSION['errors'][component][] = error
 * @enduml
 * 
 * Handles all session operations, retry data storage, and session-based error tracking.
 * Implements the Singleton pattern to ensure consistent session management across the application.
 * 
 * Key Features:
 * - Singleton pattern for global session management
 * - Automatic session path creation and validation
 * - Header-safe session initialization
 * - Component-based error tracking with session persistence
 * - Retry data storage for failed operations
 * - Fallback mechanisms for different environments (CLI, web)
 * 
 * Design Patterns:
 * - Singleton: Ensures single instance across application
 * - Template Method: Standardized session operation patterns
 * - Strategy: Different session path creation strategies
 * 
 * Error Handling:
 * - Graceful degradation when sessions can't be started
 * - Automatic fallback session paths
 * - Environment-aware error logging (CLI vs web)
 */

class SessionManager {
    private static $instance = null;
    private static $sessionStarted = false;
    private static $initializationError = null;
    
    private function __construct() {
        $this->initializeSession();
    }
    
    /**
     * Ensure session save path exists and is writable
     */
    private function ensureSessionPath() {
        // Only modify session settings if headers haven't been sent
        if (headers_sent()) {
            return;
        }
        
        $currentPath = session_save_path();
        
        // If no path is set or path doesn't exist, set a safe default
        if (empty($currentPath) || !is_dir($currentPath)) {
            // Try multiple possible paths in order of preference
            $possiblePaths = [
                sys_get_temp_dir() . '/sessions',  // System temp + sessions subdirectory
                __DIR__ . '/../tmp/sessions',      // Application tmp directory
                __DIR__ . '/sessions',             // Sessions in web_ui directory
                sys_get_temp_dir()                 // Fall back to system temp
            ];
            
            foreach ($possiblePaths as $path) {
                if ($this->createSessionPath($path)) {
                    session_save_path($path);
                    break;
                }
            }
        }
    }
    
    /**
     * Create and set permissions for session path
     */
    private function createSessionPath($path) {
        try {
            if (!is_dir($path)) {
                if (!mkdir($path, 0755, true)) {
                    return false;
                }
            }
            
            // Check if path is writable
            if (!is_writable($path)) {
                // Try to make it writable
                chmod($path, 0755);
                if (!is_writable($path)) {
                    return false;
                }
            }
            
            return true;
        } catch (\Exception $e) {
            // Log error but don't fail initialization
            if (php_sapi_name() !== 'cli') {
                error_log('SessionManager: Failed to create session path ' . $path . ': ' . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Initialize session safely with header checking
     */
    private function initializeSession() {
        try {
            // Ensure session save path exists and is writable
            $this->ensureSessionPath();
            
            if (session_status() === PHP_SESSION_NONE) {
                if (!headers_sent($file, $line)) {
                    session_start();
                    self::$sessionStarted = true;
                } else {
                    // Headers already sent - log but don't fail
                    $error = "Cannot start session - headers already sent in file $file on line $line";
                    self::$initializationError = $error;
                    
                    // Only log error in web context, not CLI
                    if (php_sapi_name() !== 'cli') {
                        error_log('SessionManager: ' . $error);
                    }
                    
                    self::$sessionStarted = false;
                }
            } else {
                // Session already started elsewhere
                self::$sessionStarted = true;
            }
        } catch (\Exception $e) {
            self::$initializationError = 'Session initialization failed: ' . $e->getMessage();
            self::$sessionStarted = false;
            
            if (php_sapi_name() !== 'cli') {
                error_log('SessionManager: ' . self::$initializationError);
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Check if session is properly initialized
     */
    public function isSessionActive() {
        return self::$sessionStarted && session_status() === PHP_SESSION_ACTIVE;
    }
    
    /**
     * Get initialization error if any
     */
    public function getInitializationError() {
        return self::$initializationError;
    }
    
    /**
     * Check if we can use session safely
     */
    private function canUseSession() {
        return $this->isSessionActive();
    }
    
    /**
     * Store retry data for failed operations
     */
    public function setRetryData($key, $data) {
        if (!$this->canUseSession()) return false;
        $_SESSION['retry_data'][$key] = $data;
        return true;
    }
    
    /**
     * Get retry data for failed operations
     */
    public function getRetryData($key) {
        if (!$this->canUseSession()) return null;
        return $_SESSION['retry_data'][$key] ?? null;
    }
    
    /**
     * Clear retry data after successful retry
     */
    public function clearRetryData($key) {
        if (!$this->canUseSession()) return false;
        unset($_SESSION['retry_data'][$key]);
        return true;
    }
    
    /**
     * Store errors in session for display across requests
     */
    public function addError($component, $error) {
        if (!$this->canUseSession()) return false;
        $_SESSION['errors'][$component][] = $error;
        return true;
    }
    
    /**
     * Get errors for a component
     */
    public function getErrors($component) {
        if (!$this->canUseSession()) return [];
        return $_SESSION['errors'][$component] ?? [];
    }
    
    /**
     * Clear errors for a component
     */
    public function clearErrors($component) {
        if (!$this->canUseSession()) return false;
        unset($_SESSION['errors'][$component]);
        return true;
    }
    
    /**
     * Get all errors
     */
    public function getAllErrors() {
        if (!$this->canUseSession()) return [];
        return $_SESSION['errors'] ?? [];
    }
    
    /**
     * Clear all errors
     */
    public function clearAllErrors() {
        if (!$this->canUseSession()) return false;
        unset($_SESSION['errors']);
        return true;
    }
    
    /**
     * Store arbitrary session data
     */
    public function set($key, $value) {
        if (!$this->canUseSession()) return false;
        $_SESSION[$key] = $value;
        return true;
    }
    
    /**
     * Get arbitrary session data
     */
    public function get($key, $default = null) {
        if (!$this->canUseSession()) return $default;
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session key exists
     */
    public function has($key) {
        if (!$this->canUseSession()) return false;
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session key
     */
    public function remove($key) {
        if (!$this->canUseSession()) return false;
        unset($_SESSION[$key]);
        return true;
    }
    
    /**
     * Destroy entire session
     */
    public function destroy() {
        if ($this->canUseSession()) {
            session_destroy();
        }
        self::$instance = null;
        self::$sessionStarted = false;
    }
}
