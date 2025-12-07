<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\WebSocket\AlphaVantageWebSocket;
use App\WebSocket\PriceStreamService;
use App\Services\EventDispatcher;
use Psr\Log\AbstractLogger;

/**
 * WebSocket Real-Time Price Streaming Examples
 * 
 * Demonstrates usage of WebSocket support for real-time stock price updates.
 * 
 * IMPORTANT: WebSocket streaming requires:
 * 1. PHP sockets extension (enabled by default in most installations)
 * 2. WebSocket server endpoint (Twelve Data or Alpha Vantage)
 * 3. Valid API key with WebSocket access
 * 
 * Note: This example uses simulated WebSocket for demonstration.
 * In production, configure with your actual WebSocket provider.
 */

// Simple console logger
class ConsoleLogger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        echo "[" . strtoupper($level) . "] " . date('H:i:s') . " - " . $message . "\n";
    }
}

$logger = new ConsoleLogger();

echo "===== WebSocket Real-Time Streaming Examples =====\n\n";

// ============================================
// Example 1: Basic WebSocket Connection
// ============================================
echo "Example 1: Basic WebSocket Connection\n";
echo str_repeat('-', 50) . "\n";

try {
    $config = [
        'api_key' => 'YOUR_API_KEY',
        'endpoint' => 'wss://ws.twelvedata.com/v1/quotes/price',
    ];

    $websocket = new AlphaVantageWebSocket($config, $logger);

    // Set reconnection strategy
    $websocket->setReconnectionStrategy([
        'enabled' => true,
        'max_attempts' => 3,
        'initial_delay' => 1000,
        'max_delay' => 10000,
    ]);

    // Enable heartbeat
    $websocket->setHeartbeat(true, 30);

    // Register callbacks
    $websocket->onConnection(function (string $event, array $data) use ($logger) {
        $logger->info("Connection event: {$event}");
    });

    $websocket->onError(function (string $error, \Exception $e) use ($logger) {
        $logger->error("Error: {$error} - {$e->getMessage()}");
    });

    $websocket->onMessage(function (array $message) use ($logger) {
        $logger->info("Message received: " . json_encode($message));
    });

    echo "Connecting to WebSocket...\n";
    // $websocket->connect(); // Uncomment with real credentials
    // $websocket->subscribe(['AAPL', 'MSFT', 'GOOGL']);
    // $websocket->listen(60); // Listen for 60 seconds

    echo "Note: Connection requires valid WebSocket endpoint and API key\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================
// Example 2: Price Stream Service with Events
// ============================================
echo "Example 2: Price Stream Service with Events\n";
echo str_repeat('-', 50) . "\n";

// Create event dispatcher
$dispatcher = new EventDispatcher();

// Register event listeners
$dispatcher->on('price.update', function (array $data) {
    $change = $data['change_percent'] !== null 
        ? sprintf("%+.2f%%", $data['change_percent']) 
        : 'N/A';
    
    echo sprintf(
        "[UPDATE] %s: $%.2f (%s) - Vol: %s\n",
        $data['symbol'],
        $data['price'],
        $change,
        number_format($data['volume'] ?? 0)
    );
});

$dispatcher->on('price.change', function (array $data) {
    echo sprintf(
        "[CHANGE] %s moved %+.2f%% to $%.2f\n",
        $data['symbol'],
        $data['change_percent'],
        $data['price']
    );
});

$dispatcher->on('price.spike', function (array $data) {
    echo sprintf(
        "[SPIKE!] %s had significant movement: %+.2f%%\n",
        $data['symbol'],
        $data['spike_percent']
    );
});

$dispatcher->on('price.alert', function (array $data) {
    echo sprintf(
        "[ALERT!] %s triggered alert: %s (Price: $%.2f)\n",
        $data['symbol'],
        $data['alert_reason'],
        $data['price']
    );
});

echo "Event listeners registered for:\n";
echo "- price.update: Every price update\n";
echo "- price.change: Significant price changes\n";
echo "- price.spike: Large movements (>5%)\n";
echo "- price.alert: Price alerts triggered\n\n";

// Create price stream service
try {
    $websocket = new AlphaVantageWebSocket([
        'api_key' => 'demo',
        'endpoint' => 'wss://example.com/stream',
    ], $logger);

    $stream = new PriceStreamService($websocket, $dispatcher, $logger);

    // Configure stream
    $stream->setMinChangePercent(0.1); // 0.1% minimum change
    $stream->setHistoryTracking(true, 500); // Track last 500 updates per symbol

    // Set price alerts
    $stream->setAlert('AAPL', [
        'above' => 200.00,
        'below' => 150.00,
        'once' => true, // One-time alert
    ]);

    $stream->setAlert('MSFT', [
        'change_percent' => 5.0, // Alert on 5% change
    ]);

    echo "Price alerts configured:\n";
    echo "- AAPL: Alert if above \$200 or below \$150\n";
    echo "- MSFT: Alert on 5% price change\n\n";

    // Start streaming (in production)
    // $stream->start(['AAPL', 'MSFT', 'GOOGL', 'AMZN'], ['timeout' => 300]);

    echo "Note: Stream requires active WebSocket connection\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================
// Example 3: Real-Time Portfolio Monitoring
// ============================================
echo "Example 3: Real-Time Portfolio Monitoring\n";
echo str_repeat('-', 50) . "\n";

$portfolio = [
    'AAPL' => ['shares' => 100, 'cost_basis' => 150.00],
    'MSFT' => ['shares' => 50, 'cost_basis' => 300.00],
    'GOOGL' => ['shares' => 25, 'cost_basis' => 2500.00],
];

$portfolioValue = 0;
$totalGainLoss = 0;

// Track portfolio value in real-time
$dispatcher->on('price.update', function (array $data) use (&$portfolio, &$portfolioValue, &$totalGainLoss) {
    $symbol = $data['symbol'];
    
    if (!isset($portfolio[$symbol])) {
        return;
    }

    $position = $portfolio[$symbol];
    $currentValue = $position['shares'] * $data['price'];
    $costBasis = $position['shares'] * $position['cost_basis'];
    $gainLoss = $currentValue - $costBasis;
    $gainLossPercent = ($gainLoss / $costBasis) * 100;

    $portfolio[$symbol]['current_price'] = $data['price'];
    $portfolio[$symbol]['current_value'] = $currentValue;
    $portfolio[$symbol]['gain_loss'] = $gainLoss;

    echo sprintf(
        "[PORTFOLIO] %s: %d shares @ $%.2f = $%s (%.2f%% gain/loss)\n",
        $symbol,
        $position['shares'],
        $data['price'],
        number_format($currentValue, 2),
        $gainLossPercent
    );
});

// Calculate total portfolio value periodically
$dispatcher->on('price.update', function (array $data) use (&$portfolio) {
    static $updateCount = 0;
    $updateCount++;

    // Calculate every 10 updates
    if ($updateCount % 10 === 0) {
        $totalValue = 0;
        $totalCost = 0;

        foreach ($portfolio as $symbol => $position) {
            if (isset($position['current_value'])) {
                $totalValue += $position['current_value'];
                $totalCost += $position['shares'] * $position['cost_basis'];
            }
        }

        $totalGain = $totalValue - $totalCost;
        $totalGainPercent = $totalCost > 0 ? ($totalGain / $totalCost) * 100 : 0;

        echo "\n";
        echo str_repeat('=', 60) . "\n";
        echo sprintf("Total Portfolio Value: $%s\n", number_format($totalValue, 2));
        echo sprintf("Total Cost Basis: $%s\n", number_format($totalCost, 2));
        echo sprintf("Total Gain/Loss: $%s (%+.2f%%)\n", number_format($totalGain, 2), $totalGainPercent);
        echo str_repeat('=', 60) . "\n\n";
    }
});

echo "Portfolio configured for real-time tracking:\n";
foreach ($portfolio as $symbol => $position) {
    echo sprintf(
        "- %s: %d shares @ $%.2f cost basis\n",
        $symbol,
        $position['shares'],
        $position['cost_basis']
    );
}
echo "\n";

// ============================================
// Example 4: Price History and Statistics
// ============================================
echo "Example 4: Price History and Statistics\n";
echo str_repeat('-', 50) . "\n";

try {
    $websocket = new AlphaVantageWebSocket(['api_key' => 'demo'], $logger);
    $stream = new PriceStreamService($websocket, $dispatcher, $logger);

    // Simulate some price updates (in production, this comes from WebSocket)
    $symbol = 'AAPL';
    
    echo "Getting price history for {$symbol}...\n";
    $history = $stream->getHistory($symbol, 10); // Last 10 updates
    
    if (!empty($history)) {
        echo "\nRecent price history:\n";
        foreach ($history as $entry) {
            echo sprintf(
                "  %s: $%.2f (Vol: %s)\n",
                date('H:i:s', $entry['timestamp']),
                $entry['price'],
                number_format($entry['volume'] ?? 0)
            );
        }
    } else {
        echo "No history available (stream not active)\n";
    }

    // Get last known price
    $lastPrice = $stream->getLastPrice($symbol);
    if ($lastPrice !== null) {
        echo "\nLast known price: $" . number_format($lastPrice, 2) . "\n";
    }

    // Get all tracked prices
    $allPrices = $stream->getAllPrices();
    if (!empty($allPrices)) {
        echo "\nAll tracked symbols:\n";
        foreach ($allPrices as $sym => $price) {
            echo "  {$sym}: $" . number_format($price, 2) . "\n";
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================
// Example 5: Event Dispatcher Advanced Usage
// ============================================
echo "Example 5: Event Dispatcher Advanced Usage\n";
echo str_repeat('-', 50) . "\n";

$dispatcher2 = new EventDispatcher();

// Priority listeners (higher priority = execute first)
$dispatcher2->on('price.update', function ($data) {
    echo "  [High Priority] Processing: {$data['symbol']}\n";
}, ['priority' => 10]);

$dispatcher2->on('price.update', function ($data) {
    echo "  [Low Priority] Logging: {$data['symbol']}\n";
}, ['priority' => 1]);

// One-time listener
$dispatcher2->once('price.spike', function ($data) {
    echo "  [One-Time] First spike detected: {$data['symbol']}\n";
});

// Wildcard listener (catches all events)
$dispatcher2->on('*', function ($data) {
    echo "  [Wildcard] Event '{$data['event']}' dispatched\n";
});

// Event listener chain
$chain = $dispatcher2->chain('price.update')
    ->then(function ($data) {
        $data['processed'] = true;
        return $data;
    })
    ->then(function ($data) {
        echo "  [Chain] Processed: {$data['symbol']}\n";
        return $data;
    })
    ->catch(function ($error) {
        echo "  [Chain Error] " . $error->getMessage() . "\n";
    });

$chain->execute();

echo "\nDispatching events...\n";
$dispatcher2->dispatch('price.update', ['symbol' => 'AAPL', 'price' => 150.00]);
$dispatcher2->dispatch('price.spike', ['symbol' => 'MSFT', 'spike_percent' => 6.5]);
$dispatcher2->dispatch('price.spike', ['symbol' => 'GOOGL', 'spike_percent' => 7.2]); // One-time won't fire

echo "\nDispatcher statistics:\n";
$stats = $dispatcher2->getStats();
print_r($stats);

echo "\n";

// ============================================
// Example 6: Production Setup
// ============================================
echo "Example 6: Production Setup with Error Handling\n";
echo str_repeat('-', 50) . "\n";

echo <<<'PRODUCTION'
Production WebSocket Streaming Setup:

1. Install PHP sockets extension (usually enabled by default):
   - Check: php -m | grep sockets
   - Enable in php.ini: extension=sockets

2. Choose WebSocket provider:
   - Twelve Data: wss://ws.twelvedata.com/v1/quotes/price
   - Alpha Vantage: Check their WebSocket documentation
   - IEX Cloud: wss://cloud.iexapis.com/stable/stocksUS

3. Example production configuration:

```php
<?php

use App\WebSocket\AlphaVantageWebSocket;
use App\WebSocket\PriceStreamService;
use App\Services\EventDispatcher;

// Configure WebSocket with retry logic
$websocket = new AlphaVantageWebSocket([
    'api_key' => getenv('WEBSOCKET_API_KEY'),
    'endpoint' => getenv('WEBSOCKET_ENDPOINT'),
    'timeout' => 30,
], $logger);

$websocket->setReconnectionStrategy([
    'enabled' => true,
    'max_attempts' => 10,
    'initial_delay' => 2000,
    'max_delay' => 60000,
    'backoff_multiplier' => 2,
]);

// Create services
$dispatcher = new EventDispatcher();
$stream = new PriceStreamService($websocket, $dispatcher, $logger);

// Register error handlers
$dispatcher->on('price.error', function ($data) use ($logger) {
    $logger->error("Price stream error: {$data['error']}");
    // Send alert, restart service, etc.
});

// Configure monitoring
$dispatcher->on('price.update', function ($data) use ($stream) {
    static $lastLog = 0;
    
    // Log stats every minute
    if (time() - $lastLog >= 60) {
        $stats = $stream->getStats();
        $logger->info("Stream stats", $stats);
        $lastLog = time();
    }
});

// Start streaming in background process
try {
    $stream->start(['AAPL', 'MSFT', 'GOOGL', /* ... */]);
} catch (\Exception $e) {
    $logger->critical("Stream failed: {$e->getMessage()}");
    // Implement recovery strategy
}
```

4. Process management:
   - Use supervisor or systemd to keep stream running
   - Implement health checks
   - Monitor memory usage
   - Log all errors

5. Performance considerations:
   - Limit number of concurrent symbols (50-100 recommended)
   - Use Redis cache for last known prices
   - Batch database updates
   - Monitor WebSocket connection health

PRODUCTION;

echo "\n\n===== Examples Complete =====\n";
