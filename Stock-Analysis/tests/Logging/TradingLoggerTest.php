<?php

declare(strict_types=1);

namespace Tests\Logging;

use PHPUnit\Framework\TestCase;
use App\Logging\TradingLogger;
use Monolog\Logger;

class TradingLoggerTest extends TestCase
{
    private string $logPath;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->logPath = sys_get_temp_dir() . '/test_trading_' . uniqid() . '.log';
    }
    
    protected function tearDown(): void
    {
        // Clean up both possible log file names
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
        
        $dateLog = preg_replace('/\.log$/', '-' . date('Y-m-d') . '.log', $this->logPath);
        if (file_exists($dateLog)) {
            unlink($dateLog);
        }
        
        parent::tearDown();
    }
    
    private function getLastLogEntry(): ?array
    {
        // RotatingFileHandler adds date suffix
        $actualPath = $this->logPath;
        if (!file_exists($actualPath)) {
            // Try with date suffix
            $actualPath = preg_replace('/\.log$/', '-' . date('Y-m-d') . '.log', $this->logPath);
        }
        
        if (!file_exists($actualPath)) {
            return null;
        }
        
        $lines = file($actualPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            return null;
        }
        
        return json_decode(end($lines), true);
    }
    
    public function testLoggerCreation(): void
    {
        $logger = new TradingLogger('test', ['log_path' => $this->logPath]);
        
        $this->assertInstanceOf(TradingLogger::class, $logger);
        $this->assertInstanceOf(Logger::class, $logger->getLogger());
    }
    
    public function testDebugLogging(): void
    {
        $logger = new TradingLogger('test', ['log_path' => $this->logPath]);
        $logger->debug('Test debug message', ['key' => 'value']);
        
        // Force handler to flush
        $logger->getLogger()->close();
        
        $entry = $this->getLastLogEntry();
        
        $this->assertNotNull($entry, 'Log file not found at: ' . $this->logPath);
        $this->assertSame('Test debug message', $entry['message']);
        $this->assertSame('DEBUG', $entry['level_name']);
        $this->assertSame('value', $entry['context']['key']);
    }
    
    public function testInfoLogging(): void
    {
        $logger = new TradingLogger('test', ['log_path' => $this->logPath]);
        $logger->info('Test info message');
        $logger->getLogger()->close();
        
        $entry = $this->getLastLogEntry();
        
        $this->assertSame('INFO', $entry['level_name']);
        $this->assertArrayHasKey('timestamp', $entry['context']);
    }
    
    public function testWarningLogging(): void
    {
        $logger = new TradingLogger('test', ['log_path' => $this->logPath]);
        $logger->warning('Test warning');
        $logger->getLogger()->close();
        
        $entry = $this->getLastLogEntry();
        
        $this->assertSame('WARNING', $entry['level_name']);
    }
    
    public function testErrorLogging(): void
    {
        $logger = new TradingLogger('test', ['log_path' => $this->logPath]);
        $logger->error('Test error');
        $logger->getLogger()->close();
        
        $entry = $this->getLastLogEntry();
        
        $this->assertSame('ERROR', $entry['level_name']);
    }
    
    public function testCriticalLogging(): void
    {
        $logger = new TradingLogger('test', ['log_path' => $this->logPath]);
        $logger->critical('Test critical');
        $logger->getLogger()->close();
        
        $entry = $this->getLastLogEntry();
        
        $this->assertSame('CRITICAL', $entry['level_name']);
    }
    
    public function testGlobalContext(): void
    {
        $logger = new TradingLogger('test', ['log_path' => $this->logPath]);
        $logger->setContext(['session_id' => '12345', 'user' => 'trader1']);
        $logger->info('Test message');
        $logger->getLogger()->close();
        
        $entry = $this->getLastLogEntry();
        
        $this->assertSame('12345', $entry['context']['session_id']);
        $this->assertSame('trader1', $entry['context']['user']);
    }
    
    public function testStrategyExecutionLogging(): void
    {
        $logger = new TradingLogger('test', ['log_path' => $this->logPath]);
        
        $signal = [
            'action' => 'BUY',
            'confidence' => 0.85,
            'entry_price' => 150.00
        ];
        
        $logger->logStrategyExecution('MeanReversion', 'AAPL', $signal);
        $logger->getLogger()->close();
        
        $entry = $this->getLastLogEntry();
        
        $this->assertSame('Strategy executed', $entry['message']);
        $this->assertSame('strategy', $entry['context']['category']);
        $this->assertSame('MeanReversion', $entry['context']['strategy']);
        $this->assertSame('AAPL', $entry['context']['symbol']);
        $this->assertSame('BUY', $entry['context']['action']);
        $this->assertSame(0.85, $entry['context']['confidence']);
    }
    
    public function testTradeLogging(): void
    {
        $logger = new TradingLogger('test', ['log_path' => $this->logPath]);
        
        $trade = [
            'symbol' => 'TSLA',
            'action' => 'SELL',
            'quantity' => 50,
            'price' => 250.00
        ];
        
        $logger->logTrade($trade);
        $logger->getLogger()->close();
        
        $entry = $this->getLastLogEntry();
        
        $this->assertSame('Trade executed', $entry['message']);
        $this->assertSame('trade', $entry['context']['category']);
        $this->assertSame('TSLA', $entry['context']['symbol']);
        $this->assertSame('SELL', $entry['context']['action']);
        $this->assertSame(50, $entry['context']['quantity']);
    }
    
    public function testAlertLogging(): void
    {
        $logger = new TradingLogger('test', ['log_path' => $this->logPath]);
        
        $alert = [
            'severity' => 'high',
            'message' => 'Price spike detected',
            'change_percent' => 15.5
        ];
        
        $logger->logAlert('price_spike', 'NVDA', $alert);
        $logger->getLogger()->close();
        
        $entry = $this->getLastLogEntry();
        
        $this->assertSame('WARNING', $entry['level_name']);
        $this->assertSame('Alert generated', $entry['message']);
        $this->assertSame('alert', $entry['context']['category']);
        $this->assertSame('price_spike', $entry['context']['alert_type']);
        $this->assertSame('high', $entry['context']['severity']);
    }
    
    public function testExceptionLogging(): void
    {
        $logger = new TradingLogger('test', ['log_path' => $this->logPath]);
        
        $exception = new \RuntimeException('Test exception', 500);
        $logger->logException($exception, ['additional' => 'context']);
        $logger->getLogger()->close();
        
        $entry = $this->getLastLogEntry();
        
        $this->assertSame('ERROR', $entry['level_name']);
        $this->assertSame('Test exception', $entry['message']);
        $this->assertSame('exception', $entry['context']['category']);
        $this->assertSame(\RuntimeException::class, $entry['context']['exception_class']);
        $this->assertSame(500, $entry['context']['exception_code']);
        $this->assertArrayHasKey('file', $entry['context']);
        $this->assertArrayHasKey('trace', $entry['context']);
        $this->assertSame('context', $entry['context']['additional']);
    }
    
    public function testAPICallLogging(): void
    {
        $logger = new TradingLogger('test', ['log_path' => $this->logPath]);
        $logger->logAPICall('/api/prices', 'GET', 200, 0.125);
        $logger->getLogger()->close();
        
        $entry = $this->getLastLogEntry();
        
        $this->assertSame('api', $entry['context']['category']);
        $this->assertSame('/api/prices', $entry['context']['endpoint']);
        $this->assertSame('GET', $entry['context']['method']);
        $this->assertSame(200, $entry['context']['status_code']);
        $this->assertSame(125.0, $entry['context']['duration_ms']);
    }
    
    public function testPerformanceLogging(): void
    {
        $logger = new TradingLogger('test', ['log_path' => $this->logPath]);
        
        $metrics = [
            'items_processed' => 1000,
            'cache_hits' => 950
        ];
        
        $logger->logPerformance('backtest_run', 2.5, $metrics);
        $logger->getLogger()->close();
        
        $entry = $this->getLastLogEntry();
        
        $this->assertSame('performance', $entry['context']['category']);
        $this->assertSame('backtest_run', $entry['context']['operation']);
        $this->assertSame(2500.0, $entry['context']['duration_ms']);
        $this->assertSame(1000, $entry['context']['items_processed']);
    }
    
    public function testMemoryTracking(): void
    {
        $logger = new TradingLogger('test', ['log_path' => $this->logPath]);
        $logger->info('Memory test');
        $logger->getLogger()->close();
        
        $entry = $this->getLastLogEntry();
        
        $this->assertArrayHasKey('memory_mb', $entry['context']);
        $this->assertIsFloat($entry['context']['memory_mb']);
        $this->assertGreaterThan(0, $entry['context']['memory_mb']);
    }
    
    public function testUIDProcessor(): void
    {
        $logger = new TradingLogger('test', ['log_path' => $this->logPath]);
        $logger->info('Test UID');
        $logger->getLogger()->close();
        
        $entry = $this->getLastLogEntry();
        
        $this->assertArrayHasKey('extra', $entry);
        $this->assertArrayHasKey('uid', $entry['extra']);
    }
    
    public function testJSONFormat(): void
    {
        $logger = new TradingLogger('test', ['log_path' => $this->logPath]);
        $logger->info('JSON test', ['data' => ['nested' => 'value']]);
        $logger->getLogger()->close();
        
        $entry = $this->getLastLogEntry();
        
        $this->assertIsArray($entry);
        $this->assertArrayHasKey('message', $entry);
        $this->assertArrayHasKey('context', $entry);
        $this->assertArrayHasKey('level', $entry);
    }
}
