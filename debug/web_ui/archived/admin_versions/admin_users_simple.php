<?php
/**
 * Simple Admin Users Test - Minimal version
 */

require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';
use Ksfraser\UIRenderer\Factories\UiFactory;

try {
    echo UiFactory::createCard('Test', 'Hello World')->render();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
