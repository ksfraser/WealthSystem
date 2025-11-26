<?php
require_once __DIR__ . '/TableTypeRegistry.php';


/**
 * Class BulkImportSymbolsAction
 * Handles the business logic for bulk importing stock symbols and creating their tables.
 *
 * @package MicroCapExperiment
 */
class BulkImportSymbolsAction
{
    /**
     * @var IStockTableManager
     */
    private $tableManager;

    /**
     * @var AddSymbolAction
     */
    private $addSymbolAction;

    /**
     * BulkImportSymbolsAction constructor.
     * @param IStockTableManager $tableManager
     * @param AddSymbolAction $addSymbolAction
     */
    public function __construct(IStockTableManager $tableManager, AddSymbolAction $addSymbolAction)
    {
        $this->tableManager = $tableManager;
        $this->addSymbolAction = $addSymbolAction;
    }

    /**
     * Bulk import symbols, creating tables for each.
     *
     * @param string[] $symbols
     * @param bool $dryRun
     * @return array
     */
    public function execute(array $symbols, $dryRun = false)
    {
        $results = [
            'existing' => [],
            'created' => [],
            'errors' => []
        ];
        foreach ($symbols as $symbol) {
            if (!TableTypeRegistry::isValidSymbol($symbol)) {
                $results['errors'][] = [
                    'symbol' => $symbol,
                    'error' => 'Invalid symbol format'
                ];
                continue;
            }
            if ($dryRun) {
                $results['created'][] = $symbol;
                continue;
            }
            try {
                $result = $this->addSymbolAction->execute($symbol, [
                    'company_name' => '',
                    'sector' => '',
                    'industry' => '',
                    'market_cap' => 'micro',
                    'active' => true
                ]);
                if ($result['status'] === 'created') {
                    $results['created'][] = $symbol;
                } else {
                    $results['existing'][] = $symbol;
                }
            } catch (Exception $e) {
                $results['errors'][] = [
                    'symbol' => $symbol,
                    'error' => $e->getMessage()
                ];
            }
        }
        return $results;
    }
}
