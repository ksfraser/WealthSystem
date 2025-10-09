<?php
/**
 * User Bank Accounts Page - MVC Implementation
 *
 * This page has been refactored to follow SOLID principles and MVC architecture:
 * - Controller handles business logic and request orchestration
 * - View Service handles presentation logic and component rendering
 * - Proper dependency injection and separation of concerns
 * - Component-based UI rendering
 *
 * Requirements Implemented:
 * - FR-1: User Authentication and Authorization
 * - FR-2: Bank Account Access Management
 * - FR-3: Permission Levels
 * - FR-4: User Interface
 * - FR-5: Data Integrity and Audit
 * - NFR-1: Performance
 * - NFR-2: Security
 * - NFR-3: Usability
 * - NFR-4: Maintainability
 * - NFR-5: Compatibility
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/BankAccountsDAO.php';
require_once __DIR__ . '/NavigationService.php';
require_once __DIR__ . '/UserAuthDAO.php';
require_once __DIR__ . '/BankAccountController.php';
require_once __DIR__ . '/BankAccountViewService.php';

// Initialize services with dependency injection
$navService = new NavigationService();
$bankDAO = new BankAccountsDAO();
$userAuth = new UserAuthDAO();
$viewService = new BankAccountViewService($navService);

// Create controller with injected dependencies
$controller = new BankAccountController($bankDAO, $userAuth, $navService, $viewService);

// Get current user
$currentUser = $navService->getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = $controller->handlePostRequest($_POST, $currentUser);

    // Handle redirects or refreshes
    if (isset($response['action']) && $response['action'] === 'refresh') {
        // Refresh the page data after successful operations
        $pageData = $controller->handleGetRequest($currentUser);
        if (isset($response['message'])) {
            $pageData['message'] = $response['message'];
        }
    } else {
        $pageData = $controller->handleGetRequest($currentUser);
        if (!$response['success']) {
            $pageData['error'] = $response['error'];
        } elseif (isset($response['message'])) {
            $pageData['message'] = $response['message'];
        }
    }
} else {
    // Handle GET requests
    $pageData = $controller->handleGetRequest($currentUser);
}

// Render the complete page
echo $controller->renderPage($pageData);