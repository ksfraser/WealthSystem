<?php

declare(strict_types=1);

namespace App\WebSocket;

/**
 * WebSocket Message Handler
 * 
 * Handles different types of WebSocket messages.
 * 
 * @package App\WebSocket
 */
class MessageHandler
{
    private array $handlers = [];
    
    /**
     * Register message handler
     */
    public function register(string $type, callable $handler): void
    {
        $this->handlers[$type] = $handler;
    }
    
    /**
     * Handle incoming message
     */
    public function handle(array $message): void
    {
        $type = $message['type'] ?? 'unknown';
        
        if (isset($this->handlers[$type])) {
            call_user_func($this->handlers[$type], $message);
        } else if (isset($this->handlers['default'])) {
            call_user_func($this->handlers['default'], $message);
        }
    }
    
    /**
     * Check if handler exists for type
     */
    public function hasHandler(string $type): bool
    {
        return isset($this->handlers[$type]);
    }
    
    /**
     * Get all registered types
     */
    public function getRegisteredTypes(): array
    {
        return array_keys($this->handlers);
    }
    
    /**
     * Remove handler
     */
    public function unregister(string $type): void
    {
        unset($this->handlers[$type]);
    }
}
