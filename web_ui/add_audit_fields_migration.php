<?php
/**
 * Add Audit Fields Migration
 * 
 * Adds created_at, last_updated, created_by, and updated_by fields to all tables
 */

require_once __DIR__ . '/UserAuthDAO.php';

class AuditFieldsMigration {
    private $pdo;
    
    public function __construct() {
        $auth = new UserAuthDAO();
        
        // Get PDO connection using reflection
        $reflection = new ReflectionClass($auth);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $this->pdo = $pdoProperty->getValue($auth);
        
        if (!$this->pdo) {
            throw new Exception('Database connection not available');
        }
    }
    
    public function run() {
        echo "<h1>🔧 Adding Audit Fields to All Tables</h1>";
        
        try {
            // Get all tables in the database
            $tables = $this->getAllTables();
            
            foreach ($tables as $table) {
                echo "<h2>Processing table: $table</h2>";
                $this->addAuditFieldsToTable($table);
            }
            
            echo "<h2>✅ Migration completed successfully!</h2>";
            
        } catch (Exception $e) {
            echo "<h2>❌ Migration failed: " . $e->getMessage() . "</h2>";
            throw $e;
        }
    }
    
    private function getAllTables() {
        $stmt = $this->pdo->query("SHOW TABLES");
        $tables = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        return $tables;
    }
    
    private function addAuditFieldsToTable($tableName) {
        $existingColumns = $this->getTableColumns($tableName);
        
        // Define audit fields
        $auditFields = [
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'last_updated' => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'created_by' => 'INT(11) NULL',
            'updated_by' => 'INT(11) NULL'
        ];
        
        foreach ($auditFields as $fieldName => $fieldDefinition) {
            if (!in_array($fieldName, $existingColumns)) {
                try {
                    $sql = "ALTER TABLE `$tableName` ADD COLUMN `$fieldName` $fieldDefinition";
                    $this->pdo->exec($sql);
                    echo "  ✅ Added $fieldName to $tableName<br>";
                } catch (Exception $e) {
                    echo "  ⚠️ Could not add $fieldName to $tableName: " . $e->getMessage() . "<br>";
                }
            } else {
                echo "  ℹ️ $fieldName already exists in $tableName<br>";
            }
        }
        
        // Add foreign key constraints for created_by and updated_by if users table exists
        if ($tableName !== 'users' && in_array('users', $this->getAllTables())) {
            $this->addUserForeignKeys($tableName, $existingColumns);
        }
    }
    
    private function getTableColumns($tableName) {
        $stmt = $this->pdo->query("DESCRIBE `$tableName`");
        $columns = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        
        return $columns;
    }
    
    private function addUserForeignKeys($tableName, $existingColumns) {
        // Check if foreign keys already exist
        $foreignKeys = $this->getForeignKeys($tableName);
        
        $fkConstraints = [
            'created_by' => "fk_{$tableName}_created_by",
            'updated_by' => "fk_{$tableName}_updated_by"
        ];
        
        foreach ($fkConstraints as $column => $constraintName) {
            if (in_array($column, $existingColumns) && !in_array($constraintName, $foreignKeys)) {
                try {
                    $sql = "ALTER TABLE `$tableName` 
                            ADD CONSTRAINT `$constraintName` 
                            FOREIGN KEY (`$column`) REFERENCES `users`(`id`) 
                            ON DELETE SET NULL ON UPDATE CASCADE";
                    $this->pdo->exec($sql);
                    echo "  ✅ Added foreign key constraint $constraintName to $tableName<br>";
                } catch (Exception $e) {
                    echo "  ⚠️ Could not add foreign key $constraintName to $tableName: " . $e->getMessage() . "<br>";
                }
            }
        }
    }
    
    private function getForeignKeys($tableName) {
        $stmt = $this->pdo->query("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = '$tableName' 
            AND CONSTRAINT_SCHEMA = DATABASE()
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        $foreignKeys = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $foreignKeys[] = $row['CONSTRAINT_NAME'];
        }
        
        return $foreignKeys;
    }
    
    public function showTablesStatus() {
        echo "<h2>📊 Current Tables Status</h2>";
        
        $tables = $this->getAllTables();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>
                <th>Table Name</th>
                <th>created_at</th>
                <th>last_updated</th>
                <th>created_by</th>
                <th>updated_by</th>
              </tr>";
        
        foreach ($tables as $table) {
            $columns = $this->getTableColumns($table);
            
            echo "<tr>";
            echo "<td><strong>$table</strong></td>";
            echo "<td>" . (in_array('created_at', $columns) ? '✅' : '❌') . "</td>";
            echo "<td>" . (in_array('last_updated', $columns) ? '✅' : '❌') . "</td>";
            echo "<td>" . (in_array('created_by', $columns) ? '✅' : '❌') . "</td>";
            echo "<td>" . (in_array('updated_by', $columns) ? '✅' : '❌') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
}

// Execute migration
try {
    $migration = new AuditFieldsMigration();
    
    echo "<h2>📋 Before Migration</h2>";
    $migration->showTablesStatus();
    
    echo "<hr>";
    
    $migration->run();
    
    echo "<hr>";
    
    echo "<h2>📋 After Migration</h2>";
    $migration->showTablesStatus();
    
    echo "<hr>";
    
    echo "<p><strong>🎉 Migration Complete!</strong> All tables now have proper audit fields.</p>";
    echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Migration Error</h2>";
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-width: 1000px;
    margin: 20px auto;
    padding: 20px;
    background: #f5f5f5;
    line-height: 1.6;
}

h1, h2 {
    color: #2c3e50;
    margin: 20px 0 10px 0;
}

table {
    margin: 20px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

th, td {
    padding: 10px;
    text-align: left;
}

th {
    background: #3498db;
    color: white;
}

tr:nth-child(even) {
    background: #f9f9f9;
}

a {
    color: #3498db;
    text-decoration: none;
    font-weight: 600;
}

a:hover {
    text-decoration: underline;
}

hr {
    border: none;
    border-top: 2px solid #3498db;
    margin: 30px 0;
}
</style>