<?php

namespace App\Repositories;

use PDO;
use PDOException;

/**
 * Strategy Parameters Repository
 * 
 * SQLite-based implementation for managing strategy configuration parameters.
 * Handles CRUD operations, type conversions, and metadata management.
 * 
 * @package App\Repositories
 */
class StrategyParametersRepository implements StrategyParametersRepositoryInterface
{
    private PDO $pdo;
    private string $tableName = 'strategy_parameters';

    public function __construct(string $databasePath)
    {
        $this->pdo = new PDO("sqlite:$databasePath");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initializeDatabase();
    }

    /**
     * Initialize database (create table if not exists)
     */
    private function initializeDatabase(): void
    {
        // Check if table already exists
        $result = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$this->tableName}'");
        if ($result && $result->fetch()) {
            // Table already exists, skip initialization
            return;
        }
        
        // Table doesn't exist, run migration
        $migrationFile = __DIR__ . '/../../database/migrations/create_strategy_parameters_table.sql';
        
        if (file_exists($migrationFile)) {
            $sql = file_get_contents($migrationFile);
            $this->pdo->exec($sql);
        }
    }

    public function getStrategyParameters(string $strategyName): array
    {
        $stmt = $this->pdo->prepare("
            SELECT parameter_key, parameter_value, parameter_type
            FROM {$this->tableName}
            WHERE strategy_name = :strategy_name AND is_active = 1
            ORDER BY display_order
        ");
        
        $stmt->execute(['strategy_name' => $strategyName]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $parameters = [];
        foreach ($results as $row) {
            $parameters[$row['parameter_key']] = $this->castValue(
                $row['parameter_value'],
                $row['parameter_type']
            );
        }
        
        return $parameters;
    }

    public function getParameter(string $strategyName, string $parameterKey, $default = null)
    {
        $stmt = $this->pdo->prepare("
            SELECT parameter_value, parameter_type
            FROM {$this->tableName}
            WHERE strategy_name = :strategy_name 
            AND parameter_key = :parameter_key
            AND is_active = 1
        ");
        
        $stmt->execute([
            'strategy_name' => $strategyName,
            'parameter_key' => $parameterKey
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return $default;
        }
        
        return $this->castValue($result['parameter_value'], $result['parameter_type']);
    }

    public function setParameter(string $strategyName, string $parameterKey, $value): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE {$this->tableName}
                SET parameter_value = :value,
                    updated_at = CURRENT_TIMESTAMP
                WHERE strategy_name = :strategy_name
                AND parameter_key = :parameter_key
            ");
            
            return $stmt->execute([
                'strategy_name' => $strategyName,
                'parameter_key' => $parameterKey,
                'value' => $this->normalizeValue($value)
            ]);
        } catch (PDOException $e) {
            error_log("Failed to set parameter: " . $e->getMessage());
            return false;
        }
    }

    public function setStrategyParameters(string $strategyName, array $parameters): bool
    {
        try {
            $this->pdo->beginTransaction();
            
            foreach ($parameters as $key => $value) {
                $this->setParameter($strategyName, $key, $value);
            }
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Failed to set strategy parameters: " . $e->getMessage());
            return false;
        }
    }

    public function getParametersWithMetadata(string $strategyName): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                id,
                parameter_key,
                parameter_value,
                parameter_type,
                display_name,
                description,
                category,
                min_value,
                max_value,
                display_order
            FROM {$this->tableName}
            WHERE strategy_name = :strategy_name AND is_active = 1
            ORDER BY category, display_order
        ");
        
        $stmt->execute(['strategy_name' => $strategyName]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableStrategies(): array
    {
        $stmt = $this->pdo->query("
            SELECT DISTINCT strategy_name
            FROM {$this->tableName}
            WHERE is_active = 1
            ORDER BY strategy_name
        ");
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function resetToDefaults(string $strategyName): bool
    {
        // For now, this would require re-running the migration
        // In a production system, you'd store original defaults
        return false;
    }

    public function getParametersByCategory(string $strategyName): array
    {
        $parameters = $this->getParametersWithMetadata($strategyName);
        
        $grouped = [];
        foreach ($parameters as $param) {
            $category = $param['category'] ?? 'General';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $param;
        }
        
        return $grouped;
    }

    public function exportParameters(string $strategyName): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM {$this->tableName}
            WHERE strategy_name = :strategy_name
            ORDER BY display_order
        ");
        
        $stmt->execute(['strategy_name' => $strategyName]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function importParameters(string $strategyName, array $parameters): bool
    {
        try {
            $this->pdo->beginTransaction();
            
            foreach ($parameters as $param) {
                $stmt = $this->pdo->prepare("
                    INSERT OR REPLACE INTO {$this->tableName}
                    (strategy_name, parameter_key, parameter_value, parameter_type,
                     display_name, description, category, min_value, max_value,
                     display_order, is_active)
                    VALUES 
                    (:strategy_name, :parameter_key, :parameter_value, :parameter_type,
                     :display_name, :description, :category, :min_value, :max_value,
                     :display_order, :is_active)
                ");
                
                $stmt->execute([
                    'strategy_name' => $strategyName,
                    'parameter_key' => $param['parameter_key'],
                    'parameter_value' => $param['parameter_value'],
                    'parameter_type' => $param['parameter_type'],
                    'display_name' => $param['display_name'],
                    'description' => $param['description'] ?? null,
                    'category' => $param['category'] ?? null,
                    'min_value' => $param['min_value'] ?? null,
                    'max_value' => $param['max_value'] ?? null,
                    'display_order' => $param['display_order'] ?? 0,
                    'is_active' => $param['is_active'] ?? 1
                ]);
            }
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Failed to import parameters: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cast parameter value to appropriate type
     */
    private function castValue($value, string $type)
    {
        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            
            case 'float':
            case 'double':
            case 'decimal':
                return (float) $value;
            
            case 'bool':
            case 'boolean':
                return (bool) $value;
            
            case 'string':
            default:
                return (string) $value;
        }
    }

    /**
     * Normalize value for storage
     */
    private function normalizeValue($value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        
        return (string) $value;
    }
}
