<?php

/**
 * Symbol Management Script
 * View and manage existing symbols and their tables
 * Usage: php ManageSymbols.php [command] [options]
 */

require_once __DIR__ . '/../DatabaseConfig.php';
require_once __DIR__ . '/../StockTableManager.php';

function showUsage() {
    <?php
    require_once __DIR__ . '/../src/ManageSymbolsCliHandler.php';

    $handler = new ManageSymbolsCliHandler();
    $handler->run($argv);
    echo "  php ManageSymbols.php remove SYMBOL          # Remove symbol and its tables\n";
