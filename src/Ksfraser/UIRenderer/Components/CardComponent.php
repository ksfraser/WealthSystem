<?php

namespace Ksfraser\UIRenderer\Components;

use Ksfraser\UIRenderer\Contracts\ComponentInterface;
use Ksfraser\UIRenderer\DTOs\CardDto;

/**
 * Card Component - Renders content cards
 * Follows Single Responsibility Principle
 */
class CardComponent implements ComponentInterface {
    /** @var CardDto */
    private $cardDto;
    
    public function __construct(CardDto $cardDto) {
        $this->cardDto = $cardDto;
    }
    
    public function toHtml() {
        $type = isset($this->cardDto->type) ? htmlspecialchars($this->cardDto->type, ENT_QUOTES, 'UTF-8') : '';
        $title = isset($this->cardDto->title) ? htmlspecialchars($this->cardDto->title, ENT_QUOTES, 'UTF-8') : '';
        $iconHtml = $this->cardDto->icon ? "<span class='card-icon' aria-hidden='true'>{$this->cardDto->icon}</span> " : '';
        $actionsHtml = $this->renderActions();
        $content = $this->cardDto->content;
        // ARIA role and label for accessibility
        return "
        <div class='card {$type}' role='region' aria-label='{$title}'>
            <h3>{$iconHtml}{$title}</h3>
            <div class='card-content'>
                {$content}
            </div>
            {$actionsHtml}
        </div>";
    }
    
    private function renderActions() {
        if (empty($this->cardDto->actions)) {
            return '';
        }
        
        $actions = [];
        foreach ($this->cardDto->actions as $action) {
            $class = isset($action['class']) ? " {$action['class']}" : '';
            $actions[] = "<a href='{$action['url']}' class='btn{$class}'>{$action['label']}</a>";
        }
        
        return "<div class='card-actions'>" . implode('', $actions) . "</div>";
    }
}
