<?php
/**
 * Invitation Service - SRP-compliant service for invitation management
 * 
 * Handles all invitation-related operations including:
 * - Friend invitations
 * - Advisor invitations with client approval
 * - Advisor upgrade requests
 * - Email notifications and token management
 */

require_once __DIR__ . '/UserAuthDAO.php';
require_once __DIR__ . '/RBACService.php';

class InvitationService {
    private $pdo;
    private $userAuth;
    private $rbac;
    
    public function __construct() {
        $this->userAuth = new UserAuthDAO();
        $this->rbac = new RBACService();
        
        // Get PDO connection
        $reflection = new ReflectionClass($this->userAuth);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $this->pdo = $pdoProperty->getValue($this->userAuth);
    }
    
    // =============================================
    // Friend Invitations
    // =============================================
    
    /**
     * Send friend invitation
     * 
     * @param int $inviterId User sending the invitation
     * @param string $inviteeEmail Email of person being invited
     * @param string $message Optional personal message
     * @return array Result with success status and invitation ID
     */
    public function sendFriendInvitation(int $inviterId, string $inviteeEmail, string $message = ''): array {
        try {
            // Validate email
            if (!filter_var($inviteeEmail, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'error' => 'Invalid email address'];
            }
            
            // Check if user already exists
            $existingUser = $this->getUserByEmail($inviteeEmail);
            
            // Check for existing pending invitation
            $existing = $this->getExistingInvitation($inviterId, $inviteeEmail, 'friend');
            if ($existing) {
                return ['success' => false, 'error' => 'Invitation already sent'];
            }
            
            // Generate invitation token
            $token = $this->generateInvitationToken();
            
            // Create invitation
            $stmt = $this->pdo->prepare("
                INSERT INTO invitations (
                    inviter_id, invitee_email, invitee_id, invitation_type, 
                    subject, message, invitation_token, expires_at
                ) VALUES (?, ?, ?, 'friend', ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
            ");
            
            $inviter = $this->userAuth->getUserById($inviterId);
            $subject = "You're invited to join {$inviter['username']}'s portfolio network!";
            
            $stmt->execute([
                $inviterId,
                $inviteeEmail,
                $existingUser ? $existingUser['id'] : null,
                $subject,
                $message,
                $token
            ]);
            
            $invitationId = $this->pdo->lastInsertId();
            
            // Send email notification (placeholder - implement actual email sending)
            $this->sendInvitationEmail($invitationId);
            
            return [
                'success' => true, 
                'invitation_id' => $invitationId, 
                'token' => $token,
                'message' => 'Friend invitation sent successfully!'
            ];
            
        } catch (Exception $e) {
            error_log("Friend invitation error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to send invitation'];
        }
    }
    
    // =============================================
    // Advisor Invitations
    // =============================================
    
    /**
     * Send advisor invitation to existing user or email
     * 
     * @param int $clientId Client sending the invitation
     * @param string $advisorEmailOrId Advisor email or user ID
     * @param string $permissionLevel 'read' or 'read_write'
     * @param string $message Optional message
     * @return array Result with success status
     */
    public function sendAdvisorInvitation(int $clientId, string $advisorEmailOrId, string $permissionLevel, string $message = ''): array {
        try {
            // Validate permission level
            if (!in_array($permissionLevel, ['read', 'read_write'])) {
                return ['success' => false, 'error' => 'Invalid permission level'];
            }
            
            $advisorEmail = '';
            $advisorId = null;
            
            // Determine if input is email or user ID
            if (filter_var($advisorEmailOrId, FILTER_VALIDATE_EMAIL)) {
                $advisorEmail = $advisorEmailOrId;
                $existingAdvisor = $this->getUserByEmail($advisorEmail);
                $advisorId = $existingAdvisor ? $existingAdvisor['id'] : null;
            } else {
                // Assume it's a user ID
                $advisorId = intval($advisorEmailOrId);
                $advisor = $this->userAuth->getUserById($advisorId);
                if ($advisor) {
                    $advisorEmail = $advisor['email'];
                } else {
                    return ['success' => false, 'error' => 'Advisor not found'];
                }
            }
            
            // Check for existing relationship
            $existing = $this->getExistingAdvisorRelationship($clientId, $advisorId ?: $advisorEmail);
            if ($existing) {
                return ['success' => false, 'error' => 'Advisor relationship already exists'];
            }
            
            // NEW: If advisor already exists as a user, auto-create relationship
            if ($advisorId && $existingAdvisor) {
                return $this->autoCreateAdvisorRelationship($clientId, $advisorId, $permissionLevel, $advisorEmail, $message);
            }
            
            // ORIGINAL: For new users, create invitation
            // Generate token
            $token = $this->generateInvitationToken();
            
            // Create invitation
            $stmt = $this->pdo->prepare("
                INSERT INTO invitations (
                    inviter_id, invitee_email, invitee_id, invitation_type,
                    subject, message, invitation_token, requested_permission_level,
                    expires_at
                ) VALUES (?, ?, ?, 'advisor', ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
            ");
            
            $client = $this->userAuth->getUserById($clientId);
            $subject = "{$client['username']} wants you to be their financial advisor";
            
            $stmt->execute([
                $clientId,
                $advisorEmail,
                $advisorId,
                $subject,
                $message,
                $token,
                $permissionLevel
            ]);
            
            $invitationId = $this->pdo->lastInsertId();
            
            // Send email notification
            $this->sendInvitationEmail($invitationId);
            
            return [
                'success' => true,
                'invitation_id' => $invitationId,
                'message' => 'Advisor invitation sent successfully!'
            ];
            
        } catch (Exception $e) {
            error_log("Advisor invitation error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to send advisor invitation'];
        }
    }
    
    // =============================================
    // Advisor Upgrade Requests
    // =============================================
    
    /**
     * Request upgrade to advisor account
     * 
     * @param int $userId User requesting upgrade
     * @param int|string $clientIdOrEmail Existing client ID or email to invite
     * @param array $upgradeDetails Business name, credentials, description
     * @return array Result with success status
     */
    public function requestAdvisorUpgrade(int $userId, $clientIdOrEmail, array $upgradeDetails): array {
        try {
            // Check if user already has pending upgrade request
            $existing = $this->pdo->prepare("SELECT id FROM advisor_upgrade_requests WHERE user_id = ? AND status IN ('pending_client', 'client_approved', 'admin_review')");
            $existing->execute([$userId]);
            
            if ($existing->fetch()) {
                return ['success' => false, 'error' => 'You already have a pending advisor upgrade request'];
            }
            
            $clientId = null;
            $clientEmail = '';
            
            // Determine if client is existing user or email
            if (filter_var($clientIdOrEmail, FILTER_VALIDATE_EMAIL)) {
                $clientEmail = $clientIdOrEmail;
                $existingClient = $this->getUserByEmail($clientEmail);
                $clientId = $existingClient ? $existingClient['id'] : null;
            } else {
                $clientId = intval($clientIdOrEmail);
                $client = $this->userAuth->getUserById($clientId);
                $clientEmail = $client ? $client['email'] : '';
            }
            
            if (!$clientEmail) {
                return ['success' => false, 'error' => 'Invalid client information'];
            }
            
            // Generate approval token
            $approvalToken = $this->generateInvitationToken();
            
            // Create upgrade request
            $stmt = $this->pdo->prepare("
                INSERT INTO advisor_upgrade_requests (
                    user_id, client_id, client_email, business_name,
                    credentials, description, client_approval_token
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $clientId,
                $clientEmail,
                $upgradeDetails['business_name'] ?? '',
                $upgradeDetails['credentials'] ?? '',
                $upgradeDetails['description'] ?? '',
                $approvalToken
            ]);
            
            $requestId = $this->pdo->lastInsertId();
            
            // Send client approval email
            $this->sendClientApprovalEmail($requestId);
            
            return [
                'success' => true,
                'request_id' => $requestId,
                'approval_token' => $approvalToken,
                'message' => 'Advisor upgrade request submitted! Client approval required.'
            ];
            
        } catch (Exception $e) {
            error_log("Advisor upgrade error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to submit upgrade request'];
        }
    }
    
    /**
     * Client approves advisor upgrade request
     * 
     * @param string $approvalToken Token from email
     * @param bool $approved Whether client approves
     * @param string $responseMessage Optional response message
     * @return array Result with success status
     */
    public function processClientApproval(string $approvalToken, bool $approved, string $responseMessage = ''): array {
        try {
            // Find the upgrade request
            $stmt = $this->pdo->prepare("
                SELECT * FROM advisor_upgrade_requests 
                WHERE client_approval_token = ? AND status = 'pending_client'
            ");
            $stmt->execute([$approvalToken]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                return ['success' => false, 'error' => 'Invalid or expired approval token'];
            }
            
            if ($approved) {
                // Update status to client approved
                $updateStmt = $this->pdo->prepare("
                    UPDATE advisor_upgrade_requests 
                    SET status = 'client_approved', 
                        client_approved_at = NOW(),
                        client_response_message = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$responseMessage, $request['id']]);
                
                // Automatically approve if no admin review needed, or mark for admin review
                $this->processAdvisorUpgradeApproval($request['id'], true, 'Auto-approved after client confirmation');
                
                return ['success' => true, 'message' => 'Advisor upgrade approved successfully!'];
            } else {
                // Update status to rejected
                $updateStmt = $this->pdo->prepare("
                    UPDATE advisor_upgrade_requests 
                    SET status = 'rejected',
                        client_response_message = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$responseMessage, $request['id']]);
                
                return ['success' => true, 'message' => 'Advisor upgrade request declined'];
            }
            
        } catch (Exception $e) {
            error_log("Client approval error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to process approval'];
        }
    }
    
    /**
     * Process advisor upgrade approval (final step)
     * 
     * @param int $requestId Upgrade request ID
     * @param bool $approved Whether to approve
     * @param string $adminMessage Admin response message
     * @return array Result with success status
     */
    public function processAdvisorUpgradeApproval(int $requestId, bool $approved, string $adminMessage = ''): array {
        try {
            $request = $this->getUpgradeRequestById($requestId);
            if (!$request) {
                return ['success' => false, 'error' => 'Upgrade request not found'];
            }
            
            if ($approved) {
                // Begin transaction
                $this->pdo->beginTransaction();
                
                try {
                    // 1. Update request status
                    $stmt = $this->pdo->prepare("
                        UPDATE advisor_upgrade_requests 
                        SET status = 'approved', 
                            admin_reviewed_at = NOW(),
                            completed_at = NOW(),
                            admin_response_message = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$adminMessage, $requestId]);
                    
                    // 2. Assign advisor role to user
                    $this->rbac->assignRole($request['user_id'], 'advisor', null, 'Upgraded via client approval');
                    
                    // 3. Create advisor-client relationship if client exists
                    if ($request['client_id']) {
                        $this->createAdvisorClientRelationship(
                            $request['client_id'], 
                            $request['user_id'], 
                            'read_write',  // Default permission level
                            $requestId
                        );
                    }
                    
                    $this->pdo->commit();
                    
                    return ['success' => true, 'message' => 'Advisor upgrade completed successfully!'];
                    
                } catch (Exception $e) {
                    $this->pdo->rollback();
                    throw $e;
                }
            } else {
                // Reject the request
                $stmt = $this->pdo->prepare("
                    UPDATE advisor_upgrade_requests 
                    SET status = 'rejected',
                        admin_reviewed_at = NOW(),
                        admin_response_message = ?
                    WHERE id = ?
                ");
                $stmt->execute([$adminMessage, $requestId]);
                
                return ['success' => true, 'message' => 'Advisor upgrade request rejected'];
            }
            
        } catch (Exception $e) {
            error_log("Advisor upgrade approval error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to process upgrade approval'];
        }
    }
    
    // =============================================
    // Invitation Response Handling
    // =============================================
    
    /**
     * Accept invitation
     * 
     * @param string $token Invitation token
     * @param int|null $userId User accepting (null if registering)
     * @return array Result with success status
     */
    public function acceptInvitation(string $token, ?int $userId = null): array {
        try {
            $invitation = $this->getInvitationByToken($token);
            
            if (!$invitation) {
                return ['success' => false, 'error' => 'Invalid invitation token'];
            }
            
            if ($invitation['status'] !== 'pending') {
                return ['success' => false, 'error' => 'Invitation has already been responded to'];
            }
            
            if ($invitation['expires_at'] && strtotime($invitation['expires_at']) < time()) {
                return ['success' => false, 'error' => 'Invitation has expired'];
            }
            
            // Update invitation status
            $stmt = $this->pdo->prepare("
                UPDATE invitations 
                SET status = 'accepted', 
                    responded_at = NOW(),
                    invitee_id = COALESCE(invitee_id, ?)
                WHERE id = ?
            ");
            $stmt->execute([$userId, $invitation['id']]);
            
            // Handle based on invitation type
            switch ($invitation['invitation_type']) {
                case 'friend':
                    return $this->processFriendInvitationAcceptance($invitation, $userId);
                    
                case 'advisor':
                    return $this->processAdvisorInvitationAcceptance($invitation, $userId);
                    
                default:
                    return ['success' => false, 'error' => 'Unknown invitation type'];
            }
            
        } catch (Exception $e) {
            error_log("Accept invitation error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to accept invitation'];
        }
    }
    
    /**
     * Decline invitation
     * 
     * @param string $token Invitation token
     * @param string $reason Optional decline reason
     * @return array Result with success status
     */
    public function declineInvitation(string $token, string $reason = ''): array {
        try {
            $invitation = $this->getInvitationByToken($token);
            
            if (!$invitation) {
                return ['success' => false, 'error' => 'Invalid invitation token'];
            }
            
            if ($invitation['status'] !== 'pending') {
                return ['success' => false, 'error' => 'Invitation has already been responded to'];
            }
            
            // Update invitation status
            $stmt = $this->pdo->prepare("
                UPDATE invitations 
                SET status = 'declined', 
                    responded_at = NOW(),
                    response_message = ?
                WHERE id = ?
            ");
            $stmt->execute([$reason, $invitation['id']]);
            
            return ['success' => true, 'message' => 'Invitation declined'];
            
        } catch (Exception $e) {
            error_log("Decline invitation error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to decline invitation'];
        }
    }
    
    // =============================================
    // Helper Methods
    // =============================================
    
    private function generateInvitationToken(): string {
        return bin2hex(random_bytes(32));
    }
    
    private function getUserByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    private function getExistingInvitation(int $inviterId, string $email, string $type): ?array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM invitations 
            WHERE inviter_id = ? AND invitee_email = ? AND invitation_type = ? AND status = 'pending'
        ");
        $stmt->execute([$inviterId, $email, $type]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    private function getExistingAdvisorRelationship(int $clientId, $advisorIdOrEmail): ?array {
        if (is_numeric($advisorIdOrEmail)) {
            $stmt = $this->pdo->prepare("
                SELECT * FROM user_advisors 
                WHERE user_id = ? AND advisor_id = ? AND status IN ('pending', 'active')
            ");
            $stmt->execute([$clientId, $advisorIdOrEmail]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT ua.* FROM user_advisors ua
                JOIN users u ON ua.advisor_id = u.id 
                WHERE ua.user_id = ? AND u.email = ? AND ua.status IN ('pending', 'active')
            ");
            $stmt->execute([$clientId, $advisorIdOrEmail]);
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    private function getInvitationByToken(string $token): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM invitations WHERE invitation_token = ?");
        $stmt->execute([$token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    private function getUpgradeRequestById(int $requestId): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM advisor_upgrade_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    private function createAdvisorClientRelationship(int $clientId, int $advisorId, string $permissionLevel, ?int $upgradeRequestId = null): bool {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_advisors (
                    user_id, advisor_id, permission_level, status, 
                    upgrade_request_id, invited_at, accepted_at
                ) VALUES (?, ?, ?, 'active', ?, NOW(), NOW())
            ");
            
            return $stmt->execute([$clientId, $advisorId, $permissionLevel, $upgradeRequestId]);
            
        } catch (Exception $e) {
            error_log("Create advisor relationship error: " . $e->getMessage());
            return false;
        }
    }
    
    private function processFriendInvitationAcceptance(array $invitation, ?int $userId): array {
        // For friend invitations, we might just add them to a friends list or network
        // This could be implemented later based on specific requirements
        return ['success' => true, 'message' => 'Friend invitation accepted!'];
    }
    
    private function processAdvisorInvitationAcceptance(array $invitation, ?int $userId): array {
        try {
            if (!$userId) {
                return ['success' => false, 'error' => 'User must be logged in to accept advisor invitation'];
            }
            
            // Create advisor-client relationship
            $this->createAdvisorClientRelationship(
                $invitation['inviter_id'],  // Client ID
                $userId,                     // Advisor ID
                $invitation['requested_permission_level'] ?: 'read',
                null                        // No upgrade request
            );
            
            // Assign advisor role if not already assigned
            if (!$this->rbac->hasRole('advisor', $userId)) {
                $this->rbac->assignRole($userId, 'advisor', $invitation['inviter_id'], 'Accepted advisor invitation');
            }
            
            return ['success' => true, 'message' => 'Advisor invitation accepted! You can now advise this client.'];
            
        } catch (Exception $e) {
            error_log("Process advisor acceptance error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to establish advisor relationship'];
        }
    }
    
    // Placeholder methods for email sending (implement with actual email service)
    private function sendInvitationEmail(int $invitationId): bool {
        // TODO: Implement actual email sending
        error_log("Would send invitation email for invitation ID: $invitationId");
        return true;
    }
    
    private function sendClientApprovalEmail(int $requestId): bool {
        // TODO: Implement actual email sending
        error_log("Would send client approval email for request ID: $requestId");
        return true;
    }
    
    // =============================================
    // Public Query Methods
    // =============================================
    
    /**
     * Get user's sent invitations
     */
    public function getUserSentInvitations(int $userId, ?string $type = null): array {
        $sql = "SELECT * FROM invitations WHERE inviter_id = ?";
        $params = [$userId];
        
        if ($type) {
            $sql .= " AND invitation_type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user's received invitations
     */
    public function getUserReceivedInvitations(int $userId): array {
        $stmt = $this->pdo->prepare("
            SELECT i.*, u.username as inviter_name 
            FROM invitations i
            JOIN users u ON i.inviter_id = u.id
            WHERE i.invitee_id = ? 
            ORDER BY i.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user's advisor upgrade requests
     */
    public function getUserUpgradeRequests(int $userId): array {
        $stmt = $this->pdo->prepare("
            SELECT aur.*, u.username as client_name
            FROM advisor_upgrade_requests aur
            LEFT JOIN users u ON aur.client_id = u.id
            WHERE aur.user_id = ?
            ORDER BY aur.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get upgrade request by token
     * 
     * @param string $token Upgrade request token
     * @return array|null Upgrade request details or null if not found
     */
    public function getUpgradeRequestByToken(string $token): ?array {
        $stmt = $this->pdo->prepare("
            SELECT aur.*, u.username as applicant_name, u.email as applicant_email
            FROM advisor_upgrade_requests aur
            JOIN users u ON aur.user_id = u.id
            WHERE aur.approval_token = ?
        ");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Auto-create advisor relationship when inviting existing user
     * 
     * Instead of creating an invitation, directly create the advisor-client relationship
     * and send a notification email. This streamlines the process for existing users.
     * 
     * @param int $clientId Client user ID
     * @param int $advisorId Advisor user ID (existing user)
     * @param string $permissionLevel 'read' or 'read_write'
     * @param string $advisorEmail Advisor email for notifications
     * @param string $message Optional message from client
     * @return array Result with success status
     */
    private function autoCreateAdvisorRelationship(int $clientId, int $advisorId, string $permissionLevel, string $advisorEmail, string $message = ''): array {
        try {
            // Create the advisor-client relationship directly
            // Get the current user ID (who is creating this relationship)
            $currentUserId = $this->userAuth->getCurrentUserId();
            if (!$currentUserId) {
                // Fallback: use the client's ID if no current user (shouldn't happen but defensive coding)
                $currentUserId = $clientId;
            }
            
            $autoNote = "User existed in system - relationship created automatically. " . ($message ?: 'No additional message.');
            
            $stmt = $this->pdo->prepare("
                INSERT INTO user_advisors (
                    user_id, advisor_id, permission_level, status, 
                    created_by, notes
                ) VALUES (?, ?, ?, 'pending', ?, ?)
            ");
            
            $stmt->execute([$clientId, $advisorId, $permissionLevel, $currentUserId, $autoNote]);
            $relationshipId = $this->pdo->lastInsertId();
            
            // Create invitation record for tracking/notification purposes with special status
            $token = $this->generateInvitationToken();
            $stmt = $this->pdo->prepare("
                INSERT INTO invitations (
                    inviter_id, invitee_email, invitee_id, invitation_type,
                    subject, message, invitation_token, requested_permission_level,
                    status, expires_at
                ) VALUES (?, ?, ?, 'advisor', ?, ?, ?, ?, 'auto_accepted', DATE_ADD(NOW(), INTERVAL 30 DAY))
            ");
            
            $client = $this->userAuth->getUserById($clientId);
            $subject = "{$client['username']} has added you as their financial advisor";
            $notificationMessage = "You have been automatically added as an advisor since you already have an account. " . ($message ?: '');
            
            $stmt->execute([
                $clientId,
                $advisorEmail,
                $advisorId,
                $subject,
                $notificationMessage,
                $token,
                $permissionLevel
            ]);
            
            $invitationId = $this->pdo->lastInsertId();
            
            // Send notification email (different from invitation - this is a notification of established relationship)
            $this->sendInvitationEmail($invitationId);
            
            return [
                'success' => true,
                'invitation_id' => $invitationId,
                'relationship_id' => $relationshipId,
                'auto_created' => true,
                'message' => 'User already exists! Advisor relationship created automatically and notification sent.'
            ];
            
        } catch (Exception $e) {
            error_log("Auto-create advisor relationship error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to create advisor relationship automatically'];
        }
    }

    // =============================================
    // Invitation Management (Admin Functions)
    // =============================================
    
    /**
     * Revoke/cancel an invitation
     * 
     * @param int $invitationId Invitation ID to revoke
     * @param int $revokedBy User ID of who is revoking the invitation
     * @param string $reason Optional reason for revocation
     * @return array Result with success status and message
     */
    public function revokeInvitation(int $invitationId, int $revokedBy, string $reason = ''): array {
        try {
            // Get invitation details first
            $stmt = $this->pdo->prepare("
                SELECT id, inviter_id, invitee_email, invitation_type, status, expires_at
                FROM invitations 
                WHERE id = ?
            ");
            $stmt->execute([$invitationId]);
            $invitation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$invitation) {
                return ['success' => false, 'error' => 'Invitation not found'];
            }
            
            // Check if invitation can be revoked
            $revokableStatuses = ['pending', 'pending_client', 'client_approved'];
            if (!in_array($invitation['status'], $revokableStatuses)) {
                return ['success' => false, 'error' => 'Cannot revoke invitation with status: ' . $invitation['status']];
            }
            
            // Update invitation status to revoked
            $updateStmt = $this->pdo->prepare("
                UPDATE invitations 
                SET status = 'revoked',
                    revoked_at = NOW(),
                    revoked_by = ?,
                    revocation_reason = ?
                WHERE id = ?
            ");
            
            $revocationReason = $reason ?: 'Revoked by administrator';
            $updateStmt->execute([$revokedBy, $revocationReason, $invitationId]);
            
            if ($updateStmt->rowCount() > 0) {
                return [
                    'success' => true, 
                    'message' => 'Invitation successfully revoked',
                    'invitation_id' => $invitationId,
                    'previous_status' => $invitation['status']
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to revoke invitation'];
            }
            
        } catch (Exception $e) {
            error_log("Invitation revocation error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to revoke invitation'];
        }
    }
    
    /**
     * Delete an invitation permanently (admin function)
     * 
     * @param int $invitationId Invitation ID to delete
     * @return array Result with success status and message
     */
    public function deleteInvitation(int $invitationId): array {
        try {
            // Get invitation details for logging
            $stmt = $this->pdo->prepare("
                SELECT id, inviter_id, invitee_email, invitation_type, status
                FROM invitations 
                WHERE id = ?
            ");
            $stmt->execute([$invitationId]);
            $invitation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$invitation) {
                return ['success' => false, 'error' => 'Invitation not found'];
            }
            
            // Delete the invitation
            $deleteStmt = $this->pdo->prepare("DELETE FROM invitations WHERE id = ?");
            $deleteStmt->execute([$invitationId]);
            
            if ($deleteStmt->rowCount() > 0) {
                return [
                    'success' => true, 
                    'message' => 'Invitation deleted successfully',
                    'deleted_invitation' => $invitation
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to delete invitation'];
            }
            
        } catch (Exception $e) {
            error_log("Invitation deletion error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to delete invitation'];
        }
    }
    
    /**
     * Admin approve invitation (for advisor upgrade requests)
     * 
     * @param int $invitationId Invitation ID to approve
     * @param int $approvedBy User ID of administrator approving
     * @return array Result with success status and message
     */
    public function adminApproveInvitation(int $invitationId, int $approvedBy): array {
        try {
            // Get invitation details first
            $stmt = $this->pdo->prepare("
                SELECT id, inviter_id, invitee_id, invitee_email, invitation_type, status, requested_permission_level
                FROM invitations 
                WHERE id = ?
            ");
            $stmt->execute([$invitationId]);
            $invitation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$invitation) {
                return ['success' => false, 'error' => 'Invitation not found'];
            }
            
            // Check if invitation can be approved
            if ($invitation['status'] !== 'client_approved') {
                return ['success' => false, 'error' => 'Invitation must be client approved before admin approval'];
            }
            
            // Update invitation status to approved
            $updateStmt = $this->pdo->prepare("
                UPDATE invitations 
                SET status = 'approved',
                    approved_at = NOW(),
                    approved_by = ?
                WHERE id = ?
            ");
            
            $updateStmt->execute([$approvedBy, $invitationId]);
            
            if ($updateStmt->rowCount() > 0) {
                return [
                    'success' => true, 
                    'message' => 'Invitation approved successfully',
                    'invitation_id' => $invitationId
                ];
            } else {
                return ['success' => false, 'error' => 'Failed to approve invitation'];
            }
            
        } catch (Exception $e) {
            error_log("Invitation approval error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to approve invitation'];
        }
    }
    
    /**
     * Get all invitations for admin management (with pagination)
     * 
     * @param array $filters Optional filters (status, type, search)
     * @param int $limit Results per page
     * @param int $offset Starting offset
     * @return array Results with invitations and total count
     */
    public function getAllInvitationsForAdmin(array $filters = [], int $limit = 50, int $offset = 0): array {
        try {
            $whereConditions = [];
            $params = [];
            
            // Base query with user details
            $sql = "
                SELECT 
                    i.*,
                    inviter.username as inviter_username,
                    inviter.email as inviter_email,
                    invitee.username as invitee_username,
                    i.invitee_email
                FROM invitations i
                LEFT JOIN users inviter ON i.inviter_id = inviter.id
                LEFT JOIN users invitee ON i.invitee_id = invitee.id
            ";
            
            // Add filters
            if (!empty($filters['status'])) {
                $whereConditions[] = "i.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['type'])) {
                $whereConditions[] = "i.invitation_type = ?";
                $params[] = $filters['type'];
            }
            
            if (!empty($filters['search'])) {
                $whereConditions[] = "(i.invitee_email LIKE ? OR inviter.username LIKE ? OR invitee.username LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Add WHERE clause if conditions exist
            if (!empty($whereConditions)) {
                $sql .= " WHERE " . implode(" AND ", $whereConditions);
            }
            
            // Count total results
            $countSql = "SELECT COUNT(*) " . substr($sql, strpos($sql, "FROM"));
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalCount = $countStmt->fetchColumn();
            
            // Add ordering and pagination
            $sql .= " ORDER BY i.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'invitations' => $invitations,
                'total_count' => $totalCount,
                'limit' => $limit,
                'offset' => $offset
            ];
            
        } catch (Exception $e) {
            error_log("Get admin invitations error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to retrieve invitations'];
        }
    }
}
?>