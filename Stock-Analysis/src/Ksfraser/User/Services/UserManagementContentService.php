<?php

namespace Ksfraser\User\Services;

use Ksfraser\UIRenderer\Factories\UiFactory;
use Ksfraser\User\DTOs\UserManagementRequest;

/**
 * User Management Content Service - Handles business logic for user management display
 */
class UserManagementContentService {
    private $userAuth;
    private $currentUser;
    private $isAdmin;
    private $message;
    private $messageType;
    
    public function __construct($userAuth, $currentUser, $isAdmin) {
        $this->userAuth = $userAuth;
        $this->currentUser = $currentUser;
        $this->isAdmin = $isAdmin;
    }
    
    /**
     * Create user management dashboard components
     */
    public function createUserManagementComponents($message = '', $messageType = '') {
        $this->message = $message;
        $this->messageType = $messageType;
        
        $components = [];
        
        // Message alert if any
        if ($this->message) {
            $components[] = $this->createMessageCard();
        }
        
        // User statistics
        $components[] = $this->createUserStatsCard();
        
        // Create new user form
        $components[] = $this->createNewUserForm();
        
        // User list table
        $components[] = $this->createUserListCard();
        
        return $components;
    }
    
    /**
     * Create message alert card
     */
    private function createMessageCard() {
        if ($this->messageType === 'success') {
            return UiFactory::createSuccessCard('Success', $this->message);
        } else {
            return UiFactory::createErrorCard('Error', $this->message);
        }
    }
    
    /**
     * Create user statistics cards
     */
    private function createUserStatsCard() {
        $totalUsers = $this->userAuth->getUserCount();
        $adminUsers = $this->userAuth->getAdminCount();
        $activeUsers = $this->userAuth->getActiveUserCount();
        
        $statsHtml = '
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-number">' . $totalUsers . '</div>
                    <div>Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . $adminUsers . '</div>
                    <div>Admin Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . $activeUsers . '</div>
                    <div>Active Users</div>
                </div>
            </div>
            <style>
                .stats-row {
                    display: flex;
                    gap: 20px;
                    margin-bottom: 20px;
                }
                .stat-card {
                    flex: 1;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 20px;
                    border-radius: 8px;
                    text-align: center;
                }
                .stat-number {
                    font-size: 2em;
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                @media (max-width: 768px) {
                    .stats-row {
                        flex-direction: column;
                    }
                }
            </style>
        ';
        
        return UiFactory::createCard('User Statistics', $statsHtml);
    }
    
    /**
     * Create new user form
     */
    private function createNewUserForm() {
        $formHtml = '
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_admin" value="1"> Admin User
                    </label>
                </div>
                <button type="submit" name="action" value="create" class="btn">Create User</button>
            </form>
            <style>
                .form-group {
                    margin-bottom: 15px;
                }
                .form-group label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: bold;
                }
                .form-group input {
                    width: 100%;
                    padding: 8px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    box-sizing: border-box;
                }
            </style>
        ';
        
        return UiFactory::createCard('Create New User', $formHtml);
    }
    
    /**
     * Create user list table
     */
    private function createUserListCard() {
        $users = $this->userAuth->getAllUsers();
        
        if (empty($users)) {
            return UiFactory::createCard('Existing Users', '<p>No users found.</p>');
        }
        
        // Prepare table data
        $tableData = [];
        $headers = ['Username', 'Email', 'Status', 'Actions'];
        
        foreach ($users as $user) {
            $status = $user['is_admin'] ? '<span class="admin-badge">Admin</span>' : 'User';
            $actions = $this->createUserActionsHtml($user);
            
            $tableData[] = [
                htmlspecialchars($user['username']),
                htmlspecialchars($user['email']),
                $status,
                $actions
            ];
        }
        
        return UiFactory::createDataCard('Existing Users', $tableData, $headers);
    }
    
    /**
     * Create action buttons HTML for user row
     */
    private function createUserActionsHtml($user) {
        if ($user['id'] == $this->currentUser['id']) {
            return '<em>Current User</em>';
        }
        
        $actions = '';
        
        // Toggle admin status
        if ($user['is_admin']) {
            $actions .= '
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" value="' . $user['id'] . '">
                    <button type="submit" name="action" value="toggle_admin" class="btn btn-warning" 
                            onclick="return confirm(\'Remove admin privileges from ' . htmlspecialchars($user['username']) . '?\')">
                        Remove Admin
                    </button>
                </form>
            ';
        } else {
            $actions .= '
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" value="' . $user['id'] . '">
                    <button type="submit" name="action" value="toggle_admin" class="btn">
                        Make Admin
                    </button>
                </form>
            ';
        }
        
        // Delete user
        $actions .= '
            <form method="POST" style="display: inline;">
                <input type="hidden" name="user_id" value="' . $user['id'] . '">
                <button type="submit" name="action" value="delete" class="btn btn-danger" 
                        onclick="return confirm(\'Are you sure you want to delete user ' . htmlspecialchars($user['username']) . '?\')">
                    Delete
                </button>
            </form>
            <style>
                .admin-badge {
                    background-color: #28a745;
                    color: white;
                    padding: 2px 8px;
                    border-radius: 12px;
                    font-size: 0.8em;
                }
                .actions {
                    white-space: nowrap;
                }
                .btn-warning {
                    background-color: #ffc107;
                    color: black;
                }
                .btn-danger {
                    background-color: #dc3545;
                }
            </style>
        ';
        
        return '<div class="actions">' . $actions . '</div>';
    }
}
