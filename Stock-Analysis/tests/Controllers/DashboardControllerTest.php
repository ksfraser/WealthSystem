
// Enable test mode for navigation auth injection (must be first, before any includes)
if (!defined('TEST_MODE_9f3b2c')) {
    define('TEST_MODE_9f3b2c', true);
}
<?php

use PHPUnit\Framework\TestCase;

// Include the classes under test
require_once __DIR__ . '/../../web_ui/UiRenderer.php';

// Mock classes for testing DashboardController components
class MockAuthenticationService
{
    private $isAuthenticated;
    private $currentUser;
    private $isAdmin;
    private $hasAuthError;
    
    public function __construct($isAuthenticated = true, $currentUser = null, $isAdmin = false, $hasAuthError = false)
    {
        $this->isAuthenticated = $isAuthenticated;
        $this->currentUser = $currentUser ?? ['username' => 'testuser'];
        $this->isAdmin = $isAdmin;
        $this->hasAuthError = $hasAuthError;
    }
    
    public function isAuthenticated()
    {
        return $this->isAuthenticated;
    }
    
    public function getCurrentUser()
    {
        return $this->currentUser;
    }
    
    public function isAdmin()
    {
        return $this->isAdmin;
    }
    
    public function hasAuthError()
    {
        return $this->hasAuthError;
    }
}

class MockMenuService
{
    public static function getMenuItems($currentPage, $isAdmin, $isAuthenticated)
    {
        if (!$isAuthenticated) {
            return [];
        }
        
        $items = [
            ['url' => 'index.php', 'label' => '🏠 Dashboard', 'active' => $currentPage === 'dashboard'],
            ['url' => 'portfolios.php', 'label' => '📈 Portfolios', 'active' => false],
            ['url' => 'trades.php', 'label' => '📋 Trades', 'active' => false],
            ['url' => 'analytics.php', 'label' => '📊 Analytics', 'active' => false]
        ];
        
        if ($isAdmin) {
            $items[] = ['url' => 'admin_users.php', 'label' => '👥 Users', 'active' => false, 'admin_only' => true];
        }
        
        return $items;
    }
}

class MockDashboardContentService
{
    private $authService;
    
    public function __construct($authService)
    {
        $this->authService = $authService;
    }
    
    public function createDashboardComponents()
    {
        $components = [];
        
        // Welcome card
        $components[] = UiFactory::createCard(
            'Welcome to Your Trading Dashboard',
            'Test dashboard content',
            'default',
            ''
        );
        
        // Auth error card if needed
        if ($this->authService->hasAuthError()) {
            $components[] = UiFactory::createCard(
                '⚠️ Authentication System Unavailable',
                'Auth error test message',
                'warning',
                '⚠️'
            );
        }
        
        // Admin card if admin
        if (!$this->authService->hasAuthError() && $this->authService->isAdmin()) {
            $components[] = UiFactory::createCard(
                '🔧 Administrator Access',
                'Admin access test message',
                'warning',
                '🔧'
            );
        }
        
        return $components;
    }
}

class TestableDashboardController
{
    private $authService;
    private $contentService;
    
    public function __construct($authService = null, $contentService = null)
    {
        $this->authService = $authService ?? new MockAuthenticationService();
        $this->contentService = $contentService ?? new MockDashboardContentService($this->authService);
    }
    
    public function renderPage()
    {
        // Create navigation component
        $user = $this->authService->getCurrentUser();
        $isAdmin = $this->authService->isAdmin();
        $isAuthenticated = $this->authService->isAuthenticated();
        $menuItems = MockMenuService::getMenuItems('dashboard', $isAdmin, $isAuthenticated);

        // Inject test auth state if TEST_MODE_9f3b2c is defined
        $testAuth = (defined('TEST_MODE_9f3b2c')) ? [
            'isLoggedIn' => $isAuthenticated,
            'currentUser' => $user,
            'isAdmin' => $isAdmin
        ] : null;

        $navigation = new \Ksfraser\UIRenderer\Components\NavigationComponent(
            new \Ksfraser\UIRenderer\DTOs\NavigationDto(
                'Enhanced Trading System Dashboard',
                'dashboard',
                $user,
                $isAdmin,
                $menuItems,
                $isAuthenticated
            ),
            $testAuth
        );

        // Create page content components
        $components = $this->contentService->createDashboardComponents();

        // Create and render the complete page
        $pageRenderer = UiFactory::createPageRenderer(
            'Dashboard - Enhanced Trading System',
            $navigation,
            $components
        );

        return $pageRenderer->render();
    }
    
