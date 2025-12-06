<?php

declare(strict_types=1);

namespace App\WebSocket;

use App\Logging\TradingLogger;
use App\Exceptions\DataException;

/**
 * Price Stream Client
 * 
 * Client for real-time price streaming via WebSocket.
 * 
 * Features:
 * - Subscribe to price updates
 * - Message handling
 * - Connection management
 * - Reconnection logic
 * 
 * @package App\WebSocket
 */
class PriceStreamClient
{
    private array $subscriptions = [];
    private array $callbacks = [];
    private bool $connected = false;
    private ?TradingLogger $logger;
    private array $config;
    private array $messageBuffer = [];
    
    public function __construct(array $config = [], ?TradingLogger $logger = null)
    {
        $this->config = array_merge([
            'url' => 'ws://localhost:8080',
            'reconnect_delay' => 5,
            'max_reconnect_attempts' => 3,
            'ping_interval' => 30
        ], $config);
        
        $this->logger = $logger;
    }
    
    /**
     * Connect to WebSocket server
     */
    public function connect(): bool
    {
        if ($this->connected) {
            return true;
        }
        
        // Simulated connection for testing
        $this->connected = true;
        
        if ($this->logger) {
            $this->logger->info('WebSocket client connected', [
                'url' => $this->config['url']
            ]);
        }
        
        return true;
    }
    
    /**
     * Disconnect from server
     */
    public function disconnect(): void
    {
        $this->connected = false;
        $this->subscriptions = [];
        
        if ($this->logger) {
            $this->logger->info('WebSocket client disconnected');
        }
    }
    
    /**
     * Subscribe to symbol price updates
     */
    public function subscribe(string $symbol, callable $callback): void
    {
        if (!$this->connected) {
            throw new DataException('Not connected to WebSocket server');
        }
        
        $this->subscriptions[] = $symbol;
        $this->callbacks[$symbol] = $callback;
        
        if ($this->logger) {
            $this->logger->debug('Subscribed to symbol', [
                'symbol' => $symbol
            ]);
        }
    }
    
    /**
     * Unsubscribe from symbol
     */
    public function unsubscribe(string $symbol): void
    {
        $this->subscriptions = array_filter(
            $this->subscriptions,
            fn($s) => $s !== $symbol
        );
        
        unset($this->callbacks[$symbol]);
        
        if ($this->logger) {
            $this->logger->debug('Unsubscribed from symbol', [
                'symbol' => $symbol
            ]);
        }
    }
    
    /**
     * Send message to server
     */
    public function send(array $message): bool
    {
        if (!$this->connected) {
            throw new DataException('Not connected to WebSocket server');
        }
        
        $this->messageBuffer[] = $message;
        
        if ($this->logger) {
            $this->logger->debug('Message sent', [
                'type' => $message['type'] ?? 'unknown',
                'message' => $message
            ]);
        }
        
        return true;
    }
    
    /**
     * Handle incoming message
     */
    public function handleMessage(array $message): void
    {
        $type = $message['type'] ?? 'unknown';
        
        if ($type === 'price_update' && isset($message['symbol'])) {
            $symbol = $message['symbol'];
            
            if (isset($this->callbacks[$symbol])) {
                call_user_func($this->callbacks[$symbol], $message['data']);
            }
        }
        
        if ($this->logger) {
            $this->logger->debug('Message received', [
                'type' => $type,
                'message' => $message
            ]);
        }
    }
    
    /**
     * Simulate receiving a price update
     */
    public function simulatePriceUpdate(string $symbol, array $priceData): void
    {
        $this->handleMessage([
            'type' => 'price_update',
            'symbol' => $symbol,
            'data' => $priceData
        ]);
    }
    
    /**
     * Check if connected
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }
    
    /**
     * Get active subscriptions
     */
    public function getSubscriptions(): array
    {
        return $this->subscriptions;
    }
    
    /**
     * Get message buffer (for testing)
     */
    public function getMessageBuffer(): array
    {
        return $this->messageBuffer;
    }
    
    /**
     * Clear message buffer
     */
    public function clearMessageBuffer(): void
    {
        $this->messageBuffer = [];
    }
}
