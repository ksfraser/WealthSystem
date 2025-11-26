# Security Fixes Implementation Guide

## Overview
This document provides step-by-step instructions for implementing the critical security fixes identified in the comprehensive code review.

## ✅ Completed: Security Classes Created

The following security classes have been created and are ready for use:

### 1. InputValidator (`app/Security/InputValidator.php`)
**Purpose**: Sanitize and validate all user input to prevent XSS and injection attacks.

**Key Methods**:
- `sanitizeString()` - Remove tags and special characters
- `validateInt()` / `validateFloat()` - Type-safe number validation
- `validateEmail()` / `validateUrl()` - Format validation
- `validateStockSymbol()` - Stock ticker validation (1-5 uppercase letters)
- `sanitizeFilename()` - Prevent directory traversal
- `validateWhitelist()` - Check against allowed values
- `sanitizeHtml()` - Allow only safe HTML tags

### 2. CsrfProtection (`app/Security/CsrfProtection.php`)
**Purpose**: Protect all forms and AJAX requests from CSRF attacks.

**Key Methods**:
- `generateToken()` - Create new CSRF token
- `getToken()` - Get current token (generates if needed)
- `validateRequest()` - Validate from POST or header
- `getFormField()` - Generate hidden input for forms
- `getAjaxScript()` - JavaScript to auto-include token in AJAX

**Token Lifetime**: 1 hour (configurable)

### 3. SessionManager (`app/Security/SessionManager.php`)
**Purpose**: Secure session handling with proper configuration.

**Key Features**:
- HTTPOnly cookies (prevents JavaScript access)
- Secure flag for HTTPS
- SameSite=Strict (prevents CSRF)
- Automatic session ID regeneration (every 30 minutes)
- Flash message support
- Authentication helpers

### 4. CommandExecutor (`app/Security/CommandExecutor.php`)
**Purpose**: Secure Python script execution with whitelist and input validation.

**Key Features**:
- Script whitelist (only approved scripts can run)
- Path traversal prevention
- Command injection prevention
- Argument validation
- Timeout support
- Sandboxed execution

**Whitelisted Scripts** (configurable):
- enhanced_automation.py
- simple_automation.py
- trading_script.py
- database_architect.py

### 5. Secure API Endpoint (`app/Api/execute_command.php`)
**Purpose**: Replacement for vulnerable `run_python_command.php`.

**Security Features**:
- CSRF protection required
- Authentication required
- Input validation
- Command whitelist
- Secure argument passing
- Proper error handling

---

## 🔴 CRITICAL: Files to Disable Immediately

These files have **REMOTE CODE EXECUTION** vulnerabilities:

### 1. `web_ui/run_python_command.php`
**Vulnerability**: Command injection - accepts raw commands from POST without proper validation.

**Action Required**:
```bash
# Option A: Delete the file
rm Stock-Analysis/web_ui/run_python_command.php

# Option B: Rename to disable
mv Stock-Analysis/web_ui/run_python_command.php Stock-Analysis/web_ui/run_python_command.php.DISABLED

# Option C: Add authentication check at top of file (temporary)
```

**Replacement**: Use `app/Api/execute_command.php` instead.

### 2. `web_ui/automation.php`
**Vulnerability**: Directly calls vulnerable `run_python_command.php` without authentication.

**Action Required**:
```bash
# Disable the file
mv Stock-Analysis/web_ui/automation.php Stock-Analysis/web_ui/automation.php.DISABLED
```

**Replacement**: Create new automation UI that uses secure API endpoint.

---

## 🔧 Implementation Steps

### Phase 1: Immediate Security (TODAY)

#### Step 1.1: Disable Vulnerable Files
```bash
cd Stock-Analysis

# Disable command injection vulnerabilities
mv web_ui/run_python_command.php web_ui/run_python_command.php.DISABLED
mv web_ui/automation.php web_ui/automation.php.DISABLED

# Create README explaining why
echo "SECURITY: File disabled due to command injection vulnerability. Use app/Api/execute_command.php instead." > web_ui/run_python_command.README
```

#### Step 1.2: Initialize Security Classes in Bootstrap
Edit `app/bootstrap.php`:

