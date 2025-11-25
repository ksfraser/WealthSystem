<div class="row">
    <div class="col-md-8">
        <!-- File Upload Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Import Bank Statement</h5>
            </div>
            <div class="card-body">
                <form action="/bank-import/upload" method="post" enctype="multipart/form-data" id="uploadForm">
                    <div class="mb-3">
                        <label for="bankFile" class="form-label">Select Bank Statement File</label>
                        <input type="file" class="form-control" id="bankFile" name="bank_file" 
                               accept=".csv,.xlsx,.xls,.txt" required>
                        <div class="form-text">
                            Supported formats: CSV, Excel (.xlsx, .xls), Text (.txt)
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bankAccount" class="form-label">Bank Account</label>
                        <select class="form-select" id="bankAccount" name="bank_account_id">
                            <option value="">Select Account...</option>
                            <?php if (isset($bankAccounts) && is_array($bankAccounts)): ?>
                                <?php foreach ($bankAccounts as $account): ?>
                                    <option value="<?php echo htmlspecialchars($account['id'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($account['account_name'] ?? ''); ?>
                                        (<?php echo htmlspecialchars($account['account_number'] ?? ''); ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#addAccountModal">Add New Account</a>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fileFormat" class="form-label">File Format</label>
                        <select class="form-select" id="fileFormat" name="file_format">
                            <option value="auto">Auto Detect</option>
                            <option value="chase">Chase Bank</option>
                            <option value="bofa">Bank of America</option>
                            <option value="wells">Wells Fargo</option>
                            <option value="generic_csv">Generic CSV</option>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" onclick="previewFile()">
                            Preview File
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Import Statement
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Import History -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Import History</h5>
            </div>
            <div class="card-body p-0">
                <?php if (isset($importHistory) && is_array($importHistory) && count($importHistory) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>File Name</th>
                                    <th>Account</th>
                                    <th>Records</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($importHistory as $import): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($import['import_date'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($import['file_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($import['account_name'] ?? ''); ?></td>
                                        <td><?php echo number_format($import['record_count'] ?? 0); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $import['status'] === 'success' ? 'success' : ($import['status'] === 'error' ? 'danger' : 'warning'); ?>">
                                                <?php echo htmlspecialchars(ucfirst($import['status'] ?? '')); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewImportDetails(<?php echo $import['id'] ?? 0; ?>)">
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">No import history available</p>
                        <small class="text-muted">Upload your first bank statement to get started</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Help & Instructions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Import Instructions</h5>
            </div>
            <div class="card-body">
                <ol>
                    <li><strong>Download Statement:</strong> Log into your bank account and download your statement in CSV or Excel format.</li>
                    <li><strong>Select Account:</strong> Choose the bank account this statement belongs to.</li>
                    <li><strong>Choose Format:</strong> Select your bank's format or use auto-detect.</li>
                    <li><strong>Upload:</strong> Click "Import Statement" to process the file.</li>
                </ol>
                
                <hr>
                
                <h6>Supported Banks:</h6>
                <ul class="mb-0">
                    <li>Chase Bank</li>
                    <li>Bank of America</li>
                    <li>Wells Fargo</li>
                    <li>Generic CSV files</li>
                </ul>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Import Statistics</h5>
            </div>
            <div class="card-body">
                <?php if (isset($importStats) && is_array($importStats)): ?>
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-primary"><?php echo number_format($importStats['total_imports'] ?? 0); ?></h4>
                            <small class="text-muted">Total Imports</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success"><?php echo number_format($importStats['total_transactions'] ?? 0); ?></h4>
                            <small class="text-muted">Transactions</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-info"><?php echo number_format($importStats['accounts_linked'] ?? 0); ?></h4>
                            <small class="text-muted">Accounts</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-warning"><?php echo htmlspecialchars($importStats['last_import'] ?? 'Never'); ?></h4>
                            <small class="text-muted">Last Import</small>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center">No import statistics available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Bank Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/bank-import/accounts" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="accountName" class="form-label">Account Name</label>
                        <input type="text" class="form-control" id="accountName" name="account_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="accountNumber" class="form-label">Account Number (Last 4 digits)</label>
                        <input type="text" class="form-control" id="accountNumber" name="account_number" maxlength="4" required>
                    </div>
                    <div class="mb-3">
                        <label for="bankName" class="form-label">Bank Name</label>
                        <input type="text" class="form-control" id="bankName" name="bank_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="accountType" class="form-label">Account Type</label>
                        <select class="form-select" id="accountType" name="account_type" required>
                            <option value="">Select Type...</option>
                            <option value="checking">Checking</option>
                            <option value="savings">Savings</option>
                            <option value="investment">Investment</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewFile() {
    const fileInput = document.getElementById('bankFile');
    if (!fileInput.files[0]) {
        alert('Please select a file first');
        return;
    }
    
    // Add file preview functionality
    console.log('Preview file:', fileInput.files[0].name);
    // This would typically show a modal with file preview
}

function viewImportDetails(importId) {
    // Add import details functionality
    console.log('View import details:', importId);
    // This would typically show a modal with import details
}

// Form validation
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('bankFile');
    const accountSelect = document.getElementById('bankAccount');
    
    if (!fileInput.files[0]) {
        e.preventDefault();
        alert('Please select a file to upload');
        return;
    }
    
    if (!accountSelect.value) {
        e.preventDefault();
        alert('Please select a bank account');
        return;
    }
});
</script>