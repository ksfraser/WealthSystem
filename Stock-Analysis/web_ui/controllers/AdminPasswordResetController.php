<?php
// Controller for admin password reset (SRP/MVC)
require_once __DIR__ . '/../web_ui/UserAuthDAO.php';
require_once __DIR__ . '/../web_ui/views/AdminPasswordResetView.php';

class AdminPasswordResetController {
    private $auth;
    public function __construct() {
        $this->auth = new UserAuthDAO();
    }

    public function resetUserPassword() {
        $error = '';
        $message = '';
        // Only allow admins
        if (!$this->auth->isAdmin()) {
            $error = 'Access denied: Admins only.';
            return AdminPasswordResetView::render([], $error, $message);
        }
        $users = $this->auth->getAllUsers(200, 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            if (!$userId || !$newPassword || !$confirm) {
                $error = 'Please select a user and enter the new password.';
            } elseif ($newPassword !== $confirm) {
                $error = 'Passwords do not match.';
            } else {
                try {
                    $this->auth->adminResetUserPassword($userId, $newPassword);
                    $message = 'Password reset successfully for user ID ' . $userId;
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }
        return AdminPasswordResetView::render($users, $error, $message);
    }
}
