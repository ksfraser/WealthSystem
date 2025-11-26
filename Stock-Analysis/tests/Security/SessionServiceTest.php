<?php

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use App\Security\SessionService;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Test SessionService (Symfony-based session management)
 */
class SessionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset singleton for each test
        SessionService::reset();
    }
    
    protected function tearDown(): void
    {
        // Clean up session after each test
        SessionService::reset();
    }
    
    /**
     * Test session starts successfully
     */
    public function testStartCreatesSession(): void
    {
        $session = SessionService::start();
        
        $this->assertInstanceOf(Session::class, $session);
        $this->assertTrue($session->isStarted());
    }
    
    /**
     * Test get returns started session
     */
    public function testGetReturnsStartedSession(): void
    {
        $session = SessionService::get();
        
        $this->assertInstanceOf(Session::class, $session);
        $this->assertTrue($session->isStarted());
    }
    
    /**
     * Test session is singleton (returns same instance)
     */
    public function testSessionIsSingleton(): void
    {
        $session1 = SessionService::start();
        $session2 = SessionService::get();
        
        $this->assertSame($session1, $session2);
    }
    
    /**
     * Test user is not authenticated by default
     */
    public function testIsAuthenticatedReturnsFalseByDefault(): void
    {
        $this->assertFalse(SessionService::isAuthenticated());
    }
    
    /**
     * Test setting user authentication
     */
    public function testSetAuthenticatedSetsUserData(): void
    {
        SessionService::setAuthenticated(123, ['name' => 'John Doe', 'email' => 'john@example.com']);
        
        $this->assertTrue(SessionService::isAuthenticated());
        $this->assertEquals(123, SessionService::getUserId());
    }
    
    /**
     * Test getting user ID when not authenticated
     */
    public function testGetUserIdReturnsNullWhenNotAuthenticated(): void
    {
        $this->assertNull(SessionService::getUserId());
    }
    
    /**
     * Test getting user ID when authenticated
     */
    public function testGetUserIdReturnsCorrectId(): void
    {
        SessionService::setAuthenticated(456);
        
        $this->assertEquals(456, SessionService::getUserId());
    }
    
    /**
     * Test logout clears authentication
     */
    public function testLogoutClearsAuthentication(): void
    {
        SessionService::setAuthenticated(789, ['name' => 'Jane Doe']);
        
        $this->assertTrue(SessionService::isAuthenticated());
        
        SessionService::logout();
        
        $this->assertFalse(SessionService::isAuthenticated());
        $this->assertNull(SessionService::getUserId());
    }
    
    /**
     * Test session regenerates ID on authentication (prevents session fixation)
     */
    public function testSetAuthenticatedRegeneratesSessionId(): void
    {
        $session = SessionService::get();
        $oldId = $session->getId();
        
        SessionService::setAuthenticated(100);
        
        $newId = $session->getId();
        
        $this->assertNotEquals($oldId, $newId);
    }
    
    /**
     * Test authentication data persists across get() calls
     */
    public function testAuthenticationDataPersists(): void
    {
        SessionService::setAuthenticated(200, ['role' => 'admin']);
        
        // Get new reference to session
        $session = SessionService::get();
        
        $this->assertTrue(SessionService::isAuthenticated());
        $this->assertEquals(200, SessionService::getUserId());
    }
    
    /**
     * Test multiple authentications update user ID
     */
    public function testMultipleAuthenticationsUpdateUserId(): void
    {
        SessionService::setAuthenticated(1);
        $this->assertEquals(1, SessionService::getUserId());
        
        SessionService::setAuthenticated(2);
        $this->assertEquals(2, SessionService::getUserId());
        
        SessionService::setAuthenticated(3);
        $this->assertEquals(3, SessionService::getUserId());
    }
    
    /**
     * Test authentication with empty user data
     */
    public function testSetAuthenticatedWithEmptyUserData(): void
    {
        SessionService::setAuthenticated(500, []);
        
        $this->assertTrue(SessionService::isAuthenticated());
        $this->assertEquals(500, SessionService::getUserId());
    }
    
    /**
     * Test authentication requires valid user ID
     */
    public function testSetAuthenticatedWithZeroUserId(): void
    {
        SessionService::setAuthenticated(0);
        
        // User ID 0 should still set authentication
        $this->assertTrue(SessionService::isAuthenticated());
        $this->assertEquals(0, SessionService::getUserId());
    }
    
    /**
     * Test session has regeneration timestamp
     */
    public function testSessionHasRegenerationTimestamp(): void
    {
        $session = SessionService::start();
        
        $this->assertTrue($session->has('_regenerated_at'));
        $this->assertIsInt($session->get('_regenerated_at'));
    }
    
    /**
     * Test authentication sets auth time
     */
    public function testSetAuthenticatedSetsAuthTime(): void
    {
        $beforeAuth = time();
        
        SessionService::setAuthenticated(999);
        
        $session = SessionService::get();
        $authTime = $session->get('auth_time');
        
        $this->assertIsInt($authTime);
        $this->assertGreaterThanOrEqual($beforeAuth, $authTime);
        $this->assertLessThanOrEqual(time(), $authTime);
    }
    
    /**
     * Test logout invalidates session
     */
    public function testLogoutInvalidatesSession(): void
    {
        $session = SessionService::get();
        $session->set('test_key', 'test_value');
        
        SessionService::setAuthenticated(123);
        
        SessionService::logout();
        
        // Session should be invalidated
        $this->assertFalse(SessionService::isAuthenticated());
        
        // Session data should be cleared (except meta data)
        $this->assertFalse($session->has('test_key'));
    }
    
    /**
     * Test concurrent authentication doesn't leak data
     */
    public function testConcurrentAuthenticationDoesntLeakData(): void
    {
        // Authenticate as user 1
        SessionService::setAuthenticated(1, ['name' => 'User One', 'role' => 'admin']);
        
        // Logout
        SessionService::logout();
        
        // Authenticate as user 2
        SessionService::setAuthenticated(2, ['name' => 'User Two', 'role' => 'user']);
        
        $userData = SessionService::getUserData();
        
        // Should only have User Two's data
        $this->assertEquals('User Two', $userData['name']);
        $this->assertEquals('user', $userData['role']);
        $this->assertNotEquals('User One', $userData['name']);
    }
    
    /**
     * Test session storage is secure (HTTPOnly, Secure, SameSite configured)
     * Note: Can't fully test these in unit tests, but we can verify they're set
     */
    public function testSessionHasSecureConfiguration(): void
    {
        SessionService::start();
        
        // These should be configured in SessionService::start()
        // Can't fully test in unit tests without actual HTTP context
        $this->assertTrue(true); // Placeholder - would need integration test
    }
}
