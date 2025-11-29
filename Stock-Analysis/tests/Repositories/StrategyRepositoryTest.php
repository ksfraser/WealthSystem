<?php

namespace Tests\Repositories;

use PHPUnit\Framework\TestCase;
use App\Repositories\StrategyRepository;
use App\Repositories\StrategyRepositoryInterface;

/**
 * Tests for StrategyRepository
 * 
 * @covers \App\Repositories\StrategyRepository
 */
class StrategyRepositoryTest extends TestCase
{
    private StrategyRepository $repository;
    private string $testStoragePath;

    protected function setUp(): void
    {
        $this->testStoragePath = __DIR__ . '/../../storage/test_strategy_' . time();
        $this->repository = new StrategyRepository($this->testStoragePath);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $this->deleteDirectory($this->testStoragePath);
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(StrategyRepositoryInterface::class, $this->repository);
    }

    public function testStoreExecution(): void
    {
        $signal = [
            'signal' => 'BUY',
            'confidence' => 0.85,
            'reason' => 'Test signal',
            'entry_price' => 100.50
        ];

        $executionId = $this->repository->storeExecution(
            'TurtleStrategy',
            'AAPL',
            $signal,
            '2024-01-15 10:30:00'
        );

        $this->assertNotEmpty($executionId);
        $this->assertStringContainsString('TurtleStrategy', $executionId);
        $this->assertStringContainsString('AAPL', $executionId);
    }

    public function testGetExecutionsForSymbol(): void
    {
        // Store multiple executions
        $this->repository->storeExecution(
            'TurtleStrategy',
            'AAPL',
            ['signal' => 'BUY', 'confidence' => 0.85],
            '2024-01-15 10:00:00'
        );

        $this->repository->storeExecution(
            'MACrossover',
            'AAPL',
            ['signal' => 'SELL', 'confidence' => 0.75],
            '2024-01-15 11:00:00'
        );

        $executions = $this->repository->getExecutions('AAPL');

        $this->assertCount(2, $executions);
        $this->assertEquals('AAPL', $executions[0]['symbol']);
    }

    public function testGetExecutionsFilterByStrategy(): void
    {
        $this->repository->storeExecution(
            'TurtleStrategy',
            'AAPL',
            ['signal' => 'BUY'],
            '2024-01-15 10:00:00'
        );

        $this->repository->storeExecution(
            'MACrossover',
            'AAPL',
            ['signal' => 'SELL'],
            '2024-01-15 11:00:00'
        );

        $executions = $this->repository->getExecutions('AAPL', 'TurtleStrategy');

        $this->assertCount(1, $executions);
        $this->assertEquals('TurtleStrategy', $executions[0]['strategy']);
    }

    public function testGetExecutionsReturnsEmptyForNonExistentSymbol(): void
    {
        $executions = $this->repository->getExecutions('NONEXISTENT');
        $this->assertEmpty($executions);
    }

    public function testGetRecentExecutions(): void
    {
        $this->repository->storeExecution(
            'TurtleStrategy',
            'AAPL',
            ['signal' => 'BUY'],
            '2024-01-15 10:00:00'
        );

        $this->repository->storeExecution(
            'TurtleStrategy',
            'MSFT',
            ['signal' => 'SELL'],
            '2024-01-15 11:00:00'
        );

        $executions = $this->repository->getRecentExecutions();

        $this->assertGreaterThanOrEqual(2, count($executions));
    }

    public function testGetRecentExecutionsFilterByStrategy(): void
    {
        $this->repository->storeExecution(
            'TurtleStrategy',
            'AAPL',
            ['signal' => 'BUY'],
            '2024-01-15 10:00:00'
        );

        $this->repository->storeExecution(
            'MACrossover',
            'AAPL',
            ['signal' => 'SELL'],
            '2024-01-15 11:00:00'
        );

        $executions = $this->repository->getRecentExecutions('TurtleStrategy');

        $this->assertNotEmpty($executions);
        foreach ($executions as $exec) {
            $this->assertEquals('TurtleStrategy', $exec['strategy']);
        }
    }

    public function testStoreBacktest(): void
    {
        $config = [
            'start_date' => '2023-01-01',
            'end_date' => '2023-12-31',
            'symbols' => ['AAPL', 'MSFT']
        ];

        $results = [
            'total_trades' => 50,
            'winning_trades' => 32,
            'total_return' => 0.15
        ];

        $backtestId = $this->repository->storeBacktest(
            'TurtleStrategy',
            $config,
            $results,
            '2024-01-15 10:00:00'
        );

        $this->assertNotEmpty($backtestId);
        $this->assertStringStartsWith('bt_', $backtestId);
    }

    public function testGetBacktest(): void
    {
        $config = ['start_date' => '2023-01-01'];
        $results = ['total_trades' => 50];

        $backtestId = $this->repository->storeBacktest(
            'TurtleStrategy',
            $config,
            $results,
            '2024-01-15 10:00:00'
        );

        $backtest = $this->repository->getBacktest($backtestId);

        $this->assertNotNull($backtest);
        $this->assertEquals($backtestId, $backtest['id']);
        $this->assertEquals('TurtleStrategy', $backtest['strategy']);
        $this->assertEquals($config, $backtest['config']);
        $this->assertEquals($results, $backtest['results']);
    }

