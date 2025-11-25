<?php

namespace App\Core;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * HTTP Response class
 * 
 * Extends Symfony's Response class instead of reinventing the wheel.
 * Provides compatibility layer for existing code while using battle-tested implementation.
 * Follows Single Responsibility Principle (SRP) - delegates to Symfony HTTP Foundation.
 */
class Response extends SymfonyResponse
{
    /**
     * Create JSON response
     */
    public static function json(array $data, int $statusCode = 200): JsonResponse 
    {
        return new JsonResponse($data, $statusCode);
    }
    
    /**
     * Create redirect response
     */
    public static function redirect(string $url, int $statusCode = 302): RedirectResponse 
    {
        return new RedirectResponse($url, $statusCode);
    }
    
    /**
     * Create HTML response
     */
    public static function html(string $content, int $statusCode = 200): self 
    {
        return new self($content, $statusCode, ['Content-Type' => 'text/html']);
    }
    
    /**
     * Compatibility method for setting headers
     */
    public function setHeader(string $key, string $value): self 
    {
        $this->headers->set($key, $value);
        return $this;
    }
    
    /**
     * Compatibility method for getting headers
     */
    public function getHeader(string $key): ?string 
    {
        return $this->headers->get($key);
    }
    
    /**
     * Compatibility method for setting content type
     */
    public function setContentType(string $contentType): self 
    {
        $this->headers->set('Content-Type', $contentType);
        return $this;
    }
}