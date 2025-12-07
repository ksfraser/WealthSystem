<?php

namespace App\Services;

/**
 * Event Dispatcher
 * 
 * Simple event system for real-time notifications and decoupled communication.
 * Allows components to listen for and dispatch events without direct dependencies.
 * 
 * Features:
 * - Event registration and dispatching
 * - Multiple listeners per event
 * - Event data passing
 * - Wildcard listeners (*) for all events
 * - Priority-based execution
 * - One-time listeners
 * 
 * Example:
 * ```php
 * $dispatcher = new EventDispatcher();
 * 
 * // Register listener
 * $dispatcher->on('price.update', function($data) {
 *     echo "Price: {$data['symbol']} @ {$data['price']}\n";
 * });
 * 
 * // Dispatch event
 * $dispatcher->dispatch('price.update', [
 *     'symbol' => 'AAPL',
 *     'price' => 150.00,
 * ]);
 * ```
 */
class EventDispatcher
{
    private array $listeners = [];
    private int $dispatchCount = 0;
    private array $eventCounts = [];

    /**
     * Register event listener
     * 
     * @param string $event Event name (use '*' for all events)
     * @param callable $callback Listener callback
     * @param array $options Options (priority, once)
     * @return string Listener ID for removal
     */
    public function on(string $event, callable $callback, array $options = []): string
    {
        $listenerId = uniqid('listener_', true);

        $listener = [
            'id' => $listenerId,
            'callback' => $callback,
            'priority' => $options['priority'] ?? 0,
            'once' => $options['once'] ?? false,
        ];

        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = $listener;

        // Sort by priority (higher = first)
        usort($this->listeners[$event], fn($a, $b) => $b['priority'] <=> $a['priority']);

        return $listenerId;
    }

    /**
     * Register one-time listener
     * 
     * @param string $event Event name
     * @param callable $callback Listener callback
     * @param int $priority Priority (higher = first)
     * @return string Listener ID
     */
    public function once(string $event, callable $callback, int $priority = 0): string
    {
        return $this->on($event, $callback, ['once' => true, 'priority' => $priority]);
    }

