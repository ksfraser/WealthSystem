<?php
require_once 'UserAuthDAO.php';

class SimpleRBACMigration {
    private $pdo;
    
    public function __construct() {
        $userAuth = new UserAuthDAO();
        $reflection = new ReflectionClass($userAuth);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $this->pdo = $pdoProperty->getValue($userAuth);
    }
    
    public function run() {
        echo "🚀 Starting Simple RBAC Migration...\n\n";
        
        try {
            // Step 1: Create tables (if not exist)
            $this->createTablesIfNotExist();
            
            // Step 2: Seed default data (if not exist)
            $this->seedDefaultData();
            
            // Step 3: Migrate existing users
            $this->migrateUsers();
            
            echo "\n🎉 RBAC Migration completed successfully!\n";
            
        } catch (Exception $e) {
            echo "\n💥 Migration failed: " . $e->getMessage() . "\n";
            echo "📍 Error at: " . $e->getFile() . ":" . $e->getLine() . "\n";
            exit(1);
        }
    }
    
    private function createTablesIfNotExist() {
        echo "📋 Creating RBAC tables (if not exist)...\n";
        
        // Check if tables already exist
        $tables = ['roles', 'permissions', 'role_permissions', 'user_roles', 'user_advisors'];
        $existingTables = [];
        
        foreach ($tables as $table) {
            $result = $this->pdo->query("SHOW TABLES LIKE '$table'")->fetch();
            if ($result) {
                $existingTables[] = $table;
            }
        }
        
        if (count($existingTables) === count($tables)) {
            echo "  ✅ All RBAC tables already exist, skipping creation\n";
            return;
        }
        
        // Create roles table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) UNIQUE NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        echo "  ✓ Created roles table\n";
        
        // Create permissions table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) UNIQUE NOT NULL,
                resource VARCHAR(50) NOT NULL,
                action VARCHAR(50) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "  ✓ Created permissions table\n";
        
        // Create role_permissions table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS role_permissions (
                role_id INT,
                permission_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (role_id, permission_id),
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
            )
        ");
        echo "  ✓ Created role_permissions table\n";
        
        // Create user_roles table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS user_roles (
                user_id INT,
                role_id INT,
                granted_by INT NULL,
                granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                notes TEXT NULL,
                PRIMARY KEY (user_id, role_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");
        echo "  ✓ Created user_roles table\n";
        
        // Create user_advisors table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS user_advisors (
                client_id INT,
                advisor_id INT,
                permission_level ENUM('read', 'read_write') DEFAULT 'read',
                invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                accepted_at TIMESTAMP NULL,
                status ENUM('pending', 'active', 'revoked') DEFAULT 'pending',
                notes TEXT NULL,
                PRIMARY KEY (client_id, advisor_id),
                FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (advisor_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        echo "  ✓ Created user_advisors table\n";
    }
    
    private function seedDefaultData() {
        echo "🌱 Seeding default roles and permissions...\n";
        
        // Check if data already exists
        $roleCount = $this->pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
        if ($roleCount > 0) {
            echo "  ✅ Default data already exists, skipping seeding\n";
            return;
        }
        
        // Insert default roles
        $roles = [
            ['admin', 'System administrator with full access'],
            ['user', 'Standard user with portfolio access'],
            ['advisor', 'Financial advisor with client management capabilities'],
            ['guest', 'Limited read-only access']
        ];
        
        $roleStmt = $this->pdo->prepare("INSERT IGNORE INTO roles (name, description) VALUES (?, ?)");
        foreach ($roles as $role) {
            $roleStmt->execute($role);
        }
        echo "  ✓ Seeded roles\n";
        
        // Insert default permissions
        $permissions = [
            // User permissions
            ['user.view.own', 'user', 'view', 'View own user profile'],
            ['user.edit.own', 'user', 'edit', 'Edit own user profile'],
            ['user.invite.advisor', 'user', 'invite', 'Invite advisors to view portfolio'],
            
            // Portfolio permissions
            ['portfolio.view.own', 'portfolio', 'view', 'View own portfolio'],
            ['portfolio.edit.own', 'portfolio', 'edit', 'Edit own portfolio'],
            ['portfolio.delete.own', 'portfolio', 'delete', 'Delete own portfolio entries'],
            
            // Admin permissions
            ['user.manage.all', 'user', 'manage', 'Manage all users'],
            ['system.status.view', 'system', 'view', 'View system status'],
            ['system.config.edit', 'system', 'edit', 'Modify system configuration'],
            
            // Advisor permissions
            ['advisor.client.manage', 'advisor', 'manage', 'Manage advisor-client relationships'],
            ['advisor.permissions.view', 'advisor', 'view', 'View advisor permission levels'],
            
            // Analytics permissions
            ['analytics.view.own', 'analytics', 'view', 'View personal analytics and reports'],
            ['analytics.view.client', 'analytics', 'view', 'View client analytics as advisor'],
            ['analytics.export.own', 'analytics', 'export', 'Export personal analytics data']
        ];
        
        $permStmt = $this->pdo->prepare("INSERT IGNORE INTO permissions (name, resource, action, description) VALUES (?, ?, ?, ?)");
        foreach ($permissions as $permission) {
            $permStmt->execute($permission);
        }
        echo "  ✓ Seeded permissions\n";
        
        // Map roles to permissions
        $this->mapRolePermissions();
        echo "  ✓ Mapped roles to permissions\n";
    }
    
    private function mapRolePermissions() {
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
                'user.view.own', 'user.edit.own', 'advisor.client.manage',
                'advisor.permissions.view', 'analytics.view.client'
            ],
            'guest' => [
                'user.view.own'
            ]
        ];
        
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO role_permissions (role_id, permission_id)
            SELECT r.id, p.id 
            FROM roles r, permissions p 
            WHERE r.name = ? AND p.name = ?
        ");
        
        foreach ($roleMappings as $roleName => $permissions) {
            foreach ($permissions as $permissionName) {
                $stmt->execute([$roleName, $permissionName]);
            }
        }
    }
    
    private function migrateUsers() {
        echo "👥 Migrating existing users to RBAC...\n";
        
        // Find admin user for granted_by field
        $adminUser = $this->pdo->query("SELECT id FROM users WHERE is_admin = 1 LIMIT 1")->fetch();
        $grantedBy = $adminUser ? $adminUser['id'] : null;
        
        if ($grantedBy) {
            echo "  👤 Using admin user ID $grantedBy for granted_by field\n";
        }
        
        // Get users not yet migrated
        $users = $this->pdo->query("
            SELECT id, username, is_admin 
            FROM users 
            WHERE id NOT IN (SELECT DISTINCT user_id FROM user_roles)
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($users)) {
            echo "  ✅ All users already migrated\n";
            return;
        }
        
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
        
        echo "  ✅ Migrated " . count($users) . " users\n";
    }
}

// Run migration if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $migration = new SimpleRBACMigration();
    $migration->run();
}
?>