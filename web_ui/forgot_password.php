<?php
// Route for forgot password (request reset)
require_once __DIR__ . '/controllers/PasswordResetController.php';
$controller = new PasswordResetController();
echo $controller->forgotPassword();
