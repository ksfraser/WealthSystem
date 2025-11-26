<?php

namespace Ksfraser\UIRenderer\DTOs;

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
