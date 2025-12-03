<?php
/**
 * Navigation Architecture Usage Examples
 * 
 * This file demonstrates how to use the new SRP navigation architecture
 */

require_once __DIR__ . '/Navigation/NavigationFactory.php';
require_once __DIR__ . '/UserAuthDAO.php';

// ============================================================================
// EXAMPLE 1: Render Navigation Menu
// ============================================================================

function renderNavigationExample() {
    // Get current user
    $auth = new UserAuthDAO();
    $user = $auth->getCurrentUser();
    
    // Get current page path
    $currentPath = basename($_SERVER['PHP_SELF']);
    
    // Create navigation builder
    $navBuilder = NavigationFactory::createNavigationBuilder($user, $currentPath);
    
    // Render menu HTML
    echo '<nav class="navbar navbar-expand-lg">';
    echo '<ul class="navbar-nav">';
    echo $navBuilder->renderMenu();
    echo '</ul>';
    echo '</nav>';
}

// ============================================================================
// EXAMPLE 2: Render Dashboard Cards
// ============================================================================

function renderDashboardExample() {
    // Get current user
    $auth = new UserAuthDAO();
    $user = $auth->getCurrentUser();
    
    // Create dashboard builder
    $dashboardBuilder = NavigationFactory::createDashboardCardBuilder($user);
    
    // Render cards HTML
    echo '<div class="row">';
    echo $dashboardBuilder->renderCards();
    echo '</div>';
}

// ============================================================================
// EXAMPLE 3: Get Cards as Array (for custom rendering)
// ============================================================================

function getCardsArrayExample() {
    $auth = new UserAuthDAO();
    $user = $auth->getCurrentUser();
    
    $dashboardBuilder = NavigationFactory::createDashboardCardBuilder($user);
    $cards = $dashboardBuilder->getCardsArray();
    
    // Now you can render cards however you want
    foreach ($cards as $card) {
        echo "Card: {$card['title']}\n";
        echo "  URL: {$card['url']}\n";
        echo "  Has Access: " . ($card['has_access'] ? 'Yes' : 'No') . "\n";
        echo "  Actions: " . count($card['actions']) . "\n";
    }
}

// ============================================================================
// EXAMPLE 4: Using a Specific Provider
// ============================================================================

function useSpecificProviderExample() {
    // Get just portfolio items
    $provider = NavigationFactory::getProvider('PortfolioItemsProvider');
    
    $menuItems = $provider->getMenuItems();
    $cards = $provider->getDashboardCards();
    
    echo "Portfolio Menu Items: " . count($menuItems) . "\n";
    echo "Portfolio Dashboard Cards: " . count($cards) . "\n";
}

// ============================================================================
// EXAMPLE 5: Modify Configuration at Runtime
// ============================================================================

function modifyConfigExample() {
    // Load config
    $config = require __DIR__ . '/config/navigation.php';
    
    // Change to hidden mode
    $config['restricted_items_mode'] = 'hidden';
    
    // Create builder with modified config
    $auth = new UserAuthDAO();
    $user = $auth->getCurrentUser();
    
    $navBuilder = new NavigationBuilder($config, $user, basename($_SERVER['PHP_SELF']));
    
    // Register providers manually if needed
    $navBuilder->addProvider(new PortfolioItemsProvider());
    $navBuilder->addProvider(new AdminItemsProvider());
    
    // Now restricted items will be hidden instead of greyed out
    echo $navBuilder->renderMenu();
}

// ============================================================================
// USAGE IN ACTUAL FILES
// ============================================================================

/*

// In dashboard.php:
// -----------------
require_once __DIR__ . '/Navigation/NavigationFactory.php';
require_once __DIR__ . '/UserAuthDAO.php';

$auth = new UserAuthDAO();
$auth->requireLogin();
$user = $auth->getCurrentUser();

// Get dashboard cards
$dashboardBuilder = NavigationFactory::createDashboardCardBuilder($user);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>
    <div class="container">
        <h1>Dashboard</h1>
        <div class="row">
            <?php echo $dashboardBuilder->renderCards(); ?>
        </div>
    </div>
</body>
</html>


// In NavigationService.php renderNavigationHeader():
// ---------------------------------------------------
require_once __DIR__ . '/Navigation/NavigationFactory.php';

public function renderNavigationHeader(?array $user = null, string $currentPath = ''): string {
    $navBuilder = NavigationFactory::createNavigationBuilder($user, $currentPath);
    
    $html = '<nav class="navbar navbar-expand-lg navbar-light bg-light">';
    $html .= '<div class="container-fluid">';
    $html .= '<a class="navbar-brand" href="index.php">Portfolio Tracker</a>';
    $html .= '<ul class="navbar-nav me-auto mb-2 mb-lg-0">';
    $html .= $navBuilder->renderMenu();
    $html .= '</ul>';
    $html .= '</div>';
    $html .= '</nav>';
    
    return $html;
}


// In MyPortfolio.php:
// -------------------
require_once __DIR__ . '/Navigation/NavigationFactory.php';
require_once __DIR__ . '/UserAuthDAO.php';

$auth = new UserAuthDAO();
$auth->requireLogin();
$user = $auth->getCurrentUser();

// Get feature cards (same as dashboard)
$dashboardBuilder = NavigationFactory::createDashboardCardBuilder($user);
$featureCards = $dashboardBuilder->getCardsArray();

// Add "Dashboard Hub" card at the beginning
array_unshift($featureCards, [
    'id' => 'dashboard_hub',
    'title' => '🏠 Dashboard Hub',
    'description' => 'Access the main portfolio dashboard and system overview.',
    'icon' => '🏠',
    'url' => 'dashboard.php',
    'actions' => [
        ['url' => 'dashboard.php', 'label' => 'Go to Dashboard', 'class' => 'btn-primary']
    ],
    'has_access' => true
]);

// Use UIRenderer to render cards
$cardRenderer = new CardComponent();
foreach ($featureCards as $card) {
    $cardDto = new CardDto(
        $card['title'],
        $card['description'],
        $card['actions']
    );
    echo $cardRenderer->render($cardDto);
}

*/
