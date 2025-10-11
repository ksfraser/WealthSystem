<?php

namespace App\Repositories;

use App\Repositories\Interfaces\PortfolioRepositoryInterface;
use App\Core\Interfaces\ModelInterface;
use App\Models\Portfolio;

// Include existing Portfolio DAOs
require_once __DIR__ . '/../../web_ui/UserPortfolioDAO.php';
require_once __DIR__ . '/../../web_ui/PortfolioDAO.php';

/**
 * Portfolio Repository Implementation
 * 
 * Bridges between new MVC architecture and existing PortfolioDAO system.
 * Supports both user-specific and system-wide portfolio management.
 */
class PortfolioRepository implements PortfolioRepositoryInterface
{
    private ?\UserPortfolioDAO $userPortfolioDAO = null;
    private ?\PortfolioDAO $portfolioDAO = null;
    
    public function __construct()
    {
        try {
            // Initialize user portfolio DAO
            $csvPath = __DIR__ . '/../../Scripts and CSV Files/chatgpt_portfolio_update.csv';
            $this->userPortfolioDAO = new \UserPortfolioDAO($csvPath, 'user_portfolios', 'LegacyDatabaseConfig');
        } catch (\Exception $e) {
            // Fallback to regular PortfolioDAO
            try {
                $csvPath = __DIR__ . '/../../Scripts and CSV Files/chatgpt_portfolio_update.csv';
                $this->portfolioDAO = new \PortfolioDAO($csvPath, 'portfolio_data', 'LegacyDatabaseConfig');
            } catch (\Exception $e2) {
                // Both failed - will work with limited functionality
            }
        }
    }
    
    /**
     * Find record by ID
     */
    public function findById(int $id): ?ModelInterface
    {
        // Implementation depends on which DAO is available
        return null; // TODO: Implement when ID-based lookup is needed
    }
    
    /**
     * Find records by criteria
     */
    public function findBy(array $criteria): array
    {
        $portfolios = [];
        
        // If we have user-specific criteria, use UserPortfolioDAO
        if (isset($criteria['user_id']) && $this->userPortfolioDAO) {
            try {
                $portfolioData = $this->userPortfolioDAO->readUserPortfolio($criteria['user_id']);
                
                foreach ($portfolioData as $row) {
                    $portfolios[] = Portfolio::fromCsvArray($row);
                }
            } catch (\Exception $e) {
                // Log error but continue
            }
        } else if ($this->portfolioDAO) {
            try {
                $portfolioData = $this->portfolioDAO->readPortfolio();
                
                foreach ($portfolioData as $row) {
                    $portfolio = Portfolio::fromCsvArray($row);
                    
                    // Apply criteria filtering
                    $matches = true;
                    foreach ($criteria as $field => $value) {
                        if ($portfolio->{$field} !== $value) {
                            $matches = false;
                            break;
                        }
                    }
                    
                    if ($matches) {
                        $portfolios[] = $portfolio;
                    }
                }
            } catch (\Exception $e) {
                // Log error but continue
            }
        }
        
        return $portfolios;
    }
    
    /**
     * Find one record by criteria
     */
    public function findOneBy(array $criteria): ?ModelInterface
    {
        $results = $this->findBy($criteria);
        return $results[0] ?? null;
    }
    
    /**
     * Find all records
     */
    public function findAll(): array
    {
        return $this->findBy([]);
    }
    
    /**
     * Create new record
     */
    public function create(array $data): ModelInterface
    {
        $portfolio = new Portfolio($data);
        
        if (!$portfolio->isValid()) {
            throw new \Exception('Invalid portfolio data: ' . implode(', ', $portfolio->getErrors()));
        }
        
        // For now, return the model - actual persistence would need implementation
        return $portfolio;
    }
    
    /**
     * Update record by ID
     */
    public function update(int $id, array $data): bool
    {
        // TODO: Implement when needed
        return false;
    }
    
    /**
     * Delete record by ID
     */
    public function delete(int $id): bool
    {
        // TODO: Implement when needed
        return false;
    }
    