    public function getAuthService()
    {
        return $this->authService;
    }
    
    public function getContentService()
    {
        return $this->contentService;
    }
}

class DashboardControllerTest extends TestCase
{
    public function testConstructorDefaults()
    {
        $controller = new TestableDashboardController();
        
        $this->assertInstanceOf(MockAuthenticationService::class, $controller->getAuthService());
        $this->assertInstanceOf(MockDashboardContentService::class, $controller->getContentService());
    }

    public function testConstructorWithCustomServices()
    {
        $authService = new MockAuthenticationService(true, ['username' => 'custom'], true, false);
        $contentService = new MockDashboardContentService($authService);
        
        $controller = new TestableDashboardController($authService, $contentService);
        
        $this->assertSame($authService, $controller->getAuthService());
        $this->assertSame($contentService, $controller->getContentService());
    }

    public function testRenderPageBasic()
    {
        $authService = new MockAuthenticationService(true, ['username' => 'testuser'], false, false);
        $controller = new TestableDashboardController($authService);
        
        $html = $controller->renderPage();
        
        $this->assertIsString($html);
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<title>Dashboard - Enhanced Trading System</title>', $html);
        $this->assertStringContainsString('Enhanced Trading System Dashboard', $html);
        $this->assertStringContainsString('testuser', $html);
        $this->assertStringContainsString('Welcome to Your Trading Dashboard', $html);
        $this->assertStringContainsString('</body></html>', $html);
    }

    public function testRenderPageUnauthenticated()
    {
        $authService = new MockAuthenticationService(false, null, false, false);
        $controller = new TestableDashboardController($authService);
        
        $html = $controller->renderPage();
        
        $this->assertIsString($html);
        $this->assertStringContainsString('Enhanced Trading System Dashboard', $html);
        $this->assertStringNotContainsString('testuser', $html);
        $this->assertStringNotContainsString('ADMIN', $html);
    }

    public function testRenderPageAdminUser()
    {
        $authService = new MockAuthenticationService(true, ['username' => 'admin'], true, false);
        $controller = new TestableDashboardController($authService);
        
        $html = $controller->renderPage();
        
        $this->assertIsString($html);
        $this->assertStringContainsString('admin', $html);
        $this->assertStringContainsString('ADMIN', $html);
        $this->assertStringContainsString('🔧 Administrator Access', $html);
        $this->assertStringContainsString('Admin access test message', $html);
    }

    public function testRenderPageWithAuthError()
    {
        $authService = new MockAuthenticationService(false, ['username' => 'guest'], false, true);
        $controller = new TestableDashboardController($authService);
        
        $html = $controller->renderPage();
        
        $this->assertIsString($html);
        $this->assertStringContainsString('⚠️ Authentication System Unavailable', $html);
        $this->assertStringContainsString('Auth error test message', $html);
        $this->assertStringNotContainsString('🔧 Administrator Access', $html);
    }

    public function testRenderPageNavigationItems()
    {
        $authService = new MockAuthenticationService(true, ['username' => 'testuser'], false, false);
        $controller = new TestableDashboardController($authService);
        
        $html = $controller->renderPage();
        
    // Should contain navigation items
    $this->assertStringContainsString('🏠 My Portfolio', $html);
    $this->assertStringContainsString('📈 Manage Portfolios', $html);
        $this->assertStringContainsString('📋 Trades', $html);
        $this->assertStringContainsString('📊 Analytics', $html);
        
        
        // Should not contain admin items for regular user
        $this->assertStringNotContainsString('👥 Users', $html);
    }

    public function testRenderPageAdminNavigationItems()
    {
        $authService = new MockAuthenticationService(true, ['username' => 'admin'], true, false);
        $controller = new TestableDashboardController($authService);
        
        $html = $controller->renderPage();
        
        // Should contain all navigation items including admin
        $this->assertStringContainsString('🏠 Dashboard', $html);
        $this->assertStringContainsString('📈 Portfolios', $html);
        $this->assertStringContainsString('📋 Trades', $html);
        $this->assertStringContainsString('📊 Analytics', $html);
        $this->assertStringContainsString('👥 Users', $html);
    }

