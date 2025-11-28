<?php

namespace App\Repositories;

/**
 * File-based Market Data Repository
 * 
 * Implements MarketDataRepositoryInterface using JSON file storage.
 * Handles fundamentals, price history, and current prices separately.
 * Follows Single Responsibility Principle.
 */
class MarketDataRepository implements MarketDataRepositoryInterface
{
    private string $storagePath;
    private const FUNDAMENTALS_SUFFIX = '_fundamentals.json';
    private const PRICE_HISTORY_SUFFIX = '_prices.json';
    private const CURRENT_PRICE_SUFFIX = '_current.json';
    
    /**
     * Constructor with dependency injection
     * 
     * @param string $storagePath Path to storage directory
     */
    public function __construct(string $storagePath)
    {
        $this->storagePath = rtrim($storagePath, '/\\');
        
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0777, true);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function storeFundamentals(string $symbol, array $fundamentals): bool
    {
        $data = [
            'symbol' => $symbol,
            'fundamentals' => $fundamentals,
            'timestamp' => time(),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $filename = $this->getFilename($symbol, self::FUNDAMENTALS_SUFFIX);
        $json = json_encode($data, JSON_PRETTY_PRINT);
        
        if ($json === false) {
            return false;
        }
        
        return file_put_contents($filename, $json) !== false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFundamentals(string $symbol, ?int $maxAge = 3600): ?array
    {
        $filename = $this->getFilename($symbol, self::FUNDAMENTALS_SUFFIX);
        
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
        
        return $data['fundamentals'] ?? null;
    }
    
    /**
     * {@inheritdoc}
     */
    public function storePriceHistory(string $symbol, array $priceHistory): bool
    {
        $data = [
            'symbol' => $symbol,
            'prices' => $priceHistory,
            'timestamp' => time(),
            'updated_at' => date('Y-m-d H:i:s'),
            'count' => count($priceHistory)
        ];
        
        $filename = $this->getFilename($symbol, self::PRICE_HISTORY_SUFFIX);
        $json = json_encode($data, JSON_PRETTY_PRINT);
        
        if ($json === false) {
            return false;
        }
        
        return file_put_contents($filename, $json) !== false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPriceHistory(
        string $symbol, 
        ?string $startDate = null, 
        ?string $endDate = null
    ): array {
        $filename = $this->getFilename($symbol, self::PRICE_HISTORY_SUFFIX);
        
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
        
        $prices = $data['prices'] ?? [];
        
        // Filter by date range if specified
        if ($startDate !== null || $endDate !== null) {
            $prices = array_filter($prices, function($priceData) use ($startDate, $endDate) {
                $date = $priceData['date'] ?? '';
                
                if ($startDate !== null && $date < $startDate) {
                    return false;
                }
                
                if ($endDate !== null && $date > $endDate) {
                    return false;
                }
                
                return true;
            });
            
            // Re-index array to maintain sequential keys
            $prices = array_values($prices);
        }
        
        return $prices;
    }
    
    /**
     * {@inheritdoc}
     */
    public function storeCurrentPrice(string $symbol, array $priceData): bool
    {
        $data = [
            'symbol' => $symbol,
            'price_data' => $priceData,
            'timestamp' => time(),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $filename = $this->getFilename($symbol, self::CURRENT_PRICE_SUFFIX);
        $json = json_encode($data, JSON_PRETTY_PRINT);
        
        if ($json === false) {
            return false;
        }
        
        return file_put_contents($filename, $json) !== false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getCurrentPrice(string $symbol, ?int $maxAge = 60): ?array
    {
        $filename = $this->getFilename($symbol, self::CURRENT_PRICE_SUFFIX);
        
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
        
        return $data['price_data'] ?? null;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getStaleSymbols(int $maxAge = 3600, int $limit = 100): array
    {
        $files = glob($this->storagePath . '/*' . self::FUNDAMENTALS_SUFFIX);
        $staleSymbols = [];
        $now = time();
        $count = 0;
        
        foreach ($files as $file) {
            if ($count >= $limit) {
                break;
            }
            
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
                $staleSymbols[] = $data['symbol'] ?? '';
                $count++;
            }
        }
        
        return array_filter($staleSymbols);
    }
    
    /**
     * {@inheritdoc}
     */
    public function deletePrice(string $symbol): bool
    {
        $filename = $this->getFilename($symbol, self::CURRENT_PRICE_SUFFIX);
        
        if (!file_exists($filename)) {
            return false;
        }
        
        return unlink($filename);
    }
    
    /**
     * Get filename for a symbol and type
     * 
     * @param string $symbol Stock ticker symbol
     * @param string $suffix File suffix
     * @return string Full path to file
     */
    private function getFilename(string $symbol, string $suffix): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $symbol);
        return $this->storagePath . '/' . strtolower($safe) . $suffix;
    }
}
