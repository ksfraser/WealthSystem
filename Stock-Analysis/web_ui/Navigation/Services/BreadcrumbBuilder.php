<?php
require_once __DIR__ . '/../Models/BreadcrumbItem.php';

/**
 * Breadcrumb Builder
 * Builds breadcrumb trails from navigation paths
 */
class BreadcrumbBuilder {
    private $config;
    private $currentUser;
    private $isAdmin;
    
    // Breadcrumb definitions mapping URL patterns to breadcrumb trails
    private $breadcrumbMap = [
        'dashboard.php' => [
            ['🏠', 'Home', 'index.php'],
            ['📊', 'Dashboard', 'dashboard.php']
        ],
        'MyPortfolio.php' => [
            ['🏠', 'Home', 'index.php'],
            ['📊', 'Dashboard', 'dashboard.php'],
            ['💼', 'My Portfolio', 'MyPortfolio.php']
        ],
        'portfolios.php' => [
            ['🏠', 'Home', 'index.php'],
            ['📊', 'Dashboard', 'dashboard.php'],
            ['📈', 'Manage Portfolios', 'portfolios.php']
        ],
        'trades.php' => [
            ['🏠', 'Home', 'index.php'],
            ['📊', 'Dashboard', 'dashboard.php'],
            ['📋', 'Trades', 'trades.php']
        ],
        'stock_analysis.php' => [
            ['🏠', 'Home', 'index.php'],
            ['📊', 'Dashboard', 'dashboard.php'],
            ['🔍', 'Stock Analysis', 'stock_analysis.php']
        ],
        'admin_bank_accounts.php' => [
            ['🏠', 'Home', 'index.php'],
            ['📊', 'Dashboard', 'dashboard.php'],
            ['⚙️', 'Admin', 'dashboard.php'],
            ['🏦', 'Bank Accounts', 'admin_bank_accounts.php']
        ],
        'admin_brokerages.php' => [
            ['🏠', 'Home', 'index.php'],
            ['📊', 'Dashboard', 'dashboard.php'],
            ['⚙️', 'Admin', 'dashboard.php'],
            ['🏢', 'Brokerages', 'admin_brokerages.php']
        ],
        'admin_users.php' => [
            ['🏠', 'Home', 'index.php'],
            ['📊', 'Dashboard', 'dashboard.php'],
            ['⚙️', 'Admin', 'dashboard.php'],
            ['👥', 'Users', 'admin_users.php']
        ],
        'profile.php' => [
            ['🏠', 'Home', 'index.php'],
            ['📊', 'Dashboard', 'dashboard.php'],
            ['👤', 'Profile', 'profile.php']
        ],
        'reports.php' => [
            ['🏠', 'Home', 'index.php'],
            ['📊', 'Dashboard', 'dashboard.php'],
            ['📊', 'Reports', 'reports.php']
        ],
    ];
    
    public function __construct(array $config, ?array $currentUser = null) {
        $this->config = $config;
        $this->currentUser = $currentUser;
        $this->isAdmin = $currentUser && ($currentUser['is_admin'] ?? false);
    }
    
    /**
     * Get breadcrumb trail for current page
     */
    public function getBreadcrumbs(string $currentPage): array {
        $breadcrumbs = [];
        
        // Get breadcrumb definition for current page
        $trail = $this->breadcrumbMap[$currentPage] ?? [
            ['🏠', 'Home', 'index.php'],
            ['📄', basename($currentPage, '.php'), $currentPage]
        ];
        
        // Create BreadcrumbItem objects
        $lastIndex = count($trail) - 1;
        foreach ($trail as $index => $crumb) {
            $item = new BreadcrumbItem(
                'breadcrumb_' . $index,
                $crumb[1], // title
                '', // description
                $crumb[0], // icon
                $crumb[2], // url
                null, // no role required for breadcrumbs
                $index
            );
            
            if ($index === $lastIndex) {
                $item->setIsLast(true);
            }
            
            $breadcrumbs[] = $item;
        }
        
        return $breadcrumbs;
    }
    
    /**
     * Render breadcrumbs as HTML
     */
    public function renderBreadcrumbs(string $currentPage): string {
        $breadcrumbs = $this->getBreadcrumbs($currentPage);
        $restrictedMode = $this->config['restricted_items_mode'] ?? 'hidden';
        
        $html = '<nav aria-label="breadcrumb">';
        $html .= '<ol class="breadcrumb">';
        
        foreach ($breadcrumbs as $item) {
            $userRole = $this->currentUser['role'] ?? null;
            $hasAccess = $item->hasAccess($userRole, $this->isAdmin);
            $html .= $item->render($hasAccess, $restrictedMode);
        }
        
        $html .= '</ol>';
        $html .= '</nav>';
        
        return $html;
    }
    
    /**
     * Add custom breadcrumb trail for a page
     */
    public function addBreadcrumbTrail(string $page, array $trail): void {
        $this->breadcrumbMap[$page] = $trail;
    }
    
    /**
     * Get breadcrumbs as array
     */
    public function getBreadcrumbsArray(string $currentPage): array {
        $breadcrumbs = $this->getBreadcrumbs($currentPage);
        $result = [];
        
        foreach ($breadcrumbs as $item) {
            $result[] = $item->toArray();
        }
        
        return $result;
    }
}
