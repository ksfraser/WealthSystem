<?php
require_once __DIR__ . '/NavigationItem.php';

/**
 * Menu Item
 * Represents an item in the navigation menu (can have dropdown children)
 */
class MenuItem extends NavigationItem {
    private $children = [];
    private $badge = null;
    private $badgeClass = 'badge-secondary';
    private $iconClass = '';
    
    /**
     * @param string $id Unique identifier
     * @param string $title Menu item title
     * @param string $description Description (for tooltips)
     * @param string $icon Icon (emoji or CSS class)
     * @param string $url Target URL
     * @param string|null $requiredRole Required role
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
        parent::__construct($id, $title, $description, $icon, $url, $requiredRole, $sortOrder);
    }
    
    /**
     * Add a child menu item (for dropdown menus)
     */
    public function addChild(MenuItem $child): self {
        $this->children[] = $child;
        return $this;
    }
    
    /**
     * Set multiple children at once
     */
    public function setChildren(array $children): self {
        $this->children = $children;
        return $this;
    }
    
    /**
     * Check if this item has children (is a dropdown)
     */
    public function hasChildren(): bool {
        return !empty($this->children);
    }
    
    /**
     * Get children
     */
    public function getChildren(): array {
        return $this->children;
    }
    
    /**
     * Add a badge to the menu item
     */
    public function setBadge(string $badge, string $badgeClass = 'badge-secondary'): self {
        $this->badge = $badge;
        $this->badgeClass = $badgeClass;
        return $this;
    }
    
    /**
     * Set icon CSS class (for icon fonts)
     */
    public function setIconClass(string $class): self {
        $this->iconClass = $class;
        return $this;
    }
    
    /**
     * Convert to array
     */
    public function toArray(): array {
        $data = parent::toArray();
        $data['badge'] = $this->badge;
        $data['badge_class'] = $this->badgeClass;
        $data['icon_class'] = $this->iconClass;
        $data['has_children'] = $this->hasChildren();
        $data['children'] = array_map(fn($child) => $child->toArray(), $this->children);
        return $data;
    }
    
    /**
     * Render as menu item HTML
     */
    public function render(bool $hasAccess, string $restrictedMode = 'hidden'): string {
        if (!$hasAccess && $restrictedMode === 'hidden') {
            return '';
        }
        
        $disabled = !$hasAccess ? 'disabled' : '';
        $tooltip = !$hasAccess ? 'title="Requires ' . ($this->requiredRole ?? 'admin') . ' access"' : '';
        $activeClass = $this->isActive ? 'active' : '';
        
        if ($this->hasChildren()) {
            // Dropdown menu
            $html = '<li class="nav-item dropdown ' . $activeClass . '">';
            $html .= '<a class="nav-link dropdown-toggle ' . $disabled . '" href="#" role="button" data-bs-toggle="dropdown" ' . $tooltip . '>';
            $html .= $this->icon . ' ' . htmlspecialchars($this->title);
            if ($this->badge) {
                $html .= ' <span class="badge ' . $this->badgeClass . '">' . htmlspecialchars($this->badge) . '</span>';
            }
            $html .= '</a>';
            $html .= '<ul class="dropdown-menu">';
            
            foreach ($this->children as $child) {
                $childHasAccess = $hasAccess; // Inherit parent access for now
                if ($childHtml = $child->renderDropdownItem($childHasAccess, $restrictedMode)) {
                    $html .= $childHtml;
                }
            }
            
            $html .= '</ul>';
            $html .= '</li>';
        } else {
            // Regular menu item
            $html = '<li class="nav-item ' . $activeClass . '">';
            $html .= '<a class="nav-link ' . $disabled . '" href="' . htmlspecialchars($this->url) . '" ' . $tooltip . '>';
            $html .= $this->icon . ' ' . htmlspecialchars($this->title);
            if ($this->badge) {
                $html .= ' <span class="badge ' . $this->badgeClass . '">' . htmlspecialchars($this->badge) . '</span>';
            }
            $html .= '</a>';
            $html .= '</li>';
        }
        
        return $html;
    }
    
    /**
     * Render as dropdown item (for child items)
     */
    public function renderDropdownItem(bool $hasAccess, string $restrictedMode = 'hidden'): string {
        if (!$hasAccess && $restrictedMode === 'hidden') {
            return '';
        }
        
        $disabled = !$hasAccess ? 'disabled' : '';
        $tooltip = !$hasAccess ? 'title="Requires ' . ($this->requiredRole ?? 'admin') . ' access"' : '';
        $activeClass = $this->isActive ? 'active' : '';
        
        $html = '<li>';
        $html .= '<a class="dropdown-item ' . $disabled . ' ' . $activeClass . '" href="' . htmlspecialchars($this->url) . '" ' . $tooltip . '>';
        $html .= $this->icon . ' ' . htmlspecialchars($this->title);
        if ($this->badge) {
            $html .= ' <span class="badge ' . $this->badgeClass . '">' . htmlspecialchars($this->badge) . '</span>';
        }
        $html .= '</a>';
        $html .= '</li>';
        
        return $html;
    }
}
