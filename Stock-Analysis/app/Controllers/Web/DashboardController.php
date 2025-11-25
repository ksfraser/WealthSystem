<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthenticationService;
use App\Services\PortfolioService;
use App\Services\NavigationService;

/**
 * Dashboard Controller
 * 
 * Handles dashboard display and user portfolio overview.
 * Follows Single Responsibility Principle (SRP) - dashboard-related actions only.
 */
class DashboardController extends BaseController 
{
    private AuthenticationService $authService;
    private PortfolioService $portfolioService;
    private NavigationService $navService;
    
    public function __construct(
        AuthenticationService $authService,
        PortfolioService $portfolioService,
        NavigationService $navService
    ) {
        $this->authService = $authService;
        $this->portfolioService = $portfolioService;
        $this->navService = $navService;
    }
    
    /**
     * Display main dashboard
     */
    protected function process(Request $request): Response 
    {
        return $this->index($request);
    }
    
    /**
     * Dashboard index page
     */
    public function index(Request $request): Response 
    {
        // For demo purposes, use a test user (bypassing auth for now)
        $user = ['id' => 1, 'username' => 'demo_user'];
        
        try {
            // Get actual portfolio data
            $portfolioData = $this->portfolioService->getDashboardData($user['id']);
            $navigation = $this->navService->getNavigationMenu();
            
            // Prepare view data with actual portfolio information
            $viewData = [
                'user' => $user,
                'pageTitle' => 'Portfolio Dashboard',
                'portfolioData' => $portfolioData,
                'holdings' => $portfolioData['holdings'] ?? [],
                'marketData' => $portfolioData['marketData'] ?? [],
                'recentActivity' => $portfolioData['recentActivity'] ?? [],
                'navigation' => $navigation
            ];
            
            return $this->view('Dashboard/index', $viewData);
            
        } catch (\Exception $e) {
            // Handle errors gracefully and show debug info
            error_log("Dashboard error: " . $e->getMessage());
            
            $viewData = [
                'user' => $user,
                'pageTitle' => 'Portfolio Dashboard - Error',
                'portfolioData' => [
                    'total_value' => 0,
                    'daily_change' => 0,
                    'total_return' => 0,
                    'stock_count' => 0
                ],
                'holdings' => [],
                'marketData' => [],
                'recentActivity' => [],
                'navigation' => $this->navService->getNavigationMenu(),
                'error_message' => 'Unable to load portfolio data: ' . $e->getMessage(),
                'debug_info' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];
            
            return $this->view('Dashboard/index', $viewData);
        }
    }
    
    /**
     * Get quick actions for dashboard
     */
    private function getQuickActions(): array 
    {
        return [
            [
                'title' => 'Import Bank Data',
                'url' => '/bank-import',
                'icon' => 'upload',
                'description' => 'Import transaction data from your bank'
            ],
            [
                'title' => 'View Portfolios',
                'url' => '/portfolios',
                'icon' => 'chart',
                'description' => 'View and manage your investment portfolios'
            ],
            [
                'title' => 'Stock Analysis',
                'url' => '/stocks/analysis',
                'icon' => 'analytics',
                'description' => 'Analyze stock performance and trends'
            ],
            [
                'title' => 'Bank Accounts',
                'url' => '/user/bank-accounts',
                'icon' => 'bank',
                'description' => 'Manage your connected bank accounts'
            ]
        ];
    }
    
    /**
     * Pre-processing: Ensure user is authenticated
     */
    protected function before(Request $request): void 
    {
        if (!$this->authService->isAuthenticated()) {
            // This will be handled in process() method
            return;
        }
        
        // Set security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
    }
}