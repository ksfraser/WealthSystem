<?php
/**
 * @covers BatchTechnicalCalculationService
 */

use PHPUnit\Framework\TestCase;
use Ksfraser\Finance\Repositories\DatabaseRepository;
use Ksfraser\Finance\Repositories\SQLiteTechnicalTableRepository;

use Services\Calculators\TALibCalculators;
use Services\Calculators\BatchTechnicalCalculationService;

class BatchTechnicalCalculationServiceTest extends TestCase
{
    public function testBatchCalculationAndStorage()
    {
        $pdo = new PDO('sqlite::memory:');
        $techRepo = new Ksfraser\Finance\Repositories\SQLiteTechnicalTableRepository($pdo);
        $repo = new Ksfraser\Finance\Repositories\DatabaseRepository($pdo, $techRepo);

        $service = new BatchTechnicalCalculationService($repo);
        $symbols = ['AAPL'];
        $ohlcv = [
            'AAPL' => [
                ['Date' => '2025-10-01', 'Open' => 100, 'High' => 110, 'Low' => 95, 'Close' => 105, 'Volume' => 10000],
                ['Date' => '2025-10-02', 'Open' => 106, 'High' => 112, 'Low' => 104, 'Close' => 110, 'Volume' => 12000],
                ['Date' => '2025-10-03', 'Open' => 111, 'High' => 115, 'Low' => 109, 'Close' => 113, 'Volume' => 13000],
                // ...more rows for indicator calculation
            ]
        ];
        $indicators = ['rsi', 'sma', 'ema', 'macd', 'bbands'];
        $params = ['rsi' => 2, 'sma' => 2, 'ema' => 2, 'macd_fast' => 2, 'macd_slow' => 3, 'macd_signal' => 2, 'bbands' => 2];

        $service->processAll($symbols, $ohlcv, $indicators, $params);

        // Check that the technical table exists and has rows for each date
        $rows = $pdo->query("SELECT * FROM AAPL_technical ORDER BY date")->fetchAll(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($rows);
        $this->assertEquals('2025-10-01', $rows[0]['date']);
        $this->assertArrayHasKey('rsi_14', $rows[0]);
        $this->assertArrayHasKey('sma_20', $rows[0]);
        $this->assertArrayHasKey('ema_20', $rows[0]);
        $this->assertArrayHasKey('macd', $rows[0]);
        $this->assertArrayHasKey('bbands_upper', $rows[0]);
    }
}
