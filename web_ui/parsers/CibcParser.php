<?php
require_once __DIR__ . '/TransactionParserInterface.php';

class CibcParser implements TransactionParserInterface {
    
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

    public function validate(array $headers, array $sampleRow): bool {
        // In CIBC files, the actual header is on a specific row, not the first.
        // We will find the row that matches our expected headers.
        foreach($headers as $headerRow) {
            $trimmedHeaders = array_map('trim', $headerRow);
            if ($trimmedHeaders === self::EXPECTED_HEADERS) {
                return true;
            }
        }
        return false;
    }

    public function parse(string $filePath): array {
        $transactions = [];
        $file = fopen($filePath, 'r');
        if (!$file) {
            throw new Exception("Could not open file: {$filePath}");
        }

        $skipRows = $this->getHeaderRowSkipCount($filePath);
        for ($i = 0; $i < $skipRows; $i++) {
            fgetcsv($file); // Skip header lines
        }
        
        // Read header row
        $header = fgetcsv($file);
        $header = array_map('trim', $header);

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < count($header) || empty(trim(implode('', $row)))) {
                continue; // Skip empty or malformed rows
            }
            
            $transactionData = array_combine($header, $row);

            // Basic data integrity check
            if (empty($transactionData['Transaction Date']) || empty($transactionData['Transaction Type'])) {
                continue;
            }

            $transactions[] = $this->transformToStandardFormat($transactionData);
        }

        fclose($file);
        return $transactions;
    }

    public function getHeaderRowSkipCount(string $filePath): int {
        $file = fopen($filePath, 'r');
        if (!$file) {
            return 0;
        }

        $rowCount = 0;
        while (($row = fgetcsv($file)) !== false) {
            $rowCount++;
            $trimmedRow = array_map('trim', $row);
            if (in_array('Transaction Date', $trimmedRow) && in_array('Settlement Date', $trimmedRow)) {
                fclose($file);
                return $rowCount -1; // Return the number of rows *before* the header
            }
            if ($rowCount > 20) { // Safety break
                break;
            }
        }

        fclose($file);
        return 9; // Default if not found
    }

    private function transformToStandardFormat(array $cibcData): array {
        // Extract symbol from description if not present in symbol field
        $symbol = trim($cibcData['Symbol']);
        if (empty($symbol)) {
            $symbol = $this->extractSymbolFromDescription($cibcData['Description']);
        }

        return [
            'tran_date' => date('Y-m-d', strtotime(trim($cibcData['Transaction Date']))),
            'stock_symbol' => $symbol,
            'tran_type' => trim($cibcData['Transaction Type']),
            'quantity' => (float) str_replace(',', '', trim($cibcData['Quantity'])),
            'price' => (float) str_replace(',', '', trim($cibcData['Price'])),
            'amount' => (float) str_replace(',', '', trim($cibcData['Amount'])),
            'description' => trim($cibcData['Description']),
        ];
    }

    private function extractSymbolFromDescription(string $description): string {
        // Example: "ISHARES S&P/TSX CANADIAN DIVIDEND ARISTOCRATS INDEX ETF COM UNIT"
        // Look for common patterns like stock tickers (e.g., 3-5 uppercase letters)
        if (preg_match('/\b([A-Z]{2,5}(\.[A-Z]{2})?)\b/', $description, $matches)) {
            // Check against a list of common non-symbols to avoid false positives
            $nonSymbols = ['ETF', 'COM', 'UNIT', 'INC', 'CORP', 'TRUST', 'FUND'];
            if (!in_array($matches[1], $nonSymbols)) {
                return $matches[1];
            }
        }
        
        // Fallback for specific known descriptions
        if (strpos($description, 'BOSTON PIZZA') !== false) return 'BPF.UN';
        if (strpos($description, 'SIR ROYALTY') !== false) return 'SRV.UN';
        if (strpos($description, 'ROYAL BANK') !== false) return 'RY';
        if (strpos($description, 'KEG ROYALTIES') !== false) return 'KEG.UN';
        if (strpos($description, 'PIZZA PIZZA') !== false) return 'PZA';
        if (strpos($description, 'MTY FOOD') !== false) return 'MTY';
        if (strpos($description, 'CANADIAN IMPERIAL BANK') !== false) return 'CM';
        if (strpos($description, 'ISHARES S&P/TSX CANADIAN DIVIDEND') !== false) return 'CDZ';

        return ''; // Return empty if no symbol can be reliably extracted
    }
}
