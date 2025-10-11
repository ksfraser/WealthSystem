<?php

namespace App\Repositories\Interfaces;

use App\Core\Interfaces\RepositoryInterface;

/**
 * Bank Account Repository Interface
 * 
 * Defines contract for bank account data access.
 * Follows Interface Segregation Principle (ISP) and Dependency Inversion Principle (DIP).
 */
interface BankAccountRepositoryInterface extends RepositoryInterface 
{
    /**
     * Find bank accounts accessible to a user
     */
    public function getUserAccessibleAccounts(int $userId): array;
    
    /**
     * Find bank account by ID with user access check
     */
    public function findByIdWithUserAccess(int $accountId, int $userId): ?object;
    
    /**
     * Create bank account if it doesn't exist
     */
    public function createIfNotExists(array $data): object;
    
    /**
     * Find by bank name and account number
     */
    public function findByBankAndAccount(string $bankName, string $accountNumber): ?object;
}