<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/MigrateSymbolsCliHandler.php';

class MigrateSymbolsCliHandlerTest extends TestCase
{
    public function testRunWithNoSymbolsFound()
    {
        // Mock dependencies
        $handler = $this->getMockBuilder(MigrateSymbolsCliHandler::class)
            ->onlyMethods(['exit'])
            ->getMock();
        // Simulate no symbols found by overriding run
        $argv = ['script.php', '--symbol='];
        ob_start();
        $handler->run($argv);
        $output = ob_get_clean();
        $this->assertStringContainsString('No symbols found to migrate', $output);
    }
}
