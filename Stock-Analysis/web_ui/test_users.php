<?php
require_once 'UserAuthDAO.php';

try {
    $userAuth = new UserAuthDAO();
    $users = $userAuth->getAllUsers(1000);
    echo 'Found ' . count($users) . ' users:' . PHP_EOL;
    foreach ($users as $user) {
        echo '  ID: ' . $user['id'] . ', Username: ' . $user['username'] . ', Email: ' . $user['email'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>