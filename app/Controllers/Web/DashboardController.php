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
        // Get current user
        $user = $this->authService->getCurrentUser();
        
        if (!$user) {
            return $this->redirect('/login');
        }
        
        // Get user's portfolio data
        $portfolioData = $this->portfolioService->getDashboardData($user['id']);
        
        // Prepare view data
        $viewData = [
            'user' => $user,
            'portfolio' => $portfolioData,
            'navigation' => $this->navService->getNavigationMenu(),
            'breadcrumbs' => $this->navService->getBreadcrumbs('dashboard'),
            'quickActions' => $this->navService->getQuickActions()
        ];
        
        return $this->view('Dashboard/index', $viewData);
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