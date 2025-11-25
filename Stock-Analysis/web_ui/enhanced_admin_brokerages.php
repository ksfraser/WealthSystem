<?php
/**
 * Enhanced Admin Brokerages - Uses existing centralized system with improvements
 * This builds upon the existing CommonDAO architecture
 */

require_once __DIR__ . '/EnhancedCommonDAO.php';
require_once __DIR__ . '/SimpleValidators.php';

/**
 * Enhanced Brokerage DAO that extends existing system
 */
class EnhancedBrokerageDAO extends EnhancedCommonDAO
{
    public function __construct($dbConfigClass = 'LegacyDatabaseConfig')
    {
        $logger = new SimpleLogger(__DIR__ . '/logs/brokerage.log');
        parent::__construct($dbConfigClass, null, $logger);
        
        // Ensure brokerages table exists if we have a connection
        if ($this->hasValidConnection()) {
            $this->ensureBrokeragesTable();
        }
    }

    private function ensureBrokeragesTable()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS brokerages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $this->executeQuery($sql);
            $this->logger->info('Brokerages table verified/created');
        } catch (Exception $e) {
            $this->logError('Failed to create brokerages table: ' . $e->getMessage());
        }
    }

    public function addBrokerage($name)
    {
        if (!$this->hasValidConnection()) {
            throw new RuntimeException('No database connection available');
        }

        $name = trim($name);
        if (empty($name)) {
            throw new InvalidArgumentException('Brokerage name cannot be empty');
        }

        $sql = "INSERT IGNORE INTO brokerages (name) VALUES (:name)";
        $stmt = $this->executeQuery($sql, ['name' => $name]);
        
        $rowsAffected = $stmt->rowCount();
        if ($rowsAffected > 0) {
            $this->logger->info('Brokerage added', ['name' => $name]);
            return true;
        } else {
            $this->logger->warning('Brokerage already exists', ['name' => $name]);
            return false; // Already exists
        }
    }

    public function getAllBrokerages()
    {
        if (!$this->hasValidConnection()) {
            return [];
        }

        try {
            $sql = "SELECT * FROM brokerages ORDER BY name";
            $stmt = $this->executeQuery($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            $this->logError('Failed to fetch brokerages: ' . $e->getMessage());
            return [];
        }
    }

    public function deleteBrokerage($id)
    {
        if (!$this->hasValidConnection()) {
            throw new RuntimeException('No database connection available');
        }

        $sql = "DELETE FROM brokerages WHERE id = :id";
        $stmt = $this->executeQuery($sql, ['id' => $id]);
        
        $rowsAffected = $stmt->rowCount();
        if ($rowsAffected > 0) {
            $this->logger->info('Brokerage deleted', ['id' => $id]);
            return true;
        }
        return false;
    }

    public function getBrokerageByName($name)
    {
        if (!$this->hasValidConnection()) {
            return null;
        }

        $sql = "SELECT * FROM brokerages WHERE name = :name";
        $stmt = $this->executeQuery($sql, ['name' => $name]);
        return $stmt->fetch();
    }
}

// Initialize DAO and handle operations
$dao = new EnhancedBrokerageDAO();
$connectionInfo = $dao->getConnectionInfo();
$message = '';
$messageType = '';

