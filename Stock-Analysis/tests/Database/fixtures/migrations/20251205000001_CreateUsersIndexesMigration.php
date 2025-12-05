<?php

declare(strict_types=1);

namespace Tests\Database\Fixtures;

use App\Database\Migration;
use PDO;

/**
 * Test migration for adding indexes to users table
 */
class CreateUsersIndexesMigration implements Migration
{
    public function getVersion(): string
    {
        return '20251205000001';
    }

    public function getDescription(): string
    {
        return 'Add indexes to users table';
    }

    public function up(PDO $pdo): void
    {
        $pdo->exec("CREATE INDEX idx_users_email ON users(email)");
        $pdo->exec("CREATE INDEX idx_users_created_at ON users(created_at)");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP INDEX IF EXISTS idx_users_email");
        $pdo->exec("DROP INDEX IF EXISTS idx_users_created_at");
    }
}
