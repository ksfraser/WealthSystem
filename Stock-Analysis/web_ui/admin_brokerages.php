<?php
require_once __DIR__ . '/UserAuthDAO.php';
$auth = new UserAuthDAO();
$auth->requireAdmin();

require_once __DIR__ . '/MidCapBankImportDAO.php';

try {
    $dao = new MidCapBankImportDAO();
    $pdo = $dao->getPdo();
    
    if (!$pdo) {
        $errors = $dao->getErrors();
        throw new Exception('Database connection failed: ' . implode(', ', $errors));
    }
} catch (Exception $e) {
    echo '<div style="background: #ffeeee; border: 1px solid #cc0000; padding: 10px; margin: 10px 0;">';
    echo '<strong>Database Connection Error:</strong> ' . htmlspecialchars($e->getMessage());
    echo '<br><br><strong>Possible solutions:</strong>';
    echo '<ul>';
    echo '<li>Ensure PHP PDO MySQL extension is installed and enabled</li>';
    echo '<li>Check database configuration in db_config.yml</li>';
    echo '<li>Verify database server is running</li>';
    echo '</ul>';
    echo '</div>';
    // Don't proceed with database operations
    $pdo = null;
}

// Add brokerage
if ($pdo && isset($_POST['add_brokerage'])) {
    $name = trim($_POST['brokerage_name']);
    $stmt = $pdo->prepare('INSERT IGNORE INTO brokerages (name) VALUES (?)');
    $stmt->execute([$name]);
}

// List brokerages
$brokerages = [];
if ($pdo) {
    try {
        $brokerages = $pdo->query('SELECT * FROM brokerages ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo '<div style="background: #ffeeee; border: 1px solid #cc0000; padding: 10px; margin: 10px 0;">';
        echo '<strong>Query Error:</strong> ' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}
?>
<h2>Brokerages</h2>

<?php if ($pdo): ?>
<form method="post">
    <input type="text" name="brokerage_name" placeholder="Brokerage Name" required>
    <button type="submit" name="add_brokerage">Add Brokerage</button>
</form>

<ul>
<?php foreach ($brokerages as $b): ?>
    <li><?= htmlspecialchars($b['name']) ?></li>
<?php endforeach; ?>
</ul>

<?php if (empty($brokerages)): ?>
    <p><em>No brokerages found. Add one using the form above.</em></p>
<?php endif; ?>

<?php else: ?>
    <p><strong>Database functionality is currently unavailable.</strong></p>
    <p>The brokerage management features require a working database connection.</p>
<?php endif; ?>
