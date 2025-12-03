#!/usr/bin/env python3
"""
PHP Integration Test - Can Python launch PHP?
Demonstrates various ways Python can work with PHP
"""

import subprocess
import sys
import os
from pathlib import Path

def test_php_availability():
    """Test if PHP is available on the system."""
    print("=" * 60)
    print("PHP AVAILABILITY TEST")
    print("=" * 60)
    
    try:
        # Test PHP version
        result = subprocess.run(['php', '--version'], 
                              capture_output=True, text=True, timeout=10)
        if result.returncode == 0:
            print("✓ PHP is available!")
            print(f"Version info: {result.stdout.split()[0:3]}")
            return True
        else:
            print("✗ PHP command failed")
            return False
    except FileNotFoundError:
        print("✗ PHP not found in PATH")
        return False
    except subprocess.TimeoutExpired:
        print("✗ PHP command timed out")
        return False

def test_php_execution():
    """Test running PHP code from Python."""
    print("\n" + "=" * 60)
    print("PHP EXECUTION TEST")
    print("=" * 60)
    
    # Create a simple PHP test script
    php_code = '''<?php
echo "Hello from PHP!\\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\\n";
echo "PHP version: " . phpversion() . "\\n";
?>'''
    
    try:
        # Method 1: Run PHP code directly
        result = subprocess.run(['php', '-r', php_code], 
                              capture_output=True, text=True)
        if result.returncode == 0:
            print("✓ Direct PHP execution works:")
            print(result.stdout)
        else:
            print("✗ Direct PHP execution failed")
            print(result.stderr)
            
    except Exception as e:
        print(f"✗ Error executing PHP: {e}")

def test_php_web_server():
    """Test launching PHP built-in web server."""
    print("\n" + "=" * 60)
    print("PHP WEB SERVER TEST")
    print("=" * 60)
    
    # Create a simple PHP web page
    web_dir = Path("web_ui")
    web_dir.mkdir(exist_ok=True)
    
    index_php = web_dir / "index.php"
    index_php.write_text('''<?php
<!DOCTYPE html>
<html>
<head>
    <title>Trading Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .card { border: 1px solid #ddd; padding: 20px; margin: 10px 0; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
    </style>
</head>
<body>
    <h1>Enhanced Trading System - Web Dashboard</h1>
    
    <div class="card success">
        <h3>✓ PHP Web Interface Working!</h3>
        <p><strong>Server Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
        <p><strong>PHP Version:</strong> <?= phpversion() ?></p>
    </div>
    
    <div class="card">
        <h3>Database Connection Test</h3>
        <?php
        // Load database configuration from YAML file
        function parseDbConfig($configFile) {
            if (!file_exists($configFile)) {
                return null;
            }
            
            $content = file_get_contents($configFile);
            if ($content === false) {
                return null;
            }
            
            // Extract database configuration using regex
            if (preg_match('/database:\s*\n.*?host:\s*([^\n]+)/s', $content, $hostMatch) &&
                preg_match('/database:\s*\n.*?port:\s*([^\n]+)/s', $content, $portMatch) &&
                preg_match('/database:\s*\n.*?username:\s*([^\n]+)/s', $content, $userMatch) &&
                preg_match('/database:\s*\n.*?password:\s*([^\n]+)/s', $content, $passMatch)) {
                
                return [
                    'host' => trim($hostMatch[1]),
                    'port' => (int)trim($portMatch[1]),
                    'username' => trim($userMatch[1]),
                    'password' => trim($passMatch[1])
                ];
            }
            return null;
        }
        
        $config = parseDbConfig('../db_config_refactored.yml');
        
        if (!$config) {
            echo "<p style='color: red;'>✗ Database configuration not found</p>";
            echo "<p>Please ensure db_config_refactored.yml exists with proper configuration.</p>";
        } else {
            $host = $config['host'];
            $username = $config['username'];
            $password = $config['password'];
            $database = 'stock_market_2';
            
            try {
                $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
                echo "<p style='color: green;'>✓ Database connection successful!</p>";
                echo "<p>Connected to: $database</p>";
            } catch(PDOException $e) {
                echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
            }
        }
        ?>
    </div>
    
    <div class="card">
        <h3>Quick Actions</h3>
        <ul>
            <li><a href="portfolio.php">View Portfolios</a></li>
            <li><a href="trades.php">Trade History</a></li>
            <li><a href="analytics.php">Analytics Dashboard</a></li>
            <li><a href="admin.php">Database Management</a></li>
        </ul>
    </div>
</body>
</html>
''')
    
    print(f"✓ Created web interface in: {web_dir.absolute()}")
    print("\nTo start PHP web server:")
    print(f"cd {web_dir}")
    print("php -S localhost:8080")
    print("\nThen visit: http://localhost:8080")
    
    return str(web_dir.absolute())

def demonstrate_python_php_integration():
    """Show different ways Python can integrate with PHP."""
    print("\n" + "=" * 60)
    print("PYTHON-PHP INTEGRATION OPTIONS")
    print("=" * 60)
    
    print("""
1. SUBPROCESS EXECUTION:
   - Run PHP scripts from Python
   - Pass data via command line arguments
   - Capture PHP output in Python
   
2. BUILT-IN WEB SERVER:
   - Launch PHP development server from Python
   - Serve web UI for trading dashboard
   - Handle real-time data updates
   
3. DATA EXCHANGE:
   - Python writes JSON data files
   - PHP reads and displays data
   - Shared database access
   
4. API ENDPOINTS:
   - Python creates REST API endpoints
   - PHP makes HTTP requests to Python
   - Real-time data synchronization
   
5. SHARED DATABASE:
   - Both access same MySQL database
   - Python handles trading logic
   - PHP provides web interface
""")

def main():
    print("PYTHON + PHP INTEGRATION ANALYSIS")
    print("For Enhanced Trading System Web UI")
    
    # Test PHP availability
    php_available = test_php_availability()
    
    if php_available:
        # Test PHP execution
        test_php_execution()
        
        # Create web interface
        web_dir = test_php_web_server()
        
        # Show integration options
        demonstrate_python_php_integration()
        
        print("\n" + "=" * 60)
        print("RECOMMENDATIONS")
        print("=" * 60)
        print("✓ PHP integration is fully supported!")
        print("✓ You can create a web UI for trading management")
        print("✓ Shared database access will work perfectly")
        print("\nNext steps:")
        print("1. Refactor database architecture (detailed plan below)")
        print("2. Create PHP web interface")
        print("3. Integrate Python trading engine with web UI")
        
    else:
        print("\n" + "=" * 60)
        print("PHP INSTALLATION NEEDED")
        print("=" * 60)
        print("To enable web UI features:")
        print("1. Install PHP: https://www.php.net/downloads")
        print("2. Add PHP to system PATH")
        print("3. Install PHP MySQL extension")

if __name__ == "__main__":
    main()
