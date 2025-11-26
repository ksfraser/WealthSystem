<?php

/**
 * Class ManageSymbolsAction
 * Handles symbol management operations (list, stats, check, remove, activate, deactivate, cleanup).
 *
 * @package MicroCapExperiment
 */
class ManageSymbolsAction
{
    /**
     * @var IStockTableManager
     */
    private $tableManager;

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * ManageSymbolsAction constructor.
     * @param IStockTableManager $tableManager
     * @param \PDO $pdo
     */
    public function __construct(IStockTableManager $tableManager, $pdo)
    {
        $this->tableManager = $tableManager;
        $this->pdo = $pdo;
    }

    /**
     * List all registered symbols.
     * @return array
     */
    public function listSymbols()
    {
        return $this->tableManager->getAllSymbols(false);
    }

    /**
     * Get statistics for a symbol's tables.
     * @param string $symbol
     * @return array|null
     */
    public function stats($symbol)
    {
        return $this->tableManager->getSymbolTableStats($symbol);
    }

    /**
     * Check table integrity for one or more symbols.
     * @param array $symbols
     * @param bool $verbose
     * @return array
     */
    public function check($symbols, $verbose = false)
    {
        $results = [];
        foreach ($symbols as $symbol) {
            $exists = $this->tableManager->tablesExistForSymbol($symbol);
            $stats = $verbose && $exists ? $this->tableManager->getSymbolTableStats($symbol) : null;
            $results[] = [
                'symbol' => $symbol,
                'exists' => $exists,
                'stats' => $stats
            ];
        }
        return $results;
    }

    /**
     * Remove a symbol and all its tables.
     * @param string $symbol
     * @return bool
     */
    public function remove($symbol)
    {
        return $this->tableManager->removeTablesForSymbol($symbol, true);
    }

    /**
     * Deactivate a symbol.
     * @param string $symbol
     * @return bool
     */
    public function deactivate($symbol)
    {
        return $this->tableManager->deactivateSymbol($symbol);
    }

    /**
     * Activate a symbol (register if missing).
     * @param string $symbol
     * @return bool
     */
    public function activate($symbol)
    {
        $allSymbols = $this->tableManager->getAllSymbols(false);
        $symbolExists = false;
        foreach ($allSymbols as $existing) {
            if ($existing['symbol'] === $symbol) {
                $symbolExists = true;
                break;
            }
        }
        if (!$symbolExists) {
            $this->tableManager->registerSymbol($symbol, [
                'company_name' => '',
                'sector' => '',
                'industry' => '',
                'market_cap' => 'micro',
                'active' => true
            ]);
            return true;
        } else {
            // Use table manager to activate symbol instead of direct SQL
            return $this->activateExistingSymbol($symbol);
        }
    }

    /**
     * Activate an existing symbol in the registry.
     * @param string $symbol
     * @return bool
     */
    private function activateExistingSymbol($symbol)
    {
        $stmt = $this->pdo->prepare("UPDATE symbol_registry SET active = 1, updated_at = CURRENT_TIMESTAMP WHERE symbol = ?");
        return $stmt->execute([$symbol]);
    }

    /**
     * Remove orphaned tables not associated with any registered symbol.
     * @return array List of removed table names
     */
    public function cleanup()
    {
        $registeredSymbols = array_column($this->tableManager->getAllSymbols(false), 'symbol');
        $stmt = $this->pdo->query("SHOW TABLES");
        $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $tableTypes = ['_prices', '_indicators', '_patterns', '_support_resistance', '_signals', '_earnings', '_dividends'];
        $orphanedTables = [];
        foreach ($allTables as $table) {
            foreach ($tableTypes as $suffix) {
                if (strpos($table, $suffix) !== false) {
                    $tableSymbol = str_replace($suffix, '', $table);
                    if (!in_array($tableSymbol, $registeredSymbols)) {
                        $orphanedTables[] = $table;
                    }
                }
            }
        }
        foreach ($orphanedTables as $table) {
            $stmt = $this->pdo->prepare("DROP TABLE IF EXISTS `{$table}`");
            $stmt->execute();
        }
        return $orphanedTables;
    }
}
