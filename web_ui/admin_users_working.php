<?php
/**
 * Simple Admin User Management - Working Version
 */

// Enable error reporting to see what's causing issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/UserAuthDAO.php';

$auth = new UserAuthDAO();

// Check if user is admin
$auth->requireAdmin();

$currentUser = $auth->getCurrentUser();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create_user':
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = trim($_POST['password'] ?? '');
                $isAdmin = isset($_POST['is_admin']);
                
                if (empty($username) || empty($email) || empty($password)) {
                    throw new Exception('All fields are required');
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email address');
                }
                
                $userId = $auth->registerUser($username, $email, $password, $isAdmin);
                $message = "User created successfully with ID: $userId";
                $messageType = 'success';
                break;
                
            case 'delete_user':
                $userId = intval($_POST['user_id'] ?? 0);
                if ($userId <= 0) {
                    throw new Exception('Invalid user ID');
                }
                
                $success = $auth->deleteUser($userId);
                if ($success) {
                    $message = 'User deleted successfully';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to delete user');
                }
                break;
                
            case 'toggle_admin':
                $userId = intval($_POST['user_id'] ?? 0);
                $makeAdmin = isset($_POST['make_admin']);
                
                if ($userId <= 0) {
                    throw new Exception('Invalid user ID');
                }
                
                $success = $auth->updateUserAdminStatus($userId, $makeAdmin);
                if ($success) {
                    $status = $makeAdmin ? 'admin' : 'regular user';
                    $message = "User status updated to $status";
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to update admin status');
                }
                break;
                
            default:
                throw new Exception('Unknown action');
        }
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
        error_log("Admin users error: " . $e->getMessage());
    }
}

// Get all users
try {
    $users = $auth->getAllUsers(1000);
} catch (Exception $e) {
    $users = [];
    $message = 'Error loading users: ' . $e->getMessage();
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 30px;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .btn {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .users-table th,
        .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .users-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .users-table tr:hover {
            background: #f5f5f5;
        }
        
        .admin-badge {
            background: #e74c3c;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .user-badge {
            background: #3498db;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
        
        .back-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
    </style>
</head>
<body>

<?php
// Use centralized NavigationService (follows SRP)
require_once 'NavigationService.php';
$navigationService = new NavigationService();
echo $navigationService->renderNavigationHeader('User Management - Admin Panel', 'admin_users');
?>

    <div class="container">
        <div class="header">
            <h1>👥 User Management</h1>
            <p>Administrative control panel for managing system users</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Create New User -->
            <div class="section">
                <h2>➕ Create New User</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="create_user">

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_admin" name="is_admin">
                                <label for="is_admin">Make Admin</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success">Create User</button>
                </form>
            </div>

            <!-- User List -->
            <div class="section">
                <h2>👤 Current Users (<?= count($users) ?>)</h2>

                <?php if (empty($users)): ?>
                    <p>No users found or error loading users.</p>
                <?php else: ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <?php if ($user['is_admin']): ?>
                                            <span class="admin-badge">Admin</span>
                                        <?php else: ?>
                                            <span class="user-badge">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($user['created_at']) && !empty($user['created_at'])) {
                                            echo date('M j, Y', strtotime($user['created_at']));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <!-- Toggle Admin Status -->
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_admin">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <?php if (!$user['is_admin']): ?>
                                                    <input type="hidden" name="make_admin" value="1">
                                                    <button type="submit" class="btn btn-warning" 
                                                            onclick="return confirm('Make this user an admin?')">
                                                        Make Admin
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-warning"
                                                            onclick="return confirm('Remove admin privileges?')">
                                                        Remove Admin
                                                    </button>
                                                <?php endif; ?>
                                            </form>

                                            <!-- Delete User -->
                                            <?php if ($user['id'] != $currentUser['id']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" class="btn btn-danger"
                                                            onclick="return confirm('Are you sure you want to delete this user?')">       
                                                        Delete
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="back-link">
                <a href="dashboard.php">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>