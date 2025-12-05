<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

/**
 * Migration interface
 * 
 * All database migrations must implement this interface.
 * 
 * @package App\Database
 */
interface Migration
{
    /**
     * Get the migration version
     * 
     * Version should follow format: YYYYMMDDHHmmss
     * Example: 20251205143000 (2025-12-05 14:30:00)
     * 
     * @return string The migration version
     */
    public function getVersion(): string;

    /**
     * Get the migration description
     * 
     * @return string Human-readable description
     */
    public function getDescription(): string;

    /**
     * Apply the migration
     * 
     * @param PDO $pdo Database connection
     * @return void
     * @throws \RuntimeException If migration fails
     */
    public function up(PDO $pdo): void;

    /**
     * Rollback the migration
     * 
     * @param PDO $pdo Database connection
     * @return void
     * @throws \RuntimeException If rollback fails
     */
    public function down(PDO $pdo): void;
}
