<?php
/**
 * Backward Compatibility Wrapper for SessionManager
 * This ensures existing pages like index.php continue to work
 * while new pages can use the Symfony-enhanced version
 */

// Include the new Symfony-based SessionManager
require_once __DIR__ . '/bootstrap_symfony.php';

/**
 * Non-namespaced SessionManager for backward compatibility
 * Wraps the new App\Core\SessionManager but maintains old interface
 */
class SessionManager {
    private static $instance = null;
    private $symfonySessionManager = null;
    
    private function __construct() {
        // Use the new Symfony-based session manager internally
        $this->symfonySessionManager = \App\Core\SessionManager::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Backward compatibility methods - delegate to Symfony session manager
    
    public function isSessionActive() {
        return $this->symfonySessionManager->isSessionActive();
    }
    
    public function getInitializationError() {
        return $this->symfonySessionManager->getInitializationError();
    }
    
    public function set($key, $value) {
        $this->symfonySessionManager->set($key, $value);
        return true; // Old interface expected boolean
    }
    
    public function get($key, $default = null) {
        return $this->symfonySessionManager->get($key, $default);
    }
    
    public function remove($key) {
        $this->symfonySessionManager->remove($key);
        return true; // Old interface expected boolean
    }
    
    public function has($key) {
        return $this->symfonySessionManager->has($key);
    }
    
    public function destroy() {
        return $this->symfonySessionManager->destroy();
    }
    
    public function getId() {
        return $this->symfonySessionManager->getId();
    }
    
    public function getName() {
        return $this->symfonySessionManager->getName();
    }
    
    // Legacy methods that the old dashboard might expect
    
    public function canUseSession() {
        return $this->symfonySessionManager->isSessionActive();
    }
    
    // Error handling methods - matching old interface
    public function addError($component, $error) {
        $errors = $this->get('errors', []);
        if (!isset($errors[$component])) {
            $errors[$component] = [];
        }
        $errors[$component][] = $error;
        $this->set('errors', $errors);
    }
    
    public function getErrors($component) {
        $errors = $this->get('errors', []);
        return $errors[$component] ?? [];
    }
    
    public function clearErrors($component) {
        $errors = $this->get('errors', []);
        unset($errors[$component]);
        $this->set('errors', $errors);
    }
    
    public function getAllErrors() {
        return $this->get('errors', []);
    }
    
    public function clearAllErrors() {
        $this->remove('errors');
        return true;
    }
    
    // Retry data methods - matching old interface
    public function setRetryData($key, $data) {
        $retryData = $this->get('retry_data', []);
        $retryData[$key] = $data;
        $this->set('retry_data', $retryData);
    }
    
    public function getRetryData($key) {
        $retryData = $this->get('retry_data', []);
        return $retryData[$key] ?? null;
    }
    
    public function clearRetryData($key) {
        $retryData = $this->get('retry_data', []);
        unset($retryData[$key]);
        $this->set('retry_data', $retryData);
    }
    
    // Direct session access (legacy compatibility)
    public function getSessionValue($key, $default = null) {
        return $this->get($key, $default);
    }
    
    public function setSessionValue($key, $value) {
        return $this->set($key, $value);
    }
}
