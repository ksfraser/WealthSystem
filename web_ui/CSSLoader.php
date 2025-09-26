<?php
/**
 * CSS Loader Helper - Modular CSS loading following SRP
 * 
 * Each page can load only the CSS modules it needs, improving performance
 * and following Single Responsibility Principle for CSS organization.
 */

class CSSLoader {
    private static $cssPath = 'css/';
    
    /**
     * Load specific CSS modules
     */
    public static function load(array $modules): string {
        $cssLinks = '';
        
        foreach ($modules as $module) {
            $cssLinks .= '<link rel="stylesheet" href="' . self::$cssPath . $module . '.css">' . "\n";
        }
        
        return $cssLinks;
    }
    
    /**
     * Predefined CSS combinations for common page types
     */
    public static function loadDashboard(): string {
        return self::load([
            'nav-core',
            'nav-links', 
            'dropdown-base',
            'user-dropdown',
            'portfolio-dropdown',
            'nav-responsive'
        ]);
    }
    
    public static function loadAuthPages(): string {
        return self::load([
            'nav-core',
            'dropdown-base',
            'user-dropdown',
            'nav-responsive'
        ]);
    }
    
    public static function loadAdminPages(): string {
        return self::load([
            'nav-core',
            'nav-links',
            'dropdown-base', 
            'user-dropdown',
            'nav-responsive'
        ]);
    }
    
    public static function loadMinimal(): string {
        return self::load(['nav-core']);
    }
    
    /**
     * Load custom combination
     */
    public static function loadCustom(array $modules): string {
        return self::load($modules);
    }
}

// Usage examples:
// echo CSSLoader::loadDashboard();           // For main dashboard pages
// echo CSSLoader::loadAuthPages();           // For login/register pages  
// echo CSSLoader::loadMinimal();             // For simple pages
// echo CSSLoader::loadCustom(['nav-core', 'user-dropdown']); // Custom combination
?>