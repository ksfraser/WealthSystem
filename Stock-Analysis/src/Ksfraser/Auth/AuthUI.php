<?php

namespace Ksfraser\Auth;

/**
 * Authentication UI Helper
 * 
 * Provides HTML components and utilities for authentication forms
 * 
 * @package Ksfraser\Auth
 */
class AuthUI
{
    private $authManager;
    
    public function __construct(AuthManager $authManager = null)
    {
        $this->authManager = $authManager;
    }
    
    /**
     * Generate login form HTML
     */
    public function loginForm(array $options = []): string
    {
        $defaults = [
            'action' => $_SERVER['REQUEST_URI'],
            'method' => 'POST',
            'class' => 'auth-form',
            'show_register_link' => true,
            'register_url' => 'register.php',
            'username_label' => 'Username or Email',
            'password_label' => 'Password',
            'submit_text' => 'Login',
            'show_csrf' => true
        ];
        
        $options = array_merge($defaults, $options);
        
        $csrfField = '';
        if ($options['show_csrf'] && $this->authManager) {
            $token = $this->authManager->getCSRFToken();
            $csrfField = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
        }
        
        $registerLink = '';
        if ($options['show_register_link']) {
            $registerLink = '<p class="auth-link">Don\'t have an account? <a href="' . htmlspecialchars($options['register_url']) . '">Register here</a></p>';
        }
        
        return '
        <form action="' . htmlspecialchars($options['action']) . '" method="' . $options['method'] . '" class="' . htmlspecialchars($options['class']) . '">
            ' . $csrfField . '
            <div class="form-group">
                <label for="username">' . htmlspecialchars($options['username_label']) . ':</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">' . htmlspecialchars($options['password_label']) . ':</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <button type="submit" name="login">' . htmlspecialchars($options['submit_text']) . '</button>
            </div>
            ' . $registerLink . '
        </form>';
    }
    
    /**
     * Generate registration form HTML
     */
    public function registerForm(array $options = []): string
    {
        $defaults = [
            'action' => $_SERVER['REQUEST_URI'],
            'method' => 'POST',
            'class' => 'auth-form',
            'show_login_link' => true,
            'login_url' => 'login.php',
            'username_label' => 'Username',
            'email_label' => 'Email',
            'password_label' => 'Password',
            'confirm_password_label' => 'Confirm Password',
            'submit_text' => 'Register',
            'show_csrf' => true,
            'show_admin_checkbox' => false
        ];
        
        $options = array_merge($defaults, $options);
        
        $csrfField = '';
        if ($options['show_csrf'] && $this->authManager) {
            $token = $this->authManager->getCSRFToken();
            $csrfField = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
        }
        
        $loginLink = '';
        if ($options['show_login_link']) {
            $loginLink = '<p class="auth-link">Already have an account? <a href="' . htmlspecialchars($options['login_url']) . '">Login here</a></p>';
        }
        
        $adminCheckbox = '';
        if ($options['show_admin_checkbox']) {
            $adminCheckbox = '
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_admin" value="1"> Admin User
                </label>
            </div>';
        }
        
        return '
        <form action="' . htmlspecialchars($options['action']) . '" method="' . $options['method'] . '" class="' . htmlspecialchars($options['class']) . '">
            ' . $csrfField . '
            <div class="form-group">
                <label for="username">' . htmlspecialchars($options['username_label']) . ':</label>
                <input type="text" id="username" name="username" required minlength="3" maxlength="64">
            </div>
            <div class="form-group">
                <label for="email">' . htmlspecialchars($options['email_label']) . ':</label>
                <input type="email" id="email" name="email" required maxlength="128">
            </div>
            <div class="form-group">
                <label for="password">' . htmlspecialchars($options['password_label']) . ':</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
            <div class="form-group">
                <label for="confirm_password">' . htmlspecialchars($options['confirm_password_label']) . ':</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
            </div>
            ' . $adminCheckbox . '
            <div class="form-group">
                <button type="submit" name="register">' . htmlspecialchars($options['submit_text']) . '</button>
            </div>
            ' . $loginLink . '
        </form>';
    }
    
