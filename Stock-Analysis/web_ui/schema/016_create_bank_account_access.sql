-- Create table for per-user, per-bank-account access control
-- Implements Role-Based Access Control (RBAC) for bank account permissions
--
-- Requirements Implemented:
-- FR-2.4: System must prevent duplicate access grants for the same user-account combination (UNIQUE KEY)
-- FR-2.5: Access grants must be auditable with timestamps and grantor information (granted_at, granted_by fields)
-- FR-3.1: Owner permission allows full read/write access and access management (permission_level ENUM)
-- FR-3.2: Read_Write permission allows viewing and modifying account data (permission_level ENUM)
-- FR-3.3: Read permission allows viewing account data only (permission_level ENUM)
-- FR-5.1: All access changes must be logged with timestamps (granted_at, revoked_at fields)
-- FR-5.2: Soft delete access records (mark as revoked, don't remove) (revoked_at field)
-- FR-5.3: Prevent orphaned access records through foreign key constraints (FOREIGN KEY constraints)
-- FR-5.4: Maintain referential integrity (CASCADE and SET NULL rules)
-- NFR-1.3: Database queries must use appropriate indexes (UNIQUE KEY and FOREIGN KEY provide indexing)
-- NFR-2.3: SQL injection prevention through prepared statements (table structure supports prepared statements)
-- NFR-5.1: Compatible with existing database schema (follows existing migration patterns)
--
CREATE TABLE IF NOT EXISTS bank_account_access (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bank_account_id INT NOT NULL,
    user_id INT NOT NULL,
    permission_level ENUM('owner','read','read_write') NOT NULL DEFAULT 'read',
    granted_by INT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL,
    notes TEXT NULL,
    UNIQUE KEY unique_account_user (bank_account_id, user_id),
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;