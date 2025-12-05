<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use App\Security\SessionSecurity;

/**
 * Test suite for SessionSecurity
 * 
 * Tests session security hardening including:
 * - Secure session configuration
 * - Session regeneration
 * - Session hijacking prevention
 * - Session fixation prevention
 * - Session timeout management
 * 
 * @covers \App\Security\SessionSecurity
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SessionSecurityTest extends TestCase
{
    private SessionSecurity $security;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure clean session state
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        $this->security = new SessionSecurity();
    }

    /**
     * @test
     * @group session
     */
    public function itInitializesSecureSession(): void
    {
        $this->security->initialize();
        
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }

    /**
     * @test
     * @group session
     */
    public function itSetsSecureCookieParameters(): void
    {
        $this->security->initialize();
        
        $params = session_get_cookie_params();
        
        $this->assertTrue($params['httponly'], 'Cookie should be HTTP only');
        $this->assertEquals('Strict', $params['samesite'], 'Cookie should use SameSite=Strict');
        
        // Secure flag should be true in production (we can't test in CLI)
        // $this->assertTrue($params['secure'], 'Cookie should be secure in production');
    }

    /**
     * @test
     * @group session
     */
    public function itRegeneratesSessionId(): void
    {
        $this->security->initialize();
        
        $oldId = session_id();
        
        $this->security->regenerate();
        
        $newId = session_id();
        
        $this->assertNotEquals($oldId, $newId);
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }

    /**
     * @test
     * @group session
     */
    public function itPreservesSessionDataOnRegeneration(): void
    {
        $this->security->initialize();
        
        $_SESSION['user_id'] = 123;
        $_SESSION['username'] = 'testuser';
        
        $this->security->regenerate();
        
        $this->assertEquals(123, $_SESSION['user_id']);
        $this->assertEquals('testuser', $_SESSION['username']);
    }

    /**
     * @test
     * @group session
     */
    public function itDetectsSessionHijacking(): void
    {
        $this->security->initialize();
        
        // Simulate user agent change (potential hijacking)
        $_SESSION['_user_agent'] = 'Mozilla/5.0 Original';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Different';
        
        $this->assertFalse($this->security->validate());
    }

    /**
     * @test
     * @group session
     */
    public function itDetectsSessionTimeout(): void
    {
        $security = new SessionSecurity(['timeout' => 1800]); // 30 minutes
        $security->initialize();
        
        // Simulate old session
        $_SESSION['_last_activity'] = time() - 3600; // 1 hour ago
        
        $this->assertFalse($security->validate());
    }

    /**
     * @test
     * @group session
     */
    public function itAcceptsValidSession(): void
    {
        $this->security->initialize();
        
        // Session should be valid immediately after initialization
        // (fingerprint already set by initialize)
        $this->assertTrue($this->security->validate());
    }

    /**
     * @test
     * @group session
     */
    public function itUpdatesLastActivity(): void
    {
        $this->security->initialize();
        
        $initialTime = time() - 60;
        $_SESSION['_last_activity'] = $initialTime;
        
        sleep(1);
        
        $this->security->updateActivity();
        
        $this->assertGreaterThan($initialTime, $_SESSION['_last_activity']);
    }

    /**
     * @test
     * @group session
     */
    public function itDestroysSession(): void
    {
        $this->security->initialize();
        
        $_SESSION['user_id'] = 123;
        
        $this->security->destroy();
        
        $this->assertEmpty($_SESSION);
        $this->assertEquals(PHP_SESSION_NONE, session_status());
    }

    /**
     * @test
     * @group session
     */
    public function itPreventsSessionFixation(): void
    {
        // Start session with attacker-provided ID
        session_id('attacker_session_id');
        $this->security->initialize();
        
        $sessionId = session_id();
        
        // Session ID should be regenerated on initialization
        $this->assertNotEquals('attacker_session_id', $sessionId);
    }

    /**
     * @test
     * @group session
     */
    public function itSetsSessionNameSecurely(): void
    {
        $this->security->initialize();
        
        $sessionName = session_name();
        
        // Should not use default PHPSESSID
        $this->assertNotEquals('PHPSESSID', $sessionName);
        $this->assertStringNotContainsString('php', strtolower($sessionName));
    }

    /**
     * @test
     * @group session
     */
    public function itConfiguresSessionGarbageCollection(): void
    {
        $this->security->initialize(['timeout' => 3600]);
        
        $gcMaxLifetime = ini_get('session.gc_maxlifetime');
        
        $this->assertGreaterThanOrEqual(3600, (int)$gcMaxLifetime);
    }

    /**
     * @test
     * @group session
     */
    public function itStoresFingerprint(): void
    {
        $this->security->initialize();
        
        $this->assertArrayHasKey('_user_agent', $_SESSION);
        $this->assertArrayHasKey('_ip_address', $_SESSION);
        $this->assertArrayHasKey('_created_at', $_SESSION);
    }

    /**
     * @test
     * @group session
     */
    public function itValidatesIpAddress(): void
    {
        $security = new SessionSecurity(['check_ip' => true]);
        $security->initialize();
        
        // Change IP to simulate hijacking
        $_SESSION['_ip_address'] = '192.168.1.1';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.2';
        
        $this->assertFalse($security->validate());
    }

    /**
     * @test
     * @group session
     */
    public function itAllowsDisablingIpCheck(): void
    {
        $security = new SessionSecurity(['check_ip' => false]);
        $security->initialize();
        
        // Change IP (should be ignored with check_ip = false)
        $_SESSION['_ip_address'] = '192.168.1.1';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.2';
        
        // Should still validate (IP check disabled)
        $this->assertTrue($security->validate());
    }

    /**
     * @test
     * @group session
     */
    public function itRegeneratesOnPrivilegeEscalation(): void
    {
        $this->security->initialize();
        
        $oldId = session_id();
        
        $this->security->regenerateOnPrivilegeChange();
        
        $newId = session_id();
        
        $this->assertNotEquals($oldId, $newId);
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        parent::tearDown();
    }
}
