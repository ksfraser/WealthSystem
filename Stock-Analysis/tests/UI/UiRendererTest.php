<?php

use PHPUnit\Framework\TestCase;

// Include the classes under test
require_once __DIR__ . '/../../web_ui/UiRenderer.php';

class UiRendererTest extends TestCase
{
    public function testNavigationDtoCreation()
    {
        $navDto = new NavigationDto(
            'Test Title',
            'dashboard',
            ['username' => 'testuser'],
            true,
            [['url' => 'test.php', 'label' => 'Test', 'active' => false]],
            true
        );

        $this->assertEquals('Test Title', $navDto->title);
        $this->assertEquals('dashboard', $navDto->currentPage);
        $this->assertEquals(['username' => 'testuser'], $navDto->user);
        $this->assertTrue($navDto->isAdmin);
        $this->assertTrue($navDto->isAuthenticated);
        $this->assertCount(1, $navDto->menuItems);
    }

    public function testNavigationDtoDefaults()
    {
        $navDto = new NavigationDto();

        $this->assertEquals('Enhanced Trading System', $navDto->title);
        $this->assertEquals('', $navDto->currentPage);
        $this->assertNull($navDto->user);
        $this->assertFalse($navDto->isAdmin);
        $this->assertEmpty($navDto->menuItems);
        $this->assertFalse($navDto->isAuthenticated);
    }

    public function testCardDtoCreation()
    {
        $cardDto = new CardDto(
            'Test Card',
            'Test content',
            'success',
            '🎯',
            [['url' => 'test.php', 'label' => 'Action', 'class' => 'btn', 'icon' => '📋']]
        );

        $this->assertEquals('Test Card', $cardDto->title);
        $this->assertEquals('Test content', $cardDto->content);
        $this->assertEquals('success', $cardDto->type);
        $this->assertEquals('🎯', $cardDto->icon);
        $this->assertCount(1, $cardDto->actions);
    }

    public function testCardDtoDefaults()
    {
        $cardDto = new CardDto('Title', 'Content');

        $this->assertEquals('Title', $cardDto->title);
        $this->assertEquals('Content', $cardDto->content);
        $this->assertEquals('default', $cardDto->type);
        $this->assertEquals('', $cardDto->icon);
        $this->assertEmpty($cardDto->actions);
    }

    public function testCssProviderBaseStyles()
    {
        $css = CssProvider::getBaseStyles();

        $this->assertStringContainsString('body {', $css);
        $this->assertStringContainsString('.container {', $css);
        $this->assertStringContainsString('.card {', $css);
        $this->assertStringContainsString('.btn {', $css);
        $this->assertStringContainsString('.success {', $css);
        $this->assertStringContainsString('.info {', $css);
        $this->assertStringContainsString('.warning {', $css);
        $this->assertStringContainsString('.error {', $css);
    }

    public function testCssProviderNavigationStyles()
    {
        $css = CssProvider::getNavigationStyles();

        $this->assertStringContainsString('.nav-header {', $css);
        $this->assertStringContainsString('.nav-container {', $css);
        $this->assertStringContainsString('.nav-title {', $css);
        $this->assertStringContainsString('.nav-links {', $css);
        $this->assertStringContainsString('.admin-badge {', $css);
        $this->assertStringContainsString('@media (max-width: 768px)', $css);
    }

    public function testNavigationComponentAuthenticated()
    {
        $navData = new NavigationDto(
            'Test System',
            'dashboard',
            ['username' => 'testuser'],
            false,
            [
                ['url' => 'index.php', 'label' => '🏠 Dashboard', 'active' => true],
                ['url' => 'portfolios.php', 'label' => '📈 Portfolios', 'active' => false]
            ],
            true
        );

        $navComponent = new NavigationComponent($navData);
        $html = $navComponent->toHtml();

        $this->assertStringContainsString('Test System', $html);
        $this->assertStringContainsString('🏠 Dashboard', $html);
        $this->assertStringContainsString('📈 Portfolios', $html);
        $this->assertStringContainsString('testuser', $html);
        $this->assertStringContainsString('🚪 Logout', $html);
        $this->assertStringContainsString('class="nav-link active"', $html);
        $this->assertStringNotContainsString('admin', $html);
    }