```php
<?php

// Existing autoloader code...

// Initialize secure session management
use App\Security\SessionManager;
SessionManager::start();

// Set security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
```

#### Step 1.3: Add CSRF to Existing Forms
For each form in the application, add CSRF token:

```php
<?php
use App\Security\CsrfProtection;
?>

<form method="POST" action="...">
    <?php echo CsrfProtection::getFormField(); ?>
    
    <!-- existing form fields -->
</form>
```

#### Step 1.4: Add CSRF Validation to POST Handlers
At the top of each controller handling POST:

```php
use App\Security\CsrfProtection;

// Validate CSRF token
try {
    CsrfProtection::verifyPostRequest();
} catch (\RuntimeException $e) {
    http_response_code(403);
    die('CSRF validation failed');
}
```

### Phase 2: Input Validation (THIS WEEK)

#### Step 2.1: Update DashboardController
**File**: `app/Controllers/Web/DashboardController.php`

**BEFORE**:
```php
$userId = $_SESSION['demo_user'] ?? 1;
```

**AFTER**:
```php
use App\Security\SessionManager;
use App\Security\InputValidator;

$userId = SessionManager::getUserId();
if (!$userId) {
    // Redirect to login
    header('Location: /login');
    exit;
}
```

#### Step 2.2: Update StockController
**File**: `app/Controllers/Web/StockController.php`

**BEFORE**:
```php
$symbol = $_POST['symbol'] ?? '';
```

**AFTER**:
```php
use App\Security\InputValidator;

$symbol = InputValidator::validateStockSymbol($_POST['symbol'] ?? '');
if (!$symbol) {
    return ['error' => 'Invalid stock symbol'];
}
```

#### Step 2.3: Update BankImportController
**File**: `app/Controllers/Web/BankImportController.php`

Add file upload validation:

```php
use App\Security\InputValidator;

public function uploadCsv(): array
{
    // Validate CSRF
    CsrfProtection::verifyPostRequest();
    
    // Validate file upload
    if (!isset($_FILES['csv_file'])) {
        return ['error' => 'No file uploaded'];
    }
    
    $file = $_FILES['csv_file'];
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'File too large (max 5MB)'];
    }
    
    // Validate MIME type
    $allowedTypes = ['text/csv', 'text/plain'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['error' => 'Invalid file type (CSV only)'];
    }
    
    // Sanitize filename
    $filename = InputValidator::sanitizeFilename($file['name']);
    
    // Continue with processing...
}
```

### Phase 3: Legacy Code Fixes (NEXT 2 WEEKS)

#### Step 3.1: Create Input Validation Middleware
**File**: `app/Middleware/ValidateInput.php`

```php
<?php

namespace App\Middleware;

use App\Security\InputValidator;

class ValidateInput
{
    /**
     * Sanitize all GET parameters
     */
    public static function sanitizeGet(): array
    {
        $sanitized = [];
        foreach ($_GET as $key => $value) {
            $sanitized[$key] = InputValidator::sanitizeString($value);
        }
        return $sanitized;
    }
    
    /**
     * Sanitize all POST parameters
     */
    public static function sanitizePost(): array
    {
        $sanitized = [];
        foreach ($_POST as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = InputValidator::validateArray($value, 'string');
            } else {
                $sanitized[$key] = InputValidator::sanitizeString($value);
            }
        }
        return $sanitized;
    }
}
```

#### Step 3.2: Update Legacy Files
For each legacy file in `web_ui/`:

1. Add at the top:
```php
<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Security\SessionManager;
use App\Security\CsrfProtection;
use App\Middleware\ValidateInput;

// Secure session
SessionManager::start();

// Sanitize input
$_GET = ValidateInput::sanitizeGet();
$_POST = ValidateInput::sanitizePost();
```

2. Replace direct superglobal access:
```php
// BEFORE
$id = $_GET['id'];

// AFTER  
$id = InputValidator::validateInt($_GET['id']);
if (!$id) {
    die('Invalid ID');
}
```

3. Add CSRF to forms (as shown in Step 1.3)

### Phase 4: Database Security (NEXT 2 WEEKS)

#### Step 4.1: Review All SQL Queries
Search for potential SQL injection:

```bash
cd Stock-Analysis
grep -r "->query(" app/ web_ui/ | grep -v "prepare"
```

