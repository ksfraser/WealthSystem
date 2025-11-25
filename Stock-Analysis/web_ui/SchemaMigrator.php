<?php
/**
 * SchemaMigrator: Handles running and versioning SQL schema migrations.
 */
class SchemaMigrator {
    private $pdo;
    private $schemaDir;
    private $table = 'schema_migrations';

    public function __construct($pdo, $schemaDir) {
        $this->pdo = $pdo;
        $this->schemaDir = $schemaDir;
        $this->ensureMigrationsTable();
    }

    private function ensureMigrationsTable() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) UNIQUE,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function migrate() {
        $applied = $this->getAppliedMigrations();
        $files = glob($this->schemaDir . '/*.sql');
        sort($files);
        foreach ($files as $file) {
            $fname = basename($file);
            if (!in_array($fname, $applied)) {
                $sql = file_get_contents($file);
                $this->pdo->exec($sql);
                $stmt = $this->pdo->prepare("INSERT INTO {$this->table} (filename) VALUES (?)");
                $stmt->execute([$fname]);
            }
        }
    }

    private function getAppliedMigrations() {
        $this->ensureMigrationsTable();
        $rows = $this->pdo->query("SELECT filename FROM {$this->table}")->fetchAll(PDO::FETCH_COLUMN);
        return $rows ?: [];
    }
}
