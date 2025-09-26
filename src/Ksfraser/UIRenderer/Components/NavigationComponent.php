<?php

namespace Ksfraser\UIRenderer\Components;

use Ksfraser\UIRenderer\Contracts\ComponentInterface;
use Ksfraser\UIRenderer\DTOs\NavigationDto;

/**
 * Navigation Component - Now uses centralized NavigationService
 * Follows Single Responsibility Principle by delegating to NavigationService
 */
class NavigationComponent implements ComponentInterface {
    /** @var NavigationDto */
    private $navigationDto;
    /** @var NavigationService */
    private $navigationService;
    
    public function __construct(NavigationDto $navigationDto) {
        $this->navigationDto = $navigationDto;
        
        // Include and use centralized navigation service
        require_once dirname(__DIR__, 4) . '/web_ui/NavigationService.php';
        $this->navigationService = new \NavigationService();
    }
    
    public function toHtml() {
        // Delegate to centralized NavigationService - Single Responsibility Principle
        return $this->navigationService->renderNavigationHeader(
            $this->navigationDto->title,
            $this->navigationDto->currentPage
        );
    }
    

}
