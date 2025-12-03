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
    
    /**
     * @param array $config Navigation configuration
     * @param array|null $currentUser Current user data
     */
    public function __construct(array $config, ?array $currentUser = null) {
        $this->config = $config;
        $this->currentUser = $currentUser;
        $this->isAdmin = $currentUser && ($currentUser['is_admin'] ?? false);
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
        
        return $allCards;
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
