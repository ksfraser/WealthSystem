<?php
/**
 * Admin Advisor Management - Dedicated screen for managing advisor-client relationships
 */

// Enable error reporting to see what's causing 500 errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/UserAuthDAO.php';
require_once __DIR__ . '/RBACService.php';
require_once __DIR__ . '/NavigationManager.php';

$auth = new UserAuthDAO();
$rbac = new RBACService();

// Check if user is admin
$auth->requireAdmin();

// Initialize navigation
$navManager = new NavigationManager();

$currentUser = $auth->getCurrentUser();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'assign_advisor':
                $result = handleAssignAdvisor($auth, $_POST);
                break;
                
            case 'update_advisor':
                $result = handleUpdateAdvisor($auth, $_POST);
                break;
                
            case 'revoke_advisor':
                $result = handleRevokeAdvisor($auth, $_POST);
                break;
                
            default:
                $result = ['success' => false, 'message' => 'Unknown action'];
        }
        
        $message = $result['message'] ?? '';
        $messageType = $result['success'] ? 'success' : 'error';
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
        error_log("Admin advisor management error: " . $e->getMessage());
    }
}

// Get filters
$filterType = $_GET['filter'] ?? 'all';
$filterValue = $_GET['value'] ?? '';

// Get all users for dropdowns
try {
    $users = $auth->getAllUsers(1000);
} catch (Exception $e) {
    $users = [];
    $message = 'Error loading users: ' . $e->getMessage();
    $messageType = 'error';
}

// Get advisor-client relationships
try {
    $advisorRelationships = getAdvisorRelationships($auth, $filterType, $filterValue);
} catch (Exception $e) {
    $advisorRelationships = [];
    error_log("Error loading advisor relationships: " . $e->getMessage());
}

