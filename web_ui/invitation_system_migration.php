<?php
/**
 * Invitation System Migration
 * 
 * Creates database schema for comprehensive invitation system including:
 * - Friend invitations
 * - Advisor invitations  
 * - Upgrade requests
 * - Approval workflows
 */

require_once __DIR__ . '/UserAuthDAO.php';

class InvitationSystemMigration {
    private $pdo;
    
    public function __construct() {
        $userAuth = new UserAuthDAO();
        $reflection = new ReflectionClass($userAuth);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $this->pdo = $pdoProperty->getValue($userAuth);
    }
    
    public function run() {
        echo "🚀 Starting Invitation System Migration...\n\n";
        
        try {
            $this->createInvitationsTable();
            $this->createAdvisorUpgradeRequestsTable();
            $this->updateUserAdvisorsTable();
            $this->seedInitialData();
            
            echo "\n🎉 Invitation System Migration completed successfully!\n";
            echo "Features now available:\n";
            echo "  - Friend invitations\n";
            echo "  - Advisor invitations with client approval\n";
            echo "  - Advisor upgrade requests\n";
            echo "  - Comprehensive invitation management\n\n";
            
        } catch (Exception $e) {
            echo "\n💥 Migration failed: " . $e->getMessage() . "\n";
            echo "📍 Error at: " . $e->getFile() . ":" . $e->getLine() . "\n";
            exit(1);
        }
    }
    
    private function createInvitationsTable() {
        echo "📋 Creating invitations table...\n";
        
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS invitations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                
                -- Basic invitation info
                inviter_id INT NOT NULL,
                invitee_email VARCHAR(255) NOT NULL,
                invitee_id INT NULL,  -- NULL if inviting by email, set when they register
                invitation_type ENUM('friend', 'advisor', 'client') NOT NULL,
                
                -- Invitation content
                subject VARCHAR(255) NOT NULL,
                message TEXT NULL,
                invitation_token VARCHAR(64) UNIQUE NOT NULL,
                
                -- Status and timing
                status ENUM('pending', 'accepted', 'declined', 'expired', 'cancelled') DEFAULT 'pending',
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                responded_at TIMESTAMP NULL,
                
                -- Advisor-specific fields
                requested_permission_level ENUM('read', 'read_write') NULL,
                
                -- Response tracking
                response_message TEXT NULL,
                
                -- Metadata
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                -- Foreign keys
                FOREIGN KEY (inviter_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (invitee_id) REFERENCES users(id) ON DELETE CASCADE,
                
                -- Indexes
                INDEX idx_invitee_email (invitee_email),
                INDEX idx_invitation_token (invitation_token),
                INDEX idx_status (status),
                INDEX idx_type_status (invitation_type, status),
                INDEX idx_inviter_type (inviter_id, invitation_type)
            )
        ");
        
        echo "  ✓ Created invitations table\n";
    }
    
    private function createAdvisorUpgradeRequestsTable() {
        echo "📋 Creating advisor_upgrade_requests table...\n";
        
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS advisor_upgrade_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                
                -- Request details
                user_id INT NOT NULL,
                client_id INT NULL,  -- NULL if inviting new client by email
                client_email VARCHAR(255) NULL,  -- Email if inviting new client
                
                -- Request content
                business_name VARCHAR(255) NULL,
                credentials TEXT NULL,  -- Professional credentials
                description TEXT NULL,   -- Why they want to be advisor
                
                -- Status tracking
                status ENUM('pending_client', 'client_approved', 'admin_review', 'approved', 'rejected', 'cancelled') DEFAULT 'pending_client',
                requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                client_approved_at TIMESTAMP NULL,
                admin_reviewed_at TIMESTAMP NULL,
                completed_at TIMESTAMP NULL,
                
                -- Approval details
                client_approval_token VARCHAR(64) UNIQUE NULL,
                client_response_message TEXT NULL,
                admin_response_message TEXT NULL,
                
                -- Metadata
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                -- Foreign keys
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE SET NULL,
                
                -- Indexes
                INDEX idx_user_status (user_id, status),
                INDEX idx_client_status (client_id, status),
                INDEX idx_approval_token (client_approval_token),
                INDEX idx_status (status)
            )
        ");
        
        echo "  ✓ Created advisor_upgrade_requests table\n";
    }
    
    private function updateUserAdvisorsTable() {
        echo "📋 Updating user_advisors table with invitation tracking...\n";
        
        // Check if invitation_id column exists
        $columns = $this->pdo->query("SHOW COLUMNS FROM user_advisors LIKE 'invitation_id'")->fetchAll();
        
        if (empty($columns)) {
            $this->pdo->exec("
                ALTER TABLE user_advisors 
                ADD COLUMN invitation_id INT NULL AFTER id,
                ADD FOREIGN KEY (invitation_id) REFERENCES invitations(id) ON DELETE SET NULL
            ");
            echo "  ✓ Added invitation_id column to user_advisors\n";
        } else {
            echo "  ✓ invitation_id column already exists in user_advisors\n";
        }
        
        // Check if upgrade_request_id column exists
        $columns = $this->pdo->query("SHOW COLUMNS FROM user_advisors LIKE 'upgrade_request_id'")->fetchAll();
        
        if (empty($columns)) {
            $this->pdo->exec("
                ALTER TABLE user_advisors 
                ADD COLUMN upgrade_request_id INT NULL AFTER invitation_id,
                ADD FOREIGN KEY (upgrade_request_id) REFERENCES advisor_upgrade_requests(id) ON DELETE SET NULL
            ");
            echo "  ✓ Added upgrade_request_id column to user_advisors\n";
        } else {
            echo "  ✓ upgrade_request_id column already exists in user_advisors\n";
        }
    }
    
    private function seedInitialData() {
        echo "📋 Seeding initial invitation templates...\n";
        
        // Create default invitation templates (we'll store these in a config or database later)
        echo "  ✓ Invitation system ready for use\n";
    }
}

// Run migration if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $migration = new InvitationSystemMigration();
    $migration->run();
}
?>