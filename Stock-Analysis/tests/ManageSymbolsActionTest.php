<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/ManageSymbolsAction.php';
require_once __DIR__ . '/../src/IStockTableManager.php';

/**
 * @covers ManageSymbolsAction
 */
class ManageSymbolsActionTest extends TestCase
{
    public function testListSymbolsReturnsAll()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockTableManager->method('getAllSymbols')->willReturn([
            ['symbol' => 'IBM', 'active' => true],
            ['symbol' => 'AAPL', 'active' => false]
        ]);
    $mockPdo = $this->createMock(PDO::class);
    $action = new ManageSymbolsAction($mockTableManager, $mockPdo);
        $symbols = $action->listSymbols();
        $this->assertCount(2, $symbols);
        $this->assertEquals('IBM', $symbols[0]['symbol']);
    }

    public function testStatsReturnsNullIfNotFound()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockTableManager->method('getSymbolTableStats')->willReturn(null);
    $mockPdo = $this->createMock(PDO::class);
    $action = new ManageSymbolsAction($mockTableManager, $mockPdo);
        $this->assertNull($action->stats('FAKE'));
    }

    public function testCheckReturnsExistsAndStats()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockTableManager->method('tablesExistForSymbol')->willReturn(true);
        $mockTableManager->method('getSymbolTableStats')->willReturn(['row_count' => 5]);
    $mockPdo = $this->createMock(PDO::class);
    $action = new ManageSymbolsAction($mockTableManager, $mockPdo);
        $results = $action->check(['IBM'], true);
        $this->assertTrue($results[0]['exists']);
        $this->assertEquals(5, $results[0]['stats']['row_count']);
    }

    public function testRemoveCallsManager()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockTableManager->expects($this->once())->method('removeTablesForSymbol')->with('IBM', true)->willReturn(true);
        $mockPdo = $this->createMock(PDO::class);
        $action = new ManageSymbolsAction($mockTableManager, $mockPdo);
        $this->assertTrue($action->remove('IBM'));
    }

    public function testActivateNewSymbol()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockTableManager->method('getAllSymbols')->willReturn([]);
        $mockTableManager->expects($this->once())->method('registerSymbol')
            ->with('TSLA', [
                'company_name' => '',
                'sector' => '',
                'industry' => '',
                'market_cap' => 'micro',
                'active' => true
            ]);
        $mockPdo = $this->createMock(PDO::class);
        $action = new ManageSymbolsAction($mockTableManager, $mockPdo);
        
        $this->assertTrue($action->activate('TSLA'));
    }

    public function testActivateExistingSymbol()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockTableManager->method('getAllSymbols')->willReturn([
            ['symbol' => 'IBM']
        ]);
        
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->method('execute')->willReturn(true);
        
        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('prepare')->willReturn($mockStatement);
        
        $action = new ManageSymbolsAction($mockTableManager, $mockPdo);
        $this->assertTrue($action->activate('IBM'));
    }

    public function testDeactivateSymbol()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockTableManager->expects($this->once())->method('deactivateSymbol')->with('IBM')->willReturn(true);
        $mockPdo = $this->createMock(PDO::class);
        $action = new ManageSymbolsAction($mockTableManager, $mockPdo);
        
        $this->assertTrue($action->deactivate('IBM'));
    }

    public function testCleanupOrphanedTables()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockTableManager->method('getAllSymbols')->willReturn([
            ['symbol' => 'IBM'],
            ['symbol' => 'AAPL']
        ]);
        
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->method('fetchAll')->willReturn(['IBM_prices', 'AAPL_prices', 'ORPHAN_prices']);
        $mockStatement->method('execute')->willReturn(true);
        
        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('query')->willReturn($mockStatement);
        $mockPdo->method('prepare')->willReturn($mockStatement);
        
        $action = new ManageSymbolsAction($mockTableManager, $mockPdo);
        $orphaned = $action->cleanup();
        
        $this->assertContains('ORPHAN_prices', $orphaned);
    }
}
