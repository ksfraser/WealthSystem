<?php
/**
 * Database Connectivity Notice Component
 * 
 * Handles database connectivity warnings following SRP:
 * - Single responsibility: Display database issues to appropriate users
 * - Encapsulates admin check logic
 * - Returns appropriate response based on user permissions
 */

require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';
use Ksfraser\UIRenderer\Factories\UiFactory;
use Ksfraser\UIRenderer\Contracts\ComponentInterface;

class DatabaseConnectivityNotice {
    private $authService;
    private $showToAdminOnly;
    
    /**
     * @param object $authService - Authentication service with isAdmin() and hasAuthError() methods
     * @param bool $showToAdminOnly - Whether to restrict display to admins only (default: true)
     */
    public function __construct($authService, $showToAdminOnly = true) {
        $this->authService = $authService;
        $this->showToAdminOnly = $showToAdminOnly;
    }
    
    /**
     * Get database connectivity notice component
     * 
     * @return ComponentInterface|null Returns component for admins with DB issues, null otherwise
     */
    public function getNoticeComponent() {
        // Check if we should show the notice
        if (!$this->shouldShowNotice()) {
            return null;
        }
        
        // Determine message based on error severity
        if ($this->authService->hasAuthError()) {
            return $this->createDatabaseErrorNotice();
        }
        
        // If database is working but we want to show status anyway
        return $this->createDatabaseStatusNotice();
    }
    
    /**
     * Determine if notice should be shown based on user permissions and database status
     */
    private function shouldShowNotice(): bool {
        // If restricted to admin only, check admin status
        if ($this->showToAdminOnly && !$this->authService->isAdmin()) {
            return false;
        }
        
        // Show if there's an actual database error
        if ($this->authService->hasAuthError()) {
            return true;
        }
        
        // Don't show if database is working fine
        return false;
    }
    
    /**
     * Create notice for database connectivity problems
     */
    private function createDatabaseErrorNotice() {
        return UiFactory::createCard(
            '⚠️ Database Connectivity Notice',
            'There may be database connectivity issues. Some features may be limited. Please check the system status for more information.',
            'warning',
            '⚠️',
            [
                ['url' => 'system_status.php', 'label' => 'System Status', 'class' => 'btn btn-warning', 'icon' => '📊'],
                ['url' => 'test_db_simple.php', 'label' => 'Test Database', 'class' => 'btn', 'icon' => '🗄️']
            ]
        );
    }
    
    /**
     * Create notice for database status (when showing to all users)
     */
    private function createDatabaseStatusNotice() {
        return UiFactory::createCard(
            '✅ Database Status',
            'Database connectivity is operational. All features are available.',
            'success',
            '✅',
            [
                ['url' => 'system_status.php', 'label' => 'View Status', 'class' => 'btn btn-success', 'icon' => '📊']
            ]
        );
    }
    
    /**
     * Get HTML string directly (alternative to component)
     */
    public function getNoticeHtml(): string {
        $component = $this->getNoticeComponent();
        return $component ? $component->toHtml() : '';
    }
    
    /**
     * Check if notice would be displayed (useful for layout decisions)
     */
    public function hasNotice(): bool {
        return $this->shouldShowNotice();
    }
    
    /**
     * Static factory method for quick usage
     */
    public static function createForUser($authService, $adminOnly = true) {
        return new self($authService, $adminOnly);
    }
    
    /**
     * Create notice that shows to all users (not just admins)
     */
    public static function createForAllUsers($authService) {
        return new self($authService, false);
    }
}

/**
 * Empty Component - Follows Null Object pattern
 * 
 * Used when no notice should be displayed but we need to return a ComponentInterface
 */
class EmptyComponent implements ComponentInterface {
    public function toHtml(): string {
        return '';
    }
}

// Usage examples:
/*
// Admin-only notice (default behavior)
$notice = DatabaseConnectivityNotice::createForUser($authService);
$component = $notice->getNoticeComponent(); // null for non-admins

// Notice for all users
$notice = DatabaseConnectivityNotice::createForAllUsers($authService);
$component = $notice->getNoticeComponent();

// Check if notice will be shown
if ($notice->hasNotice()) {
    // Adjust layout accordingly
}

// Get HTML directly
$html = $notice->getNoticeHtml(); // Returns empty string if no notice
*/
?>