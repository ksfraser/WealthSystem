<?php
/**
 * Navigation Header - Consistent navigation for all pages
 */

// This file should be included after auth_check.php on protected pages
if (!isset($userAuth)) {
    // For pages that don't require auth, create a minimal auth object for nav
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    try {
        require_once __DIR__ . '/UserAuthDAO.php';
        $userAuth = new UserAuthDAO();
    } catch (Exception $e) {
        // If auth fails, just continue without user info
        $userAuth = null;
    }
}

function renderNavigationHeader($pageTitle = 'Trading System') {
    global $userAuth, $currentUser;
    
    $isLoggedIn = $userAuth && $userAuth->isLoggedIn();
    $isAdmin = $isLoggedIn && $userAuth->isAdmin();
    $user = $isLoggedIn ? ($currentUser ?? $userAuth->getCurrentUser()) : null;
    
    echo '<style>
        .nav-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        /* Admin header styling - red gradient */
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
        .admin-badge {
            background: #ffffff;
            color: #dc3545;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            border: 1px solid #ffffff;
        }
        /* Admin badge styling for red header */
        .nav-header.admin .admin-badge {
            background: #ffffff;
            color: #dc3545;
            border: 2px solid #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .user-info {
            font-size: 14px;
        }
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 10px;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>';
    
    echo '<div class="nav-header' . ($isAdmin ? ' admin' : '') . '">';
    echo '<div class="nav-container">';
    echo '<h1 class="nav-title">' . htmlspecialchars($pageTitle) . '</h1>';
    
    echo '<div class="nav-user">';
    
    if ($isLoggedIn && $user) {
        echo '<div class="user-info">';
        echo 'Welcome, ' . htmlspecialchars($user['username']);
        if ($isAdmin) {
            echo ' <span class="admin-badge">ADMIN</span>';
        }
        echo '</div>';
        
        echo '<div class="nav-links">';
        echo '<a href="index.php">Dashboard</a>';
        
        if ($isAdmin) {
            echo '<a href="admin_users.php">Users</a>';
            echo '<a href="system_status.php">System</a>';
        }
        
        echo '<a href="logout.php">Logout</a>';
        echo '</div>';
    } else {
        echo '<div class="nav-links">';
        echo '<a href="login.php">Login</a>';
        echo '<a href="register.php">Register</a>';
        echo '</div>';
    }
    
    echo '</div>'; // nav-user
    echo '</div>'; // nav-container
    echo '</div>'; // nav-header
}
?>
