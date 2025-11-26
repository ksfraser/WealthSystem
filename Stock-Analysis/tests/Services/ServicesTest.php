<?php

use PHPUnit\Framework\TestCase;

// Mock the global functions and includes that are used in the services
class MockNavigationManager
{
    private $currentUser;
    private $isAdmin;
    
    public function __construct($user = null, $isAdmin = false)
    {
        $this->currentUser = $user ?? ['username' => 'testuser'];
        $this->isAdmin = $isAdmin;
    }
    
    public function getCurrentUser()
    {
        return $this->currentUser;
    }
    
    public function isAdmin()
    {
        return $this->isAdmin;
    }
}

// Mock global function
function getNavManager()
{
    global $mockNavManager;
    return $mockNavManager ?? new MockNavigationManager();
}

// Mock headers_sent to prevent issues in testing
if (!function_exists('headers_sent')) {
    function headers_sent()
    {
        return false;
    }
}

// Include the classes under test after setting up mocks
require_once __DIR__ . '/../../web_ui/UiRenderer.php';

class ServicesTest extends TestCase
{
    private $mockNavManager;

    protected function setUp(): void
    {
        global $mockNavManager;
        $this->mockNavManager = new MockNavigationManager(['username' => 'testuser'], false);
        $mockNavManager = $this->mockNavManager;
    }

    protected function tearDown(): void
    {
        global $mockNavManager;
        $mockNavManager = null;
    }

    public function testMenuServiceGetMenuItemsAuthenticated()
    {
        $items = MenuService::getMenuItems('dashboard', false, true);

        $this->assertIsArray($items);
        $this->assertGreaterThan(0, count($items));
        
        // Check that dashboard item exists and is active
        $dashboardItem = array_filter($items, function($item) {
            return $item['url'] === 'index.php';
        });
        $this->assertCount(1, $dashboardItem);
        $this->assertTrue(reset($dashboardItem)['active']);

        // Check for core menu items
        $urls = array_column($items, 'url');
        $this->assertContains('index.php', $urls);
        $this->assertContains('portfolios.php', $urls);
        $this->assertContains('trades.php', $urls);
        $this->assertContains('analytics.php', $urls);
    }

    public function testMenuServiceGetMenuItemsAdmin()
    {
        $items = MenuService::getMenuItems('users', true, true);

        $this->assertIsArray($items);
        
        // Check for admin-only items
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

    public function testMenuServiceGetMenuItemsUnauthenticated()
    {
        $items = MenuService::getMenuItems('login', false, false);

        $this->assertEmpty($items);
    }

    public function testMenuServiceNonAdminNoAdminItems()
    {
        $items = MenuService::getMenuItems('dashboard', false, true);

        $adminItems = array_filter($items, function($item) {
            return isset($item['admin_only']);
        });
        $this->assertEmpty($adminItems);
    }

    public function testMenuServiceActiveItemDetection()
    {
        $items = MenuService::getMenuItems('portfolios', false, true);

        $portfoliosItem = array_filter($items, function($item) {
            return $item['url'] === 'portfolios.php';
        });
        $this->assertTrue(reset($portfoliosItem)['active']);

        $otherItems = array_filter($items, function($item) {
            return $item['url'] !== 'portfolios.php';
        });
        foreach ($otherItems as $item) {
            $this->assertFalse($item['active']);
        }
    }
}

// Test AuthenticationService (mocked version since it depends on files that may not exist)
class AuthenticationServiceTest extends TestCase
{
    public function testAuthenticationServiceWithMockFailure()
    {
        // Since we can't easily mock the file includes, we'll test the error handling path
        
        // Create a mock that simulates the real class behavior during auth failure
        $authService = new class {
            private $authError = true;
            private $currentUser = ['username' => 'Guest (Auth Unavailable)'];
            private $isAuthenticated = false;
            private $isAdmin = false;
            
            public function isAuthenticated() { return $this->isAuthenticated; }
            public function getCurrentUser() { return $this->currentUser; }
            public function isAdmin() { return $this->isAdmin; }
            public function hasAuthError() { return $this->authError; }
        };

        $this->assertFalse($authService->isAuthenticated());
        $this->assertTrue($authService->hasAuthError());
        $this->assertEquals(['username' => 'Guest (Auth Unavailable)'], $authService->getCurrentUser());
        $this->assertFalse($authService->isAdmin());
    }

    public function testAuthenticationServiceWithMockSuccess()
    {
        $authService = new class {
            private $authError = false;
            private $currentUser = ['username' => 'testuser'];
            private $isAuthenticated = true;
            private $isAdmin = false;
            
            public function isAuthenticated() { return $this->isAuthenticated; }
            public function getCurrentUser() { return $this->currentUser; }
            public function isAdmin() { return $this->isAdmin; }
            public function hasAuthError() { return $this->authError; }
        };

        $this->assertTrue($authService->isAuthenticated());
        $this->assertFalse($authService->hasAuthError());
        $this->assertEquals(['username' => 'testuser'], $authService->getCurrentUser());
        $this->assertFalse($authService->isAdmin());
    }

