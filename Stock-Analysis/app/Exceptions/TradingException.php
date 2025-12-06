<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Base Trading Exception
 * 
 * Base exception for all trading-related errors.
 * Provides structured error context and severity levels.
 * 
 * @package App\Exceptions
 */
class TradingException extends Exception
{
    protected array $context = [];
    protected string $severity = 'error';
    
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }
    
    public function getContext(): array
    {
        return $this->context;
    }
    
    public function getSeverity(): string
    {
        return $this->severity;
    }
    
    public function toArray(): array
    {
        return [
            'exception' => get_class($this),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'severity' => $this->severity,
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine()
        ];
    }
}
