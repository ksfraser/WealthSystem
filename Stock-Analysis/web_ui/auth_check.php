<?php
/**
 * Auth Check - Simple authentication wrapper for pages
 * Include this at the top of pages that require authentication
 */

// Include custom authentication exceptions
require_once __DIR__ . '/AuthExceptions.php';

// Import the namespaced exception classes
use App\Auth\AuthenticationException;
use App\Auth\LoginRequiredException;
use App\Auth\AdminRequiredException;
use App\Auth\SessionException;

// Use centralized SessionManager
require_once __DIR__ . '/SessionManager.php';

// Initialize session through SessionManager (handles headers safely)
$sessionManager = SessionManager::getInstance();

// Log any session initialization issues
if (!$sessionManager->isSessionActive()) {
    $error = $sessionManager->getInitializationError();
    if ($error && php_sapi_name() !== 'cli') {
        error_log('Auth Check: ' . $error);
    }
}

try {
    require_once __DIR__ . '/UserAuthDAO.php';
    $userAuth = new UserAuthDAO();
    
    // Check if user is logged in
    if (!$userAuth->isLoggedIn()) {
        // Redirect to login page
        $currentPage = $_SERVER['REQUEST_URI'] ?? '';
        $loginUrl = 'login.php';
        
        // Add return URL parameter if not already on login page
        if (!empty($currentPage) && !strpos($currentPage, 'login.php')) {
            $loginUrl .= '?return=' . urlencode($currentPage);
        }
        
        throw new LoginRequiredException($loginUrl, "User not logged in - redirect to login required");
    }
    
    // Make user data available to the page
    $currentUser = $userAuth->getCurrentUser();
    $user = $currentUser; // For backward compatibility
    $isAdmin = $userAuth->isAdmin();
    
} catch (Exception $e) {
    // If there's an authentication error, log it and throw appropriate exception
    error_log('Authentication error: ' . $e->getMessage());
    
    if ($e instanceof AuthenticationException) {
        // Re-throw authentication exceptions
        throw $e;
    } else {
        // Wrap other exceptions in SessionException
        throw new SessionException("Authentication system error: " . $e->getMessage());
    }
}

// Function to check if current user is admin
function requireAdmin() {
    global $userAuth;
    if (!$userAuth->isAdmin()) {
        throw new AdminRequiredException("Access denied - administrator privileges required");
    }
}

// Function to get current user safely
function getCurrentUser() {
    global $currentUser;
    return $currentUser;
}

// Function to check if current user is admin (non-fatal)
function isCurrentUserAdmin() {
    global $userAuth;
    return $userAuth->isAdmin();
}

// Function to explicitly require login (alias for clarity)
function requireLogin() {
    // Auth check already handles this automatically
    // This function exists for explicit calls in code
    return true;
}
?>