// Handle form submission
if ($_POST) {
    if (isset($_POST['add_brokerage']) && !empty($_POST['brokerage_name'])) {
        if ($dao->hasValidConnection()) {
            try {
                $name = trim($_POST['brokerage_name']);
                $added = $dao->addBrokerage($name);
                
                if ($added) {
                    $message = "Brokerage '$name' added successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Brokerage '$name' already exists.";
                    $messageType = 'warning';
                }
            } catch (Exception $e) {
                $message = "Error adding brokerage: " . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = "Cannot add brokerage: No database connection.";
            $messageType = 'error';
        }
    } elseif (isset($_POST['delete_brokerage']) && !empty($_POST['brokerage_id'])) {
        if ($dao->hasValidConnection()) {
            try {
                $deleted = $dao->deleteBrokerage($_POST['brokerage_id']);
                if ($deleted) {
                    $message = "Brokerage deleted successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Brokerage not found or already deleted.";
                    $messageType = 'warning';
                }
            } catch (Exception $e) {
                $message = "Error deleting brokerage: " . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = "Cannot delete brokerage: No database connection.";
            $messageType = 'error';
        }
    }
}

// Get all brokerages
$brokerages = $dao->getAllBrokerages();
$errors = $dao->getErrors();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enhanced Brokerage Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .warning { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #e3f2fd; border: 1px solid #bbdefb; color: #0d47a1; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { padding: 8px; width: 300px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-small { padding: 4px 8px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .status-connected { color: #28a745; font-weight: bold; }
        .status-disconnected { color: #dc3545; font-weight: bold; }
        .connection-info { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Enhanced Brokerage Management</h1>
        
        <!-- Connection Status -->
        <div class="connection-info">
            <h3>Database Connection Status</h3>
            <?php if ($connectionInfo['connected']): ?>
                <p class="status-connected">✅ Connected</p>
                <p><strong>Server Version:</strong> <?= htmlspecialchars($connectionInfo['server_version'] ?? 'Unknown') ?></p>
                <p><strong>Configuration:</strong> <?= htmlspecialchars($connectionInfo['config_class']) ?></p>
            <?php else: ?>
                <p class="status-disconnected">❌ Not Connected</p>
                <p><strong>Configuration:</strong> <?= htmlspecialchars($connectionInfo['config_class']) ?></p>
                <?php if (!empty($connectionInfo['error'])): ?>
                    <p><strong>Error:</strong> <?= htmlspecialchars($connectionInfo['error']) ?></p>
                <?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <p><strong>Connection Errors:</strong></p>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <div class="message info">
                    <h4>Database Setup Information</h4>
                    <p>This system uses the existing centralized database configuration from <code>DbConfigClasses.php</code>.</p>
                    <p>To resolve connection issues:</p>
                    <ul>
                        <li>Ensure PHP PDO MySQL extension is installed and enabled</li>
                        <li>Check database configuration in <code>db_config.yml</code></li>
                        <li>Verify database server is running and accessible</li>
                        <li>Use the <a href="database.php">Database Management</a> page for diagnosis</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Add Brokerage Form -->
        <?php if ($dao->hasValidConnection()): ?>
            <div class="card">
                <h2>Add New Brokerage</h2>
                <form method="post">
                    <div class="form-group">
                        <label for="brokerage_name">Brokerage Name:</label>
                        <input type="text" id="brokerage_name" name="brokerage_name" required 
                               placeholder="Enter brokerage name" maxlength="255">
                    </div>
                    <button type="submit" name="add_brokerage" class="btn">Add Brokerage</button>
                </form>
            </div>

            <!-- Brokerages List -->
            <div class="card">
                <h2>Existing Brokerages</h2>
                <?php if (empty($brokerages)): ?>
                    <p><em>No brokerages found. Add one using the form above.</em></p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($brokerages as $brokerage): ?>
                                <tr>
                                    <td><?= htmlspecialchars($brokerage['id']) ?></td>
                                    <td><?= htmlspecialchars($brokerage['name']) ?></td>
                                    <td><?= htmlspecialchars($brokerage['created_at'] ?? 'Unknown') ?></td>
                                    <td>
                                        <form method="post" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this brokerage?')">
                                            <input type="hidden" name="brokerage_id" value="<?= $brokerage['id'] ?>">
                                            <button type="submit" name="delete_brokerage" 
                                                    class="btn btn-danger btn-small">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p><strong>Total:</strong> <?= count($brokerages) ?> brokerage(s)</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="message error">
                <h3>Database Functionality Unavailable</h3>
                <p>Brokerage management requires a working database connection.</p>
                <p>Please resolve the database connection issues to use this feature.</p>
                <p><a href="database.php">Go to Database Management</a> for diagnosis and setup.</p>
            </div>
        <?php endif; ?>

        <!-- Back to Main -->
        <div style="margin-top: 30px;">
            <a href="database.php" class="btn">← Back to Database Management</a>
        </div>
    </div>
</body>
</html>
