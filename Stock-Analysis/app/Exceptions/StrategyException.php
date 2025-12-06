<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Strategy Exception
 * 
 * Thrown when trading strategy encounters an error.
 * 
 * @package App\Exceptions
 */
class StrategyException extends TradingException
{
    protected string $severity = 'error';
}
