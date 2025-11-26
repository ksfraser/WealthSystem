<?php

/**
 * Bulk Symbol Import Script
 * Creates tables for multiple symbols from a file or command line list
 * Usage: php BulkImportSymbols.php [--file=symbols.txt] [--symbols=IBM,MSFT,AAPL]
 */

require_once __DIR__ . '/../DatabaseConfig.php';
require_once __DIR__ . '/../StockTableManager.php';

function showUsage() {
    echo "Usage:\n";
    echo "  php BulkImportSymbols.php --file=symbols.txt\n";
    echo "  php BulkImportSymbols.php --symbols=IBM,MSFT,AAPL,GOOGL\n";
    echo "  php BulkImportSymbols.php --file=symbols.txt --dry-run\n";
    echo "\nOptions:\n";
    echo "  --file=FILE        Read symbols from a text file (one per line)\n";
    echo "  --symbols=LIST     Comma-separated list of symbols\n";
    echo "  --dry-run          Show what would be done without making changes\n";
    echo "  --help             Show this help message\n";
    echo "\nFile format (symbols.txt):\n";
    echo "  IBM\n";
    echo "  MSFT\n";
    echo "  AAPL\n";
    echo "  # Comments start with #\n";
}

<?php
require_once __DIR__ . '/../src/BulkImportSymbolsCliHandler.php';

$handler = new BulkImportSymbolsCliHandler();
$handler->run($argv);
