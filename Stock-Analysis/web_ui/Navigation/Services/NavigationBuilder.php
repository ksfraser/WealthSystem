<?php
require_once __DIR__ . '/../Providers/NavigationItemProvider.php';

/**
 * Navigation Builder
 * Builds navigation menus from providers with access control filtering
 */
class NavigationBuilder {
    private $providers = [];
    private $config;
    private $currentUser;
    private $isAdmin;
    private $currentPath;
    
    /**
     * @param array $config Navigation configuration
     * @param array|null $currentUser Current user data
     * @param string $currentPath Current page path for active state
     */
    public function __construct(array $config, ?array $currentUser = null, string $currentPath = '') {
        $this->config = $config;
        $this->currentUser = $currentUser;
        $this->isAdmin = $currentUser && ($currentUser['is_admin'] ?? false);
        $this->currentPath = $currentPath;
    }
    
    /**
     * Register a provider
     */
    public function addProvider(NavigationItemProvider $provider): self {
        $this->providers[] = $provider;
        return $this;
    }
    
    /**
     * Get all menu items from all providers, filtered by access
     * @return MenuItem[]
     */
    public function getMenuItems(): array {
        $allItems = [];
        
        foreach ($this->providers as $provider) {
            $items = $provider->getMenuItems();
            foreach ($items as $item) {
                // Mark as active if URL matches current path
                if ($this->isItemActive($item)) {
                    $item->setActive(true);
                }
                $allItems[] = $item;
            }
        }
        
        // Sort by sort order
        usort($allItems, function($a, $b) {
            return $a->getSortOrder() <=> $b->getSortOrder();
        });
        
        return $allItems;
    }
    
    /**
     * Check if menu item or any of its children match current path
     */
    private function isItemActive($item): bool {
        if (strpos($this->currentPath, $item->getUrl()) !== false) {
            return true;
        }
        
        if ($item->hasChildren()) {
            foreach ($item->getChildren() as $child) {
                if ($this->isItemActive($child)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Render menu items as HTML
     */
    public function renderMenu(): string {
        $items = $this->getMenuItems();
        $restrictedMode = $this->config['restricted_items_mode'] ?? 'hidden';
        $html = '';
        
        foreach ($items as $item) {
            $userRole = $this->currentUser['role'] ?? null;
            $hasAccess = $item->hasAccess($userRole, $this->isAdmin);
            
            // Also filter children by access
            if ($item->hasChildren()) {
                $accessibleChildren = array_filter(
                    $item->getChildren(),
                    fn($child) => $child->hasAccess($userRole, $this->isAdmin) || $restrictedMode === 'greyed_out'
                );
                
                // If no accessible children and hidden mode, skip the parent too
                if (empty($accessibleChildren) && $restrictedMode === 'hidden') {
                    continue;
                }
            }
            
            $html .= $item->render($hasAccess, $restrictedMode);
        }
        
        return $html;
    }
    
    /**
     * Get menu items as array data
     */
    public function getMenuArray(): array {
        $items = $this->getMenuItems();
        $restrictedMode = $this->config['restricted_items_mode'] ?? 'hidden';
        $result = [];
        
        foreach ($items as $item) {
            $userRole = $this->currentUser['role'] ?? null;
            $hasAccess = $item->hasAccess($userRole, $this->isAdmin);
            
            // Skip if hidden mode and no access
            if (!$hasAccess && $restrictedMode === 'hidden' && !$item->hasChildren()) {
                continue;
            }
            
            $itemData = $item->toArray();
            $itemData['has_access'] = $hasAccess;
            
            // Filter children
            if ($item->hasChildren()) {
                $accessibleChildren = [];
                foreach ($item->getChildren() as $child) {
                    $childHasAccess = $child->hasAccess($userRole, $this->isAdmin);
                    if ($childHasAccess || $restrictedMode === 'greyed_out') {
                        $childData = $child->toArray();
                        $childData['has_access'] = $childHasAccess;
                        $accessibleChildren[] = $childData;
                    }
                }
                $itemData['children'] = $accessibleChildren;
                
                // Skip parent if no children and hidden mode
                if (empty($accessibleChildren) && $restrictedMode === 'hidden') {
                    continue;
                }
            }
            
            $result[] = $itemData;
        }
        
        return $result;
    }
}
