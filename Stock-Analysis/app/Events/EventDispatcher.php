<?php

declare(strict_types=1);

namespace App\Events;

/**
 * Event Dispatcher
 * 
 * Simple event dispatcher for domain events.
 * 
 * @package App\Events
 */
class EventDispatcher
{
    /** @var array<string, array<callable>> */
    private array $listeners = [];
    
    /** @var array Event history for debugging */
    private array $history = [];
    
    private bool $recordHistory;
    
    public function __construct(bool $recordHistory = false)
    {
        $this->recordHistory = $recordHistory;
    }
    
    /**
     * Register event listener
     */
    public function listen(string $event, callable $listener): void
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        
        $this->listeners[$event][] = $listener;
    }
    
    /**
     * Dispatch event to all listeners
     */
    public function dispatch(string $event, $payload = null): void
    {
        if ($this->recordHistory) {
            $this->history[] = [
                'event' => $event,
                'payload' => $payload,
                'timestamp' => microtime(true)
            ];
        }
        
        if (!isset($this->listeners[$event])) {
            return;
        }
        
        foreach ($this->listeners[$event] as $listener) {
            call_user_func($listener, $payload);
        }
    }
    
    /**
     * Remove all listeners for event
     */
    public function forget(string $event): void
    {
        unset($this->listeners[$event]);
    }
    
    /**
     * Remove specific listener
     */
    public function removeListener(string $event, callable $listener): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }
        
        $this->listeners[$event] = array_filter(
            $this->listeners[$event],
            fn($l) => $l !== $listener
        );
    }
    
    /**
     * Check if event has listeners
     */
    public function hasListeners(string $event): bool
    {
        return isset($this->listeners[$event]) && !empty($this->listeners[$event]);
    }
    
    /**
     * Get listener count for event
     */
    public function getListenerCount(string $event): int
    {
        return isset($this->listeners[$event]) ? count($this->listeners[$event]) : 0;
    }
    
    /**
     * Get all registered events
     */
    public function getEvents(): array
    {
        return array_keys($this->listeners);
    }
    
    /**
     * Get event history
     */
    public function getHistory(): array
    {
        return $this->history;
    }
    
    /**
     * Clear event history
     */
    public function clearHistory(): void
    {
        $this->history = [];
    }
    
    /**
     * Clear all listeners
     */
    public function clearAll(): void
    {
        $this->listeners = [];
        $this->history = [];
    }
}
