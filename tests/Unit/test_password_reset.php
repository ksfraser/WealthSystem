<?php
// Unit tests for UserAuthDAO password reset features
require_once __DIR__ . '/../../web_ui/UserAuthDAO.php';

class UserAuthDAOTest_PasswordReset {
    private $auth;
    private $testUserId;
    private $testEmail;

    public function __construct() {
        $this->auth = new UserAuthDAO();
        $this->testEmail = 'reset_test_' . time() . '@example.com';
        $this->testUserId = $this->auth->registerUser('reset_test_' . time(), $this->testEmail, 'initialPass123!');
    }

    public function testInitiateAndResetPassword() {
        echo "Testing password reset token generation...\n";
        $token = $this->auth->initiatePasswordReset($this->testEmail);
        assert(strlen($token) === 64, 'Token should be 64 hex chars');
        echo "Token generated: $token\n";

        echo "Testing password reset with token...\n";
        $result = $this->auth->resetPasswordWithToken($token, 'NewPass123!');
        assert($result === true, 'Password reset should succeed');
        echo "Password reset successful.\n";
    }

    public function testAdminResetUserPassword() {
        echo "Testing admin password reset...\n";
        $result = $this->auth->adminResetUserPassword($this->testUserId, 'AdminResetPass123!');
        assert($result === true, 'Admin password reset should succeed');
        echo "Admin password reset successful.\n";
    }
}

// Run tests
$test = new UserAuthDAOTest_PasswordReset();
$test->testInitiateAndResetPassword();
$test->testAdminResetUserPassword();
echo "All password reset tests passed.\n";
