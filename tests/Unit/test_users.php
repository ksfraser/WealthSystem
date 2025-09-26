<?php
// Include UI system only (skip auth for testing)
require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';
require_once 'MenuService.php';

// Use the namespaced UI Factory
use Ksfraser\UIRenderer\Factories\UiFactory;

/**
 * Simple User Management Controller - Testing
 */
class SimpleUserManagementController {
    
    public function renderPage() {
        try {
            // Create a simple test card
            $testCard = UiFactory::createCard('User Management', 'This is a test of the user management system.');
            
            // Create navigation
            $navigation = UiFactory::createNavigation(
                'User Management Test',
                'admin_users',
                null,
                false,
                [],
                false
            );
            
            // Create page layout
            $pageRenderer = UiFactory::createPage(
                'User Management Test',
                $navigation,
                [$testCard]
            );
            
            return $pageRenderer->render();
            
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
}

// Application Entry Point
try {
    $controller = new SimpleUserManagementController();
    echo $controller->renderPage();
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage();
}
?>
