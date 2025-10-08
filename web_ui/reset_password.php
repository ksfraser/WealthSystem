<?php
// Route for reset password (with token)
require_once __DIR__ . '/controllers/PasswordResetController.php';
$controller = new PasswordResetController();
echo $controller->resetPassword();
