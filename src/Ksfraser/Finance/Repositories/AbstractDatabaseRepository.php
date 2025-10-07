<?php
/**
 * Abstract base class for database repositories.
 * Defines the contract for per-symbol technical table operations.
 */

namespace Ksfraser\Finance\Repositories;

use Ksfraser\Finance\Interfaces\DataRepositoryInterface;
use PDO;

abstract class AbstractDatabaseRepository implements DataRepositoryInterface
{
    protected $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Returns the technical indicator table name for a given symbol.
     * @param string $symbol
     * @return string
     */
    public function getSymbolTechnicalTableName(string $symbol): string
    {
        return $symbol . '_technical';
    }

    /**
     * Creates the per-symbol technical indicator table if it does not exist.
     * @param string $symbol
     * @return bool
     */
    abstract public function createSymbolTechnicalTable(string $symbol): bool;

    /**
     * Upserts a row of technical indicator values for a symbol and date.
     * @param string $symbol
     * @param array $values
     * @return bool
     */
    abstract public function saveSymbolTechnicalValues(string $symbol, array $values): bool;
}
