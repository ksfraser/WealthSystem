<?php
require_once __DIR__ . '/TransactionParserInterface.php';

use InvalidArgumentException;

/**
 * MidCap Transaction Parser
 *
 * Parses MidCap bank CSV files (both holdings and transactions) and converts them 
 * to standardized format. Follows Single Responsibility Principle.
 *
 * @startuml MidCapParser
 * class MidCapParser implements TransactionParserInterface {
 *   -EXPECTED_HOLDINGS_HEADERS: array
 *   -EXPECTED_TRANSACTIONS_HEADERS: array
 *   +canParse(lines: array): bool
 *   +parse(lines: array): array
 *   +getParserName(): string
 *   -isHoldingsFile(header: array): bool
 *   -isTransactionsFile(header: array): bool
 *   -transformToStandardFormat(data: array): array
 *   -getAliasedValue(row: array, aliases: array, default: mixed): mixed
 * }
 * @enduml
 */
class MidCapParser implements TransactionParserInterface {

    /**
     * Expected headers for holdings files (case-insensitive)
     */
    private const EXPECTED_HOLDINGS_HEADERS = ['symbol', 'shares', 'price', 'market_value'];
    
    /**
     * Expected headers for transaction files (case-insensitive)
     */
    private const EXPECTED_TRANSACTIONS_HEADERS = ['date', 'symbol', 'type', 'shares', 'price', 'amount'];

    /**
     * {@inheritdoc}
     */
    public function canParse(array $lines): bool {
        if (empty($lines) || !isset($lines[0]) || !is_array($lines[0])) {
            return false;
        }

        $firstHeader = array_map('strtolower', array_map('trim', $lines[0]));
        
        return $this->isHoldingsFile($firstHeader) || $this->isTransactionsFile($firstHeader);
    }

    /**
     * {@inheritdoc}
     */
    public function parse(array $lines): array {
        if (!$this->canParse($lines)) {
            throw new InvalidArgumentException('Invalid MidCap CSV format');
        }

        if (empty($lines)) {
            return [];
        }

        $transactions = [];
        $header = array_map('strtolower', array_map('trim', $lines[0]));

        // Process data rows (skip header)
        $lineCount = count($lines);
        for ($i = 1; $i < $lineCount; $i++) {
            $row = $lines[$i];
            
            if (empty($row) || count($row) !== count($header)) {
                continue; // Skip empty or malformed rows
            }
            
            // Skip rows where all values are empty
            if (empty(trim(implode('', $row)))) {
                continue;
            }
            
            $transactionData = array_combine($header, $row);
            
            if ($transactionData === false) {
                continue; // Skip if combine failed
            }
            
            $transactions[] = $this->transformToStandardFormat($transactionData);
        }

        return $transactions;
    }

    /**
     * {@inheritdoc}
     */
    public function getParserName(): string {
        return 'MidCap Transaction/Holdings Parser';
    }

    /**
     * Check if header indicates holdings file
     *
     * @param array $header Normalized header array
     * @return bool True if holdings file
     */
    private function isHoldingsFile(array $header): bool {
        $matchCount = count(array_intersect(self::EXPECTED_HOLDINGS_HEADERS, $header));
        return $matchCount >= 3; // At least 3 out of 4 expected headers
    }

    /**
     * Check if header indicates transactions file
     *
     * @param array $header Normalized header array
     * @return bool True if transactions file
     */
    private function isTransactionsFile(array $header): bool {
        $matchCount = count(array_intersect(self::EXPECTED_TRANSACTIONS_HEADERS, $header));
        return $matchCount >= 4; // At least 4 out of 6 expected headers
    }

    /**
     * Transform MidCap data to standardized transaction format
     *
     * @param array $data Raw MidCap transaction/holdings data
     * @return array Standardized transaction data
     */
    private function transformToStandardFormat(array $data): array {
        $symbol = $this->getAliasedValue($data, ['symbol', 'ticker', 'stock_symbol'], '');
        $date = $this->getAliasedValue($data, ['date', 'transaction_date', 'txn_date'], date('Y-m-d'));
        $qty = $this->getAliasedValue($data, ['shares', 'quantity'], 0);
        $price = $this->getAliasedValue($data, ['price', 'current_price'], 0);
        $amount = $this->getAliasedValue($data, ['amount', 'total', 'market_value'], 0);
        
        // Calculate amount if missing but we have quantity and price
        if (empty($amount) && !empty($qty) && !empty($price)) {
            $amount = (float)$qty * (float)$price;
        }

        return [
            'tran_date' => date('Y-m-d', strtotime($date)),
            'stock_symbol' => trim($symbol),
            'tran_type' => $this->getAliasedValue($data, ['type', 'transaction_type', 'txn_type'], 'Holdings'),
            'quantity' => (float) $qty,
            'price' => (float) $price,
            'amount' => (float) $amount,
            'description' => $this->getAliasedValue($data, ['description'], 'Imported from MidCap'),
        ];
    }

    /**
     * Get value from data array using multiple possible key aliases
     *
     * @param array $row Data row
     * @param array $aliases Array of possible key names
     * @param mixed $default Default value if no alias found
     * @return mixed Found value or default
     */
    private function getAliasedValue(array $row, array $aliases, $default = null) {
        foreach ($aliases as $alias) {
            if (isset($row[$alias]) && $row[$alias] !== '') {
                return $row[$alias];
            }
        }
        return $default;
    }
}
