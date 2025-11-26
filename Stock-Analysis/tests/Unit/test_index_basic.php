<?php
// Test just the beginning of index.php to isolate the issue
echo "Starting index.php test...\n";

// Test error reporting
echo "1. Testing error reporting...\n";
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "✅ Error reporting enabled\n";

// Test UI components
echo "2. Testing UI component loading...\n";
try {
    require_once 'UiRenderer.php';
    echo "✅ UiRenderer loaded\n";
} catch (Exception $e) {
    echo "❌ UiRenderer failed: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    require_once 'SessionManager.php';
    echo "✅ SessionManager loaded\n";
} catch (Exception $e) {
    echo "❌ SessionManager failed: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    require_once 'MenuService.php';
    echo "✅ MenuService loaded\n";
} catch (Exception $e) {
    echo "❌ MenuService failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test auth service initialization
echo "3. Testing auth service...\n";
require_once __DIR__ . '/UserAuthDAO.php';

class TestCompatibleAuthenticationService {
    private $isAuthenticated = false;
    private $currentUser = null;
    private $isAdmin = false;
    private $authError = false;
    private $errorMessage = '';
    private $userAuth = null;
    private $sessionManager = null;
    
    public function __construct() {
        $this->initializeAuthentication();
    }
    
    private function initializeAuthentication() {
        try {
            echo "  - Initializing SessionManager...\n";
            $this->sessionManager = SessionManager::getInstance();
            
            if (!$this->sessionManager->isSessionActive()) {
                $error = $this->sessionManager->getInitializationError();
                if ($error) {
                    throw new Exception('Session initialization failed: ' . $error);
                }
            }
            echo "  ✅ SessionManager initialized\n";
            
            echo "  - Loading UserAuthDAO...\n";
            $this->userAuth = new UserAuthDAO();
            echo "  ✅ UserAuthDAO loaded\n";
            
            echo "  - Checking login status...\n";
            if ($this->userAuth->isLoggedIn()) {
                $this->isAuthenticated = true;
                $this->currentUser = $this->userAuth->getCurrentUser();
                $this->isAdmin = $this->userAuth->isAdmin();
                echo "  ✅ User logged in\n";
            } else {
                $this->isAuthenticated = false;
                $this->currentUser = ['username' => 'Guest'];
                $this->isAdmin = false;
                echo "  ⚠️ Not logged in (guest mode)\n";
            }
            
        } catch (Exception $e) {
            $this->handleAuthFailure($e);
        } catch (Error $e) {
            $this->handleAuthFailure($e);
        }
    }
    
    private function handleAuthFailure($error) {
        $this->authError = true;
        $this->currentUser = ['username' => 'Guest (Auth Error)'];
        $this->isAuthenticated = false;
        $this->isAdmin = false;
        $this->errorMessage = $error->getMessage();
        echo "  ⚠️ Auth error: " . $error->getMessage() . "\n";
    }
    
    public function isAuthenticated() { return $this->isAuthenticated; }
    public function getCurrentUser() { return $this->currentUser; }
    public function isAdmin() { return $this->isAdmin; }
    public function hasAuthError() { return $this->authError; }
    public function getErrorMessage() { return $this->errorMessage; }
}

try {
    $testAuth = new TestCompatibleAuthenticationService();
    echo "✅ Auth service initialized\n";
    echo "User: " . $testAuth->getCurrentUser()['username'] . "\n";
    echo "Admin: " . ($testAuth->isAdmin() ? 'Yes' : 'No') . "\n";
    echo "Authenticated: " . ($testAuth->isAuthenticated() ? 'Yes' : 'No') . "\n";
} catch (Exception $e) {
    echo "❌ Auth service failed: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Auth service error: " . $e->getMessage() . "\n";
}

echo "\n✅ Basic index.php components working!\n";
?>