    /**
     * Count records
     */
    public function count(array $criteria = []): int
    {
        return count($this->findBy($criteria));
    }
    
    // PortfolioRepositoryInterface specific methods
    
    /**
     * Get user's current portfolio
     */
    public function getCurrentPortfolio(int $userId): ?object
    {
        if (!$this->userPortfolioDAO) {
            return null;
        }
        
        try {
            $portfolioData = $this->userPortfolioDAO->readUserPortfolio($userId);
            return (object) ['holdings' => $portfolioData, 'user_id' => $userId];
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Get portfolio performance history
     */
    public function getPerformanceHistory(int $userId, ?string $startDate = null, ?string $endDate = null): array
    {
        if (!$this->userPortfolioDAO) {
            return [];
        }
        
        try {
            return $this->userPortfolioDAO->getUserPortfolioHistory($userId, 30);
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get portfolio statistics
     */
    public function getStatistics(int $userId): array
    {
        $statistics = [
            'total_value' => 0.0,
            'total_cost' => 0.0,
            'total_gain_loss' => 0.0,
            'total_gain_loss_percent' => 0.0,
            'holdings_count' => 0,
            'profitable_positions' => 0
        ];
        
        try {
            $portfolios = $this->findBy(['user_id' => $userId]);
            
            $statistics['holdings_count'] = count($portfolios);
            
            foreach ($portfolios as $portfolio) {
                $statistics['total_value'] += $portfolio->getMarketValue();
                $statistics['total_cost'] += $portfolio->getBookCost();
                $statistics['total_gain_loss'] += $portfolio->getGainLoss();
                
                if ($portfolio->isProfitable()) {
                    $statistics['profitable_positions']++;
                }
            }
            
            if ($statistics['total_cost'] > 0) {
                $statistics['total_gain_loss_percent'] = 
                    (($statistics['total_value'] - $statistics['total_cost']) / $statistics['total_cost']) * 100;
            }
        } catch (\Exception $e) {
            // Return default statistics on error
        }
        
        return $statistics;
    }
    
    /**
     * Update portfolio value
     */
    public function updateValue(int $portfolioId, float $value): bool
    {
        // TODO: Implement when ID-based updates are needed
        return false;
    }
    
    /**
     * Get current positions for user
     */
    public function getCurrentPositions(int $userId): array
    {
        return $this->findBy(['user_id' => $userId]);
    }
    
    /**
     * Calculate portfolio metrics
     */
    public function calculateMetrics(int $userId): array
    {
        $positions = $this->getCurrentPositions($userId);
        $statistics = $this->getStatistics($userId);
        
        $metrics = [
            'diversification_score' => 0,
            'risk_score' => 0,
            'concentration_risk' => 0,
            'top_holdings' => [],
            'sector_allocation' => []
        ];
        
        if (empty($positions)) {
            return $metrics;
        }
        
        // Calculate diversification score (based on number of holdings)
        $holdings_count = count($positions);
        $metrics['diversification_score'] = min(100, ($holdings_count / 20) * 100);
        
        // Find top holdings by market value
        usort($positions, function($a, $b) {
            return $b->getMarketValue() <=> $a->getMarketValue();
        });
        
        $totalValue = $statistics['total_value'];
        $topHoldings = [];
        $concentrationRisk = 0;
        
        foreach (array_slice($positions, 0, 5) as $position) {
            $weight = $totalValue > 0 ? ($position->getMarketValue() / $totalValue) * 100 : 0;
            
            $topHoldings[] = [
                'symbol' => $position->getSymbol(),
                'value' => $position->getMarketValue(),
                'weight_percent' => $weight
            ];
            
            // Concentration risk increases with large single positions
            if ($weight > 20) {
                $concentrationRisk += ($weight - 20);
            }
        }
        
        $metrics['top_holdings'] = $topHoldings;
        $metrics['concentration_risk'] = min(100, $concentrationRisk);
        
        // Risk score based on concentration and volatility proxies
        $metrics['risk_score'] = min(100, $metrics['concentration_risk'] + 
                                   (100 - $metrics['diversification_score']));
        
        return $metrics;
    }
}