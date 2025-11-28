<?php

namespace Tests\Repositories;

use App\Repositories\MarketDataRepository;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for MarketDataRepository (TDD approach)
 * 
 * Tests all CRUD operations, cache management, and data retrieval
 * for market data persistence (fundamentals and price history).
 */
class MarketDataRepositoryTest extends TestCase
{
    private MarketDataRepository $repository;
    private string $tempDir;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create temporary directory for test data
        $this->tempDir = sys_get_temp_dir() . '/market_data_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        
        $this->repository = new MarketDataRepository($this->tempDir);
    }
    
    protected function tearDown(): void
    {
        // Clean up test files
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
        
        parent::tearDown();
    }
    
    public function testStoreFundamentals(): void
    {
        // Arrange
        $symbol = 'AAPL';
        $fundamentals = [
            'market_cap' => 2800000000000,
            'pe_ratio' => 28.5,
            'revenue' => 394328000000
        ];
        
        // Act
        $result = $this->repository->storeFundamentals($symbol, $fundamentals);
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function testGetFundamentals(): void
    {
        // Arrange
        $symbol = 'AAPL';
        $fundamentals = [
            'market_cap' => 2800000000000,
            'pe_ratio' => 28.5
        ];
        $this->repository->storeFundamentals($symbol, $fundamentals);
        
        // Act
        $retrieved = $this->repository->getFundamentals($symbol);
        
        // Assert
        $this->assertIsArray($retrieved);
        $this->assertEquals(2800000000000, $retrieved['market_cap']);
        $this->assertEquals(28.5, $retrieved['pe_ratio']);
    }
    
    public function testGetNonExistentFundamentals(): void
    {
        // Act
        $result = $this->repository->getFundamentals('NONEXISTENT');
        
        // Assert
        $this->assertNull($result);
    }
    
    public function testGetExpiredFundamentals(): void
    {
        // Arrange
        $symbol = 'AAPL';
        $fundamentals = ['market_cap' => 2800000000000];
        $this->repository->storeFundamentals($symbol, $fundamentals);
        
        // Act - check with 0 second max age (expired immediately)
        $result = $this->repository->getFundamentals($symbol, 0);
        
        // Assert
        $this->assertNull($result);
    }
    
    public function testStorePriceHistory(): void
    {
        // Arrange
        $symbol = 'AAPL';
        $priceHistory = [
            ['date' => '2024-01-01', 'open' => 180.0, 'high' => 185.0, 'low' => 179.0, 'close' => 184.0, 'volume' => 50000000],
            ['date' => '2024-01-02', 'open' => 184.0, 'high' => 188.0, 'low' => 183.0, 'close' => 187.0, 'volume' => 55000000]
        ];
        
        // Act
        $result = $this->repository->storePriceHistory($symbol, $priceHistory);
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function testGetPriceHistory(): void
    {
        // Arrange
        $symbol = 'AAPL';
        $priceHistory = [
            ['date' => '2024-01-01', 'close' => 184.0],
            ['date' => '2024-01-02', 'close' => 187.0],
            ['date' => '2024-01-03', 'close' => 185.0]
        ];
        $this->repository->storePriceHistory($symbol, $priceHistory);
        
        // Act
        $retrieved = $this->repository->getPriceHistory($symbol);
        
        // Assert
        $this->assertIsArray($retrieved);
        $this->assertCount(3, $retrieved);
        $this->assertEquals(184.0, $retrieved[0]['close']);
    }
    
    public function testGetPriceHistoryWithDateRange(): void
    {
        // Arrange
        $symbol = 'AAPL';
        $priceHistory = [
            ['date' => '2024-01-01', 'close' => 184.0],
            ['date' => '2024-01-02', 'close' => 187.0],
            ['date' => '2024-01-03', 'close' => 185.0],
            ['date' => '2024-01-04', 'close' => 189.0]
        ];
        $this->repository->storePriceHistory($symbol, $priceHistory);
        
        // Act - get only Jan 2-3
        $retrieved = $this->repository->getPriceHistory($symbol, '2024-01-02', '2024-01-03');
        
        // Assert
        $this->assertIsArray($retrieved);
        $this->assertCount(2, $retrieved);
        $this->assertEquals('2024-01-02', $retrieved[0]['date']);
        $this->assertEquals('2024-01-03', $retrieved[1]['date']);
    }
    
    public function testStoreCurrentPrice(): void
    {
        // Arrange
        $symbol = 'AAPL';
        $priceData = ['price' => 187.50, 'bid' => 187.45, 'ask' => 187.55];
        
        // Act
        $result = $this->repository->storeCurrentPrice($symbol, $priceData);
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function testGetCurrentPrice(): void
    {
        // Arrange
        $symbol = 'AAPL';
        $priceData = ['price' => 187.50, 'bid' => 187.45, 'ask' => 187.55];
        $this->repository->storeCurrentPrice($symbol, $priceData);
        
        // Act
        $retrieved = $this->repository->getCurrentPrice($symbol);
        
        // Assert
        $this->assertIsArray($retrieved);
        $this->assertEquals(187.50, $retrieved['price']);
    }
    
    public function testGetNonExistentCurrentPrice(): void
    {
        // Act
        $result = $this->repository->getCurrentPrice('NONEXISTENT');
        
        // Assert
        $this->assertNull($result);
    }
    
    public function testGetExpiredCurrentPrice(): void
    {
        // Arrange
        $symbol = 'AAPL';
        $priceData = ['price' => 187.50];
        $this->repository->storeCurrentPrice($symbol, $priceData);
        
        // Act - check with 0 second max age
        $result = $this->repository->getCurrentPrice($symbol, 0);
        
        // Assert
        $this->assertNull($result);
    }
    
    public function testGetStaleSymbols(): void
    {
        // Arrange - store multiple symbols
        $this->repository->storeFundamentals('AAPL', ['market_cap' => 2800000000000]);
        $this->repository->storeFundamentals('GOOGL', ['market_cap' => 1700000000000]);
        $this->repository->storeFundamentals('MSFT', ['market_cap' => 2500000000000]);
        
        // Act - get stale symbols with 0 max age (all are stale)
        $staleSymbols = $this->repository->getStaleSymbols(0);
        
        // Assert
        $this->assertIsArray($staleSymbols);
        $this->assertCount(3, $staleSymbols);
        $this->assertContains('AAPL', $staleSymbols);
        $this->assertContains('GOOGL', $staleSymbols);
        $this->assertContains('MSFT', $staleSymbols);
    }
    
    public function testGetStaleSymbolsWithFreshData(): void
    {
        // Arrange
        $this->repository->storeFundamentals('AAPL', ['market_cap' => 2800000000000]);
        
        // Act - check with very large max age (nothing is stale)
        $staleSymbols = $this->repository->getStaleSymbols(86400);
        
        // Assert
        $this->assertIsArray($staleSymbols);
        $this->assertEmpty($staleSymbols);
    }
    
    public function testDeletePrice(): void
    {
        // Arrange
        $symbol = 'AAPL';
        $priceData = ['price' => 187.50];
        $this->repository->storeCurrentPrice($symbol, $priceData);
        
        // Act
        $deleted = $this->repository->deletePrice($symbol);
        
        // Assert
        $this->assertTrue($deleted);
        $this->assertNull($this->repository->getCurrentPrice($symbol));
    }
    
    public function testBulkStoreFundamentals(): void
    {
        // Arrange
        $bulkData = [
            'AAPL' => ['market_cap' => 2800000000000],
            'GOOGL' => ['market_cap' => 1700000000000],
            'MSFT' => ['market_cap' => 2500000000000]
        ];
        
        // Act
        $results = [];
        foreach ($bulkData as $symbol => $fundamentals) {
            $results[$symbol] = $this->repository->storeFundamentals($symbol, $fundamentals);
        }
        
        // Assert
        $this->assertTrue($results['AAPL']);
        $this->assertTrue($results['GOOGL']);
        $this->assertTrue($results['MSFT']);
        
        // Verify retrieval
        $this->assertNotNull($this->repository->getFundamentals('AAPL'));
        $this->assertNotNull($this->repository->getFundamentals('GOOGL'));
        $this->assertNotNull($this->repository->getFundamentals('MSFT'));
    }
}
