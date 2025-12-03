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
    private $cacheKey;
    private $cachedItems = null;
    
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
        
        // Generate cache key based on user role and admin status
        $userRole = $currentUser['role'] ?? 'guest';
        $adminStatus = $this->isAdmin ? 'admin' : 'user';
        $this->cacheKey = "nav_menu_{$userRole}_{$adminStatus}";
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
        // Check cache if enabled
        if ($this->config['cache_enabled'] ?? false) {
            if ($this->cachedItems !== null) {
                return $this->cachedItems;
            }
            
            $cached = $this->getCachedItems();
            if ($cached !== null) {
                $this->cachedItems = $cached;
                return $cached;
            }
        }
        
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
        
        // Cache the results if enabled
        if ($this->config['cache_enabled'] ?? false) {
            $this->cacheItems($allItems);
            $this->cachedItems = $allItems;
        }
        
        return $allItems;
    }
    
    /**
     * Get cached items from file or memory cache
     */
    private function getCachedItems(): ?array {
        $cacheDir = __DIR__ . '/../../cache';
        $cacheFile = $cacheDir . '/' . $this->cacheKey . '.cache';
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $cacheDuration = $this->config['cache_duration'] ?? 3600;
        if (time() - filemtime($cacheFile) > $cacheDuration) {
            @unlink($cacheFile);
            return null;
        }
        
        $data = @file_get_contents($cacheFile);
        if ($data === false) {
            return null;
        }
        
        return unserialize($data);
    }
    
    /**
     * Cache items to file
     */
    private function cacheItems(array $items): void {
        $cacheDir = __DIR__ . '/../../cache';
        
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        
        $cacheFile = $cacheDir . '/' . $this->cacheKey . '.cache';
        @file_put_contents($cacheFile, serialize($items));
    }
    
    /**
     * Clear cache for this builder
     */
    public function clearCache(): void {
        $cacheDir = __DIR__ . '/../../cache';
        $cacheFile = $cacheDir . '/' . $this->cacheKey . '.cache';
        @unlink($cacheFile);
        $this->cachedItems = null;
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
