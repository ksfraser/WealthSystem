<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Bank CSV - Complete - Enhanced Trading System</title>
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
    <div class="success-container">
        <div class="success-header">
            <h1>Import Complete</h1>
            <p>Successfully imported <?php echo count($rows); ?> transactions.</p>
            <?php if (isset($accountName)): ?>
                <p><?php echo htmlspecialchars($accountName); ?></p>
            <?php endif; ?>
        </div>
        <div class="success-content">
            <div class="next-actions">
                <h3>Next Actions</h3>
                <ul>
                    <?php if (isset($viewTransactionsUrl)): ?>
                        <li><a href="<?php echo htmlspecialchars($viewTransactionsUrl); ?>">View Imported Transactions</a></li>
                    <?php endif; ?>
                    <li><a href="user_bank_accounts.php">View Your Bank Accounts</a></li>
                    <li><a href="reconcile_ledger.php">Reconcile with Ledger/Journal</a></li>
                    <li><a href="bank_import.php">Import Another File</a></li>
                    <li><a href="dashboard.php">Return to Dashboard</a></li>
                    <?php if (isset($downloadLogUrl)): ?>
                        <li><a href="<?php echo htmlspecialchars($downloadLogUrl); ?>">Download Import Log/Report</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php echo $navScript; ?>
</body>
</html>