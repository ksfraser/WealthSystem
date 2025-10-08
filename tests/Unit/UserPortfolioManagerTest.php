<?php
/**
 * Unit tests for UserPortfolioManager CSV operations
 */
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/web_ui/UserPortfolioManager.php';
require_once dirname(__DIR__, 2) . '/web_ui/Logger.php';
require_once dirname(__DIR__, 2) . '/web_ui/CsvParser.php';

class UserPortfolioManagerTest extends TestCase {
    private $manager;
    private $testUserId = 9999;
    private $testCsvPath;

    protected function setUp(): void {
        $this->testCsvPath = __DIR__ . '/test_portfolio.csv';
        $this->manager = new UserPortfolioManager($this->testCsvPath);
    }

    protected function tearDown(): void {
        // Remove any CSV files for the test user in the expected directory
        $csvDir = dirname($this->testCsvPath) . '/users/' . $this->testUserId;
        if (is_dir($csvDir)) {
            foreach (glob($csvDir . '/*.csv') as $file) {
                unlink($file);
            }
            $files = scandir($csvDir);
            if (count($files) == 2) { // only . and ..
                rmdir($csvDir);
            }
        }
    }

    public function testWriteAndReadUserPortfolioCsv() {
        $rows = [
            [
                'symbol' => 'AAPL',
                'shares' => 10,
                'market_value' => 1500,
                'book_cost' => 1200,
                'gain_loss' => 300,
                'gain_loss_percent' => 0.25,
                'current_price' => 150,
                'date' => '2025-10-08',
                'user_id' => $this->testUserId
            ]
        ];
        $this->assertTrue($this->manager->writeUserPortfolioCsv($rows, $this->testUserId));
        $readRows = $this->manager->readUserPortfolioCsv($this->testUserId);
        $this->assertCount(1, $readRows);
        $this->assertEquals('AAPL', $readRows[0]['symbol']);
    }
}