function handleAssignAdvisor($auth, $postData) {
    $clientUserId = intval($postData['client_user_id'] ?? 0);
    $advisorUserId = intval($postData['advisor_user_id'] ?? 0);
    $permissionLevel = trim($postData['permission_level'] ?? '');
    $adminNotes = trim($postData['admin_notes'] ?? '');
    
    if ($clientUserId <= 0 || $advisorUserId <= 0) {
        return ['success' => false, 'message' => 'Invalid client or advisor ID'];
    }
    
    if ($clientUserId === $advisorUserId) {
        return ['success' => false, 'message' => 'Client and advisor cannot be the same user'];
    }
    
    if (!in_array($permissionLevel, ['read', 'read_write'])) {
        return ['success' => false, 'message' => 'Invalid permission level'];
    }
    
    try {
        // Get PDO connection from UserAuthDAO
        $reflection = new ReflectionClass($auth);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdo = $pdoProperty->getValue($auth);
        
        // Check if relationship already exists
        $stmt = $pdo->prepare("
            SELECT id FROM user_advisors 
            WHERE user_id = ? AND advisor_id = ? AND status != 'revoked'
        ");
        $stmt->execute([$clientUserId, $advisorUserId]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Advisor is already assigned to this client'];
        }
        
        // Insert new advisor-client relationship
        $currentUserId = $auth->getCurrentUserId();
        $stmt = $pdo->prepare("
            INSERT INTO user_advisors (
                user_id, advisor_id, permission_level, status, 
                accepted_at, created_by, notes
            ) VALUES (?, ?, ?, 'active', NOW(), ?, ?)
        ");
        
        $notes = "Administrative assignment. " . ($adminNotes ?: 'No additional notes.');
        
        if ($stmt->execute([$clientUserId, $advisorUserId, $permissionLevel, $currentUserId, $notes])) {
            return ['success' => true, 'message' => 'Advisor successfully assigned to client'];
        } else {
            return ['success' => false, 'message' => 'Failed to assign advisor'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error assigning advisor: ' . $e->getMessage()];
    }
}

function handleUpdateAdvisor($auth, $postData) {
    $relationshipId = intval($postData['relationship_id'] ?? 0);
    $permissionLevel = trim($postData['permission_level'] ?? '');
    $notes = trim($postData['notes'] ?? '');
    
    if ($relationshipId <= 0) {
        return ['success' => false, 'message' => 'Invalid relationship ID'];
    }
    
    if (!in_array($permissionLevel, ['read', 'read_write'])) {
        return ['success' => false, 'message' => 'Invalid permission level'];
    }
    
    try {
        // Get PDO connection from UserAuthDAO
        $reflection = new ReflectionClass($auth);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdo = $pdoProperty->getValue($auth);
        
        // Update the relationship
        $stmt = $pdo->prepare("
            UPDATE user_advisors 
            SET permission_level = ?, notes = ?
            WHERE id = ? AND status = 'active'
        ");
        
        if ($stmt->execute([$permissionLevel, $notes, $relationshipId])) {
            $rowCount = $stmt->rowCount();
            if ($rowCount > 0) {
                return ['success' => true, 'message' => 'Advisor relationship updated successfully'];
            } else {
                return ['success' => false, 'message' => 'No active relationship found to update'];
            }
        } else {
            return ['success' => false, 'message' => 'Failed to update advisor relationship'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error updating advisor relationship: ' . $e->getMessage()];
    }
}

function handleRevokeAdvisor($auth, $postData) {
    $relationshipId = intval($postData['relationship_id'] ?? 0);
    
    if ($relationshipId <= 0) {
        return ['success' => false, 'message' => 'Invalid relationship ID'];
    }
    
    try {
        // Get PDO connection from UserAuthDAO
        $reflection = new ReflectionClass($auth);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdo = $pdoProperty->getValue($auth);
        
        // Update status to revoked
        $stmt = $pdo->prepare("
            UPDATE user_advisors 
            SET status = 'revoked'
            WHERE id = ? AND status = 'active'
        ");
        
        if ($stmt->execute([$relationshipId])) {
            $rowCount = $stmt->rowCount();
            if ($rowCount > 0) {
                return ['success' => true, 'message' => 'Advisor access revoked successfully'];
            } else {
                return ['success' => false, 'message' => 'No active advisor relationship found'];
            }
        } else {
            return ['success' => false, 'message' => 'Failed to revoke advisor access'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error revoking advisor access: ' . $e->getMessage()];
    }
}

function getAdvisorRelationships($auth, $filterType = 'all', $filterValue = '') {
    try {
        // Get PDO connection from UserAuthDAO
        $reflection = new ReflectionClass($auth);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdo = $pdoProperty->getValue($auth);
        
        $baseQuery = "
            SELECT 
                ua.id,
                ua.user_id as client_id,
                ua.advisor_id,
                ua.permission_level,
                ua.status,
                ua.accepted_at,
                ua.invited_at,
                ua.created_by,
                ua.notes,
                client.username as client_name,
                client.email as client_email,
                advisor.username as advisor_name,
                advisor.email as advisor_email,
                creator.username as created_by_name
            FROM user_advisors ua
            JOIN users client ON ua.user_id = client.id
            JOIN users advisor ON ua.advisor_id = advisor.id
            LEFT JOIN users creator ON ua.created_by = creator.id
        ";
        
        $whereClause = " WHERE ua.status IN ('active', 'pending', 'suspended')";
        $params = [];
        
        if ($filterType && $filterValue) {
            switch ($filterType) {
                case 'client':
                    $whereClause .= " AND (client.username LIKE ? OR client.email LIKE ?)";
                    $params[] = "%$filterValue%";
                    $params[] = "%$filterValue%";
                    break;
                case 'advisor':
                    $whereClause .= " AND (advisor.username LIKE ? OR advisor.email LIKE ?)";
                    $params[] = "%$filterValue%";
                    $params[] = "%$filterValue%";
                    break;
                case 'permission':
                    $whereClause .= " AND ua.permission_level = ?";
                    $params[] = $filterValue;
                    break;
                case 'status':
                    $whereClause .= " AND ua.status = ?";
                    $params[] = $filterValue;
                    break;
            }
        }
        
        $orderClause = " ORDER BY ua.accepted_at DESC, ua.invited_at DESC";
        
        $stmt = $pdo->prepare($baseQuery . $whereClause . $orderClause);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error fetching advisor relationships: " . $e->getMessage());
        return [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advisor Management - Admin Panel</title>
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
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
        
        .btn-small {
            font-size: 0.8rem;
            padding: 5px 10px;
        }
        
        .relationships-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .relationships-table th,
        .relationships-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .relationships-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .relationships-table tr:hover {
            background: #f5f5f5;
        }
        
        .role-badge {
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.7rem;
            margin-right: 5px;
        }
        
        .filter-section {
            background: #e8f4fd;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: end;
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
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
    </style>
</head>
<body>

<?php
// Use centralized NavigationService (follows SRP)
require_once 'NavigationService.php';
$navigationService = new NavigationService();
echo $navigationService->renderNavigationHeader('Advisor Management - Admin Panel', 'admin_advisor');
?>

    <div class="container">
        <div class="header">
            <h1>🤝 Advisor Management</h1>
            <p>Administrative control panel for managing advisor-client relationships</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <!-- New Advisor Assignment -->
            <div class="section">
                <h2>➕ Assign New Advisor</h2>
                <p class="support-info" style="background: #e3f2fd; padding: 15px; border-radius: 6px; margin-bottom: 20px; color: #1976d2;">
                    <strong>Note:</strong> This is for administrative support purposes only. 
                    Normal advisor-client relationships should be established through the invitation system.
                </p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="assign_advisor">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="client_user_id">Client:</label>
                            <select id="client_user_id" name="client_user_id" required>
                                <option value="">Select Client...</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>">
                                        <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="advisor_user_id">Advisor:</label>
                            <select id="advisor_user_id" name="advisor_user_id" required>
                                <option value="">Select Advisor...</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>">
                                        <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="permission_level">Access Level:</label>
                            <select id="permission_level" name="permission_level" required>
                                <option value="">Select Access Level...</option>
                                <option value="read">RO (Read Only) - Default</option>
                                <option value="read_write">RW (Read/Write)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_notes">Admin Notes:</label>
                            <textarea id="admin_notes" name="admin_notes" rows="3" 
                                     placeholder="Reason for administrative assignment..."></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Assign Advisor</button>
                </form>
            </div>
            
            <!-- Filter Section -->
            <div class="section">
                <h2>🔍 Filter Relationships</h2>
                <div class="filter-section">
                    <form method="GET" class="filter-form">
                        <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                            <label for="filter">Filter by:</label>
                            <select id="filter" name="filter">
                                <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>All Relationships</option>
                                <option value="client" <?= $filterType === 'client' ? 'selected' : '' ?>>Client Name/Email</option>
                                <option value="advisor" <?= $filterType === 'advisor' ? 'selected' : '' ?>>Advisor Name/Email</option>
                                <option value="permission" <?= $filterType === 'permission' ? 'selected' : '' ?>>Access Level</option>
                                <option value="status" <?= $filterType === 'status' ? 'selected' : '' ?>>Status</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0; min-width: 200px;">
                            <label for="value">Search Value:</label>
                            <input type="text" id="value" name="value" value="<?= htmlspecialchars($filterValue) ?>" 
                                   placeholder="Enter search term...">
                        </div>
                        
                        <button type="submit" class="btn">Apply Filter</button>
                        <a href="admin_advisor_management.php" class="btn btn-warning">Clear</a>
                    </form>
                </div>
            </div>
            
            <!-- Current Relationships -->
            <div class="section">
                <h2>📋 Current Advisor-Client Relationships (<?= count($advisorRelationships) ?>)</h2>
                
                <?php if (empty($advisorRelationships)): ?>
                    <p><em>No advisor-client relationships found<?= $filterType !== 'all' ? ' matching your filter' : '' ?>.</em></p>
                <?php else: ?>
                    <table class="relationships-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Advisor</th>
                                <th>Access Level</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Created By</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($advisorRelationships as $relationship): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($relationship['client_name']) ?></strong><br>
                                        <small><?= htmlspecialchars($relationship['client_email']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($relationship['advisor_name']) ?></strong><br>
                                        <small><?= htmlspecialchars($relationship['advisor_email']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($relationship['permission_level'] === 'read'): ?>
                                            <span class="role-badge" style="background: #17a2b8;">RO</span>
                                        <?php else: ?>
                                            <span class="role-badge" style="background: #dc3545;">RW</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($relationship['status'] === 'active'): ?>
                                            <span class="role-badge" style="background: #28a745;">Active</span>
                                        <?php elseif ($relationship['status'] === 'pending'): ?>
                                            <span class="role-badge" style="background: #ffc107;">Pending</span>
                                        <?php else: ?>
                                            <span class="role-badge" style="background: #6c757d;"><?= htmlspecialchars($relationship['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($relationship['accepted_at']): ?>
                                            <?= date('M j, Y', strtotime($relationship['accepted_at'])) ?>
                                        <?php elseif ($relationship['invited_at']): ?>
                                            <small>Inv: <?= date('M j', strtotime($relationship['invited_at'])) ?></small>
                                        <?php else: ?>
                                            <em>N/A</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $relationship['created_by_name'] ? htmlspecialchars($relationship['created_by_name']) : '<em>System</em>' ?>
                                    </td>
                                    <td>
                                        <?php if ($relationship['notes']): ?>
                                            <span title="<?= htmlspecialchars($relationship['notes']) ?>">
                                                <?= htmlspecialchars(substr($relationship['notes'], 0, 30)) ?><?= strlen($relationship['notes']) > 30 ? '...' : '' ?>
                                            </span>
                                        <?php else: ?>
                                            <em>No notes</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-small" 
                                                onclick="editRelationship(<?= $relationship['id'] ?>, '<?= $relationship['permission_level'] ?>', '<?= htmlspecialchars($relationship['notes'], ENT_QUOTES) ?>')">
                                            Edit
                                        </button>
                                        
                                        <?php if ($relationship['status'] === 'active'): ?>
                                            <form method="POST" style="display: inline; margin-left: 5px;">
                                                <input type="hidden" name="action" value="revoke_advisor">
                                                <input type="hidden" name="relationship_id" value="<?= $relationship['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-small"
                                                        onclick="return confirm('Revoke this advisor relationship?')">
                                                    Revoke
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="back-link">
                <a href="admin_users.php">← Back to User Management</a> | 
                <a href="dashboard.php">← Back to Dashboard</a>
            </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Advisor Relationship</h2>
            
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update_advisor">
                <input type="hidden" name="relationship_id" id="edit_relationship_id">
                
                <div class="form-group">
                    <label for="edit_permission_level">Access Level:</label>
                    <select id="edit_permission_level" name="permission_level" required>
                        <option value="read">RO (Read Only)</option>
                        <option value="read_write">RW (Read/Write)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_notes">Notes:</label>
                    <textarea id="edit_notes" name="notes" rows="4" 
                             placeholder="Update notes for this relationship..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-success">Update Relationship</button>
                <button type="button" class="btn" onclick="closeEditModal()">Cancel</button>
            </form>
        </div>
    </div>
    
    <script>
        function editRelationship(id, permissionLevel, notes) {
            document.getElementById('edit_relationship_id').value = id;
            document.getElementById('edit_permission_level').value = permissionLevel;
            document.getElementById('edit_notes').value = notes;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }
        
        // Close modal when clicking X
        document.querySelector('.close').onclick = closeEditModal;
    </script>
</body>
</html>