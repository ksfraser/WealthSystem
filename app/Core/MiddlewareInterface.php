<?php

namespace App\Core\Interfaces;

use App\Core\Request;

/**
 * Middleware Interface
 * 
 * Defines the contract for middleware classes.
 * Follows Single Responsibility Principle (SRP) and Chain of Responsibility pattern.
 */
interface MiddlewareInterface 
{
    /**
     * Process request through middleware
     * 
     * @param Request $request The HTTP request
     * @param callable $next The next middleware in the chain
     * @return mixed The response or result of next middleware
     */
    public function handle(Request $request, callable $next);
}