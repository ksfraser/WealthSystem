<?php
require_once __DIR__ . '/../src/TableTypeRegistry.php';


/**
 * Class AddSymbolAction
 * Handles the business logic for adding a new stock symbol and creating its tables.
 *
 * @package MicroCapExperiment
 */
class AddSymbolAction
{
    /**
     * @var IStockTableManager
     */
    private $tableManager;

    /**
     * AddSymbolAction constructor.
     * @param IStockTableManager $tableManager
     */
    public function __construct(IStockTableManager $tableManager)
    {
        $this->tableManager = $tableManager;
    }

    /**
     * Add a new symbol or ensure tables exist for an existing symbol.
     *
     * @param string $symbol
     * @param array $companyData
     * @return array
     * @throws Exception
     */
    public function execute($symbol, $companyData = [])
    {
        if (!TableTypeRegistry::isValidSymbol($symbol)) {
            throw new Exception("Invalid symbol format: {$symbol}");
        }
        $existingSymbols = $this->tableManager->getAllSymbols(false);
        $symbolExists = false;
        foreach ($existingSymbols as $existing) {
            if ($existing['symbol'] === $symbol) {
                $symbolExists = true;
                break;
            }
        }
        if ($symbolExists) {
            if (!$this->tableManager->tablesExistForSymbol($symbol)) {
                $this->tableManager->createTablesForSymbol($symbol);
            }
            return [
                'status' => 'exists',
                'symbol' => $symbol
            ];
        } else {
            $this->tableManager->registerSymbol($symbol, $companyData);
            $this->tableManager->createTablesForSymbol($symbol);
            return [
                'status' => 'created',
                'symbol' => $symbol
            ];
        }
    }
}