    /**
     * Generate password change form HTML
     */
    public function passwordChangeForm(array $options = []): string
    {
        $defaults = [
            'action' => $_SERVER['REQUEST_URI'],
            'method' => 'POST',
            'class' => 'auth-form',
            'current_password_label' => 'Current Password',
            'new_password_label' => 'New Password',
            'confirm_password_label' => 'Confirm New Password',
            'submit_text' => 'Change Password',
            'show_csrf' => true
        ];
        
        $options = array_merge($defaults, $options);
        
        $csrfField = '';
        if ($options['show_csrf'] && $this->authManager) {
            $token = $this->authManager->getCSRFToken();
            $csrfField = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
        }
        
        return '
        <form action="' . htmlspecialchars($options['action']) . '" method="' . $options['method'] . '" class="' . htmlspecialchars($options['class']) . '">
            ' . $csrfField . '
            <div class="form-group">
                <label for="current_password">' . htmlspecialchars($options['current_password_label']) . ':</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">' . htmlspecialchars($options['new_password_label']) . ':</label>
                <input type="password" id="new_password" name="new_password" required minlength="8">
            </div>
            <div class="form-group">
                <label for="confirm_password">' . htmlspecialchars($options['confirm_password_label']) . ':</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
            </div>
            <div class="form-group">
                <button type="submit" name="change_password">' . htmlspecialchars($options['submit_text']) . '</button>
            </div>
        </form>';
    }
    
    /**
     * Generate user info display HTML
     */
    public function userInfo(array $user = null, array $options = []): string
    {
        if (!$user && $this->authManager) {
            $user = $this->authManager->getCurrentUser();
        }
        
        if (!$user) {
            return '<p>No user information available</p>';
        }
        
        $defaults = [
            'class' => 'user-info',
            'show_logout' => true,
            'logout_url' => 'logout.php',
            'show_edit_link' => true,
            'edit_url' => 'profile.php',
            'show_admin_badge' => true
        ];
        
        $options = array_merge($defaults, $options);
        
        $adminBadge = '';
        if ($options['show_admin_badge'] && !empty($user['is_admin'])) {
            $adminBadge = ' <span class="admin-badge">Admin</span>';
        }
        
        $logoutLink = '';
        if ($options['show_logout']) {
            $logoutLink = '<a href="' . htmlspecialchars($options['logout_url']) . '" class="logout-link">Logout</a>';
        }
        
        $editLink = '';
        if ($options['show_edit_link']) {
            $editLink = '<a href="' . htmlspecialchars($options['edit_url']) . '" class="edit-link">Edit Profile</a>';
        }
        
        return '
        <div class="' . htmlspecialchars($options['class']) . '">
            <h3>Welcome, ' . htmlspecialchars($user['username']) . $adminBadge . '</h3>
            <p><strong>Email:</strong> ' . htmlspecialchars($user['email']) . '</p>
            <div class="user-actions">
                ' . $editLink . '
                ' . $logoutLink . '
            </div>
        </div>';
    }
    
