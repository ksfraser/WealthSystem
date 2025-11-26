<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../web_ui/QuickActions.php';

class QuickActionsTest extends TestCase
{
    public function testRenderOutputsHtml()
    {
        ob_start();
        QuickActions::render();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('<div class="card">', $output);
        $this->assertStringContainsString('<h3>Quick Actions</h3>', $output);
        $this->assertStringContainsString('</div>', $output);
    }

    public function testRenderContainsExpectedActions()
    {
        ob_start();
        QuickActions::render();
        $output = ob_get_clean();
        
        // Check for core actions
        $this->assertStringContainsString('href="index.php"', $output);
        $this->assertStringContainsString('Dashboard', $output);
        $this->assertStringContainsString('href="portfolios.php"', $output);
        $this->assertStringContainsString('View Portfolios', $output);
        $this->assertStringContainsString('href="trades.php"', $output);
        $this->assertStringContainsString('Trade History', $output);
    }

    public function testRenderContainsAdminActions()
    {
        ob_start();
        QuickActions::render();
        $output = ob_get_clean();
        
        // Check for admin actions
        $this->assertStringContainsString('href="admin_brokerages.php"', $output);
        $this->assertStringContainsString('Admin Brokerages', $output);
        $this->assertStringContainsString('href="admin_account_types.php"', $output);
        $this->assertStringContainsString('Admin Account Types', $output);
        $this->assertStringContainsString('href="bank_import.php"', $output);
        $this->assertStringContainsString('Bank Import', $output);
    }

    public function testRenderWithExtraClass()
    {
        ob_start();
        QuickActions::render('btn-primary');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('class="btn btn-primary"', $output);
    }

    public function testRenderWithoutExtraClass()
    {
        ob_start();
        QuickActions::render();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('class="btn "', $output);
    }

    public function testAllLinksAreProperlyEscaped()
    {
        ob_start();
        QuickActions::render();
        $output = ob_get_clean();
        
        // Should not contain any unescaped content
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringNotContainsString('javascript:', $output);
    }

    public function testActionsArrayStructure()
    {
        // Use reflection to access private static property
        $reflection = new ReflectionClass('QuickActions');
        $actionsProperty = $reflection->getProperty('actions');
        $actionsProperty->setAccessible(true);
        $actions = $actionsProperty->getValue();
        
        $this->assertIsArray($actions);
        $this->assertNotEmpty($actions);
        
        foreach ($actions as $action) {
            $this->assertArrayHasKey('label', $action);
            $this->assertArrayHasKey('href', $action);
            $this->assertIsString($action['label']);
            $this->assertIsString($action['href']);
            $this->assertNotEmpty($action['label']);
            $this->assertNotEmpty($action['href']);
        }
    }

    public function testAllActionsHaveUniqueLabels()
    {
        $reflection = new ReflectionClass('QuickActions');
        $actionsProperty = $reflection->getProperty('actions');
        $actionsProperty->setAccessible(true);
        $actions = $actionsProperty->getValue();
        
        $labels = array_column($actions, 'label');
        $uniqueLabels = array_unique($labels);
        
        $this->assertCount(count($labels), $uniqueLabels, 'All action labels should be unique');
    }

    public function testAllActionsHaveUniqueHrefs()
    {
        $reflection = new ReflectionClass('QuickActions');
        $actionsProperty = $reflection->getProperty('actions');
        $actionsProperty->setAccessible(true);
        $actions = $actionsProperty->getValue();
        
        $hrefs = array_column($actions, 'href');
        $uniqueHrefs = array_unique($hrefs);
        
        $this->assertCount(count($hrefs), $uniqueHrefs, 'All action hrefs should be unique');
    }

    public function testRenderGeneratesValidHtml()
    {
        ob_start();
        QuickActions::render('test-class');
        $output = ob_get_clean();
        
        // Basic HTML structure validation
        $this->assertStringStartsWith('<div class="card">', $output);
        $this->assertStringEndsWith('</div>', $output);
        
        // Count opening and closing tags
        $openDivCount = substr_count($output, '<div');
        $closeDivCount = substr_count($output, '</div>');
        $this->assertEquals($openDivCount, $closeDivCount, 'Opening and closing div tags should match');
        
        $openACount = substr_count($output, '<a ');
        $closeACount = substr_count($output, '</a>');
        $this->assertEquals($openACount, $closeACount, 'Opening and closing anchor tags should match');
    }
}
