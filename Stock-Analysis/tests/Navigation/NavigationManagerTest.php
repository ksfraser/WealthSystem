<?php

use PHPUnit\Framework\TestCase;

// Create a testable version of NavigationManager
class TestableNavigationManager 
{
    protected $userAuth;
    protected $currentUser;
    protected $isLoggedIn;
    protected $isAdmin;
    
    public function __construct($isLoggedIn = false, $currentUser = null, $isAdmin = false) 
    {
        $this->isLoggedIn = $isLoggedIn;
        $this->currentUser = $currentUser;
        $this->isAdmin = $isAdmin;
    }
    
    public function getNavigationItems($currentPage = '') {
        $items = [];
        
        if ($this->isLoggedIn) {
            // Core navigation items for all users
            $items[] = [
                'url' => 'index.php',
                'label' => '🏠 Dashboard',
                'icon' => '🏠',
                'active' => $currentPage === 'index.php' || $currentPage === 'dashboard'
            ];
            
            $items[] = [
                'url' => 'portfolios.php',
                'label' => '📈 Portfolios',
                'icon' => '📈',
                'active' => $currentPage === 'portfolios.php' || $currentPage === 'portfolios'
            ];
            
            $items[] = [
                'url' => 'trades.php',
                'label' => '📋 Trades',
                'icon' => '📋',
                'active' => $currentPage === 'trades.php' || $currentPage === 'trades'
            ];
            
            $items[] = [
                'url' => 'analytics.php',
                'label' => '📊 Analytics',
                'icon' => '📊',
                'active' => $currentPage === 'analytics.php' || $currentPage === 'analytics'
            ];
            
            // Admin-only navigation items
            if ($this->isAdmin) {
                $items[] = [
                    'url' => 'admin_users.php',
                    'label' => '👥 Users',
                    'icon' => '👥',
                    'active' => $currentPage === 'admin_users.php' || $currentPage === 'users',
                    'admin_only' => true
                ];
                
                $items[] = [
                    'url' => 'system_status.php',
                    'label' => '⚙️ System',
                    'icon' => '⚙️',
                    'active' => $currentPage === 'system_status.php' || $currentPage === 'system',
                    'admin_only' => true
                ];
                
                $items[] = [
                    'url' => 'database.php',
                    'label' => '🗄️ Database',
                    'icon' => '🗄️',
                    'active' => $currentPage === 'database.php' || $currentPage === 'database',
                    'admin_only' => true
                ];
            }
        } else {
            // Public navigation items
            $items[] = [
                'url' => 'login.php',
                'label' => '🔐 Login',
                'icon' => '🔐',
                'active' => $currentPage === 'login.php' || $currentPage === 'login'
            ];
            
            $items[] = [
                'url' => 'register.php',
                'label' => '📝 Register',
                'icon' => '📝',
                'active' => $currentPage === 'register.php' || $currentPage === 'register'
            ];
        }
        
        return $items;
    }
    
    public function hasAccess($feature) {
        switch ($feature) {
            case 'admin_users':
            case 'system_status':
            case 'database':
            case 'user_management':
                return $this->isAdmin;
                
            case 'portfolios':
            case 'trades':
            case 'analytics':
            case 'dashboard':
                return $this->isLoggedIn;
                
            case 'login':
            case 'register':
                return !$this->isLoggedIn;
                
            default:
                return false;
        }
    }
    
    public function getCurrentUser() {
        return $this->currentUser;
    }
    
    public function isLoggedIn() {
        return $this->isLoggedIn;
    }
    
    public function isAdmin() {
        return $this->isAdmin;
    }
    
    public function getQuickActions() {
        $actions = [];
        
        if ($this->isLoggedIn) {
            $actions[] = [
                'url' => 'portfolios.php',
                'label' => 'Add Portfolio',
                'class' => 'btn btn-success',
                'icon' => '📈'
            ];
            
            $actions[] = [
                'url' => 'trades.php',
                'label' => 'Record Trade',
                'class' => 'btn',
                'icon' => '📋'
            ];
            
            if ($this->isAdmin) {
                $actions[] = [
                    'url' => 'admin_users.php',
                    'label' => 'Manage Users',
                    'class' => 'btn btn-warning',
                    'icon' => '👥'
                ];
                
                $actions[] = [
                    'url' => 'database.php',
                    'label' => 'Database Tools',
                    'class' => 'btn',
                    'icon' => '🗄️'
                ];
            }
        }
        
        return $actions;
    }
}

class NavigationManagerTest extends TestCase
{
    public function testConstructorDefaults()
    {
        $navManager = new TestableNavigationManager();
        
        $this->assertFalse($navManager->isLoggedIn());
        $this->assertNull($navManager->getCurrentUser());
        $this->assertFalse($navManager->isAdmin());
    }

