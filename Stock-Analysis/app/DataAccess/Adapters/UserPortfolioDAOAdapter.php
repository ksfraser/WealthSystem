<?php

namespace App\DataAccess\Adapters;

use App\DataAccess\Interfaces\PortfolioDataSourceInterface;

// Include existing DAO
require_once __DIR__ . '/../../../web_ui/UserPortfolioDAO.php';

/**
 * Adapter for UserPortfolioDAO
 * 
 * Wraps the existing UserPortfolioDAO class to implement PortfolioDataSourceInterface.
 */
class UserPortfolioDAOAdapter implements PortfolioDataSourceInterface
{
    private ?\UserPortfolioDAO $dao;
    
    /**
     * Constructor
     * 
     * @param \UserPortfolioDAO|null $dao Optional DAO instance (for testing)
     * @param string|null $csvPath Path to CSV file
     */
    public function __construct(?\UserPortfolioDAO $dao = null, ?string $csvPath = null)
    {
        if ($dao !== null) {
            $this->dao = $dao;
        } else {
            try {
                $path = $csvPath ?? __DIR__ . '/../../../Scripts and CSV Files/chatgpt_portfolio_update.csv';
                $this->dao = new \UserPortfolioDAO($path, 'user_portfolios', 'LegacyDatabaseConfig');
            } catch (\Exception $e) {
                $this->dao = null;
                error_log('UserPortfolioDAO initialization failed: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function readPortfolio(?int $userId = null): array
    {
        if ($this->dao === null || $userId === null) {
            return [];
        }
        
        try {
            return $this->dao->readUserPortfolio($userId);
        } catch (\Exception $e) {
            error_log("Failed to read user portfolio for user {$userId}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function writePortfolio(array $portfolioData, ?int $userId = null): bool
    {
        if ($this->dao === null || $userId === null) {
            return false;
        }
        
        try {
            return $this->dao->writeUserPortfolio($userId, $portfolioData);
        } catch (\Exception $e) {
            error_log("Failed to write user portfolio for user {$userId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return $this->dao !== null;
    }
}
