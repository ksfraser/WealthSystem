<?php

namespace App\Core;

/**
 * HTTP Request class
 * 
 * Represents an HTTP request with all relevant data.
 * Follows Single Responsibility Principle (SRP) - handles only request data.
 */
class Request 
{
    private array $get;
    private array $post;
    private array $server;
    private array $files;
    private array $cookies;
    private string $method;
    private string $uri;
    private array $headers;
    
    public function __construct(
        array $get = null,
        array $post = null, 
        array $server = null,
        array $files = null,
        array $cookies = null
    ) {
        $this->get = $get ?? $_GET;
        $this->post = $post ?? $_POST;
        $this->server = $server ?? $_SERVER;
        $this->files = $files ?? $_FILES;
        $this->cookies = $cookies ?? $_COOKIE;
        
        $this->method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $this->server['REQUEST_URI'] ?? '/';
        $this->headers = $this->parseHeaders();
    }
    
    /**
     * Get request method (GET, POST, PUT, DELETE, etc.)
     */
    public function getMethod(): string 
    {
        return $this->method;
    }
    
    /**
     * Get request URI
     */
    public function getUri(): string 
    {
        return parse_url($this->uri, PHP_URL_PATH) ?? '/';
    }
    
    /**
     * Get GET parameter
     */
    public function get(string $key, $default = null) 
    {
        return $this->get[$key] ?? $default;
    }
    
    /**
     * Get POST parameter
     */
    public function post(string $key, $default = null) 
    {
        return $this->post[$key] ?? $default;
    }
    
    /**
     * Get all GET parameters
     */
    public function allGet(): array 
    {
        return $this->get;
    }
    
    /**
     * Get all POST parameters
     */
    public function allPost(): array 
    {
        return $this->post;
    }
    
    /**
     * Get uploaded file
     */
    public function file(string $key): ?array 
    {
        return $this->files[$key] ?? null;
    }
    
    /**
     * Get cookie value
     */
    public function cookie(string $key, $default = null) 
    {
        return $this->cookies[$key] ?? $default;
    }
    
    /**
     * Get header value
     */
    public function header(string $key, $default = null) 
    {
        return $this->headers[strtolower($key)] ?? $default;
    }
    
    /**
     * Check if request is AJAX
     */
    public function isAjax(): bool 
    {
        return strtolower($this->header('x-requested-with', '')) === 'xmlhttprequest';
    }
    
    /**
     * Check if request method matches
     */
    public function isMethod(string $method): bool 
    {
        return $this->method === strtoupper($method);
    }
    
    /**
     * Parse HTTP headers from server variables
     */
    private function parseHeaders(): array 
    {
        $headers = [];
        
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$header] = $value;
            }
        }
        
        return $headers;
    }
}