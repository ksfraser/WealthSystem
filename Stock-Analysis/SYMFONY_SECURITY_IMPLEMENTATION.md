# Symfony Security Implementation Guide

## Overview

This guide shows how to properly implement security using the **already-installed Symfony components** instead of custom implementations.

---

## 1. CSRF Protection with Symfony

### Install (Already Installed ✅)
```bash
# Already in composer.json:
# "symfony/security-csrf": "^6.0"
```

### Implementation

**File**: `app/Security/CsrfManager.php`

```php
<?php

namespace App\Security;

use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * CSRF Protection using Symfony
 */
class CsrfManager
{
    private CsrfTokenManager $tokenManager;
    private string $tokenId = 'csrf_token';
    
    public function __construct(?Session $session = null)
    {
        $session = $session ?? new Session();
        if (!$session->isStarted()) {
            $session->start();
        }
        
        $generator = new UriSafeTokenGenerator();
        $storage = new SessionTokenStorage($session);
        $this->tokenManager = new CsrfTokenManager($generator, $storage);
    }
    
    /**
     * Generate CSRF token
     */
    public function getToken(string $tokenId = null): string
    {
        $tokenId = $tokenId ?? $this->tokenId;
        return $this->tokenManager->getToken($tokenId)->getValue();
    }
    
    /**
     * Validate CSRF token
     */
    public function isTokenValid(string $token, string $tokenId = null): bool
    {
        $tokenId = $tokenId ?? $this->tokenId;
        return $this->tokenManager->isTokenValid(new CsrfToken($tokenId, $token));
    }
    
    /**
     * Get HTML form field
     */
    public function getFormField(string $tokenId = null): string
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
     */
    public function verifyRequest(array $postData, string $tokenId = null): bool
    {
        $tokenId = $tokenId ?? $this->tokenId;
        $token = $postData[$tokenId] ?? null;
        
        if (!$token) {
            return false;
        }
        
        return $this->isTokenValid($token, $tokenId);
    }
    
    /**
     * Verify or throw exception
     */
    public function verifyOrFail(array $postData, string $tokenId = null): void
    {
        if (!$this->verifyRequest($postData, $tokenId)) {
            http_response_code(403);
            throw new \RuntimeException('CSRF token validation failed');
        }
    }
}
```

### Usage

```php
use App\Security\CsrfManager;

$csrf = new CsrfManager();

// In form:
echo $csrf->getFormField();

// In controller:
$csrf->verifyOrFail($_POST);
```

---

## 2. Session Management with Symfony

### Implementation

**File**: `app/Security/SessionService.php`

```php
<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;

/**
 * Session Service using Symfony
 */
class SessionService
{
    private static ?Session $session = null;
    
    public static function start(): Session
    {
        if (self::$session !== null) {
            return self::$session;
        }
        
        // Configure secure session storage
        $options = [
            'cookie_httponly' => true,
            'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'cookie_samesite' => 'strict',
            'use_strict_mode' => true,
            'use_only_cookies' => true,
            'cookie_lifetime' => 0,
            'gc_maxlifetime' => 3600,
            'sid_length' => 48,
            'sid_bits_per_character' => 6,
        ];
        
        $handler = new NativeFileSessionHandler();
        $storage = new NativeSessionStorage($options, $handler);
        
        self::$session = new Session($storage);
        
        if (!self::$session->isStarted()) {
            self::$session->start();
        }
        
        // Regenerate session ID periodically
        self::regenerateIfNeeded();
        
        return self::$session;
    }
    
    public static function get(): Session
    {
        return self::start();
    }
    
    private static function regenerateIfNeeded(): void
    {
        $session = self::$session;
        
        if (!$session->has('_regenerated_at')) {
            $session->migrate(true);
            $session->set('_regenerated_at', time());
            return;
        }
        
        $age = time() - $session->get('_regenerated_at');
        if ($age > 1800) { // 30 minutes
            $session->migrate(true);
            $session->set('_regenerated_at', time());
        }
    }
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated(): bool
    {
        $session = self::get();
        return $session->has('user_id') && $session->get('authenticated') === true;
    }
    
    /**
     * Set user authentication
     */
    public static function setAuthenticated(int $userId, array $userData = []): void
    {
        $session = self::get();
        $session->migrate(true); // Prevent session fixation
        $session->set('user_id', $userId);
        $session->set('authenticated', true);
        $session->set('user_data', $userData);
        $session->set('auth_time', time());
    }
    
    /**
     * Clear authentication
     */
    public static function logout(): void
    {
        $session = self::get();
        $session->remove('user_id');
        $session->remove('authenticated');
        $session->remove('user_data');
        $session->remove('auth_time');
        $session->invalidate();
    }
    
    /**
     * Get user ID
     */
    public static function getUserId(): ?int
    {
        return self::isAuthenticated() ? self::get()->get('user_id') : null;
    }
}
```

