<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/BulkImportSymbolsAction.php';
require_once __DIR__ . '/../src/AddSymbolAction.php';
require_once __DIR__ . '/../src/TableTypeRegistry.php';
require_once __DIR__ . '/../src/IStockTableManager.php';

/**
 * @covers BulkImportSymbolsAction
 */
class BulkImportSymbolsActionTest extends TestCase
{
    public function testExecuteDryRun()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockAddSymbolAction = $this->createMock(AddSymbolAction::class);
        $action = new BulkImportSymbolsAction($mockTableManager, $mockAddSymbolAction);
        $symbols = ['IBM', 'AAPL'];
        $results = $action->execute($symbols, true);
        $this->assertEquals(['IBM', 'AAPL'], $results['created']);
    }

    public function testExecuteCreatesAndExists()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockAddSymbolAction = $this->createMock(AddSymbolAction::class);
        $mockAddSymbolAction->method('execute')->willReturnOnConsecutiveCalls(
            ['status' => 'created', 'symbol' => 'IBM'],
            ['status' => 'exists', 'symbol' => 'AAPL']
        );
        $action = new BulkImportSymbolsAction($mockTableManager, $mockAddSymbolAction);
        $symbols = ['IBM', 'AAPL'];
        $results = $action->execute($symbols, false);
        $this->assertEquals(['IBM'], $results['created']);
        $this->assertEquals(['AAPL'], $results['existing']);
    }

    public function testExecuteInvalidSymbol()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockAddSymbolAction = $this->createMock(AddSymbolAction::class);
        $action = new BulkImportSymbolsAction($mockTableManager, $mockAddSymbolAction);
        $symbols = ['ibm!'];
        $results = $action->execute($symbols, false);
        $this->assertNotEmpty($results['errors']);
        $this->assertEquals('ibm!', $results['errors'][0]['symbol']);
        $this->assertEquals('Invalid symbol format', $results['errors'][0]['error']);
    }

    public function testExecuteWithException()
    {
        $this->expectException(Exception::class);
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockAddSymbolAction = $this->createMock(AddSymbolAction::class);
        $mockAddSymbolAction->method('execute')->willThrowException(new Exception('Database error'));
        $action = new BulkImportSymbolsAction($mockTableManager, $mockAddSymbolAction);
        $symbols = ['IBM'];
        $action->execute($symbols, false);
    }

    public function testExecuteMixedResults()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockAddSymbolAction = $this->createMock(AddSymbolAction::class);
        $mockAddSymbolAction->method('execute')->willReturnOnConsecutiveCalls(
            ['status' => 'created', 'symbol' => 'IBM'],
            ['status' => 'exists', 'symbol' => 'AAPL']
        );
        
        $action = new BulkImportSymbolsAction($mockTableManager, $mockAddSymbolAction);
        $symbols = ['IBM', 'AAPL', 'invalid!'];
        $results = $action->execute($symbols, false);
        
        $this->assertEquals(['IBM'], $results['created']);
        $this->assertEquals(['AAPL'], $results['existing']);
        $this->assertCount(1, $results['errors']);
        $this->assertEquals('invalid!', $results['errors'][0]['symbol']);
    }
}
