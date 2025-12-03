<?php

namespace App\DAOs;

use App\Models\IndexPerformance;
use PDO;

/**
 * Index Performance Data Access Object
 * 
 * Handles database operations for index performance data.
 * 
 * @package App\DAOs
 */
class IndexPerformanceDAO
{
    private PDO $pdo;
    
    /**
     * Constructor
     * 
     * @param PDO|null $pdo Database connection
     */
    public function __construct(?PDO $pdo = null)
    {
        if ($pdo === null) {
            require_once __DIR__ . '/../../config/database.php';
            $this->pdo = getDatabaseConnection();
        } else {
            $this->pdo = $pdo;
        }
    }
    
    /**
     * Save index performance data
     * 
     * @param IndexPerformance $indexPerformance
     * @return bool Success
     */
    public function save(IndexPerformance $indexPerformance): bool
    {
        $sql = "INSERT INTO index_performance 
                (index_symbol, index_name, region, asset_class, value, 
                 change_percent, constituents, market_cap, currency, timestamp, metadata)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            
            $metadata = $indexPerformance->getMetadata();
            $metadataJson = $metadata ? json_encode($metadata) : null;
            
            $result = $stmt->execute([
                $indexPerformance->getIndexSymbol(),
                $indexPerformance->getIndexName(),
                $indexPerformance->getRegion(),
                $indexPerformance->getAssetClass(),
                $indexPerformance->getValue(),
                $indexPerformance->getChangePercent(),
                $indexPerformance->getConstituents(),
                $indexPerformance->getMarketCap(),
                $indexPerformance->getCurrency(),
                $indexPerformance->getTimestamp(),
                $metadataJson
            ]);
            
            if ($result) {
                $indexPerformance->setId((int)$this->pdo->lastInsertId());
            }
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Failed to save index performance: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get index performance for a specific period
     * 
     * @param string $indexSymbol Index symbol
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array|null Index performance data
     */
    public function getIndexPerformance(string $indexSymbol, string $startDate, string $endDate): ?array
    {
        $sql = "SELECT * FROM index_performance 
                WHERE index_symbol = ? 
                AND timestamp BETWEEN ? AND ?
                ORDER BY timestamp DESC
                LIMIT 1";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$indexSymbol, $startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                return null;
            }
            
            if (isset($row['metadata']) && is_string($row['metadata'])) {
                $row['metadata'] = json_decode($row['metadata'], true);
            }
            
            return $row;
        } catch (\PDOException $e) {
            error_log("Failed to get index performance: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get latest index performance
     * 
     * @param string $indexSymbol Index symbol
     * @return IndexPerformance|null
     */
    public function getLatest(string $indexSymbol): ?IndexPerformance
    {
        $sql = "SELECT * FROM index_performance 
                WHERE index_symbol = ? 
                ORDER BY timestamp DESC 
                LIMIT 1";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$indexSymbol]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                return null;
            }
            
            return IndexPerformance::fromDatabaseRow($row);
        } catch (\PDOException $e) {
            error_log("Failed to get latest index performance: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all indexes' latest performance
     * 
     * @return array Array of IndexPerformance objects
     */
    public function getAllLatest(): array
    {
        $sql = "SELECT ip1.* 
                FROM index_performance ip1
                INNER JOIN (
                    SELECT index_symbol, MAX(timestamp) as max_timestamp
                    FROM index_performance
                    GROUP BY index_symbol
                ) ip2 ON ip1.index_symbol = ip2.index_symbol 
                     AND ip1.timestamp = ip2.max_timestamp
                ORDER BY ip1.index_name";
        
        try {
            $stmt = $this->pdo->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $indexes = [];
            foreach ($rows as $row) {
                $indexes[] = IndexPerformance::fromDatabaseRow($row);
            }
            
            return $indexes;
        } catch (\PDOException $e) {
            error_log("Failed to get all latest index performances: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get index performance history
     * 
     * @param string $indexSymbol Index symbol
     * @param int $days Number of days of history
     * @return array Array of IndexPerformance objects
     */
    public function getHistory(string $indexSymbol, int $days = 30): array
    {
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $sql = "SELECT * FROM index_performance 
                WHERE index_symbol = ? 
                AND timestamp >= ?
                ORDER BY timestamp DESC";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$indexSymbol, $startDate]);
            
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $history = [];
            foreach ($rows as $row) {
                $history[] = IndexPerformance::fromDatabaseRow($row);
            }
            
            return $history;
        } catch (\PDOException $e) {
            error_log("Failed to get index performance history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete old index performance data
     * 
     * @param int $daysToKeep Number of days to keep
     * @return int Number of rows deleted
     */
    public function deleteOld(int $daysToKeep = 90): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        
        $sql = "DELETE FROM index_performance WHERE timestamp < ?";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$cutoffDate]);
            
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            error_log("Failed to delete old index performance data: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get index by name
     * 
     * @param string $indexName Index name
     * @return IndexPerformance|null
     */
    public function getByIndexName(string $indexName): ?IndexPerformance
    {
        $sql = "SELECT * FROM index_performance 
                WHERE index_name = ? 
                ORDER BY timestamp DESC 
                LIMIT 1";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$indexName]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                return null;
            }
            
            return IndexPerformance::fromDatabaseRow($row);
        } catch (\PDOException $e) {
            error_log("Failed to get index by name: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update index performance
     * 
     * @param int $id Record ID
     * @param array $data Updated data
     * @return bool Success
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'value',
            'change_percent',
            'constituents',
            'market_cap',
            'metadata'
        ];
        
        $updates = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "{$field} = ?";
                
                if ($field === 'metadata' && is_array($value)) {
                    $values[] = json_encode($value);
                } else {
                    $values[] = $value;
                }
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $values[] = $id;
        
        $sql = "UPDATE index_performance SET " . implode(', ', $updates) . " WHERE id = ?";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
        } catch (\PDOException $e) {
            error_log("Failed to update index performance: " . $e->getMessage());
            return false;
        }
    }
}
