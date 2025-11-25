<?php
/**
 * Finance Integration for Web UI
 * 
 * Integration layer to connect the SOLID Finance package with the existing web_ui system.
 * This bridges the new Finance architecture with the current authentication and navigation.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UserAuthDAO.php';
require_once __DIR__ . '/../DatabaseConfig.php';

use Ksfraser\Finance\DI\Container;

class FinanceIntegration
{
    private $container;
    private $userAuth;
    private $config;

    public function __construct(?UserAuthDAO $userAuth = null)
    {
        $this->userAuth = $userAuth ?? new UserAuthDAO();
        $this->config = $this->loadConfig();
        $this->container = new Container($this->config);
    }

    /**
     * Get the finance container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Get stock controller with authentication check
     */
    public function getStockController()
    {
        // Ensure user is authenticated
        if (!$this->userAuth->isLoggedIn()) {
            throw new \Exception('Authentication required for finance operations');
        }

        return $this->container->get('stock_controller');
    }

    /**
     * Get market overview for dashboard
     */
    public function getMarketOverviewForDashboard(): array
    {
        try {
            $controller = $this->getStockController();
            $result = $controller->getMarketOverview();
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'data' => $result['data']
                ];
            }
            
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Failed to get market overview'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update stock price with admin check
     */
    public function updateStockPrice(string $symbol): array
    {
        try {
            // Admin-only operation
            if (!$this->userAuth->isAdmin()) {
                return [
                    'success' => false,
                    'error' => 'Admin privileges required for stock updates'
                ];
            }

            $controller = $this->getStockController();
            return $controller->updateStock($symbol);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get stock analysis for any authenticated user
     */
    public function getStockAnalysis(string $symbol): array
    {
        try {
            $controller = $this->getStockController();
            return $controller->getAnalysis($symbol);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Bulk update stocks (admin only)
     */
    public function bulkUpdateStocks(array $symbols): array
    {
        try {
            if (!$this->userAuth->isAdmin()) {
                return [
                    'success' => false,
                    'error' => 'Admin privileges required for bulk updates'
                ];
            }

            $controller = $this->getStockController();
            return $controller->bulkUpdate($symbols);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get stock history
     */
    public function getStockHistory(string $symbol, int $days = 30): array
    {
        try {
            $controller = $this->getStockController();
            return $controller->getHistory($symbol, $days);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Load configuration with fallbacks
     */
    private function loadConfig(): array
    {
        try {
            // Use the existing DatabaseConfig system
            return DatabaseConfig::getFinanceConfig();
        } catch (\Exception $e) {
            error_log('Finance configuration error: ' . $e->getMessage());
            
            // Fallback to basic configuration
            return [
                'database' => [
                    'dsn' => 'mysql:host=localhost;dbname=stock_market;charset=utf8mb4',
                    'username' => 'root',
                    'password' => ''
                ],
                'alphavantage' => ['api_key' => ''],
                'openai' => ['api_key' => ''],
                'rate_limiting' => ['delay_between_requests' => 500000],
                'general' => ['max_retries' => 3, 'timeout' => 30]
            ];
        }
    }

    /**
     * Check if finance features are available
     */
    public function isAvailable(): bool
    {
        try {
            $this->container->get('database');
            return true;
        } catch (\Exception $e) {
            error_log('Finance integration not available: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get finance status for admin dashboard
     */
    public function getFinanceStatus(): array
    {
        $status = [
            'database' => false,
            'yahoo_finance' => false,
            'alpha_vantage' => false,
            'openai' => false,
            'last_check' => date('Y-m-d H:i:s')
        ];

        try {
            // Check database
            $this->container->get('database');
            $status['database'] = true;

            // Check Yahoo Finance
            $yahooSource = $this->container->get('yahoo_source');
            $status['yahoo_finance'] = $yahooSource->isAvailable();

            // Check Alpha Vantage
            $alphaSource = $this->container->get('alphavantage_source');
            $status['alpha_vantage'] = $alphaSource->isAvailable();

            // Check OpenAI
            $llmProvider = $this->container->get('llm_provider');
            $status['openai'] = $llmProvider ? $llmProvider->isAvailable() : false;

        } catch (\Exception $e) {
            error_log('Error checking finance status: ' . $e->getMessage());
        }

        return $status;
    }
}
