<?php
/**
 * Admin User Management - Entry Point
 * Using clean MVC architecture with controller pattern
 */

require_once __DIR__ . '/UserAuthDAO.php';
require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';

// Use the User Management Controller
use Ksfraser\User\Controllers\UserManagementController;

// Application entry point - let the controller handle everything
try {
    $userAuth = new UserAuthDAO();
    $controller = new UserManagementController($userAuth);
    echo $controller->renderPage();
} catch (Exception $e) {
    echo UserManagementController::renderErrorPage($e->getMessage());
}
?>
