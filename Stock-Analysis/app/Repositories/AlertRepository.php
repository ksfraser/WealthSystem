<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Alert;
use App\Database\Connection;
use PDO;

/**
 * Alert Repository
 * 
 * Manages database persistence for alerts with full CRUD operations,
 * filtering capabilities, and bulk operations.
 * 
 * Features:
 * - CRUD operations (Create, Read, Update, Delete)
 * - Find by user ID, symbol, active status
 * - Bulk delete operations
 * - Activation/deactivation
 * - Count queries
 * - Automatic timestamp management
 * 
 * @package App\Repositories
 */
class AlertRepository
{
    private Connection $connection;
    private string $table = 'alerts';
    
    /**
     * Create new alert repository
     *
     * @param Connection $connection Database connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    
    /**
     * Save alert (create or update)
     *
     * @param Alert $alert Alert to save
     * @return Alert Saved alert with ID
     */
    public function save(Alert $alert): Alert
    {
        if ($alert->getId() === null) {
            return $this->insert($alert);
        }
        
        return $this->update($alert);
    }
    
    /**
     * Insert new alert
     *
     * @param Alert $alert Alert to insert
     * @return Alert Inserted alert with ID
     */
    private function insert(Alert $alert): Alert
    {
        $pdo = $this->connection->getPDO();
        
        $sql = "INSERT INTO {$this->table} 
                (user_id, name, symbol, condition_type, threshold, email, throttle_minutes, active, created_at) 
                VALUES (:user_id, :name, :symbol, :condition_type, :threshold, :email, :throttle_minutes, :active, :created_at)";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            'user_id' => $alert->getUserId(),
            'name' => $alert->getName(),
            'symbol' => $alert->getSymbol(),
            'condition_type' => $alert->getConditionType(),
            'threshold' => $alert->getThreshold(),
            'email' => $alert->getEmail(),
            'throttle_minutes' => $alert->getThrottleMinutes(),
            'active' => $alert->isActive() ? 1 : 0,
            'created_at' => $alert->getCreatedAt()->format('Y-m-d H:i:s')
        ]);
        
        $alert->setId((int) $pdo->lastInsertId());
        
        return $alert;
    }
    
    /**
     * Update existing alert
     *
     * @param Alert $alert Alert to update
     * @return Alert Updated alert
     */
    private function update(Alert $alert): Alert
    {
        $pdo = $this->connection->getPDO();
        
        $alert->setUpdatedAt(new \DateTime());
        
        $sql = "UPDATE {$this->table} 
                SET user_id = :user_id,
                    name = :name,
                    symbol = :symbol,
                    condition_type = :condition_type,
                    threshold = :threshold,
                    email = :email,
                    throttle_minutes = :throttle_minutes,
                    active = :active,
                    updated_at = :updated_at
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            'id' => $alert->getId(),
            'user_id' => $alert->getUserId(),
            'name' => $alert->getName(),
            'symbol' => $alert->getSymbol(),
            'condition_type' => $alert->getConditionType(),
            'threshold' => $alert->getThreshold(),
            'email' => $alert->getEmail(),
            'throttle_minutes' => $alert->getThrottleMinutes(),
            'active' => $alert->isActive() ? 1 : 0,
            'updated_at' => $alert->getUpdatedAt()->format('Y-m-d H:i:s')
        ]);
        
        return $alert;
    }
    
    /**
     * Find alert by ID
     *
     * @param int $id Alert ID
     * @return Alert|null Alert or null if not found
     */
    public function findById(int $id): ?Alert
    {
        $pdo = $this->connection->getPDO();
        
        $stmt = $pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data === false) {
            return null;
        }
        
        return $this->hydrate($data);
    }
    
    /**
     * Find all alerts
     *
     * @return array<int, Alert> All alerts
     */
    public function findAll(): array
    {
        $pdo = $this->connection->getPDO();
        
        $stmt = $pdo->query("SELECT * FROM {$this->table} ORDER BY id");
        
        $alerts = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $alerts[] = $this->hydrate($data);
        }
        
        return $alerts;
    }
    
    /**
     * Find alerts by user ID
     *
     * @param int $userId User ID
     * @return array<int, Alert> User's alerts
     */
    public function findByUserId(int $userId): array
    {
        $pdo = $this->connection->getPDO();
        
        $stmt = $pdo->prepare("SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY id");
        $stmt->execute(['user_id' => $userId]);
        
        $alerts = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $alerts[] = $this->hydrate($data);
        }
        
        return $alerts;
    }
    
    /**
     * Find alerts by symbol
     *
     * @param string $symbol Stock symbol
     * @return array<int, Alert> Alerts for symbol
     */
    public function findBySymbol(string $symbol): array
    {
        $pdo = $this->connection->getPDO();
        
        $stmt = $pdo->prepare("SELECT * FROM {$this->table} WHERE symbol = :symbol ORDER BY id");
        $stmt->execute(['symbol' => $symbol]);
        
        $alerts = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $alerts[] = $this->hydrate($data);
        }
        
        return $alerts;
    }
    
    /**
     * Find active alerts
     *
     * @return array<int, Alert> Active alerts
     */
    public function findActive(): array
    {
        $pdo = $this->connection->getPDO();
        
        $stmt = $pdo->query("SELECT * FROM {$this->table} WHERE active = 1 ORDER BY id");
        
        $alerts = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $alerts[] = $this->hydrate($data);
        }
        
        return $alerts;
    }
    
    /**
     * Delete alert by ID
     *
     * @param int $id Alert ID
     * @return bool True if deleted, false if not found
     */
    public function delete(int $id): bool
    {
        $pdo = $this->connection->getPDO();
        
        $stmt = $pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Delete alerts by user ID
     *
     * @param int $userId User ID
     * @return int Number of alerts deleted
     */
    public function deleteByUserId(int $userId): int
    {
        $pdo = $this->connection->getPDO();
        
        $stmt = $pdo->prepare("DELETE FROM {$this->table} WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        
        return $stmt->rowCount();
    }
    
    /**
     * Activate alert
     *
     * @param int $id Alert ID
     * @return void
     */
    public function activate(int $id): void
    {
        $pdo = $this->connection->getPDO();
        
        $stmt = $pdo->prepare("UPDATE {$this->table} SET active = 1 WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
    
    /**
     * Deactivate alert
     *
     * @param int $id Alert ID
     * @return void
     */
    public function deactivate(int $id): void
    {
        $pdo = $this->connection->getPDO();
        
        $stmt = $pdo->prepare("UPDATE {$this->table} SET active = 0 WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
    
    /**
     * Count all alerts
     *
     * @return int Total number of alerts
     */
    public function count(): int
    {
        $pdo = $this->connection->getPDO();
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$this->table}");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int) $result['count'];
    }
    
    /**
     * Hydrate alert from database row
     *
     * @param array<string, mixed> $data Database row
     * @return Alert Alert instance
     */
    private function hydrate(array $data): Alert
    {
        // Convert active from integer to boolean
        $data['active'] = (bool) $data['active'];
        
        return new Alert($data);
    }
}
