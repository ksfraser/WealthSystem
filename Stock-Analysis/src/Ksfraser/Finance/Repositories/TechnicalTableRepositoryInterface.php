<?php
/**
 * Interface for per-symbol technical table operations.
 */

namespace Ksfraser\Finance\Repositories;

interface TechnicalTableRepositoryInterface
{
    /**
     * Returns the technical indicator table name for a given symbol.
     * @param string $symbol
     * @return string
     */
    public function getSymbolTechnicalTableName(string $symbol): string;

    /**
     * Creates the per-symbol technical indicator table if it does not exist.
     * @param string $symbol
     * @return bool
     */
    public function createSymbolTechnicalTable(string $symbol): bool;

    /**
     * Upserts a row of technical indicator values for a symbol and date.
     * @param string $symbol
     * @param array $values
     * @return bool
     */
    public function saveSymbolTechnicalValues(string $symbol, array $values): bool;
}
