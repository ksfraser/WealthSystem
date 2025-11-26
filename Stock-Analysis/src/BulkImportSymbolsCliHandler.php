<?php
require_once __DIR__ . '/BulkImportSymbolsAction.php';
require_once __DIR__ . '/TableTypeRegistry.php';

/**
 * CLI Handler for bulk importing stock symbols.
 * Follows SRP by focusing on CLI parsing and delegation to the business logic.
 */
class BulkImportSymbolsCliHandler
{
    private $bulkImportAction;

    /**
     * Constructor with dependency injection.
     *
     * @param BulkImportSymbolsAction $bulkImportAction The action to handle bulk imports
     */
    public function __construct(BulkImportSymbolsAction $bulkImportAction)
    {
        $this->bulkImportAction = $bulkImportAction;
    }

    /**
     * Run the CLI command for bulk importing symbols.
     *
     * @param array $argv Command line arguments
     * @return int Exit code (0 for success, 1 for failure)
     */
    public function run($argv)
    {
        try {
            $params = $this->parseArguments($argv);
            $symbols = $this->loadSymbols($params['options']);
            
            if (empty($symbols)) {
                echo "No valid symbols found\n";
                return 1;
            }

            $results = $this->bulkImportAction->execute($symbols, $params['dry_run']);
            $this->displayResults($results, $symbols);
            return 0;
        } catch (InvalidArgumentException $e) {
            echo "Error: " . $e->getMessage() . "\n";
            $this->showUsage();
            return 1;
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    /**
     * Parse command line arguments.
     *
     * @param array $argv Command line arguments
     * @return array Parsed parameters
     * @throws InvalidArgumentException If no symbols specified
     */
    private function parseArguments($argv)
    {
        $options = [];
        $dryRun = false;

        for ($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];
            if ($arg === '--dry-run') {
                $dryRun = true;
            } elseif ($arg === '--help') {
                $this->showUsage();
                return ['help' => true];
            } elseif (strpos($arg, '--file=') === 0) {
                $options['file'] = substr($arg, 7);
            } elseif (strpos($arg, '--symbols=') === 0) {
                $options['symbols'] = substr($arg, 10);
            }
        }

        if (empty($options)) {
            throw new InvalidArgumentException("No symbols specified");
        }

        return [
            'options' => $options,
            'dry_run' => $dryRun
        ];
    }

    /**
     * Load symbols from file and/or command line.
     *
     * @param array $options Parsed options containing file and/or symbols
     * @return array Valid symbols
     * @throws InvalidArgumentException If file not found
     */
    private function loadSymbols($options)
    {
        $symbols = [];

        // Load from file
        if (isset($options['file'])) {
            $symbols = array_merge($symbols, $this->loadSymbolsFromFile($options['file']));
        }

        // Load from command line
        if (isset($options['symbols'])) {
            $cmdSymbols = array_map('trim', explode(',', $options['symbols']));
            $cmdSymbols = array_map('strtoupper', $cmdSymbols);
            $symbols = array_merge($symbols, $cmdSymbols);
        }

        // Remove duplicates and validate
        $symbols = array_unique($symbols);
        return array_filter($symbols, [TableTypeRegistry::class, 'isValidSymbol']);
    }

    /**
     * Load symbols from a file.
     *
     * @param string $filename File path
     * @return array Symbols from file
     * @throws InvalidArgumentException If file not found
     */
    private function loadSymbolsFromFile($filename)
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException("File not found: {$filename}");
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $symbols = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') {
                continue; // Skip empty lines and comments
            }
            $symbols[] = strtoupper($line);
        }

        return $symbols;
    }

    /**
     * Display the bulk import results.
     *
     * @param array $results Results from BulkImportSymbolsAction
     * @param array $symbols Original symbol list
     */
    private function displayResults($results, $symbols)
    {
        echo "\n=== BULK IMPORT SUMMARY ===\n";
        echo "Total symbols processed: " . count($symbols) . "\n";
        echo "Already existed: " . count($results['existing']) . "\n";
        echo "Newly created: " . count($results['created']) . "\n";
        echo "Errors: " . count($results['errors']) . "\n";

        if (!empty($results['created'])) {
            echo "\nNewly created symbols:\n";
            foreach ($results['created'] as $symbol) {
                echo "  ✓ {$symbol}\n";
            }
        }

        if (!empty($results['existing'])) {
            echo "\nSymbols that already existed:\n";
            foreach ($results['existing'] as $symbol) {
                echo "  → {$symbol}\n";
            }
        }

        if (!empty($results['errors'])) {
            echo "\nErrors encountered:\n";
            foreach ($results['errors'] as $error) {
                echo "  ✗ {$error['symbol']}: {$error['error']}\n";
            }
        }
    }

    /**
     * Show usage information.
     */
    private function showUsage()
    {
        echo "Bulk Import Symbols Tool\n\n";
        echo "Usage:\n";
        echo "  php BulkImportSymbols.php [OPTIONS]\n\n";
        echo "Options:\n";
        echo "  --file=PATH          Import symbols from file (one per line)\n";
        echo "  --symbols=LIST       Comma-separated list of symbols\n";
        echo "  --dry-run           Show what would be done without making changes\n";
        echo "  --help              Show this help message\n\n";
        echo "Examples:\n";
        echo "  php BulkImportSymbols.php --symbols=IBM,AAPL,GOOGL\n";
        echo "  php BulkImportSymbols.php --file=symbols.txt --dry-run\n";
        echo "  php BulkImportSymbols.php --file=symbols.txt --symbols=TSLA,MSFT\n";
    }
}
