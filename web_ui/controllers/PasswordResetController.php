<?php
// Controller for user password reset flow (SRP/MVC)
require_once __DIR__ . '/UserAuthDAO.php';
require_once __DIR__ . '/views/PasswordResetViews.php';

class PasswordResetController {
    private $auth;
    public function __construct() {
        $this->auth = new UserAuthDAO();
    }

    // Handle forgot password request
    public function forgotPassword() {
        $error = '';
        $message = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            if (!$email) {
                $error = 'Please enter your email address.';
            } else {
                try {
                    $token = $this->auth->initiatePasswordReset($email);
                    // In production, send $token via email. For demo, show on screen.
                    $message = 'If your email is registered, a reset link has been sent.';
                    $message .= '<br><small>Demo token: ' . htmlspecialchars($token) . '</small>';
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }
        return ForgotPasswordView::render($error, $message);
    }

    // Handle reset password form
    public function resetPassword() {
        $error = '';
        $message = '';
        $token = $_GET['token'] ?? ($_POST['token'] ?? '');
        if (!$token) {
            $error = 'Invalid or missing reset token.';
            return ResetPasswordView::render('', $error, $message);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            if (!$newPassword || !$confirm) {
                $error = 'Please enter and confirm your new password.';
            } elseif ($newPassword !== $confirm) {
                $error = 'Passwords do not match.';
            } else {
                try {
                    $this->auth->resetPasswordWithToken($token, $newPassword);
                    $message = 'Password reset successful. You may now log in.';
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }
        return ResetPasswordView::render($token, $error, $message);
    }
}