    public function testGetBacktestReturnsNullForNonExistent(): void
    {
        $backtest = $this->repository->getBacktest('nonexistent_id');
        $this->assertNull($backtest);
    }

    public function testGetBacktestsByStrategy(): void
    {
        $this->repository->storeBacktest(
            'TurtleStrategy',
            ['config' => 'test1'],
            ['results' => 'test1'],
            '2024-01-15 10:00:00'
        );

        $this->repository->storeBacktest(
            'TurtleStrategy',
            ['config' => 'test2'],
            ['results' => 'test2'],
            '2024-01-15 11:00:00'
        );

        $backtests = $this->repository->getBacktestsByStrategy('TurtleStrategy');

        $this->assertCount(2, $backtests);
        $this->assertEquals('TurtleStrategy', $backtests[0]['strategy']);
    }

    public function testStorePerformanceMetrics(): void
    {
        $metrics = [
            'win_rate' => 0.65,
            'avg_return' => 0.08,
            'sharpe_ratio' => 1.5
        ];

        $result = $this->repository->storePerformanceMetrics(
            'TurtleStrategy',
            $metrics,
            '2024-Q1'
        );

        $this->assertTrue($result);
    }

    public function testGetPerformanceMetrics(): void
    {
        $metrics = [
            'win_rate' => 0.65,
            'avg_return' => 0.08
        ];

        $this->repository->storePerformanceMetrics(
            'TurtleStrategy',
            $metrics,
            '2024-Q1'
        );

        $retrieved = $this->repository->getPerformanceMetrics('TurtleStrategy', '2024-Q1');

        $this->assertNotEmpty($retrieved);
        $this->assertEquals($metrics, $retrieved['metrics']);
    }

    public function testGetAllPerformanceMetrics(): void
    {
        $this->repository->storePerformanceMetrics(
            'TurtleStrategy',
            ['win_rate' => 0.65],
            '2024-Q1'
        );

        $this->repository->storePerformanceMetrics(
            'TurtleStrategy',
            ['win_rate' => 0.70],
            '2024-Q2'
        );

        $allMetrics = $this->repository->getPerformanceMetrics('TurtleStrategy');

        $this->assertCount(2, $allMetrics);
        $this->assertArrayHasKey('2024-Q1', $allMetrics);
        $this->assertArrayHasKey('2024-Q2', $allMetrics);
    }

    public function testGetStrategyStatistics(): void
    {
        $this->repository->storeExecution(
            'TurtleStrategy',
            'AAPL',
            ['signal' => 'BUY', 'confidence' => 0.80],
            '2024-01-15 10:00:00'
        );

        $this->repository->storeExecution(
            'TurtleStrategy',
            'MSFT',
            ['signal' => 'SELL', 'confidence' => 0.75],
            '2024-01-15 11:00:00'
        );

        $this->repository->storeExecution(
            'TurtleStrategy',
            'GOOGL',
            ['signal' => 'HOLD', 'confidence' => 0.50],
            '2024-01-15 12:00:00'
        );

        $stats = $this->repository->getStrategyStatistics('TurtleStrategy');

        $this->assertEquals(3, $stats['total_signals']);
        $this->assertEquals(1, $stats['buy_signals']);
        $this->assertEquals(1, $stats['sell_signals']);
        $this->assertEquals(1, $stats['hold_signals']);
        $this->assertGreaterThan(0, $stats['avg_confidence']);
    }

    public function testGetStrategyStatisticsReturnsZerosForNonExistent(): void
    {
        $stats = $this->repository->getStrategyStatistics('NonExistentStrategy');

        $this->assertEquals(0, $stats['total_signals']);
        $this->assertEquals(0, $stats['buy_signals']);
    }

    public function testDeleteOldExecutions(): void
    {
        // Store old execution
        $this->repository->storeExecution(
            'TurtleStrategy',
            'AAPL',
            ['signal' => 'BUY'],
            date('Y-m-d H:i:s', strtotime('-100 days'))
        );

        // Store recent execution
        $this->repository->storeExecution(
            'TurtleStrategy',
            'AAPL',
            ['signal' => 'SELL'],
            date('Y-m-d H:i:s')
        );

        $deletedCount = $this->repository->deleteOldExecutions(90);

        $this->assertGreaterThanOrEqual(1, $deletedCount);

        // Verify recent execution still exists
        $executions = $this->repository->getExecutions('AAPL');
        $this->assertNotEmpty($executions);
    }

    public function testGetAvailableStrategies(): void
    {
        $this->repository->storeExecution(
            'TurtleStrategy',
            'AAPL',
            ['signal' => 'BUY'],
            '2024-01-15 10:00:00'
        );

        $this->repository->storeExecution(
            'MACrossover',
            'MSFT',
            ['signal' => 'SELL'],
            '2024-01-15 11:00:00'
        );

        $strategies = $this->repository->getAvailableStrategies();

        $this->assertContains('TurtleStrategy', $strategies);
        $this->assertContains('MACrossover', $strategies);
    }

    /**
     * Helper: Recursively delete directory
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }
}