#### Step 4.2: Convert to Prepared Statements
**BEFORE**:
```php
$sql = "SELECT * FROM stocks WHERE symbol = '$symbol'";
$result = $db->query($sql);
```

**AFTER**:
```php
$sql = "SELECT * FROM stocks WHERE symbol = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $symbol);
$stmt->execute();
$result = $stmt->get_result();
```

---

## 📊 Validation Testing

### Test 1: CSRF Protection
```bash
# Should fail without CSRF token
curl -X POST http://localhost/api/execute_command \
  -d "command_key=enhanced_automation"

# Should succeed with CSRF token
curl -X POST http://localhost/api/execute_command \
  -H "X-CSRF-Token: [token]" \
  -d "command_key=enhanced_automation"
```

### Test 2: Input Validation
```php
// Test stock symbol validation
var_dump(InputValidator::validateStockSymbol('AAPL'));  // Should return 'AAPL'
var_dump(InputValidator::validateStockSymbol('AAP L')); // Should return null
var_dump(InputValidator::validateStockSymbol('<script>')); // Should return null
```

### Test 3: Command Execution
```php
use App\Security\CommandExecutor;

$executor = new CommandExecutor();

// Should succeed (whitelisted script)
$result = $executor->executePythonScript('enhanced_automation.py');
var_dump($result['success']);

// Should fail (not whitelisted)
$result = $executor->executePythonScript('malicious.py');
var_dump($result['success']); // false
```

### Test 4: Session Security
```php
use App\Security\SessionManager;

SessionManager::start();
SessionManager::setAuthenticated(1, ['name' => 'Test User']);

var_dump(SessionManager::isAuthenticated()); // true
var_dump(SessionManager::getUserId()); // 1
```

---

## 🔍 Security Audit Checklist

After implementing all fixes:

- [ ] All forms have CSRF protection
- [ ] All POST handlers validate CSRF token
- [ ] No direct $_GET/$_POST access without validation
- [ ] All SQL queries use prepared statements
- [ ] File uploads validate MIME types and size
- [ ] Sessions use secure configuration
- [ ] run_python_command.php is disabled/deleted
- [ ] automation.php is disabled/replaced
- [ ] All Python execution uses CommandExecutor
- [ ] Authentication required for sensitive operations
- [ ] Error messages don't leak sensitive information
- [ ] Logging implemented for security events

---

## 📈 Progress Tracking

| Phase | Status | Completion Date |
|-------|--------|-----------------|
| Phase 1: Immediate Security | 🔄 In Progress | [TARGET: TODAY] |
| Phase 2: Input Validation | ⏸️ Pending | [TARGET: +3 days] |
| Phase 3: Legacy Code Fixes | ⏸️ Pending | [TARGET: +2 weeks] |
| Phase 4: Database Security | ⏸️ Pending | [TARGET: +2 weeks] |

---

## 🆘 Emergency Rollback

If any security fix breaks functionality:

1. **Disable security class**:
```php
// In bootstrap.php, comment out:
// SessionManager::start();
```

2. **Revert specific file**:
```bash
git checkout HEAD -- path/to/file.php
```

3. **Re-enable legacy endpoint temporarily**:
```bash
mv web_ui/run_python_command.php.DISABLED web_ui/run_python_command.php
```

4. **Document the issue** and create a plan to fix properly.

---

## 📚 Additional Resources

- **OWASP PHP Security Cheat Sheet**: https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html
- **CSRF Protection**: https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html
- **Input Validation**: https://cheatsheetseries.owasp.org/cheatsheets/Input_Validation_Cheat_Sheet.html
- **Session Management**: https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html

---

## 🎯 Success Metrics

After full implementation:

- **Command Injection**: ✅ Eliminated (CommandExecutor enforces whitelist)
- **CSRF Attacks**: ✅ Prevented (all forms protected)
- **XSS Attacks**: ✅ Mitigated (input validation + output escaping)
- **Session Hijacking**: ✅ Prevented (secure session configuration)
- **SQL Injection**: ✅ Eliminated (prepared statements)
- **File Upload Attacks**: ✅ Prevented (MIME validation + sanitization)

**Estimated Security Score Improvement**: 35% → 85%
