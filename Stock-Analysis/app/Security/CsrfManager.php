<?php

namespace App\Security;

use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use RuntimeException;

/**
 * CSRF Protection Manager using Symfony Security Component
 * 
 * Provides secure CSRF token generation and validation using Symfony's
 * battle-tested security components. Prevents cross-site request forgery attacks.
 * 
 * @package App\Security
 */
class CsrfManager
{
    /**
     * @var CsrfTokenManager Symfony CSRF token manager
     */
    private CsrfTokenManager $tokenManager;
    
    /**
     * @var string Default token identifier
     */
    private string $tokenId = 'csrf_token';
    
    /**
     * @var array<string, string> Cached tokens to ensure consistency
     */
    private array $tokenCache = [];
    
    /**
     * Constructor
     * 
     * @param Session|null $session Optional session instance (for testing)
     */
    public function __construct(?Session $session = null)
    {
        $session = $session ?? new Session();
        if (!$session->isStarted()) {
            $session->start();
        }
        
        // Create RequestStack and add current request with session
        $requestStack = new RequestStack();
        $request = new Request();
        $request->setSession($session);
        $requestStack->push($request);
        
        $generator = new UriSafeTokenGenerator();
        $storage = new SessionTokenStorage($requestStack);
        $this->tokenManager = new CsrfTokenManager($generator, $storage);
    }
    
    /**
     * Generate CSRF token
     * 
     * Generates a cryptographically secure token for CSRF protection.
     * Returns the same token on repeated calls for the same token ID.
     * 
     * @param string|null $tokenId Optional custom token identifier for multiple forms
     * @return string The CSRF token value
     * 
     * @example
     * ```php
     * $csrfManager = new CsrfManager();
     * $token = $csrfManager->getToken(); // Default form
     * $token2 = $csrfManager->getToken('delete-form'); // Specific form
     * ```
     */
    public function getToken(?string $tokenId = null): string
    {
        $tokenId = $tokenId ?? $this->tokenId;
        
        // Cache tokens to ensure consistency across multiple calls
        if (!isset($this->tokenCache[$tokenId])) {
            $this->tokenCache[$tokenId] = $this->tokenManager->getToken($tokenId)->getValue();
        }
        
        return $this->tokenCache[$tokenId];
    }
    
    /**
     * Validate CSRF token
     * 
     * Uses timing-safe comparison to prevent timing attacks.
     * 
     * @param string $token The token to validate
     * @param string|null $tokenId Optional custom token identifier
     * @return bool True if token is valid, false otherwise
     * 
     * @example
     * ```php
     * if ($csrfManager->isTokenValid($_POST['csrf_token'])) {
     *     // Process form
     * }
     * ```
     */
    public function isTokenValid(string $token, ?string $tokenId = null): bool
    {
        $tokenId = $tokenId ?? $this->tokenId;
        return $this->tokenManager->isTokenValid(new CsrfToken($tokenId, $token));
    }
    
    /**
     * Get HTML form field
     * 
     * Generates a hidden input field with CSRF token, properly escaped.
     * 
     * @param string|null $tokenId Optional custom token identifier
     * @return string HTML input field
     * 
     * @example
     * ```php
     * <form method="post">
     *     <?= $csrfManager->getFormField() ?>
     *     <!-- other fields -->
     * </form>
     * ```
     */
    public function getFormField(?string $tokenId = null): string
    {
        $tokenId = $tokenId ?? $this->tokenId;
        $token = $this->getToken($tokenId);
        
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars($tokenId, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }
    
    /**
     * Verify request has valid CSRF token
     * 
     * Checks if POST data contains a valid CSRF token.
     * 
     * @param array $postData POST request data (typically $_POST)
     * @param string|null $tokenId Optional custom token identifier
     * @return bool True if valid token found, false otherwise
     * 
     * @example
     * ```php
     * if (!$csrfManager->verifyRequest($_POST)) {
     *     http_response_code(403);
     *     exit('Invalid CSRF token');
     * }
     * ```
     */
    public function verifyRequest(array $postData, ?string $tokenId = null): bool
    {
        $tokenId = $tokenId ?? $this->tokenId;
        $token = $postData[$tokenId] ?? null;
        
        if (!$token) {
            return false;
        }
        
        return $this->isTokenValid($token, $tokenId);
    }
    
    /**
     * Verify request or throw exception
     * 
     * Same as verifyRequest() but throws exception on failure.
     * Useful for API endpoints.
     * 
     * @param array $postData POST request data
     * @param string|null $tokenId Optional custom token identifier
     * @throws RuntimeException If token is missing or invalid
     * 
     * @example
     * ```php
     * try {
     *     $csrfManager->verifyOrFail($_POST);
     *     // Process request
     * } catch (RuntimeException $e) {
     *     http_response_code(403);
     *     echo json_encode(['error' => $e->getMessage()]);
     * }
     * ```
     */
    public function verifyOrFail(array $postData, ?string $tokenId = null): void
    {
        if (!$this->verifyRequest($postData, $tokenId)) {
            throw new RuntimeException('CSRF token validation failed');
        }
    }
    
    /**
     * Refresh token
     * 
     * Removes the current token from session, forcing generation of a new token
     * on next getToken() call. Useful after sensitive operations.
     * 
     * @param string|null $tokenId Optional custom token identifier
     * 
     * @example
     * ```php
     * // After password change
     * $csrfManager->refreshToken();
     * ```
     */
    public function refreshToken(?string $tokenId = null): void
    {
        $tokenId = $tokenId ?? $this->tokenId;
        $this->tokenManager->removeToken($tokenId);
        unset($this->tokenCache[$tokenId]); // Clear cache
    }
}
