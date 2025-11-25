<?php
/**
 * Delete Transaction API Endpoint
 *
 * Handles the deletion of a transaction via a POST request.
 * Expects 'id' to be provided in the POST body.
 */

require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../InvestGLDAO.php';
require_once __DIR__ . '/../NavigationService.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// Initialize services
$navService = new NavigationService();
$investGLDAO = new InvestGLDAO($navService->getPDO());

// Get current user
$currentUser = $navService->getCurrentUser();
if (!$currentUser) {
    $response['message'] = 'Authentication required.';
    http_response_code(401);
    echo json_encode($response);
    exit;
}
$userId = $currentUser['id'];

$transactionId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($transactionId <= 0) {
    $response['message'] = 'Invalid transaction ID.';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

try {
    if ($investGLDAO->deleteTransaction($transactionId, $userId)) {
        $response['success'] = true;
        $response['message'] = 'Transaction deleted successfully.';
    } else {
        $response['message'] = 'Failed to delete transaction. It may not exist or you may not have permission.';
        http_response_code(403);
    }
} catch (Exception $e) {
    $response['message'] = 'An error occurred during deletion: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