    public function testNavigationComponentAdmin()
    {
        $navData = new NavigationDto(
            'Admin System',
            'admin',
            ['username' => 'admin'],
            true,
            [['url' => 'admin.php', 'label' => '👥 Users', 'active' => false, 'admin_only' => true]],
            true
        );

        $navComponent = new NavigationComponent($navData);
        $html = $navComponent->toHtml();

        $this->assertStringContainsString('nav-header admin', $html);
        $this->assertStringContainsString('admin', $html);
        $this->assertStringContainsString('ADMIN', $html);
        $this->assertStringContainsString('title="Admin Only"', $html);
    }

    public function testNavigationComponentUnauthenticated()
    {
        $navData = new NavigationDto(
            'Public System',
            'login',
            null,
            false,
            [],
            false
        );

        $navComponent = new NavigationComponent($navData);
        $html = $navComponent->toHtml();

        $this->assertStringContainsString('🔐 Login', $html);
        $this->assertStringContainsString('📝 Register', $html);
        $this->assertStringNotContainsString('Logout', $html);
        $this->assertStringNotContainsString('admin', $html);
    }

    public function testCardComponentBasic()
    {
        $cardData = new CardDto('Test Title', 'Test content');
        $cardComponent = new CardComponent($cardData);
        $html = $cardComponent->toHtml();

        $this->assertStringContainsString('<div class="card">', $html);
        $this->assertStringContainsString('<h3> Test Title</h3>', $html);
        $this->assertStringContainsString('<p>Test content</p>', $html);
    }

    public function testCardComponentWithTypeAndIcon()
    {
        $cardData = new CardDto('Warning Card', 'Warning message', 'warning', '⚠️');
        $cardComponent = new CardComponent($cardData);
        $html = $cardComponent->toHtml();

        $this->assertStringContainsString('<div class="card warning">', $html);
        $this->assertStringContainsString('<h3>⚠️ Warning Card</h3>', $html);
        $this->assertStringContainsString('<p>Warning message</p>', $html);
    }

    public function testCardComponentWithActions()
    {
        $actions = [
            ['url' => 'action1.php', 'label' => 'Action 1', 'class' => 'btn', 'icon' => '🔧'],
            ['url' => 'action2.php', 'label' => 'Action 2', 'class' => 'btn btn-success', 'icon' => '✅']
        ];

        $cardData = new CardDto('Card with Actions', 'Content', 'info', '📋', $actions);
        $cardComponent = new CardComponent($cardData);
        $html = $cardComponent->toHtml();

        $this->assertStringContainsString('<a href="action1.php" class="btn">🔧 Action 1</a>', $html);
        $this->assertStringContainsString('<a href="action2.php" class="btn btn-success">✅ Action 2</a>', $html);
    }

    public function testPageRendererBasic()
    {
        $navData = new NavigationDto('Test Page', 'test', ['username' => 'user'], false, [], true);
        $navigation = new NavigationComponent($navData);
        $components = [];

        $pageRenderer = new PageRenderer('Test Page Title', $navigation, $components);
        $html = $pageRenderer->render();

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<title>Test Page Title</title>', $html);
        $this->assertStringContainsString('Test Page', $html);
        $this->assertStringContainsString('<div class="container">', $html);
        $this->assertStringContainsString('</body></html>', $html);
    }

    public function testPageRendererWithComponents()
    {
        $navData = new NavigationDto('Test Page', 'test', ['username' => 'user'], false, [], true);
        $navigation = new NavigationComponent($navData);
        
        $cardData = new CardDto('Test Card', 'Card content');
        $card = new CardComponent($cardData);
        $components = [$card];

        $pageRenderer = new PageRenderer('Test Page Title', $navigation, $components);
        $html = $pageRenderer->render();

        $this->assertStringContainsString('Test Card', $html);
        $this->assertStringContainsString('Card content', $html);
    }

