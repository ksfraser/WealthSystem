<?php

namespace App;

use DateTime;

require_once __DIR__ . '/CoreInterfaces.php';

/**
 * CSV file validator
 */
class CsvFileValidator implements ValidatorInterface
{
    private $requiredColumns;
    private $logger;

    public function __construct(array $requiredColumns, LoggerInterface $logger)
    {
        $this->requiredColumns = $requiredColumns;
        $this->logger = $logger;
    }

    public function validate($csvData)
    {
        $errors = [];
        $warnings = [];

        if (empty($csvData)) {
            $errors[] = 'CSV data is empty';
            return new ValidationResult(false, $errors, $warnings);
        }

        // Check if we have required columns
        $firstRow = $csvData[0];
        $availableColumns = array_keys($firstRow);
        
        foreach ($this->requiredColumns as $required) {
            if (!in_array($required, $availableColumns)) {
                $errors[] = "Required column '$required' not found";
            }
        }

        // Check for data quality issues
        $rowCount = count($csvData);
        $emptyRowCount = 0;
        
        foreach ($csvData as $index => $row) {
            $rowNumber = $index + 1;
            
            // Check for completely empty rows
            if (empty(array_filter($row))) {
                $emptyRowCount++;
                continue;
            }

            // Check for missing required data
            foreach ($this->requiredColumns as $column) {
                if (isset($row[$column]) && (trim($row[$column]) === '' || $row[$column] === null)) {
                    $warnings[] = "Row $rowNumber: Missing value for required column '$column'";
                }
            }
        }

        if ($emptyRowCount > 0) {
            $warnings[] = "Found $emptyRowCount empty rows";
        }

        if ($rowCount - $emptyRowCount < 1) {
            $errors[] = 'No valid data rows found';
        }

        $isValid = empty($errors);
        
        $this->logger->info('CSV validation completed', [
            'valid' => $isValid,
            'total_rows' => $rowCount,
            'empty_rows' => $emptyRowCount,
            'errors' => count($errors),
            'warnings' => count($warnings)
        ]);

        return new ValidationResult($isValid, $errors, $warnings);
    }
}

/**
 * Transaction data validator
 */
class TransactionDataValidator implements ValidatorInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function validate($transactionData)
    {
        $errors = [];
        $warnings = [];

        // Required fields for transaction
        $requiredFields = ['symbol', 'shares', 'price', 'txn_date'];
        
        foreach ($requiredFields as $field) {
            if (!isset($transactionData[$field]) || trim($transactionData[$field]) === '') {
                $errors[] = "Missing required field: $field";
            }
        }

        // Validate numeric fields
        if (isset($transactionData['shares'])) {
            if (!is_numeric($transactionData['shares']) || $transactionData['shares'] <= 0) {
                $errors[] = 'Shares must be a positive number';
            }
        }

        if (isset($transactionData['price'])) {
            if (!is_numeric($transactionData['price']) || $transactionData['price'] <= 0) {
                $errors[] = 'Price must be a positive number';
            }
        }

        // Validate date
        if (isset($transactionData['txn_date'])) {
            $date = DateTime::createFromFormat('Y-m-d', $transactionData['txn_date']);
            if (!$date) {
                // Try other common formats
                $date = DateTime::createFromFormat('m/d/Y', $transactionData['txn_date']);
                if (!$date) {
                    $date = DateTime::createFromFormat('d/m/Y', $transactionData['txn_date']);
                }
                if (!$date) {
                    $errors[] = 'Transaction date is not in a valid format (expected Y-m-d, m/d/Y, or d/m/Y)';
                } else {
                    $warnings[] = 'Date format converted from ' . $transactionData['txn_date'] . ' to ' . $date->format('Y-m-d');
                }
            }
        }

        // Validate symbol
        if (isset($transactionData['symbol'])) {
            if (strlen($transactionData['symbol']) > 10) {
                $warnings[] = 'Symbol is unusually long (>10 characters)';
            }
            if (!preg_match('/^[A-Z0-9.\-]+$/i', $transactionData['symbol'])) {
                $warnings[] = 'Symbol contains unusual characters';
            }
        }

        $isValid = empty($errors);
        
        return new ValidationResult($isValid, $errors, $warnings);
    }
}
