<?php

namespace App\Core;

/**
 * HTTP Response class
 * 
 * Represents an HTTP response with status, headers, and content.
 * Follows Single Responsibility Principle (SRP) - handles only response data.
 */
class Response 
{
    private string $content;
    private int $statusCode;
    private array $headers;
    
    public function __construct(string $content = '', int $statusCode = 200, array $headers = []) 
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }
    
    /**
     * Set response content
     */
    public function setContent(string $content): self 
    {
        $this->content = $content;
        return $this;
    }
    
    /**
     * Get response content
     */
    public function getContent(): string 
    {
        return $this->content;
    }
    
    /**
     * Set HTTP status code
     */
    public function setStatusCode(int $statusCode): self 
    {
        $this->statusCode = $statusCode;
        return $this;
    }
    
    /**
     * Get HTTP status code
     */
    public function getStatusCode(): int 
    {
        return $this->statusCode;
    }
    
    /**
     * Set header
     */
    public function setHeader(string $key, string $value): self 
    {
        $this->headers[$key] = $value;
        return $this;
    }
    
    /**
     * Get header
     */
    public function getHeader(string $key): ?string 
    {
        return $this->headers[$key] ?? null;
    }
    
    /**
     * Get all headers
     */
    public function getHeaders(): array 
    {
        return $this->headers;
    }
    
    /**
     * Set content type header
     */
    public function setContentType(string $contentType): self 
    {
        return $this->setHeader('Content-Type', $contentType);
    }
    
    /**
     * Send the response to the browser
     */
    public function send(): void 
    {
        // Send status code
        http_response_code($this->statusCode);
        
        // Send headers
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
        
        // Send content
        echo $this->content;
    }
    
    /**
     * Create JSON response
     */
    public static function json(array $data, int $statusCode = 200): self 
    {
        $response = new self(json_encode($data), $statusCode);
        $response->setContentType('application/json');
        return $response;
    }
    
    /**
     * Create redirect response
     */
    public static function redirect(string $url, int $statusCode = 302): self 
    {
        $response = new self('', $statusCode);
        $response->setHeader('Location', $url);
        return $response;
    }
    
    /**
     * Create HTML response
     */
    public static function html(string $content, int $statusCode = 200): self 
    {
        $response = new self($content, $statusCode);
        $response->setContentType('text/html');
        return $response;
    }
}