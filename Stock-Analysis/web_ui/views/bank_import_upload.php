<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Bank CSV - Enhanced Trading System</title>
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

    <div class="import-container">
        <div class="import-header">
            <h1>Import Bank CSV</h1>
            <p>Upload and import your bank transaction data</p>
        </div>

        <div class="import-form">
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="csv_type">CSV Type:</label>
                    <select name="csv_type" id="csv_type">
                        <option value="holdings">Account Holdings</option>
                        <option value="transactions">Transaction History</option>
                    </select>
                    <div class="help-text">
                        Select "Account Holdings" for position data or "Transaction History" for trade records
                    </div>
                </div>

                <div class="form-group">
                    <label for="csv_file">CSV File:</label>
                    <input type="file" name="csv_file" id="csv_file" required accept=".csv">
                    <div class="help-text">
                        Choose a CSV file exported from your bank or brokerage
                    </div>
                </div>

                <button type="submit" class="submit-btn">Upload and Process</button>
            </form>
        </div>
    </div>

    <?php echo $navScript; ?>
</body>
</html>