### Usage

```php
use App\Security\SessionService;

// Start session
$session = SessionService::start();

// Set authenticated user
SessionService::setAuthenticated(1, ['name' => 'John Doe']);

// Check authentication
if (SessionService::isAuthenticated()) {
    $userId = SessionService::getUserId();
}

// Logout
SessionService::logout();
```

---

## 3. Input Validation with Symfony

### Install Additional Package

```bash
cd Stock-Analysis
composer require symfony/validator
```

### Implementation

**File**: `app/Security/InputSanitizer.php`

```php
<?php

namespace App\Security;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input Sanitizer using Symfony Validator
 */
class InputSanitizer
{
    private $validator;
    
    public function __construct()
    {
        $this->validator = Validation::createValidator();
    }
    
    /**
     * Sanitize string
     */
    public function sanitizeString(string $input): string
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Validate integer
     */
    public function validateInt($input): ?int
    {
        $violations = $this->validator->validate($input, [
            new Assert\Type('integer')
        ]);
        
        if (count($violations) > 0) {
            return null;
        }
        
        return (int) $input;
    }
    
    /**
     * Validate email
     */
    public function validateEmail(string $input): ?string
    {
        $violations = $this->validator->validate($input, [
            new Assert\Email()
        ]);
        
        if (count($violations) > 0) {
            return null;
        }
        
        return $input;
    }
    
    /**
     * Validate URL
     */
    public function validateUrl(string $input): ?string
    {
        $violations = $this->validator->validate($input, [
            new Assert\Url()
        ]);
        
        if (count($violations) > 0) {
            return null;
        }
        
        return $input;
    }
    
    /**
     * Validate stock symbol (custom business logic)
     */
    public function validateStockSymbol(string $input): ?string
    {
        $input = strtoupper(trim($input));
        
        $violations = $this->validator->validate($input, [
            new Assert\Regex([
                'pattern' => '/^[A-Z]{1,5}$/',
                'message' => 'Stock symbol must be 1-5 uppercase letters'
            ])
        ]);
        
        if (count($violations) > 0) {
            return null;
        }
        
        return $input;
    }
    
    /**
     * Validate date
     */
    public function validateDate(string $input): ?string
    {
        $violations = $this->validator->validate($input, [
            new Assert\Date()
        ]);
        
        if (count($violations) > 0) {
            return null;
        }
        
        return $input;
    }
    
    /**
     * Sanitize filename
     */
    public function sanitizeFilename(string $filename): string
    {
        $filename = str_replace(['/', '\\', '..', chr(0)], '', $filename);
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    }
}
```

---

## 4. Process Execution with Symfony

### Install Additional Package

```bash
cd Stock-Analysis
composer require symfony/process
```

### Implementation

**File**: `app/Security/ProcessManager.php`

```php
<?php

namespace App\Security;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Process Manager using Symfony Process
 */
class ProcessManager
{
    private array $allowedScripts = [
        'enhanced_automation.py',
        'simple_automation.py',
        'trading_script.py',
        'database_architect.py'
    ];
    
    private string $baseDir;
    
    public function __construct(?string $baseDir = null)
    {
        $this->baseDir = $baseDir ?? dirname(__DIR__, 2);
    }
    
    /**
     * Execute Python script securely
     */
    public function executePythonScript(
        string $scriptName,
        array $args = [],
        int $timeout = 60
    ): array {
        // Validate script is whitelisted
        if (!in_array($scriptName, $this->allowedScripts, true)) {
            return [
                'success' => false,
                'output' => '',
                'error' => "Script '{$scriptName}' is not whitelisted"
            ];
        }
        
        // Build script path
        $scriptPath = $this->baseDir . DIRECTORY_SEPARATOR . $scriptName;
        
        if (!file_exists($scriptPath)) {
            return [
                'success' => false,
                'output' => '',
                'error' => "Script not found: {$scriptName}"
            ];
        }
        
        // Build command array
        $command = ['python', $scriptPath];
        
        foreach ($args as $key => $value) {
            if (is_int($key)) {
                $command[] = $value;
            } else {
                $command[] = "--{$key}";
                $command[] = $value;
            }
        }
        
        // Create process
        $process = new Process($command, $this->baseDir, null, null, $timeout);
        
        try {
            $process->run();
            
            return [
                'success' => $process->isSuccessful(),
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
                'exit_code' => $process->getExitCode()
            ];
            
        } catch (ProcessFailedException $e) {
            return [
                'success' => false,
                'output' => $process->getOutput(),
                'error' => $e->getMessage(),
                'exit_code' => $process->getExitCode()
            ];
        }
    }
    
    /**
     * Check if Python is available
     */
    public function isPythonAvailable(): bool
    {
        $process = new Process(['python', '--version']);
        $process->run();
        return $process->isSuccessful();
    }
    
    /**
     * Get Python version
     */
    public function getPythonVersion(): ?string
    {
        $process = new Process(['python', '--version']);
        $process->run();
        
        if ($process->isSuccessful()) {
            return trim($process->getOutput());
        }
        
        return null;
    }
}
```

