<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Bank CSV - Assign Account - Enhanced Trading System</title>
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

    <div class="staging-container">
        <div class="staging-header">
            <h1>Staging Complete</h1>
            <p><?php echo htmlspecialchars($prompt); ?></p>
        </div>

        <div class="staging-content">
            <div class="account-selection">
                <h3>Assign to Existing Bank Account</h3>
                <form method="post" action="bank_import.php" style="margin-bottom: 20px;">
                    <input type="hidden" name="staging_file" value="<?php echo htmlspecialchars($stagingFile); ?>">
                    <input type="hidden" name="csv_type" value="<?php echo htmlspecialchars($type); ?>">
                    <input type="hidden" name="action" value="select_existing">

                    <div class="form-group">
                        <label for="existing_account">Select Existing Bank Account:</label>
                        <select name="existing_account_id" id="existing_account" required>
                            <option value="">-- Choose an existing account --</option>
                            <?php foreach ($userBankAccounts as $account): ?>
                                <?php
                                $displayName = htmlspecialchars($account['bank_name'] . ' - ' . $account['account_number']);
                                if (!empty($account['account_nickname'])) {
                                    $displayName .= ' (' . htmlspecialchars($account['account_nickname']) . ')';
                                }
                                ?>
                                <option value="<?php echo $account['id']; ?>"><?php echo $displayName; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn">Select Account & Import</button>
                </form>

                <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

                <h3>Or Create New Bank Account</h3>
                <button type="button" id="showCreateForm" class="btn btn-primary">Create New Account</button>

                <div id="createFormContainer" style="display: none; background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); border: 2px solid #2196f3; padding: 25px; border-radius: 8px; margin-top: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h3 style="color: #1976d2; margin-top: 0; text-align: center;">🏦 Create New Bank Account</h3>
                    <p style="text-align: center; color: #666; margin-bottom: 20px;">Add a new bank account to track your transactions</p>
                    <form method="post" action="bank_import.php" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; align-items: end;">
                        <input type="hidden" name="staging_file" value="<?php echo htmlspecialchars($stagingFile); ?>">
                        <input type="hidden" name="csv_type" value="<?php echo htmlspecialchars($type); ?>">
                        <input type="hidden" name="action" value="create_and_import">
                        <div>
                            <label for="bank_name" style="display: block; margin-bottom: 5px; font-weight: bold;">Bank Name *</label>
                            <input type="text" id="bank_name" name="bank_name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div>
                            <label for="account_number" style="display: block; margin-bottom: 5px; font-weight: bold;">Account Number *</label>
                            <input type="text" id="account_number" name="account_number" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div>
                            <label for="nickname" style="display: block; margin-bottom: 5px; font-weight: bold;">Nickname (Optional)</label>
                            <input type="text" id="nickname" name="nickname" placeholder="e.g., My RBC Account" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <div>
                            <label for="account_type" style="display: block; margin-bottom: 5px; font-weight: bold;">Account Type</label>
                            <select id="account_type" name="account_type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="Investment Account">Investment Account</option>
                                <option value="Savings Account">Savings Account</option>
                                <option value="Checking Account">Checking Account</option>
                                <option value="Retirement Account">Retirement Account</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="currency" style="display: block; margin-bottom: 5px; font-weight: bold;">Currency</label>
                            <select id="currency" name="currency" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="CAD">CAD - Canadian Dollar</option>
                                <option value="USD">USD - US Dollar</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="GBP">GBP - British Pound</option>
                            </select>
                        </div>
                        <div style="grid-column: span 2;">
                            <button type="submit" class="btn" style="width: 100%;">Create Account & Import</button>
                        </div>
                    </form>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <a href="bank_import.php" class="back-link">← Back to Upload Form</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('showCreateForm').addEventListener('click', function() {
            const container = document.getElementById('createFormContainer');
            if (container.style.display === 'none') {
                container.style.display = 'block';
                this.textContent = 'Hide Create Form';
            } else {
                container.style.display = 'none';
                this.textContent = 'Create New Account';
            }
        });
    </script>

    <?php echo $navScript; ?>
</body>
</html>