# Test Organization for ChatGPT-Micro-Cap-Experiment

## Directory Structure

```
tests/
├── unit/                    # Unit tests for individual classes
│   ├── test_users.php       # User management testing
│   ├── test_userauth.php    # Authentication testing
│   ├── test_ui_components.php # UI component testing
│   ├── test_simple_db.php   # Database connection testing
│   ├── test_session_headers.php # Session handling testing
│   ├── test_registration.php # User registration testing
│   ├── test_minimal_auth.php # Minimal auth testing
│   ├── test_index_basic.php # Basic index testing
│   ├── test_enhanced_db.php # Enhanced DB testing
│   ├── test_db_connection.php # DB connection testing
│   └── test_navigation_fix.php # Navigation testing
├── integration/             # Integration tests between components
└── fixtures/               # Test data and fixtures
```

## Test Categories

### Unit Tests
- **Authentication Tests:** `test_userauth.php`, `test_minimal_auth.php`, `test_registration.php`
- **Database Tests:** `test_simple_db.php`, `test_enhanced_db.php`, `test_db_connection.php`
- **UI Tests:** `test_ui_components.php`, `test_index_basic.php`, `test_navigation_fix.php`
- **User Management:** `test_users.php`
- **Session Tests:** `test_session_headers.php`

### Integration Tests
- **Cross-component testing**
- **Database-to-UI integration**
- **Authentication flow testing**
- **Portfolio management workflows**

## Running Tests

### Prerequisites
```bash
# Install PHPUnit (if not already installed)
composer install phpunit/phpunit

# Or global installation
composer global require phpunit/phpunit
```

### Test Execution
```bash
# Run all unit tests
phpunit tests/unit/

# Run specific test file
phpunit tests/unit/test_userauth.php

# Run integration tests
phpunit tests/integration/

# Run all tests with coverage
phpunit --coverage-html coverage/ tests/
```

## Test Standards

### File Naming Convention
- Unit tests: `test_[component_name].php`
- Integration tests: `integration_[feature_name].php`
- Fixtures: `[component_name]_fixture.php`

### Test Class Naming
- Unit test classes: `[ComponentName]Test`
- Integration test classes: `[FeatureName]IntegrationTest`

### Test Method Naming
- Test methods: `test[MethodName][Scenario]()`
- Setup methods: `setUp()`, `tearDown()`
- Data providers: `[testMethod]Provider()`

## Coverage Goals

- **Unit Test Coverage:** 80%+
- **Integration Test Coverage:** 60%+
- **Critical Path Coverage:** 95%+

## Test Data Management

### Database Testing
- Use test database configuration
- Implement database fixtures for consistent test data
- Clean up test data after each test run

### CSV Testing  
- Use test CSV files in `tests/fixtures/`
- Mock external data sources
- Test both success and failure scenarios

## Continuous Integration

### Pre-commit Hooks
```bash
#!/bin/bash
# Run tests before commit
phpunit tests/unit/
if [ $? -ne 0 ]; then
    echo "Unit tests failed. Commit aborted."
    exit 1
fi
```

### Automated Testing
- Run tests on every push to main branch
- Generate coverage reports
- Notify on test failures