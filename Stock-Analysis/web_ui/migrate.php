<?php
require_once 'UiStyles.php';
require_once 'QuickActions.php';

// Migration options
$dryRun = true;
$includePortfolios = true;
$includeTrades = true;
$includeAnalytics = false;
$logOutput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dryRun = isset($_POST['dry_run']);
    $includePortfolios = isset($_POST['include_portfolios']);
    $includeTrades = isset($_POST['include_trades']);
    $includeAnalytics = isset($_POST['include_analytics']);

    // Build command
    $cmd = 'python migrate_data.py';
    if ($dryRun) $cmd .= ' --dry-run';
    if ($includePortfolios) $cmd .= ' --portfolios';
    if ($includeTrades) $cmd .= ' --trades';
    if ($includeAnalytics) $cmd .= ' --analytics';

    // Run migration (simulate for now)
    $logOutput = shell_exec($cmd . ' 2>&1');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Data Migration</title>
    <?php UiStyles::render(); ?>
</head>
<body>
<div class="container">
    <?php QuickActions::render(); ?>
    <div class="header">
        <h1>Data Migration</h1>
        <p>Move or copy data between databases with full control. Use dry run to preview changes.</p>
    </div>
    <form method="post" class="card">
        <label><input type="checkbox" name="dry_run" <?= $dryRun?'checked':'' ?>> Dry Run (preview only, no changes)</label><br><br>
        <label><input type="checkbox" name="include_portfolios" <?= $includePortfolios?'checked':'' ?>> Migrate Portfolios</label><br>
        <label><input type="checkbox" name="include_trades" <?= $includeTrades?'checked':'' ?>> Migrate Trades</label><br>
        <label><input type="checkbox" name="include_analytics" <?= $includeAnalytics?'checked':'' ?>> Migrate Analytics</label><br><br>
        <button class="btn" type="submit">Start Migration</button>
    </form>
    <?php if ($logOutput): ?>
        <div class="card info">
            <h3>Migration Output</h3>
            <pre><?= htmlspecialchars($logOutput) ?></pre>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
