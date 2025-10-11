<?php

namespace App\Core\Interfaces;

use App\Core\Request;
use App\Core\Response;

/**
 * Controller Interface
 * 
 * Follows Interface Segregation Principle (ISP) by defining only essential controller methods.
 * All controllers must implement this interface for consistency and testability.
 */
interface ControllerInterface 
{
    /**
     * Handle an incoming request and return a response
     * 
     * @param Request $request The HTTP request object
     * @return Response The HTTP response object
     */
    public function handle(Request $request): Response;
}