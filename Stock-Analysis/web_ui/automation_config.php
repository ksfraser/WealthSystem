
<?php
// automation_config.php

require_once 'UiStyles.php';
require_once 'QuickActions.php';
require_once __DIR__ . '/DbConfigClasses.php';

// Path to config file
 $defaults = [
     'market_cap' => 'micro',
     'max_trades_per_day' => 10,
     'stop_loss' => 0.15,
     'position_limit' => 0.1,
 ];

$configFile = '../db_config_refactored.yml';
$config = $defaults;

// Load from YAML
if (file_exists($configFile)) {
    $content = file_get_contents($configFile);
    if (preg_match('/market_cap:\s*(\w+)/', $content, $m)) $config['market_cap'] = $m[1];
    if (preg_match('/max_trades_per_day:\s*(\d+)/', $content, $m)) $config['max_trades_per_day'] = $m[1];
    if (preg_match('/stop_loss:\s*([0-9.]+)/', $content, $m)) $config['stop_loss'] = $m[1];
    if (preg_match('/position_limit:\s*([0-9.]+)/', $content, $m)) $config['position_limit'] = $m[1];
}

// Load from DB (if exists, overrides YAML) - use LEGACY DB
try {
    $pdo = LegacyDatabaseConfig::createConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Create table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS automation_config (
        id INT PRIMARY KEY NOT NULL DEFAULT 1,
        market_cap VARCHAR(16) NOT NULL,
        max_trades_per_day INT NOT NULL,
        stop_loss FLOAT NOT NULL,
        position_limit FLOAT NOT NULL
    )");
    $stmt = $pdo->query("SELECT * FROM automation_config WHERE id=1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $config['market_cap'] = $row['market_cap'];
        $config['max_trades_per_day'] = $row['max_trades_per_day'];
        $config['stop_loss'] = $row['stop_loss'];
        $config['position_limit'] = $row['position_limit'];
    }
} catch (Exception $e) {
    // DB not available, ignore for now
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config['market_cap'] = $_POST['market_cap'] ?? $config['market_cap'];
    $config['max_trades_per_day'] = $_POST['max_trades_per_day'] ?? $config['max_trades_per_day'];
    $config['stop_loss'] = $_POST['stop_loss'] ?? $config['stop_loss'];
    $config['position_limit'] = $_POST['position_limit'] ?? $config['position_limit'];

    // Save to YAML (append or update block)
    $yaml = file_get_contents($configFile);
    $yaml = preg_replace('/market_cap:\s*\w+/', 'market_cap: ' . $config['market_cap'], $yaml);
    $yaml = preg_replace('/max_trades_per_day:\s*\d+/', 'max_trades_per_day: ' . $config['max_trades_per_day'], $yaml);
    $yaml = preg_replace('/stop_loss:\s*[0-9.]+/', 'stop_loss: ' . $config['stop_loss'], $yaml);
    $yaml = preg_replace('/position_limit:\s*[0-9.]+/', 'position_limit: ' . $config['position_limit'], $yaml);
    file_put_contents($configFile, $yaml);

    // Save to DB (LEGACY DB)
    try {
        $pdo = LegacyDatabaseConfig::createConnection();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE TABLE IF NOT EXISTS automation_config (
            id INT PRIMARY KEY NOT NULL DEFAULT 1,
            market_cap VARCHAR(16) NOT NULL,
            max_trades_per_day INT NOT NULL,
            stop_loss FLOAT NOT NULL,
            position_limit FLOAT NOT NULL
        )");
        // Upsert row
        $stmt = $pdo->prepare("REPLACE INTO automation_config (id, market_cap, max_trades_per_day, stop_loss, position_limit) VALUES (1,?,?,?,?)");
        $stmt->execute([
            $config['market_cap'],
            $config['max_trades_per_day'],
            $config['stop_loss'],
            $config['position_limit']
        ]);
        echo '<div class="card success">Configuration updated in YAML and database!</div>';
    } catch (Exception $e) {
        echo '<div class="card warning">YAML updated, but DB update failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Automation Configuration</title>
    <?php UiStyles::render(); ?>
</head>
<body>
<div class="container">
    <?php QuickActions::render(); ?>
    <div class="header">
        <h1>Automation Configuration</h1>
        <p>Set up automation parameters for trading scripts</p>
    </div>
    <form method="post" class="card">
        <label>Market Cap:
            <select name="market_cap">
                <option value="micro" <?= $config['market_cap']=='micro'?'selected':'' ?>>Micro</option>
                <option value="small" <?= $config['market_cap']=='small'?'selected':'' ?>>Small</option>
                <option value="mid" <?= $config['market_cap']=='mid'?'selected':'' ?>>Mid</option>
            </select>
        </label><br><br>
        <label>Max Trades Per Day:
            <input type="number" name="max_trades_per_day" value="<?= htmlspecialchars($config['max_trades_per_day']) ?>">
        </label><br><br>
        <label>Stop Loss (%):
            <input type="number" step="0.01" name="stop_loss" value="<?= htmlspecialchars($config['stop_loss']) ?>">
        </label><br><br>
        <label>Position Limit (%):
            <input type="number" step="0.01" name="position_limit" value="<?= htmlspecialchars($config['position_limit']) ?>">
        </label><br><br>
        <button class="btn" type="submit">Save Configuration</button>
    </form>
</div>
</body>
</html>