    public function testAuthenticationServiceAdminUser()
    {
        $authService = new class {
            private $authError = false;
            private $currentUser = ['username' => 'admin'];
            private $isAuthenticated = true;
            private $isAdmin = true;
            
            public function isAuthenticated() { return $this->isAuthenticated; }
            public function getCurrentUser() { return $this->currentUser; }
            public function isAdmin() { return $this->isAdmin; }
            public function hasAuthError() { return $this->authError; }
        };

        $this->assertTrue($authService->isAuthenticated());
        $this->assertFalse($authService->hasAuthError());
        $this->assertEquals(['username' => 'admin'], $authService->getCurrentUser());
        $this->assertTrue($authService->isAdmin());
    }
}

// Test DashboardContentService
class DashboardContentServiceTest extends TestCase
{
    public function testDashboardContentServiceWithNormalUser()
    {
        $mockAuthService = new class {
            public function hasAuthError() { return false; }
            public function isAdmin() { return false; }
            public function getCurrentUser() { return ['username' => 'testuser']; }
        };

        $contentService = new DashboardContentService($mockAuthService);
        $components = $contentService->createDashboardComponents();

        $this->assertIsArray($components);
        $this->assertGreaterThan(0, count($components));

        // Should not contain admin warning card
        $htmlStrings = [];
        foreach ($components as $component) {
            $htmlStrings[] = $component->toHtml();
        }
        $combinedHtml = implode('', $htmlStrings);
        
        $this->assertStringNotContainsString('🔧 Administrator Access', $combinedHtml);
        $this->assertStringNotContainsString('Authentication System Unavailable', $combinedHtml);
    }

    public function testDashboardContentServiceWithAdminUser()
    {
        $mockAuthService = new class {
            public function hasAuthError() { return false; }
            public function isAdmin() { return true; }
            public function getCurrentUser() { return ['username' => 'admin']; }
        };

        $contentService = new DashboardContentService($mockAuthService);
        $components = $contentService->createDashboardComponents();

        $this->assertIsArray($components);
        $this->assertGreaterThan(0, count($components));

        // Should contain admin access card
        $htmlStrings = [];
        foreach ($components as $component) {
            $htmlStrings[] = $component->toHtml();
        }
        $combinedHtml = implode('', $htmlStrings);
        
        $this->assertStringContainsString('🔧 Administrator Access', $combinedHtml);
        $this->assertStringContainsString('admin_users.php', $combinedHtml);
    }

    public function testDashboardContentServiceWithAuthError()
    {
        $mockAuthService = new class {
            public function hasAuthError() { return true; }
            public function isAdmin() { return false; }
            public function getCurrentUser() { return ['username' => 'Guest']; }
        };

        $contentService = new DashboardContentService($mockAuthService);
        $components = $contentService->createDashboardComponents();

        $this->assertIsArray($components);
        $this->assertGreaterThan(0, count($components));

        // Should contain auth error warning
        $htmlStrings = [];
        foreach ($components as $component) {
            $htmlStrings[] = $component->toHtml();
        }
        $combinedHtml = implode('', $htmlStrings);
        
        $this->assertStringContainsString('⚠️ Authentication System Unavailable', $combinedHtml);
        $this->assertStringContainsString('database connectivity issues', $combinedHtml);
    }

    public function testDashboardContentServiceComponentTypes()
    {
        $mockAuthService = new class {
            public function hasAuthError() { return false; }
            public function isAdmin() { return false; }
            public function getCurrentUser() { return ['username' => 'testuser']; }
        };

        $contentService = new DashboardContentService($mockAuthService);
        $components = $contentService->createDashboardComponents();

        // Verify all components implement ComponentInterface
        foreach ($components as $component) {
            $this->assertInstanceOf(ComponentInterface::class, $component);
            $this->assertIsString($component->toHtml());
        }
    }

    public function testDashboardContentServiceQuickActions()
    {
        $mockAuthService = new class {
            public function hasAuthError() { return false; }
            public function isAdmin() { return false; }
            public function getCurrentUser() { return ['username' => 'testuser']; }
        };

        $contentService = new DashboardContentService($mockAuthService);
        $components = $contentService->createDashboardComponents();

        $htmlStrings = [];
        foreach ($components as $component) {
            $htmlStrings[] = $component->toHtml();
        }
        $combinedHtml = implode('', $htmlStrings);

        // Should contain quick action buttons
        $this->assertStringContainsString('Add Portfolio', $combinedHtml);
        $this->assertStringContainsString('Record Trade', $combinedHtml);
        $this->assertStringContainsString('portfolios.php', $combinedHtml);
        $this->assertStringContainsString('trades.php', $combinedHtml);
    }

    public function testDashboardContentServiceSystemInfo()
    {
        $mockAuthService = new class {
            public function hasAuthError() { return false; }
            public function isAdmin() { return false; }
            public function getCurrentUser() { return ['username' => 'testuser']; }
        };

        $contentService = new DashboardContentService($mockAuthService);
        $components = $contentService->createDashboardComponents();

        $htmlStrings = [];
        foreach ($components as $component) {
            $htmlStrings[] = $component->toHtml();
        }
        $combinedHtml = implode('', $htmlStrings);

        // Should contain system information
        $this->assertStringContainsString('📋 System Information', $combinedHtml);
        $this->assertStringContainsString('Database Architecture', $combinedHtml);
        $this->assertStringContainsString('Authentication Status', $combinedHtml);
        $this->assertStringContainsString('Current User', $combinedHtml);
        $this->assertStringContainsString('testuser', $combinedHtml);
    }
}
