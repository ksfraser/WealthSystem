<?php
/**
 * Navigation Item Provider Interface
 * Providers return navigation items for specific feature areas
 */
interface NavigationItemProvider {
    /**
     * Get menu items for navigation header
     * @return MenuItem[]
     */
    public function getMenuItems(): array;
    
    /**
     * Get dashboard cards
     * @return DashboardCard[]
     */
    public function getDashboardCards(): array;
}
