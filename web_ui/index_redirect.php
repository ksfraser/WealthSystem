<?php
/**
 * Redirect to MyPortfolio.php - Maintains backward compatibility
 * 
 * The main portfolio page has been renamed from index.php to MyPortfolio.php
 * for better clarity. This redirect ensures existing bookmarks still work.
 */

// Redirect to the new MyPortfolio page
header('Location: MyPortfolio.php');
exit;
?>