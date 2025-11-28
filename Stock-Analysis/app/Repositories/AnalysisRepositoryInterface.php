<?php

namespace App\Repositories;

/**
 * Analysis Repository Interface
 * 
 * Handles persistence and retrieval of stock analysis results.
 * Follows Repository Pattern to abstract data storage.
 */
interface AnalysisRepositoryInterface
{
    /**
     * Store analysis result
     * 
     * @param string $symbol Stock ticker symbol
     * @param array $analysisData Analysis results
     * @param array $metadata Optional metadata (user_id, timestamp, etc.)
     * @return bool Success status
     */
    public function store(string $symbol, array $analysisData, array $metadata = []): bool;
    
    /**
     * Retrieve cached analysis
     * 
     * @param string $symbol Stock ticker symbol
     * @param int|null $maxAge Maximum age in seconds (null = any age)
     * @return array|null Analysis data or null if not found/expired
     */
    public function get(string $symbol, ?int $maxAge = 3600): ?array;
    
    /**
     * Check if cached analysis exists and is fresh
     * 
     * @param string $symbol Stock ticker symbol
     * @param int $maxAge Maximum age in seconds
     * @return bool True if fresh cache exists
     */
    public function isCached(string $symbol, int $maxAge = 3600): bool;
    
    /**
     * Delete cached analysis
     * 
     * @param string $symbol Stock ticker symbol
     * @return bool Success status
     */
    public function delete(string $symbol): bool;
    
    /**
     * Delete all expired cache entries
     * 
     * @param int $maxAge Age threshold in seconds
     * @return int Number of entries deleted
     */
    public function deleteExpired(int $maxAge = 86400): int;
    
    /**
     * Get analysis history for a symbol
     * 
     * @param string $symbol Stock ticker symbol
     * @param int $limit Maximum number of results
     * @return array Array of historical analysis results
     */
    public function getHistory(string $symbol, int $limit = 10): array;
}
