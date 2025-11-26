
<?php
/**
 * Class TableTypeRegistry
 * Central registry for table types and symbol validation.
 *
 * @package MicroCapExperiment
 */
class TableTypeRegistry
{
    /**
     * List of all supported table types for a stock symbol.
     *
     * @var string[]
     */
    public const TABLE_TYPES = [
        'prices',
        'indicators',
        'patterns',
        'support_resistance',
        'signals',
        'earnings',
        'dividends',
    ];

    /**
     * Validate a stock symbol (must be 1-10 uppercase alphanumeric characters).
     *
     * @param mixed $symbol
     * @return bool
     */
    public static function isValidSymbol($symbol)
    {
        // Ensure input is a string
        if (!is_string($symbol)) {
            return false;
        }
        
        return preg_match('/^[A-Z0-9]{1,10}$/', $symbol) === 1;
    }
}
