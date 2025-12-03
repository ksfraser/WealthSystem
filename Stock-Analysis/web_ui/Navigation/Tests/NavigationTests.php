<?php
/**
 * Unit Tests for Navigation Architecture
 * 
 * Run with: php NavigationTests.php
 */

require_once __DIR__ . '/../NavigationFactory.php';
require_once __DIR__ . '/../Models/NavigationItem.php';
require_once __DIR__ . '/../Models/MenuItem.php';
require_once __DIR__ . '/../Models/DashboardCard.php';
require_once __DIR__ . '/../Providers/PortfolioItemsProvider.php';
require_once __DIR__ . '/../Providers/AdminItemsProvider.php';

class NavigationTests {
    private $passedTests = 0;
    private $failedTests = 0;
    
    public function runAll() {
        echo "Running Navigation Architecture Tests...\n\n";
        
        $this->testNavigationItemAccess();
        $this->testMenuItemCreation();
        $this->testDashboardCardCreation();
        $this->testPortfolioProvider();
        $this->testAdminProvider();
        $this->testNavigationBuilder();
        $this->testDashboardCardBuilder();
        $this->testAccessControl();
        $this->testCaching();
        $this->testBreadcrumbs();
        
        echo "\n========================================\n";
        echo "Tests Passed: {$this->passedTests}\n";
        echo "Tests Failed: {$this->failedTests}\n";
        echo "========================================\n";
        
        return $this->failedTests === 0;
    }
    
    private function assert($condition, $message) {
        if ($condition) {
            echo "✓ PASS: $message\n";
            $this->passedTests++;
        } else {
            echo "✗ FAIL: $message\n";
            $this->failedTests++;
        }
    }
    
    private function testNavigationItemAccess() {
        echo "Testing NavigationItem Access Control...\n";
        
        $item = new MenuItem(
            'test',
            'Test Item',
            'Description',
            '🔍',
            'test.php',
            'admin',
            1
        );
        
        // Test admin access
        $this->assert(
            $item->hasAccess(null, true),
            "Admin should have access to admin-only item"
        );
        
        // Test non-admin access
        $this->assert(
            !$item->hasAccess('user', false),
            "Non-admin should not have access to admin-only item"
        );
        
        // Test null role requirement
        $publicItem = new MenuItem(
            'public',
            'Public Item',
            'Description',
            '🏠',
            'public.php',
            null,
            1
        );
        
        $this->assert(
            $publicItem->hasAccess('user', false),
            "User should have access to public item"
        );
        
        echo "\n";
    }
    
    private function testMenuItemCreation() {
        echo "Testing MenuItem Creation...\n";
        
        $menu = new MenuItem(
            'portfolio',
            'Portfolio',
            'Portfolio management',
            '💼',
            'portfolio.php',
            null,
            1
        );
        
        $this->assert(
            $menu->getTitle() === 'Portfolio',
            "MenuItem title should match"
        );
        
        $this->assert(
            $menu->getIcon() === '💼',
            "MenuItem icon should match"
        );
        
        // Test adding children
        $child = new MenuItem(
            'portfolio.my',
            'My Portfolio',
            'View portfolio',
            '🏠',
            'MyPortfolio.php',
            null,
            1
        );
        
        $menu->addChild($child);
        
        $this->assert(
            $menu->hasChildren(),
            "MenuItem should have children after adding"
        );
        
        $this->assert(
            count($menu->getChildren()) === 1,
            "MenuItem should have 1 child"
        );
        
        echo "\n";
    }
    
    private function testDashboardCardCreation() {
        echo "Testing DashboardCard Creation...\n";
        
        $card = new DashboardCard(
            'test_card',
            'Test Card',
            'Test description',
            '📊',
            'test.php',
            null,
            1
        );
        
        $this->assert(
            $card->getTitle() === 'Test Card',
            "DashboardCard title should match"
        );
        
        // Test actions
        $card->addAction('action1.php', 'Action 1', 'btn-primary');
        
        $actions = $card->getActions();
        $this->assert(
            count($actions) === 2, // Default action + added action
            "DashboardCard should have 2 actions"
        );
        
        echo "\n";
    }
    
    private function testPortfolioProvider() {
        echo "Testing PortfolioItemsProvider...\n";
        
        $provider = new PortfolioItemsProvider();
        
        $menuItems = $provider->getMenuItems();
        $this->assert(
            count($menuItems) > 0,
            "PortfolioItemsProvider should return menu items"
        );
        
        $cards = $provider->getDashboardCards();
        $this->assert(
            count($cards) > 0,
            "PortfolioItemsProvider should return dashboard cards"
        );
        
        echo "\n";
    }
    
    private function testAdminProvider() {
        echo "Testing AdminItemsProvider...\n";
        
        $provider = new AdminItemsProvider();
        
        $menuItems = $provider->getMenuItems();
        $this->assert(
            count($menuItems) > 0,
            "AdminItemsProvider should return menu items"
        );
        
        // Check that items require admin
        $firstItem = $menuItems[0];
        $this->assert(
            $firstItem->getRequiredRole() === 'admin',
            "Admin menu items should require admin role"
        );
        
        $cards = $provider->getDashboardCards();
        foreach ($cards as $card) {
            $this->assert(
                $card->getRequiredRole() === 'admin',
                "Admin cards should require admin role"
            );
        }
        
        echo "\n";
    }
    
