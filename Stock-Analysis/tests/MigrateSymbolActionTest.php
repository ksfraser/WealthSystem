<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/MigrateSymbolAction.php';
require_once __DIR__ . '/../src/TableTypeRegistry.php';
require_once __DIR__ . '/../src/IStockTableManager.php';
require_once __DIR__ . '/../src/IStockDataAccess.php';

/**
 * @covers MigrateSymbolAction
 */
class MigrateSymbolActionTest extends TestCase
{
    public function testExecuteInvalidSymbol()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockDataAccess = $this->createMock(IStockDataAccess::class);
        $mockPdo = $this->createMock(PDO::class);
        $action = new MigrateSymbolAction($mockTableManager, $mockDataAccess, $mockPdo);
        $result = $action->execute('ibm!', [], ['dry_run' => true]);
        $this->assertContains('Invalid symbol format', $result['errors']);
    }

    public function testExecuteDryRun()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockDataAccess = $this->createMock(IStockDataAccess::class);
        
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->method('execute')->willReturn(true);
        $mockStatement->method('fetch')->willReturn(['count' => 100]);
        
        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('prepare')->willReturn($mockStatement);
        
        $action = new MigrateSymbolAction($mockTableManager, $mockDataAccess, $mockPdo);
        
        $legacyTables = [
            'historical_prices' => [
                'target' => 'prices',
                'symbol_column' => 'symbol',
                'required_columns' => ['symbol', 'date', 'open', 'high', 'low', 'close']
            ]
        ];
        
        $result = $action->execute('IBM', $legacyTables, ['dry_run' => true]);
        
        $this->assertEquals('IBM', $result['symbol']);
        $this->assertEquals(1, $result['tables_migrated']);
        $this->assertEquals(100, $result['total_records']);
    }

    public function testExecuteWithEmptyTables()
    {
        $mockTableManager = $this->createMock(IStockTableManager::class);
        $mockDataAccess = $this->createMock(IStockDataAccess::class);
        
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->method('execute')->willReturn(true);
        $mockStatement->method('fetch')->willReturn(['count' => 0]);
        
        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('prepare')->willReturn($mockStatement);
        
        $action = new MigrateSymbolAction($mockTableManager, $mockDataAccess, $mockPdo);
        
        $legacyTables = [
            'historical_prices' => [
                'target' => 'prices',
                'symbol_column' => 'symbol',
                'required_columns' => ['symbol', 'date', 'open', 'high', 'low', 'close']
            ]
        ];
        
        $result = $action->execute('IBM', $legacyTables, ['dry_run' => false]);
        
        $this->assertEquals('IBM', $result['symbol']);
        $this->assertEquals(0, $result['tables_migrated']);
        $this->assertEquals(0, $result['total_records']);
    }
}
