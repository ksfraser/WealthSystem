<?php
/**
 * @covers \Ksfraser\Finance\Repositories\MySQLTechnicalTableRepository
 * @covers \Ksfraser\Finance\Repositories\SQLiteTechnicalTableRepository
 */

use PHPUnit\Framework\TestCase;
use Ksfraser\Finance\Repositories\MySQLTechnicalTableRepository;
use Ksfraser\Finance\Repositories\SQLiteTechnicalTableRepository;

class TechnicalTableRepositoryTest extends TestCase
{
    public function testGetSymbolTechnicalTableName()
    {
        $mysql = new MySQLTechnicalTableRepository($this->createMock(PDO::class));
        $sqlite = new SQLiteTechnicalTableRepository($this->createMock(PDO::class));
        $this->assertEquals('AAPL_technical', $mysql->getSymbolTechnicalTableName('AAPL'));
        $this->assertEquals('AAPL_technical', $sqlite->getSymbolTechnicalTableName('AAPL'));
    }

    public function testCreateTableAndUpsertSQLite()
    {
        $pdo = new PDO('sqlite::memory:');
        $repo = new SQLiteTechnicalTableRepository($pdo);
        $this->assertTrue($repo->createSymbolTechnicalTable('AAPL'));
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
        $this->assertTrue($repo->saveSymbolTechnicalValues('AAPL', $values));
        $values['rsi_14'] = 60.0;
        $this->assertTrue($repo->saveSymbolTechnicalValues('AAPL', $values));
        $row = $pdo->query("SELECT * FROM AAPL_technical WHERE date='2025-10-06'")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(60.0, $row['rsi_14']);
    }
}
