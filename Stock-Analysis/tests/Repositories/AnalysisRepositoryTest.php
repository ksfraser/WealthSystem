<?php

namespace Tests\Repositories;

use PHPUnit\Framework\TestCase;
use App\Repositories\AnalysisRepositoryInterface;
use App\Repositories\AnalysisRepository;

/**
 * Analysis Repository Tests
 * 
 * TDD approach - tests written first to define expected behavior.
 * Covers CRUD operations, caching, and expiration logic.
 */
class AnalysisRepositoryTest extends TestCase
{
    private AnalysisRepositoryInterface $repository;
    private string $testDataPath;
    
    protected function setUp(): void
    {
        $this->testDataPath = sys_get_temp_dir() . '/analysis_test_' . uniqid();
        mkdir($this->testDataPath, 0777, true);
        
        $this->repository = new AnalysisRepository($this->testDataPath);
    }
    
    protected function tearDown(): void
    {
        // Clean up test data
        if (is_dir($this->testDataPath)) {
            $files = glob($this->testDataPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testDataPath);
        }
    }
    
    public function testStoreAnalysisData(): void
    {
        // Arrange
        $symbol = 'AAPL';
        $analysisData = [
            'overall_score' => 75.0,
            'recommendation' => 'BUY',
            'risk_level' => 'MEDIUM'
        ];
        
        // Act
        $result = $this->repository->store($symbol, $analysisData);
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function testRetrieveCachedAnalysis(): void
    {
        // Arrange
        $symbol = 'AAPL';
        $analysisData = [
            'overall_score' => 75.0,
            'recommendation' => 'BUY',
            'risk_level' => 'MEDIUM'
        ];
        $this->repository->store($symbol, $analysisData);
        
        // Act
        $retrieved = $this->repository->get($symbol);
        
        // Assert
        $this->assertIsArray($retrieved);
        $this->assertEquals(75.0, $retrieved['overall_score']);
        $this->assertEquals('BUY', $retrieved['recommendation']);
    }
    
    public function testGetNonExistentAnalysis(): void
    {
        // Act
        $result = $this->repository->get('NONEXISTENT');
        
        // Assert
        $this->assertNull($result);
    }
    
    public function testIsCachedReturnsTrueForFreshData(): void
    {
        // Arrange
        $symbol = 'AAPL';
        $analysisData = ['overall_score' => 75.0];
        $this->repository->store($symbol, $analysisData);
        
        // Act
        $isCached = $this->repository->isCached($symbol, 3600);
        
        // Assert
        $this->assertTrue($isCached);
    }
    
    public function testIsCachedReturnsFalseForExpiredData(): void
    {
        // Arrange
        $symbol = 'AAPL';
        $analysisData = ['overall_score' => 75.0];
        $this->repository->store($symbol, $analysisData);
        
        // Act - check with 0 second max age (everything is expired)
        $isCached = $this->repository->isCached($symbol, 0);
        
        // Assert
        $this->assertFalse($isCached);
    }
    
    public function testDeleteAnalysis(): void
    {
        // Arrange
        $symbol = 'AAPL';
        $analysisData = ['overall_score' => 75.0];
        $this->repository->store($symbol, $analysisData);
        
        // Act
        $deleted = $this->repository->delete($symbol);
        
        // Assert
        $this->assertTrue($deleted);
        $this->assertNull($this->repository->get($symbol));
    }
    
    public function testDeleteNonExistentAnalysis(): void
    {
        // Act
        $result = $this->repository->delete('NONEXISTENT');
        
        // Assert
        $this->assertFalse($result);
    }
    
    public function testDeleteExpiredEntries(): void
    {
        // Arrange - store multiple entries
        $this->repository->store('AAPL', ['score' => 75]);
        $this->repository->store('GOOGL', ['score' => 80]);
        $this->repository->store('MSFT', ['score' => 85]);
        
        // Act - delete with 0 max age (all are expired)
        $deleted = $this->repository->deleteExpired(0);
        
        // Assert
        $this->assertEquals(3, $deleted);
        $this->assertNull($this->repository->get('AAPL'));
        $this->assertNull($this->repository->get('GOOGL'));
        $this->assertNull($this->repository->get('MSFT'));
    }
    
    public function testGetAnalysisHistory(): void
    {
        // Arrange - store multiple analyses for same symbol
        $symbol = 'AAPL';
        $this->repository->store($symbol, ['score' => 75, 'timestamp' => time() - 3600]);
        sleep(1); // Ensure different timestamps
        $this->repository->store($symbol, ['score' => 80, 'timestamp' => time() - 1800]);
        sleep(1);
        $this->repository->store($symbol, ['score' => 85, 'timestamp' => time()]);
        
        // Act
        $history = $this->repository->getHistory($symbol, 10);
        
        // Assert
        $this->assertIsArray($history);
        $this->assertGreaterThan(0, count($history));
    }
    
    public function testStoreWithMetadata(): void
    {
        // Arrange
        $symbol = 'AAPL';
        $analysisData = ['overall_score' => 75.0];
        $metadata = ['user_id' => 123, 'analysis_type' => 'full'];
        
        // Act
        $result = $this->repository->store($symbol, $analysisData, $metadata);
        
        // Assert
        $this->assertTrue($result);
        
        $retrieved = $this->repository->get($symbol);
        $this->assertIsArray($retrieved);
        $this->assertArrayHasKey('metadata', $retrieved);
        $this->assertEquals(123, $retrieved['metadata']['user_id']);
    }
    
    public function testGetWithMaxAgeFilter(): void
    {
        // Arrange
        $symbol = 'AAPL';
        $analysisData = ['overall_score' => 75.0];
        $this->repository->store($symbol, $analysisData);
        
        // Act - get with very short max age
        sleep(1);
        $result = $this->repository->get($symbol, 0);
        
        // Assert - should be null because it's expired
        $this->assertNull($result);
    }
}
