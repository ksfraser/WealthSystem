<?php

namespace Ksfraser\UIRenderer\DTOs;

/**
 * Navigation Data Transfer Object
 * Follows Single Responsibility Principle
 */
class NavigationDto {
    /** @var string */
    public $title;
    /** @var string */
    public $currentPage;
    /** @var array|null */
    public $user;
    /** @var bool */
    public $isAdmin;
    /** @var array */
    public $menuItems;
    /** @var bool */
    public $isAuthenticated;
    
    public function __construct(
        $title = 'Application',
        $currentPage = '',
        $user = null,
        $isAdmin = false,
        $menuItems = [],
        $isAuthenticated = false
    ) {
        $this->title = $title;
        $this->currentPage = $currentPage;
        $this->user = $user;
        $this->isAdmin = $isAdmin;
        $this->menuItems = $menuItems;
        $this->isAuthenticated = $isAuthenticated;
    }
}

/**
 * Card Data Transfer Object
 */
class CardDto {
    /** @var string */
    public $title;
    /** @var string */
    public $content;
    /** @var string */
    public $type;
    /** @var string */
    public $icon;
    /** @var array */
    public $actions;
    
    public function __construct(
        $title,
        $content,
        $type = 'default',
        $icon = '',
        $actions = []
    ) {
        $this->title = $title;
        $this->content = $content;
        $this->type = $type;
        $this->icon = $icon;
        $this->actions = $actions;
    }
}
