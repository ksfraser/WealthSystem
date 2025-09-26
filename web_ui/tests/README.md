# Test Suite for RBAC System

This directory contains organized test files for the Role-Based Access Control (RBAC) implementation.

## Directory Structure

```
tests/
├── rbac/                    # RBAC-specific tests
│   ├── test_rbac_service.php       # Tests RBACService functionality
│   ├── test_userauth_rbac.php      # Tests UserAuthDAO RBAC integration
│   ├── verify_rbac.php             # Verifies RBAC migration results
│   └── check_rbac_tables.php       # Checks RBAC table creation
├── diagnostic/              # System diagnostic utilities
│   ├── check_users.php             # Lists current users and roles
│   └── check_table_structure.php   # Shows database table structures
└── run_all_tests.php       # Test runner for all RBAC tests
```

## Running Tests

### Individual Tests
From the web_ui directory:

```bash
# Test RBACService functionality
php tests/rbac/test_rbac_service.php

# Test UserAuthDAO RBAC integration
php tests/rbac/test_userauth_rbac.php

# Verify RBAC migration results
php tests/rbac/verify_rbac.php
```

### All Tests
```bash
# Run all RBAC tests
php tests/run_all_tests.php
```

### Diagnostic Utilities
```bash
# Check current users and their roles
php tests/diagnostic/check_users.php

# Check database table structures
php tests/diagnostic/check_table_structure.php
```

## Test Coverage

### RBAC Service Tests
- ✅ Multi-role support (admin + user combinations)
- ✅ Granular permission checking
- ✅ Role assignment and revocation
- ✅ Permission-based menu generation
- ✅ Data access control validation
- ✅ Advisor-client relationship management

### UserAuthDAO Integration Tests
- ✅ Backward compatibility with existing isAdmin() checks
- ✅ Enhanced permission-based authentication
- ✅ Role-based data access control
- ✅ Dynamic menu generation based on permissions
- ✅ Graceful fallback when RBAC is unavailable

### Migration and Setup Tests
- ✅ RBAC table creation and seeding
- ✅ Existing user migration to role-based system
- ✅ Foreign key constraint handling
- ✅ Admin user detection and assignment

## Security Considerations

All tests are designed to:
- Not expose sensitive data in production
- Use read-only operations where possible
- Validate permission boundaries
- Test access control edge cases
- Ensure proper error handling

## Maintenance

- Run tests after any RBAC-related changes
- Update tests when adding new roles or permissions
- Verify backward compatibility with legacy code
- Test advisor-client relationship features as they're developed