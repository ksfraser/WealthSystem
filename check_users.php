<?php
require_once __DIR__ . '/web_ui/DbConfigClasses.php';

$dbConfig = new LegacyDatabaseConfig();
$conn = $dbConfig->createConnection();

$stmt = $conn->query('SELECT id, username FROM users LIMIT 5');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Existing users:\n";
foreach ($users as $user) {
    echo $user['id'] . ': ' . $user['username'] . "\n";
}
?>