    /**
     * Remove event listener
     * 
     * @param string $listenerId Listener ID from on()
     * @return bool True if removed
     */
    public function off(string $listenerId): bool
    {
        foreach ($this->listeners as $event => &$listeners) {
            foreach ($listeners as $index => $listener) {
                if ($listener['id'] === $listenerId) {
                    unset($listeners[$index]);
                    $listeners = array_values($listeners);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Remove all listeners for event
     * 
     * @param string $event Event name
     * @return int Number of listeners removed
     */
    public function removeAllListeners(string $event): int
    {
        $count = count($this->listeners[$event] ?? []);
        unset($this->listeners[$event]);
        return $count;
    }

    /**
     * Dispatch event to all listeners
     * 
     * @param string $event Event name
     * @param array $data Event data
     * @return int Number of listeners called
     */
    public function dispatch(string $event, array $data = []): int
    {
        $this->dispatchCount++;

        if (!isset($this->eventCounts[$event])) {
            $this->eventCounts[$event] = 0;
        }
        $this->eventCounts[$event]++;

        $called = 0;

        // Call specific event listeners
        if (isset($this->listeners[$event])) {
            $called += $this->callListeners($event, $this->listeners[$event], $data);
        }

        // Call wildcard listeners
        if (isset($this->listeners['*'])) {
            $data['event'] = $event; // Add event name to wildcard data
            $called += $this->callListeners('*', $this->listeners['*'], $data);
        }

        return $called;
    }

    /**
     * Call event listeners
     * 
     * @param string $event Event name
     * @param array $listeners Listeners array
     * @param array $data Event data
     * @return int Number of listeners called
     */
    private function callListeners(string $event, array &$listeners, array $data): int
    {
        $called = 0;
        $toRemove = [];

        foreach ($listeners as $index => $listener) {
            try {
                $listener['callback']($data);
                $called++;

                // Mark one-time listeners for removal
                if ($listener['once']) {
                    $toRemove[] = $index;
                }

            } catch (\Exception $e) {
                // Log error but continue executing other listeners
                error_log("Event listener error for '{$event}': " . $e->getMessage());
            }
        }

        // Remove one-time listeners
        foreach (array_reverse($toRemove) as $index) {
            unset($listeners[$index]);
        }

        if (!empty($toRemove)) {
            $listeners = array_values($listeners);
        }

        return $called;
    }

    /**
     * Check if event has listeners
     * 
     * @param string $event Event name
     * @return bool True if has listeners
     */
    public function hasListeners(string $event): bool
    {
        return !empty($this->listeners[$event]) || !empty($this->listeners['*']);
    }

    /**
     * Get number of listeners for event
     * 
     * @param string $event Event name (use '*' for wildcard count)
     * @return int Number of listeners
     */
    public function getListenerCount(string $event): int
    {
        return count($this->listeners[$event] ?? []);
    }

    /**
     * Get all registered events
     * 
     * @return array Event names
     */
    public function getEvents(): array
    {
        return array_keys($this->listeners);
    }

    /**
     * Get dispatcher statistics
     * 
     * @return array Statistics
     */
    public function getStats(): array
    {
        $totalListeners = 0;
        foreach ($this->listeners as $listeners) {
            $totalListeners += count($listeners);
        }

        return [
            'total_dispatches' => $this->dispatchCount,
            'total_listeners' => $totalListeners,
            'events_registered' => count($this->listeners),
            'event_counts' => $this->eventCounts,
            'most_dispatched' => $this->getMostDispatchedEvent(),
        ];
    }

    /**
     * Get most frequently dispatched event
     * 
     * @return array|null [event, count] or null
     */
    private function getMostDispatchedEvent(): ?array
    {
        if (empty($this->eventCounts)) {
            return null;
        }

        $max = max($this->eventCounts);
        $event = array_search($max, $this->eventCounts);

        return ['event' => $event, 'count' => $max];
    }

    /**
     * Clear all listeners
     * 
     * @return void
     */
    public function clear(): void
    {
        $this->listeners = [];
        $this->dispatchCount = 0;
        $this->eventCounts = [];
    }

    /**
     * Create event listener chain
     * 
     * Allows chaining multiple listeners with data transformation:
     * ```php
     * $chain = $dispatcher->chain('price.update')
     *     ->then(fn($data) => array_merge($data, ['processed' => true]))
     *     ->then(fn($data) => $this->saveToDatabase($data))
     *     ->catch(fn($error) => $this->logger->error($error));
     * ```
     * 
     * @param string $event Event name
     * @return EventListenerChain
     */
    public function chain(string $event): EventListenerChain
    {
        return new EventListenerChain($this, $event);
    }
}

/**
 * Event Listener Chain
 * 
 * Helper class for chaining event listeners with data transformation.
 */
class EventListenerChain
{
    private array $steps = [];
    private ?callable $errorHandler = null;

    public function __construct(
        private readonly EventDispatcher $dispatcher,
        private readonly string $event
    ) {
    }

    /**
     * Add step to chain
     * 
     * @param callable $callback Callback that receives and returns data
     * @return self
     */
    public function then(callable $callback): self
    {
        $this->steps[] = $callback;
        return $this;
    }

    /**
     * Add error handler
     * 
     * @param callable $callback Error handler
     * @return self
     */
    public function catch(callable $callback): self
    {
        $this->errorHandler = $callback;
        return $this;
    }

    /**
     * Execute chain when event dispatched
     * 
     * @return string Listener ID
     */
    public function execute(): string
    {
        return $this->dispatcher->on($this->event, function (array $data) {
            try {
                $result = $data;

                foreach ($this->steps as $step) {
                    $result = $step($result);
                    
                    // Stop if step returns false
                    if ($result === false) {
                        break;
                    }
                }

            } catch (\Exception $e) {
                if ($this->errorHandler) {
                    ($this->errorHandler)($e);
                } else {
                    throw $e;
                }
            }
        });
    }
}
