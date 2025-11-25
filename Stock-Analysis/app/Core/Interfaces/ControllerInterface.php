<?php

namespace App\Core\Interfaces;

use App\Core\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller Interface
 * 
 * Follows Interface Segregation Principle (ISP) by defining only essential controller methods.
 * All controllers must implement this interface for consistency and testability.
 * 
 * Uses Symfony Response as return type to allow Response, JsonResponse, RedirectResponse, etc.
 */
interface ControllerInterface 
{
    /**
     * Handle an incoming request and return a response
     * 
     * @param Request $request The HTTP request object
     * @return Response The HTTP response object (Symfony base class)
     */
    public function handle(Request $request): Response;
}