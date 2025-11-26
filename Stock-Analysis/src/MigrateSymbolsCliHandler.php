<?php
require_once __DIR__ . '/../DatabaseConfig.php';
require_once __DIR__ . '/../StockTableManager.php';
require_once __DIR__ . '/../DynamicStockDataAccess.php';
require_once __DIR__ . '/IStockTableManager.php';
require_once __DIR__ . '/IStockDataAccess.php';
require_once __DIR__ . '/MigrateSymbolAction.php';

class MigrateSymbolsCliHandler
{
    public function run($argv)
    {
        $options = [
            'symbol' => null,
            'dry_run' => false,
            'batch_size' => 1000,
            'force' => false
        ];
        for ($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];
            if ($arg === '--dry-run') {
                $options['dry_run'] = true;
            } elseif ($arg === '--force') {
                $options['force'] = true;
            } elseif (strpos($arg, '--symbol=') === 0) {
                $options['symbol'] = strtoupper(substr($arg, 9));
            } elseif (strpos($arg, '--batch-size=') === 0) {
                $options['batch_size'] = intval(substr($arg, 13));
            }
        }
        DatabaseConfig::load();
        $pdo = DatabaseConfig::createMicroCapConnection();
        $tableManager = new StockTableManager();
        $dataAccess = new DynamicStockDataAccess();
        $action = new MigrateSymbolAction($tableManager, $dataAccess, $pdo);
        // Define legacy table mappings
        $legacyTables = [
            'historical_prices' => [
                'target' => 'prices',
                'symbol_column' => 'symbol',
                'required_columns' => ['symbol', 'date', 'open', 'high', 'low', 'close']
            ],
            'technical_indicators' => [
                'target' => 'indicators',
                'symbol_column' => 'symbol',
                'required_columns' => ['symbol', 'date', 'indicator_name', 'value']
            ],
            'candlestick_patterns' => [
                'target' => 'patterns',
                'symbol_column' => 'symbol',
                'required_columns' => ['symbol', 'date', 'pattern_name']
            ]
        ];
        // Get symbols to migrate
        $symbolsToMigrate = [];
        if ($options['symbol']) {
            $symbolsToMigrate = [$options['symbol']];
        } else {
            foreach ($legacyTables as $tableName => $config) {
                $stmt = $pdo->query("SELECT DISTINCT {$config['symbol_column']} as symbol FROM {$tableName} ORDER BY symbol");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (!empty($row['symbol'])) {
                        $symbolsToMigrate[] = $row['symbol'];
                    }
                }
            }
            $symbolsToMigrate = array_unique($symbolsToMigrate);
        }
        if (empty($symbolsToMigrate)) {
            echo "No symbols found to migrate.\n";
            exit(0);
        }
        foreach ($symbolsToMigrate as $symbol) {
            $result = $action->execute($symbol, $legacyTables, $options);
            echo "\n--- {$symbol} ---\n";
            echo "Tables migrated: {$result['tables_migrated']}\n";
            echo "Total records: {$result['total_records']}\n";
            if (!empty($result['errors'])) {
                echo "Errors:\n";
                foreach ($result['errors'] as $err) {
                    echo "  - {$err}\n";
                }
            }
        }
    }
}
