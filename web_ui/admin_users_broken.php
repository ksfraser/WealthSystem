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

/**
 * Handle form submissions using properly namespaced User classes
 */
function handleUserManagementForms($userAuth, $currentUser) {
    $message = '';
    $messageType = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $request = new UserManagementRequest();
        
        if ($request->hasAction()) {
            $service = new UserManagementService($userAuth, $currentUser);
            
            if ($request->isCreateAction()) {
                list($message, $messageType) = $service->createUser($request);
            } elseif ($request->isDeleteAction()) {
                list($message, $messageType) = $service->deleteUser($request);
            } elseif ($request->isToggleAdminAction()) {
                list($message, $messageType) = $service->toggleAdminStatus($request);
            }
        }
    }
    
    return [$message, $messageType];
}

// Handle form submissions
list($message, $messageType) = handleUserManagementForms($userAuth, $currentUser);

// Initialize the service for data operations
$service = new UserManagementService($userAuth, $currentUser);

// Get all users using the service
try {
    $users = $service->getAllUsers();
    $userStats = $service->getUserStatistics($users);
} catch (Exception $e) {
    $users = [];
    $userStats = ['total' => 0, 'admins' => 0, 'active' => 0];
    $message = $e->getMessage();
    $messageType = 'error';
}

$currentUser = $userAuth->getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Enhanced Trading System</title>
    <style>
        <?php 
        // Include NavigationManager for consistent navigation
        require_once 'NavigationManager.php';
        $navManager = new NavigationManager();
        echo $navManager->getNavigationCSS(); 
        
        // Add basic CSS for user management
        echo CSSManager::getBaseCSS();
        echo CSSManager::getFormCSS();
        echo CSSManager::getTableCSS();
        echo CSSManager::getCardCSS();
        echo CSSManager::getUtilityCSS();
        ?>
    </style>
</head>
<body>

<?php 
// Render navigation with MenuService integration
require_once 'NavigationManager.php';
$navManager = new NavigationManager();
$navManager->renderNavigationHeader('User Management', 'users');

// Add admin menu items for quick access
$isAdminUser = $userAuth->isAdmin();
$menuItems = MenuService::getMenuItems('users', $isAdminUser, true);

// Display quick access links for admin users
if ($isAdminUser && !empty($menuItems)) {
    echo '<div style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px;">';
    echo '<strong>Quick Access:</strong> ';
    foreach ($menuItems as $item) {
        if (isset($item['admin_only']) && $item['admin_only'] && !$item['active']) {
            echo '<a href="' . htmlspecialchars($item['url']) . '" style="margin-right: 10px; color: #007cba; text-decoration: none;">' . htmlspecialchars($item['label']) . '</a>';
        }
    }
    echo '</div>';
}
?>

<div class="container">
    <h1>👥 User Management</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <!-- User Statistics -->
    <div class="management-section">
        <h2>User Statistics</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $userStats['total']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $userStats['admins']; ?></div>
                <div class="stat-label">Administrators</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $userStats['total'] - $userStats['admins']; ?></div>
                <div class="stat-label">Regular Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $userStats['active']; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
        </div>
    </div>
    
    <!-- Create New User -->
    <div class="management-section">
        <h2>Create New User</h2>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required minlength="3" maxlength="50">
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_admin"> Administrator
                </label>
            </div>
            <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
        </form>
    </div>
    
    <!-- User List -->
    <div class="management-section">
        <h2>All Users</h2>
        <?php if (empty($users)): ?>
            <p>No users found.</p>
        <?php else: ?>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($user['username']); ?>
                                <?php if ($user['id'] === $currentUser['id']): ?>
                                    <small>(You)</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php if ($user['is_admin']): ?>
                                    <span class="admin-badge">Admin</span>
                                <?php else: ?>
                                    User
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['last_login']): ?>
                                    <?php echo date('M j, Y H:i', strtotime($user['last_login'])); ?>
                                <?php else: ?>
                                    <em>Never</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['id'] !== $currentUser['id']): ?>
                                    <!-- Toggle Admin Status -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <?php if (!$user['is_admin']): ?>
                                            <input type="hidden" name="is_admin" value="1">
                                            <button type="submit" name="toggle_admin" class="btn btn-success btn-small" 
                                                    onclick="return confirm('Promote this user to administrator?')">
                                                ⬆️ Make Admin
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="is_admin" value="0">
                                            <button type="submit" name="toggle_admin" class="btn btn-warning btn-small"
                                                    onclick="return confirm('Remove admin privileges and make this user a regular user?')">
                                                ⬇️ Make Regular User
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                    
                                    <!-- Delete User -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger btn-small"
                                                onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                            Delete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <em>Current User</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div class="management-section">
        <?php require_once 'QuickActions.php'; QuickActions::render(); ?>
    </div>
</div>

</body>
</html>