    /**
     * Generate navigation menu with auth-aware items
     */
    public function navigationMenu(array $menuItems = [], array $options = []): string
    {
        $defaults = [
            'class' => 'auth-nav',
            'login_url' => 'login.php',
            'register_url' => 'register.php',
            'logout_url' => 'logout.php',
            'dashboard_url' => 'dashboard.php',
            'profile_url' => 'profile.php'
        ];
        
        $options = array_merge($defaults, $options);
        
        $isLoggedIn = $this->authManager ? $this->authManager->isLoggedIn() : false;
        $isAdmin = $this->authManager ? $this->authManager->isAdmin() : false;
        $user = $this->authManager ? $this->authManager->getCurrentUser() : null;
        
        $html = '<nav class="' . htmlspecialchars($options['class']) . '"><ul>';
        
        // Add custom menu items
        foreach ($menuItems as $item) {
            $requireAuth = $item['require_auth'] ?? false;
            $requireAdmin = $item['require_admin'] ?? false;
            
            if ($requireAdmin && !$isAdmin) continue;
            if ($requireAuth && !$isLoggedIn) continue;
            if (isset($item['require_guest']) && $item['require_guest'] && $isLoggedIn) continue;
            
            $html .= '<li><a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['label']) . '</a></li>';
        }
        
        // Add auth-specific items
        if ($isLoggedIn) {
            if ($user) {
                $html .= '<li><a href="' . htmlspecialchars($options['dashboard_url']) . '">Dashboard</a></li>';
                $html .= '<li><a href="' . htmlspecialchars($options['profile_url']) . '">Profile</a></li>';
                $html .= '<li><span class="user-greeting">Hello, ' . htmlspecialchars($user['username']) . '</span></li>';
            }
            $html .= '<li><a href="' . htmlspecialchars($options['logout_url']) . '">Logout</a></li>';
        } else {
            $html .= '<li><a href="' . htmlspecialchars($options['login_url']) . '">Login</a></li>';
            $html .= '<li><a href="' . htmlspecialchars($options['register_url']) . '">Register</a></li>';
        }
        
        $html .= '</ul></nav>';
        
        return $html;
    }
    
    /**
     * Display error message HTML
     */
    public function errorMessage(string $message, array $options = []): string
    {
        $defaults = [
            'class' => 'error-message',
            'dismissible' => false
        ];
        
        $options = array_merge($defaults, $options);
        
        $dismissButton = '';
        if ($options['dismissible']) {
            $dismissButton = '<button type="button" class="dismiss" onclick="this.parentElement.style.display=\'none\'">&times;</button>';
        }
        
        return '<div class="' . htmlspecialchars($options['class']) . '">' . 
               htmlspecialchars($message) . $dismissButton . '</div>';
    }
    
    /**
     * Display success message HTML
     */
    public function successMessage(string $message, array $options = []): string
    {
        $defaults = [
            'class' => 'success-message',
            'dismissible' => false
        ];
        
        $options = array_merge($defaults, $options);
        
        $dismissButton = '';
        if ($options['dismissible']) {
            $dismissButton = '<button type="button" class="dismiss" onclick="this.parentElement.style.display=\'none\'">&times;</button>';
        }
        
        return '<div class="' . htmlspecialchars($options['class']) . '">' . 
               htmlspecialchars($message) . $dismissButton . '</div>';
    }
    
    /**
     * Generate basic CSS for auth components
     */
    public function getCSS(): string
    {
        return '
        <style>
        .auth-form {
            max-width: 400px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        
        .auth-form .form-group {
            margin-bottom: 15px;
        }
        
        .auth-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .auth-form input[type="text"],
        .auth-form input[type="email"],
        .auth-form input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            box-sizing: border-box;
        }
        
        .auth-form button {
            width: 100%;
            padding: 10px;
            background: #007cba;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .auth-form button:hover {
            background: #005a87;
        }
        
        .auth-link {
            text-align: center;
            margin-top: 15px;
        }
        
        .user-info {
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        
        .admin-badge {
            background: #dc3545;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .user-actions {
            margin-top: 15px;
        }
        
        .user-actions a {
            margin-right: 10px;
            text-decoration: none;
            color: #007cba;
        }
        
        .error-message {
            color: #dc3545;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
        }
        
        .success-message {
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
        }
        
        .auth-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 15px;
        }
        
        .auth-nav li {
            display: inline;
        }
        
        .auth-nav a {
            text-decoration: none;
            color: #007cba;
            padding: 5px 10px;
        }
        
        .auth-nav a:hover {
            background: #f0f0f0;
            border-radius: 3px;
        }
        
        .user-greeting {
            color: #666;
            font-style: italic;
        }
        </style>';
    }
}
