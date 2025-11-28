<?php
/**
 * DI Container Usage Example for Web UI
 * 
 * This page demonstrates how to use the Dependency Injection Container
 * in web UI pages to access Stock Analysis services.
 */

// Load DI Container
$container = require_once __DIR__ . '/bootstrap.php';

// Optional: Require authentication
// $auth = $container->get(UserAuthDAO::class);
// $auth->requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DI Container Example - Stock Analysis</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 2rem;
            background: #f5f7fa;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 0.5rem;
        }
        h2 {
            color: #34495e;
            margin-top: 2rem;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .code-block {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 1rem;
            border-radius: 4px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
        .service-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        .service-card {
            background: #ecf0f1;
            padding: 1rem;
            border-radius: 4px;
            border-left: 4px solid #3498db;
        }
        .service-card h3 {
            margin: 0 0 0.5rem 0;
            color: #2c3e50;
        }
        .service-card p {
            margin: 0.25rem 0;
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        .demo-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Dependency Injection Container - Web UI Integration</h1>
        
        <div class="info">
            <strong>Purpose:</strong> This page demonstrates how the DI Container provides services to web UI pages,
            eliminating hard-coded dependencies and enabling testable, maintainable code.
        </div>

        <h2>📋 Available Services</h2>
        
        <?php
        // Demonstrate service resolution
        try {
            echo '<div class="success">✅ DI Container successfully loaded and operational!</div>';
            
            // List all available services
            $services = [
                'StockAnalysisService' => [
                    'class' => App\Services\StockAnalysisService::class,
                    'description' => 'Orchestrates stock analysis workflow with caching and Python integration',
                    'methods' => 'analyzeStock(), persistAnalysisResult(), getCachedAnalysis()'
                ],
                'MarketDataService' => [
                    'class' => App\Services\MarketDataService::class,
                    'description' => 'Provides market data with intelligent caching (fundamentals, prices, history)',
                    'methods' => 'getFundamentals(), getCurrentPrice(), getPriceHistory()'
                ],
                'AnalysisRepository' => [
                    'class' => App\Repositories\AnalysisRepositoryInterface::class,
                    'description' => 'Persists and retrieves stock analysis results with metadata',
                    'methods' => 'save(), find(), findAll(), delete(), exists()'
                ],
                'MarketDataRepository' => [
                    'class' => App\Repositories\MarketDataRepositoryInterface::class,
                    'description' => 'Manages market data storage with TTL support',
                    'methods' => 'storeFundamentals(), getFundamentals(), storePrice(), getPrice()'
                ],
                'PythonIntegrationService' => [
                    'class' => App\Services\PythonIntegrationService::class,
                    'description' => 'Bridges PHP and Python for AI analysis',
                    'methods' => 'executeAnalysis(), validatePythonEnvironment()'
                ],
                'UserAuthDAO' => [
                    'class' => UserAuthDAO::class,
                    'description' => 'Handles user authentication and session management',
                    'methods' => 'requireLogin(), isLoggedIn(), getCurrentUser(), logout()'
                ],
                'NavigationService' => [
                    'class' => App\Services\NavigationService::class,
                    'description' => 'Renders navigation headers and menus',
                    'methods' => 'renderNavigationHeader(), renderMenu()'
                ]
            ];
            
            echo '<div class="service-list">';
            foreach ($services as $name => $info) {
                echo '<div class="service-card">';
                echo '<h3>' . htmlspecialchars($name) . '</h3>';
                echo '<p><strong>Class:</strong> ' . htmlspecialchars($info['class']) . '</p>';
                echo '<p><strong>Purpose:</strong> ' . htmlspecialchars($info['description']) . '</p>';
                echo '<p><strong>Methods:</strong> ' . htmlspecialchars($info['methods']) . '</p>';
                
                // Test if service can be resolved
                try {
                    $service = $container->get($info['class']);
                    echo '<p style="color: #27ae60;">✅ <strong>Status:</strong> Available</p>';
                } catch (Exception $e) {
                    echo '<p style="color: #e74c3c;">❌ <strong>Status:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                
                echo '</div>';
            }
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>

        <h2>💡 Usage Examples</h2>
        
        <div class="card">
            <h3>Example 1: Get Stock Analysis Service</h3>
            <div class="code-block">
&lt;?php<br>
// Load container<br>
$container = require_once __DIR__ . '/bootstrap.php';<br>
<br>
// Resolve service (auto-wiring handles all dependencies)<br>
$analysisService = $container->get(App\Services\StockAnalysisService::class);<br>
<br>
// Use the service<br>
$result = $analysisService->analyzeStock('AAPL');<br>
?&gt;
            </div>
        </div>

        <div class="card">
            <h3>Example 2: Get Cached Market Data</h3>
            <div class="code-block">
&lt;?php<br>
// Resolve market data service<br>
$marketData = $container->get(App\Services\MarketDataService::class);<br>
<br>
// Fetch fundamentals (cached for 24 hours)<br>
$fundamentals = $marketData->getFundamentals('MSFT');<br>
<br>
echo "P/E Ratio: " . $fundamentals['pe_ratio'];<br>
echo "Market Cap: $" . number_format($fundamentals['market_cap']);<br>
?&gt;
            </div>
        </div>

        <div class="card">
            <h3>Example 3: Check Cached Analysis</h3>
            <div class="code-block">
&lt;?php<br>
$analysisService = $container->get(App\Services\StockAnalysisService::class);<br>
<br>
// Try to get cached analysis (1 hour TTL)<br>
$cached = $analysisService->getCachedAnalysis('TSLA');<br>
<br>
if ($cached) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;echo "Score: " . $cached['total_score'];<br>
&nbsp;&nbsp;&nbsp;&nbsp;echo "Cached at: " . $cached['cached_at'];<br>
&nbsp;&nbsp;&nbsp;&nbsp;echo "Age: " . $cached['cache_age'] . " seconds";<br>
} else {<br>
&nbsp;&nbsp;&nbsp;&nbsp;// No cache, analyze fresh<br>
&nbsp;&nbsp;&nbsp;&nbsp;$result = $analysisService->analyzeStock('TSLA');<br>
}<br>
?&gt;
            </div>
        </div>

        <div class="card">
            <h3>Example 4: Authenticate User</h3>
            <div class="code-block">
&lt;?php<br>
// Resolve authentication service<br>
$auth = $container->get(UserAuthDAO::class);<br>
<br>
// Require login (redirects if not authenticated)<br>
$auth->requireLogin();<br>
<br>
// Get current user<br>
$user = $auth->getCurrentUser();<br>
echo "Welcome, " . htmlspecialchars($user['email']);<br>
?&gt;
            </div>
        </div>

        <h2>🎯 Live Demo: Container Capabilities</h2>
        
        <div class="demo-section">
            <?php
            echo '<h3>Container Information</h3>';
            
            // Show container type
            echo '<p><strong>Container Class:</strong> ' . get_class($container) . '</p>';
            
            // Show PSR-11 compliance
            echo '<p><strong>PSR-11 Compliant:</strong> ';
            if ($container instanceof Psr\Container\ContainerInterface) {
                echo '✅ Yes</p>';
            } else {
                echo '❌ No</p>';
            }
            
            // Test singleton behavior
            echo '<h3>Singleton Test</h3>';
            $service1 = $container->get(App\Services\MarketDataService::class);
            $service2 = $container->get(App\Services\MarketDataService::class);
            
            if ($service1 === $service2) {
                echo '<p>✅ <strong>Singleton working:</strong> Same instance returned on multiple calls</p>';
                echo '<p>Service1 ID: ' . spl_object_id($service1) . '</p>';
                echo '<p>Service2 ID: ' . spl_object_id($service2) . '</p>';
            } else {
                echo '<p>❌ <strong>Singleton not working:</strong> Different instances returned</p>';
            }
            ?>
        </div>

        <h2>📚 Benefits of Using DI Container</h2>
        
        <div class="card">
            <ul style="color: #2c3e50; line-height: 1.8;">
                <li>✅ <strong>No Hard-Coded Dependencies:</strong> Services don't know how to create their dependencies</li>
                <li>✅ <strong>Easy Testing:</strong> Inject mocks/stubs during tests</li>
                <li>✅ <strong>Centralized Configuration:</strong> All service setup in bootstrap.php</li>
                <li>✅ <strong>Auto-Wiring:</strong> Container resolves constructor dependencies automatically</li>
                <li>✅ <strong>Singleton Management:</strong> Expensive services (DB, cache) reused across requests</li>
                <li>✅ <strong>PSR-11 Standard:</strong> Industry-standard container interface</li>
                <li>✅ <strong>Type Safety:</strong> IDE autocomplete and type checking work perfectly</li>
                <li>✅ <strong>Maintainability:</strong> Change implementations without touching consumers</li>
            </ul>
        </div>

        <h2>🔗 Next Steps</h2>
        
        <div class="card">
            <ul style="color: #2c3e50; line-height: 1.8;">
                <li>📖 Review <code>Stock-Analysis/bootstrap.php</code> for service configuration</li>
                <li>📖 Check <code>Stock-Analysis/MIGRATION_TO_SYMFONY_DI.md</code> for scaling guidance</li>
                <li>🧪 Run tests: <code>cd Stock-Analysis && php vendor/bin/phpunit</code></li>
                <li>📝 See <code>Stock-Analysis/example_container_usage.php</code> for more examples</li>
                <li>🚀 Start using DI in your pages (see examples above)</li>
            </ul>
        </div>

        <div class="info" style="margin-top: 2rem;">
            <strong>Documentation:</strong> For complete API documentation, see:<br>
            • <code>Stock-Analysis/app/Container/DIContainer.php</code> - Container implementation<br>
            • <code>Stock-Analysis/app/Services/</code> - Available services<br>
            • <code>Stock-Analysis/app/Repositories/</code> - Data persistence layer<br>
            • <code>Stock-Analysis/CODE_REVIEW_REPORT.md</code> - Architecture decisions
        </div>
    </div>
</body>
</html>
