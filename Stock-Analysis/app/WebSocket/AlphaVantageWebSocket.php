<?php

namespace App\WebSocket;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Alpha Vantage WebSocket Client
 * 
 * Real-time stock price streaming using Alpha Vantage's WebSocket API.
 * Supports automatic reconnection, heartbeat management, and subscription handling.
 * 
 * Features:
 * - Real-time quote streaming
 * - Automatic reconnection with exponential backoff
 * - Heartbeat ping/pong for connection health
 * - Multi-symbol subscription management
 * - Error handling and recovery
 * 
 * Example:
 * ```php
 * $ws = new AlphaVantageWebSocket([
 *     'api_key' => 'YOUR_API_KEY',
 *     'endpoint' => 'wss://ws.example.com/stream',
 * ], $logger);
 * 
 * $ws->onMessage(function($msg) {
 *     echo "{$msg['symbol']}: ${$msg['price']}\n";
 * });
 * 
 * $ws->connect();
 * $ws->subscribe(['AAPL', 'MSFT']);
 * $ws->listen();
 * ```
 */
class AlphaVantageWebSocket implements WebSocketInterface
{
    private mixed $socket = null;
    private bool $connected = false;
    private array $subscriptions = [];
    private array $callbacks = [
        'message' => [],
        'connection' => [],
        'error' => [],
    ];

    // Statistics
    private int $messagesReceived = 0;
    private int $messagesSent = 0;
    private int $reconnections = 0;
    private int $connectedAt = 0;

    // Reconnection strategy
    private array $reconnectionConfig = [
        'enabled' => true,
        'max_attempts' => 5,
        'initial_delay' => 1000, // ms
        'max_delay' => 30000, // ms
        'backoff_multiplier' => 2,
    ];

    // Heartbeat config
    private bool $heartbeatEnabled = true;
    private int $heartbeatInterval = 30; // seconds
    private int $lastHeartbeat = 0;

    public function __construct(
        private readonly array $config,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
        if (!extension_loaded('sockets')) {
            throw new \RuntimeException('Sockets extension not available');
        }
    }

