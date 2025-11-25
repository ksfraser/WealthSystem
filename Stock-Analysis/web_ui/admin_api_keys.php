<?php
// admin_api_keys.php
require_once 'UiStyles.php';
require_once 'QuickActions.php';
require_once __DIR__ . '/DbConfigClasses.php';

$configFile = '../db_config_refactored.yml';

// Ensure DB table exists
ensureApiKeysTable();

// Load from YAML
$yaml = file_exists($configFile) ? file_get_contents($configFile) : '';
function getYamlKey($yaml, $key) {
    if (preg_match('/'.preg_quote($key, '/').':\s*([\w-]*)/', $yaml, $m)) return $m[1];
    return '';
}
$api_keys = [
    'openai' => getYamlKey($yaml, 'openai'),
    'alpha_vantage' => getYamlKey($yaml, 'alpha_vantage'),
    'finnhub' => getYamlKey($yaml, 'finnhub'),
    'fmp' => getYamlKey($yaml, 'fmp'),
];

// Load from DB (overrides YAML if present)
try {
    $pdo = LegacyDatabaseConfig::createConnection();
    $stmt = $pdo->query('SELECT name, value FROM api_keys');
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach ($api_keys as $k => $v) {
        if (!empty($rows[$k])) $api_keys[$k] = $rows[$k];
    }
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($api_keys as $k => $v) {
        $api_keys[$k] = $_POST[$k] ?? $v;
    }
    // Save to YAML
    $yaml = file_get_contents($configFile);
    foreach ($api_keys as $k => $v) {
        $yaml = preg_replace('/'.preg_quote($k, '/').':\s*([\w-]*)/', "$k: $v", $yaml);
    }
    file_put_contents($configFile, $yaml);
    // Save to DB (name-value rows)
    try {
        $pdo = LegacyDatabaseConfig::createConnection();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->beginTransaction();
        foreach ($api_keys as $k => $v) {
            $stmt = $pdo->prepare("REPLACE INTO api_keys (name, value) VALUES (?, ?)");
            $stmt->execute([$k, $v]);
        }
        $pdo->commit();
        $msg = '<div class="card success">API keys updated in YAML and database!</div>';
    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
        $msg = '<div class="card warning">YAML updated, but DB update failed: '.htmlspecialchars($e->getMessage()).'</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>API Key Management</title>
    <?php UiStyles::render(); ?>
</head>
<body>
<div class="container">
    <?php QuickActions::render(); ?>
    <div class="header">
        <h1>API Key Management</h1>
        <p>Store and update API keys for external services. Keys are saved in both YAML and the database.</p>
    </div>
    <?php if (!empty($msg)) echo $msg; ?>
    <form method="post" class="card">
        <label>OpenAI API Key:
            <input type="text" name="openai" value="<?=htmlspecialchars($api_keys['openai'])?>" style="width:100%">
            <small>Get your key at <a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI API Keys</a></small>
        </label><br><br>
        <label>Alpha Vantage API Key:
            <input type="text" name="alpha_vantage" value="<?=htmlspecialchars($api_keys['alpha_vantage'])?>" style="width:100%">
            <small>Get your key at <a href="https://www.alphavantage.co/support/#api-key" target="_blank">Alpha Vantage</a></small>
        </label><br><br>
        <label>Finnhub API Key:
            <input type="text" name="finnhub" value="<?=htmlspecialchars($api_keys['finnhub'])?>" style="width:100%">
            <small>Get your key at <a href="https://finnhub.io/register" target="_blank">Finnhub</a></small>
        </label><br><br>
        <label>FMP API Key:
            <input type="text" name="fmp" value="<?=htmlspecialchars($api_keys['fmp'])?>" style="width:100%">
            <small>Get your key at <a href="https://financialmodelingprep.com/developer/docs/pricing/" target="_blank">Financial Modeling Prep</a></small>
        </label><br><br>
        <button class="btn" type="submit">Save API Keys</button>
    </form>
    <div class="card info">
        <h3>How to Acquire API Keys</h3>
        <ul>
            <li><b>OpenAI:</b> <a href="https://platform.openai.com/account/api-keys" target="_blank">Create an account and generate a key</a></li>
            <li><b>Alpha Vantage:</b> <a href="https://www.alphavantage.co/support/#api-key" target="_blank">Sign up for a free API key</a></li>
            <li><b>Finnhub:</b> <a href="https://finnhub.io/register" target="_blank">Register and get a free API key</a></li>
            <li><b>FMP:</b> <a href="https://financialmodelingprep.com/developer/docs/pricing/" target="_blank">Sign up for a free or paid API key</a></li>
        </ul>
    </div>
</div>
</body>
</html>
