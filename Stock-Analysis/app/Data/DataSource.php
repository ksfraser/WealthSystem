<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Data source result enumeration
 * 
 * Tracks which data provider successfully returned data
 */
enum DataSource: string
{
    case YAHOO = 'yahoo';
    case ALPHA_VANTAGE = 'alpha_vantage';
    case FINNHUB = 'finnhub';
    case STOOQ = 'stooq';
    case CACHE = 'cache';
    case EMPTY = 'empty';
}
