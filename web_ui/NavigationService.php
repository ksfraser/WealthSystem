<?php
/**
 * Centralized Navigation Service - Single Responsibility Principle
 * 
 * This class is the SINGLE source of truth for all navigation rendering.
 * All other components should use this service instead of duplicating logic.
 */

class NavigationService {
    private $userAuth;
    private $isLoggedIn = false;
    private $currentUser = null;
    private $isAdmin = false;
    
    public function __construct() {
        $this->initializeAuth();
    }
    
    private function initializeAuth() {
        try {
            require_once __DIR__ . '/SessionManager.php';
            SessionManager::getInstance();
            
            require_once __DIR__ . '/UserAuthDAO.php';
            $this->userAuth = new UserAuthDAO();
            $this->isLoggedIn = $this->userAuth->isLoggedIn();
            $this->currentUser = $this->isLoggedIn ? $this->userAuth->getCurrentUser() : null;
            $this->isAdmin = $this->isLoggedIn && $this->userAuth->isAdmin();
        } catch (Exception $e) {
            // Gracefully handle auth failures
            $this->isLoggedIn = false;
            $this->currentUser = null;
            $this->isAdmin = false;
        }
    }
    
    /**
     * Get navigation CSS - single source of truth
     */
    public function getNavigationCSS(): string {
        return '
        <style>
        .nav-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .nav-header.admin {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav-title {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .nav-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .nav-links {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        .nav-links a.active {
            background: rgba(255,255,255,0.3);
            font-weight: 600;
        }
        .admin-badge {
            background: #ffffff;
            color: #dc3545;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            border: 1px solid #ffffff;
        }
        .user-dropdown {
            position: relative;
            display: inline-block;
        }
        .user-dropdown-toggle {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .user-dropdown-toggle:hover {
            background: rgba(255,255,255,0.2);
        }
        .user-dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            min-width: 160px;
            z-index: 1000;
            margin-top: 5px;
        }
        .dropdown-item {
            display: block;
            padding: 8px 16px;
            color: #333;
            text-decoration: none;
            border-bottom: 1px solid #f8f9fa;
            transition: background-color 0.3s;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #007bff;
        }
        .dropdown-item:last-child {
            border-bottom: none;
        }
        .dropdown-divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 4px 0;
        }
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .nav-links {
                justify-content: center;
            }
        }
        </style>';
    }
    
    /**
     * Get navigation JavaScript - single source of truth
     */
    public function getNavigationScript(): string {
        return '
        <script>
        function toggleUserDropdown() {
            var menu = document.getElementById("userDropdownMenu");
            if (menu) {
                menu.style.display = menu.style.display === "block" ? "none" : "block";
            }
        }
        
        // Close dropdown when clicking outside
        document.addEventListener("click", function(event) {
            var dropdown = document.querySelector(".user-dropdown");
            var menu = document.getElementById("userDropdownMenu");
            if (dropdown && menu && !dropdown.contains(event.target)) {
                menu.style.display = "none";
            }
        });
        </script>';
    }
    
    /**
     * Render user section - handles both authenticated and guest states
     * This is the SINGLE method that determines user display logic
     */
    public function renderUserSection(): string {
        if ($this->isLoggedIn && $this->currentUser) {
            // Authenticated user with logout dropdown
            $username = htmlspecialchars($this->currentUser['username']);
            $adminBadge = $this->isAdmin ? ' <span class="admin-badge">ADMIN</span>' : '';
            
            return '
            <div class="user-dropdown">
                <button class="user-dropdown-toggle" onclick="toggleUserDropdown()">
                    👤 ' . $username . $adminBadge . ' ▼
                </button>
                <div class="user-dropdown-menu" id="userDropdownMenu">
                    <a href="index.php" class="dropdown-item">🏠 Dashboard</a>
                    <a href="portfolios.php" class="dropdown-item">📈 Portfolios</a>
                    <a href="trades.php" class="dropdown-item">📋 Trades</a>
                    <div class="dropdown-divider"></div>
                    <a href="system_status.php" class="dropdown-item">📊 System Status</a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item">🚪 Logout</a>
                </div>
            </div>';
        } else {
            // Guest user with login dropdown
            return '
            <div class="user-dropdown">
                <button class="user-dropdown-toggle" onclick="toggleUserDropdown()">
                    👤 Guest ▼
                </button>
                <div class="user-dropdown-menu" id="userDropdownMenu">
                    <a href="login.php" class="dropdown-item">🚪 Login</a>
                    <a href="register.php" class="dropdown-item">📝 Register</a>
                    <div class="dropdown-divider"></div>
                    <a href="system_status.php" class="dropdown-item">📊 System Status</a>
                </div>
            </div>';
        }
    }
    
    /**
     * Get menu items based on user authentication status
     */
    public function getMenuItems(string $currentPage = ''): array {
        $items = [];
        
        if ($this->isLoggedIn) {
            $items[] = [
                'url' => 'index.php',
                'label' => '🏠 Dashboard',
                'active' => in_array($currentPage, ['index.php', 'dashboard', ''])
            ];
            
            $items[] = [
                'url' => 'portfolios.php', 
                'label' => '📈 Portfolios',
                'active' => $currentPage === 'portfolios.php'
            ];
            
            $items[] = [
                'url' => 'trades.php',
                'label' => '📋 Trades', 
                'active' => $currentPage === 'trades.php'
            ];
            
            $items[] = [
                'url' => 'analytics.php',
                'label' => '📊 Analytics',
                'active' => $currentPage === 'analytics.php'
            ];
            
            // Admin-only items
            if ($this->isAdmin) {
                $items[] = [
                    'url' => 'admin_users.php',
                    'label' => '👥 Users',
                    'active' => $currentPage === 'admin_users.php'
                ];
                
                $items[] = [
                    'url' => 'database.php',
                    'label' => '🗄️ Database',
                    'active' => $currentPage === 'database.php'
                ];
            }
        }
        
        return $items;
    }
    
    /**
     * Render complete navigation header - single method to rule them all
     */
    public function renderNavigationHeader(string $title = 'Enhanced Trading System', string $currentPage = ''): string {
        $adminClass = $this->isAdmin ? ' admin' : '';
        $menuItems = $this->getMenuItems($currentPage);
        
        $html = '<div class="nav-header' . $adminClass . '">';
        $html .= '<div class="nav-container">';
        $html .= '<h1 class="nav-title">' . htmlspecialchars($title) . '</h1>';
        $html .= '<div class="nav-user">';
        
        // Navigation links
        if (!empty($menuItems)) {
            $html .= '<div class="nav-links">';
            foreach ($menuItems as $item) {
                $activeClass = $item['active'] ? ' active' : '';
                $html .= '<a href="' . htmlspecialchars($item['url']) . '" class="nav-link' . $activeClass . '">';
                $html .= htmlspecialchars($item['label']);
                $html .= '</a>';
            }
            $html .= '</div>';
        }
        
        // User section (dropdown for both auth states)
        $html .= $this->renderUserSection();
        
        $html .= '</div>'; // nav-user
        $html .= '</div>'; // nav-container  
        $html .= '</div>'; // nav-header
        
        // Include CSS and JS
        $html .= $this->getNavigationCSS();
        $html .= $this->getNavigationScript();
        
        return $html;
    }
    
    /**
     * Simple method for other components to get user info
     */
    public function isUserLoggedIn(): bool {
        return $this->isLoggedIn;
    }
    
    public function isUserAdmin(): bool {
        return $this->isAdmin;  
    }
    
    public function getCurrentUser(): ?array {
        return $this->currentUser;
    }
}
?>