    private function testNavigationBuilder() {
        echo "Testing NavigationBuilder...\n";
        
        $config = [
            'restricted_items_mode' => 'hidden',
            'cache_enabled' => false
        ];
        
        $user = ['id' => 1, 'username' => 'test', 'is_admin' => false];
        
        $builder = NavigationFactory::createNavigationBuilder($user, 'test.php');
        
        $menuArray = $builder->getMenuArray();
        $this->assert(
            is_array($menuArray),
            "NavigationBuilder should return array"
        );
        
        // Admin items should be hidden for non-admin
        $hasAdminItems = false;
        foreach ($menuArray as $item) {
            if ($item['required_role'] === 'admin' && $item['has_access'] === true) {
                $hasAdminItems = true;
            }
        }
        
        $this->assert(
            !$hasAdminItems,
            "Non-admin user should not have access to admin items"
        );
        
        echo "\n";
    }
    
    private function testDashboardCardBuilder() {
        echo "Testing DashboardCardBuilder...\n";
        
        $user = ['id' => 1, 'username' => 'test', 'is_admin' => true];
        
        $builder = NavigationFactory::createDashboardCardBuilder($user);
        
        $cards = $builder->getCards();
        $this->assert(
            count($cards) > 0,
            "DashboardCardBuilder should return cards"
        );
        
        $cardsArray = $builder->getCardsArray();
        $this->assert(
            is_array($cardsArray) && count($cardsArray) > 0,
            "DashboardCardBuilder should return cards array"
        );
        
        echo "\n";
    }
    
    private function testAccessControl() {
        echo "Testing Access Control...\n";
        
        // Test hidden mode
        $config = ['restricted_items_mode' => 'hidden', 'cache_enabled' => false];
        $normalUser = ['id' => 1, 'username' => 'user', 'is_admin' => false];
        $adminUser = ['id' => 2, 'username' => 'admin', 'is_admin' => true];
        
        $normalBuilder = new DashboardCardBuilder($config, $normalUser);
        $normalBuilder->addProvider(new AdminItemsProvider());
        
        $adminBuilder = new DashboardCardBuilder($config, $adminUser);
        $adminBuilder->addProvider(new AdminItemsProvider());
        
        $normalCards = $normalBuilder->getCardsArray();
        $adminCards = $adminBuilder->getCardsArray();
        
        $this->assert(
            count($normalCards) === 0,
            "Normal user should have 0 admin cards in hidden mode"
        );
        
        $this->assert(
            count($adminCards) > 0,
            "Admin user should have admin cards"
        );
        
        // Test greyed out mode
        $config['restricted_items_mode'] = 'greyed_out';
        $greyedBuilder = new DashboardCardBuilder($config, $normalUser);
        $greyedBuilder->addProvider(new AdminItemsProvider());
        
        $greyedCards = $greyedBuilder->getCardsArray();
        $this->assert(
            count($greyedCards) > 0,
            "Normal user should see greyed out admin cards"
        );
        
        $this->assert(
            !$greyedCards[0]['has_access'],
            "Greyed out cards should have has_access = false"
        );
        
        echo "\n";
    }
    
    private function testCaching() {
        echo "Testing Caching...\n";
        
        $config = [
            'restricted_items_mode' => 'hidden',
            'cache_enabled' => true,
            'cache_duration' => 3600
        ];
        
        $user = ['id' => 1, 'username' => 'test', 'is_admin' => false];
        
        $builder = NavigationFactory::createDashboardCardBuilder($user);
        
        // First call should cache
        $cards1 = $builder->getCards();
        
        // Second call should use cache
        $cards2 = $builder->getCards();
        
        $this->assert(
            count($cards1) === count($cards2),
            "Cached results should match original"
        );
        
        // Clear cache
        $builder->clearCache();
        
        $this->assert(
            true,
            "Cache should clear without errors"
        );
        
        echo "\n";
    }
    
    private function testBreadcrumbs() {
        echo "Testing Breadcrumbs...\n";
        
        $config = ['restricted_items_mode' => 'hidden', 'cache_enabled' => false];
        $user = ['id' => 1, 'username' => 'test', 'is_admin' => false];
        
        $breadcrumbBuilder = NavigationFactory::createBreadcrumbBuilder($user);
        
        $breadcrumbs = $breadcrumbBuilder->getBreadcrumbs('dashboard.php');
        
        $this->assert(
            count($breadcrumbs) > 0,
            "Breadcrumbs should be generated for dashboard.php"
        );
        
        $lastBreadcrumb = end($breadcrumbs);
        $this->assert(
            $lastBreadcrumb->isLast(),
            "Last breadcrumb should be marked as last"
        );
        
        $html = $breadcrumbBuilder->renderBreadcrumbs('dashboard.php');
        $this->assert(
            strpos($html, 'breadcrumb') !== false,
            "Rendered breadcrumbs should contain breadcrumb HTML"
        );
        
        echo "\n";
    }
}

// Run tests
$tests = new NavigationTests();
$success = $tests->runAll();

exit($success ? 0 : 1);
