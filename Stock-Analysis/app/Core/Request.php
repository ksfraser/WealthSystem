<?php

namespace App\Core;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * HTTP Request class
 * 
 * Extends Symfony's Request class instead of reinventing the wheel.
 * Provides compatibility layer for existing code while using battle-tested implementation.
 * Follows Single Responsibility Principle (SRP) - delegates to Symfony HTTP Foundation.
 */
class Request extends SymfonyRequest
{
    /**
     * Create Request from PHP globals
     */
    public static function fromGlobals(): self
    {
        $request = parent::createFromGlobals();
        return self::createFromSymfonyRequest($request);
    }
    
    /**
     * Create from Symfony Request
     */
    public static function createFromSymfonyRequest(SymfonyRequest $symfonyRequest): self
    {
        $request = new self(
            $symfonyRequest->query->all(),
            $symfonyRequest->request->all(),
            $symfonyRequest->attributes->all(),
            $symfonyRequest->cookies->all(),
            $symfonyRequest->files->all(),
            $symfonyRequest->server->all(),
            $symfonyRequest->getContent()
        );
        
        if ($symfonyRequest->hasSession()) {
            $request->setSession($symfonyRequest->getSession());
        }
        
        return $request;
    }
    
    /**
     * Get request URI path (compatibility method)
     */
    public function getUri(): string 
    {
        return $this->getPathInfo();
    }
    
    /**
     * Get GET parameter (compatibility method)
     * Renamed to avoid conflict with parent get() method
     */
    public function getQuery(string $key, $default = null) 
    {
        return $this->query->get($key, $default);
    }
    
    /**
     * Legacy alias for getQuery() - for backward compatibility
     * Note: parent::get() shadows this, so use getQuery() for new code
     */
    public function getParam(string $key, $default = null) 
    {
        return $this->query->get($key, $default);
    }
    
    /**
     * Get POST parameter (compatibility method)
     */
    public function post(string $key, $default = null) 
    {
        return $this->request->get($key, $default);
    }
    
    /**
     * Get all GET parameters (compatibility method)
     */
    public function allGet(): array 
    {
        return $this->query->all();
    }
    
    /**
     * Get all POST parameters (compatibility method)
     */
    public function allPost(): array 
    {
        return $this->request->all();
    }
    
    /**
     * Get uploaded file (compatibility method)
     */
    public function file(string $key) 
    {
        return $this->files->get($key);
    }
    
    /**
     * Get cookie value (compatibility method)
     */
    public function cookie(string $key, $default = null) 
    {
        return $this->cookies->get($key, $default);
    }
    
    /**
     * Get header value (compatibility method)
     */
    public function header(string $key, $default = null) 
    {
        return $this->headers->get($key, $default);
    }
    
    /**
     * Check if request is AJAX (compatibility method)
     */
    public function isAjax(): bool 
    {
        return $this->isXmlHttpRequest();
    }
}