<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Backtest Exception
 * 
 * Thrown during backtesting operations.
 * 
 * @package App\Exceptions
 */
class BacktestException extends TradingException
{
    protected string $severity = 'error';
}
