<?php
/**
 * Migration to add bank_account_id to gl_trans_invest table.
 *
 * This script can be run from the command line to apply the database schema change.
 * Usage: php add_bank_account_id_to_gl_trans.php
 */

require_once __DIR__ . '/../CommonDAO.php';

class AddBankAccountIdMigration extends CommonDAO {

    public function __construct() {
        // Use the same database configuration as the rest of the application
        parent::__construct('LegacyDatabaseConfig');
    }

    /**
     * Executes the database migration.
     */
    public function run() {
        if (!$this->pdo) {
            echo "Error: Database connection not available.\n";
            return;
        }

        try {
            echo "Checking if 'bank_account_id' column exists...\n";
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `gl_trans_invest` LIKE 'bank_account_id'");
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($exists) {
                echo "'bank_account_id' column already exists. No action needed.\n";
                return;
            }

            echo "Applying migration: Adding 'bank_account_id' to 'gl_trans_invest' table...\n";
            
            $sql = "ALTER TABLE gl_trans_invest 
                    ADD COLUMN bank_account_id INT NULL AFTER user_id, 
                    ADD INDEX (bank_account_id);";
            
            $this->pdo->exec($sql);
            
            echo "Migration successful! The 'gl_trans_invest' table has been updated.\n";

        } catch (PDOException $e) {
            echo "Error applying migration: " . $e->getMessage() . "\n";
        }
    }
}

// Execute the migration
$migration = new AddBankAccountIdMigration();
$migration->run();
