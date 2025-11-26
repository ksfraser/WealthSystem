<?php

namespace Ksfraser\UIRenderer\Contracts;

/**
 * Component Interface - For reusable UI components
 * Follows Interface Segregation Principle
 */
interface ComponentInterface {
    /**
     * Convert component to HTML string
     * @return string
     */
    public function toHtml();
}
