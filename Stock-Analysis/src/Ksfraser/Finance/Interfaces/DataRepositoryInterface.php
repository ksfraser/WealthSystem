<?php
/**
 * Data Repository Interface for Financial Data Persistence
 * 
 * Defines the contract for storing and retrieving financial data.
 * Follows the Dependency Inversion Principle by abstracting data access.
 */

namespace Ksfraser\Finance\Interfaces;

use DateTime;

interface DataRepositoryInterface
{
    /**
     * Save stock price data to persistent storage
     * 
     * @param array $data Normalized stock price data
     * @return bool True if saved successfully
     */
    public function saveStockPrice(array $data): bool;
    
    /**
     * Save financial statement data
     * 
     * @param array $data Financial statement data
     * @return bool True if saved successfully
     */
    public function saveFinancialStatement(array $data): bool;
    
    /**
     * Retrieve stock price data
     * 
     * @param string $symbol Stock symbol
     * @param DateTime|null $date Specific date or null for latest
     * @return array|null Stock price data or null if not found
     */
    public function getStockPrice(string $symbol, ?DateTime $date = null): ?array;
    
    /**
     * Retrieve company information
     * 
     * @param string $symbol Stock symbol
     * @return array|null Company data or null if not found
     */
    public function getCompany(string $symbol): ?array;
    
    /**
     * Get historical stock prices
     * 
     * @param string $symbol Stock symbol
     * @param DateTime $startDate Start date
     * @param DateTime $endDate End date
     * @return array Array of historical price data
     */
    public function getHistoricalPrices(string $symbol, DateTime $startDate, DateTime $endDate): array;
    
    /**
     * Execute a SQL query and return results
     * 
     * @param string $sql SQL query string
     * @param array $params Query parameters
     * @return array Query results
     */
    public function query(string $sql, array $params = []): array;
    
    /**
     * Execute a SQL statement (INSERT, UPDATE, DELETE)
     * 
     * @param string $sql SQL statement string
     * @param array $params Statement parameters
     * @return bool True if executed successfully
     */
    public function execute(string $sql, array $params = []): bool;
}
