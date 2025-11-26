<?php

namespace Ksfraser\Auth\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\Database\EnhancedUserAuthDAO;
use Ksfraser\Database\EnhancedDbManager;

/**
 * Test suite for EnhancedUserAuthDAO class
 */
class EnhancedUserAuthDAOTest extends TestCase
{
    /** @var EnhancedUserAuthDAO */
    private $auth;

    protected function setUp(): void
    {
        // Reset database connection to use SQLite for testing
        EnhancedDbManager::resetConnection();
        
        // Create a fresh auth instance
        $this->auth = new EnhancedUserAuthDAO();
        
        // Clear any existing users for clean test state
        $this->cleanupUsers();
    }

    protected function tearDown(): void
    {
        // Clean up after tests
        $this->cleanupUsers();
        EnhancedDbManager::resetConnection();
    }

    private function cleanupUsers(): void
    {
        try {
            EnhancedDbManager::execute("DELETE FROM users WHERE username LIKE 'test_%'");
        } catch (\Exception $e) {
            // Ignore errors during cleanup
        }
    }

    public function testRegisterUser()
    {
        $userId = $this->auth->registerUser('test_user', 'test@example.com', 'password123');
        
        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);
        
        // Verify user was created
        $user = $this->auth->getUserById($userId);
        
        $this->assertNotNull($user);
        $this->assertEquals('test_user', $user['username']);
        $this->assertEquals('test@example.com', $user['email']);
        $this->assertArrayHasKey('is_admin', $user);
        $this->assertFalse((bool)$user['is_admin']); // Should default to false
    }

    public function testRegisterDuplicateUser()
    {
        // Register first user
        $userId1 = $this->auth->registerUser('test_duplicate', 'test1@example.com', 'password123');
        $this->assertIsInt($userId1);
        
        // Try to register with same username
        $userId2 = $this->auth->registerUser('test_duplicate', 'test2@example.com', 'password456');
        $this->assertFalse($userId2);
    }

    public function testRegisterDuplicateEmail()
    {
        // Register first user
        $userId1 = $this->auth->registerUser('test_user1', 'duplicate@example.com', 'password123');
        $this->assertIsInt($userId1);
        
        // Try to register with same email
        $userId2 = $this->auth->registerUser('test_user2', 'duplicate@example.com', 'password456');
        $this->assertFalse($userId2);
    }

    public function testAuthenticateUser()
    {
        // Register a test user
        $userId = $this->auth->registerUser('test_auth', 'auth@example.com', 'correct_password');
        $this->assertIsInt($userId);
        
        // Test correct authentication
        $result = $this->auth->authenticateUser('test_auth', 'correct_password');
        $this->assertIsArray($result);
        $this->assertEquals('test_auth', $result['username']);
        $this->assertEquals('auth@example.com', $result['email']);
        
        // Test incorrect password
        $result = $this->auth->authenticateUser('test_auth', 'wrong_password');
        $this->assertFalse($result);
        
        // Test non-existent user
        $result = $this->auth->authenticateUser('nonexistent_user', 'any_password');
        $this->assertFalse($result);
    }

    public function testGetAllUsers()
    {
        // Register multiple test users
        $userId1 = $this->auth->registerUser('test_user1', 'user1@example.com', 'password1');
        $userId2 = $this->auth->registerUser('test_user2', 'user2@example.com', 'password2');
        $userId3 = $this->auth->registerUser('test_user3', 'user3@example.com', 'password3');
        
        $this->assertIsInt($userId1);
        $this->assertIsInt($userId2);
        $this->assertIsInt($userId3);
        
        $users = $this->auth->getAllUsers();
        
        $this->assertIsArray($users);
        $this->assertGreaterThanOrEqual(3, count($users));
        
        // Check that test users are in the list
        $usernames = array_column($users, 'username');
        $this->assertContains('test_user1', $usernames);
        $this->assertContains('test_user2', $usernames);
        $this->assertContains('test_user3', $usernames);
        
        // Verify structure of user data
        foreach ($users as $user) {
            $this->assertArrayHasKey('id', $user);
            $this->assertArrayHasKey('username', $user);
            $this->assertArrayHasKey('email', $user);
            $this->assertArrayHasKey('is_admin', $user);
            $this->assertArrayHasKey('created_at', $user);
            $this->assertArrayNotHasKey('password_hash', $user); // Should be filtered out
        }
    }

    public function testGetUserById()
    {
        // Register a test user
        $userId = $this->auth->registerUser('test_getbyid', 'getbyid@example.com', 'password123');
        $this->assertIsInt($userId);
        
        // Test getUserById
        $retrievedUser = $this->auth->getUserById($userId);
        
        $this->assertNotNull($retrievedUser);
        $this->assertEquals($userId, $retrievedUser['id']);
        $this->assertEquals('test_getbyid', $retrievedUser['username']);
        $this->assertEquals('getbyid@example.com', $retrievedUser['email']);
        $this->assertArrayNotHasKey('password_hash', $retrievedUser);
    }

    public function testGetUserByIdNotFound()
    {
        $result = $this->auth->getUserById(99999);
        $this->assertNull($result);
    }

    public function testUpdateUserAdminStatus()
    {
        // Register a test user
        $userId = $this->auth->registerUser('test_admin_update', 'adminupdate@example.com', 'password123');
        $this->assertIsInt($userId);
        
        // Verify user is not admin initially
        $user = $this->auth->getUserById($userId);
        $this->assertFalse((bool)$user['is_admin']);
        
        // Update to admin
        $result = $this->auth->updateUserAdminStatus($userId, true);
        $this->assertTrue($result);
        
        // Verify the update
        $updatedUser = $this->auth->getUserById($userId);
        $this->assertTrue((bool)$updatedUser['is_admin']);
        
        // Update back to non-admin
        $result = $this->auth->updateUserAdminStatus($userId, false);
        $this->assertTrue($result);
        
        // Verify the update
        $updatedUser = $this->auth->getUserById($userId);
        $this->assertFalse((bool)$updatedUser['is_admin']);
    }

    public function testDeleteUser()
    {
        // Register a test user
        $userId = $this->auth->registerUser('test_delete', 'delete@example.com', 'password123');
        $this->assertIsInt($userId);
        
        // Verify user exists
        $user = $this->auth->getUserById($userId);
        $this->assertNotNull($user);
        
        // Delete the user
        $result = $this->auth->deleteUser($userId);
        $this->assertTrue($result);
        
        // Verify the user is gone
        $deletedUser = $this->auth->getUserById($userId);
        $this->assertNull($deletedUser);
    }

    public function testPasswordHashing()
    {
        // Register user and verify password is hashed
        $this->auth->registerUser('test_hash', 'hash@example.com', 'plaintext_password');
        
        // Get user data directly from database to check hash
        $user = EnhancedDbManager::fetchOne(
            "SELECT password_hash FROM users WHERE username = ?",
            ['test_hash']
        );
        
        $this->assertNotNull($user);
        $this->assertNotEmpty($user['password_hash']);
        $this->assertNotEquals('plaintext_password', $user['password_hash']);
        
        // Verify password can be verified
        $this->assertTrue(password_verify('plaintext_password', $user['password_hash']));
        $this->assertFalse(password_verify('wrong_password', $user['password_hash']));
    }

    public function testEmptyUsernameHandling()
    {
        $result = $this->auth->registerUser('', 'empty@example.com', 'password123');
        $this->assertFalse($result);
    }

    public function testEmptyEmailHandling()
    {
        $result = $this->auth->registerUser('test_empty_email', '', 'password123');
        $this->assertFalse($result);
    }

    public function testEmptyPasswordHandling()
    {
        $result = $this->auth->registerUser('test_empty_password', 'empty_pwd@example.com', '');
        $this->assertFalse($result);
    }

    public function testUserRegistrationValidation()
    {
        // Test with whitespace-only values
        $result1 = $this->auth->registerUser('   ', 'test@example.com', 'password123');
        $this->assertFalse($result1);
        
        $result2 = $this->auth->registerUser('test_user', '   ', 'password123');
        $this->assertFalse($result2);
        
        $result3 = $this->auth->registerUser('test_user', 'test@example.com', '   ');
        $this->assertFalse($result3);
    }

    public function testSessionMethods()
    {
        // Register and authenticate a user
        $userId = $this->auth->registerUser('test_session', 'session@example.com', 'password123');
        $this->assertIsInt($userId);
        
        $authenticatedUser = $this->auth->authenticateUser('test_session', 'password123');
        $this->assertIsArray($authenticatedUser);
        
        // Test initial state (should not be logged in)
        $this->assertFalse($this->auth->isLoggedIn());
        $this->assertFalse($this->auth->isAdmin());
        $this->assertNull($this->auth->getCurrentUserId());
        
        // Login user
        $this->auth->loginUser($authenticatedUser);
        
        // Test logged in state
        $this->assertTrue($this->auth->isLoggedIn());
        $this->assertEquals($userId, $this->auth->getCurrentUserId());
        
        // Test logout
        $this->auth->logoutUser();
        $this->assertFalse($this->auth->isLoggedIn());
    }

    public function testAdminUserRegistration()
    {
        // Register admin user
        $adminId = $this->auth->registerUser('test_admin', 'admin@example.com', 'password123', true);
        $this->assertIsInt($adminId);
        
        $adminUser = $this->auth->getUserById($adminId);
        $this->assertTrue((bool)$adminUser['is_admin']);
    }

    public function testGetDatabaseInfo()
    {
        $info = $this->auth->getDatabaseInfo();
        
        $this->assertIsArray($info);
        $this->assertArrayHasKey('driver', $info);
        $this->assertArrayHasKey('connection_class', $info);
        $this->assertNotEmpty($info['driver']);
        $this->assertNotEmpty($info['connection_class']);
    }
}
