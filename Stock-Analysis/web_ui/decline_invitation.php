<?php
/**
 * Decline Invitation Handler
 * 
 * Processes invitation decline via token
 */

require_once __DIR__ . '/UserAuthDAO.php';
require_once __DIR__ . '/InvitationService.php';

$auth = new UserAuthDAO();
$invitationService = new InvitationService();

// Require login
try {
    $auth->requireLogin();
} catch (\App\Auth\LoginRequiredException $e) {
    $returnUrl = urlencode($_SERVER['REQUEST_URI'] ?? 'profile.php');
    header('Location: login.php?return_url=' . $returnUrl);
    exit;
}

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header("Location: profile.php?error=" . urlencode("Invalid invitation token"));
    exit;
}

try {
    $result = $invitationService->declineInvitation($token);
    
    if ($result['success']) {
        header("Location: profile.php?success=" . urlencode($result['message']));
    } else {
        header("Location: profile.php?error=" . urlencode($result['message']));
    }
    
} catch (Exception $e) {
    error_log("Decline invitation error: " . $e->getMessage());
    header("Location: profile.php?error=" . urlencode("Failed to decline invitation"));
}

exit;
?>