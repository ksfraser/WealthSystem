<?php
/**
 * Web-based Admin User Creator
 * 
 * This page allows you to create admin users through the web interface.
 * For security, this should only be used during initial setup.
 */

require_once __DIR__ . '/UserAuthDAO.php';

$auth = new UserAuthDAO();
$message = '';
$messageType = '';

// Check if any users exist - if none, allow admin creation
// If users exist, require current user to be admin
$allUsers = $auth->getAllUsers();
$hasUsers = count($allUsers) > 0;
$hasAdmins = count(array_filter($allUsers, function($user) { return $user['is_admin']; })) > 0;

if ($hasUsers && !$auth->isLoggedIn()) {
    header('Location: login.php?error=admin_required');
    exit;
}

if ($hasUsers && $auth->isLoggedIn() && !$auth->isAdmin()) {
    header('Location: dashboard.php?error=admin_required');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            throw new Exception('All fields are required');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address');
        }
        
        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters long');
        }
        
        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }
        
        // Create admin user
        $userId = $auth->registerUser($username, $email, $password, true); // true = admin
        
        if ($userId) {
            $message = "Admin user '{$username}' created successfully! User ID: {$userId}";
            
            // Initialize RBAC system after first admin creation
            try {
                require_once __DIR__ . '/simple_rbac_migration.php';
                $rbacMigration = new SimpleRBACMigration();
                $rbacMigration->run();
                $message .= "<br>✅ RBAC system initialized successfully!";
            } catch (Exception $rbacError) {
                $message .= "<br>⚠️  RBAC initialization failed: " . $rbacError->getMessage();
                error_log("RBAC initialization failed: " . $rbacError->getMessage());
            }
            
            $messageType = 'success';
        } else {
            throw new Exception('Failed to create admin user');
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User - Enhanced Trading System</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 500px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            margin: 0 0 10px 0;
            font-size: 2rem;
        }
        
        .header p {
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
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .info-box {
            background: #e9ecef;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .info-box h3 {
            margin: 0 0 10px 0;
            color: #495057;
        }
        
        .info-box p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Create Admin User</h1>
            <p>Enhanced Trading System Administration</p>
        </div>
        
        <?php if (!$hasUsers): ?>
            <div class="info-box">
                <h3>Initial Setup</h3>
                <p>No users exist in the system. Create your first admin user to get started.</p>
            </div>
        <?php elseif (!$hasAdmins): ?>
            <div class="info-box">
                <h3>No Admin Users</h3>
                <p>The system has users but no administrators. Create an admin user to manage the system.</p>
            </div>
        <?php else: ?>
            <div class="info-box">
                <h3>Add Another Admin</h3>
                <p>You are logged in as an administrator and can create additional admin users.</p>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required minlength="3" maxlength="50" 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            
            <button type="submit" class="btn">Create Admin User</button>
        </form>
        
        <div class="back-link">
            <?php if ($hasUsers && $auth->isLoggedIn()): ?>
                <a href="dashboard.php">← Back to Dashboard</a>
                <?php if ($auth->isAdmin()): ?>
                    | <a href="admin_users.php">User Management</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php">← Back to Login</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
