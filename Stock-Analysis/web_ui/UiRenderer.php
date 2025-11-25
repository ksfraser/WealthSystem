<?php
/**
 * UI Renderer Compatibility Layer
 * Provides backward compatibility with the old UiRenderer.php while using the new namespaced version
 */

// Load the new namespaced UI Renderer
require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';

use Ksfraser\UIRenderer\Factories\UiFactory as NamespacedUiFactory;

// Backward compatibility aliases and wrapper classes

/**
 * Legacy UiFactory wrapper for backward compatibility
 */
class UiFactory {
    public static function createNavigationComponent(
        $title = 'Enhanced Trading System',
        $currentPage = '',
        $user = null,
        $isAdmin = false,
        $menuItems = [],
        $isAuthenticated = false
    ) {
        return NamespacedUiFactory::createNavigation(
            $title,
            $currentPage,
            $user,
            $isAdmin,
            $menuItems,
            $isAuthenticated
        );
    }
    
    public static function createCardComponent(
        $title,
        $content,
        $type = 'default',
        $icon = '',
        $actions = []
    ) {
        return NamespacedUiFactory::createCard($title, $content, $type, $icon, $actions);
    }
    
    public static function createTableComponent($data = [], $headers = [], $options = []) {
        return NamespacedUiFactory::createTable($data, $headers, $options);
    }
    
    public static function createPageRenderer($title, $navigation, $components = [], $options = []) {
        return NamespacedUiFactory::createPage($title, $navigation, $components, $options);
    }
    
    // Convenience methods
    public static function createSuccessCard($title, $content, $actions = []) {
        return NamespacedUiFactory::createSuccessCard($title, $content, $actions);
    }
    
    public static function createInfoCard($title, $content, $actions = []) {
        return NamespacedUiFactory::createInfoCard($title, $content, $actions);
    }
    
    public static function createWarningCard($title, $content, $actions = []) {
        return NamespacedUiFactory::createWarningCard($title, $content, $actions);
    }
    
    public static function createErrorCard($title, $content, $actions = []) {
        return NamespacedUiFactory::createErrorCard($title, $content, $actions);
    }
    
    public static function createDataCard($title, $data, $headers = [], $tableOptions = []) {
        return NamespacedUiFactory::createDataCard($title, $data, $headers, $tableOptions);
    }
    
    // Additional aliases for methods used in index.php
    public static function createCard($title, $content, $type = 'default', $icon = '', $actions = []) {
        return NamespacedUiFactory::createCard($title, $content, $type, $icon, $actions);
    }
    
    public static function createNavigation($title, $currentPage, $user, $isAdmin, $menuItems, $isAuthenticated = false) {
        return NamespacedUiFactory::createNavigation($title, $currentPage, $user, $isAdmin, $menuItems, $isAuthenticated);
    }
}

// MenuService class removed - now using standalone MenuService.php file
