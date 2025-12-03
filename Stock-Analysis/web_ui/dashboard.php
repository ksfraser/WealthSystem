<?php
/**
 * User Dashboard - Main logged-in area
 * 
 * Uses Dependency Injection Container for service resolution
 */

// Load DI Container
$container = require_once __DIR__ . '/bootstrap.php';

// Resolve services from container
$auth = $container->get(UserAuthDAO::class);

// Use web_ui NavigationService directly (has renderNavigationHeader method)
require_once __DIR__ . '/NavigationService.php';
$navigationService = new NavigationService($container->get(App\Services\Interfaces\AuthenticationServiceInterface::class));

// Use new SRP Navigation Architecture for dashboard cards
require_once __DIR__ . '/Navigation/NavigationFactory.php';

// Require login with proper exception handling
try {
    $auth->requireLogin();
    $user = $auth->getCurrentUser();
} catch (\App\Auth\LoginRequiredException $e) {
    // Redirect to login with return URL
    $returnUrl = urlencode($_SERVER['REQUEST_URI'] ?? 'dashboard.php');
    header('Location: login.php?return_url=' . $returnUrl);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Portfolio Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8f9fa;
        }
        

        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }
        
        .dashboard-card h3 {
            margin: 0 0 1rem 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .dashboard-card p {
            color: #666;
            margin: 0 0 1rem 0;
        }
        
        .card-links {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .card-links a {
            color: #667eea;
            text-decoration: none;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .card-links a:hover {
            background: #f8f9fa;
            text-decoration: underline;
        }
        
        .welcome-section {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
            text-align: center;
        }
        

        
        .status-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 4px;
            padding: 1rem;
            margin: 1rem 0;
            color: #1976d2;
        }
        

    </style>
</head>
<body>

<?php
// Render navigation header using DI-resolved service
echo $navigationService->renderNavigationHeader('Portfolio Dashboard - Enhanced Trading System', 'dashboard');
?>


    
    <div class="container">
        <div class="welcome-section">
            <h2>🎯 Your Portfolio Management Hub</h2>
            <p>Access all your investment tools and portfolio data from this centralized dashboard.</p>
            <p style="margin-top: 10px;">
                <a href="MyPortfolio.php" style="display: inline-block; padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 6px; font-weight: 600; transition: transform 0.2s;">📊 View Trading Dashboard</a>
            </p>
            
            <div class="status-info">
                <?php
                try {
                    // Test database connection via auth service
                    $auth->isLoggedIn();
                    echo '<strong>🔧 System Status:</strong> 🟢 Database Available - All features operational.';
                } catch (Exception $e) {
                    echo '<strong>🔧 System Status:</strong> 🔴 Database Unavailable - Operating in limited mode.';
                }
                ?>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <?php
            // Use new SRP architecture to render dashboard cards with access control
            $dashboardBuilder = NavigationFactory::createDashboardCardBuilder($user);
            echo $dashboardBuilder->renderCards();
            ?>
        </div>
        
        <!-- Quick Stats -->
        <div style="margin-top: 2rem;">
            <div class="dashboard-card">
                <h3>📋 Quick Stats</h3>
                <p><strong>User ID:</strong> <?php echo $user['id']; ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Account Type:</strong> <?php echo $user['is_admin'] ? 'Administrator' : 'Standard User'; ?></p>
                <p><strong>Login Time:</strong> <?php echo date('Y-m-d H:i:s', $user['login_time']); ?></p>
            </div>
        </div>
    </div>
</body>
</html>
