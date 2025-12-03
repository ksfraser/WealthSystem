<?php
/**
 * Base Navigation Item
 * Represents any navigable item (menu item, card, etc.)
 */
abstract class NavigationItem {
    protected $id;
    protected $title;
    protected $description;
    protected $icon;
    protected $url;
    protected $requiredRole;
    protected $isActive = false;
    protected $sortOrder = 0;
    protected $isEnabled = true;
    
    /**
     * @param string $id Unique identifier
     * @param string $title Display title
     * @param string $description Item description
     * @param string $icon Icon (emoji or CSS class)
     * @param string $url Target URL
     * @param string|null $requiredRole Required role (null = all authenticated users)
     * @param int $sortOrder Display order
     */
    public function __construct(
        string $id,
        string $title,
        string $description,
        string $icon,
        string $url,
        ?string $requiredRole = null,
        int $sortOrder = 0
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->icon = $icon;
        $this->url = $url;
        $this->requiredRole = $requiredRole;
        $this->sortOrder = $sortOrder;
    }
    
    // Getters
    public function getId(): string { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): string { return $this->description; }
    public function getIcon(): string { return $this->icon; }
    public function getUrl(): string { return $this->url; }
    public function getRequiredRole(): ?string { return $this->requiredRole; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function isActive(): bool { return $this->isActive; }
    public function isEnabled(): bool { return $this->isEnabled; }
    
    // Setters
    public function setActive(bool $active): self {
        $this->isActive = $active;
        return $this;
    }
    
    public function setEnabled(bool $enabled): self {
        $this->isEnabled = $enabled;
        return $this;
    }
    
    /**
     * Check if user has access to this item
     */
    public function hasAccess(?string $userRole, bool $isAdmin = false): bool {
        if ($this->requiredRole === null) {
            return true; // Available to all authenticated users
        }
        
        if ($isAdmin) {
            return true; // Admins have access to everything
        }
        
        return $userRole === $this->requiredRole;
    }
    
    /**
     * Convert to array for rendering
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon,
            'url' => $this->url,
            'required_role' => $this->requiredRole,
            'is_active' => $this->isActive,
            'is_enabled' => $this->isEnabled,
            'sort_order' => $this->sortOrder,
        ];
    }
}
