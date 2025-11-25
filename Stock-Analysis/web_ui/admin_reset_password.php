<?php
// Route for admin password reset
require_once __DIR__ . '/controllers/AdminPasswordResetController.php';
$controller = new AdminPasswordResetController();
echo $controller->resetUserPassword();
