<?php

require __DIR__ . '/vendor/autoload.php';

use App\Repositories\StrategyParametersRepository;

$repo = new StrategyParametersRepository(__DIR__ . '/storage/database/stock_analysis.db');
$params = $repo->getStrategyParameters('Warren Buffett Value Strategy');

echo "Warren Buffett Value Strategy Parameters:\n";
echo "==========================================\n";
echo "min_roe_percent: " . $params['min_roe_percent'] . "\n";
echo "margin_of_safety_percent: " . $params['margin_of_safety_percent'] . "\n";
echo "min_profit_margin_percent: " . $params['min_profit_margin_percent'] . "\n";
echo "stop_loss_percent: " . $params['stop_loss_percent'] . "\n";
