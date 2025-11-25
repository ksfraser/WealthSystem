<?php
/**
 * SystemStatusService - Single Responsibility: System Status Information
 * 
 * Handles all system status checks and reporting for administrators
 */
class SystemStatusService {
    
    /**
     * Check if database connectivity is available
     * @return array Status information with connected flag and message
     */
    public function getDatabaseStatus(): array {
        try {
            // Test actual database connectivity
            require_once 'UserAuthDAO.php';
            $testAuth = new UserAuthDAO();
            
            // Try to perform a simple database operation
            $testAuth->isLoggedIn(); // This will trigger DB connection
            
            return [
                'connected' => true,
                'message' => '🟢 Database Available - Full functionality enabled',
                'details' => 'All database operations are working normally'
            ];
            
        } catch (Exception $e) {
            // Database is not available
            return [
                'connected' => false,
                'message' => '🔴 Database Unavailable - Using fallback authentication',
                'details' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create system status card for admin users only
     * @param object $authService Authentication service to check admin status
     * @return object|null System status card or null for non-admins
     */
    public function createSystemStatusCard($authService) {
        // Only show system status to administrators
        if (!$authService->isAdmin()) {
            return null;
        }
        
        $user = $authService->getCurrentUser();
        $accessLevel = 'Administrator';
        $username = $user && isset($user['username']) ? $user['username'] : 'Admin User';
        
        // Get actual database status
        $dbStatus = $this->getDatabaseStatus();
        $authStatus = $dbStatus['connected'] ? '✅ Database Authentication Active' : '⚠️ Fallback Authentication Active';
        
        $content = '<strong>Database Status:</strong> ' . $dbStatus['message'] . '<br>
                   <strong>Authentication Status:</strong> ' . $authStatus . '<br>
                   <strong>Current Admin:</strong> ' . htmlspecialchars($username) . '<br>
                   <strong>Access Level:</strong> ' . $accessLevel . '<br>
                   <strong>System Mode:</strong> ' . ($dbStatus['connected'] ? 'Full Functionality' : 'Limited Mode (Core features only)') . '<br>
                   <strong>User Management:</strong> ' . ($dbStatus['connected'] ? 'Full user system with roles' : 'Demo user authentication') . '';
        
        // Use appropriate card type based on database status
        $cardType = $dbStatus['connected'] ? 'success' : 'warning';
        
        return \Ksfraser\UIRenderer\Factories\UiFactory::createCard(
            '⚙️ System Status (Admin)',
            $content,
            $cardType,
            '⚙️'
        );
    }
    
    /**
     * Get a simple user-friendly system info card (non-sensitive information)
     * @param object $authService Authentication service
     * @return object Simple system info for regular users
     */
    public function createUserInfoCard($authService) {
        $user = $authService->getCurrentUser();
        $accessLevel = $authService->isAdmin() ? 'Administrator' : 'User';
        $username = $user && isset($user['username']) ? $user['username'] : 'Guest User';
        
        $content = '<strong>Welcome:</strong> ' . htmlspecialchars($username) . '<br>
                   <strong>Access Level:</strong> ' . $accessLevel . '<br>
                   <strong>Trading Features:</strong> Portfolio management, trade tracking, and performance analytics<br>
                   <strong>Status:</strong> System operational';
        
        return \Ksfraser\UIRenderer\Factories\UiFactory::createCard(
            '👤 Account Information',
            $content,
            'info',
            '👤'
        );
    }
}
?>