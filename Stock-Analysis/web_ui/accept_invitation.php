<?php
/**
 * Accept Invitation Handler
 * 
 * Processes invitation acceptance via token
 */

require_once __DIR__ . '/UserAuthDAO.php';
require_once __DIR__ . '/InvitationService.php';

$auth = new UserAuthDAO();
$invitationService = new InvitationService();

// Require login
$auth->requireLogin();

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header("Location: profile.php?error=" . urlencode("Invalid invitation token"));
    exit;
}

try {
    $result = $invitationService->acceptInvitation($token);
    
    if ($result['success']) {
        header("Location: profile.php?success=" . urlencode($result['message']));
    } else {
        header("Location: profile.php?error=" . urlencode($result['message']));
    }
    
} catch (Exception $e) {
    error_log("Accept invitation error: " . $e->getMessage());
    header("Location: profile.php?error=" . urlencode("Failed to accept invitation"));
}

exit;
?>