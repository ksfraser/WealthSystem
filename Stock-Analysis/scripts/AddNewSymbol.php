<?php
require_once __DIR__ . '/../src/AddSymbolCliHandler.php';

$handler = new AddSymbolCliHandler();
$handler->run($argv);