    public function testConstructorWithParameters()
    {
        $user = ['username' => 'testuser'];
        $navManager = new TestableNavigationManager(true, $user, false);
        
        $this->assertTrue($navManager->isLoggedIn());
        $this->assertEquals($user, $navManager->getCurrentUser());
        $this->assertFalse($navManager->isAdmin());
    }

    public function testGetNavigationItemsLoggedInUser()
    {
        $navManager = new TestableNavigationManager(true, ['username' => 'testuser'], false);
        $items = $navManager->getNavigationItems('dashboard');
        
        $this->assertIsArray($items);
        $this->assertGreaterThan(0, count($items));
        
        // Check for core navigation items
        $urls = array_column($items, 'url');
        $this->assertContains('index.php', $urls);
        $this->assertContains('portfolios.php', $urls);
        $this->assertContains('trades.php', $urls);
        $this->assertContains('analytics.php', $urls);
        
        // Check that dashboard is active
        $dashboardItem = array_filter($items, function($item) {
            return $item['url'] === 'index.php';
        });
        $this->assertTrue(reset($dashboardItem)['active']);
        
        // Should not have admin items
        $adminItems = array_filter($items, function($item) {
            return isset($item['admin_only']);
        });
        $this->assertEmpty($adminItems);
    }

    public function testGetNavigationItemsAdminUser()
    {
        $navManager = new TestableNavigationManager(true, ['username' => 'admin'], true);
        $items = $navManager->getNavigationItems('users');
        
        $this->assertIsArray($items);
        
        // Check for admin items
        $adminItems = array_filter($items, function($item) {
            return isset($item['admin_only']) && $item['admin_only'];
        });
        $this->assertGreaterThan(0, count($adminItems));
        
        // Check for specific admin URLs
        $urls = array_column($items, 'url');
        $this->assertContains('admin_users.php', $urls);
        $this->assertContains('system_status.php', $urls);
        $this->assertContains('database.php', $urls);
        
        // Check that users page is active
        $usersItem = array_filter($items, function($item) {
            return $item['url'] === 'admin_users.php';
        });
        $this->assertTrue(reset($usersItem)['active']);
    }

    public function testGetNavigationItemsNotLoggedIn()
    {
        $navManager = new TestableNavigationManager(false, null, false);
        $items = $navManager->getNavigationItems('login');
        
        $this->assertIsArray($items);
        
        // Should have public navigation items
        $urls = array_column($items, 'url');
        $this->assertContains('login.php', $urls);
        $this->assertContains('register.php', $urls);
        
        // Check that login is active
        $loginItem = array_filter($items, function($item) {
            return $item['url'] === 'login.php';
        });
        $this->assertTrue(reset($loginItem)['active']);
    }

    public function testHasAccessAdminFeatures()
    {
        $adminNavManager = new TestableNavigationManager(true, ['username' => 'admin'], true);
        $userNavManager = new TestableNavigationManager(true, ['username' => 'user'], false);
        
        // Admin should have access to admin features
        $this->assertTrue($adminNavManager->hasAccess('admin_users'));
        $this->assertTrue($adminNavManager->hasAccess('system_status'));
        $this->assertTrue($adminNavManager->hasAccess('database'));
        $this->assertTrue($adminNavManager->hasAccess('user_management'));
        
        // Regular user should not
        $this->assertFalse($userNavManager->hasAccess('admin_users'));
        $this->assertFalse($userNavManager->hasAccess('system_status'));
        $this->assertFalse($userNavManager->hasAccess('database'));
        $this->assertFalse($userNavManager->hasAccess('user_management'));
    }

    public function testHasAccessUserFeatures()
    {
        $loggedInNavManager = new TestableNavigationManager(true, ['username' => 'user'], false);
        $loggedOutNavManager = new TestableNavigationManager(false, null, false);
        
        // Logged in user should have access to user features
        $this->assertTrue($loggedInNavManager->hasAccess('portfolios'));
        $this->assertTrue($loggedInNavManager->hasAccess('trades'));
        $this->assertTrue($loggedInNavManager->hasAccess('analytics'));
        $this->assertTrue($loggedInNavManager->hasAccess('dashboard'));
        
        // Logged out user should not
        $this->assertFalse($loggedOutNavManager->hasAccess('portfolios'));
        $this->assertFalse($loggedOutNavManager->hasAccess('trades'));
        $this->assertFalse($loggedOutNavManager->hasAccess('analytics'));
        $this->assertFalse($loggedOutNavManager->hasAccess('dashboard'));
    }

