<?php
require_once __DIR__ . "/BankAccountsDAO.php";
require_once __DIR__ . "/EnhancedUserAuthDAO.php";

// Only execute web interface when accessed directly, not when included in tests
if (basename(__FILE__) !== basename($_SERVER["SCRIPT_FILENAME"])) {
    return; // Exit if included in another file
}

echo "Web interface loaded successfully";
?>
