# Ksfraser Authentication Library

A comprehensive, secure, and reusable PHP authentication library providing user management, session handling, and security features for web applications.

## Features

- **User Management**: Registration, login, logout, profile management
- **Security**: Password hashing, CSRF protection, rate limiting, audit logging
- **Session Management**: Secure session handling with configurable options
- **Database Integration**: MySQL/MariaDB support with automatic schema setup
- **Middleware**: Request-level authentication and authorization
- **UI Components**: Pre-built HTML forms and components
- **Configuration**: Flexible configuration system with file-based config
- **Admin System**: Role-based access control and admin management
- **Utilities**: Helper functions for common authentication tasks

## Installation

### Via Composer (Recommended)

```bash
composer require ksfraser/auth
```

### Manual Installation

1. Download or clone this repository
2. Include the autoloader in your project
3. Configure your database connection

## Quick Start

### 1. Basic Setup

```php
<?php
require_once 'vendor/autoload.php';

use Ksfraser\Auth\Auth;

// Configure database connection
$auth = Auth::quickSetup([
    'host' => 'localhost',
    'database' => 'your_database',
    'username' => 'your_username',
    'password' => 'your_password'
]);

// Create default admin user
$admin = $auth->createAdmin('admin', 'admin@example.com', 'secure_password');
echo "Admin created: " . $admin['username'] . " / " . $admin['password'];
```

### 2. Create Login Page

```php
<?php
require_once 'vendor/autoload.php';

use Ksfraser\Auth\Auth;

$auth = Auth::getInstance();
$result = $auth->handleLogin();

if ($result['success'] && !empty($result['redirect'])) {
    header('Location: ' . $result['redirect']);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <?= $auth->ui()->getCSS() ?>
</head>
<body>
    <h1>Login</h1>
    
    <?php if (!empty($result['message'])): ?>
        <?= $auth->ui()->errorMessage($result['message']) ?>
    <?php endif; ?>
    
    <?= $auth->ui()->loginForm() ?>
</body>
</html>
```

### 3. Protect Pages

```php
<?php
require_once 'vendor/autoload.php';

use Ksfraser\Auth\Auth;

$auth = Auth::getInstance();

// Require authentication
$auth->requireAuth();

// Or require admin access
$auth->requireAdmin();

// Get current user
$user = $auth->user();
echo "Welcome, " . $user['username'];
?>
```

## Configuration

### Basic Configuration

```php
$config = [
    'database' => [
        'host' => 'localhost',
        'database' => 'auth_db',
        'username' => 'db_user',
        'password' => 'db_pass'
    ],
    'security' => [
        'csrf_protection' => true,
        'rate_limiting' => true,
        'jwt_secret' => 'your-secret-key'
    ],
    'session' => [
        'lifetime' => 3600, // 1 hour
        'name' => 'MY_APP_SESSION'
    ]
];

$auth = Auth::getInstance($config);
```

### Configuration File

Create a configuration file (config/auth.php):

```php
<?php
return [
    'database' => [
        'host' => 'localhost',
        'database' => 'your_database',
        'username' => 'your_username',
        'password' => 'your_password'
    ],
    'ui' => [
        'login_url' => '/auth/login.php',
        'dashboard_url' => '/dashboard.php'
    ]
];
```

Load configuration:

```php
$auth = Auth::getInstance();
$auth->config()->loadFromFile('config/auth.php');
```

## Usage Examples

### User Registration

```php
$auth = Auth::getInstance();

try {
    $userId = $auth->register('john_doe', 'john@example.com', 'secure_password');
    echo "User registered with ID: " . $userId;
} catch (Exception $e) {
    echo "Registration failed: " . $e->getMessage();
}
```

### User Login

```php
$auth = Auth::getInstance();

try {
    $user = $auth->login('john_doe', 'secure_password');
    echo "Login successful: " . $user['username'];
} catch (Exception $e) {
    echo "Login failed: " . $e->getMessage();
}
```

### Authentication Status

```php
$auth = Auth::getInstance();

if ($auth->check()) {
    $user = $auth->user();
    echo "Logged in as: " . $user['username'];
    
    if ($auth->admin()) {
        echo " (Administrator)";
    }
} else {
    echo "Not logged in";
}
```

### Middleware Usage

```php
$auth = Auth::getInstance();

// Process authentication for current request
$auth->processRequest();

// Or use specific middleware
$auth->middleware()->requireAuth();
$auth->middleware()->requireAdmin();
$auth->middleware()->requireCSRF();
```

### UI Components

