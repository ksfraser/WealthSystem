# Move Development/Archive Files from web_ui
# This script moves development files that shouldn't be in the web root

$source = "web_ui"
$debugRoot = "debug\web_ui\archived"

Write-Host "Moving development/archive files from web_ui..." -ForegroundColor Green

# Admin file variations
Write-Host "Moving admin file variations..." -ForegroundColor Yellow
Move-Item "$source\admin_users_*.php" "$debugRoot\admin_versions\" -ErrorAction SilentlyContinue
Move-Item "$source\admin_brokerages_*.php" "$debugRoot\admin_versions\" -ErrorAction SilentlyContinue

# Index file variations  
Write-Host "Moving index file variations..." -ForegroundColor Yellow
Move-Item "$source\index_*.php" "$debugRoot\" -ErrorAction SilentlyContinue

# Navigation variations
Write-Host "Moving navigation variations..." -ForegroundColor Yellow
Move-Item "$source\NavigationManager_*.php" "$debugRoot\" -ErrorAction SilentlyContinue

# Portfolio variations
Write-Host "Moving portfolio variations..." -ForegroundColor Yellow
Move-Item "$source\portfolios_*.php" "$debugRoot\" -ErrorAction SilentlyContinue

# Simple versions
Write-Host "Moving simple versions..." -ForegroundColor Yellow
Move-Item "$source\analytics_simple.php" "$debugRoot\" -ErrorAction SilentlyContinue
Move-Item "$source\database_simple.php" "$debugRoot\" -ErrorAction SilentlyContinue
Move-Item "$source\simple_*.php" "$debugRoot\" -ErrorAction SilentlyContinue

Write-Host "✅ Archive file movement completed!" -ForegroundColor Green