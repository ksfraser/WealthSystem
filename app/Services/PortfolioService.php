<?php

namespace App\Services;

use App\Services\Interfaces\PortfolioServiceInterface;
use App\Repositories\Interfaces\PortfolioRepositoryInterface;

// Include existing Portfolio DAOs for compatibility
require_once __DIR__ . '/../../web_ui/UserPortfolioDAO.php';
require_once __DIR__ . '/../../web_ui/PortfolioDAO.php';

/**
 * Portfolio Service Implementation
 * 
 * Provides portfolio management and analytics using existing DAO system.
 * Integrates with UserPortfolioDAO and PortfolioDAO for data access.
 */
class PortfolioService implements PortfolioServiceInterface
{
    private PortfolioRepositoryInterface $portfolioRepository;
    private ?\UserPortfolioDAO $userPortfolioDAO = null;
    
    public function __construct(PortfolioRepositoryInterface $portfolioRepository)
    {
        $this->portfolioRepository = $portfolioRepository;
        
        // Initialize UserPortfolioDAO for compatibility
        try {
            $csvPath = __DIR__ . '/../../Scripts and CSV Files/chatgpt_portfolio_update.csv';
            $this->userPortfolioDAO = new \UserPortfolioDAO($csvPath, 'user_portfolios', 'LegacyDatabaseConfig');
        } catch (\Exception $e) {
            // Will work with limited functionality
        }
    }
    
    /**
     * Get comprehensive dashboard data for user
     */
    public function getDashboardData(int $userId): array
    {
        $dashboardData = [
            'user_id' => $userId,
            'portfolio_summary' => $this->getSummary($userId),
            'performance' => $this->calculatePerformance($userId),
            'positions' => $this->getPositions($userId),
            'metrics' => $this->calculateMetrics($userId),
            'charts_data' => $this->getChartsData($userId),
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
        return $dashboardData;
    }
    
    /**
     * Calculate portfolio performance
     */
    public function calculatePerformance(int $userId): array
    {
        $performance = $this->portfolioRepository->getPerformanceHistory($userId);
        
        if (empty($performance)) {
            return [
                'total_return' => 0,
                'total_return_percent' => 0,
                'daily_return' => 0,
                'daily_return_percent' => 0
            ];
        }
        
        $latest = end($performance);
        $previous = count($performance) > 1 ? $performance[count($performance) - 2] : $latest;
        $initial = reset($performance);
        
        $currentValue = $latest['value'] ?? 0;
        $previousValue = $previous['value'] ?? $currentValue;
        $initialValue = $initial['value'] ?? $currentValue;
        
        return [
            'total_return' => $currentValue - $initialValue,
            'total_return_percent' => $initialValue > 0 ? (($currentValue - $initialValue) / $initialValue) * 100 : 0,
            'daily_return' => $currentValue - $previousValue,
            'daily_return_percent' => $previousValue > 0 ? (($currentValue - $previousValue) / $previousValue) * 100 : 0,
            'current_value' => $currentValue,
            'initial_value' => $initialValue
        ];
    }
    
    /**
     * Get current portfolio value
     */
    public function getCurrentValue(int $userId): float
    {
        $portfolio = $this->portfolioRepository->getCurrentPortfolio($userId);
        return $portfolio->value ?? 0.0;
    }
    
    /**
     * Get portfolio positions
     */
    public function getPositions(int $userId): array
    {
        return $this->portfolioRepository->getCurrentPositions($userId);
    }
    
    /**
     * Update portfolio with new transaction
     */
    public function addTransaction(int $userId, array $transactionData): bool
    {
        // Validate transaction data
        $requiredFields = ['amount', 'type', 'date'];
        foreach ($requiredFields as $field) {
            if (!isset($transactionData[$field])) {
                return false;
            }
        }
        
        // Add user ID to transaction
        $transactionData['user_id'] = $userId;
        $transactionData['created_at'] = date('Y-m-d H:i:s');
        
        // This would typically create a transaction record and update portfolio value
        // Placeholder implementation
        return true;
    }
    
    /**
     * Get performance charts data
     */
    public function getChartsData(int $userId, ?string $period = null): array
    {
        $startDate = null;
        $endDate = null;
        
        // Set date range based on period
        if ($period) {
            $endDate = date('Y-m-d');
            switch ($period) {
                case '1M':
                    $startDate = date('Y-m-d', strtotime('-1 month'));
                    break;
                case '3M':
                    $startDate = date('Y-m-d', strtotime('-3 months'));
                    break;
                case '6M':
                    $startDate = date('Y-m-d', strtotime('-6 months'));
                    break;
                case '1Y':
                    $startDate = date('Y-m-d', strtotime('-1 year'));
                    break;
                case 'ALL':
                default:
                    // No date restriction
                    break;
            }
        }
        
        $performanceData = $this->portfolioRepository->getPerformanceHistory($userId, $startDate, $endDate);
        
        // Format data for charts
        $chartData = [
            'labels' => [],
            'values' => [],
            'returns' => []
        ];
        
        foreach ($performanceData as $dataPoint) {
            $chartData['labels'][] = $dataPoint['date'] ?? '';
            $chartData['values'][] = $dataPoint['value'] ?? 0;
            $chartData['returns'][] = $dataPoint['return'] ?? 0;
        }
        
        return $chartData;
    }
    
    /**
     * Calculate portfolio metrics (Sharpe ratio, volatility, etc.)
     */
    public function calculateMetrics(int $userId): array
    {
        return $this->portfolioRepository->calculateMetrics($userId);
    }
    
    /**
     * Get portfolio summary
     */
    public function getSummary(int $userId): array
    {
        $statistics = $this->portfolioRepository->getStatistics($userId);
        $performance = $this->calculatePerformance($userId);
        
        return [
            'total_value' => $performance['current_value'],
            'total_return' => $performance['total_return'],
            'total_return_percent' => $performance['total_return_percent'],
            'positions_count' => count($this->getPositions($userId)),
            'last_updated' => date('Y-m-d H:i:s'),
            'statistics' => $statistics
        ];
    }
}