    public function testPageRendererWithCustomCSS()
    {
        $navData = new NavigationDto();
        $navigation = new NavigationComponent($navData);
        $customCss = '.custom { color: red; }';

        $pageRenderer = new PageRenderer('Custom Page', $navigation, [], $customCss);
        $html = $pageRenderer->render();

        $this->assertStringContainsString('.custom { color: red; }', $html);
    }

    public function testPageRendererAddComponent()
    {
        $navData = new NavigationDto();
        $navigation = new NavigationComponent($navData);
        $pageRenderer = new PageRenderer('Test', $navigation);

        $cardData = new CardDto('Added Card', 'Added content');
        $card = new CardComponent($cardData);
        $pageRenderer->addComponent($card);

        $html = $pageRenderer->render();
        $this->assertStringContainsString('Added Card', $html);
        $this->assertStringContainsString('Added content', $html);
    }

    public function testUiFactoryCreateNavigationComponent()
    {
        $menuItems = [['url' => 'test.php', 'label' => 'Test', 'active' => false]];
        $user = ['username' => 'testuser'];

        $navigation = UiFactory::createNavigationComponent(
            'Factory Test',
            'dashboard',
            $user,
            true,
            $menuItems,
            true
        );

        $this->assertInstanceOf(NavigationComponent::class, $navigation);
        
        $html = $navigation->toHtml();
        $this->assertStringContainsString('Factory Test', $html);
        $this->assertStringContainsString('testuser', $html);
        $this->assertStringContainsString('ADMIN', $html);
    }

    public function testUiFactoryCreateCard()
    {
        $actions = [['url' => 'test.php', 'label' => 'Test Action', 'class' => 'btn', 'icon' => '🔧']];
        
        $card = UiFactory::createCard(
            'Factory Card',
            'Factory content',
            'success',
            '🎯',
            $actions
        );

        $this->assertInstanceOf(CardComponent::class, $card);
        
        $html = $card->toHtml();
        $this->assertStringContainsString('Factory Card', $html);
        $this->assertStringContainsString('Factory content', $html);
        $this->assertStringContainsString('card success', $html);
        $this->assertStringContainsString('🎯', $html);
        $this->assertStringContainsString('Test Action', $html);
    }

    public function testUiFactoryCreatePageRenderer()
    {
        $navData = new NavigationDto();
        $navigation = new NavigationComponent($navData);
        $components = [];
        $customCss = '.test { color: blue; }';

        $pageRenderer = UiFactory::createPageRenderer(
            'Factory Page',
            $navigation,
            $components,
            $customCss
        );

        $this->assertInstanceOf(PageRenderer::class, $pageRenderer);
        
        $html = $pageRenderer->render();
        $this->assertStringContainsString('Factory Page', $html);
        $this->assertStringContainsString('.test { color: blue; }', $html);
    }

    public function testXSSPrevention()
    {
        // Test that user input is properly escaped
        $maliciousInput = '<script>alert("XSS")</script>';
        
        $navData = new NavigationDto($maliciousInput, 'test', ['username' => $maliciousInput], false, [], true);
        $navigation = new NavigationComponent($navData);
        $html = $navigation->toHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testCardXSSPrevention()
    {
        $maliciousTitle = '<script>alert("XSS")</script>';
        $cardData = new CardDto($maliciousTitle, 'Safe content');
        $card = new CardComponent($cardData);
        $html = $card->toHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testActionUrlXSSPrevention()
    {
        $maliciousAction = [
            'url' => 'javascript:alert("XSS")',
            'label' => 'Safe Label',
            'class' => 'btn',
            'icon' => '🔧'
        ];

        $cardData = new CardDto('Test Card', 'Content', 'default', '', [$maliciousAction]);
        $card = new CardComponent($cardData);
        $html = $card->toHtml();

        $this->assertStringContainsString('href="javascript:alert(&quot;XSS&quot;)"', $html);
    }
}
