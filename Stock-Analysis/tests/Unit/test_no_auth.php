<?php
/**
 * Simplified admin_users.php without UserAuthDAO for testing
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';
use Ksfraser\UIRenderer\Factories\UiFactory;

echo "<!DOCTYPE html><html><body>";
echo "<h1>Simple Test - No UserAuthDAO</h1>";

try {
    echo "<p>✓ Autoloader loaded</p>";
    echo "<p>✓ UiFactory imported</p>";
    
    // Create a simple card
    $card = UiFactory::createCard(
        'Test Card',
        '<p>This is a test to see if UiFactory works without UserAuthDAO.</p>'
    );
    echo "<p>✓ Card created</p>";
    
    echo "<div style='margin: 20px;'>";
    echo $card->render();
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
