# WebSocket Real-Time Streaming Guide

Complete guide to real-time stock price streaming using WebSocket connections.

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Quick Start](#quick-start)
4. [WebSocket Interface](#websocket-interface)
5. [Alpha Vantage WebSocket Client](#alpha-vantage-websocket-client)
6. [Price Stream Service](#price-stream-service)
7. [Event Dispatcher](#event-dispatcher)
8. [Real-Time Monitoring](#real-time-monitoring)
9. [Production Setup](#production-setup)
10. [Troubleshooting](#troubleshooting)

## Overview

### Why WebSocket Streaming?

Traditional REST API polling is inefficient and slow:

| Method | Latency | Updates/sec | Efficiency |
|--------|---------|-------------|------------|
| **REST Polling (5s)** | 5000ms | 0.2 | Very Low |
| **REST Polling (1s)** | 1000ms | 1.0 | Low |
| **WebSocket Streaming** | < 50ms | 10-100+ | Very High |

### Benefits

- **Real-Time Updates**: Sub-second price updates
- **Efficient**: Single persistent connection vs repeated HTTP requests
- **Lower Latency**: No HTTP overhead on each update
- **Bandwidth Savings**: Server pushes only changes
- **Alert Support**: Immediate notification of price movements

### Architecture

```
┌──────────────┐
│ WebSocket    │
│ Server       │◄───────┐
│ (Provider)   │        │
└──────────────┘        │ Persistent
        │               │ Connection
        │ Push Updates  │
        ▼               │
┌──────────────────────┐
│ AlphaVantageWebSocket│
│ (Client)             │
└──────────────────────┘
        │
        ▼
┌──────────────────────┐
│ PriceStreamService   │
│ - Change Detection   │
│ - Alert Management   │
│ - History Tracking   │
└──────────────────────┘
        │
        ▼
┌──────────────────────┐
│ EventDispatcher      │
│ - price.update       │
│ - price.change       │
│ - price.alert        │
│ - price.spike        │
└──────────────────────┘
        │
        ▼
┌──────────────────────┐
│ Your Application     │
│ - Portfolio Monitor  │
│ - Trading Alerts     │
│ - Live Charts        │
└──────────────────────┘
```

## Installation

### 1. PHP Sockets Extension

The sockets extension is usually enabled by default. Check if it's available:

```bash
php -m | grep sockets
```

If not available, enable in `php.ini`:

```ini
extension=sockets
```

### 2. WebSocket Provider

Choose a WebSocket data provider:

#### Twelve Data (Recommended)
- **Endpoint**: `wss://ws.twelvedata.com/v1/quotes/price`
- **Free Tier**: Real-time quotes for US stocks
- **Documentation**: https://twelvedata.com/docs#websocket

#### IEX Cloud
- **Endpoint**: `wss://cloud.iexapis.com/stable/stocksUS`
- **Free Tier**: Limited real-time quotes
- **Documentation**: https://iexcloud.io/docs/api/#sse-streaming

#### Alpha Vantage
- **Note**: Check current documentation for WebSocket availability
- **Documentation**: https://www.alphavantage.co/documentation/

### 3. No Additional Dependencies

All WebSocket functionality uses PHP's built-in sockets extension.

## Quick Start

### Basic WebSocket Connection

```php
<?php

use App\WebSocket\AlphaVantageWebSocket;
use Psr\Log\NullLogger;

$websocket = new AlphaVantageWebSocket([
    'api_key' => 'YOUR_API_KEY',
    'endpoint' => 'wss://ws.twelvedata.com/v1/quotes/price',
], new NullLogger());

// Register message handler
$websocket->onMessage(function (array $message) {
    echo "Price update: {$message['symbol']} @ {$message['price']}\n";
});

// Connect and subscribe
$websocket->connect();
$websocket->subscribe(['AAPL', 'MSFT', 'GOOGL']);

// Listen for updates (blocking)
$websocket->listen(300); // Listen for 5 minutes
```

### Price Streaming with Events

```php
<?php

use App\WebSocket\AlphaVantageWebSocket;
use App\WebSocket\PriceStreamService;
use App\Services\EventDispatcher;

$websocket = new AlphaVantageWebSocket($config, $logger);
$dispatcher = new EventDispatcher();
$stream = new PriceStreamService($websocket, $dispatcher, $logger);

// Listen for price updates
$dispatcher->on('price.update', function ($data) {
    echo "{$data['symbol']}: ${$data['price']} ";
    echo "({$data['change_percent']}%)\n";
});

// Set price alert
$stream->setAlert('AAPL', [
    'above' => 200.00,
    'below' => 150.00,
]);

// Start streaming
$stream->start(['AAPL', 'MSFT', 'GOOGL']);
```

## WebSocket Interface

### Standard Interface

```php
interface WebSocketInterface
{
    public function connect(): bool;
    public function disconnect(): void;
    public function isConnected(): bool;
    
    public function subscribe(array $symbols): bool;
    public function unsubscribe(array $symbols): bool;
    public function getSubscriptions(): array;
    
    public function listen(?int $timeout = null): void;
    public function send(array $message): bool;
    
    public function onMessage(callable $callback): void;
    public function onConnection(callable $callback): void;
    public function onError(callable $callback): void;
    
    public function getStats(): array;
    public function setReconnectionStrategy(array $config): void;
    public function setHeartbeat(bool $enabled, int $interval = 30): void;
}
```

### Connection Lifecycle

```php
// 1. Create client
$ws = new AlphaVantageWebSocket($config, $logger);

// 2. Configure (optional)
$ws->setReconnectionStrategy([
    'enabled' => true,
    'max_attempts' => 5,
    'initial_delay' => 1000,
    'max_delay' => 30000,
    'backoff_multiplier' => 2,
]);

$ws->setHeartbeat(true, 30);

// 3. Register callbacks
$ws->onMessage(fn($msg) => handleMessage($msg));
$ws->onConnection(fn($event, $data) => logConnection($event));
$ws->onError(fn($error, $e) => handleError($error, $e));

// 4. Connect
$ws->connect();

// 5. Subscribe
$ws->subscribe(['AAPL', 'MSFT']);

// 6. Listen
$ws->listen(); // Blocks until disconnect

// 7. Cleanup
$ws->disconnect();
```

## Alpha Vantage WebSocket Client

### Configuration

```php
$config = [
    'api_key' => 'YOUR_API_KEY',
    'endpoint' => 'wss://ws.twelvedata.com/v1/quotes/price',
    'timeout' => 30, // Connection timeout in seconds
];

$websocket = new AlphaVantageWebSocket($config, $logger);
```

### Reconnection Strategy

Automatic reconnection with exponential backoff:

```php
$websocket->setReconnectionStrategy([
    'enabled' => true,          // Enable auto-reconnect
    'max_attempts' => 5,        // Max reconnection attempts
    'initial_delay' => 1000,    // Initial delay in ms
    'max_delay' => 30000,       // Maximum delay in ms
    'backoff_multiplier' => 2,  // Exponential multiplier
]);
```

**Delay Calculation:**
- Attempt 1: 1000ms (1s)
- Attempt 2: 2000ms (2s)
- Attempt 3: 4000ms (4s)
- Attempt 4: 8000ms (8s)
- Attempt 5: 16000ms (16s)

### Heartbeat Management

Keep connection alive with periodic ping/pong:

```php
$websocket->setHeartbeat(true, 30); // Ping every 30 seconds
```

### Event Callbacks

#### Message Callback

```php
$websocket->onMessage(function (array $message) {
    // Message structure depends on provider
    $symbol = $message['symbol'] ?? null;
    $price = $message['price'] ?? null;
    $volume = $message['volume'] ?? null;
    $timestamp = $message['timestamp'] ?? time();
    
    // Process price update
    updateDatabase($symbol, $price, $volume, $timestamp);
});
```

#### Connection Callback

```php
$websocket->onConnection(function (string $event, array $data) {
    switch ($event) {
        case 'connected':
            echo "Connected to {$data['host']}:{$data['port']}\n";
            break;
        case 'disconnected':
            echo "Disconnected\n";
            break;
        case 'reconnecting':
            echo "Reconnecting (attempt {$data['attempt']})\n";
            break;
    }
});
```

#### Error Callback

```php
$websocket->onError(function (string $error, \Exception $e) use ($logger) {
    $logger->error("WebSocket error: {$error}", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    // Send alert, restart service, etc.
    notifyAdmin("WebSocket error: {$error}");
});
```

### Statistics

```php
$stats = $websocket->getStats();

print_r($stats);
/*
Array
(
    [connected] => true
    [uptime] => 3600
    [messages_received] => 12450
    [messages_sent] => 125
    [reconnections] => 2
    [subscriptions] => 10
    [subscribed_symbols] => Array (
        [0] => AAPL
        [1] => MSFT
        ...
    )
    [heartbeat_enabled] => true
    [last_heartbeat] => 15s ago
)
*/
```

## Price Stream Service

### Features

- **Price Change Detection**: Automatic detection of price movements
- **Alert Management**: Set price thresholds and get notifications
- **History Tracking**: Keep recent price history per symbol
- **Event Dispatching**: Emit events for price updates, changes, alerts, spikes

### Configuration

```php
$stream = new PriceStreamService($websocket, $dispatcher, $logger);

// Set minimum change percentage to dispatch events
$stream->setMinChangePercent(0.1); // 0.1% minimum

// Enable price history tracking
$stream->setHistoryTracking(true, 1000); // Keep last 1000 updates per symbol
```

### Price Alerts

#### Basic Alerts

```php
// Alert when price crosses threshold
$stream->setAlert('AAPL', [
    'above' => 200.00,  // Alert if price >= $200
    'below' => 150.00,  // Alert if price <= $150
]);
```

#### Change Percentage Alert

```php
// Alert on significant price movement
$stream->setAlert('MSFT', [
    'change_percent' => 5.0, // Alert on 5% change
]);
```

#### One-Time Alert

```php
// Alert only once, then remove
$stream->setAlert('GOOGL', [
    'above' => 3000.00,
    'once' => true,
]);
```

#### Clear Alerts

```php
$stream->clearAlerts('AAPL');
```

### Price History

```php
// Get last 10 price updates
$history = $stream->getHistory('AAPL', 10);

foreach ($history as $entry) {
    echo date('H:i:s', $entry['timestamp']);
    echo " - ${$entry['price']} ";
    echo "(Vol: " . number_format($entry['volume']) . ")\n";
}
```

### Current Prices

```php
// Get last known price for symbol
$price = $stream->getLastPrice('AAPL');

// Get all tracked prices
$allPrices = $stream->getAllPrices();
/*
Array
(
    [AAPL] => 150.25
    [MSFT] => 305.75
    [GOOGL] => 2750.50
)
*/
```

### Dynamic Subscriptions

```php
// Add symbols to stream
$stream->subscribe(['TSLA', 'NVDA']);

// Remove symbols from stream
$stream->unsubscribe(['GOOGL']);
```

### Statistics

```php
$stats = $stream->getStats();

print_r($stats);
/*
Array
(
    [running] => true
    [uptime] => 3600
    [updates_received] => 45230
    [changes_detected] => 3512
    [alerts_triggered] => 15
    [symbols_tracked] => 10
    [alerts_active] => 8
    [updates_per_second] => 12.56
    [websocket_stats] => Array (...)
)
*/
```

## Event Dispatcher

### Event Types

The system dispatches four types of price events:

1. **price.update**: Every price update (most frequent)
2. **price.change**: When price changes significantly
3. **price.spike**: Large price movement (>5% by default)
4. **price.alert**: When price alert is triggered

### Registering Listeners

#### Basic Listener

```php
$dispatcher->on('price.update', function (array $data) {
    echo "{$data['symbol']}: ${$data['price']}\n";
});
```

#### Priority Listener

```php
// High priority (executes first)
$dispatcher->on('price.update', function ($data) {
    // Critical processing
}, ['priority' => 10]);

// Low priority (executes last)
$dispatcher->on('price.update', function ($data) {
    // Logging
}, ['priority' => 1]);
```

#### One-Time Listener

```php
$dispatcher->once('price.spike', function ($data) {
    notifyAdmin("First spike detected: {$data['symbol']}");
});
```

#### Wildcard Listener

```php
// Listen to ALL events
$dispatcher->on('*', function ($data) {
    logEvent($data['event'], $data);
});
```

### Event Data Structure

#### price.update

```php
[
    'symbol' => 'AAPL',
    'price' => 150.25,
    'volume' => 1250000,
    'timestamp' => 1638835200,
    'last_price' => 150.00,
    'change' => 0.25,
    'change_percent' => 0.17,
]
```

#### price.change

Same as `price.update`, but only when change exceeds minimum threshold.

#### price.spike

```php
[
    // All price.update fields, plus:
    'spike_percent' => 6.5,
]
```

#### price.alert

```php
[
    // All price.update fields, plus:
    'alert_reason' => 'price_above_200.00',
    'alert_conditions' => [
        'above' => 200.00,
        'once' => true,
    ],
]
```

### Removing Listeners

```php
// Remove specific listener
$listenerId = $dispatcher->on('price.update', $callback);
$dispatcher->off($listenerId);

// Remove all listeners for event
$dispatcher->removeAllListeners('price.update');

// Clear everything
$dispatcher->clear();
```

### Event Listener Chains

Chain multiple operations with data transformation:

```php
$dispatcher->chain('price.update')
    ->then(function ($data) {
        // Step 1: Validate
        if ($data['price'] <= 0) {
            return false; // Stop chain
        }
        $data['validated'] = true;
        return $data;
    })
    ->then(function ($data) {
        // Step 2: Save to database
        saveToDatabase($data);
        return $data;
    })
    ->then(function ($data) {
        // Step 3: Update cache
        updateCache($data['symbol'], $data['price']);
        return $data;
    })
    ->catch(function ($error) {
        // Error handler
        logError($error);
    })
    ->execute();
```

### Dispatcher Statistics

```php
$stats = $dispatcher->getStats();

print_r($stats);
/*
Array
(
    [total_dispatches] => 45230
    [total_listeners] => 15
    [events_registered] => 4
    [event_counts] => Array (
        [price.update] => 45230
        [price.change] => 3512
        [price.spike] => 89
        [price.alert] => 15
    )
    [most_dispatched] => Array (
        [event] => price.update
        [count] => 45230
    )
)
*/
```

## Real-Time Monitoring

### Portfolio Monitoring

```php
$portfolio = [
    'AAPL' => ['shares' => 100, 'cost_basis' => 150.00],
    'MSFT' => ['shares' => 50, 'cost_basis' => 300.00],
    'GOOGL' => ['shares' => 25, 'cost_basis' => 2500.00],
];

$dispatcher->on('price.update', function ($data) use (&$portfolio) {
    $symbol = $data['symbol'];
    
    if (!isset($portfolio[$symbol])) {
        return;
    }
    
    $position = $portfolio[$symbol];
    $currentValue = $position['shares'] * $data['price'];
    $costBasis = $position['shares'] * $position['cost_basis'];
    $gainLoss = $currentValue - $costBasis;
    $gainLossPercent = ($gainLoss / $costBasis) * 100;
    
    echo sprintf(
        "%s: %d shares @ $%.2f = $%s (%+.2f%%)\n",
        $symbol,
        $position['shares'],
        $data['price'],
        number_format($currentValue, 2),
        $gainLossPercent
    );
});
```

### Trading Alerts

```php
// Stop-loss alert
$stream->setAlert('AAPL', [
    'below' => 140.00, // Exit if drops below $140
    'once' => true,
]);

$dispatcher->on('price.alert', function ($data) {
    if ($data['alert_reason'] === 'price_below_140.00') {
        // Execute sell order
        executeSellOrder($data['symbol'], $data['price']);
        notifyUser("Stop-loss triggered: {$data['symbol']}");
    }
});

// Take-profit alert
$stream->setAlert('MSFT', [
    'above' => 350.00, // Take profit at $350
    'once' => true,
]);
```

### Volatility Detection

```php
$dispatcher->on('price.spike', function ($data) {
    if (abs($data['spike_percent']) >= 10.0) {
        // Extreme volatility
        logAlert('EXTREME_VOLATILITY', [
            'symbol' => $data['symbol'],
            'spike' => $data['spike_percent'],
            'price' => $data['price'],
        ]);
        
        // Pause automated trading
        pauseTradingForSymbol($data['symbol'], 300); // 5 minutes
    }
});
```

### Performance Monitoring

```php
// Log statistics every minute
$dispatcher->on('price.update', function ($data) use ($stream) {
    static $lastLog = 0;
    
    if (time() - $lastLog >= 60) {
        $stats = $stream->getStats();
        
        logMetrics([
            'uptime' => $stats['uptime'],
            'updates_per_second' => $stats['updates_per_second'],
            'symbols_tracked' => $stats['symbols_tracked'],
            'alerts_triggered' => $stats['alerts_triggered'],
        ]);
        
        $lastLog = time();
    }
});
```

## Production Setup

### Process Management with Supervisor

Create `/etc/supervisor/conf.d/price-stream.conf`:

```ini
[program:price-stream]
command=/usr/bin/php /path/to/stream.php
directory=/path/to/app
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/price-stream.log
```

### Systemd Service

Create `/etc/systemd/system/price-stream.service`:

```ini
[Unit]
Description=Price Stream Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/app
ExecStart=/usr/bin/php stream.php
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl enable price-stream
sudo systemctl start price-stream
sudo systemctl status price-stream
```

### Production Configuration

```php
<?php

use App\WebSocket\AlphaVantageWebSocket;
use App\WebSocket\PriceStreamService;
use App\Services\EventDispatcher;

// Load environment config
$config = [
    'api_key' => getenv('WEBSOCKET_API_KEY'),
    'endpoint' => getenv('WEBSOCKET_ENDPOINT'),
];

// Create services
$websocket = new AlphaVantageWebSocket($config, $logger);
$dispatcher = new EventDispatcher();
$stream = new PriceStreamService($websocket, $dispatcher, $logger);

// Configure reconnection (aggressive for production)
$websocket->setReconnectionStrategy([
    'enabled' => true,
    'max_attempts' => 10,
    'initial_delay' => 2000,
    'max_delay' => 60000,
    'backoff_multiplier' => 2,
]);

// Enable heartbeat
$websocket->setHeartbeat(true, 30);

// Configure stream
$stream->setMinChangePercent(0.05); // 0.05% minimum
$stream->setHistoryTracking(true, 500);

// Error handling
$dispatcher->on('price.error', function ($data) use ($logger) {
    $logger->critical('Price stream error', $data);
    notifyAdmin('Price stream error: ' . $data['error']);
});

// Health check endpoint
registerHealthCheck(function () use ($stream, $websocket) {
    return [
        'status' => $stream->isRunning() && $websocket->isConnected() ? 'healthy' : 'unhealthy',
        'stats' => $stream->getStats(),
    ];
});

// Graceful shutdown
pcntl_signal(SIGTERM, function () use ($stream) {
    $logger->info('Received SIGTERM, shutting down...');
    $stream->stop();
    exit(0);
});

pcntl_signal(SIGINT, function () use ($stream) {
    $logger->info('Received SIGINT, shutting down...');
    $stream->stop();
    exit(0);
});

// Start streaming
try {
    $symbols = getWatchlistSymbols(); // Load from database
    $stream->start($symbols);
} catch (\Exception $e) {
    $logger->critical('Stream failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    exit(1);
}
```

### Performance Optimization

#### Limit Concurrent Symbols

```php
// Stream 50-100 symbols max per connection
$watchlist = getTopSymbols(100);
$stream->start($watchlist);
```

#### Batch Database Updates

```php
$batchBuffer = [];

$dispatcher->on('price.update', function ($data) use (&$batchBuffer) {
    $batchBuffer[] = $data;
    
    // Flush every 100 updates
    if (count($batchBuffer) >= 100) {
        savePricesToDatabase($batchBuffer);
        $batchBuffer = [];
    }
});
```

#### Use Redis for Last Prices

```php
use App\Cache\RedisCache;

$cache = new RedisCache($redis, ['prefix' => 'price:']);

$dispatcher->on('price.update', function ($data) use ($cache) {
    $cache->set("last:{$data['symbol']}", $data['price'], 300); // 5 min TTL
});
```

#### Memory Management

```php
// Limit history size
$stream->setHistoryTracking(true, 100); // Small history

// Periodically clear old data
$dispatcher->on('price.update', function () use ($stream) {
    static $clearCounter = 0;
    
    if (++$clearCounter % 10000 === 0) {
        gc_collect_cycles(); // Force garbage collection
    }
});
```

## Troubleshooting

### Connection Issues

#### Cannot connect to WebSocket

```
Error: Failed to connect: Connection refused
```

**Solutions:**
1. Check endpoint URL is correct
2. Verify API key is valid and has WebSocket access
3. Check firewall allows outbound WebSocket connections
4. Try with different endpoint (some providers have multiple)

```php
// Test connection
$websocket = new AlphaVantageWebSocket($config, $logger);
if (!$websocket->connect()) {
    echo "Connection failed\n";
    print_r($websocket->getStats());
}
```

#### SSL/TLS errors

```
Error: SSL operation failed with code 1
```

**Solutions:**
1. Update CA certificates: `sudo update-ca-certificates`
2. Check PHP OpenSSL version: `php -i | grep OpenSSL`
3. Use `ws://` instead of `wss://` for testing (not for production)

### Disconnection Issues

#### Frequent disconnections

**Check reconnection stats:**

```php
$stats = $websocket->getStats();
echo "Reconnections: {$stats['reconnections']}\n";
```

**Solutions:**
1. Enable heartbeat to keep connection alive
2. Increase heartbeat interval if too frequent
3. Check network stability
4. Contact provider about connection limits

```php
$websocket->setHeartbeat(true, 30); // Ping every 30s
```

#### Connection dropped after inactivity

**Solution:** Enable heartbeat

```php
$websocket->setHeartbeat(true, 20); // Ping every 20s
```

### Performance Issues

#### High CPU usage

**Causes:**
- Too many subscriptions (>200 symbols)
- Processing too complex in event listeners
- No sleep in listen loop

**Solutions:**

```php
// Limit subscriptions
$stream->start(array_slice($watchlist, 0, 100));

// Optimize event processing
$dispatcher->on('price.update', function ($data) {
    // Keep this fast - defer heavy work
    queueForProcessing($data);
});
```

#### High memory usage

**Check memory:**

```php
echo "Memory: " . memory_get_usage(true) / 1024 / 1024 . " MB\n";
```

**Solutions:**

```php
// Reduce history size
$stream->setHistoryTracking(true, 50);

// Clear old data periodically
gc_collect_cycles();
```

#### Slow event processing

**Profile event handlers:**

```php
$dispatcher->on('price.update', function ($data) {
    $start = microtime(true);
    
    // Your processing
    processData($data);
    
    $duration = (microtime(true) - $start) * 1000;
    if ($duration > 10) {
        logSlow("Slow processing: {$duration}ms");
    }
});
```

### Data Issues

#### Missing price updates

**Check subscriptions:**

```php
$subscriptions = $websocket->getSubscriptions();
print_r($subscriptions);
```

**Check stats:**

```php
$stats = $stream->getStats();
echo "Updates received: {$stats['updates_received']}\n";
echo "Updates/sec: {$stats['updates_per_second']}\n";
```

#### Incorrect price data

**Validate incoming data:**

```php
$websocket->onMessage(function ($message) use ($logger) {
    if (!isset($message['price']) || $message['price'] <= 0) {
        $logger->warning('Invalid price data', $message);
        return;
    }
    
    // Process valid data
});
```

### Error Recovery

#### Automatic recovery strategy

```php
$errorCount = 0;
$maxErrors = 10;

$dispatcher->on('price.error', function ($data) use (&$errorCount, $maxErrors, $stream) {
    $errorCount++;
    
    if ($errorCount >= $maxErrors) {
        // Too many errors - restart service
        $stream->stop();
        sleep(60);
        restartService();
    }
});

// Reset error count on successful operation
$dispatcher->on('price.update', function () use (&$errorCount) {
    $errorCount = 0;
});
```

### Logging

#### Enable debug logging

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('websocket');
$logger->pushHandler(new StreamHandler('/var/log/websocket.log', Logger::DEBUG));

$websocket = new AlphaVantageWebSocket($config, $logger);
```

#### Monitor log file

```bash
tail -f /var/log/websocket.log
```

## Best Practices

### 1. Connection Management

- Always enable reconnection strategy in production
- Use heartbeat to detect dead connections
- Implement graceful shutdown handling

### 2. Resource Management

- Limit concurrent symbol subscriptions (50-100 recommended)
- Use small history buffers (100-500 entries)
- Batch database operations
- Clear memory periodically with `gc_collect_cycles()`

### 3. Error Handling

- Always register error callbacks
- Implement retry logic with backoff
- Log all errors with context
- Alert on critical failures

### 4. Monitoring

- Track connection uptime
- Monitor updates per second
- Log reconnection attempts
- Set up health check endpoints

### 5. Data Processing

- Keep event listeners fast (<10ms)
- Defer heavy processing to background jobs
- Validate all incoming data
- Use Redis/cache for fast lookups

### 6. Security

- Never hardcode API keys - use environment variables
- Use WSS (secure WebSocket) in production
- Validate all message data
- Implement rate limiting if needed

## Summary

WebSocket streaming provides:

✅ **Real-time updates** (<50ms latency)  
✅ **Efficient** (single persistent connection)  
✅ **Reliable** (automatic reconnection)  
✅ **Flexible** (event-driven architecture)  
✅ **Scalable** (handle 50-100 symbols per connection)

Perfect for:
- Real-time portfolio monitoring
- Trading alerts and automation
- Market volatility detection
- Live price charts
- High-frequency updates

**Next Steps:**
1. Choose WebSocket provider
2. Get API key with WebSocket access
3. Implement basic streaming
4. Add event listeners
5. Configure alerts
6. Deploy to production with supervisor/systemd

For more examples, see `examples/websocket_streaming.php`.
