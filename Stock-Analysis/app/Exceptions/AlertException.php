<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Alert Exception
 * 
 * Thrown when alert generation or delivery fails.
 * 
 * @package App\Exceptions
 */
class AlertException extends TradingException
{
    protected string $severity = 'warning';
}
