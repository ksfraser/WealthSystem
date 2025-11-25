<?php
/**
 * Bootstrap with Symfony Session Integration
 * Simple, clean approach using existing libraries
 */

// Load Composer's autoloader for Symfony components
require_once __DIR__ . '/../vendor/autoload.php';

// Include our existing classes with proper paths
require_once __DIR__ . '/AuthExceptions.php';             // Existing auth exceptions
require_once __DIR__ . '/UserAuthDAO.php';                // Existing auth system

// Define constants
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (!defined('WEB_ROOT')) {
    define('WEB_ROOT', __DIR__);
}

// Error reporting for development
if (!defined('PRODUCTION')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Helper functions for easy session access with UserAuthDAO compatibility
function getSessionManager(): \App\Core\SessionManager {
    return \App\Core\SessionManager::getInstance();
}

function getSessionValue(string $key, $default = null) {
    return getSessionManager()->get($key, $default);
}

function setSessionValue(string $key, $value): void {
    getSessionManager()->set($key, $value);
}

function isLoggedIn(): bool {
    // Use UserAuthDAO format for compatibility
    $userAuth = getUserAuth();
    return $userAuth ? $userAuth->isLoggedIn() : false;
}

function isAdmin(): bool {
    // Use UserAuthDAO format for compatibility
    $currentUser = getCurrentUser();
    return $currentUser && isset($currentUser['is_admin']) && $currentUser['is_admin'];
}

function getCurrentUser(): ?array {
    // Use UserAuthDAO format for compatibility
    $userAuth = getUserAuth();
    return $userAuth ? $userAuth->getCurrentUser() : null;
}

function getUserAuth(): ?\UserAuthDAO {
    static $userAuth = null;
    if ($userAuth === null) {
        try {
            $userAuth = new \UserAuthDAO();
        } catch (\Exception $e) {
            error_log('UserAuth initialization failed: ' . $e->getMessage());
            return null;
        }
    }
    return $userAuth;
}

function addFlashMessage(string $type, string $message): void {
    getSessionManager()->addFlash($type, $message);
}

function getFlashMessages(string $type = null): array {
    $sessionManager = getSessionManager();
    return $type ? $sessionManager->getFlashes($type) : $sessionManager->getAllFlashes();
}
