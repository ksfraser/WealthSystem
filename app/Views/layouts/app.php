<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'ChatGPT Micro Cap Portfolio'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <?php if (isset($styles) && is_array($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($style); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .content-area {
            min-height: 100vh;
            padding: 2rem;
        }
        .navbar-brand {
            font-weight: bold;
            color: #007bff !important;
        }
        .nav-link.active {
            background-color: #007bff;
            color: white !important;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard">
                <?php echo $appName ?? 'ChatGPT Portfolio'; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <?php if (isset($user)): ?>
                    <span class="navbar-text me-3">
                        Welcome, <?php echo htmlspecialchars($user['username'] ?? 'User'); ?>
                    </span>
                    <a class="nav-link" href="/logout">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="/login">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <nav class="col-md-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <?php if (isset($navigation) && is_array($navigation)): ?>
                        <ul class="nav flex-column">
                            <?php foreach ($navigation as $item): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $item['active'] ? 'active' : ''; ?>" 
                                       href="<?php echo htmlspecialchars($item['url']); ?>">
                                        <?php if (isset($item['icon'])): ?>
                                            <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-10 ms-sm-auto content-area">
                <?php if (isset($alerts) && is_array($alerts)): ?>
                    <?php foreach ($alerts as $alert): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($alert['type']); ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($alert['message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (isset($pageTitle)): ?>
                    <div class="row">
                        <div class="col">
                            <h1 class="h2 mb-4"><?php echo htmlspecialchars($pageTitle); ?></h1>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Content will be inserted here -->
                <?php echo $content ?? ''; ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <?php if (isset($scripts) && is_array($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?php echo htmlspecialchars($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline JS -->
    <?php if (isset($inlineJs)): ?>
        <script><?php echo $inlineJs; ?></script>
    <?php endif; ?>
</body>
</html>