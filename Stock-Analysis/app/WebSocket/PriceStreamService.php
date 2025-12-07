<?php

namespace App\WebSocket;

use App\Services\EventDispatcher;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Price Stream Service
 * 
 * Manages real-time stock price streaming with event dispatching,
 * price change detection, and subscription management.
 * 
 * Features:
 * - Real-time price updates via WebSocket
 * - Price change detection (percentage and absolute)
 * - Alert triggering for price thresholds
 * - Event dispatching for price updates
 * - Last known price tracking
 * - Statistics and monitoring
 * 
 * Events dispatched:
 * - price.update: Every price update
 * - price.change: When price changes
 * - price.alert: When price crosses threshold
 * - price.spike: Significant price movement (>5%)
 * 
 * Example:
 * ```php
 * $stream = new PriceStreamService($websocket, $dispatcher, $logger);
 * 
 * // Listen for price updates
 * $dispatcher->on('price.update', function($event) {
 *     echo "{$event['symbol']}: ${$event['price']}\n";
 * });
 * 
 * // Set price alerts
 * $stream->setAlert('AAPL', [
 *     'above' => 200.00,
 *     'below' => 150.00,
 * ]);
 * 
 * $stream->start(['AAPL', 'MSFT', 'GOOGL']);
 * ```
 */
class PriceStreamService
{
    private array $lastPrices = [];
    private array $alerts = [];
    private array $priceHistory = [];
    private bool $running = false;

    // Statistics
    private int $updatesReceived = 0;
    private int $changesDetected = 0;
    private int $alertsTriggered = 0;
    private int $startTime = 0;

    // Configuration
    private float $minChangePercent = 0.01; // 0.01% minimum change to dispatch
    private int $maxHistorySize = 1000; // Per symbol
    private bool $trackHistory = true;

