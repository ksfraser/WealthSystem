<?php

namespace App\Services\Interfaces;

/**
 * Bank Import Service Interface
 * 
 * Defines bank data import and processing operations.
 * Handles various bank file formats and validation.
 */
interface BankImportServiceInterface
{
    /**
     * Detect file format from uploaded file
     */
    public function detectFormat(array $fileData): ?string;
    
    /**
     * Validate uploaded file
     */
    public function validateFile(array $fileData): array;
    
    /**
     * Process bank import file
     */
    public function processImport(array $fileData, int $userId): array;
    
    /**
     * Get supported file formats
     */
    public function getSupportedFormats(): array;
    
    /**
     * Parse bank statement data
     */
    public function parseStatement(string $filePath, string $format): array;
    
    /**
     * Map bank data to portfolio format
     */
    public function mapToPortfolio(array $bankData, int $userId): array;
    
    /**
     * Get import history for user
     */
    public function getImportHistory(int $userId): array;
}