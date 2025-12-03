<?php
require_once __DIR__ . '/../Models/NavigationItem.php';

/**
 * Breadcrumb Item
 * Represents a breadcrumb link in the navigation trail
 */
class BreadcrumbItem extends NavigationItem {
    private $isLast = false;
    
    /**
     * Mark this as the last breadcrumb (current page)
     */
    public function setIsLast(bool $isLast): self {
        $this->isLast = $isLast;
        return $this;
    }
    
    /**
     * Check if this is the last breadcrumb
     */
    public function isLast(): bool {
        return $this->isLast;
    }
    
    /**
     * Render as breadcrumb HTML
     */
    public function render(bool $hasAccess, string $restrictedMode = 'hidden'): string {
        if (!$hasAccess && $restrictedMode === 'hidden') {
            return '';
        }
        
        $disabled = !$hasAccess ? 'style="opacity: 0.5; pointer-events: none;"' : '';
        $activeClass = $this->isLast ? 'active' : '';
        
        if ($this->isLast) {
            // Last breadcrumb is not a link
            $html = '<li class="breadcrumb-item ' . $activeClass . '" aria-current="page">';
            $html .= htmlspecialchars($this->icon) . ' ' . htmlspecialchars($this->title);
            $html .= '</li>';
        } else {
            // Regular breadcrumb link
            $html = '<li class="breadcrumb-item">';
            $html .= '<a href="' . htmlspecialchars($this->url) . '" ' . $disabled . '>';
            $html .= htmlspecialchars($this->icon) . ' ' . htmlspecialchars($this->title);
            $html .= '</a>';
            $html .= '</li>';
        }
        
        return $html;
    }
}