```php
$auth = Auth::getInstance();

// Login form
echo $auth->ui()->loginForm();

// Registration form
echo $auth->ui()->registerForm();

// User info display
echo $auth->ui()->userInfo();

// Navigation menu
echo $auth->ui()->navigationMenu([
    ['label' => 'Home', 'url' => '/', 'require_auth' => false],
    ['label' => 'Admin', 'url' => '/admin/', 'require_admin' => true]
]);

// Error/success messages
echo $auth->ui()->errorMessage('Login failed');
echo $auth->ui()->successMessage('Profile updated');
```

## Advanced Features

### Custom Password Validation

```php
use Ksfraser\Auth\AuthUtils;

$validation = AuthUtils::validatePasswordStrength('mypassword123', [
    'min_length' => 8,
    'require_uppercase' => true,
    'require_numbers' => true,
    'require_symbols' => false
]);

if (!$validation['valid']) {
    foreach ($validation['errors'] as $error) {
        echo $error . "\n";
    }
}

echo "Password strength: " . $validation['strength'];
```

### CSRF Protection

```php
$auth = Auth::getInstance();

// Generate CSRF token for forms
$token = $auth->csrf();

// In your form
echo '<input type="hidden" name="csrf_token" value="' . $token . '">';

// Validate CSRF token
if ($auth->validateCSRF($_POST['csrf_token'])) {
    // Process form
}
```

### Database Operations

```php
$auth = Auth::getInstance();

// Get system statistics
$stats = $auth->stats();
echo "Total users: " . $stats['users']['total'];

// Cleanup expired data
$auth->cleanup();

// Test system
$test = $auth->test();
if ($test['database_connection']) {
    echo "Database connection OK";
}
```

## Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with bcrypt
- **CSRF Protection**: Automatic token generation and validation
- **Rate Limiting**: Configurable request rate limiting
- **Session Security**: Secure session handling with regeneration
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: Input sanitization and output escaping
- **Account Lockout**: Automatic lockout after failed login attempts
- **Audit Logging**: Comprehensive security event logging

## Database Schema

The library automatically creates the following tables:

- `users` - User accounts and authentication data
- `user_sessions` - Session storage (optional)
- `password_reset_tokens` - Password reset functionality
- `email_verification_tokens` - Email verification
- `user_roles` - Role-based permissions
- `user_role_assignments` - User-role mappings
- `auth_audit_log` - Security audit trail

## API Reference

### Auth Class

- `Auth::getInstance($config)` - Get singleton instance
- `Auth::quickSetup($dbConfig)` - Quick setup with minimal config
- `setup()` - Initialize database and schema
- `createAdmin($username, $email, $password)` - Create admin user
- `login($username, $password)` - Authenticate user
- `logout()` - Log out current user
- `register($username, $email, $password, $isAdmin)` - Register new user
- `check()` - Check if user is logged in
- `admin()` - Check if user is admin
- `user()` - Get current user data
- `requireAuth()` - Require authentication
- `requireAdmin()` - Require admin access

### AuthManager Class

Core authentication functionality including user management, session handling, and security features.

### AuthMiddleware Class

Request-level authentication and authorization with support for route-based protection and CSRF validation.

### AuthUI Class

HTML form generation and UI components for rapid development.

### AuthConfig Class

Configuration management with support for file-based configuration and environment-specific settings.

### AuthUtils Class

Utility functions for password validation, token generation, input sanitization, and security helpers.

## Configuration Options

### Database Configuration

```php
'database' => [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'auth_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'table_prefix' => '',
    'users_table' => 'users'
]
```

### Security Configuration

```php
'security' => [
    'csrf_protection' => true,
    'max_login_attempts' => 5,
    'lockout_duration' => 900, // 15 minutes
    'jwt_secret' => 'your-secret-key',
    'jwt_expiry' => 86400, // 24 hours
    'rate_limiting' => false,
    'audit_logging' => true
]
```

### Password Configuration

```php
'password' => [
    'min_length' => 8,
    'max_length' => 128,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_symbols' => true,
    'cost' => 12
]
```

## Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- PDO MySQL extension
- OpenSSL extension (for secure random number generation)

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## Support

For issues and support:
- Create an issue on GitHub
- Review the documentation
- Check the examples directory

## Changelog

### Version 1.0.0
- Initial release
- Core authentication functionality
- User management system
- Security features
- UI components
- Configuration system
- Database integration
- Middleware support

---

**Ksfraser Authentication Library** - Secure, flexible, and reusable authentication for PHP applications.
