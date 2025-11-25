<?php
/**
 * User Logout Handler
 */

require_once __DIR__ . '/UserAuthDAO.php';

$auth = new UserAuthDAO();

// Logout user
$auth->logoutUser();

// Redirect to login with logout message
header('Location: login.php?logout=1');
exit;
?>
