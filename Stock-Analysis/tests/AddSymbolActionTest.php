<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/AddSymbolAction.php';
require_once __DIR__ . '/../src/TableTypeRegistry.php';
require_once __DIR__ . '/../src/IStockTableManager.php';

/**
 * @covers AddSymbolAction
 */
class AddSymbolActionTest extends TestCase
{
    public function testExecuteCreatesSymbol()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockTableManager->method('getAllSymbols')->willReturn([]);
        $mockTableManager->expects($this->once())->method('registerSymbol');
        $mockTableManager->expects($this->once())->method('createTablesForSymbol');
        $action = new AddSymbolAction($mockTableManager);
        $result = $action->execute('IBM');
        $this->assertEquals('created', $result['status']);
        $this->assertEquals('IBM', $result['symbol']);
    }

    public function testExecuteSymbolExists()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockTableManager->method('getAllSymbols')->willReturn([
            ['symbol' => 'IBM']
        ]);
        $mockTableManager->method('tablesExistForSymbol')->willReturn(true);
        $mockTableManager->expects($this->never())->method('registerSymbol');
        $mockTableManager->expects($this->never())->method('createTablesForSymbol');
        $action = new AddSymbolAction($mockTableManager);
        $result = $action->execute('IBM');
        $this->assertEquals('exists', $result['status']);
        $this->assertEquals('IBM', $result['symbol']);
    }

    public function testExecuteInvalidSymbol()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $action = new AddSymbolAction($mockTableManager);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid symbol format: ibm!');
        $action->execute('ibm!');
    }

    public function testExecuteSymbolExistsButNoTables()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockTableManager->method('getAllSymbols')->willReturn([
            ['symbol' => 'IBM']
        ]);
        $mockTableManager->method('tablesExistForSymbol')->willReturn(false);
        $mockTableManager->expects($this->once())->method('createTablesForSymbol');
        $mockTableManager->expects($this->never())->method('registerSymbol');
        
        $action = new AddSymbolAction($mockTableManager);
        $result = $action->execute('IBM');
        
        $this->assertEquals('exists', $result['status']);
        $this->assertEquals('IBM', $result['symbol']);
    }

    public function testExecuteWithCompanyData()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockTableManager->method('getAllSymbols')->willReturn([]);
        $mockTableManager->expects($this->once())->method('registerSymbol')
            ->with('MSFT', ['company_name' => 'Microsoft', 'sector' => 'Technology']);
        $mockTableManager->expects($this->once())->method('createTablesForSymbol');
        
        $action = new AddSymbolAction($mockTableManager);
        $result = $action->execute('MSFT', ['company_name' => 'Microsoft', 'sector' => 'Technology']);
        
        $this->assertEquals('created', $result['status']);
        $this->assertEquals('MSFT', $result['symbol']);
    }
}
