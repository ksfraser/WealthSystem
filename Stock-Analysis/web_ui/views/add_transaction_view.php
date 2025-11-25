<?php
/**
 * View for the Add Transaction page.
 * This file is included by AddTransactionController.php and has access to its public methods
 * and properties.
 *
 * @var AddTransactionController $this
 * @var string $pageTitle
 * @var string $navHeader
 * @var string $navCSS
 * @var string $navScript
 * @var array $bankAccounts
 * @var array $stockSymbols
 * @var array $transactionTypes
 * @var int|null $selectedBankAccountId
 * @var array $errors
 * @var array $postData
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Enhanced Trading System</title>
    <link rel="stylesheet" href="css/nav-core.css">
    <link rel="stylesheet" href="css/nav-links.css">
    <link rel="stylesheet" href="css/dropdown-base.css">
    <link rel="stylesheet" href="css/user-dropdown.css">
    <link rel="stylesheet" href="css/portfolio-dropdown.css">
    <link rel="stylesheet" href="css/stocks-dropdown.css">
    <link rel="stylesheet" href="css/nav-responsive.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-header {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-actions {
            margin-top: 20px;
            text-align: right;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .error-summary {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php echo $navHeader; ?>

    <div class="form-container">
        <h1 class="form-header"><?php echo htmlspecialchars($pageTitle); ?></h1>

        <?php if (!empty($this->errors)): ?>
            <div class="error-summary">
                <strong>Please correct the following errors:</strong>
                <ul>
                    <?php foreach ($this->errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="add_transaction.php" method="post">
            <div class="form-group">
                <label for="bank_account_id">Bank Account</label>
                <select id="bank_account_id" name="bank_account_id" required>
                    <option value="">-- Select Bank Account --</option>
                    <?php foreach ($bankAccounts as $account): ?>
                        <option value="<?php echo $account['id']; ?>" <?php echo ($selectedBankAccountId == $account['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($account['bank_name'] . ' - ' . $account['account_nickname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="tran_date">Transaction Date</label>
                <input type="date" id="tran_date" name="tran_date" value="<?php echo htmlspecialchars($this->postData['tran_date'] ?? date('Y-m-d')); ?>" required>
            </div>

            <div class="form-group">
                <label for="tran_type">Activity</label>
                <select id="tran_type" name="tran_type" required>
                    <option value="">-- Select Activity --</option>
                    <?php foreach ($transactionTypes as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo (isset($this->postData['tran_type']) && $this->postData['tran_type'] == $type) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="stock_symbol">Symbol</label>
                <input list="symbols" id="stock_symbol" name="stock_symbol" value="<?php echo htmlspecialchars($this->postData['stock_symbol'] ?? ''); ?>" required>
                <datalist id="symbols">
                    <?php foreach ($stockSymbols as $symbol): ?>
                        <option value="<?php echo htmlspecialchars($symbol); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" step="any" id="quantity" name="quantity" value="<?php echo htmlspecialchars($this->postData['quantity'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" step="any" id="price" name="price" value="<?php echo htmlspecialchars($this->postData['price'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="amount">Total Amount</label>
                <input type="number" step="any" id="amount" name="amount" value="<?php echo htmlspecialchars($this->postData['amount'] ?? ''); ?>">
                <small>Leave blank to auto-calculate (Quantity * Price).</small>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($this->postData['description'] ?? 'Manual Entry'); ?></textarea>
            </div>

            <div class="form-actions">
                <a href="view_imported_transactions.php?bank_account_id=<?php echo htmlspecialchars((string)$selectedBankAccountId); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Add Transaction</button>
            </div>
        </form>
    </div>

    <?php echo $navScript; ?>
</body>
</html>
