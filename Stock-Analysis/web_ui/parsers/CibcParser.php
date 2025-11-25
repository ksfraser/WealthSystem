<?php
require_once __DIR__ . '/TransactionParserInterface.php';

use InvalidArgumentException;

/**
 * CIBC Transaction Parser
 *
 * Parses CIBC bank CSV transaction files and converts them to standardized format.
 * Follows Single Responsibility Principle - only handles CIBC-specific parsing logic.
 *
 * @startuml CibcParser
 * class CibcParser implements TransactionParserInterface {
 *   -EXPECTED_HEADERS: array
 *   -SYMBOL_MAPPINGS: array
 *   +canParse(lines: array): bool
 *   +parse(lines: array): array
 *   +getParserName(): string
 *   -findHeaderRowIndex(lines: array): int
 *   -transformToStandardFormat(data: array): array
 *   -extractSymbolFromDescription(description: string): string
 *   -cleanNumericValue(value: string): float
 * }
 * @enduml
 */
class CibcParser implements TransactionParserInterface {
    
    /**
     * Expected CIBC CSV header columns
     */
    private const EXPECTED_HEADERS = [
        'Transaction Date',
        'Settlement Date', 
        'Currency of Sub-account Held In',
        'Transaction Type',
        'Symbol',
        'Market',
        'Description',
        'Quantity',
        'Currency of Price',
        'Price',
        'Commission',
        'Exchange Rate',
        'Currency of Amount',
        'Amount',
        'Settlement Instruction',
        'Exchange Rate (Canadian Equivalent)',
        'Canadian Equivalent'
    ];

    /**
     * Known symbol mappings for descriptions that don't contain clear symbols
     */
    private const SYMBOL_MAPPINGS = [
        'BOSTON PIZZA' => 'BPF.UN',
        'SIR ROYALTY' => 'SRV.UN',
        'ROYAL BANK' => 'RY',
        'KEG ROYALTIES' => 'KEG.UN',
        'PIZZA PIZZA' => 'PZA',
        'MTY FOOD' => 'MTY',
        'CANADIAN IMPERIAL BANK' => 'CM',
        'ISHARES S&P/TSX CANADIAN DIVIDEND' => 'CDZ'
    ];

    /**
     * {@inheritdoc}
     */
    public function canParse(array $lines): bool {
        if (empty($lines)) {
            return false;
        }

        // Look for CIBC-specific header pattern in first 20 lines
        foreach ($lines as $index => $line) {
            if ($index > 20) break; // Don't search too far
            
            if (!is_array($line)) continue;
            
            $trimmedLine = array_map('trim', $line);
            
            // Check if this line contains CIBC header pattern
            if (in_array('Transaction Date', $trimmedLine) && 
                in_array('Settlement Date', $trimmedLine) &&
                in_array('Transaction Type', $trimmedLine)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(array $lines): array {
        if (!$this->canParse($lines)) {
            throw new InvalidArgumentException('Invalid CIBC CSV format');
        }

        $transactions = [];
        $headerIndex = $this->findHeaderRowIndex($lines);
        
        if ($headerIndex === -1) {
            return []; // No valid header found
        }

        $header = array_map('trim', $lines[$headerIndex]);

        // Process data rows after header
        $lineCount = count($lines);
        for ($i = $headerIndex + 1; $i < $lineCount; $i++) {
            $row = $lines[$i];
            
            if (empty($row)) {
                continue; // Skip empty rows
            }

            // Pad row to match header length (CIBC files can have variable column counts)
            $headerCount = count($header);
            while (count($row) < $headerCount) {
                $row[] = '';
            }
            
            // Truncate if row is longer than header
            if (count($row) > count($header)) {
                $row = array_slice($row, 0, count($header));
            }

            $transactionData = array_combine($header, $row);

            // Skip empty rows (all fields are empty) - check this first
            if (empty(trim(implode('', $row)))) {
                continue;
            }

            // Validate required fields
            if (empty(trim($transactionData['Transaction Date'] ?? '')) || 
                empty(trim($transactionData['Transaction Type'] ?? ''))) {
                continue;
            }

            $transactions[] = $this->transformToStandardFormat($transactionData);
        }

        return $transactions;
    }

    /**
     * {@inheritdoc}
     */
    public function getParserName(): string {
        return 'CIBC Transaction Parser';
    }

    /**
     * Find the index of the header row in the CSV lines
     *
     * @param array $lines CSV lines
     * @return int Header row index, or -1 if not found
     */
    private function findHeaderRowIndex(array $lines): int {
        foreach ($lines as $index => $line) {
            if ($index > 20) break; // Don't search too far
            
            if (!is_array($line)) continue;
            
            $trimmedLine = array_map('trim', $line);
            
            if (in_array('Transaction Date', $trimmedLine) && 
                in_array('Settlement Date', $trimmedLine)) {
                return $index;
            }
        }

        return -1;
    }

    /**
     * Transform CIBC data to standardized transaction format
     *
     * @param array $cibcData Raw CIBC transaction data
     * @return array Standardized transaction data
     */
    private function transformToStandardFormat(array $cibcData): array {
        $symbol = trim($cibcData['Symbol'] ?? '');
        
        // Extract symbol from description if not present in symbol field
        if (empty($symbol)) {
            $symbol = $this->extractSymbolFromDescription($cibcData['Description'] ?? '');
        }

        return [
            'tran_date' => date('Y-m-d', strtotime(trim($cibcData['Transaction Date']))),
            'stock_symbol' => $symbol,
            'tran_type' => trim($cibcData['Transaction Type']),
            'quantity' => $this->cleanNumericValue($cibcData['Quantity'] ?? '0'),
            'price' => $this->cleanNumericValue($cibcData['Price'] ?? '0'),
            'amount' => $this->cleanNumericValue($cibcData['Amount'] ?? '0'),
            'description' => trim($cibcData['Description'] ?? ''),
        ];
    }

    /**
     * Extract stock symbol from transaction description
     *
     * @param string $description Transaction description
     * @return string Stock symbol or empty string if not found
     */
    private function extractSymbolFromDescription(string $description): string {
        // Check known mappings first
        foreach (self::SYMBOL_MAPPINGS as $keyword => $symbol) {
            if (stripos($description, $keyword) !== false) {
                return $symbol;
            }
        }

        // Look for ticker patterns (2-5 uppercase letters, optionally with .XX suffix)
        if (preg_match('/\b([A-Z]{2,5}(?:\.[A-Z]{1,3})?)\b/', $description, $matches)) {
            // Filter out common words that aren't symbols
            $nonSymbols = ['ETF', 'COM', 'UNIT', 'INC', 'CORP', 'TRUST', 'FUND', 'DIV', 'PAY', 'MONEY', 'MARKET', 'CASH', 'FUND'];
            if (!in_array($matches[1], $nonSymbols)) {
                return $matches[1];
            }
        }

        return '';
    }

    /**
     * Clean and convert string to numeric value
     *
     * @param string $value Numeric string potentially with commas
     * @return float Cleaned numeric value
     */
    private function cleanNumericValue(string $value): float {
        return (float) str_replace([',', ' '], '', trim($value));
    }
}
