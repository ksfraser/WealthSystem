<?php
require_once __DIR__ . '/../DatabaseConfig.php';
require_once __DIR__ . '/../StockTableManager.php';
require_once __DIR__ . '/IStockTableManager.php';
require_once __DIR__ . '/ManageSymbolsAction.php';

class ManageSymbolsCliHandler
{
    public function run($argv)
    {
        if (count($argv) < 2) {
            $this->showUsage();
            throw new \RuntimeException('Insufficient arguments');
        }
        $command = $argv[1];
        $symbol = isset($argv[2]) ? strtoupper($argv[2]) : null;
        $options = [
            'force' => in_array('--force', $argv),
            'verbose' => in_array('--verbose', $argv)
        ];
        DatabaseConfig::load();
        $pdo = DatabaseConfig::createMicroCapConnection();
        $tableManager = new StockTableManager();
        $action = new ManageSymbolsAction($tableManager, $pdo);
        switch ($command) {
            case 'list':
                $symbols = $action->listSymbols();
                echo "Registered Symbols (" . count($symbols) . "):\n";
                foreach ($symbols as $symbolData) {
                    echo $symbolData['symbol'] . "\t" . ($symbolData['active'] ? 'Active' : 'Inactive') . "\n";
                }
                break;
            case 'stats':
                if (!$symbol) {
                    echo "Error: Symbol required for stats command\n";
                    throw new \RuntimeException('Symbol required for stats command');
                }
                $stats = $action->stats($symbol);
                print_r($stats);
                break;
            case 'check':
                $symbolsToCheck = $symbol ? [$symbol] : array_column($action->listSymbols(), 'symbol');
                $results = $action->check($symbolsToCheck, $options['verbose']);
                foreach ($results as $result) {
                    echo $result['symbol'] . ': ' . ($result['exists'] ? '✓' : '✗') . "\n";
                }
                break;
            case 'remove':
                if (!$symbol) {
                    echo "Error: Symbol required for remove command\n";
                    throw new \RuntimeException('Symbol required for remove command');
                }
                if (!$options['force']) {
                    echo "Type 'DELETE {$symbol}' to confirm: ";
                    $handle = fopen("php://stdin", "r");
                    $line = trim(fgets($handle));
                    fclose($handle);
                    if ($line !== "DELETE {$symbol}") {
                        echo "Operation cancelled.\n";
                        throw new \RuntimeException('Operation cancelled');
                    }
                }
                $result = $action->remove($symbol);
                echo $result ? "✓ Symbol {$symbol} removed.\n" : "✗ Failed to remove symbol.\n";
                break;
            case 'deactivate':
                if (!$symbol) {
                    echo "Error: Symbol required for deactivate command\n";
                    throw new \RuntimeException('Symbol required for deactivate command');
                }
                $result = $action->deactivate($symbol);
                echo $result ? "✓ Symbol {$symbol} deactivated.\n" : "✗ Failed to deactivate symbol.\n";
                break;
            case 'activate':
                if (!$symbol) {
                    echo "Error: Symbol required for activate command\n";
                    throw new \RuntimeException('Symbol required for activate command');
                }
                $result = $action->activate($symbol);
                echo $result ? "✓ Symbol {$symbol} activated.\n" : "✗ Failed to activate symbol.\n";
                break;
            case 'cleanup':
                $orphaned = $action->cleanup();
                if (empty($orphaned)) {
                    echo "No orphaned tables found.\n";
                } else {
                    echo "Removed orphaned tables:\n";
                    foreach ($orphaned as $table) {
                        echo "  - {$table}\n";
                    }
                }
                break;
            default:
                echo "Unknown command: {$command}\n\n";
                $this->showUsage();
                throw new \RuntimeException('Unknown command');
        }
    }
    private function showUsage()
    {
        echo "Symbol Management Tool\n\n";
        echo "Usage:\n";
        echo "  php ManageSymbols.php list                    # List all symbols\n";
        echo "  php ManageSymbols.php stats SYMBOL           # Show table statistics for symbol\n";
        echo "  php ManageSymbols.php check [SYMBOL]         # Check table integrity\n";
        echo "  php ManageSymbols.php remove SYMBOL          # Remove symbol and its tables\n";
        echo "  php ManageSymbols.php deactivate SYMBOL      # Deactivate symbol\n";
        echo "  php ManageSymbols.php activate SYMBOL        # Activate symbol\n";
        echo "  php ManageSymbols.php cleanup                # Remove orphaned tables\n";
        echo "\nOptions:\n";
        echo "  --force      Skip confirmation prompts\n";
        echo "  --verbose    Show detailed output\n";
        echo "  --help       Show this help message\n";
    }
}
