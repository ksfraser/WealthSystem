<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\IntrospectionProcessor;

/**
 * Trading Logger
 * 
 * Structured JSON logging system for trading operations.
 * 
 * Features:
 * - JSON format for easy parsing
 * - Log rotation (daily)
 * - Context injection
 * - Request ID tracking
 * - Memory usage tracking
 * - Multiple log levels
 * 
 * @package App\Logging
 */
class TradingLogger
{
    private Logger $logger;
    private array $context = [];
    
    public function __construct(string $name = 'trading', array $config = [])
    {
        $this->logger = new Logger($name);
        
        $logPath = $config['log_path'] ?? __DIR__ . '/../../storage/logs/trading.log';
        $logLevel = $config['log_level'] ?? Logger::DEBUG;
        $maxFiles = $config['max_files'] ?? 30;
        
        // Ensure log directory exists
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Rotating file handler with JSON formatter
        $handler = new RotatingFileHandler($logPath, $maxFiles, $logLevel);
        $handler->setFormatter(new JsonFormatter());
        
        $this->logger->pushHandler($handler);
        
        // Add processors for context enrichment
        $this->logger->pushProcessor(new UidProcessor());
        $this->logger->pushProcessor(new MemoryUsageProcessor());
        $this->logger->pushProcessor(new IntrospectionProcessor());
    }
    
    /**
     * Set global context for all log entries
     */
    public function setContext(array $context): void
    {
        $this->context = array_merge($this->context, $context);
    }
    
    /**
     * Log debug message
     */
    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $this->mergeContext($context));
    }
    
    /**
     * Log info message
     */
    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $this->mergeContext($context));
    }
    
    /**
     * Log warning message
     */
    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $this->mergeContext($context));
    }
    
    /**
     * Log error message
     */
    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $this->mergeContext($context));
    }
    
    /**
     * Log critical message
     */
    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $this->mergeContext($context));
    }
    
    /**
     * Log strategy execution
     */
    public function logStrategyExecution(string $strategy, string $symbol, array $signal): void
    {
        $this->info('Strategy executed', [
            'category' => 'strategy',
            'strategy' => $strategy,
            'symbol' => $symbol,
            'action' => $signal['action'] ?? 'NONE',
            'confidence' => $signal['confidence'] ?? 0,
            'signal' => $signal
        ]);
    }
    
    /**
     * Log trade execution
     */
    public function logTrade(array $trade): void
    {
        $this->info('Trade executed', [
            'category' => 'trade',
            'symbol' => $trade['symbol'] ?? '',
            'action' => $trade['action'] ?? '',
            'quantity' => $trade['quantity'] ?? 0,
            'price' => $trade['price'] ?? 0,
            'trade' => $trade
        ]);
    }
    
    /**
     * Log alert generation
     */
    public function logAlert(string $type, string $symbol, array $alert): void
    {
        $this->warning('Alert generated', [
            'category' => 'alert',
            'alert_type' => $type,
            'symbol' => $symbol,
            'severity' => $alert['severity'] ?? 'medium',
            'alert' => $alert
        ]);
    }
    
    /**
     * Log exception with full context
     */
    public function logException(\Throwable $exception, array $context = []): void
    {
        $this->error($exception->getMessage(), array_merge([
            'category' => 'exception',
            'exception_class' => get_class($exception),
            'exception_code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ], $context));
    }
    
    /**
     * Log API call
     */
    public function logAPICall(string $endpoint, string $method, int $statusCode, float $duration): void
    {
        $this->debug('API call', [
            'category' => 'api',
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
            'duration_ms' => round($duration * 1000, 2)
        ]);
    }
    
    /**
     * Log performance metrics
     */
    public function logPerformance(string $operation, float $duration, array $metrics = []): void
    {
        $this->info('Performance metric', array_merge([
            'category' => 'performance',
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2)
        ], $metrics));
    }
    
    /**
     * Get underlying Monolog logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }
    
    /**
     * Merge global context with message context
     */
    private function mergeContext(array $context): array
    {
        return array_merge($this->context, $context, [
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
        ]);
    }
}
