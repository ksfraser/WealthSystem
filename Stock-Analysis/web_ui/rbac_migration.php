<?php
/**
 * RBAC Database Schema Migration
 * 
 * Creates the Role-Based Access Control tables for multi-role support.
 * Supports scenarios like: admin+user, user+advisor, admin+advisor+user
 */

require_once 'UserAuthDAO.php';

class RBACMigration {
    private $pdo;
    
    public function __construct() {
        // Use existing UserAuthDAO to get database connection
        $userAuth = new UserAuthDAO();
        $this->pdo = $this->getPDOFromUserAuth($userAuth);
    }
    
    private function getPDOFromUserAuth($userAuth) {
        // Access the protected pdo property through reflection
        $reflection = new ReflectionClass($userAuth);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        return $pdoProperty->getValue($userAuth);
    }
    
    public function createRBACTables() {
        try {
            echo "Creating RBAC tables...\n";
            
            // 1. Roles table
            $this->createRolesTable();
            
            // 2. Permissions table
            $this->createPermissionsTable();
            
            // 3. Role-Permission mapping
            $this->createRolePermissionsTable();
            
            // 4. User-Role mapping (many-to-many for multiple roles)
            $this->createUserRolesTable();
            
            // 5. Advisor relationships
            $this->createUserAdvisorsTable();
            
            // 6. Insert default roles and permissions
            echo "  🔄 Seeding data...\n";
            $this->seedDefaultRolesAndPermissions();
            
            echo "✅ RBAC tables created successfully!\n";
            
        } catch (Exception $e) {
            echo "❌ Error creating RBAC tables: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    private function createRolesTable() {
        $sql = "
        CREATE TABLE IF NOT EXISTS roles (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL UNIQUE,
            display_name VARCHAR(100) NOT NULL,
            description TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_name (name),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->pdo->exec($sql);
        echo "  ✓ Created roles table\n";
    }
    
    private function createPermissionsTable() {
        $sql = "
        CREATE TABLE IF NOT EXISTS permissions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL UNIQUE,
            resource VARCHAR(50) NOT NULL,
            action VARCHAR(50) NOT NULL,
            description TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_resource_action (resource, action),
            INDEX idx_name (name),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->pdo->exec($sql);
        echo "  ✓ Created permissions table\n";
    }
    
    private function createRolePermissionsTable() {
        $sql = "
        CREATE TABLE IF NOT EXISTS role_permissions (
            role_id INT NOT NULL,
            permission_id INT NOT NULL,
            granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            granted_by INT NULL,
            PRIMARY KEY (role_id, permission_id),
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
            FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->pdo->exec($sql);
        echo "  ✓ Created role_permissions table\n";
    }
    
    private function createUserRolesTable() {
        $sql = "
        CREATE TABLE IF NOT EXISTS user_roles (
            user_id INT NOT NULL,
            role_id INT NOT NULL,
            granted_by INT NULL,
            granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            is_active BOOLEAN DEFAULT TRUE,
            notes TEXT NULL,
            PRIMARY KEY (user_id, role_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_user_active (user_id, is_active),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->pdo->exec($sql);
        echo "  ✓ Created user_roles table\n";
    }
    
    private function createUserAdvisorsTable() {
        $sql = "
        CREATE TABLE IF NOT EXISTS user_advisors (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            advisor_id INT NOT NULL,
            permission_level ENUM('read', 'read_write') DEFAULT 'read',
            status ENUM('pending', 'active', 'suspended', 'revoked') DEFAULT 'pending',
            invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            accepted_at TIMESTAMP NULL,
            expires_at TIMESTAMP NULL,
            created_by INT NOT NULL,
            notes TEXT NULL,
            UNIQUE KEY unique_user_advisor (user_id, advisor_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (advisor_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id),
            INDEX idx_advisor_status (advisor_id, status),
            INDEX idx_user_status (user_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->pdo->exec($sql);
        echo "  ✓ Created user_advisors table\n";
    }
    
    private function seedDefaultRolesAndPermissions() {
        echo "    🌱 Inserting roles...\n";
        echo "  Seeding default roles and permissions...\n";
        
        // Insert default roles
        $roles = [
            ['admin', 'System Administrator', 'Full system access and user management'],
            ['user', 'Regular User', 'Standard user with personal portfolio access'],
            ['advisor', 'Financial Advisor', 'Can access client portfolios with granted permissions'],
            ['readonly', 'Read-Only Access', 'View-only access to permitted resources']
        ];
        
        $roleStmt = $this->pdo->prepare("
            INSERT IGNORE INTO roles (name, display_name, description) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($roles as $role) {
            $roleStmt->execute($role);
        }
        
        // Insert default permissions
        $permissions = [
            // Portfolio permissions
            ['portfolio.view.own', 'portfolio', 'view', 'View own portfolios and trades'],
            ['portfolio.edit.own', 'portfolio', 'edit', 'Edit own portfolios and trades'],
            ['portfolio.delete.own', 'portfolio', 'delete', 'Delete own portfolios and trades'],
            ['portfolio.view.client', 'portfolio', 'view', 'View client portfolios as advisor'],
            ['portfolio.edit.client', 'portfolio', 'edit', 'Edit client portfolios as advisor'],
            
            // User management permissions
            ['user.view.own', 'user', 'view', 'View own user profile'],
            ['user.edit.own', 'user', 'edit', 'Edit own user profile'],
            ['user.manage.all', 'user', 'manage', 'Manage all users (admin only)'],
            ['user.invite.advisor', 'user', 'invite', 'Invite advisors to access portfolio'],
            
            // System permissions
            ['system.status.view', 'system', 'view', 'View system status and diagnostics'],
            ['system.config.edit', 'system', 'edit', 'Modify system configuration'],
            
            // Advisor permissions
            ['advisor.client.manage', 'advisor', 'manage', 'Manage advisor-client relationships'],
            ['advisor.permissions.view', 'advisor', 'view', 'View advisor permission levels'],
            
            // Analytics permissions
            ['analytics.view.own', 'analytics', 'view', 'View personal analytics and reports'],
            ['analytics.view.client', 'analytics', 'view', 'View client analytics as advisor'],
            ['analytics.export.own', 'analytics', 'export', 'Export personal analytics data']
        ];
        
        $permStmt = $this->pdo->prepare("
            INSERT IGNORE INTO permissions (name, resource, action, description) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($permissions as $permission) {
            $permStmt->execute($permission);
        }
        
        echo "    🌱 Inserting permissions...\n";
        // Set up role-permission mappings
        echo "    🌱 Setting up role permissions...\n";
        $this->setupRolePermissions();
        
        echo "  ✓ Seeded default roles and permissions\n";
    }
    
    private function setupRolePermissions() {
        echo "      📋 Getting roles...\n";
        // Get role IDs
        $roles = $this->pdo->query("SELECT id, name FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);
        echo "      📋 Found " . count($roles) . " roles\n";
            
            // Define role-permission mappings
            $roleMappings = [
            'admin' => [
                'user.manage.all', 'system.status.view', 'system.config.edit',
                'portfolio.view.own', 'portfolio.edit.own', 'portfolio.delete.own',
                'user.view.own', 'user.edit.own', 'analytics.view.own', 'analytics.export.own'
            ],
            'user' => [
                'portfolio.view.own', 'portfolio.edit.own', 'portfolio.delete.own',
                'user.view.own', 'user.edit.own', 'user.invite.advisor',
                'analytics.view.own', 'analytics.export.own'
            ],
            'advisor' => [
                'portfolio.view.client', 'portfolio.edit.client', 'analytics.view.client',
                'advisor.client.manage', 'advisor.permissions.view',
                'user.view.own', 'user.edit.own'
            ],
            'readonly' => [
                'portfolio.view.own', 'user.view.own', 'analytics.view.own'
            ]
        ];
        
        echo "      📋 Preparing role-permission mapping statement...\n";
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO role_permissions (role_id, permission_id)
            SELECT r.id, p.id 
            FROM roles r, permissions p 
            WHERE r.name = ? AND p.name = ?
        ");
        
        echo "      📋 Mapping roles to permissions...\n";
        $mappingCount = 0;
        foreach ($roleMappings as $roleName => $permissions) {
            foreach ($permissions as $permissionName) {
                try {
                    $stmt->execute([$roleName, $permissionName]);
                    $mappingCount++;
                } catch (PDOException $e) {
                    echo "      ❌ Failed to map $roleName -> $permissionName: " . $e->getMessage() . "\n";
                    throw $e;
                }
            }
        }
        echo "      ✅ Created $mappingCount role-permission mappings\n";
    }
    
    public function migrateExistingUsers() {
        echo "Migrating existing users to RBAC...\n";
        
        try {
            // Check if we're already in a transaction
            if ($this->pdo->inTransaction()) {
                echo "  ⚠️  Already in transaction, committing first...\n";
                $this->pdo->commit();
            }
            $this->pdo->beginTransaction();
            // Get all existing users
            $users = $this->pdo->query("
                SELECT id, username, is_admin 
                FROM users 
                WHERE id NOT IN (SELECT DISTINCT user_id FROM user_roles)
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            // Find an admin user to use as granted_by, or use NULL
            $adminUser = $this->pdo->query("SELECT id FROM users WHERE is_admin = 1 LIMIT 1")->fetch();
            $grantedBy = $adminUser ? $adminUser['id'] : null;
            
            $userRoleStmt = $this->pdo->prepare("
                INSERT INTO user_roles (user_id, role_id, granted_by, notes) 
                SELECT ?, r.id, ?, 'Migrated from is_admin column'
                FROM roles r 
                WHERE r.name = ?
            ");
            
            foreach ($users as $user) {
                // Every user gets the 'user' role
                $userRoleStmt->execute([$user['id'], $grantedBy, 'user']);
                
                // Admins also get the 'admin' role  
                if ($user['is_admin']) {
                    $userRoleStmt->execute([$user['id'], $grantedBy, 'admin']);
                    echo "  ✓ Migrated admin user: {$user['username']} (admin + user)\n";
                } else {
                    echo "  ✓ Migrated regular user: {$user['username']} (user)\n";
                }
            }
            
            $this->pdo->commit();
            echo "✅ Migration completed for " . count($users) . " users\n";
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            echo "❌ Error migrating users: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}

// Run migration if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "🚀 Starting RBAC Migration...\n\n";
    
    try {
        $migration = new RBACMigration();
        echo "  📋 Step 1: Creating RBAC tables...\n";
        $migration->createRBACTables();
        echo "  📋 Step 2: Migrating existing users...\n";
        try {
            $migration->migrateExistingUsers();
        } catch (Exception $e) {
            echo "  ❌ Error in migrateExistingUsers: " . $e->getMessage() . "\n";
            echo "  📍 Error occurred at: " . $e->getFile() . ":" . $e->getLine() . "\n";
            throw $e;
        }
        
        echo "\n🎉 RBAC Migration completed successfully!\n";
        echo "You now have:\n";
        echo "  - Multi-role support (users can have multiple roles)\n";
        echo "  - Granular permissions system\n"; 
        echo "  - Advisor-client relationships\n";
        echo "  - Backward compatibility with existing users\n\n";
        
    } catch (Exception $e) {
        echo "\n💥 Migration failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>