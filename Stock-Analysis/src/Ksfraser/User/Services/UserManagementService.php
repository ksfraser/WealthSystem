<?php

namespace Ksfraser\User\Services;

/**
 * UserManagementService - Business logic for user management operations
 * Handles create, delete, and admin status toggle operations
 */
class UserManagementService {
    private $userAuth;
    private $currentUser;
    
    public function __construct($userAuth, $currentUser) {
        $this->userAuth = $userAuth;
        $this->currentUser = $currentUser;
    }
    
    public function createUser($request) {
        try {
            $userId = $this->userAuth->registerUser(
                $request->username,
                $request->email,
                $request->password,
                $request->isAdmin
            );
            
            if ($userId) {
                return ['User created successfully with ID: ' . $userId, 'success'];
            } else {
                return ['Failed to create user', 'error'];
            }
        } catch (\Exception $e) {
            return ['Error creating user: ' . $e->getMessage(), 'error'];
        }
    }
    
    public function deleteUser($request) {
        if ($request->userId === $this->currentUser['id']) {
            return ['Cannot delete your own account!', 'error'];
        }
        
        try {
            if ($this->userAuth->deleteUser($request->userId)) {
                return ['User deleted successfully!', 'success'];
            } else {
                return ['Failed to delete user.', 'error'];
            }
        } catch (\Exception $e) {
            return ['Error deleting user: ' . $e->getMessage(), 'error'];
        }
    }
    
    public function toggleAdminStatus($request) {
        if ($request->userId === $this->currentUser['id']) {
            return ['Cannot modify your own admin status!', 'error'];
        }
        
        $newAdminStatus = $request->isAdmin ? 1 : 0;
        
        try {
            if ($this->userAuth->updateUserAdminStatus($request->userId, $newAdminStatus)) {
                $actionText = $newAdminStatus ? 'promoted to admin' : 'changed to regular user';
                return ["User {$actionText} successfully!", 'success'];
            } else {
                return ['Failed to update user admin status.', 'error'];
            }
        } catch (\Exception $e) {
            return ['Error updating user: ' . $e->getMessage(), 'error'];
        }
    }
    
    /**
     * Get all users with error handling
     */
    public function getAllUsers() {
        try {
            return $this->userAuth->getAllUsers();
        } catch (\Exception $e) {
            throw new \Exception('Error loading users: ' . $e->getMessage());
        }
    }
    
    /**
     * Get user statistics
     */
    public function getUserStatistics($users) {
        $totalUsers = count($users);
        $adminUsers = count(array_filter($users, function($u) { return $u['is_admin']; }));
        $activeUsers = count(array_filter($users, function($u) { return !empty($u['last_login']); }));
        
        return [
            'total' => $totalUsers,
            'admins' => $adminUsers,
            'active' => $activeUsers
        ];
    }
}
