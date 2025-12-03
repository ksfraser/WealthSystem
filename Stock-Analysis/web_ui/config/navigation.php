<?php
/**
 * Navigation Configuration
 * Controls behavior of navigation menus and dashboard cards
 */

return [
    // Display mode for restricted items
    // 'hidden' - Don't show items user doesn't have access to (default)
    // 'greyed_out' - Show items but disable them (like FrontAccounting)
    'restricted_items_mode' => 'greyed_out', // Options: 'hidden', 'greyed_out'
    
    // Cache navigation items (improves performance)
    'cache_enabled' => false,
    'cache_duration' => 3600, // seconds
    
    // Show icons in navigation
    'show_icons' => true,
    
    // Tooltip behavior for greyed out items
    'show_restriction_tooltip' => true,
    'restriction_tooltip_text' => 'Requires {level} access',
    
    // Admin detection
    'admin_roles' => ['admin', 'administrator', 'super_admin'],
    
    // Advisor detection
    'advisor_roles' => ['advisor', 'financial_advisor'],
];
