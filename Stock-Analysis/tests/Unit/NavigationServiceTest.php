<?php

use PHPUnit\Framework\TestCase;

// Define a dummy constant to enable test mode in NavigationService
if (!defined('TEST_MODE_9f3b2c')) {
    define('TEST_MODE_9f3b2c', true);
}

require_once __DIR__ . '/../../web_ui/NavigationService.php';

class NavigationServiceTest extends TestCase {
    
    private function createNavService($isLoggedIn, $isAdmin) {
        global $NAVSERVICE_TEST_AUTH;
        $NAVSERVICE_TEST_AUTH = [
            'isLoggedIn' => $isLoggedIn,
            'currentUser' => $isLoggedIn ? ['username' => 'testuser', 'is_admin' => $isAdmin] : null,
            'isAdmin' => $isAdmin
        ];
        return new NavigationService($NAVSERVICE_TEST_AUTH);
    }

    public function testGuestUserMenu() {
        $navService = $this->createNavService(false, false);
        $menuHtml = $navService->renderNavigationHeader();

        $this->assertStringContainsString('Guest', $menuHtml);
        $this->assertStringContainsString('Login', $menuHtml);
        $this->assertStringContainsString('Register', $menuHtml);
        $this->assertStringNotContainsString('My Portfolio', $menuHtml);
        $this->assertStringNotContainsString('Admin', $menuHtml);
    }

    public function testStandardUserMenu() {
        $navService = $this->createNavService(true, false);
        $menuHtml = $navService->renderNavigationHeader();

        // Check for standard user items
        $this->assertStringContainsString('My Portfolio', $menuHtml);
        $this->assertStringContainsString('Automation', $menuHtml);
        $this->assertStringContainsString('Stock Search', $menuHtml);
        $this->assertStringContainsString('Bank CSV Import', $menuHtml);
        $this->assertStringContainsString('Performance Charts', $menuHtml);
        $this->assertStringContainsString('Manage Invitations', $menuHtml);

        // Ensure admin menu is NOT present
        $this->assertStringNotContainsString('🔧 Admin', $menuHtml);
        $this->assertStringNotContainsString('User Management', $menuHtml);
    }

    public function testAdminUserMenu() {
        $navService = $this->createNavService(true, true);
        $menuHtml = $navService->renderNavigationHeader();

        // Check for standard items
        $this->assertStringContainsString('My Portfolio', $menuHtml);
        
        // Check for admin items
        $this->assertStringContainsString('🔧 Admin', $menuHtml);
        $this->assertStringContainsString('User Management', $menuHtml);
        $this->assertStringContainsString('Account Types', $menuHtml);
        $this->assertStringContainsString('Brokerages', $menuHtml);
        $this->assertStringContainsString('Database', $menuHtml);
    }

    public function testDropdownMenuStructure() {
        $navService = $this->createNavService(true, true);
        $menuHtml = $navService->renderNavigationHeader();

        // Check Portfolio Dropdown
        $this->assertMatchesRegularExpression('/<div class="portfolio-dropdown">.*<a href="..\/simple_automation.py"/s', $menuHtml);
        
        // Check Stocks Dropdown
        $this->assertMatchesRegularExpression('/<div class="stocks-dropdown">.*<a href="stock_analysis.php\?demo=1"/s', $menuHtml);

        // Check Data Dropdown
        $this->assertMatchesRegularExpression('/<div class="data-dropdown".*<a href="bank_import.php"/s', $menuHtml);

        // Check Reports Dropdown
        $this->assertMatchesRegularExpression('/<div class="reports-dropdown".*<a href="reports.php"/s', $menuHtml);

        // Check Admin Dropdown
        $this->assertMatchesRegularExpression('/<div class="admin-dropdown".*<a href="admin_users.php"/s', $menuHtml);
    }
}
