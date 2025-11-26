<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/BulkImportSymbolsCliHandler.php';
require_once __DIR__ . '/../src/BulkImportSymbolsAction.php';

/**
 * @covers BulkImportSymbolsCliHandler
 */
class BulkImportSymbolsCliHandlerTest extends TestCase
{
    private $mockBulkImportAction;
    private $handler;
    private $tempFile;

    protected function setUp(): void
    {
        $this->mockBulkImportAction = $this->createMock(BulkImportSymbolsAction::class);
        $this->handler = new BulkImportSymbolsCliHandler($this->mockBulkImportAction);
        
        // Create a temporary test file
        $this->tempFile = tempnam(sys_get_temp_dir(), 'test_symbols');
        file_put_contents($this->tempFile, "IBM\nAAPL\n# Comment\nGOOGL\n\n");
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testRunWithSymbolsArgument()
    {
        $expectedSymbols = ['IBM', 'AAPL', 'GOOGL'];
        $expectedResults = [
            'created' => ['IBM', 'AAPL'],
            'existing' => ['GOOGL'],
            'errors' => []
        ];

        $this->mockBulkImportAction
            ->expects($this->once())
            ->method('execute')
            ->with($expectedSymbols, false)
            ->willReturn($expectedResults);

        $result = $this->handler->run(['script.php', '--symbols=IBM,AAPL,GOOGL']);
        $this->assertEquals(0, $result);
    }

    public function testRunWithFileArgument()
    {
        $expectedSymbols = ['IBM', 'AAPL', 'GOOGL'];
        $expectedResults = [
            'created' => ['IBM'],
            'existing' => ['AAPL', 'GOOGL'],
            'errors' => []
        ];

        $this->mockBulkImportAction
            ->expects($this->once())
            ->method('execute')
            ->with($expectedSymbols, false)
            ->willReturn($expectedResults);

        $result = $this->handler->run(['script.php', "--file={$this->tempFile}"]);
        $this->assertEquals(0, $result);
    }

    public function testRunWithDryRun()
    {
        $expectedSymbols = ['IBM', 'AAPL'];
        $expectedResults = [
            'created' => [],
            'existing' => [],
            'errors' => []
        ];

        $this->mockBulkImportAction
            ->expects($this->once())
            ->method('execute')
            ->with($expectedSymbols, true) // dry_run = true
            ->willReturn($expectedResults);

        $result = $this->handler->run(['script.php', '--symbols=IBM,AAPL', '--dry-run']);
        $this->assertEquals(0, $result);
    }

    public function testRunWithNoSymbolsSpecified()
    {
        $this->mockBulkImportAction
            ->expects($this->never())
            ->method('execute');
        $this->expectException(InvalidArgumentException::class);
        $this->handler->run(['script.php']);
    }

    public function testRunWithFileNotFound()
    {
        $this->mockBulkImportAction
            ->expects($this->never())
            ->method('execute');
        $this->expectException(InvalidArgumentException::class);
        $this->handler->run(['script.php', '--file=nonexistent.txt']);
    }

    public function testRunWithActionException()
    {
        $this->mockBulkImportAction
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new Exception('Database error'));
        $this->expectException(Exception::class);
        $this->handler->run(['script.php', '--symbols=IBM']);
    }
}
