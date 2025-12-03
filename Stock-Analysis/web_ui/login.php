<?php
/**
 * User Login Page
 */

require_once __DIR__ . '/UserAuthDAO.php';

$auth = new UserAuthDAO();

// Check for database connection errors
if (!$auth->getPdo()) {
    $errors = $auth->getErrors();
    if (!empty($errors)) {
        error_log("Login page - DB connection failed: " . implode(", ", $errors));
    }
}

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$message = '';

// Handle logout message
if (isset($_GET['logout'])) {
    $message = 'You have been successfully logged out.';
}

// Handle registration success
if (isset($_GET['registered'])) {
    $message = 'Registration successful! Please log in with your credentials.';
}

// Get return URL if provided
$returnUrl = $_GET['return_url'] ?? 'dashboard.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            throw new Exception('Please enter both username and password');
        }
        
        $user = $auth->loginUser($username, $password);
        
        // Redirect to return URL or dashboard after successful login
        $redirectTo = $_POST['return_url'] ?? $_GET['return_url'] ?? 'dashboard.php';
        // Sanitize return URL to prevent open redirects
        if (!preg_match('/^[a-zA-Z0-9_\-\.\?\/=&]+$/', $redirectTo) || strpos($redirectTo, '//') !== false) {
            $redirectTo = 'dashboard.php';
        }
        
        // Queue priority jobs for user's portfolio (MQTT-based system)
        // This is optional functionality - errors won't prevent login
        try {
            // Check if required files exist before loading
            if (class_exists('UserPortfolioJobManager') || 
                file_exists(__DIR__ . '/../UserPortfolioJobManager.php')) {
                
                if (!class_exists('UserPortfolioJobManager')) {
                    require_once __DIR__ . '/../UserPortfolioJobManager.php';
                }
                
                // Get database connection if available
                $db = null;
                if (method_exists($auth, 'getDatabase')) {
                    $db = $auth->getDatabase();
                } elseif (method_exists($auth, 'getPdo')) {
                    $db = $auth->getPdo();
                }
                
                // Only proceed if we have a database connection
                if ($db !== null) {
                    // Load job processor configuration
                    $configFile = __DIR__ . '/../stock_job_processor.yml';
                    if (file_exists($configFile) && function_exists('yaml_parse_file')) {
                        $config = yaml_parse_file($configFile);
                    } else {
                        // Fallback configuration if YAML extension not available
                        $config = [
                            'job_processor' => [
                                'stock_jobs' => [
                                    'portfolio_priority' => ['data_staleness_threshold' => 30],
                                    'analysis' => ['cache_ttl' => 360]
                                ],
                                'jobs' => [
                                    'priority_rules' => [
                                        'user_login' => 1,
                                        'user_request' => 3,
                                        'scheduled_update' => 5,
                                        'background_analysis' => 8
                                    ]
                                ],
                                'portfolio' => [
                                    'priority_factors' => [
                                        'user_activity' => 0.4,
                                        'data_age' => 0.2
                                    ]
                                ]
                            ]
                        ];
                    }
                    
                    // Simple logger for job manager
                    $logger = new class {
                        public function info($message) { error_log("INFO: " . $message); }
                        public function warning($message) { error_log("WARNING: " . $message); }
                        public function error($message) { error_log("ERROR: " . $message); }
                        public function debug($message) { error_log("DEBUG: " . $message); }
                    };
                    
                    $portfolioJobManager = new UserPortfolioJobManager($config['job_processor'], $logger, $db);
                    $result = $portfolioJobManager->processUserLogin($user['id']);
                    
                    if (!$result['success']) {
                        error_log("Portfolio job queue error during login: " . $result['error']);
                    }
                }
            }
        } catch (Exception $e) {
            // Silently handle job queue errors to not interfere with login
            error_log("Portfolio job manager error during login: " . $e->getMessage());
        } catch (Error $e) {
            // Catch PHP errors (like undefined variables) and continue
            error_log("Portfolio job manager error during login: " . $e->getMessage());
        }
        
        // Redirect to intended page (already sanitized above)
        header("Location: $redirectTo");
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Portfolio Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        
        .login-header p {
            color: #666;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }
        
        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        
        .login-btn:hover {
            opacity: 0.9;
        }
        
        .login-btn:active {
            transform: translateY(1px);
        }
        
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #e57373;
        }
        
        .alert-success {
            background: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #81c784;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .demo-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .demo-info h4 {
            margin: 0 0 10px 0;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>💼 Portfolio Login</h1>
            <p>Secure access to your investment portfolio</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($returnUrl); ?>">
            
            <div class="form-group">
                <label for="username">Username or Email:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="login-btn">
                🔐 Sign In
            </button>
        </form>
        
        <div class="links">
            <a href="register.php">Create Account</a>
            <span>|</span>
            <a href="forgot_password.php">Forgot Password?</a>
            <span>|</span>
            <a href="reset_password.php">Reset Password (token)</a>
            <span>|</span>
            <a href="admin_reset_password.php">Admin Reset User Password</a>
        </div>
        
        <div class="demo-info">
            <h4>🧪 Demo Access</h4>
            <p>For testing, you can register a new account or use demo credentials once the database is connected.</p>
        </div>
        
        <!-- Back to Public Area -->
        <div style="text-align: center; margin-top: 20px;">
            <a href="../" style="color: #666; text-decoration: none; font-size: 14px;">
                ← Back to Portfolio View
            </a>
        </div>
    </div>
</body>
</html>
