<?php
/**
 * Enhanced Brokerages Management System - Simplified
 * Compatible with existing infrastructure
 */

require_once __DIR__ . '/CommonDAO.php';

class SimpleBrokeragesDAO extends CommonDAO {
    
    public function __construct() {
        parent::__construct('LegacyDatabaseConfig');
    }
    
    public function checkTableStatus() {
        try {
            if (!$this->pdo) {
                return ['status' => 'no_connection', 'message' => 'Database connection not available'];
            }
            
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'brokerages'");
            if ($stmt->rowCount() === 0) {
                return ['status' => 'missing', 'message' => 'Brokerages table does not exist'];
            }
            
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM brokerages");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = (int)$result['count'];
            
            if ($count === 0) {
                return ['status' => 'empty', 'message' => 'Brokerages table exists but is empty'];
            }
            
            return ['status' => 'ready', 'message' => "Brokerages table ready with {$count} entries"];
            
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Error checking table status: ' . $e->getMessage()];
        }
    }
    
    public function createTable() {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            require_once __DIR__ . '/SchemaMigrator.php';
            $schemaDir = __DIR__ . '/schema';
            $migrator = new SchemaMigrator($this->pdo, $schemaDir);
            $migrator->migrate();
            
            return true;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function prepopulateStandardBrokerages() {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            $standardBrokerages = [
                'RBC Direct Investing', 'TD Direct Investing', 'Scotia iTRADE',
                'BMO InvestorLine', 'CIBC Investor\'s Edge', 'National Bank Direct Brokerage',
                'Desjardins Online Brokerage', 'Questrade', 'Qtrade', 'Wealthsimple Trade',
                'Virtual Brokers', 'HSBC InvestDirect', 'Credential Direct',
                'Interactive Brokers Canada', 'Laurentian Bank Discount Brokerage',
                'Manulife Securities', 'Canaccord Genuity', 'Richardson Wealth',
                'PI Financial', 'Raymond James', 'Industrial Alliance Securities',
                'Mackie Research Capital', 'Leede Jones Gable', 'BBS Securities',
                'Friedberg Direct', 'B2B Bank Dealer Services', 'Vanguard Canada',
                'Invesco Canada', 'Fidelity Investments Canada', 'Edward Jones Canada', 'Other'
            ];
            
            $inserted = 0;
            foreach ($standardBrokerages as $name) {
                $stmt = $this->pdo->prepare('INSERT IGNORE INTO brokerages (name) VALUES (?)');
                if ($stmt->execute([$name]) && $stmt->rowCount() > 0) {
                    $inserted++;
                }
            }
            
            return $inserted;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function getAllBrokerages($search = '', $limit = 100, $offset = 0) {
        try {
            if (!$this->pdo) return [];
            
            $sql = "SELECT * FROM brokerages";
            $params = [];
            
            if (!empty($search)) {
                $sql .= " WHERE name LIKE ?";
                $params[] = "%{$search}%";
            }
            
            $sql .= " ORDER BY name LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getBrokeragesCount($search = '') {
        try {
            if (!$this->pdo) return 0;
            
            $sql = "SELECT COUNT(*) as count FROM brokerages";
            $params = [];
            
            if (!empty($search)) {
                $sql .= " WHERE name LIKE ?";
                $params[] = "%{$search}%";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)$result['count'];
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function getBrokerageById($id) {
        try {
            if (!$this->pdo) return null;
            
            $stmt = $this->pdo->prepare("SELECT * FROM brokerages WHERE id = ?");
            $stmt->execute([$id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function createBrokerage($name) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            if (empty(trim($name))) {
                throw new Exception('Brokerage name is required');
            }
            
            if (strlen($name) > 128) {
                throw new Exception('Brokerage name must be 128 characters or less');
            }
            
            $stmt = $this->pdo->prepare('INSERT INTO brokerages (name) VALUES (?)');
            if ($stmt->execute([$name])) {
                return $this->pdo->lastInsertId();
            }
            
            throw new Exception('Failed to create brokerage');
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function updateBrokerage($id, $name) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            if (empty(trim($name))) {
                throw new Exception('Brokerage name is required');
            }
            
            if (strlen($name) > 128) {
                throw new Exception('Brokerage name must be 128 characters or less');
            }
            
            $stmt = $this->pdo->prepare('UPDATE brokerages SET name = ? WHERE id = ?');
            if ($stmt->execute([$name, $id])) {
                return $stmt->rowCount() > 0;
            }
            
            throw new Exception('Failed to update brokerage');
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function deleteBrokerage($id) {
        try {
            if (!$this->pdo) {
                throw new Exception('Database connection not available');
            }
            
            // Check if brokerage is in use
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM accounts WHERE brokerage_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ((int)$result['count'] > 0) {
                throw new Exception('Cannot delete brokerage: it is referenced by existing accounts');
            }
            
            $stmt = $this->pdo->prepare('DELETE FROM brokerages WHERE id = ?');
            if ($stmt->execute([$id])) {
                return $stmt->rowCount() > 0;
            }
            
            throw new Exception('Failed to delete brokerage');
            
        } catch (Exception $e) {
            throw $e;
        }
    }
}

// Initialize DAO
$dao = new SimpleBrokeragesDAO();
$tableStatus = $dao->checkTableStatus();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_table':
                    $dao->createTable();
                    $message = "✅ Brokerages table created successfully!";
                    $tableStatus = $dao->checkTableStatus();
                    break;
                    
                case 'prepopulate':
                    $count = $dao->prepopulateStandardBrokerages();
                    $message = "✅ Added {$count} standard Canadian brokerages!";
                    $tableStatus = $dao->checkTableStatus();
                    break;
                    
                case 'create':
                    $name = trim($_POST['name'] ?? '');
                    $id = $dao->createBrokerage($name);
                    $message = "✅ Brokerage '{$name}' created successfully!";
                    break;
                    
                case 'update':
                    $id = (int)$_POST['id'];
                    $name = trim($_POST['name'] ?? '');
                    $dao->updateBrokerage($id, $name);
                    $message = "✅ Brokerage updated successfully!";
                    break;
                    
                case 'delete':
                    $id = (int)$_POST['id'];
                    $dao->deleteBrokerage($id);
                    $message = "✅ Brokerage deleted successfully!";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Get data for display
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$brokerages = $dao->getAllBrokerages($search, $limit, $offset);
$totalCount = $dao->getBrokeragesCount($search);
$totalPages = ceil($totalCount / $limit);

$editBrokerage = null;
if (isset($_GET['edit'])) {
    $editBrokerage = $dao->getBrokerageById((int)$_GET['edit']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brokerages Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; }
        .status-box { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .status-error { background: #ffeeee; border: 1px solid #cc0000; color: #cc0000; }
        .status-success { background: #eeffee; border: 1px solid #00cc00; color: #00cc00; }
        .status-info { background: #eeeeff; border: 1px solid #0066cc; color: #0066cc; }
        
        .setup-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .setup-section h3 { margin-top: 0; color: #333; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .form-group input[type="submit"], .btn { background: #007cba; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; }
        .form-group input[type="submit"]:hover, .btn:hover { background: #005a87; }
        
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #545b62; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-small { padding: 5px 10px; font-size: 12px; margin: 2px; }
        
        .search-form { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .search-form input { display: inline-block; width: 300px; margin-right: 10px; }
        
        .brokerages-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .brokerages-table th, .brokerages-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .brokerages-table th { background-color: #f2f2f2; font-weight: bold; }
        .brokerages-table tr:hover { background-color: #f5f5f5; }
        
        .pagination { text-align: center; margin: 20px 0; }
        .pagination a { display: inline-block; padding: 8px 12px; margin: 0 2px; border: 1px solid #ddd; text-decoration: none; }
        .pagination a:hover { background: #f5f5f5; }
        .pagination .current { background: #007cba; color: white; }
        
        .stats { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .quick-actions { margin: 15px 0; }
        .quick-actions button { margin: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏦 Brokerages Management</h1>
        
        <!-- Database Connection Status -->
        <div class="status-box <?php echo $tableStatus['status'] === 'no_connection' ? 'status-error' : 'status-info'; ?>">
            <strong>Database Connection Status:</strong> 
            <?php if ($tableStatus['status'] === 'no_connection'): ?>
                ❌ Not Connected
                <p>Database functionality is currently unavailable.</p>
            <?php else: ?>
                ✅ Connected
            <?php endif; ?>
        </div>
        
        <!-- Messages -->
        <?php if (isset($message)): ?>
            <div class="status-box status-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="status-box status-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($tableStatus['status'] !== 'no_connection'): ?>
            
            <!-- Table Setup -->
            <?php if ($tableStatus['status'] === 'missing'): ?>
                <div class="setup-section">
                    <h3>🔧 Initial Setup Required</h3>
                    <p>The brokerages table needs to be created before you can manage brokerages.</p>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="create_table">
                        <input type="submit" value="Create Brokerages Table" class="btn">
                    </form>
                </div>
                
            <?php elseif ($tableStatus['status'] === 'empty'): ?>
                <div class="setup-section">
                    <h3>📋 Prepopulate Standard Brokerages</h3>
                    <p>Add standard Canadian brokerages to get started.</p>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="prepopulate">
                        <input type="submit" value="Add Standard Canadian Brokerages" class="btn">
                    </form>
                </div>
                
            <?php else: ?>
                <!-- Table Status Info -->
                <div class="stats">
                    <strong>Status:</strong> <?php echo htmlspecialchars($tableStatus['message']); ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="prepopulate">
                        <button type="submit" class="btn btn-secondary">Add More Standard Brokerages</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <?php if ($tableStatus['status'] === 'ready'): ?>
                
                <!-- Search Form -->
                <form method="get" class="search-form">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search brokerages...">
                    <input type="submit" value="Search" class="btn">
                    <?php if (!empty($search)): ?>
                        <a href="?" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
                
                <!-- Add/Edit Form -->
                <div class="setup-section">
                    <h3><?php echo $editBrokerage ? 'Edit Brokerage' : 'Add New Brokerage'; ?></h3>
                    <form method="post">
                        <input type="hidden" name="action" value="<?php echo $editBrokerage ? 'update' : 'create'; ?>">
                        <?php if ($editBrokerage): ?>
                            <input type="hidden" name="id" value="<?php echo $editBrokerage['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="name">Brokerage Name:</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($editBrokerage['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <input type="submit" value="<?php echo $editBrokerage ? 'Update Brokerage' : 'Add Brokerage'; ?>" class="btn">
                            <?php if ($editBrokerage): ?>
                                <a href="?" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Brokerages List -->
                <?php if (!empty($brokerages)): ?>
                    <h3>Brokerages (<?php echo $totalCount; ?> total)</h3>
                    
                    <table class="brokerages-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($brokerages as $brokerage): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($brokerage['id']); ?></td>
                                    <td><?php echo htmlspecialchars($brokerage['name']); ?></td>
                                    <td>
                                        <a href="?edit=<?php echo $brokerage['id']; ?>" class="btn btn-small btn-secondary">Edit</a>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $brokerage['id']; ?>">
                                            <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i === $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <p><em>No brokerages found<?php echo !empty($search) ? ' matching your search' : ''; ?>.</em></p>
                <?php endif; ?>
                
            <?php endif; ?>
            
        <?php endif; ?>
        
        <!-- Links -->
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p><strong>Related:</strong> 
                <a href="admin_account_types.php">Account Types</a> | 
                <a href="admin_brokerages.php">Original Brokerages</a> |
                <a href="bank_import.php">Bank Import</a>
            </p>
        </div>
    </div>
</body>
</html>
