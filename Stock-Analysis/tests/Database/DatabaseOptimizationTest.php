<?php

declare(strict_types=1);

namespace Tests\Database;

use PHPUnit\Framework\TestCase;
use App\Database\DatabaseOptimizer;
use PDO;

/**
 * Test suite for DatabaseOptimizer
 * 
 * Tests database performance optimization including:
 * - Index analysis and recommendations
 * - Query performance monitoring
 * - Slow query detection
 * - Index usage statistics
 * - Table statistics and analysis
 * 
 * @covers \App\Database\DatabaseOptimizer
 */
class DatabaseOptimizationTest extends TestCase
{
    private ?DatabaseOptimizer $optimizer = null;
    private ?PDO $pdo = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create test tables
        $this->createTestTables();
        
        $this->optimizer = new DatabaseOptimizer($this->pdo);
    }

    private function createTestTables(): void
    {
        // Users table
        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                email VARCHAR(255),
                username VARCHAR(100),
                created_at TIMESTAMP,
                last_login TIMESTAMP,
                status VARCHAR(20)
            )
        ");
        
        // Portfolio table
        $this->pdo->exec("
            CREATE TABLE portfolio (
                id INTEGER PRIMARY KEY,
                user_id INTEGER,
                symbol VARCHAR(10),
                shares INTEGER,
                cost_basis DECIMAL(15,2),
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )
        ");
        
        // Transactions table
        $this->pdo->exec("
            CREATE TABLE transactions (
                id INTEGER PRIMARY KEY,
                user_id INTEGER,
                symbol VARCHAR(10),
                type VARCHAR(10),
                shares INTEGER,
                price DECIMAL(15,2),
                transaction_date TIMESTAMP
            )
        ");
    }

    /**
     * @test
     * @group optimization
     */
    public function itAnalyzesTableIndexes(): void
    {
        $analysis = $this->optimizer->analyzeTableIndexes('portfolio');
        
        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('table', $analysis);
        $this->assertArrayHasKey('indexes', $analysis);
        $this->assertArrayHasKey('recommendations', $analysis);
    }

    /**
     * @test
     * @group optimization
     */
    public function itRecommendsIndexesForForeignKeys(): void
    {
        $recommendations = $this->optimizer->getIndexRecommendations('portfolio');
        
        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);
        
        // Should recommend index on user_id (foreign key)
        $hasUserIdRecommendation = false;
        foreach ($recommendations as $rec) {
            if (isset($rec['column']) && $rec['column'] === 'user_id') {
                $hasUserIdRecommendation = true;
                break;
            }
        }
        
        $this->assertTrue($hasUserIdRecommendation, 'Should recommend index on user_id');
    }

    /**
     * @test
     * @group optimization
     */
    public function itRecommendsIndexesForFrequentQueries(): void
    {
        // Simulate frequent queries on symbol
        $this->optimizer->trackQuery("SELECT * FROM portfolio WHERE symbol = 'AAPL'");
        $this->optimizer->trackQuery("SELECT * FROM portfolio WHERE symbol = 'GOOGL'");
        $this->optimizer->trackQuery("SELECT * FROM portfolio WHERE symbol = 'MSFT'");
        
        $recommendations = $this->optimizer->getIndexRecommendations('portfolio');
        
        // Should recommend index on symbol (frequently queried)
        $hasSymbolRecommendation = false;
        foreach ($recommendations as $rec) {
            if (isset($rec['column']) && $rec['column'] === 'symbol') {
                $hasSymbolRecommendation = true;
                break;
            }
        }
        
        $this->assertTrue($hasSymbolRecommendation);
    }

    /**
     * @test
     * @group optimization
     */
    public function itDetectsSlowQueries(): void
    {
        // Track a slow query
        $this->optimizer->trackQuery(
            "SELECT * FROM portfolio WHERE user_id > 1000",
            0.5 // 500ms execution time
        );
        
        $slowQueries = $this->optimizer->getSlowQueries(0.1); // Threshold: 100ms
        
        $this->assertNotEmpty($slowQueries);
        $this->assertGreaterThan(0.1, $slowQueries[0]['execution_time']);
    }

    /**
     * @test
     * @group optimization
     */
    public function itCalculatesTableStatistics(): void
    {
        // Insert test data
        for ($i = 1; $i <= 100; $i++) {
            $this->pdo->exec("INSERT INTO portfolio (user_id, symbol, shares) VALUES ({$i}, 'AAPL', 100)");
        }
        
        $stats = $this->optimizer->getTableStatistics('portfolio');
        
        $this->assertArrayHasKey('row_count', $stats);
        $this->assertArrayHasKey('table_size', $stats);
        $this->assertArrayHasKey('index_count', $stats);
        $this->assertEquals(100, $stats['row_count']);
    }

    /**
     * @test
     * @group optimization
     */
    public function itIdentifiesMissingIndexes(): void
    {
        $missing = $this->optimizer->findMissingIndexes();
        
        $this->assertIsArray($missing);
        
        // Should identify tables without proper indexes
        foreach ($missing as $item) {
            $this->assertArrayHasKey('table', $item);
            $this->assertArrayHasKey('column', $item);
            $this->assertArrayHasKey('reason', $item);
        }
    }

    /**
     * @test
     * @group optimization
     */
    public function itGeneratesIndexCreationSQL(): void
    {
        $recommendations = $this->optimizer->getIndexRecommendations('portfolio');
        
        if (!empty($recommendations)) {
            $sql = $this->optimizer->generateIndexSQL($recommendations[0]);
            
            $this->assertIsString($sql);
            $this->assertStringContainsString('CREATE INDEX', $sql);
        } else {
            $this->markTestSkipped('No recommendations available');
        }
    }

    /**
     * @test
     * @group optimization
     */
    public function itAnalyzesQueryPerformance(): void
    {
        // Track multiple queries
        $this->optimizer->trackQuery("SELECT * FROM users WHERE email = 'test@example.com'", 0.05);
        $this->optimizer->trackQuery("SELECT * FROM portfolio WHERE user_id = 1", 0.02);
        $this->optimizer->trackQuery("SELECT * FROM transactions WHERE user_id = 1 AND transaction_date > '2024-01-01'", 0.15);
        
        $performance = $this->optimizer->getQueryPerformanceReport();
        
        $this->assertArrayHasKey('total_queries', $performance);
        $this->assertArrayHasKey('average_time', $performance);
        $this->assertArrayHasKey('slowest_queries', $performance);
        $this->assertEquals(3, $performance['total_queries']);
    }

    /**
     * @test
     * @group optimization
     */
    public function itRecommendsCompositeIndexes(): void
    {
        // Simulate queries using multiple columns
        $this->optimizer->trackQuery("SELECT * FROM transactions WHERE user_id = 1 AND transaction_date > '2024-01-01'");
        $this->optimizer->trackQuery("SELECT * FROM transactions WHERE user_id = 2 AND transaction_date > '2024-01-01'");
        
        $recommendations = $this->optimizer->getIndexRecommendations('transactions');
        
        // Should recommend composite index
        $hasComposite = false;
        foreach ($recommendations as $rec) {
            if (isset($rec['columns']) && is_array($rec['columns']) && count($rec['columns']) > 1) {
                $hasComposite = true;
                break;
            }
        }
        
        $this->assertTrue($hasComposite, 'Should recommend composite index');
    }

    /**
     * @test
     * @group optimization
     */
    public function itChecksIndexUsage(): void
    {
        // Add an index
        $this->pdo->exec("CREATE INDEX idx_portfolio_user ON portfolio(user_id)");
        
        $usage = $this->optimizer->getIndexUsageStatistics('portfolio');
        
        $this->assertIsArray($usage);
        
        foreach ($usage as $stat) {
            $this->assertArrayHasKey('index_name', $stat);
            $this->assertArrayHasKey('is_used', $stat);
        }
    }

    /**
     * @test
     * @group optimization
     */
    public function itIdentifiesUnusedIndexes(): void
    {
        // Create indexes
        $this->pdo->exec("CREATE INDEX idx_portfolio_user ON portfolio(user_id)");
        $this->pdo->exec("CREATE INDEX idx_portfolio_unused ON portfolio(created_at)");
        
        // Mark user_id index as used
        $this->optimizer->trackQuery("SELECT * FROM portfolio WHERE user_id = 1");
        
        $unused = $this->optimizer->findUnusedIndexes();
        
        $this->assertIsArray($unused);
    }

    /**
     * @test
     * @group optimization
     */
    public function itValidatesIndexEffectiveness(): void
    {
        $effectiveness = $this->optimizer->analyzeIndexEffectiveness('portfolio');
        
        $this->assertIsArray($effectiveness);
        $this->assertArrayHasKey('total_indexes', $effectiveness);
        $this->assertArrayHasKey('used_indexes', $effectiveness);
        $this->assertArrayHasKey('effectiveness_score', $effectiveness);
    }

    /**
     * @test
     * @group optimization
     */
    public function itGeneratesOptimizationReport(): void
    {
        $report = $this->optimizer->generateOptimizationReport();
        
        $this->assertIsArray($report);
        $this->assertArrayHasKey('tables_analyzed', $report);
        $this->assertArrayHasKey('recommendations', $report);
        $this->assertArrayHasKey('slow_queries', $report);
        $this->assertArrayHasKey('missing_indexes', $report);
    }

    /**
     * @test
     * @group optimization
     */
    public function itHandlesInvalidTableName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->optimizer->analyzeTableIndexes('nonexistent_table');
    }

    /**
     * @test
     * @group optimization
     */
    public function itTracksDuplicateIndexes(): void
    {
        // Create duplicate indexes
        $this->pdo->exec("CREATE INDEX idx_portfolio_user1 ON portfolio(user_id)");
        $this->pdo->exec("CREATE INDEX idx_portfolio_user2 ON portfolio(user_id)");
        
        $duplicates = $this->optimizer->findDuplicateIndexes('portfolio');
        
        $this->assertIsArray($duplicates);
        $this->assertNotEmpty($duplicates);
    }

    protected function tearDown(): void
    {
        if ($this->pdo instanceof PDO) {
            $this->pdo = null;
        }
        parent::tearDown();
    }
}
