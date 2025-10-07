<?php
/**
 * MySQL implementation of the database repository.
 */

namespace Ksfraser\Finance\Repositories;

use PDO;

class MySQLDatabaseRepository extends AbstractDatabaseRepository
{
    /**
     * {@inheritdoc}
     */
    public function createSymbolTechnicalTable(string $symbol): bool
    {
        $table = $this->getSymbolTechnicalTableName($symbol);
        $sql = "CREATE TABLE IF NOT EXISTS `$table` (
            id INTEGER PRIMARY KEY AUTO_INCREMENT,
            date DATE NOT NULL,
            rsi_14 DECIMAL(10,6),
            sma_20 DECIMAL(10,6),
            ema_20 DECIMAL(10,6),
            macd DECIMAL(10,6),
            macd_signal DECIMAL(10,6),
            macd_hist DECIMAL(10,6),
            bbands_upper DECIMAL(10,6),
            bbands_middle DECIMAL(10,6),
            bbands_lower DECIMAL(10,6),
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(date)
        );";
        try {
            $this->connection->exec($sql);
            return true;
        } catch (\PDOException $e) {
            error_log("Error creating technical table for $symbol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function saveSymbolTechnicalValues(string $symbol, array $values): bool
    {
        $table = $this->getSymbolTechnicalTableName($symbol);
        $columns = array_keys($values);
        $placeholders = array_map(function($col) { return ':' . $col; }, $columns);
        $updates = array_map(function($col) { return "$col = VALUES($col)"; }, array_diff($columns, ['date']));
        $sql = "INSERT INTO `$table` (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")
                ON DUPLICATE KEY UPDATE " . implode(',', $updates);
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($values);
        } catch (\PDOException $e) {
            error_log("Error upserting technical values for $symbol: " . $e->getMessage());
            return false;
        }
    }
}
