<?php

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use App\Security\CsrfManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Test CsrfManager (Symfony-based CSRF protection)
 */
class CsrfManagerTest extends TestCase
{
    private CsrfManager $csrfManager;
    private Session $session;
    
    protected function setUp(): void
    {
        // Use mock session storage for testing
        $this->session = new Session(new MockArraySessionStorage());
        $this->session->start();
        $this->csrfManager = new CsrfManager($this->session);
    }
    
    protected function tearDown(): void
    {
        $this->session->clear();
    }
    
    /**
     * Test token generation
     */
    public function testGetTokenGeneratesValidToken(): void
    {
        $token = $this->csrfManager->getToken();
        
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertGreaterThan(20, strlen($token)); // Should be reasonably long
    }
    
    /**
     * Test token consistency (same token on repeated calls)
     */
    public function testGetTokenReturnsSameTokenOnMultipleCalls(): void
    {
        $token1 = $this->csrfManager->getToken();
        $token2 = $this->csrfManager->getToken();
        
        $this->assertSame($token1, $token2);
    }
    
    /**
     * Test custom token IDs
     */
    public function testGetTokenWithCustomTokenId(): void
    {
        $token1 = $this->csrfManager->getToken('form1');
        $token2 = $this->csrfManager->getToken('form2');
        
        $this->assertNotSame($token1, $token2);
    }
    
    /**
     * Test valid token validation
     */
    public function testIsTokenValidWithValidToken(): void
    {
        $token = $this->csrfManager->getToken();
        
        $this->assertTrue($this->csrfManager->isTokenValid($token));
    }
    
    /**
     * Test invalid token validation
     */
    public function testIsTokenValidWithInvalidToken(): void
    {
        $this->assertFalse($this->csrfManager->isTokenValid('invalid-token'));
    }
    
    /**
     * Test empty token validation
     */
    public function testIsTokenValidWithEmptyToken(): void
    {
        $this->assertFalse($this->csrfManager->isTokenValid(''));
    }
    
    /**
     * Test token validation with custom token ID
     */
    public function testIsTokenValidWithCustomTokenId(): void
    {
        $token = $this->csrfManager->getToken('custom_form');
        
        $this->assertTrue($this->csrfManager->isTokenValid($token, 'custom_form'));
        $this->assertFalse($this->csrfManager->isTokenValid($token, 'different_form'));
    }
    
    /**
     * Test form field generation
     */
    public function testGetFormFieldGeneratesValidHtml(): void
    {
        $html = $this->csrfManager->getFormField();
        
        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="csrf_token"', $html);
        $this->assertStringContainsString('value="', $html);
    }
    
    /**
     * Test form field with custom token ID
     */
    public function testGetFormFieldWithCustomTokenId(): void
    {
        $html = $this->csrfManager->getFormField('my_form_token');
        
        $this->assertStringContainsString('name="my_form_token"', $html);
    }
    
    /**
     * Test form field escapes HTML properly
     */
    public function testGetFormFieldEscapesHtml(): void
    {
        $html = $this->csrfManager->getFormField();
        
        // Should not contain unescaped special characters
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }
    
    /**
     * Test request verification with valid token
     */
    public function testVerifyRequestWithValidToken(): void
    {
        $token = $this->csrfManager->getToken();
        $postData = ['csrf_token' => $token];
        
        $this->assertTrue($this->csrfManager->verifyRequest($postData));
    }
    
    /**
     * Test request verification with invalid token
     */
    public function testVerifyRequestWithInvalidToken(): void
    {
        $postData = ['csrf_token' => 'invalid-token'];
        
        $this->assertFalse($this->csrfManager->verifyRequest($postData));
    }
    
    /**
     * Test request verification with missing token
     */
    public function testVerifyRequestWithMissingToken(): void
    {
        $postData = [];
        
        $this->assertFalse($this->csrfManager->verifyRequest($postData));
    }
    
    /**
     * Test verify or fail with valid token
     */
    public function testVerifyOrFailWithValidToken(): void
    {
        $token = $this->csrfManager->getToken();
        $postData = ['csrf_token' => $token];
        
        // Should not throw exception
        $this->csrfManager->verifyOrFail($postData);
        $this->assertTrue(true); // If we get here, test passed
    }
    
    /**
     * Test verify or fail with invalid token throws exception
     */
    public function testVerifyOrFailWithInvalidTokenThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CSRF token validation failed');
        
        $postData = ['csrf_token' => 'invalid-token'];
        $this->csrfManager->verifyOrFail($postData);
    }
    
    /**
     * Test timing-safe token comparison (should not be vulnerable to timing attacks)
     */
    public function testTimingSafeComparison(): void
    {
        $token = $this->csrfManager->getToken();
        
        // These should take roughly the same time regardless of how many characters match
        $start1 = microtime(true);
        $this->csrfManager->isTokenValid('a' . substr($token, 1));
        $time1 = microtime(true) - $start1;
        
        $start2 = microtime(true);
        $this->csrfManager->isTokenValid(str_repeat('a', strlen($token)));
        $time2 = microtime(true) - $start2;
        
        // Times should be within 50% of each other (timing-safe comparison)
        $ratio = $time1 > $time2 ? $time1 / $time2 : $time2 / $time1;
        $this->assertLessThan(1.5, $ratio, 'Token comparison may be vulnerable to timing attacks');
    }
    
    /**
     * Test multiple concurrent forms with different token IDs
     */
    public function testMultipleFormsWithDifferentTokenIds(): void
    {
        $loginToken = $this->csrfManager->getToken('login_form');
        $registerToken = $this->csrfManager->getToken('register_form');
        $contactToken = $this->csrfManager->getToken('contact_form');
        
        // All tokens should be different
        $this->assertNotSame($loginToken, $registerToken);
        $this->assertNotSame($loginToken, $contactToken);
        $this->assertNotSame($registerToken, $contactToken);
        
        // Each should validate correctly
        $this->assertTrue($this->csrfManager->isTokenValid($loginToken, 'login_form'));
        $this->assertTrue($this->csrfManager->isTokenValid($registerToken, 'register_form'));
        $this->assertTrue($this->csrfManager->isTokenValid($contactToken, 'contact_form'));
        
        // Cross-validation should fail
        $this->assertFalse($this->csrfManager->isTokenValid($loginToken, 'register_form'));
    }
    
    /**
     * Test token regeneration after session clear
     */
    public function testTokenRegenerationAfterSessionClear(): void
    {
        $token1 = $this->csrfManager->getToken();
        
        $this->session->clear();
        $this->session->start();
        
        // Need new CsrfManager instance after session clear
        $newCsrfManager = new CsrfManager($this->session);
        $token2 = $newCsrfManager->getToken();
        
        $this->assertNotSame($token1, $token2);
        $this->assertFalse($newCsrfManager->isTokenValid($token1));
    }
}
