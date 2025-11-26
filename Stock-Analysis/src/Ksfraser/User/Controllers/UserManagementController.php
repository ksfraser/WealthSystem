<?php

namespace Ksfraser\User\Controllers;

use Ksfraser\UIRenderer\Factories\UiFactory;
use Ksfraser\User\Services\UserManagementContentService;
use Ksfraser\User\Services\UserManagementService;
use Ksfraser\User\DTOs\UserManagementRequest;
use Exception;

/**
 * User Management Controller - Clean MVC Architecture
 * Handles the user management interface using component-based UI rendering
 */
class UserManagementController {
    private $contentService;
    private $userService;
    private $currentUser;
    private $isAdmin;
    private $userAuth;
    private $message = '';
    private $messageType = '';
    
    public function __construct($userAuth) {
        $this->userAuth = $userAuth;
        $this->currentUser = $userAuth->getCurrentUser();
        $this->isAdmin = $this->currentUser ? $userAuth->isAdmin() : false;
        
        $this->contentService = new UserManagementContentService($userAuth, $this->currentUser, $this->isAdmin);
        $this->userService = new UserManagementService($userAuth, $this->currentUser);
        
        // Handle form submissions
        $this->handleFormSubmissions();
    }
    
    /**
     * Handle form submissions using the service layer
     */
    private function handleFormSubmissions() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $request = new UserManagementRequest();
            
            if ($request->hasAction()) {
                if ($request->isCreateAction()) {
                    list($this->message, $this->messageType) = $this->userService->createUser($request);
                } elseif ($request->isDeleteAction()) {
                    list($this->message, $this->messageType) = $this->userService->deleteUser($request);
                } elseif ($request->isToggleAdminAction()) {
                    list($this->message, $this->messageType) = $this->userService->toggleAdminStatus($request);
                }
            }
        }
    }
    
    /**
     * Render the user management page
     */
    public function renderPage() {
        // Check authentication
        if (!$this->currentUser) {
            return $this->renderAccessDeniedPage(
                'Access Denied',
                '<p>Please log in to access the user management dashboard.</p>' .
                '<div style="margin-top: 15px;"><a href="login.php" class="btn">Login</a></div>'
            );
        }
        
        if (!$this->isAdmin) {
            return $this->renderAccessDeniedPage(
                'Admin Access Required',
                '<p>You need administrator privileges to access the user management dashboard.</p>' .
                '<div style="margin-top: 15px;"><a href="index.php" class="btn">Return to Portfolio</a></div>'
            );
        }
        
        // Create menu service - using simplified menu for now
        $menuItems = $this->getMenuItems();
        
        // Generate page components
        $components = $this->contentService->createUserManagementComponents($this->message, $this->messageType);
        
        // Create navigation
        $navigation = UiFactory::createNavigation(
            'User Management Dashboard',
            'admin_users',
            $this->currentUser,
            $this->isAdmin,
            $menuItems,
            true
        );
        
        // Create page layout
        $pageRenderer = UiFactory::createPage(
            'User Management Dashboard - Enhanced Trading System',
            $navigation,
            $components
        );
        
        return $pageRenderer->render();
    }
    
    /**
     * Render access denied page
     */
    private function renderAccessDeniedPage($title, $content) {
        $errorNavigation = UiFactory::createNavigation(
            'Access Denied - User Management',
            'error',
            $this->currentUser,
            false,
            [],
            false
        );
        
        $errorCard = UiFactory::createErrorCard($title, $content);
        
        $pageRenderer = UiFactory::createPage(
            'Access Denied - User Management',
            $errorNavigation,
            [$errorCard]
        );
        
        return $pageRenderer->render();
    }
    
    /**
     * Get menu items - simplified for now
     */
    private function getMenuItems() {
        // This would typically use MenuService::getMenuItems() but simplified for testing
        return [
            ['label' => 'Portfolio', 'url' => 'index.php', 'active' => false],
            ['label' => 'Trades', 'url' => 'admin_trades.php', 'active' => false],
            ['label' => 'Analytics', 'url' => 'analytics.php', 'active' => false],
            ['label' => 'Users', 'url' => 'admin_users.php', 'active' => true]
        ];
    }
    
    /**
     * Render error page
     */
    public static function renderErrorPage($errorMessage) {
        $errorNavigation = UiFactory::createNavigation(
            'Error - User Management',
            'error',
            null,
            false,
            [],
            false
        );
        
        $errorCard = UiFactory::createErrorCard(
            'User Management System Error',
            '<p>The user management system encountered an error.</p>' .
            '<div style="margin-top: 15px; padding: 10px; background-color: #f8f9fa; border-radius: 4px;">' .
            '<strong>Error:</strong> ' . htmlspecialchars($errorMessage) . 
            '</div>' .
            '<div style="margin-top: 15px;">' .
            '<a href="index.php" class="btn">Return to Portfolio</a>' .
            '</div>'
        );
        
        $pageRenderer = UiFactory::createPage(
            'Error - User Management System',
            $errorNavigation,
            [$errorCard]
        );
        
        return $pageRenderer->render();
    }
}
