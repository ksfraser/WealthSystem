<?php
require_once __DIR__ . '/TableTypeRegistry.php';


/**
 * Class MigrateSymbolAction
 * Handles migration of data from legacy monolithic tables to per-symbol tables for a given symbol.
 *
 * @package MicroCapExperiment
 */
class MigrateSymbolAction
{
    /**
     * @var IStockTableManager
     */
    private $tableManager;

    /**
     * @var IStockDataAccess
     */
    private $dataAccess;

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * MigrateSymbolAction constructor.
     * @param IStockTableManager $tableManager
     * @param IStockDataAccess $dataAccess
     * @param \PDO $pdo
     */
    public function __construct(IStockTableManager $tableManager, IStockDataAccess $dataAccess, $pdo)
    {
        $this->tableManager = $tableManager;
        $this->dataAccess = $dataAccess;
        $this->pdo = $pdo;
    }

    /**
     * Migrate all data for a symbol from legacy tables to per-symbol tables.
     *
     * @param string $symbol
     * @param array $legacyTables
     * @param array $options
     * @return array
     */
    public function execute($symbol, $legacyTables, $options = [])
    {
        $batchSize = $options['batch_size'] ?? 1000;
        $dryRun = $options['dry_run'] ?? false;
        $results = [
            'symbol' => $symbol,
            'tables_migrated' => 0,
            'total_records' => 0,
            'errors' => []
        ];
        if (!TableTypeRegistry::isValidSymbol($symbol)) {
            $results['errors'][] = 'Invalid symbol format';
            return $results;
        }
        if (!$dryRun) {
            $this->tableManager->registerSymbol($symbol, [
                'company_name' => '',
                'sector' => '',
                'industry' => '',
                'market_cap' => 'micro',
                'active' => true
            ]);
            if (!$this->tableManager->tablesExistForSymbol($symbol)) {
                $this->tableManager->createTablesForSymbol($symbol);
            }
        }
        foreach ($legacyTables as $legacyTable => $config) {
            try {
                $countStmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM {$legacyTable} WHERE {$config['symbol_column']} = ?");
                $countStmt->execute([$symbol]);
                $recordCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
                if ($recordCount == 0) continue;
                if ($dryRun) {
                    $results['total_records'] += $recordCount;
                    $results['tables_migrated']++;
                    continue;
                }
                $offset = 0;
                $migratedCount = 0;
                while ($offset < $recordCount) {
                    $dataStmt = $this->pdo->prepare("
                        SELECT * FROM {$legacyTable} 
                        WHERE {$config['symbol_column']} = ? 
                        ORDER BY id 
                        LIMIT {$batchSize} OFFSET {$offset}
                    ");
                    $dataStmt->execute([$symbol]);
                    $batchCount = 0;
                    while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
                        switch ($config['target']) {
                            case 'prices':
                                $this->dataAccess->insertPriceData($symbol, [$row]);
                                break;
                            case 'indicators':
                                $this->dataAccess->insertTechnicalIndicator($symbol, $row);
                                break;
                            case 'patterns':
                                $this->dataAccess->insertCandlestickPattern($symbol, $row);
                                break;
                        }
                        $batchCount++;
                    }
                    $migratedCount += $batchCount;
                    $offset += $batchSize;
                }
                $results['total_records'] += $migratedCount;
                $results['tables_migrated']++;
            } catch (Exception $e) {
                $results['errors'][] = $e->getMessage();
            }
        }
        return $results;
    }
}
