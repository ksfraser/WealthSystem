<?php

namespace App\Repositories;

/**
 * File-based Analysis Repository
 * 
 * Implements AnalysisRepositoryInterface using JSON file storage.
 * Follows Single Responsibility Principle - only handles analysis data persistence.
 * 
 * @see AnalysisRepositoryInterface
 */
class AnalysisRepository implements AnalysisRepositoryInterface
{
    private string $storagePath;
    private const FILE_EXTENSION = '.json';
    
    /**
     * Constructor with dependency injection
     * 
     * @param string $storagePath Path to storage directory
     */
    public function __construct(string $storagePath)
    {
        $this->storagePath = rtrim($storagePath, '/\\');
        
        // Create storage directory if it doesn't exist
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0777, true);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function store(string $symbol, array $analysisData, array $metadata = []): bool
    {
        $data = [
            'symbol' => $symbol,
            'analysis' => $analysisData,
            'metadata' => $metadata,
            'timestamp' => time(),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $filename = $this->getFilename($symbol);
        $json = json_encode($data, JSON_PRETTY_PRINT);
        
        if ($json === false) {
            return false;
        }
        
        return file_put_contents($filename, $json) !== false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function get(string $symbol, ?int $maxAge = 3600): ?array
    {
        $filename = $this->getFilename($symbol);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $content = file_get_contents($filename);
        if ($content === false) {
            return null;
        }
        
        $data = json_decode($content, true);
        if ($data === null) {
            return null;
        }
        
        // Check age if maxAge specified
        if ($maxAge !== null) {
            $age = time() - ($data['timestamp'] ?? 0);
            if ($age >= $maxAge) {
                return null;
            }
        }
        
        // Merge analysis data with metadata for convenience
        $result = $data['analysis'] ?? [];
        if (!empty($data['metadata'])) {
            $result['metadata'] = $data['metadata'];
        }
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isCached(string $symbol, int $maxAge = 3600): bool
    {
        return $this->get($symbol, $maxAge) !== null;
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete(string $symbol): bool
    {
        $filename = $this->getFilename($symbol);
        
        if (!file_exists($filename)) {
            return false;
        }
        
        return unlink($filename);
    }
    
    /**
     * {@inheritdoc}
     */
    public function deleteExpired(int $maxAge = 86400): int
    {
        $files = glob($this->storagePath . '/*' . self::FILE_EXTENSION);
        $deleted = 0;
        $now = time();
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }
            
            $data = json_decode($content, true);
            if ($data === null) {
                continue;
            }
            
            $age = $now - ($data['timestamp'] ?? 0);
            if ($age >= $maxAge) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getHistory(string $symbol, int $limit = 10): array
    {
        // For file-based storage, we only keep latest version
        // In a database implementation, this would query multiple versions
        $filename = $this->getFilename($symbol);
        
        if (!file_exists($filename)) {
            return [];
        }
        
        $content = file_get_contents($filename);
        if ($content === false) {
            return [];
        }
        
        $data = json_decode($content, true);
        if ($data === null) {
            return [];
        }
        
        // Return full data wrapper for history (includes timestamp)
        return [$data];
    }
    
    /**
     * Get filename for a symbol
     * 
     * @param string $symbol Stock ticker symbol
     * @return string Full path to file
     */
    private function getFilename(string $symbol): string
    {
        // Sanitize symbol for filesystem
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $symbol);
        return $this->storagePath . '/' . strtolower($safe) . self::FILE_EXTENSION;
    }
}
