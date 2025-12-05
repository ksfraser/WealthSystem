<?php

declare(strict_types=1);

namespace Tests\Database\Fixtures;

use App\Database\Migration;
use PDO;

/**
 * Test migration for adding foreign keys
 */
class CreateForeignKeysMigration implements Migration
{
    public function getVersion(): string
    {
        return '20251205000002';
    }

    public function getDescription(): string
    {
        return 'Add foreign key constraints';
    }

    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS test_parent (
                id INTEGER PRIMARY KEY
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS test_child (
                id INTEGER PRIMARY KEY,
                parent_id INTEGER,
                FOREIGN KEY (parent_id) REFERENCES test_parent(id) ON DELETE CASCADE
            )
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS test_child");
        $pdo->exec("DROP TABLE IF EXISTS test_parent");
    }
}
