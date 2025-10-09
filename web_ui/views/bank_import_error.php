<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Bank CSV - Error - Enhanced Trading System</title>
    <link rel="stylesheet" href="css/nav-core.css">
    <link rel="stylesheet" href="css/nav-links.css">
    <link rel="stylesheet" href="css/dropdown-base.css">
    <link rel="stylesheet" href="css/user-dropdown.css">
    <link rel="stylesheet" href="css/portfolio-dropdown.css">
    <link rel="stylesheet" href="css/stocks-dropdown.css">
    <link rel="stylesheet" href="css/nav-responsive.css">
    <link rel="stylesheet" href="css/bank-import.css">
</head>
<body>
    <?php echo $navHeader; ?>
    <div class="error-container">
        <div class="error-header">
            <h1><?php echo htmlspecialchars($errorTitle ?? 'Import Error'); ?></h1>
        </div>
        <div class="error-content">
            <p><?php echo htmlspecialchars($errorMessage ?? 'An error occurred during the import process:'); ?></p>
            <?php if (isset($errorDetails)): ?>
                <div class="error-details">
                    <pre><?php echo htmlspecialchars($errorDetails); ?></pre>
                </div>
            <?php endif; ?>
            <div style="text-align: center; margin-top: 20px;">
                <a href="bank_import.php" class="back-link">← Try Again</a>
            </div>
        </div>
    </div>
    <?php echo $navScript; ?>
</body>
</html>