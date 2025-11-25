<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Stock Analysis Dashboard' ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js for charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- TradingView Charting Library -->
    <script src="https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js"></script>
    <!-- DataTables for advanced tables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 10px 20px;
            border-radius: 5px;
            margin: 2px 10px;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,.1);
        }
        
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0,0,0,.125);
        }
        
        .metric-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
        }
        
        .metric-label {
            color: #6c757d;
            font-size: 0.875rem;
            margin: 0;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }
        
        .candlestick-chart {
            height: 500px;
            width: 100%;
        }
        
        .indicator-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .buy-signal {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .sell-signal {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .hold-signal {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .loading-spinner {
            display: none;
        }
        
        .progress-container {
            margin: 20px 0;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <nav class="sidebar d-flex flex-column p-3">
        <a href="/Legacy/ui/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <i class="fas fa-chart-line me-2"></i>
            <span class="fs-4">Stock Analysis</span>
        </a>
        <hr class="text-white">
        
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="/Legacy/ui/" class="nav-link <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-chart-line me-2"></i>
                    Stock Data
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/Legacy/ui/pages/stocks.php">Stock Management</a></li>
                    <li><a class="dropdown-item" href="/Legacy/ui/pages/prices.php">Price Management</a></li>
                </ul>
            </li>
            
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-chart-bar me-2"></i>
                    Technical Analysis
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/Legacy/ui/pages/technical_analysis.php">TA-Lib Integration</a></li>
                    <li><a class="dropdown-item" href="/Legacy/ui/pages/indicators.php">Indicators</a></li>
                    <li><a class="dropdown-item" href="/Legacy/ui/pages/patterns.php">Candlestick Patterns</a></li>
                    <li><a class="dropdown-item" href="/Legacy/ui/pages/support_resistance.php">Support/Resistance</a></li>
                    <li><a class="dropdown-item" href="/Legacy/ui/pages/volume_analysis.php">Volume Analysis</a></li>
                </ul>
            </li>
            
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-brain me-2"></i>
                    Investment Analysis
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/Legacy/ui/pages/motley_fool.php">Motley Fool</a></li>
                    <li><a class="dropdown-item" href="/Legacy/ui/pages/buffett.php">Warren Buffett</a></li>
                    <li><a class="dropdown-item" href="/Legacy/ui/pages/iplace.php">IPlace Analysis</a></li>
                    <li><a class="dropdown-item" href="/Legacy/ui/pages/evaluation.php">Comprehensive Evaluation</a></li>
                    <li><a class="dropdown-item" href="/Legacy/ui/pages/unified.php">Unified Analysis</a></li>
                </ul>
            </li>
            
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-database me-2"></i>
                    Data Management
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/Legacy/ui/pages/stocks.php">Stocks</a></li>
                    <li><a class="dropdown-item" href="/Legacy/ui/pages/prices.php">Stock Prices</a></li>
                    <li><a class="dropdown-item" href="/Legacy/ui/pages/portfolio.php">Portfolio</a></li>
                    <li><a class="dropdown-item" href="/Legacy/ui/pages/transactions.php">Transactions</a></li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a href="/Legacy/ui/pages/jobs.php" class="nav-link <?= ($currentPage ?? '') === 'jobs' ? 'active' : '' ?>">
                    <i class="fas fa-tasks me-2"></i>
                    Analysis Jobs
                </a>
            </li>
            
            <li class="nav-item">
                <a href="/Legacy/ui/pages/alerts.php" class="nav-link <?= ($currentPage ?? '') === 'alerts' ? 'active' : '' ?>">
                    <i class="fas fa-bell me-2"></i>
                    Alerts
                </a>
            </li>
        </ul>
        
        <hr class="text-white">
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-user me-2"></i>
                <strong>Admin</strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                <li><a class="dropdown-item" href="#">Settings</a></li>
                <li><a class="dropdown-item" href="#">Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#">Sign out</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Mobile Menu Toggle -->
        <button class="btn btn-primary d-md-none mb-3" type="button" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i> Menu
        </button>

        <!-- Page Content -->
        <div class="container-fluid">
            <?php if (isset($pageHeader)): ?>
            <div class="row mb-4">
                <div class="col">
                    <h1 class="h2"><?= $pageHeader ?></h1>
                    <?php if (isset($pageDescription)): ?>
                    <p class="text-muted"><?= $pageDescription ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Alert Messages -->
            <div id="alert-container">
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); endif; ?>
            </div>
