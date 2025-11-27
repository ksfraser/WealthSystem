<?php

namespace App\DataAccess\Adapters;

use App\DataAccess\Interfaces\PortfolioDataSourceInterface;

// Include existing DAO
require_once __DIR__ . '/../../../web_ui/MicroCapPortfolioDAO.php';

/**
 * Adapter for MicroCapPortfolioDAO
 * 
 * Wraps the existing MicroCapPortfolioDAO class to implement PortfolioDataSourceInterface.
 */
class MicroCapPortfolioDAOAdapter implements PortfolioDataSourceInterface
{
    private ?\MicroCapPortfolioDAO $dao;
    
    /**
     * Constructor
     * 
     * @param \MicroCapPortfolioDAO|null $dao Optional DAO instance (for testing)
     * @param string|null $csvPath Path to CSV file
     */
    public function __construct(?\MicroCapPortfolioDAO $dao = null, ?string $csvPath = null)
    {
        if ($dao !== null) {
            $this->dao = $dao;
        } else {
            try {
                $path = $csvPath ?? __DIR__ . '/../../../Scripts and CSV Files/chatgpt_portfolio_update.csv';
                $this->dao = new \MicroCapPortfolioDAO($path);
            } catch (\Exception $e) {
                $this->dao = null;
                error_log('MicroCapPortfolioDAO initialization failed: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function readPortfolio(?int $userId = null): array
    {
        if ($this->dao === null) {
            return [];
        }
        
        try {
            return $this->dao->readPortfolio();
        } catch (\Exception $e) {
            error_log("Failed to read micro-cap portfolio: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function writePortfolio(array $portfolioData, ?int $userId = null): bool
    {
        if ($this->dao === null) {
            return false;
        }
        
        try {
            return $this->dao->writePortfolio($portfolioData);
        } catch (\Exception $e) {
            error_log("Failed to write micro-cap portfolio: " . $e->getMessage());
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
