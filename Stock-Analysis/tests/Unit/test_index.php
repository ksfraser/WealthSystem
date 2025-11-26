<?php
// Minimal index test with no prior output
require_once 'auth_check.php';
require_once 'NavigationManager.php';

$navManager = getNavManager();
$currentUser = $navManager->getCurrentUser();
$isAdmin = $navManager->isAdmin();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Index Test</title>
    <style><?php echo $navManager->getNavigationCSS(); ?></style>
</head>
<body>
    <?php $navManager->renderNavigationHeader('Test Dashboard', 'dashboard'); ?>
    <h1>Index Test Working</h1>
    <p>User: <?php echo $currentUser ? htmlspecialchars($currentUser['username']) : 'Not logged in'; ?></p>
    <p>Admin: <?php echo $isAdmin ? 'Yes' : 'No'; ?></p>
</body>
</html>