---

## 5. Updated API Endpoint

**File**: `app/Api/execute_command.php`

```php
<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Security\CsrfManager;
use App\Security\SessionService;
use App\Security\InputSanitizer;
use App\Security\ProcessManager;

header('Content-Type: application/json');

try {
    // Start session
    $session = SessionService::start();
    
    // Check authentication
    if (!SessionService::isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // Validate CSRF
    $csrf = new CsrfManager($session);
    $csrf->verifyOrFail($_POST);
    
    // Validate input
    $sanitizer = new InputSanitizer();
    $commandKey = $sanitizer->sanitizeString($_POST['command_key'] ?? '');
    
    if (empty($commandKey)) {
        http_response_code(400);
        echo json_encode(['error' => 'No command key provided']);
        exit;
    }
    
    // Map commands to scripts
    $scriptMap = [
        'enhanced_automation' => [
            'script' => 'enhanced_automation.py',
            'allowed_args' => ['market_cap']
        ],
        'simple_automation' => [
            'script' => 'simple_automation.py',
            'allowed_args' => []
        ],
        'trading_script' => [
            'script' => 'trading_script.py',
            'allowed_args' => []
        ]
    ];
    
    if (!isset($scriptMap[$commandKey])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unknown command key']);
        exit;
    }
    
    $config = $scriptMap[$commandKey];
    
    // Validate arguments
    $args = [];
    foreach ($config['allowed_args'] as $argName) {
        if (isset($_POST[$argName])) {
            $args[$argName] = $sanitizer->sanitizeString($_POST[$argName]);
        }
    }
    
    // Execute command
    $processManager = new ProcessManager();
    $result = $processManager->executePythonScript($config['script'], $args, 60);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'output' => $result['output']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }
    
} catch (\RuntimeException $e) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF validation failed']);
    
} catch (\Exception $e) {
    error_log('Command execution error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
```

---

## 6. Updated Bootstrap

**File**: `app/bootstrap.php`

```php
<?php

require_once __DIR__ . '/../web_ui/includes/bootstrap.php';

// Autoloader
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\ServiceContainer;
use App\Core\Router;
use App\Core\Request;
use App\Security\SessionService;

// Initialize secure session with Symfony
$session = SessionService::start();

// Set security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;");

// Continue with existing bootstrap...
$container = ServiceContainer::bootstrap();
// ... rest of file
```

---

## Migration Checklist

### Phase 1: Install Dependencies
- [ ] Run `composer require symfony/validator`
- [ ] Run `composer require symfony/process`
- [ ] Verify all Symfony packages are installed

### Phase 2: Create Symfony-Based Classes
- [ ] Create `app/Security/CsrfManager.php`
- [ ] Create `app/Security/SessionService.php`
- [ ] Create `app/Security/InputSanitizer.php`
- [ ] Create `app/Security/ProcessManager.php`

### Phase 3: Update Existing Code
- [ ] Update `app/bootstrap.php` to use SessionService
- [ ] Update `app/Api/execute_command.php` to use new classes
- [ ] Update controllers to use new security classes

### Phase 4: Remove Custom Classes
- [ ] Delete or archive `app/Security/CsrfProtection.php`
- [ ] Delete or archive `app/Security/SessionManager.php`
- [ ] Delete or archive `app/Security/CommandExecutor.php`
- [ ] Keep `app/Security/FileUploadValidator.php` (no Symfony equivalent)

### Phase 5: Testing
- [ ] Test CSRF protection
- [ ] Test session management
- [ ] Test input validation
- [ ] Test process execution
- [ ] Run security audit

---

## Benefits of Symfony Approach

1. **Security**: Battle-tested by millions of applications
2. **Maintenance**: Regular updates and security patches
3. **Documentation**: Extensive official documentation
4. **Community**: Large community support
5. **Standards**: Follows PHP-FIG standards (PSR)
6. **Testing**: Comprehensive test suites
7. **CVE Tracking**: Known vulnerabilities tracked and patched

---

## Estimated Time

- **Installation**: 5 minutes
- **Implementation**: 2-3 hours
- **Testing**: 1 hour
- **Total**: ~4 hours

---

**Status**: Ready to implement with proper Symfony components.
