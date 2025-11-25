<?php
/**
 * Bank Account Controller
 *
 * Handles all bank account related HTTP requests and orchestrates
 * business logic with proper separation of concerns.
 *
 * @package Controllers
 *
 * Requirements Implemented:
 * - FR-1: User Authentication and Authorization
 * - FR-2: Bank Account Access Management
 * - FR-3: Permission Levels
 * - FR-4: User Interface
 * - FR-5: Data Integrity and Audit
 * - NFR-1: Performance
 * - NFR-2: Security
 * - NFR-3: Usability
 * - NFR-4: Maintainability
 * - NFR-5: Compatibility
 */

class BankAccountController {
    private $bankDAO;
    private $userAuth;
    private $navigationService;
    private $viewService;

    /**
     * Constructor with dependency injection
     *
     * @param BankAccountsDAO $bankDAO Data access object for bank accounts
     * @param UserAuthDAO $userAuth User authentication service
     * @param NavigationService $navigationService Navigation rendering service
     * @param BankAccountViewService $viewService View rendering service
     */
    public function __construct(
        BankAccountsDAO $bankDAO,
        UserAuthDAO $userAuth,
        NavigationService $navigationService,
        BankAccountViewService $viewService
    ) {
        $this->bankDAO = $bankDAO;
        $this->userAuth = $userAuth;
        $this->navigationService = $navigationService;
        $this->viewService = $viewService;
    }

    /**
     * Handle GET request for bank accounts page
     *
     * @param array $currentUser Current authenticated user
     * @return array Response data for rendering
     */
    public function handleGetRequest($currentUser) {
        $userId = $currentUser['id'];

        try {
            $userBankAccounts = $this->bankDAO->getUserAccessibleBankAccounts($userId);

            // Get access information for each account (for owners)
            $accountAccessInfo = [];
            foreach ($userBankAccounts as $account) {
                if ($account['permission_level'] === 'owner') {
                    $accessList = $this->bankDAO->getBankAccountAccess($account['id']);
                    $accountAccessInfo[$account['id']] = array_filter($accessList, function($access) use ($userId) {
                        return $access['user_id'] != $userId && $access['revoked_at'] === null;
                    });
                }
            }

            return [
                'success' => true,
                'userBankAccounts' => $userBankAccounts,
                'accountAccessInfo' => $accountAccessInfo,
                'userId' => $userId
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => "Error loading bank accounts: " . $e->getMessage()
            ];
        }
    }

    /**
     * Handle POST request for bank account operations
     *
     * @param array $postData POST data from request
     * @param array $currentUser Current authenticated user
     * @return array Response data
     */
    public function handlePostRequest($postData, $currentUser) {
        $userId = $currentUser['id'];

        // Handle bank account creation
        if (isset($postData['create_account'])) {
            return $this->handleCreateAccount($postData, $userId);
        }

        // Handle account access revocation
        if (isset($postData['action']) && $postData['action'] === 'revoke_access') {
            return $this->handleRevokeAccess($postData, $userId);
        }

        // Handle account sharing
        if (isset($postData['action']) && $postData['action'] === 'share_account') {
            return $this->handleShareAccount($postData, $userId);
        }

        return ['success' => false, 'error' => 'Invalid action'];
    }

    /**
     * Handle bank account creation
     *
     * @param array $postData POST data
     * @param int $userId User ID
     * @return array Response data
     */
    private function handleCreateAccount($postData, $userId) {
        $bankName = trim($postData['bank_name'] ?? '');
        $accountNumber = trim($postData['account_number'] ?? '');
        $nickname = trim($postData['nickname'] ?? '');
        $accountType = $postData['account_type'] ?? 'Investment Account';
        $currency = $postData['currency'] ?? 'CAD';

        try {
            $bankAccountId = $this->bankDAO->createBankAccountIfNotExists(
                $bankName,
                $accountNumber,
                $userId,
                $nickname,
                $accountType,
                $currency
            );

            return [
                'success' => true,
                'message' => "Bank account '{$bankName} - {$accountNumber}' has been created successfully and you have been granted owner access.",
                'action' => 'refresh'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => "Error creating bank account: " . $e->getMessage()
            ];
        }
    }

    /**
     * Handle account access revocation
     *
     * @param array $postData POST data
     * @param int $userId User ID
     * @return array Response data
     */
    private function handleRevokeAccess($postData, $userId) {
        $bankAccountId = (int)($postData['bank_account_id'] ?? 0);
        $revokeUserId = (int)($postData['revoke_user_id'] ?? 0);

        try {
            // Verify the current user owns this account
            $accountAccess = $this->bankDAO->getBankAccountAccess($bankAccountId);
            $isOwner = false;
            foreach ($accountAccess as $access) {
                if ($access['user_id'] == $userId && $access['permission_level'] === 'owner' && $access['revoked_at'] === null) {
                    $isOwner = true;
                    break;
                }
            }

            if (!$isOwner) {
                throw new Exception('You do not have permission to revoke access from this account.');
            }

            // Revoke access
            $this->bankDAO->revokeBankAccountAccess($bankAccountId, $revokeUserId);

            return [
                'success' => true,
                'message' => "Access has been revoked successfully.",
                'action' => 'refresh'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => "Error revoking access: " . $e->getMessage()
            ];
        }
    }

    /**
     * Handle account sharing
     *
     * @param array $postData POST data
     * @param int $userId User ID
     * @return array Response data
     */
    private function handleShareAccount($postData, $userId) {
        $bankAccountId = (int)($postData['bank_account_id'] ?? 0);
        $shareUserId = (int)($postData['share_user_id'] ?? 0);
        $permissionLevel = $postData['permission_level'] ?? '';

        try {
            // Verify the current user owns this account
            $accountAccess = $this->bankDAO->getBankAccountAccess($bankAccountId);
            $isOwner = false;
            foreach ($accountAccess as $access) {
                if ($access['user_id'] == $userId && $access['permission_level'] === 'owner' && $access['revoked_at'] === null) {
                    $isOwner = true;
                    break;
                }
            }

            if (!$isOwner) {
                throw new Exception('You do not have permission to share this account.');
            }

            // Validate permission level
            if (!in_array($permissionLevel, ['read', 'read_write', 'owner'])) {
                throw new Exception('Invalid permission level.');
            }

            // Share the account
            $this->bankDAO->setBankAccountAccess($bankAccountId, $shareUserId, $permissionLevel, $userId);

            return [
                'success' => true,
                'message' => "Account has been shared successfully.",
                'action' => 'refresh'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => "Error sharing account: " . $e->getMessage()
            ];
        }
    }

    /**
     * Render the complete page
     *
     * @param array $data Page data
     * @return string Complete HTML page
     */
    public function renderPage($data) {
        return $this->viewService->renderPage($data);
    }
}