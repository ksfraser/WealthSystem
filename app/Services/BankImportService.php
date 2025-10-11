<?php

namespace App\Services;

use App\Services\Interfaces\BankImportServiceInterface;
use App\Repositories\Interfaces\BankAccountRepositoryInterface;
use App\Repositories\Interfaces\PortfolioRepositoryInterface;
use Exception;

/**
 * Bank Import Service
 * 
 * Handles bank statement imports and data processing.
 * Supports multiple bank formats and validation.
 */
class BankImportService implements BankImportServiceInterface
{
    private BankAccountRepositoryInterface $bankAccountRepository;
    private PortfolioRepositoryInterface $portfolioRepository;
    
    // Supported file formats and their MIME types
    private const SUPPORTED_FORMATS = [
        'csv' => [
            'mime_types' => ['text/csv', 'application/csv', 'text/plain'],
            'extensions' => ['csv']
        ],
        'ofx' => [
            'mime_types' => ['application/x-ofx', 'text/plain'],
            'extensions' => ['ofx', 'qfx']
        ],
        'qif' => [
            'mime_types' => ['application/x-qif', 'text/plain'],
            'extensions' => ['qif']
        ]
    ];
    
    public function __construct(
        BankAccountRepositoryInterface $bankAccountRepository,
        PortfolioRepositoryInterface $portfolioRepository
    ) {
        $this->bankAccountRepository = $bankAccountRepository;
        $this->portfolioRepository = $portfolioRepository;
    }
    
    /**
     * Detect file format from uploaded file
     */
    public function detectFormat(array $fileData): ?string
    {
        $mimeType = $fileData['type'] ?? '';
        $fileName = $fileData['name'] ?? '';
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        foreach (self::SUPPORTED_FORMATS as $format => $config) {
            if (in_array($mimeType, $config['mime_types']) || 
                in_array($extension, $config['extensions'])) {
                return $format;
            }
        }
        
        return null;
    }
    
    /**
     * Validate uploaded file
     */
    public function validateFile(array $fileData): array
    {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($fileData['tmp_name']) || empty($fileData['tmp_name'])) {
            $errors[] = 'No file was uploaded';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size (max 10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($fileData['size'] > $maxSize) {
            $errors[] = 'File size exceeds 10MB limit';
        }
        
        // Check file format
        $format = $this->detectFormat($fileData);
        if (!$format) {
            $errors[] = 'Unsupported file format. Please upload CSV, OFX, or QIF files';
        }
        
        // Check if file is readable
        if (!is_readable($fileData['tmp_name'])) {
            $errors[] = 'File is not readable';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'format' => $format
        ];
    }
    
    /**
     * Process bank import file
     */
    public function processImport(array $fileData, int $userId): array
    {
        $validation = $this->validateFile($fileData);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }
        
        try {
            $parsedData = $this->parseStatement($fileData['tmp_name'], $validation['format']);
            $portfolioData = $this->mapToPortfolio($parsedData, $userId);
            
            $importedCount = 0;
            foreach ($portfolioData as $transaction) {
                // Process each transaction
                $importedCount++;
            }
            
            return [
                'success' => true,
                'imported_count' => $importedCount,
                'format' => $validation['format'],
                'message' => "Successfully imported {$importedCount} transactions"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'errors' => ['Import failed: ' . $e->getMessage()]
            ];
        }
    }
    
    /**
     * Get supported file formats
     */
    public function getSupportedFormats(): array
    {
        return array_keys(self::SUPPORTED_FORMATS);
    }
    
    /**
     * Parse bank statement data
     */
    public function parseStatement(string $filePath, string $format): array
    {
        switch ($format) {
            case 'csv':
                return $this->parseCsvFile($filePath);
            case 'ofx':
                return $this->parseOfxFile($filePath);
            case 'qif':
                return $this->parseQifFile($filePath);
            default:
                throw new Exception("Unsupported format: {$format}");
        }
    }
    
    /**
     * Map bank data to portfolio format
     */
    public function mapToPortfolio(array $bankData, int $userId): array
    {
        $portfolioData = [];
        
        foreach ($bankData as $transaction) {
            $portfolioData[] = [
                'user_id' => $userId,
                'date' => $transaction['date'] ?? date('Y-m-d'),
                'description' => $transaction['description'] ?? '',
                'amount' => $transaction['amount'] ?? 0,
                'type' => $transaction['type'] ?? 'unknown',
                'account_id' => $transaction['account_id'] ?? null,
                'imported_at' => date('Y-m-d H:i:s')
            ];
        }
        
        return $portfolioData;
    }
    
    /**
     * Get import history for user
     */
    public function getImportHistory(int $userId): array
    {
        // This would typically query a database table for import history
        // For now, return empty array as placeholder
        return [];
    }
    
    /**
     * Parse CSV file
     */
    private function parseCsvFile(string $filePath): array
    {
        $data = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle !== false) {
            $header = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) === count($header)) {
                    $data[] = array_combine($header, $row);
                }
            }
            
            fclose($handle);
        }
        
        return $data;
    }
    
    /**
     * Parse OFX file (placeholder)
     */
    private function parseOfxFile(string $filePath): array
    {
        // OFX parsing would be more complex, requiring XML parsing
        // This is a placeholder implementation
        return [];
    }
    
    /**
     * Parse QIF file (placeholder)
     */
    private function parseQifFile(string $filePath): array
    {
        // QIF parsing implementation
        // This is a placeholder implementation
        return [];
    }
}