<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Data Exception
 * 
 * Thrown when data fetch or validation fails.
 * 
 * @package App\Exceptions
 */
class DataException extends TradingException
{
    protected string $severity = 'warning';
}
