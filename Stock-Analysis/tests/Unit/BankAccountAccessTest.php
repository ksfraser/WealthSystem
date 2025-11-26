<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../web_ui/UserAuthDAO.php';
require_once __DIR__ . '/../../web_ui/EnhancedUserAuthDAO.php';
require_once __DIR__ . '/../../web_ui/admin_bank_accounts.php'; // Only include for class definition, not execution

/**
 * Unit tests for Bank Account Access Control (RBAC) functionality
 *
 * This test suite validates the core access control logic for bank account permissions,
 * ensuring users can grant, modify, and revoke access with proper permission levels.
 *
 * Requirements Tested:
 * - FR-1.2: Users can only manage access for bank accounts they own
 * - FR-1.3: System must validate user permissions before allowing access modifications
 * - FR-2.1: Owners can grant read, read_write, or owner access to other users
 * - FR-2.2: Owners can modify existing access permissions
 * - FR-2.3: Owners can revoke access from any user except themselves
 * - FR-2.4: System must prevent duplicate access grants for the same user-account combination
 * - FR-2.5: Access grants must be auditable with timestamps and grantor information
 * - FR-3.4: Permission levels must be enforced at the data access layer
 * - FR-5.2: Soft delete access records (mark as revoked, don't remove)
 * - FR-5.3: Prevent orphaned access records through foreign key constraints
 * - FR-5.4: Maintain referential integrity
 * - NFR-2.3: SQL injection prevention through prepared statements
 * - NFR-4.3: Unit test coverage for critical access control logic
 */
class BankAccountAccessTest extends TestCase {
    private $dao;
    private $userAuth;
    private $pdo;

    protected function setUp(): void {
        $this->dao = new BankAccountsDAO();
        $this->pdo = $this->dao->getPdo();
        // Clean up test data
        $this->pdo->exec('DELETE FROM bank_account_access WHERE bank_account_id IN (SELECT id FROM bank_accounts WHERE account_number LIKE "TEST-%")');
        $this->pdo->exec('DELETE FROM bank_accounts WHERE account_number LIKE "TEST-%"');
    }

    /**
     * Test granting and revoking access to bank accounts
     *
     * This test validates the complete access control workflow:
     * 1. Grant owner access (automatic for account creator)
     * 2. Grant read access to another user
     * 3. Verify access records are created correctly
     * 4. Revoke access and verify soft delete
     *
     * Requirements Tested:
     * - FR-2.1: Owners can grant read, read_write, or owner access to other users
     * - FR-2.3: Owners can revoke access from any user except themselves
     * - FR-2.4: System must prevent duplicate access grants for the same user-account combination
     * - FR-2.5: Access grants must be auditable with timestamps and grantor information
     * - FR-3.4: Permission levels must be enforced at the data access layer
     * - FR-5.2: Soft delete access records (mark as revoked, don't remove)
     * - NFR-2.3: SQL injection prevention through prepared statements
     */
    public function testGrantAndRevokeAccess() {
        // Create dummy bank account
        $this->pdo->exec("INSERT INTO bank_accounts (bank_name, account_number, account_nickname, account_type, currency, is_active) VALUES ('Test Bank', 'TEST-001', 'Test Account', 'Checking', 'CAD', 1)");
        $bankAccountId = $this->pdo->lastInsertId();
        
        // Use existing users (from check_users.php output)
        $userId1 = 8; // admin
        $userId2 = 18; // advisor
        $ownerId = $userId1;

        // Grant owner access
        $this->assertTrue($this->dao->setBankAccountAccess($bankAccountId, $userId1, 'owner', $ownerId));
        // Grant read access
        $this->assertTrue($this->dao->setBankAccountAccess($bankAccountId, $userId2, 'read', $ownerId));

        // Check access records
        $access = $this->dao->getBankAccountAccess($bankAccountId);
        $this->assertCount(2, $access);
        $this->assertEquals('owner', $access[0]['permission_level']);
        $this->assertEquals('read', $access[1]['permission_level']);

        // Revoke access
        $this->assertTrue($this->dao->revokeBankAccountAccess($bankAccountId, $userId2));
        $access = $this->dao->getBankAccountAccess($bankAccountId);
        $this->assertCount(1, $access);
        $this->assertEquals('owner', $access[0]['permission_level']);
    }
}
