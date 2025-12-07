<?php

namespace App\WebSocket;

/**
 * WebSocket Interface
 * 
 * Standard interface for WebSocket connections enabling real-time data streaming.
 * Supports subscription management, message handling, reconnection logic, and
 * connection lifecycle management.
 * 
 * Usage:
 * ```php
 * $ws = new AlphaVantageWebSocket($config, $logger);
 * 
 * $ws->onMessage(function(array $message) {
 *     echo "Price update: {$message['symbol']} @ {$message['price']}\n";
 * });
 * 
 * $ws->connect();
 * $ws->subscribe(['AAPL', 'MSFT', 'GOOGL']);
 * $ws->listen();
 * ```
 */
interface WebSocketInterface
{
    /**
     * Connect to WebSocket server
     * 
     * Establishes connection with appropriate headers and authentication.
     * Implements exponential backoff for connection failures.
     * 
     * @return bool True if connected successfully
     * @throws WebSocketException If connection fails after retries
     */
    public function connect(): bool;

    /**
     * Disconnect from WebSocket server
     * 
     * Gracefully closes connection, unsubscribes from all symbols,
     * and cleans up resources.
     * 
     * @return void
     */
    public function disconnect(): void;

    /**
     * Check if WebSocket is connected
     * 
     * @return bool True if connected and ready
     */
    public function isConnected(): bool;

    /**
     * Subscribe to real-time updates for symbols
     * 
     * @param array $symbols List of symbols to subscribe (e.g., ['AAPL', 'MSFT'])
     * @return bool True if subscription successful
     */
    public function subscribe(array $symbols): bool;

    /**
     * Unsubscribe from symbols
     * 
     * @param array $symbols List of symbols to unsubscribe
     * @return bool True if unsubscription successful
     */
    public function unsubscribe(array $symbols): bool;

    /**
     * Get list of currently subscribed symbols
     * 
     * @return array List of subscribed symbols
     */
    public function getSubscriptions(): array;

    /**
     * Listen for incoming messages
     * 
     * Blocking call that continuously listens for WebSocket messages.
     * Handles heartbeats, reconnection, and error recovery automatically.
     * Call onMessage() before listen() to handle incoming data.
     * 
     * @param int|null $timeout Optional timeout in seconds (null = infinite)
     * @return void
     */
    public function listen(?int $timeout = null): void;

    /**
     * Register callback for incoming messages
     * 
     * Callback receives decoded message array with fields like:
     * - symbol: Stock symbol
     * - price: Current price
     * - volume: Trading volume
     * - timestamp: Unix timestamp
     * 
     * @param callable $callback Function(array $message): void
     * @return void
     */
    public function onMessage(callable $callback): void;

    /**
     * Register callback for connection events
     * 
     * Called when connection state changes (connected, disconnected, reconnecting).
     * 
     * @param callable $callback Function(string $event, array $data): void
     * @return void
     */
    public function onConnection(callable $callback): void;

    /**
     * Register callback for errors
     * 
     * @param callable $callback Function(string $error, \Exception $exception): void
     * @return void
     */
    public function onError(callable $callback): void;

    /**
     * Send message to WebSocket server
     * 
     * @param array $message Message to send (will be JSON encoded)
     * @return bool True if sent successfully
     */
    public function send(array $message): bool;

    /**
     * Get WebSocket connection statistics
     * 
     * Returns metrics like:
     * - messages_received: Total messages received
     * - messages_sent: Total messages sent
     * - reconnections: Number of reconnection attempts
     * - uptime: Connection uptime in seconds
     * - subscriptions: Number of active subscriptions
     * 
     * @return array Statistics
     */
    public function getStats(): array;

    /**
     * Set reconnection strategy
     * 
     * Configure automatic reconnection behavior:
     * - enabled: Enable auto-reconnect (default: true)
     * - max_attempts: Max reconnection attempts (default: 5)
     * - initial_delay: Initial delay in ms (default: 1000)
     * - max_delay: Maximum delay in ms (default: 30000)
     * - backoff_multiplier: Exponential backoff multiplier (default: 2)
     * 
     * @param array $config Reconnection configuration
     * @return void
     */
    public function setReconnectionStrategy(array $config): void;

    /**
     * Enable or disable heartbeat ping/pong
     * 
     * @param bool $enabled Enable heartbeat
     * @param int $interval Heartbeat interval in seconds
     * @return void
     */
    public function setHeartbeat(bool $enabled, int $interval = 30): void;
}
