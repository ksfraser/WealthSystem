-- Database schema for navigation items
-- This allows storing navigation configuration in database

CREATE TABLE IF NOT EXISTS navigation_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id VARCHAR(100) UNIQUE NOT NULL,
    item_type ENUM('menu', 'card', 'both') DEFAULT 'both',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    url VARCHAR(255) NOT NULL,
    required_role VARCHAR(50) NULL,
    sort_order INT DEFAULT 0,
    parent_id VARCHAR(100) NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_item_type (item_type),
    INDEX idx_required_role (required_role),
    INDEX idx_parent_id (parent_id),
    INDEX idx_sort_order (sort_order),
    INDEX idx_enabled (is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for navigation item actions (for dashboard cards)
CREATE TABLE IF NOT EXISTS navigation_item_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id VARCHAR(100) NOT NULL,
    url VARCHAR(255) NOT NULL,
    label VARCHAR(100) NOT NULL,
    sort_order INT DEFAULT 0,
    
    FOREIGN KEY (item_id) REFERENCES navigation_items(item_id) ON DELETE CASCADE,
    INDEX idx_item_id (item_id),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default portfolio items
INSERT INTO navigation_items (item_id, item_type, title, description, icon, url, required_role, sort_order) VALUES
('portfolio', 'menu', 'Portfolio', 'Portfolio management and tracking', '💼', '#', NULL, 1),
('portfolio.my', 'both', 'My Portfolio', 'View your portfolio', '🏠', 'MyPortfolio.php', NULL, 1),
('portfolio.manage', 'menu', 'Manage Portfolios', 'Create and manage portfolios', '📈', 'portfolios.php', NULL, 2),
('portfolio.trades', 'menu', 'Trades', 'View trading history', '📋', 'trades.php', NULL, 3),
('portfolio.automation', 'menu', 'Automation', 'Automated trading scripts', '🤖', 'automation_scripts.php', NULL, 4);

-- Set parent for portfolio submenu items
UPDATE navigation_items SET parent_id = 'portfolio' WHERE item_id LIKE 'portfolio.%';

-- Insert stock analysis items
INSERT INTO navigation_items (item_id, item_type, title, description, icon, url, required_role, sort_order) VALUES
('stock_analysis', 'both', 'Stock Analysis', 'AI-powered stock analysis and screening tools', '🔍', 'stock_analysis.php', NULL, 2);

-- Insert data management items
INSERT INTO navigation_items (item_id, item_type, title, description, icon, url, required_role, sort_order) VALUES
('data_management', 'both', 'Data Management', 'Import transaction data and account holdings', '📥', 'bank_import.php', NULL, 3);

-- Insert reports items
INSERT INTO navigation_items (item_id, item_type, title, description, icon, url, required_role, sort_order) VALUES
('reports', 'both', 'Reports', 'Generate reports and view performance charts', '📊', 'reports.php', NULL, 4);

-- Insert admin items
INSERT INTO navigation_items (item_id, item_type, title, description, icon, url, required_role, sort_order) VALUES
('admin', 'menu', 'Administration', 'Administrative functions', '⚙️', '#', 'admin', 5),
('admin.bank_accounts', 'menu', 'Bank Accounts', 'Manage bank accounts', '🏦', 'admin_bank_accounts.php', 'admin', 1),
('admin.brokerages', 'menu', 'Brokerages', 'Manage brokerage accounts', '🏢', 'admin_brokerages.php', 'admin', 2),
('admin.users', 'menu', 'User Management', 'Manage system users', '👥', 'admin_users.php', 'admin', 3),
('admin.accounts_card', 'card', 'Account Management', 'Manage bank accounts, brokerages, and system settings', '🏦', 'admin_bank_accounts.php', 'admin', 5),
('admin.tools_card', 'card', 'Admin Tools', 'Administrative functions for managing users and system settings', '🔧', 'admin_users.php', 'admin', 6);

-- Set parent for admin submenu items
UPDATE navigation_items SET parent_id = 'admin' WHERE item_id LIKE 'admin.%' AND item_id NOT LIKE '%_card';

-- Insert profile items
INSERT INTO navigation_items (item_id, item_type, title, description, icon, url, required_role, sort_order) VALUES
('profile', 'menu', 'Profile', 'User profile and settings', '👤', '#', NULL, 6),
('profile.view', 'menu', 'View Profile', 'View your profile', '👤', 'profile.php', NULL, 1),
('profile.settings', 'menu', 'Settings', 'Account settings', '⚙️', 'settings.php', NULL, 2),
('profile.logout', 'menu', 'Logout', 'Sign out of your account', '🚪', 'logout.php', NULL, 3),
('profile_card', 'card', 'Profile & Invitations', 'Manage your profile, invite friends and advisors', '👤', 'profile.php', NULL, 8);

-- Set parent for profile submenu items
UPDATE navigation_items SET parent_id = 'profile' WHERE item_id LIKE 'profile.%';

-- Insert actions for portfolio management card
INSERT INTO navigation_item_actions (item_id, url, label, sort_order) VALUES
('portfolio.my', 'MyPortfolio.php', '🏠 My Portfolio', 1),
('portfolio.my', 'portfolios.php', '📈 Manage Portfolios', 2),
('portfolio.my', 'trades.php', '📋 Trades', 3),
('portfolio.my', '../simple_automation.py', '🤖 Automation', 4);

-- Insert actions for stock analysis card
INSERT INTO navigation_item_actions (item_id, url, label, sort_order) VALUES
('stock_analysis', 'stock_search.php', '🔍 Stock Search', 1),
('stock_analysis', 'stock_analysis.php', '🤖 Stock Analysis', 2),
('stock_analysis', 'stock_analysis.php?demo=1', '🎯 Demo Analysis', 3);

-- Insert actions for data management card
INSERT INTO navigation_item_actions (item_id, url, label, sort_order) VALUES
('data_management', 'bank_import.php', '💾 Bank CSV Import', 1),
('data_management', 'trades.php', '📝 Trade Log', 2);

-- Insert actions for reports card
INSERT INTO navigation_item_actions (item_id, url, label, sort_order) VALUES
('reports', '../Scripts and CSV Files/Generate_Graph.py', '📈 Performance Charts', 1),
('reports', 'reports.php', '📋 Custom Reports', 2);

-- Insert actions for admin cards
INSERT INTO navigation_item_actions (item_id, url, label, sort_order) VALUES
('admin.accounts_card', 'admin_account_types.php', '📋 Account Types', 1),
('admin.accounts_card', 'admin_brokerages.php', '🏢 Brokerages', 2),
('admin.accounts_card', 'admin_bank_accounts.php', '🏪 Bank Accounts', 3),
('admin.tools_card', 'admin_users.php', '👥 User Management', 1),
('admin.tools_card', 'admin_system.php', '⚙️ System Settings', 2),
('admin.tools_card', 'database.php', '🗄️ Database Management', 3);

-- Insert actions for profile card
INSERT INTO navigation_item_actions (item_id, url, label, sort_order) VALUES
('profile_card', 'profile.php', '⚙️ Edit Profile', 1),
('profile_card', 'profile.php#invitations', '📧 Manage Invitations', 2),
('profile_card', 'profile.php#upgrade', '🎓 Become an Advisor', 3);
