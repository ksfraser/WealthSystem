<?php

namespace App\DAOs;

use App\Models\SectorPerformance;
use PDO;

/**
 * Sector Performance Data Access Object
 * 
 * Handles database operations for sector performance data.
 * 
 * @package App\DAOs
 */
class SectorPerformanceDAO
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
            // Use default database connection
            require_once __DIR__ . '/../../config/database.php';
            $this->pdo = getDatabaseConnection();
        } else {
            $this->pdo = $pdo;
        }
    }
    
    /**
     * Save sector performance data
     * 
     * @param SectorPerformance $sectorPerformance
     * @return bool Success
     */
    public function save(SectorPerformance $sectorPerformance): bool
    {
        $sql = "INSERT INTO sector_performance 
                (sector_code, sector_name, classification, performance_value, 
                 change_percent, market_cap_weight, timestamp, metadata)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            
            $metadata = $sectorPerformance->getMetadata();
            $metadataJson = $metadata ? json_encode($metadata) : null;
            
            $result = $stmt->execute([
                $sectorPerformance->getSectorCode(),
                $sectorPerformance->getSectorName(),
                $sectorPerformance->getClassification(),
                $sectorPerformance->getPerformanceValue(),
                $sectorPerformance->getChangePercent(),
                $sectorPerformance->getMarketCapWeight(),
                $sectorPerformance->getTimestamp(),
                $metadataJson
            ]);
            
            if ($result) {
                $sectorPerformance->setId((int)$this->pdo->lastInsertId());
            }
            
            return $result;
        } catch (\PDOException $e) {
            error_log("Failed to save sector performance: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get sector performance for a specific period
     * 
     * @param string $sectorName Sector name
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array|null Sector performance data
     */
    public function getSectorPerformance(string $sectorName, string $startDate, string $endDate): ?array
    {
        $sql = "SELECT * FROM sector_performance 
                WHERE sector_name = ? 
                AND timestamp BETWEEN ? AND ?
                ORDER BY timestamp DESC
                LIMIT 1";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$sectorName, $startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                return null;
            }
            
            // Decode metadata if present
            if (isset($row['metadata']) && is_string($row['metadata'])) {
                $row['metadata'] = json_decode($row['metadata'], true);
            }
            
            return $row;
        } catch (\PDOException $e) {
            error_log("Failed to get sector performance: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get latest sector performance
     * 
     * @param string $sectorName Sector name
     * @return SectorPerformance|null
     */
    public function getLatest(string $sectorName): ?SectorPerformance
    {
        $sql = "SELECT * FROM sector_performance 
                WHERE sector_name = ? 
                ORDER BY timestamp DESC 
                LIMIT 1";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$sectorName]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                return null;
            }
            
            return SectorPerformance::fromDatabaseRow($row);
        } catch (\PDOException $e) {
            error_log("Failed to get latest sector performance: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all sectors' latest performance
     * 
     * @return array Array of SectorPerformance objects
     */
    public function getAllLatest(): array
    {
        $sql = "SELECT sp1.* 
                FROM sector_performance sp1
                INNER JOIN (
                    SELECT sector_name, MAX(timestamp) as max_timestamp
                    FROM sector_performance
                    GROUP BY sector_name
                ) sp2 ON sp1.sector_name = sp2.sector_name 
                     AND sp1.timestamp = sp2.max_timestamp
                ORDER BY sp1.sector_name";
        
        try {
            $stmt = $this->pdo->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sectors = [];
            foreach ($rows as $row) {
                $sectors[] = SectorPerformance::fromDatabaseRow($row);
            }
            
            return $sectors;
        } catch (\PDOException $e) {
            error_log("Failed to get all latest sector performances: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get sector performance history
     * 
     * @param string $sectorName Sector name
     * @param int $days Number of days of history
     * @return array Array of SectorPerformance objects
     */
    public function getHistory(string $sectorName, int $days = 30): array
    {
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $sql = "SELECT * FROM sector_performance 
                WHERE sector_name = ? 
                AND timestamp >= ?
                ORDER BY timestamp DESC";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$sectorName, $startDate]);
            
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $history = [];
            foreach ($rows as $row) {
                $history[] = SectorPerformance::fromDatabaseRow($row);
            }
            
            return $history;
        } catch (\PDOException $e) {
            error_log("Failed to get sector performance history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete old sector performance data
     * 
     * @param int $daysToKeep Number of days to keep
     * @return int Number of rows deleted
     */
    public function deleteOld(int $daysToKeep = 90): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        
        $sql = "DELETE FROM sector_performance WHERE timestamp < ?";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$cutoffDate]);
            
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            error_log("Failed to delete old sector performance data: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get sector by code
     * 
     * @param string $sectorCode Sector code
     * @return SectorPerformance|null
     */
    public function getBySectorCode(string $sectorCode): ?SectorPerformance
    {
        $sql = "SELECT * FROM sector_performance 
                WHERE sector_code = ? 
                ORDER BY timestamp DESC 
                LIMIT 1";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$sectorCode]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                return null;
            }
            
            return SectorPerformance::fromDatabaseRow($row);
        } catch (\PDOException $e) {
            error_log("Failed to get sector by code: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update sector performance
     * 
     * @param int $id Record ID
     * @param array $data Updated data
     * @return bool Success
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'performance_value',
            'change_percent',
            'market_cap_weight',
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
        
        $sql = "UPDATE sector_performance SET " . implode(', ', $updates) . " WHERE id = ?";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
        } catch (\PDOException $e) {
            error_log("Failed to update sector performance: " . $e->getMessage());
            return false;
        }
    }
}