    public function testRenderPageComponents()
    {
        $authService = new MockAuthenticationService(true, ['username' => 'testuser'], false, false);
        $controller = new TestableDashboardController($authService);
        
        $html = $controller->renderPage();
        
        // Should contain welcome component
        $this->assertStringContainsString('Welcome to Your Trading Dashboard', $html);
        $this->assertStringContainsString('Test dashboard content', $html);
    }

    public function testRenderPageCSS()
    {
        $authService = new MockAuthenticationService(true, ['username' => 'testuser'], false, false);
        $controller = new TestableDashboardController($authService);
        
        $html = $controller->renderPage();
        
        // Should contain CSS styles
        $this->assertStringContainsString('<style>', $html);
        $this->assertStringContainsString('body {', $html);
        $this->assertStringContainsString('.nav-header {', $html);
        $this->assertStringContainsString('.card {', $html);
        $this->assertStringContainsString('</style>', $html);
    }

    public function testRenderPageHTMLStructure()
    {
        $authService = new MockAuthenticationService(true, ['username' => 'testuser'], false, false);
        $controller = new TestableDashboardController($authService);
        
        $html = $controller->renderPage();
        
        // Should have proper HTML structure
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<html lang="en">', $html);
        $this->assertStringContainsString('<head>', $html);
        $this->assertStringContainsString('<meta charset="UTF-8">', $html);
        $this->assertStringContainsString('<meta name="viewport"', $html);
        $this->assertStringContainsString('<body>', $html);
        $this->assertStringContainsString('<div class="container">', $html);
        $this->assertStringContainsString('</body></html>', $html);
    }

    public function testRenderPageSecurity()
    {
        // Test with potentially malicious username
        $maliciousUser = ['username' => '<script>alert("XSS")</script>'];
        $authService = new MockAuthenticationService(true, $maliciousUser, false, false);
            // Set global test auth for NavigationService
            global $NAVSERVICE_TEST_AUTH;
            $NAVSERVICE_TEST_AUTH = [
                'isLoggedIn' => $authService->isAuthenticated(),
                'currentUser' => $authService->getCurrentUser(),
                'isAdmin' => $authService->isAdmin()
            ];
        $controller = new TestableDashboardController($authService);
        
        $html = $controller->renderPage();

        // DEBUG: Dump HTML and test auth state
        file_put_contents(__DIR__ . '/debug_testRenderPageSecurity.html', $html);
        file_put_contents(__DIR__ . '/debug_testRenderPageSecurity_auth.json', json_encode([
            'isLoggedIn' => $authService->isAuthenticated(),
            'currentUser' => $authService->getCurrentUser(),
            'isAdmin' => $authService->isAdmin()
        ], JSON_PRETTY_PRINT));

        // Should properly escape the malicious content
        $this->assertStringNotContainsString('<script>alert("XSS")</script>', $html);
        $this->assertStringContainsString('👤 &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt; ▼', $html);
    }

    public function testDependencyInjection()
    {
        $authService = new MockAuthenticationService(true, ['username' => 'di_test'], false, false);
        $contentService = new MockDashboardContentService($authService);
        
        $controller = new TestableDashboardController($authService, $contentService);
        
        // Verify dependencies are properly injected
        $this->assertSame($authService, $controller->getAuthService());
        $this->assertSame($contentService, $controller->getContentService());
        
        $html = $controller->renderPage();
        $this->assertStringContainsString('di_test', $html);
    }

    public function testPageTitleCustomization()
    {
        $authService = new MockAuthenticationService(true, ['username' => 'testuser'], false, false);
        $controller = new TestableDashboardController($authService);
        
        $html = $controller->renderPage();
        
        $this->assertStringContainsString('<title>Dashboard - Enhanced Trading System</title>', $html);
    }

    public function testNavigationTitleCustomization()
    {
        $authService = new MockAuthenticationService(true, ['username' => 'testuser'], false, false);
        $controller = new TestableDashboardController($authService);
        
        $html = $controller->renderPage();
        
        $this->assertStringContainsString('Enhanced Trading System Dashboard', $html);
    }
}