    public function connect(): bool
    {
        if ($this->connected) {
            $this->logger->debug('Already connected');
            return true;
        }

        $endpoint = $this->config['endpoint'] ?? 'wss://ws.twelvedata.com/v1/quotes/price';
        $apiKey = $this->config['api_key'] ?? '';

        if (empty($apiKey)) {
            throw new \InvalidArgumentException('API key required for WebSocket connection');
        }

        try {
            // Parse WebSocket URL
            $url = parse_url($endpoint);
            $host = $url['host'] ?? 'ws.twelvedata.com';
            $port = $url['port'] ?? ($url['scheme'] === 'wss' ? 443 : 80);
            $path = $url['path'] ?? '/v1/quotes/price';

            // Create socket
            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($this->socket === false) {
                throw new \RuntimeException('Failed to create socket: ' . socket_strerror(socket_last_error()));
            }

            // Set socket options
            socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 30, 'usec' => 0]);
            socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 10, 'usec' => 0]);

            // Connect
            if (!@socket_connect($this->socket, $host, $port)) {
                throw new \RuntimeException('Failed to connect: ' . socket_strerror(socket_last_error($this->socket)));
            }

            // Perform WebSocket handshake
            $this->performHandshake($host, $path, $apiKey);

            $this->connected = true;
            $this->connectedAt = time();
            $this->lastHeartbeat = time();

            $this->logger->info("WebSocket connected to {$host}:{$port}");
            $this->triggerCallback('connection', 'connected', ['host' => $host, 'port' => $port]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('WebSocket connection failed: ' . $e->getMessage());
            $this->triggerCallback('error', 'connection_failed', $e);
            
            if ($this->socket) {
                @socket_close($this->socket);
                $this->socket = null;
            }

            return false;
        }
    }

    private function performHandshake(string $host, string $path, string $apiKey): void
    {
        // Generate WebSocket key
        $key = base64_encode(random_bytes(16));

        // Build handshake request
        $request = "GET {$path}?apikey={$apiKey} HTTP/1.1\r\n";
        $request .= "Host: {$host}\r\n";
        $request .= "Upgrade: websocket\r\n";
        $request .= "Connection: Upgrade\r\n";
        $request .= "Sec-WebSocket-Key: {$key}\r\n";
        $request .= "Sec-WebSocket-Version: 13\r\n";
        $request .= "\r\n";

        // Send handshake
        socket_write($this->socket, $request, strlen($request));

        // Read response
        $response = '';
        while ($chunk = @socket_read($this->socket, 1024)) {
            $response .= $chunk;
            if (strpos($response, "\r\n\r\n") !== false) {
                break;
            }
        }

        // Validate handshake
        if (!preg_match('/HTTP\/1\.[01] 101/i', $response)) {
            throw new \RuntimeException('WebSocket handshake failed: Invalid response');
        }

        $this->logger->debug('WebSocket handshake successful');
    }

    public function disconnect(): void
    {
        if (!$this->connected) {
            return;
        }

        try {
            // Unsubscribe from all symbols
            if (!empty($this->subscriptions)) {
                $this->unsubscribe($this->subscriptions);
            }

            // Send close frame
            if ($this->socket) {
                $closeFrame = $this->encodeFrame(json_encode(['action' => 'close']), 0x8);
                @socket_write($this->socket, $closeFrame);
                @socket_close($this->socket);
            }

            $this->connected = false;
            $this->socket = null;

            $this->logger->info('WebSocket disconnected');
            $this->triggerCallback('connection', 'disconnected', []);

        } catch (\Exception $e) {
            $this->logger->error('Error during disconnect: ' . $e->getMessage());
        }
    }

    public function isConnected(): bool
    {
        return $this->connected && $this->socket !== null;
    }

    public function subscribe(array $symbols): bool
    {
        if (!$this->isConnected()) {
            $this->logger->warning('Cannot subscribe: not connected');
            return false;
        }

        try {
            // Add to subscriptions
            $this->subscriptions = array_unique(array_merge($this->subscriptions, $symbols));

            // Send subscribe message
            $message = [
                'action' => 'subscribe',
                'symbols' => array_values($symbols),
            ];

            $this->send($message);

            $this->logger->info('Subscribed to: ' . implode(', ', $symbols));
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Subscription failed: ' . $e->getMessage());
            return false;
        }
    }

    public function unsubscribe(array $symbols): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        try {
            // Remove from subscriptions
            $this->subscriptions = array_values(array_diff($this->subscriptions, $symbols));

            // Send unsubscribe message
            $message = [
                'action' => 'unsubscribe',
                'symbols' => array_values($symbols),
            ];

            $this->send($message);

            $this->logger->info('Unsubscribed from: ' . implode(', ', $symbols));
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Unsubscription failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getSubscriptions(): array
    {
        return $this->subscriptions;
    }

    public function listen(?int $timeout = null): void
    {
        if (!$this->isConnected()) {
            throw new \RuntimeException('Cannot listen: not connected');
        }

        $this->logger->info('Starting WebSocket listener');
        $startTime = time();

        while ($this->isConnected()) {
            // Check timeout
            if ($timeout !== null && (time() - $startTime) >= $timeout) {
                $this->logger->info('Listen timeout reached');
                break;
            }

            // Check heartbeat
            if ($this->heartbeatEnabled && (time() - $this->lastHeartbeat) >= $this->heartbeatInterval) {
                $this->sendHeartbeat();
            }

            // Read data
            try {
                $data = @socket_read($this->socket, 8192, PHP_BINARY_READ);

                if ($data === false) {
                    $error = socket_last_error($this->socket);
                    if ($error === SOCKET_ETIMEDOUT || $error === SOCKET_EAGAIN) {
                        usleep(100000); // 100ms
                        continue;
                    }

                    throw new \RuntimeException('Socket read error: ' . socket_strerror($error));
                }

                if ($data === '') {
                    // Connection closed
                    $this->logger->warning('WebSocket connection closed by server');
                    $this->connected = false;
                    $this->attemptReconnect();
                    continue;
                }

                // Decode WebSocket frame
                $message = $this->decodeFrame($data);
                if ($message !== null) {
                    $this->handleMessage($message);
                }

            } catch (\Exception $e) {
                $this->logger->error('Listen error: ' . $e->getMessage());
                $this->triggerCallback('error', 'listen_error', $e);

                if (!$this->isConnected()) {
                    $this->attemptReconnect();
                }
            }

            usleep(10000); // 10ms sleep to prevent CPU spinning
        }

        $this->logger->info('WebSocket listener stopped');
    }

    private function handleMessage(string $data): void
    {
        try {
            $message = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->warning('Failed to decode message: ' . json_last_error_msg());
                return;
            }

            $this->messagesReceived++;

            // Handle different message types
            if (isset($message['type'])) {
                switch ($message['type']) {
                    case 'heartbeat':
                    case 'pong':
                        $this->lastHeartbeat = time();
                        $this->logger->debug('Heartbeat received');
                        return;

                    case 'error':
                        $this->logger->error('Server error: ' . ($message['message'] ?? 'Unknown'));
                        $this->triggerCallback('error', 'server_error', new \RuntimeException($message['message'] ?? 'Server error'));
                        return;

                    case 'subscribed':
                    case 'unsubscribed':
                        $this->logger->debug('Subscription confirmation: ' . $message['type']);
                        return;
                }
            }

            // Trigger message callbacks
            $this->triggerCallback('message', $message);

        } catch (\Exception $e) {
            $this->logger->error('Message handling error: ' . $e->getMessage());
            $this->triggerCallback('error', 'message_error', $e);
        }
    }

    private function sendHeartbeat(): void
    {
        try {
            $this->send(['action' => 'ping']);
            $this->lastHeartbeat = time();
            $this->logger->debug('Heartbeat sent');
        } catch (\Exception $e) {
            $this->logger->warning('Failed to send heartbeat: ' . $e->getMessage());
        }
    }

    private function attemptReconnect(): void
    {
        if (!$this->reconnectionConfig['enabled']) {
            $this->logger->info('Auto-reconnect disabled');
            return;
        }

        $maxAttempts = $this->reconnectionConfig['max_attempts'];
        $delay = $this->reconnectionConfig['initial_delay'];

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $this->logger->info("Reconnection attempt {$attempt}/{$maxAttempts}");
            $this->triggerCallback('connection', 'reconnecting', ['attempt' => $attempt]);

            // Wait with exponential backoff
            usleep($delay * 1000);

            if ($this->connect()) {
                $this->reconnections++;
                
                // Re-subscribe to previous symbols
                if (!empty($this->subscriptions)) {
                    $this->subscribe($this->subscriptions);
                }

                $this->logger->info('Reconnection successful');
                return;
            }

            // Increase delay with exponential backoff
            $delay = min(
                $delay * $this->reconnectionConfig['backoff_multiplier'],
                $this->reconnectionConfig['max_delay']
            );
        }

        $this->logger->error('Reconnection failed after max attempts');
        $this->triggerCallback('error', 'reconnection_failed', new \RuntimeException('Max reconnection attempts reached'));
    }

    public function onMessage(callable $callback): void
    {
        $this->callbacks['message'][] = $callback;
    }

    public function onConnection(callable $callback): void
    {
        $this->callbacks['connection'][] = $callback;
    }

    public function onError(callable $callback): void
    {
        $this->callbacks['error'][] = $callback;
    }

    private function triggerCallback(string $type, ...$args): void
    {
        foreach ($this->callbacks[$type] as $callback) {
            try {
                $callback(...$args);
            } catch (\Exception $e) {
                $this->logger->error("Callback error: " . $e->getMessage());
            }
        }
    }

    public function send(array $message): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        try {
            $json = json_encode($message);
            $frame = $this->encodeFrame($json, 0x1); // Text frame

            $written = @socket_write($this->socket, $frame, strlen($frame));
            if ($written === false) {
                throw new \RuntimeException('Failed to write to socket');
            }

            $this->messagesSent++;
            $this->logger->debug('Message sent: ' . $json);
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Send error: ' . $e->getMessage());
            return false;
        }
    }

    private function encodeFrame(string $data, int $opcode): string
    {
        $length = strlen($data);
        $frame = chr($opcode | 0x80); // FIN bit + opcode

        // Encode length
        if ($length <= 125) {
            $frame .= chr($length | 0x80); // MASK bit + length
        } elseif ($length <= 65535) {
            $frame .= chr(126 | 0x80); // MASK bit + 126
            $frame .= pack('n', $length);
        } else {
            $frame .= chr(127 | 0x80); // MASK bit + 127
            $frame .= pack('J', $length);
        }

        // Add masking key
        $mask = random_bytes(4);
        $frame .= $mask;

        // Mask data
        for ($i = 0; $i < $length; $i++) {
            $frame .= $data[$i] ^ $mask[$i % 4];
        }

        return $frame;
    }

    private function decodeFrame(string $data): ?string
    {
        if (strlen($data) < 2) {
            return null;
        }

        $byte1 = ord($data[0]);
        $byte2 = ord($data[1]);

        $opcode = $byte1 & 0x0F;
        $masked = ($byte2 & 0x80) !== 0;
        $length = $byte2 & 0x7F;

        $offset = 2;

        // Get actual length
        if ($length === 126) {
            if (strlen($data) < 4) return null;
            $length = unpack('n', substr($data, 2, 2))[1];
            $offset = 4;
        } elseif ($length === 127) {
            if (strlen($data) < 10) return null;
            $length = unpack('J', substr($data, 2, 8))[1];
            $offset = 10;
        }

        // Get mask key if masked
        $mask = null;
        if ($masked) {
            if (strlen($data) < $offset + 4) return null;
            $mask = substr($data, $offset, 4);
            $offset += 4;
        }

        // Get payload
        if (strlen($data) < $offset + $length) {
            return null;
        }

        $payload = substr($data, $offset, $length);

        // Unmask if needed
        if ($masked && $mask !== null) {
            $unmasked = '';
            for ($i = 0; $i < $length; $i++) {
                $unmasked .= $payload[$i] ^ $mask[$i % 4];
            }
            $payload = $unmasked;
        }

        // Handle control frames
        if ($opcode === 0x8) {
            // Close frame
            $this->logger->info('Received close frame');
            $this->connected = false;
            return null;
        } elseif ($opcode === 0x9) {
            // Ping frame - send pong
            $this->send(['action' => 'pong']);
            return null;
        } elseif ($opcode === 0xA) {
            // Pong frame
            $this->lastHeartbeat = time();
            return null;
        }

        return $payload;
    }

    public function getStats(): array
    {
        return [
            'connected' => $this->connected,
            'uptime' => $this->connected ? time() - $this->connectedAt : 0,
            'messages_received' => $this->messagesReceived,
            'messages_sent' => $this->messagesSent,
            'reconnections' => $this->reconnections,
            'subscriptions' => count($this->subscriptions),
            'subscribed_symbols' => $this->subscriptions,
            'heartbeat_enabled' => $this->heartbeatEnabled,
            'last_heartbeat' => time() - $this->lastHeartbeat . 's ago',
        ];
    }

    public function setReconnectionStrategy(array $config): void
    {
        $this->reconnectionConfig = array_merge($this->reconnectionConfig, $config);
        $this->logger->debug('Reconnection strategy updated');
    }

    public function setHeartbeat(bool $enabled, int $interval = 30): void
    {
        $this->heartbeatEnabled = $enabled;
        $this->heartbeatInterval = $interval;
        $this->logger->debug("Heartbeat: " . ($enabled ? "enabled ({$interval}s)" : "disabled"));
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