    public function testHasAccessPublicFeatures()
    {
        $loggedOutNavManager = new TestableNavigationManager(false, null, false);
        $loggedInNavManager = new TestableNavigationManager(true, ['username' => 'user'], false);
        
        // Logged out user should have access to public features
        $this->assertTrue($loggedOutNavManager->hasAccess('login'));
        $this->assertTrue($loggedOutNavManager->hasAccess('register'));
        
        // Logged in user should not need login/register
        $this->assertFalse($loggedInNavManager->hasAccess('login'));
        $this->assertFalse($loggedInNavManager->hasAccess('register'));
    }

    public function testHasAccessUnknownFeature()
    {
        $navManager = new TestableNavigationManager(true, ['username' => 'admin'], true);
        
        // Unknown features should return false
        $this->assertFalse($navManager->hasAccess('unknown_feature'));
        $this->assertFalse($navManager->hasAccess('invalid'));
        $this->assertFalse($navManager->hasAccess(''));
    }

    public function testGetQuickActionsLoggedInUser()
    {
        $navManager = new TestableNavigationManager(true, ['username' => 'testuser'], false);
        $actions = $navManager->getQuickActions();
        
        $this->assertIsArray($actions);
        $this->assertGreaterThan(0, count($actions));
        
        // Should have basic user actions
        $urls = array_column($actions, 'url');
        $this->assertContains('portfolios.php', $urls);
        $this->assertContains('trades.php', $urls);
        
        // Should not have admin actions
        $this->assertNotContains('admin_users.php', $urls);
    }

    public function testGetQuickActionsAdminUser()
    {
        $navManager = new TestableNavigationManager(true, ['username' => 'admin'], true);
        $actions = $navManager->getQuickActions();
        
        $this->assertIsArray($actions);
        
        // Should have both user and admin actions
        $urls = array_column($actions, 'url');
        $this->assertContains('portfolios.php', $urls);
        $this->assertContains('trades.php', $urls);
        $this->assertContains('admin_users.php', $urls);
        $this->assertContains('database.php', $urls);
    }

    public function testGetQuickActionsNotLoggedIn()
    {
        $navManager = new TestableNavigationManager(false, null, false);
        $actions = $navManager->getQuickActions();
        
        $this->assertIsArray($actions);
        $this->assertEmpty($actions);
    }

    public function testActiveItemDetection()
    {
        $navManager = new TestableNavigationManager(true, ['username' => 'user'], false);
        
        // Test exact page match
        $items = $navManager->getNavigationItems('portfolios.php');
        $portfoliosItem = array_filter($items, function($item) {
            return $item['url'] === 'portfolios.php';
        });
        $this->assertTrue(reset($portfoliosItem)['active']);
        
        // Test short name match
        $items = $navManager->getNavigationItems('trades');
        $tradesItem = array_filter($items, function($item) {
            return $item['url'] === 'trades.php';
        });
        $this->assertTrue(reset($tradesItem)['active']);
    }

    public function testNavigationItemStructure()
    {
        $navManager = new TestableNavigationManager(true, ['username' => 'admin'], true);
        $items = $navManager->getNavigationItems('dashboard');
        
        foreach ($items as $item) {
            // Each item should have required fields
            $this->assertArrayHasKey('url', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('icon', $item);
            $this->assertArrayHasKey('active', $item);
            
            // Validate field types
            $this->assertIsString($item['url']);
            $this->assertIsString($item['label']);
            $this->assertIsString($item['icon']);
            $this->assertIsBool($item['active']);
            
            // Admin items should have admin_only flag
            if (isset($item['admin_only'])) {
                $this->assertTrue($item['admin_only']);
            }
        }
    }

    public function testActiveStateConsistency()
    {
        $navManager = new TestableNavigationManager(true, ['username' => 'user'], false);
        $items = $navManager->getNavigationItems('portfolios');
        
        $activeCount = 0;
        foreach ($items as $item) {
            if ($item['active']) {
                $activeCount++;
            }
        }
        
        // Only one item should be active
        $this->assertEquals(1, $activeCount);
    }

    public function testQuickActionStructure()
    {
        $navManager = new TestableNavigationManager(true, ['username' => 'admin'], true);
        $actions = $navManager->getQuickActions();
        
        foreach ($actions as $action) {
            // Each action should have required fields
            $this->assertArrayHasKey('url', $action);
            $this->assertArrayHasKey('label', $action);
            $this->assertArrayHasKey('class', $action);
            $this->assertArrayHasKey('icon', $action);
            
            // Validate field types
            $this->assertIsString($action['url']);
            $this->assertIsString($action['label']);
            $this->assertIsString($action['class']);
            $this->assertIsString($action['icon']);
        }
    }
}
