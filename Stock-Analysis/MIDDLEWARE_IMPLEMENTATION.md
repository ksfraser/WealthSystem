# Middleware Implementation - Symfony vs Custom

## Current State

Your `app/Middleware/ValidateInput.php` has **static helper methods** which is **NOT actually middleware**. It's just a utility class.

**Current (NOT Middleware)**:
```php
// This is just a utility class, not real middleware
class ValidateInput {
    public static function sanitizeGet(): array { ... }
    public static function getString(string $key): ?string { ... }
}
```

## ✅ Proper Middleware Pattern

Your `Router.php` already has middleware support via `MiddlewareInterface`. This is **good design** and aligns with industry standards.

### Symfony's Middleware Pattern

Symfony uses **HTTP Kernel** with middleware (called "event subscribers" or "kernel listeners"), but for a custom router like yours, the pattern you have is actually **correct and standard**.

**Your MiddlewareInterface** (from Router.php):
```php
interface MiddlewareInterface {
    public function handle(Request $request, callable $next): Response;
}
```

This is the **PSR-15 pattern** (PHP Standard Recommendation), which is the industry standard for HTTP middleware.

## 🎯 Recommended Approach

**Don't install `symfony/http-kernel`** - your custom router middleware pattern is already good!

Instead, we should:

1. ✅ **Keep your middleware architecture** (it's already correct)
2. ✅ **Use Symfony components for the actual validation/security logic**
3. ✅ **Create proper middleware classes** (not static helpers)

---

## Proper Middleware Implementation

### 1. CSRF Middleware (Using Symfony Components)

**File**: `app/Middleware/CsrfMiddleware.php`

```php
<?php

namespace App\Middleware;

use App\Core\Interfaces\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Security\CsrfManager;

/**
 * CSRF Protection Middleware
 * Uses Symfony's CSRF component internally
 */
class CsrfMiddleware implements MiddlewareInterface
{
    private CsrfManager $csrfManager;
    
    public function __construct(CsrfManager $csrfManager)
    {
        $this->csrfManager = $csrfManager;
    }
    
    public function handle(Request $request, callable $next): Response
    {
        // Only validate CSRF for state-changing methods
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $token = $request->post('csrf_token') ?? $request->header('X-CSRF-Token');
            
            if (!$this->csrfManager->isTokenValid($token)) {
                return new Response('CSRF token validation failed', 403, [
                    'Content-Type' => 'application/json'
                ], json_encode(['error' => 'CSRF token validation failed']));
            }
        }
        
        // Continue to next middleware/handler
        return $next($request);
    }
}
```

### 2. Authentication Middleware (Using Symfony Session)

**File**: `app/Middleware/AuthMiddleware.php`

```php
<?php

namespace App\Middleware;

use App\Core\Interfaces\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Security\SessionService;

/**
 * Authentication Middleware
 * Uses Symfony's Session component internally
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (!SessionService::isAuthenticated()) {
            // Return 401 for API requests
            if ($this->isApiRequest($request)) {
                return new Response('Unauthorized', 401, [
                    'Content-Type' => 'application/json'
                ], json_encode(['error' => 'Authentication required']));
            }
            
            // Redirect to login for web requests
            return new Response('Redirecting to login', 302, [
                'Location' => '/login'
            ]);
        }
        
        // Add user to request attributes
        $request->setAttribute('user_id', SessionService::getUserId());
        $request->setAttribute('user_data', SessionService::get()->get('user_data'));
        
        return $next($request);
    }
    
    private function isApiRequest(Request $request): bool
    {
        return strpos($request->getUri(), '/api/') === 0 
            || $request->header('Accept') === 'application/json';
    }
}
```

### 3. Input Validation Middleware (Using Symfony Validator)

**File**: `app/Middleware/ValidateInputMiddleware.php`

```php
<?php

namespace App\Middleware;

use App\Core\Interfaces\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Security\InputSanitizer;

/**
 * Input Validation Middleware
 * Uses Symfony's Validator component internally
 */
class ValidateInputMiddleware implements MiddlewareInterface
{
    private InputSanitizer $sanitizer;
    
    public function __construct(InputSanitizer $sanitizer)
    {
        $this->sanitizer = $sanitizer;
    }
    
    public function handle(Request $request, callable $next): Response
    {
        // Sanitize GET parameters
        $sanitizedGet = [];
        foreach ($request->query() as $key => $value) {
            if (is_string($value)) {
                $sanitizedGet[$key] = $this->sanitizer->sanitizeString($value);
            } else {
                $sanitizedGet[$key] = $value;
            }
        }
        
        // Sanitize POST parameters
        $sanitizedPost = [];
        foreach ($request->post() as $key => $value) {
            if (is_string($value)) {
                $sanitizedPost[$key] = $this->sanitizer->sanitizeString($value);
            } else {
                $sanitizedPost[$key] = $value;
            }
        }
        
        // Replace request data with sanitized versions
        $request->setQueryParams($sanitizedGet);
        $request->setPostParams($sanitizedPost);
        
        return $next($request);
    }
}
```

### 4. Security Headers Middleware

**File**: `app/Middleware/SecurityHeadersMiddleware.php`

```php
<?php

namespace App\Middleware;

use App\Core\Interfaces\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;

/**
 * Security Headers Middleware
 * Adds security headers to all responses
 */
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);
        
        // Add security headers
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-XSS-Protection', '1; mode=block');
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // HTTPS-only headers
        if ($request->isSecure()) {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        
        // Content Security Policy
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self'",
            "connect-src 'self'",
            "frame-ancestors 'none'"
        ]);
        $response->setHeader('Content-Security-Policy', $csp);
        
        return $response;
    }
}
```

---

## Usage in Routes

```php
// In app/bootstrap.php

use App\Middleware\SecurityHeadersMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\ValidateInputMiddleware;

// Global middleware (applies to all routes)
$router->middleware(SecurityHeadersMiddleware::class);
$router->middleware(ValidateInputMiddleware::class);

// Routes with specific middleware
$router->post('/api/execute-command', function() use ($container) {
    $controller = $container->get('App\\Controllers\\Api\\CommandController');
    return $controller->execute();
}, [
    AuthMiddleware::class,  // Must be authenticated
    CsrfMiddleware::class   // Must have valid CSRF token
]);

// Public routes (no auth)
$router->get('/login', function() use ($container) {
    $controller = $container->get('App\\Controllers\\Web\\AuthController');
    return $controller->loginForm();
});

// Protected routes
$router->get('/dashboard', function() use ($container) {
    $controller = $container->get('App\\Controllers\\Web\\DashboardController');
    return $controller->index();
}, [
    AuthMiddleware::class  // Requires authentication
]);
```

---

## Key Differences

### ❌ WRONG (Current ValidateInput.php):
```php
// Static utility class - NOT middleware
class ValidateInput {
    public static function getString(string $key): ?string {
        // This is just a helper, not middleware
    }
}

// Used like this (manual call in controller):
$value = ValidateInput::getString('name');
```

### ✅ CORRECT (Proper Middleware):
```php
// Implements MiddlewareInterface - REAL middleware
class ValidateInputMiddleware implements MiddlewareInterface {
    public function handle(Request $request, callable $next): Response {
        // Automatically sanitizes ALL input before reaching controller
        $sanitizedData = $this->sanitizeAll($request);
        $request->setParams($sanitizedData);
        return $next($request);
    }
}

// Used like this (automatic, declared in routes):
$router->middleware(ValidateInputMiddleware::class);
```

---

## Summary: What to Do

### ✅ KEEP:
1. **Your middleware architecture** - It's already correct (PSR-15 pattern)
2. **Your MiddlewareInterface** - Industry standard
3. **Your Router middleware chain** - Well implemented

### ✅ CREATE:
1. **Proper middleware classes** that implement `MiddlewareInterface`
2. **Use Symfony components internally** (CsrfManager, SessionService, etc.)
3. **Remove static helper classes** like `ValidateInput.php`

### 📦 Additional Packages Needed:
```bash
composer require symfony/validator
composer require symfony/process
```

### ⏱️ Estimated Time:
- Create 4 middleware classes: **1-2 hours**
- Update routes to use middleware: **30 minutes**
- Test thoroughly: **1 hour**
- **Total: 2-3 hours**

---

## Verdict

**Your middleware architecture is actually GOOD!** 

The problem is:
1. ❌ `ValidateInput.php` is not real middleware (it's just static helpers)
2. ✅ Your `Router` + `MiddlewareInterface` is correct
3. ✅ We should keep your architecture and create proper middleware classes
4. ✅ Those middleware classes should use Symfony components internally

**You don't need Symfony's HTTP Kernel** - your custom router with PSR-15 middleware pattern is already industry-standard and correct.
