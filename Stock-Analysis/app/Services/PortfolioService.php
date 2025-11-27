<?php

namespace App\Services;

use App\Services\Interfaces\PortfolioServiceInterface;
use App\Services\Interfaces\MarketDataServiceInterface;
use App\Repositories\Interfaces\PortfolioRepositoryInterface;
use App\DataAccess\Interfaces\PortfolioDataSourceInterface;
use App\DataAccess\Adapters\UserPortfolioDAOAdapter;
use App\DataAccess\Adapters\MicroCapPortfolioDAOAdapter;

/**
 * Portfolio Service Implementation
 * 
 * Provides portfolio management and analytics with dependency injection.
 * Uses data source interfaces for flexible data access.
 */
class PortfolioService implements PortfolioServiceInterface
{
    private PortfolioRepositoryInterface $portfolioRepository;
    private MarketDataServiceInterface $marketDataService;
    private PortfolioDataSourceInterface $userPortfolioDataSource;
    private PortfolioDataSourceInterface $microCapDataSource;
    
    /**
     * Constructor with dependency injection
     * 
     * @param PortfolioRepositoryInterface $portfolioRepository Repository for portfolio persistence
     * @param MarketDataServiceInterface $marketDataService Service for market data
     * @param PortfolioDataSourceInterface|null $userPortfolioDataSource User portfolio data source (optional)
     * @param PortfolioDataSourceInterface|null $microCapDataSource Micro-cap portfolio data source (optional)
     */
    public function __construct(
        PortfolioRepositoryInterface $portfolioRepository,
        MarketDataServiceInterface $marketDataService,
        ?PortfolioDataSourceInterface $userPortfolioDataSource = null,
        ?PortfolioDataSourceInterface $microCapDataSource = null
    ) {
        $this->portfolioRepository = $portfolioRepository;
        $this->marketDataService = $marketDataService;
        $this->userPortfolioDataSource = $userPortfolioDataSource ?? new UserPortfolioDAOAdapter();
        $this->microCapDataSource = $microCapDataSource ?? new MicroCapPortfolioDAOAdapter();
    }
    
    /**
     * Get comprehensive dashboard data for user
     */
    public function getDashboardData(int $userId): array
    {
        // Get actual portfolio data from existing DAOs
        $portfolioData = $this->getActualPortfolioData($userId);
        $holdings = $this->getActualHoldings($userId);
        $marketData = $this->marketDataService->getMarketSummary();
        
        $dashboardData = [
            'user_id' => $userId,
            'total_value' => $portfolioData['total_value'],
            'daily_change' => $portfolioData['daily_change'],
            'total_return' => $portfolioData['total_return'],
            'stock_count' => count($holdings),
            'holdings' => $holdings,
            'marketData' => $marketData,
            'recentActivity' => $this->getRecentActivity($userId),
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
    
    /**
     * Get actual portfolio data from existing systems
     */
    private function getActualPortfolioData(int $userId): array
    {
        try {
            // Try to get from micro-cap data source first
            if ($this->microCapDataSource->isAvailable()) {
                $portfolioRows = $this->microCapDataSource->readPortfolio();
                if (!empty($portfolioRows)) {
                    return $this->calculatePortfolioMetrics($portfolioRows);
                }
            }
            
            // Fallback to user portfolio data source
            if ($this->userPortfolioDataSource->isAvailable()) {
                $portfolio = $this->userPortfolioDataSource->readPortfolio($userId);
                if (!empty($portfolio)) {
                    return $this->calculatePortfolioMetrics($portfolio);
                }
            }
            
            // Return default values if no data
            return [
                'total_value' => 0,
                'daily_change' => 0,
                'total_return' => 0
            ];
            
        } catch (\Exception $e) {
            error_log("Failed to get actual portfolio data: " . $e->getMessage());
            return [
                'total_value' => 0,
                'daily_change' => 0,
                'total_return' => 0
            ];
        }
    }
    
    /**
     * Get actual holdings with current market values
     */
    private function getActualHoldings(int $userId): array
    {
        try {
            // Get holdings from data source
            $holdings = [];
            
            if ($this->microCapDataSource->isAvailable()) {
                $portfolioRows = $this->microCapDataSource->readPortfolio();
                if (!empty($portfolioRows)) {
                    $holdings = $this->formatHoldings($portfolioRows);
                }
            }
            
            // Get current prices for all symbols
            if (!empty($holdings)) {
                $symbols = array_column($holdings, 'symbol');
                $currentPrices = $this->marketDataService->getCurrentPrices($symbols);
                
                // Update holdings with current prices
                foreach ($holdings as &$holding) {
                    $symbol = $holding['symbol'];
                    if (isset($currentPrices[$symbol])) {
                        $priceData = $currentPrices[$symbol];
                        $holding['current_price'] = $priceData['price'];
                        $holding['day_change'] = $priceData['change'];
                        $holding['market_value'] = $holding['shares'] * $priceData['price'];
                        $holding['total_return'] = $holding['market_value'] - ($holding['shares'] * $holding['buy_price']);
                    }
                }
            }
            
            return $holdings;
            
        } catch (\Exception $e) {
            error_log("Failed to get actual holdings: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Format portfolio rows into holdings array
     */
    private function formatHoldings(array $portfolioRows): array
    {
        $holdings = [];
        
        foreach ($portfolioRows as $row) {
            // Skip TOTAL rows and ensure we have required fields
            if (isset($row['Ticker']) && strtoupper($row['Ticker']) !== 'TOTAL' && !empty($row['Ticker'])) {
                $holdings[] = [
                    'symbol' => strtoupper(trim($row['Ticker'])),
                    'company_name' => $row['Company'] ?? $row['Ticker'],
                    'shares' => (float)($row['Shares'] ?? $row['Position'] ?? 0),
                    'buy_price' => (float)($row['Buy Price'] ?? $row['Cost Basis'] ?? 0),
                    'current_price' => (float)($row['Current Price'] ?? $row['Last Price'] ?? 0),
                    'market_value' => (float)($row['Market Value'] ?? 0),
                    'day_change' => 0, // Will be updated with live data
                    'total_return' => (float)($row['P&L'] ?? $row['Gain/Loss'] ?? 0)
                ];
            }
        }
        
        return $holdings;
    }
    
    /**
     * Calculate portfolio metrics from raw data
     */
    private function calculatePortfolioMetrics(array $portfolioRows): array
    {
        $totalValue = 0;
        $totalCost = 0;
        
        foreach ($portfolioRows as $row) {
            if (isset($row['Market Value']) && strtoupper($row['Ticker'] ?? '') !== 'TOTAL') {
                $marketValue = (float)str_replace(['$', ','], '', $row['Market Value']);
                $totalValue += $marketValue;
                
                if (isset($row['Cost Basis'])) {
                    $costBasis = (float)str_replace(['$', ','], '', $row['Cost Basis']);
                    $totalCost += $costBasis;
                }
            }
        }
        
        $totalReturn = $totalValue - $totalCost;
        
        return [
            'total_value' => $totalValue,
            'daily_change' => 0, // This would need previous day's value
            'total_return' => $totalReturn
        ];
    }
    
    /**
     * Get recent portfolio activity
     */
    private function getRecentActivity(int $userId): array
    {
        // This would integrate with trade log systems
        // For now, return empty array
        return [];
    }
}