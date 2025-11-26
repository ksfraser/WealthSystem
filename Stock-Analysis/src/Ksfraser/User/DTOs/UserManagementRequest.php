<?php

namespace Ksfraser\User\DTOs;

/**
 * UserManagementRequest - Data Transfer Object for form submissions
 * Encapsulates all POST data for user management operations
 */
class UserManagementRequest {
    public $action;
    public $username;
    public $email;
    public $password;
    public $isAdmin;
    public $userId;
    
    public function __construct() {
        $this->action = $this->determineAction();
        $this->username = trim($_POST['username'] ?? '');
        $this->email = trim($_POST['email'] ?? '');
        $this->password = $_POST['password'] ?? '';
        $this->isAdmin = isset($_POST['is_admin']);
        $this->userId = (int)($_POST['user_id'] ?? 0);
    }
    
    private function determineAction() {
        if (isset($_POST['create_user'])) return 'create';
        if (isset($_POST['delete_user'])) return 'delete';
        if (isset($_POST['toggle_admin'])) return 'toggle_admin';
        return null;
    }
    
    public function hasAction() {
        return $this->action !== null;
    }
    
    public function isCreateAction() {
        return $this->action === 'create';
    }
    
    public function isDeleteAction() {
        return $this->action === 'delete';
    }
    
    public function isToggleAdminAction() {
        return $this->action === 'toggle_admin';
    }
}
