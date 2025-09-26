<?php
require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';
use Ksfraser\UIRenderer\Factories\UiFactory;

class TestController {
    public function renderPage() {
        try {
            $card = UiFactory::createCard('Test', 'Hello');
            $nav = UiFactory::createNavigation('Test', 'test', null, false, [], false);
            $page = UiFactory::createPage('Test', $nav, [$card]);
            return $page->render();
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
}

$controller = new TestController();
echo $controller->renderPage();
?>
