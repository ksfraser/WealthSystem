<?php

namespace Ksfraser\UIRenderer\Factories;

use Ksfraser\UIRenderer\Components\NavigationComponent;
use Ksfraser\UIRenderer\Components\CardComponent;
use Ksfraser\UIRenderer\Components\TableComponent;
use Ksfraser\UIRenderer\Renderers\PageRenderer;
use Ksfraser\UIRenderer\DTOs\NavigationDto;
use Ksfraser\UIRenderer\DTOs\CardDto;

/**
 * UI Factory - Creates UI components using Factory pattern
 * Follows Factory Pattern and provides convenient creation methods
 */
class UiFactory {
    
    /**
     * Create a navigation component
     */
    public static function createNavigation(
        $title = 'Application',
        $currentPage = '',
        $user = null,
        $isAdmin = false,
        $menuItems = [],
        $isAuthenticated = false
    ) {
        $navigationDto = new NavigationDto(
            $title,
            $currentPage,
            $user,
            $isAdmin,
            $menuItems,
            $isAuthenticated
        );
        
        return new NavigationComponent($navigationDto);
    }
    
    /**
     * Create a card component
     */
    public static function createCard(
        $title,
        $content,
        $type = 'default',
        $icon = '',
        $actions = []
    ) {
        $cardDto = new CardDto($title, $content, $type, $icon, $actions);
        return new CardComponent($cardDto);
    }
    
    /**
     * Create a table component
     */
    public static function createTable($data = [], $headers = [], $options = []) {
        return new TableComponent($data, $headers, $options);
    }
    
    /**
     * Create a page renderer
     */
    public static function createPage($title, $navigation, $components = [], $options = []) {
        return new PageRenderer($title, $navigation, $components, $options);
    }
    
    /**
     * Create a success card
     */
    public static function createSuccessCard($title, $content, $actions = []) {
        return self::createCard($title, $content, 'success', '✅', $actions);
    }
    
    /**
     * Create an info card
     */
    public static function createInfoCard($title, $content, $actions = []) {
        return self::createCard($title, $content, 'info', 'ℹ️', $actions);
    }
    
    /**
     * Create a warning card
     */
    public static function createWarningCard($title, $content, $actions = []) {
        return self::createCard($title, $content, 'warning', '⚠️', $actions);
    }
    
    /**
     * Create an error card
     */
    public static function createErrorCard($title, $content, $actions = []) {
        return self::createCard($title, $content, 'error', '❌', $actions);
    }
    
    /**
     * Create a data card with table
     */
    public static function createDataCard($title, $data, $headers = [], $tableOptions = []) {
        $table = self::createTable($data, $headers, $tableOptions);
        return self::createCard($title, $table->toHtml(), 'info');
    }
    
    /**
     * Quick method to create a complete page with navigation and cards
     */
    public static function createQuickPage(
        $pageTitle,
        $navTitle = '',
        $currentPage = '',
        $user = null,
        $isAdmin = false,
        $menuItems = [],
        $isAuthenticated = false,
        $cards = [],
        $pageOptions = []
    ) {
        $navigation = self::createNavigation(
            $navTitle ?: $pageTitle,
            $currentPage,
            $user,
            $isAdmin,
            $menuItems,
            $isAuthenticated
        );
        
        $components = [];
        foreach ($cards as $card) {
            if (is_array($card)) {
                $components[] = self::createCard(
                    $card['title'] ?? 'Card',
                    $card['content'] ?? '',
                    $card['type'] ?? 'default',
                    $card['icon'] ?? '',
                    $card['actions'] ?? []
                );
            } else {
                $components[] = $card;
            }
        }
        
        return self::createPage($pageTitle, $navigation, $components, $pageOptions);
    }
}
