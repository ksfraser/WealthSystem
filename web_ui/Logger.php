<?php


require_once __DIR__ . '/CoreInterfaces.php';
use App\LoggerInterface;

/**
 * Simple file-based logger implementation
 */

class FileLogger implements LoggerInterface
{
    private $logFile;
    private $minLevel;

    const LEVELS = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3
    ];

    public function __construct($logFile, $minLevel = 'info')
    {
        $this->logFile = $logFile;
        $this->minLevel = $minLevel;

        // Ensure log directory exists
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    private function log($level, string $message, array $context = []): void
    {
        if (self::LEVELS[$level] < self::LEVELS[$this->minLevel]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        $logLine = sprintf("[%s] %s: %s%s\n", $timestamp, strtoupper($level), $message, $contextStr);

        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Null logger for testing or when logging is disabled
 */
class NullLogger implements LoggerInterface
{
    public function error(string $message, array $context = []): void {}
    public function warning(string $message, array $context = []): void {}
    public function info(string $message, array $context = []): void {}
    public function debug(string $message, array $context = []): void {}
}
