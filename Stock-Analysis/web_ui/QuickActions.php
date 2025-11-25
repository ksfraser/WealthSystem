<?php
/**
 * QuickActions - Generates navigation buttons for all web UI pages
 */
class QuickActions {
    private static $actions = [
        ['label' => 'Dashboard', 'href' => 'index.php'],
        ['label' => 'View Portfolios', 'href' => 'portfolios.php'],
        ['label' => 'Trade History', 'href' => 'trades.php'],
        ['label' => 'Analytics', 'href' => 'analytics.php'],
        ['label' => 'Database Manager', 'href' => 'database.php'],
    ['label' => 'Automation', 'href' => 'automation.php'],
    ['label' => 'Automation Monitoring', 'href' => 'automation_monitoring.php'],
    ['label' => 'Automation Config', 'href' => 'automation_config.php'],
        ['label' => 'System Status', 'href' => 'system_status.php'],
    ['label' => 'Admin Brokerages', 'href' => 'admin_brokerages.php'],
    ['label' => 'Admin Account Types', 'href' => 'admin_account_types.php'],
    ['label' => 'Bank Import', 'href' => 'bank_import.php'],
    ['label' => 'User Management', 'href' => 'admin_users.php'],
    ];

    public static function render($extraClass = '') {
        echo '<div class="card"><h3>Quick Actions</h3>';
        foreach (self::$actions as $action) {
            echo '<a href="' . htmlspecialchars($action['href']) . '" class="btn ' . htmlspecialchars($extraClass) . '">' . htmlspecialchars($action['label']) . '</a>';
        }
        echo '</div>';
    }
}
