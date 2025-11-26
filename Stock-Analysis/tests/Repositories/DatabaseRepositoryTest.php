<?php
/**
 * @covers \Ksfraser\Finance\Repositories\DatabaseRepository
 *
 * Unit tests for per-symbol technical table methods.
 */

use PHPUnit\Framework\TestCase;
use Ksfraser\Finance\Repositories\DatabaseRepository;
use Ksfraser\Finance\Repositories\SQLiteTechnicalTableRepository;

class DatabaseRepositoryTest extends TestCase
{
    private $pdo;
    private $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $techRepo = new SQLiteTechnicalTableRepository($this->pdo);
        $this->repo = new DatabaseRepository($this->pdo, $techRepo);
    }

    public function testGetSymbolTechnicalTableName()
    {
        $this->assertEquals('AAPL_technical', $this->repo->getSymbolTechnicalTableName('AAPL'));
        $this->assertEquals('TSLA_technical', $this->repo->getSymbolTechnicalTableName('TSLA'));
    }

    public function testCreateSymbolTechnicalTable()
    {
        $result = $this->repo->createSymbolTechnicalTable('AAPL');
        $this->assertTrue($result);
        $tables = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='AAPL_technical'")->fetchAll();
        $this->assertNotEmpty($tables);
    }

    public function testSaveSymbolTechnicalValuesInsertAndUpdate()
    {
        $this->repo->createSymbolTechnicalTable('AAPL');
        $values = [
            'date' => '2025-10-06',
            'rsi_14' => 55.5,
            'sma_20' => 100.1,
            'ema_20' => 101.2,
            'macd' => 1.1,
            'macd_signal' => 1.2,
            'macd_hist' => 0.1,
            'bbands_upper' => 110.0,
            'bbands_middle' => 105.0,
            'bbands_lower' => 100.0
        ];
        $result = $this->repo->saveSymbolTechnicalValues('AAPL', $values);
        $this->assertTrue($result);
        // Update
        $values['rsi_14'] = 60.0;
        $result2 = $this->repo->saveSymbolTechnicalValues('AAPL', $values);
        $this->assertTrue($result2);
        $row = $this->pdo->query("SELECT * FROM AAPL_technical WHERE date='2025-10-06'")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(60.0, $row['rsi_14']);
    }
}
