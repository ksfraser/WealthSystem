<?php
require_once __DIR__ . '/NavigationItem.php';

/**
 * Dashboard Card
 * Represents a card on the dashboard
 */
class DashboardCard extends NavigationItem {
    private $actions = [];
    private $cardType = 'default';
    private $colorClass = '';
    
    /**
     * @param string $id Unique identifier
     * @param string $title Card title
     * @param string $description Card description
     * @param string $icon Card icon
     * @param string $url Primary action URL
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
        
        // Default action uses the primary URL
        $this->actions[] = [
            'url' => $url,
            'label' => 'View',
            'class' => 'btn-primary'
        ];
    }
    
    /**
     * Add an action button to the card
     */
    public function addAction(string $url, string $label, string $class = 'btn-secondary'): self {
        $this->actions[] = [
            'url' => $url,
            'label' => $label,
            'class' => $class
        ];
        return $this;
    }
    
    /**
     * Clear default action and set custom actions
     */
    public function setActions(array $actions): self {
        $this->actions = $actions;
        return $this;
    }
    
    /**
     * Set card type (default, success, warning, info, danger)
     */
    public function setCardType(string $type): self {
        $this->cardType = $type;
        return $this;
    }
    
    /**
     * Set custom color class
     */
    public function setColorClass(string $class): self {
        $this->colorClass = $class;
        return $this;
    }
    
    /**
     * Get actions array
     */
    public function getActions(): array {
        return $this->actions;
    }
    
    /**
     * Convert to array format expected by CardComponent
     */
    public function toArray(): array {
        $data = parent::toArray();
        $data['actions'] = $this->actions;
        $data['card_type'] = $this->cardType;
        $data['color_class'] = $this->colorClass;
        return $data;
    }
    
    /**
     * Render as dashboard card HTML
     */
    public function render(bool $hasAccess, string $restrictedMode = 'hidden'): string {
        if (!$hasAccess && $restrictedMode === 'hidden') {
            return '';
        }
        
        $disabled = !$hasAccess ? 'opacity: 0.5; cursor: not-allowed;' : '';
        $tooltip = !$hasAccess ? 'title="Requires ' . ($this->requiredRole ?? 'admin') . ' access"' : '';
        
        $html = '<div class="dashboard-card" style="' . $disabled . '" ' . $tooltip . '>';
        $html .= '<h3>' . htmlspecialchars($this->title) . '</h3>';
        $html .= '<p>' . htmlspecialchars($this->description) . '</p>';
        
        if (!empty($this->actions)) {
            $html .= '<div class="card-links">';
            foreach ($this->actions as $action) {
                $btnDisabled = !$hasAccess ? 'style="pointer-events: none; opacity: 0.6;"' : '';
                $html .= '<a href="' . htmlspecialchars($action['url']) . '" ' . $btnDisabled . '>';
                $html .= htmlspecialchars($action['label']);
                $html .= '</a>';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
