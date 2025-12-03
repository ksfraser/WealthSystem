<?php
require_once __DIR__ . '/Services/NavigationBuilder.php';
require_once __DIR__ . '/Services/DashboardCardBuilder.php';
require_once __DIR__ . '/Services/BreadcrumbBuilder.php';
require_once __DIR__ . '/Providers/PortfolioItemsProvider.php';
require_once __DIR__ . '/Providers/StockAnalysisItemsProvider.php';
require_once __DIR__ . '/Providers/DataManagementItemsProvider.php';
require_once __DIR__ . '/Providers/ReportsItemsProvider.php';
require_once __DIR__ . '/Providers/AdminItemsProvider.php';
require_once __DIR__ . '/Providers/ProfileItemsProvider.php';
require_once __DIR__ . '/Providers/TradingStrategiesItemsProvider.php';

/**
 * Navigation Factory
 * Factory class for creating navigation builders with all providers registered
 */
class NavigationFactory {
    private static $config = null;
    
    /**
     * Load navigation configuration
     */
    private static function getConfig(): array {
        if (self::$config === null) {
            $configFile = __DIR__ . '/../config/navigation.php';
            if (file_exists($configFile)) {
                self::$config = require $configFile;
            } else {
                // Default configuration
                self::$config = [
                    'restricted_items_mode' => 'greyed_out',
                    'cache_enabled' => false,
                    'show_icons' => true,
                    'show_restriction_tooltip' => true,
                ];
            }
        }
        return self::$config;
    }
    
    /**
     * Create a navigation builder with all providers registered
     */
    public static function createNavigationBuilder(?array $currentUser = null, string $currentPath = ''): NavigationBuilder {
        $config = self::getConfig();
        $builder = new NavigationBuilder($config, $currentUser, $currentPath);
        
        // Register all providers
        $builder->addProvider(new PortfolioItemsProvider());
        $builder->addProvider(new StockAnalysisItemsProvider());
        $builder->addProvider(new DataManagementItemsProvider());
        $builder->addProvider(new ReportsItemsProvider());
        $builder->addProvider(new AdminItemsProvider());
        $builder->addProvider(new ProfileItemsProvider());
        
        return $builder;
    }
    
    /**
     * Create a dashboard card builder with all providers registered
     */
    public static function createDashboardCardBuilder(?array $currentUser = null): DashboardCardBuilder {
        $config = self::getConfig();
        $builder = new DashboardCardBuilder($config, $currentUser);
        
        // Register all providers
        $builder->addProvider(new PortfolioItemsProvider());
        $builder->addProvider(new StockAnalysisItemsProvider());
        $builder->addProvider(new DataManagementItemsProvider());
        $builder->addProvider(new ReportsItemsProvider());
        $builder->addProvider(new AdminItemsProvider());
        $builder->addProvider(new TradingStrategiesItemsProvider());
        $builder->addProvider(new ProfileItemsProvider());
        
        return $builder;
    }
    
    /**
     * Create a breadcrumb builder with configuration
     */
    public static function createBreadcrumbBuilder(?array $currentUser = null): BreadcrumbBuilder {
        $config = self::getConfig();
        return new BreadcrumbBuilder($config, $currentUser);
    }
    
    /**
     * Get a specific provider
     */
    public static function getProvider(string $providerClass): NavigationItemProvider {
        if (!class_exists($providerClass)) {
            throw new Exception("Provider class not found: $providerClass");
        }
        return new $providerClass();
    }
}
