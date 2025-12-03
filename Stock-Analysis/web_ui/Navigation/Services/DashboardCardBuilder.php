<?php
require_once __DIR__ . '/../Providers/NavigationItemProvider.php';

/**
 * Dashboard Card Builder
 * Builds dashboard cards from providers with access control filtering
 */
class DashboardCardBuilder {
    private $providers = [];
    private $config;
    private $currentUser;
    private $isAdmin;
    private $cacheKey;
    private $cachedCards = null;
    
    /**
     * @param array $config Navigation configuration
     * @param array|null $currentUser Current user data
     */
    public function __construct(array $config, ?array $currentUser = null) {
        $this->config = $config;
        $this->currentUser = $currentUser;
        $this->isAdmin = $currentUser && ($currentUser['is_admin'] ?? false);
        
        // Generate cache key
        $userRole = $currentUser['role'] ?? 'guest';
        $adminStatus = $this->isAdmin ? 'admin' : 'user';
        $this->cacheKey = "dashboard_cards_{$userRole}_{$adminStatus}";
    }
    
    /**
     * Register a provider
     */
    public function addProvider(NavigationItemProvider $provider): self {
        $this->providers[] = $provider;
        return $this;
    }
    
    /**
     * Get all cards from all providers, filtered by access
     * @return DashboardCard[]
     */
    public function getCards(): array {
        // Check cache if enabled
        if ($this->config['cache_enabled'] ?? false) {
            if ($this->cachedCards !== null) {
                return $this->cachedCards;
            }
            
            $cached = $this->getCachedCards();
            if ($cached !== null) {
                $this->cachedCards = $cached;
                return $cached;
            }
        }
        
        $allCards = [];
        
        foreach ($this->providers as $provider) {
            $cards = $provider->getDashboardCards();
            foreach ($cards as $card) {
                $allCards[] = $card;
            }
        }
        
        // Sort by sort order
        usort($allCards, function($a, $b) {
            return $a->getSortOrder() <=> $b->getSortOrder();
        });
        
        // Cache if enabled
        if ($this->config['cache_enabled'] ?? false) {
            $this->cacheCards($allCards);
            $this->cachedCards = $allCards;
        }
        
        return $allCards;
    }
    
    /**
     * Get cached cards
     */
    private function getCachedCards(): ?array {
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
     * Cache cards to file
     */
    private function cacheCards(array $cards): void {
        $cacheDir = __DIR__ . '/../../cache';
        
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        
        $cacheFile = $cacheDir . '/' . $this->cacheKey . '.cache';
        @file_put_contents($cacheFile, serialize($cards));
    }
    
    /**
     * Clear cache
     */
    public function clearCache(): void {
        $cacheDir = __DIR__ . '/../../cache';
        $cacheFile = $cacheDir . '/' . $this->cacheKey . '.cache';
        @unlink($cacheFile);
        $this->cachedCards = null;
    }
    
    /**
     * Render all cards as HTML
     */
    public function renderCards(): string {
        $cards = $this->getCards();
        $restrictedMode = $this->config['restricted_items_mode'] ?? 'hidden';
        $html = '';
        
        foreach ($cards as $card) {
            $userRole = $this->currentUser['role'] ?? null;
            $hasAccess = $card->hasAccess($userRole, $this->isAdmin);
            
            $html .= $card->render($hasAccess, $restrictedMode);
        }
        
        return $html;
    }
    
    /**
     * Get cards as array data
     */
    public function getCardsArray(): array {
        $cards = $this->getCards();
        $restrictedMode = $this->config['restricted_items_mode'] ?? 'hidden';
        $result = [];
        
        foreach ($cards as $card) {
            $userRole = $this->currentUser['role'] ?? null;
            $hasAccess = $card->hasAccess($userRole, $this->isAdmin);
            
            // Skip if hidden mode and no access
            if (!$hasAccess && $restrictedMode === 'hidden') {
                continue;
            }
            
            $cardData = $card->toArray();
            $cardData['has_access'] = $hasAccess;
            $result[] = $cardData;
        }
        
        return $result;
    }
}
