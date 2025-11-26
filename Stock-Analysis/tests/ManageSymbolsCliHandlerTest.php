<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/ManageSymbolsCliHandler.php';
require_once __DIR__ . '/../src/IStockTableManager.php';

/**
 * @covers ManageSymbolsCliHandler
 */
class ManageSymbolsCliHandlerTest extends TestCase
{
    public function testRunWithInsufficientArguments()
    {
        $handler = new ManageSymbolsCliHandler();
        $argv = ['script.php'];
        $this->expectException(\RuntimeException::class);
        ob_start();
        $handler->run($argv);
        $output = ob_get_clean();
        $this->assertStringContainsString('Usage', $output);
    }

    public function testRunWithUnknownCommand()
    {
        $handler = new ManageSymbolsCliHandler();
        $argv = ['script.php', 'unknown'];
        $this->expectException(\RuntimeException::class);
        ob_start();
        $handler->run($argv);
        $output = ob_get_clean();
        $this->assertStringContainsString('Unknown command', $output);
    }

    public function testShowUsage()
    {
        $handler = new ManageSymbolsCliHandler();
        // Use reflection to test private method
        $reflection = new ReflectionClass($handler);
        $method = $reflection->getMethod('showUsage');
        $method->setAccessible(true);
        
        ob_start();
        $method->invoke($handler);
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Symbol Management Tool', $output);
        $this->assertStringContainsString('list', $output);
        $this->assertStringContainsString('remove', $output);
    }
}
