<?php
/**
 * Navigation Manager - Legacy wrapper for NavigationService
 * 
 * This class now delegates to NavigationService to follow SRP.
 * Eventually, components should use NavigationService directly.
 */

class NavigationManager {
    private $navigationService;
    
    public function __construct() {
        require_once __DIR__ . '/NavigationService.php';
        $this->navigationService = new NavigationService();
    }
    
    /**
     * Delegate to NavigationService
     */
    public function getNavigationCSS() {
        return $this->navigationService->getNavigationCSS();
    }
    
    public function getNavigationItems($currentPage = "") {
        return $this->navigationService->getMenuItems($currentPage);
    }
    
    public function renderNavigationHeader($pageTitle = "Enhanced Trading System", $currentPage = "") {
        echo $this->navigationService->renderNavigationHeader($pageTitle, $currentPage);
    }
    
    public function hasAccess($feature) {
        switch ($feature) {
            case "admin_users":
            case "system_status":
            case "database":
            case "user_management":
                return $this->navigationService->isUserAdmin();
            case "portfolios":
            case "trades":
            case "analytics":
            case "dashboard":
                return $this->navigationService->isUserLoggedIn();
            case "login":
            case "register":
                return !$this->navigationService->isUserLoggedIn();
            default:
                return false;
        }
    }
    
    public function getCurrentUser() {
        return $this->navigationService->getCurrentUser();
    }
    
    public function isLoggedIn() {
        return $this->navigationService->isUserLoggedIn();
    }
    
    public function isAdmin() {
        return $this->navigationService->isUserAdmin();
    }
    
    // Legacy method for backward compatibility
    public function getLoggedInUserNavigation($currentPage = "") {
        return $this->navigationService->getMenuItems($currentPage);
    }
}

// Global navigation instance - lazy loaded
$navManager = null;

// Helper function to get NavigationManager instance
function getNavManager() {
    global $navManager;
    if ($navManager === null) {
        $navManager = new NavigationManager();
    }
    return $navManager;
}

// Helper functions for backward compatibility
function renderNavigationHeader($pageTitle = "Enhanced Trading System", $currentPage = "") {
    $navManager = getNavManager();
    $navManager->renderNavigationHeader($pageTitle, $currentPage);
}

function getCurrentUser() {
    $navManager = getNavManager();
    return $navManager->getCurrentUser();
}

function isCurrentUserAdmin() {
    $navManager = getNavManager();
    return $navManager->isAdmin();
}

function isLoggedIn() {
    $navManager = getNavManager();
    return $navManager->isLoggedIn();
}
?>