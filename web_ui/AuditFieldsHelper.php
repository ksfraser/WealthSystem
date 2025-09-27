<?php
/**
 * Audit Fields Helper
 * 
 * Provides consistent handling of audit fields (created_at, last_updated, created_by, updated_by)
 * across all database operations.
 */

class AuditFieldsHelper {
    
    /**
     * Get current user ID for audit purposes
     * 
     * @return int|null Current user ID or null if not logged in
     */
    public static function getCurrentUserId(): ?int {
        // Try to get from session
        $sessionManager = SessionManager::getInstance();
        $userData = $sessionManager->get('user_auth');
        
        if ($userData && isset($userData['id'])) {
            return (int) $userData['id'];
        }
        
        return null;
    }
    
    /**
     * Get INSERT SQL with audit fields
     * 
     * @param string $table Table name
     * @param array $fields Regular fields to insert
     * @return array [sql, parameters] SQL string and parameter values
     */
    public static function getInsertSqlWithAudit(string $table, array $fields): array {
        $currentUserId = self::getCurrentUserId();
        
        $fieldNames = array_keys($fields);
        $fieldNames[] = 'created_at';
        $fieldNames[] = 'created_by';
        
        $placeholders = array_fill(0, count($fields), '?');
        $placeholders[] = 'NOW()';
        $placeholders[] = '?';
        
        $sql = "INSERT INTO `$table` (`" . implode('`, `', $fieldNames) . "`) VALUES (" . implode(', ', $placeholders) . ")";
        
        $parameters = array_values($fields);
        $parameters[] = $currentUserId;
        
        return [$sql, $parameters];
    }
    
    /**
     * Get UPDATE SQL with audit fields
     * 
     * @param string $table Table name
     * @param array $fields Fields to update (field => value)
     * @param string $whereClause WHERE clause (without WHERE keyword)
     * @param array $whereParams Parameters for WHERE clause
     * @return array [sql, parameters] SQL string and parameter values
     */
    public static function getUpdateSqlWithAudit(string $table, array $fields, string $whereClause, array $whereParams = []): array {
        $currentUserId = self::getCurrentUserId();
        
        $setParts = [];
        $parameters = [];
        
        // Add regular fields
        foreach ($fields as $field => $value) {
            $setParts[] = "`$field` = ?";
            $parameters[] = $value;
        }
        
        // Add audit fields
        $setParts[] = "last_updated = NOW()";
        $setParts[] = "updated_by = ?";
        $parameters[] = $currentUserId;
        
        $sql = "UPDATE `$table` SET " . implode(', ', $setParts) . " WHERE " . $whereClause;
        
        // Add WHERE parameters
        $parameters = array_merge($parameters, $whereParams);
        
        return [$sql, $parameters];
    }
    
    /**
     * Get CREATE TABLE SQL with audit fields
     * 
     * @param string $tableName Table name
     * @param array $columns Column definitions (name => definition)
     * @param array $constraints Additional constraints (indexes, foreign keys, etc.)
     * @return string Complete CREATE TABLE SQL
     */
    public static function getCreateTableSqlWithAudit(string $tableName, array $columns, array $constraints = []): string {
        // Add audit fields to columns
        $columns['created_at'] = 'DATETIME DEFAULT CURRENT_TIMESTAMP';
        $columns['last_updated'] = 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        $columns['created_by'] = 'INT(11) NULL';
        $columns['updated_by'] = 'INT(11) NULL';
        
        // Add foreign key constraints for audit fields
        $constraints[] = 'FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE';
        $constraints[] = 'FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE';
        
        $columnSql = [];
        foreach ($columns as $name => $definition) {
            $columnSql[] = "`$name` $definition";
        }
        
        $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (\n";
        $sql .= "  " . implode(",\n  ", $columnSql);
        
        if (!empty($constraints)) {
            $sql .= ",\n  " . implode(",\n  ", $constraints);
        }
        
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        return $sql;
    }
    
    /**
     * Format audit information for display
     * 
     * @param array $record Database record with audit fields
     * @return array Formatted audit information
     */
    public static function formatAuditInfo(array $record): array {
        $audit = [];
        
        // Created information
        if (isset($record['created_at'])) {
            $audit['created'] = [
                'date' => $record['created_at'],
                'formatted_date' => date('M j, Y \a\t g:i A', strtotime($record['created_at'])),
                'user_id' => $record['created_by'] ?? null,
                'days_ago' => self::getDaysAgo($record['created_at'])
            ];
        }
        
        // Updated information
        if (isset($record['last_updated'])) {
            $audit['updated'] = [
                'date' => $record['last_updated'],
                'formatted_date' => date('M j, Y \a\t g:i A', strtotime($record['last_updated'])),
                'user_id' => $record['updated_by'] ?? null,
                'days_ago' => self::getDaysAgo($record['last_updated'])
            ];
        }
        
        return $audit;
    }
    
    /**
     * Calculate days ago from a date
     * 
     * @param string $date Date string
     * @return int Days ago
     */
    private static function getDaysAgo(string $date): int {
        $timestamp = strtotime($date);
        $now = time();
        $diff = $now - $timestamp;
        
        return (int) floor($diff / (60 * 60 * 24));
    }
    
    /**
     * Validate that audit fields exist in table
     * 
     * @param PDO $pdo Database connection
     * @param string $tableName Table name to check
     * @return array Missing audit fields
     */
    public static function validateAuditFields(PDO $pdo, string $tableName): array {
        $requiredFields = ['created_at', 'last_updated', 'created_by', 'updated_by'];
        $missingFields = [];
        
        try {
            $stmt = $pdo->query("DESCRIBE `$tableName`");
            $existingFields = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existingFields[] = $row['Field'];
            }
            
            foreach ($requiredFields as $field) {
                if (!in_array($field, $existingFields)) {
                    $missingFields[] = $field;
                }
            }
            
        } catch (Exception $e) {
            // Table doesn't exist or other error
            return $requiredFields;
        }
        
        return $missingFields;
    }
}
?>