    public function __construct(
        private readonly WebSocketInterface $websocket,
        private readonly EventDispatcher $dispatcher,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * Start streaming prices for symbols
     * 
     * @param array $symbols Symbols to stream
     * @param array $options Options (timeout, etc.)
     * @return void
     */
    public function start(array $symbols, array $options = []): void
    {
        if ($this->running) {
            $this->logger->warning('Price stream already running');
            return;
        }

        $this->logger->info('Starting price stream for: ' . implode(', ', $symbols));
        $this->running = true;
        $this->startTime = time();

        // Register message handler
        $this->websocket->onMessage(function (array $message) {
            $this->handlePriceUpdate($message);
        });

        // Register error handler
        $this->websocket->onError(function (string $error, \Exception $e) {
            $this->logger->error("WebSocket error: {$error} - {$e->getMessage()}");
            $this->dispatcher->dispatch('price.error', [
                'error' => $error,
                'message' => $e->getMessage(),
                'timestamp' => time(),
            ]);
        });

        // Connect and subscribe
        try {
            if (!$this->websocket->isConnected()) {
                $this->websocket->connect();
            }

            $this->websocket->subscribe($symbols);

            // Start listening (blocking)
            $timeout = $options['timeout'] ?? null;
            $this->websocket->listen($timeout);

        } catch (\Exception $e) {
            $this->logger->error('Stream error: ' . $e->getMessage());
            $this->running = false;
            throw $e;
        }

        $this->running = false;
        $this->logger->info('Price stream stopped');
    }

    /**
     * Stop streaming
     * 
     * @return void
     */
    public function stop(): void
    {
        if (!$this->running) {
            return;
        }

        $this->logger->info('Stopping price stream');
        $this->running = false;
        $this->websocket->disconnect();
    }

    /**
     * Handle incoming price update
     * 
     * @param array $message WebSocket message
     * @return void
     */
    private function handlePriceUpdate(array $message): void
    {
        // Extract price data from message
        $symbol = $message['symbol'] ?? null;
        $price = $message['price'] ?? null;
        $volume = $message['volume'] ?? null;
        $timestamp = $message['timestamp'] ?? time();

        if ($symbol === null || $price === null) {
            $this->logger->debug('Invalid price update message');
            return;
        }

        $this->updatesReceived++;

        // Get last known price
        $lastPrice = $this->lastPrices[$symbol] ?? null;

        // Calculate change
        $change = null;
        $changePercent = null;
        if ($lastPrice !== null) {
            $change = $price - $lastPrice;
            $changePercent = ($change / $lastPrice) * 100;
        }

        // Update last price
        $this->lastPrices[$symbol] = $price;

        // Add to history
        if ($this->trackHistory) {
            $this->addToHistory($symbol, [
                'price' => $price,
                'volume' => $volume,
                'timestamp' => $timestamp,
            ]);
        }

        // Build event data
        $eventData = [
            'symbol' => $symbol,
            'price' => $price,
            'volume' => $volume,
            'timestamp' => $timestamp,
            'last_price' => $lastPrice,
            'change' => $change,
            'change_percent' => $changePercent,
        ];

        // Dispatch price.update event (always)
        $this->dispatcher->dispatch('price.update', $eventData);

        // Detect changes
        if ($change !== null && abs($changePercent) >= $this->minChangePercent) {
            $this->changesDetected++;
            $this->dispatcher->dispatch('price.change', $eventData);

            // Detect significant spikes
            if (abs($changePercent) >= 5.0) {
                $this->logger->warning("Price spike detected: {$symbol} {$changePercent}%");
                $this->dispatcher->dispatch('price.spike', array_merge($eventData, [
                    'spike_percent' => $changePercent,
                ]));
            }
        }

        // Check alerts
        $this->checkAlerts($symbol, $price, $eventData);

        $this->logger->debug("Price update: {$symbol} \${$price} ({$changePercent}%)");
    }

    /**
     * Add price to history
     * 
     * @param string $symbol Symbol
     * @param array $data Price data
     * @return void
     */
    private function addToHistory(string $symbol, array $data): void
    {
        if (!isset($this->priceHistory[$symbol])) {
            $this->priceHistory[$symbol] = [];
        }

        $this->priceHistory[$symbol][] = $data;

        // Limit history size
        if (count($this->priceHistory[$symbol]) > $this->maxHistorySize) {
            array_shift($this->priceHistory[$symbol]);
        }
    }

    /**
     * Set price alert for symbol
     * 
     * @param string $symbol Symbol
     * @param array $conditions Alert conditions (above, below, change_percent)
     * @return void
     */
    public function setAlert(string $symbol, array $conditions): void
    {
        if (!isset($this->alerts[$symbol])) {
            $this->alerts[$symbol] = [];
        }

        $this->alerts[$symbol][] = $conditions;
        $this->logger->info("Alert set for {$symbol}: " . json_encode($conditions));
    }

    /**
     * Check if price triggers any alerts
     * 
     * @param string $symbol Symbol
     * @param float $price Current price
     * @param array $eventData Event data
     * @return void
     */
    private function checkAlerts(string $symbol, float $price, array $eventData): void
    {
        if (!isset($this->alerts[$symbol])) {
            return;
        }

        foreach ($this->alerts[$symbol] as $index => $conditions) {
            $triggered = false;
            $reason = null;

            // Check 'above' condition
            if (isset($conditions['above']) && $price >= $conditions['above']) {
                $triggered = true;
                $reason = "price_above_{$conditions['above']}";
            }

            // Check 'below' condition
            if (isset($conditions['below']) && $price <= $conditions['below']) {
                $triggered = true;
                $reason = "price_below_{$conditions['below']}";
            }

            // Check 'change_percent' condition
            if (isset($conditions['change_percent']) && isset($eventData['change_percent'])) {
                if (abs($eventData['change_percent']) >= abs($conditions['change_percent'])) {
                    $triggered = true;
                    $reason = "change_percent_{$conditions['change_percent']}";
                }
            }

            if ($triggered) {
                $this->alertsTriggered++;

                $this->logger->warning("Alert triggered: {$symbol} - {$reason}");
                $this->dispatcher->dispatch('price.alert', array_merge($eventData, [
                    'alert_reason' => $reason,
                    'alert_conditions' => $conditions,
                ]));

                // Remove one-time alerts
                if ($conditions['once'] ?? false) {
                    unset($this->alerts[$symbol][$index]);
                }
            }
        }

        // Re-index array
        $this->alerts[$symbol] = array_values($this->alerts[$symbol]);
    }

    /**
     * Clear alert for symbol
     * 
     * @param string $symbol Symbol
     * @return void
     */
    public function clearAlerts(string $symbol): void
    {
        unset($this->alerts[$symbol]);
        $this->logger->info("Alerts cleared for {$symbol}");
    }

    /**
     * Get last known price for symbol
     * 
     * @param string $symbol Symbol
     * @return float|null Last price or null
     */
    public function getLastPrice(string $symbol): ?float
    {
        return $this->lastPrices[$symbol] ?? null;
    }

    /**
     * Get price history for symbol
     * 
     * @param string $symbol Symbol
     * @param int|null $limit Max records to return
     * @return array Price history
     */
    public function getHistory(string $symbol, ?int $limit = null): array
    {
        $history = $this->priceHistory[$symbol] ?? [];

        if ($limit !== null) {
            $history = array_slice($history, -$limit);
        }

        return $history;
    }

    /**
     * Get all last known prices
     * 
     * @return array Symbol => price map
     */
    public function getAllPrices(): array
    {
        return $this->lastPrices;
    }

    /**
     * Subscribe to additional symbols
     * 
     * @param array $symbols Symbols to add
     * @return bool Success
     */
    public function subscribe(array $symbols): bool
    {
        $this->logger->info('Subscribing to: ' . implode(', ', $symbols));
        return $this->websocket->subscribe($symbols);
    }

    /**
     * Unsubscribe from symbols
     * 
     * @param array $symbols Symbols to remove
     * @return bool Success
     */
    public function unsubscribe(array $symbols): bool
    {
        $this->logger->info('Unsubscribing from: ' . implode(', ', $symbols));

        // Clear alerts for unsubscribed symbols
        foreach ($symbols as $symbol) {
            $this->clearAlerts($symbol);
        }

        return $this->websocket->unsubscribe($symbols);
    }

    /**
     * Get streaming statistics
     * 
     * @return array Statistics
     */
    public function getStats(): array
    {
        $uptime = $this->running ? time() - $this->startTime : 0;

        return [
            'running' => $this->running,
            'uptime' => $uptime,
            'updates_received' => $this->updatesReceived,
            'changes_detected' => $this->changesDetected,
            'alerts_triggered' => $this->alertsTriggered,
            'symbols_tracked' => count($this->lastPrices),
            'alerts_active' => count($this->alerts),
            'updates_per_second' => $uptime > 0 ? round($this->updatesReceived / $uptime, 2) : 0,
            'websocket_stats' => $this->websocket->getStats(),
        ];
    }

    /**
     * Set minimum change percentage to dispatch events
     * 
     * @param float $percent Minimum change percentage (0.01 = 0.01%)
     * @return void
     */
    public function setMinChangePercent(float $percent): void
    {
        $this->minChangePercent = $percent;
        $this->logger->debug("Minimum change percent set to {$percent}%");
    }

    /**
     * Enable or disable price history tracking
     * 
     * @param bool $enabled Enable tracking
     * @param int $maxSize Maximum history size per symbol
     * @return void
     */
    public function setHistoryTracking(bool $enabled, int $maxSize = 1000): void
    {
        $this->trackHistory = $enabled;
        $this->maxHistorySize = $maxSize;
        $this->logger->debug("History tracking: " . ($enabled ? "enabled (max {$maxSize})" : "disabled"));
    }

    /**
     * Check if stream is running
     * 
     * @return bool True if running
     */
    public function isRunning(): bool
    {
        return $this->running;
    }
}
