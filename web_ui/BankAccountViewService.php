<?php
/**
 * Bank Account View Service
 *
 * Handles rendering of bank account related UI components with proper
 * separation of concerns and component-based architecture.
 *
 * @package ViewServices
 *
 * Requirements Implemented:
 * - FR-4: User Interface
 * - NFR-3: Usability
 * - NFR-4: Maintainability
 */

class BankAccountViewService {
    private $navigationService;

    /**
     * Constructor with dependency injection
     *
     * @param NavigationService $navigationService Navigation rendering service
     */
    public function __construct(NavigationService $navigationService) {
        $this->navigationService = $navigationService;
    }

    /**
     * Render the complete bank accounts page
     *
     * @param array $data Page data including accounts, access info, etc.
     * @return string Complete HTML page
     */
    public function renderPage($data) {
        $navHeader = $this->navigationService->renderNavigationHeader('My Bank Accounts');
        $navCSS = $this->navigationService->getDashboardCSS();
        $navScript = $this->navigationService->getNavigationScript();

        $html = $this->renderHtmlHead();
        $html .= $navHeader;
        $html .= '<div class="bank-accounts-container">';

        if (isset($data['error'])) {
            $html .= $this->renderErrorMessage($data['error']);
        }

        $html .= $this->renderHeader();
        $html .= $this->renderAccountInfo($data);
        $html .= $this->renderCreateAccountForm();

        if (isset($data['message'])) {
            $html .= $this->renderSuccessMessage($data['message']);
        }

        if (empty($data['userBankAccounts'])) {
            $html .= $this->renderNoAccountsMessage();
        } else {
            $html .= $this->renderAccountsTable($data);
        }

        $html .= '</div>';
        $html .= $this->renderShareAccountModal($data);
        $html .= $navScript;
        $html .= $this->renderJavaScript();
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Render HTML head section
     *
     * @return string HTML head
     */
    private function renderHtmlHead() {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bank Accounts - Enhanced Trading System</title>
    <link rel="stylesheet" href="css/nav-core.css">
    <link rel="stylesheet" href="css/nav-links.css">
    <link rel="stylesheet" href="css/dropdown-base.css">
    <link rel="stylesheet" href="css/user-dropdown.css">
    <link rel="stylesheet" href="css/portfolio-dropdown.css">
    <link rel="stylesheet" href="css/stocks-dropdown.css">
    <link rel="stylesheet" href="css/nav-responsive.css">
    <style>' . $this->getPageStyles() . '</style>
</head>
<body>';
    }

    /**
     * Get page-specific CSS styles
     *
     * @return string CSS styles
     */
    private function getPageStyles() {
        return '
        .bank-accounts-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .bank-accounts-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .bank-accounts-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .bank-accounts-table th,
        .bank-accounts-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .bank-accounts-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .bank-accounts-table tr:hover {
            background: #f8f9fa;
        }

        .permission-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .permission-owner {
            background: #28a745;
            color: white;
        }

        .permission-read-write {
            background: #007bff;
            color: white;
        }

        .permission-read {
            background: #6c757d;
            color: white;
        }

        .view-transactions-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .view-transactions-btn:hover {
            background: #0056b3;
            text-decoration: none;
            color: white;
        }

        .no-accounts {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .account-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .account-info h3 {
            margin-top: 0;
            color: #495057;
        }';
    }

    /**
     * Render page header
     *
     * @return string HTML header
     */
    private function renderHeader() {
        return '<div class="bank-accounts-header">
            <h1>My Bank Accounts</h1>
            <p>View the bank accounts you have access to</p>
        </div>';
    }

    /**
     * Render account information section
     *
     * @param array $data Page data
     * @return string HTML account info
     */
    private function renderAccountInfo($data) {
        $accountCount = count($data['userBankAccounts'] ?? []);

        return '<div class="account-info">
            <h3>Account Access Information</h3>
            <p>You have access to <strong>' . $accountCount . '</strong> bank account(s).</p>
            <ul>
                <li><strong>Owner</strong>: Full access including managing who can access this account</li>
                <li><strong>Read/Write</strong>: Can view and modify account data</li>
                <li><strong>Read</strong>: Can only view account data</li>
            </ul>
        </div>';
    }

    /**
     * Render create account form
     *
     * @return string HTML form
     */
    private function renderCreateAccountForm() {
        return '<div class="create-account-section" style="background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); border: 2px solid #2196f3; padding: 25px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <h3 style="color: #1976d2; margin-top: 0; text-align: center;">🏦 Create New Bank Account</h3>
            <p style="text-align: center; color: #666; margin-bottom: 20px;">Add a new bank account to track your transactions</p>
            <form method="post" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; align-items: end;">
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
                    <button type="submit" name="create_account" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 16px;">
                        Create Bank Account
                    </button>
                </div>
            </form>
        </div>';
    }

    /**
     * Render success message
     *
     * @param string $message Success message
     * @return string HTML message
     */
    private function renderSuccessMessage($message) {
        return '<div class="success-message" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb;">' .
               htmlspecialchars($message) . '</div>';
    }

    /**
     * Render error message
     *
     * @param string $error Error message
     * @return string HTML message
     */
    private function renderErrorMessage($error) {
        return '<div class="error-message">' . htmlspecialchars($error) . '</div>';
    }

    /**
     * Render no accounts message
     *
     * @return string HTML message
     */
    private function renderNoAccountsMessage() {
        return '<div class="no-accounts">
            <h3>No Bank Accounts Found</h3>
            <p>You don\'t have access to any bank accounts yet. Use the form above to create your first bank account, or import transactions from a CSV file.</p>
        </div>';
    }

    /**
     * Render accounts table
     *
     * @param array $data Page data
     * @return string HTML table
     */
    private function renderAccountsTable($data) {
        $html = '<table class="bank-accounts-table">
            <thead>
                <tr>
                    <th>Bank Name</th>
                    <th>Account Number</th>
                    <th>Nickname</th>
                    <th>Account Type</th>
                    <th>Currency</th>
                    <th>Your Permission</th>
                    <th>Shared With</th>
                    <th>Granted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($data['userBankAccounts'] as $account) {
            $html .= $this->renderAccountRow($account, $data);
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Render account table row
     *
     * @param array $account Account data
     * @param array $data Page data
     * @return string HTML row
     */
    private function renderAccountRow($account, $data) {
        $userId = $data['userId'];
        $accountAccessInfo = $data['accountAccessInfo'] ?? [];

        $html = '<tr>';
        $html .= '<td>' . htmlspecialchars($account['bank_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($account['account_number']) . '</td>';
        $html .= '<td>' . htmlspecialchars($account['account_nickname'] ?: 'N/A') . '</td>';
        $html .= '<td>' . htmlspecialchars($account['account_type'] ?: 'N/A') . '</td>';
        $html .= '<td>' . htmlspecialchars($account['currency']) . '</td>';
        $html .= '<td><span class="permission-badge permission-' . str_replace('_', '-', $account['permission_level']) . '">' .
                htmlspecialchars(ucfirst(str_replace('_', '/', $account['permission_level']))) . '</span></td>';

        // Shared with column
        $html .= '<td>';
        if ($account['permission_level'] === 'owner' && isset($accountAccessInfo[$account['id']])) {
            $sharedUsers = $accountAccessInfo[$account['id']];
            if (count($sharedUsers) > 0) {
                $html .= '<div style="font-size: 12px;">';
                foreach ($sharedUsers as $access) {
                    $html .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">';
                    $html .= '<span>' . htmlspecialchars($access['username']) . ' (' . ucfirst(str_replace('_', '/', $access['permission_level'])) . ')</span>';
                    $html .= '<form method="post" style="display: inline; margin-left: 5px;">
                        <input type="hidden" name="action" value="revoke_access">
                        <input type="hidden" name="bank_account_id" value="' . $account['id'] . '">
                        <input type="hidden" name="revoke_user_id" value="' . $access['user_id'] . '">
                        <button type="submit" style="background: #dc3545; color: white; border: none; padding: 2px 6px; border-radius: 3px; cursor: pointer; font-size: 10px;" onclick="return confirm(\'Are you sure you want to revoke access for ' . htmlspecialchars($access['username']) . '?\')">Revoke</button>
                    </form>';
                    $html .= '</div>';
                }
                $html .= '</div>';
            } else {
                $html .= '<em style="color: #666; font-size: 12px;">Not shared</em>';
            }
        } else {
            $html .= '<em style="color: #666; font-size: 12px;">N/A</em>';
        }
        $html .= '</td>';

        // Granted column
        $html .= '<td>' . htmlspecialchars(date('M j, Y', strtotime($account['granted_at'])));
        if ($account['granted_by_username']) {
            $html .= '<br><small>by ' . htmlspecialchars($account['granted_by_username']) . '</small>';
        }
        $html .= '</td>';

        // Actions column
        $html .= '<td>';
        $html .= '<a href="view_imported_transactions.php?bank_account_id=' . $account['id'] . '" class="view-transactions-btn">View Transactions</a>';
        if ($account['permission_level'] === 'owner') {
            $html .= '<button type="button" class="share-account-btn" data-account-id="' . $account['id'] . '" data-account-name="' . htmlspecialchars($account['bank_name'] . ' - ' . $account['account_number']) . '" style="background: #17a2b8; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 14px; margin-left: 5px;">Share Account</button>';
        }
        $html .= '</td>';

        $html .= '</tr>';
        return $html;
    }

    /**
     * Render share account modal
     *
     * @param array $data Page data
     * @return string HTML modal
     */
    private function renderShareAccountModal($data) {
        $userId = $data['userId'];

        $html = '<!-- Share Account Modal -->
        <div id="shareAccountModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
            <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 0; border: 1px solid #888; width: 90%; max-width: 600px; border-radius: 8px;">
                <div class="modal-header" style="padding: 15px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px 8px 0 0;">
                    <h3 id="modalTitle" style="margin: 0;">Share Account</h3>
                    <span class="close" style="color: white; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <form id="shareAccountForm" method="post">
                        <input type="hidden" name="action" value="share_account">
                        <input type="hidden" name="bank_account_id" id="modalBankAccountId">

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Select User to Share With:</label>
                            <select name="share_user_id" id="shareUserSelect" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="">Choose a user...</option>';

        // Get all users for sharing
        try {
            $allUsers = $this->getAllUsersForSharing($userId);
            foreach ($allUsers as $user) {
                $html .= '<option value="' . $user['id'] . '">' . htmlspecialchars($user['username'] . ' (' . $user['email'] . ')') . '</option>';
            }
        } catch (Exception $e) {
            $html .= '<option value="">Error loading users: ' . htmlspecialchars($e->getMessage()) . '</option>';
        }

        $html .= '</select>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Permission Level:</label>
                            <select name="permission_level" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="read">Read Only - Can view transactions</option>
                                <option value="read_write">Read/Write - Can view and modify transactions</option>
                                <option value="owner">Owner - Full access including sharing management</option>
                            </select>
                        </div>

                        <div style="text-align: right;">
                            <button type="button" id="cancelShare" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-right: 10px;">Cancel</button>
                            <button type="submit" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Share Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';

        return $html;
    }

    /**
     * Get all users for sharing dropdown
     *
     * @param int $currentUserId Current user ID to exclude
     * @return array Array of users
     */
    private function getAllUsersForSharing($currentUserId) {
        // This would normally be injected, but for now we'll create it
        $userAuth = new UserAuthDAO();
        $allUsers = $userAuth->getAllUsers(1000);

        // Filter out current user
        return array_filter($allUsers, function($user) use ($currentUserId) {
            return $user['id'] != $currentUserId;
        });
    }

    /**
     * Render JavaScript for modal functionality
     *
     * @return string JavaScript code
     */
    private function renderJavaScript() {
        return '<script>
            // Modal functionality
            const modal = document.getElementById(\'shareAccountModal\');
            const closeBtn = document.querySelector(\'.close\');
            const cancelBtn = document.getElementById(\'cancelShare\');

            // Open modal when share button is clicked
            document.querySelectorAll(\'.share-account-btn\').forEach(btn => {
                btn.addEventListener(\'click\', function() {
                    console.log(\'Share button clicked for account:\', this.getAttribute(\'data-account-id\'));
                    const accountId = this.getAttribute(\'data-account-id\');
                    const accountName = this.getAttribute(\'data-account-name\');

                    document.getElementById(\'modalBankAccountId\').value = accountId;
                    document.getElementById(\'modalTitle\').textContent = \'Share Account: \' + accountName;
                    modal.style.display = \'block\';
                    console.log(\'Modal should now be visible\');
                });
            });

            // Close modal
            closeBtn.addEventListener(\'click\', () => {
                modal.style.display = \'none\';
            });

            cancelBtn.addEventListener(\'click\', () => {
                modal.style.display = \'none\';
            });

            // Close modal when clicking outside
            window.addEventListener(\'click\', (event) => {
                if (event.target === modal) {
                    modal.style.display = \'none\';
                }
            });

            // Debug: Check if buttons exist
            document.addEventListener(\'DOMContentLoaded\', function() {
                console.log(\'Page loaded, checking for share buttons...\');
                const shareButtons = document.querySelectorAll(\'.share-account-btn\');
                console.log(\'Found\', shareButtons.length, \'share buttons\');
            });
        </script>';
    }
}